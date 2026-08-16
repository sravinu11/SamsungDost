<?php

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/filters.php";
require_once __DIR__ . "/auth.php";
require_login_json();
header("Content-Type: application/json");

$activeFilters = apply_role_lock(activeFilters($DIMS));
$pdo = get_pdo();

/* ---- filter options (cascading: each dim excludes its own filter) ---- */
$filterOptions = [];
foreach ($DIMS as $dim => $expr) {
    [$whereSql, $params] = whereClause($DIMS, $activeFilters, $dim);
    $sql = "SELECT $expr AS label, COUNT(*) AS c FROM dost2026 $whereSql
            GROUP BY 1 HAVING $expr IS NOT NULL AND $expr <> '' ORDER BY 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $filterOptions[$dim] = $stmt->fetchAll();
}
if ($_SESSION['role'] !== 'ALL') {
    $filterOptions['partner'] = array_values(array_filter(
        $filterOptions['partner'],
        fn($o) => strtoupper($o['label']) === $_SESSION['role']
    ));
}

/* ---- main filtered aggregates ---- */
[$whereSql, $params] = whereClause($DIMS, $activeFilters, null);

function q($pdo, $sql, $params) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

$total = (int) $pdo->query("SELECT COUNT(*) FROM dost2026")->fetchColumn();

$kpiRow = q($pdo, "SELECT
        COUNT(*) AS n,
        AVG(NULLIF(age_in_years,'')::numeric) AS avg_age,
        AVG(NULLIF(ojt_duration,'')::numeric) AS avg_duration,
        100.0 * SUM(CASE WHEN UPPER(TRIM(nhit_test_yn)) = 'YES' THEN 1 ELSE 0 END) / GREATEST(COUNT(*),1) AS nhit_pct,
        100.0 * SUM(CASE WHEN $GENDER_EXPR = 'Male' THEN 1 ELSE 0 END) / GREATEST(COUNT(*),1) AS male_pct,
        100.0 * SUM(CASE WHEN $GENDER_EXPR = 'Female' THEN 1 ELSE 0 END) / GREATEST(COUNT(*),1) AS female_pct
    FROM dost2026 $whereSql", $params)[0];

$gender = q($pdo, "SELECT $GENDER_EXPR AS name, COUNT(*) AS count FROM dost2026 $whereSql GROUP BY 1 ORDER BY count DESC", $params);

$ageBucketExpr = "CASE
    WHEN NULLIF(age_in_years,'')::int BETWEEN 18 AND 20 THEN '18-20'
    WHEN NULLIF(age_in_years,'')::int BETWEEN 21 AND 23 THEN '21-23'
    WHEN NULLIF(age_in_years,'')::int BETWEEN 24 AND 26 THEN '24-26'
    WHEN NULLIF(age_in_years,'')::int BETWEEN 27 AND 29 THEN '27-29'
    ELSE 'Other'
END";
$ageRaw = q($pdo, "SELECT $ageBucketExpr AS name, COUNT(*) AS count FROM dost2026 $whereSql GROUP BY 1", $params);
$ageMap = [];
foreach ($ageRaw as $r) { $ageMap[$r['name']] = (int)$r['count']; }
$ageBuckets = [];
foreach (['18-20', '21-23', '24-26', '27-29'] as $b) {
    $ageBuckets[] = ['name' => $b, 'count' => $ageMap[$b] ?? 0];
}

$qualification = q($pdo, "SELECT $QUAL_EXPR AS name, COUNT(*) AS count FROM dost2026 $whereSql GROUP BY 1 ORDER BY count DESC", $params);
$region = q($pdo, "SELECT INITCAP(TRIM(region)) AS name, COUNT(*) AS count FROM dost2026 $whereSql GROUP BY 1 ORDER BY count DESC", $params);
$business = q($pdo, "SELECT TRIM(business_type) AS name, COUNT(*) AS count FROM dost2026 $whereSql GROUP BY 1 ORDER BY count DESC", $params);
$partner = q($pdo, "SELECT TRIM(implementation_partner) AS name, COUNT(*) AS count FROM dost2026 $whereSql GROUP BY 1 ORDER BY count DESC", $params);

$stateAll = q($pdo, "SELECT INITCAP(TRIM(state_name)) AS name, COUNT(*) AS count FROM dost2026 $whereSql GROUP BY 1 ORDER BY count DESC", $params);
$state = array_slice($stateAll, 0, 12);
$stateOther = array_sum(array_map(fn($r) => (int)$r['count'], array_slice($stateAll, 12)));
if ($stateOther > 0) $state[] = ['name' => 'Other', 'count' => $stateOther];

/* duration filter (ojt_duration IS NOT NULL) has to merge into $whereSql's own WHERE/no-WHERE case: */
$durSql = $whereSql === ""
    ? "SELECT NULLIF(ojt_duration,'')::int AS name, COUNT(*) AS count FROM dost2026 WHERE NULLIF(ojt_duration,'') IS NOT NULL GROUP BY 1 ORDER BY 1"
    : "SELECT NULLIF(ojt_duration,'')::int AS name, COUNT(*) AS count FROM dost2026 $whereSql AND NULLIF(ojt_duration,'') IS NOT NULL GROUP BY 1 ORDER BY 1";
$duration = q($pdo, $durSql, $params);

/* day-wise candidate intake, split by business_type, driven off ojt_start_date */
$intakeSql = $whereSql === ""
    ? "SELECT ojt_start_date AS d, to_char(ojt_start_date,'DD Mon') AS label, TRIM(business_type) AS business, COUNT(*) AS count
       FROM dost2026 WHERE ojt_start_date IS NOT NULL GROUP BY ojt_start_date, business_type ORDER BY ojt_start_date"
    : "SELECT ojt_start_date AS d, to_char(ojt_start_date,'DD Mon') AS label, TRIM(business_type) AS business, COUNT(*) AS count
       FROM dost2026 $whereSql AND ojt_start_date IS NOT NULL GROUP BY ojt_start_date, business_type ORDER BY ojt_start_date";
$intakeRows = q($pdo, $intakeSql, $params);
$intakeMap = [];
$intakeOrder = [];
foreach ($intakeRows as $r) {
    if (!isset($intakeMap[$r['label']])) { $intakeMap[$r['label']] = []; $intakeOrder[] = $r['label']; }
    $intakeMap[$r['label']][$r['business']] = (int) $r['count'];
}
$businessNames = array_map(fn($r) => $r['name'], $business);
$dailyIntake = [];
foreach ($intakeOrder as $label) {
    $entry = ['label' => $label];
    foreach ($businessNames as $bname) { $entry[$bname] = $intakeMap[$label][$bname] ?? 0; }
    $dailyIntake[] = $entry;
}

$topRegions = array_slice(array_map(fn($r) => $r['name'], $region), 0, 8);
$regionPartner = [];
if ($topRegions) {
    $ph = [];
    $rpParams = $params;
    foreach ($topRegions as $i => $rname) { $ph[] = ":rp$i"; $rpParams[":rp$i"] = $rname; }
    $rpWhere = $whereSql === "" ? "WHERE INITCAP(TRIM(region)) IN (" . implode(",", $ph) . ")"
                                 : "$whereSql AND INITCAP(TRIM(region)) IN (" . implode(",", $ph) . ")";
    $rpRows = q($pdo, "SELECT INITCAP(TRIM(region)) AS region, TRIM(implementation_partner) AS partner, COUNT(*) AS count
                        FROM dost2026 $rpWhere GROUP BY 1, 2", $rpParams);
    $rpMap = [];
    foreach ($rpRows as $r) { $rpMap[$r['region']][$r['partner']] = (int)$r['count']; }
    $partnerNames = array_map(fn($r) => $r['name'], $partner);
    foreach ($topRegions as $rname) {
        $entry = ['label' => $rname];
        foreach ($partnerNames as $pname) { $entry[$pname] = $rpMap[$rname][$pname] ?? 0; }
        $regionPartner[] = $entry;
    }
}

echo json_encode([
    'total' => $total,
    'filtered' => (int) $kpiRow['n'],
    'kpis' => [
        'avg_age'      => round((float) $kpiRow['avg_age'], 1),
        'avg_duration' => round((float) $kpiRow['avg_duration'], 1),
        'nhit_pct'     => round((float) $kpiRow['nhit_pct'], 1),
        'male_pct'     => round((float) $kpiRow['male_pct'], 1),
        'female_pct'   => round((float) $kpiRow['female_pct'], 1),
    ],
    'gender' => $gender,
    'ageBuckets' => $ageBuckets,
    'qualification' => $qualification,
    'region' => $region,
    'state' => $state,
    'business' => $business,
    'partner' => $partner,
    'duration' => $duration,
    'dailyIntake' => $dailyIntake,
    'businessNames' => $businessNames,
    'regionPartner' => $regionPartner,
    'partnerNames' => array_map(fn($r) => $r['name'], $partner),
    'filterOptions' => $filterOptions,
    'session' => ['username' => $_SESSION['username'], 'role' => $_SESSION['role'], 'displayName' => $_SESSION['display_name']],
], JSON_UNESCAPED_UNICODE);
