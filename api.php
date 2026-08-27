<?php

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/filters.php";
require_once __DIR__ . "/auth.php";
require_login_json();
header("Content-Type: application/json");

$activeFilters = apply_role_lock(activeFilters($DIMS));
$pdo = get_pdo();

/* ---- filter options (cascading: each dim excludes its own filter) ----
   All 6 dimensions are pulled in a single round trip via UNION ALL instead
   of one query per dimension — each Neon round trip costs ~250-300ms, so
   this alone saves the better part of a second per page load. */
$foParts = [];
$foParams = [];
foreach ($DIMS as $dim => $expr) {
    [$ws, $p] = whereClause($DIMS, $activeFilters, $dim);
    $foParts[] = "SELECT '$dim' AS dim, $expr AS label, COUNT(*) AS c
                  FROM dost2026 $ws GROUP BY 2 HAVING $expr IS NOT NULL AND $expr <> ''";
    foreach ($p as $k => $v) { $foParams[$k] = $v; }
}
$foStmt = $pdo->prepare(implode("\nUNION ALL\n", $foParts));
$foStmt->execute($foParams);
$filterOptions = array_fill_keys(array_keys($DIMS), []);
foreach ($foStmt->fetchAll() as $r) {
    $filterOptions[$r['dim']][] = ['label' => $r['label'], 'c' => (int) $r['c']];
}
foreach ($filterOptions as &$opts) {
    usort($opts, fn($a, $b) => strcmp($a['label'], $b['label']));
}
unset($opts);
if ($_SESSION['role'] !== 'ALL') {
    $filterOptions['partner'] = array_values(array_filter(
        $filterOptions['partner'],
        fn($o) => strtoupper($o['label']) === $_SESSION['role']
    ));
}

/* ---- main filtered aggregates ----
   This used to be ~13 separate queries (one round trip each). They all
   read from the same filtered row set, so they're now computed as CTEs
   off a single `base` CTE and fetched in one round trip, with each
   chart's rows packed as a JSON column. */
[$whereSql, $params] = whereClause($DIMS, $activeFilters, null);

$ageBucketExpr = "CASE
    WHEN NULLIF(age_in_years,'')::int BETWEEN 18 AND 20 THEN '18-20'
    WHEN NULLIF(age_in_years,'')::int BETWEEN 21 AND 23 THEN '21-23'
    WHEN NULLIF(age_in_years,'')::int BETWEEN 24 AND 26 THEN '24-26'
    WHEN NULLIF(age_in_years,'')::int BETWEEN 27 AND 29 THEN '27-29'
    ELSE 'Other'
END";

$sql = "
WITH base AS (
    SELECT * FROM dost2026 $whereSql
),
kpi AS (
    SELECT
        COUNT(*) AS n,
        AVG(NULLIF(age_in_years,'')::numeric) AS avg_age,
        AVG(NULLIF(ojt_duration,'')::numeric) AS avg_duration,
        100.0 * SUM(CASE WHEN UPPER(TRIM(nhit_test_yn)) = 'YES' THEN 1 ELSE 0 END) / GREATEST(COUNT(*),1) AS nhit_pct,
        100.0 * SUM(CASE WHEN $GENDER_EXPR = 'Male' THEN 1 ELSE 0 END) / GREATEST(COUNT(*),1) AS male_pct,
        100.0 * SUM(CASE WHEN $GENDER_EXPR = 'Female' THEN 1 ELSE 0 END) / GREATEST(COUNT(*),1) AS female_pct
    FROM base
),
gender_g AS (SELECT $GENDER_EXPR AS name, COUNT(*) AS count FROM base GROUP BY 1),
age_g AS (SELECT $ageBucketExpr AS name, COUNT(*) AS count FROM base GROUP BY 1),
qual_g AS (SELECT $QUAL_EXPR AS name, COUNT(*) AS count FROM base GROUP BY 1),
region_g AS (SELECT INITCAP(TRIM(region)) AS name, COUNT(*) AS count FROM base GROUP BY 1),
business_g AS (SELECT TRIM(business_type) AS name, COUNT(*) AS count FROM base GROUP BY 1),
partner_g AS (SELECT TRIM(implementation_partner) AS name, COUNT(*) AS count FROM base GROUP BY 1),
state_g AS (SELECT INITCAP(TRIM(state_name)) AS name, COUNT(*) AS count FROM base GROUP BY 1),
duration_g AS (SELECT NULLIF(ojt_duration,'')::int AS name, COUNT(*) AS count FROM base WHERE NULLIF(ojt_duration,'') IS NOT NULL GROUP BY 1),
intake_g AS (
    SELECT ojt_start_date AS d, TRIM(business_type) AS business, COUNT(*) AS count
    FROM base WHERE ojt_start_date IS NOT NULL GROUP BY 1, 2
),
canid_g AS (SELECT TRIM(business_type) AS name, COUNT(*) AS count FROM base WHERE UPPER(can_id_ekyc) LIKE 'CAN%' GROUP BY 1),
regionpartner_g AS (SELECT INITCAP(TRIM(region)) AS region, TRIM(implementation_partner) AS partner, COUNT(*) AS count FROM base GROUP BY 1, 2),
total_g AS (SELECT COUNT(*) AS c FROM dost2026)
SELECT
    (SELECT c FROM total_g) AS total,
    (SELECT row_to_json(k) FROM kpi k) AS kpi,
    (SELECT COALESCE(json_agg(g ORDER BY g.count DESC), '[]') FROM gender_g g) AS gender,
    (SELECT COALESCE(json_agg(a), '[]') FROM age_g a) AS age,
    (SELECT COALESCE(json_agg(q ORDER BY q.count DESC), '[]') FROM qual_g q) AS qualification,
    (SELECT COALESCE(json_agg(r ORDER BY r.count DESC), '[]') FROM region_g r) AS region,
    (SELECT COALESCE(json_agg(b ORDER BY b.count DESC), '[]') FROM business_g b) AS business,
    (SELECT COALESCE(json_agg(p ORDER BY p.count DESC), '[]') FROM partner_g p) AS partner,
    (SELECT COALESCE(json_agg(s ORDER BY s.count DESC), '[]') FROM state_g s) AS state,
    (SELECT COALESCE(json_agg(d ORDER BY d.name), '[]') FROM duration_g d) AS duration,
    (SELECT COALESCE(json_agg(json_build_object('date', i.d, 'business', i.business, 'count', i.count) ORDER BY i.d), '[]') FROM intake_g i) AS intake,
    (SELECT COALESCE(json_agg(c ORDER BY c.count DESC), '[]') FROM canid_g c) AS canid,
    (SELECT COALESCE(json_agg(rp), '[]') FROM regionpartner_g rp) AS regionpartner
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$agg = $stmt->fetch();

$total = (int) $agg['total'];
$kpiRow = json_decode($agg['kpi'], true) ?: ['n' => 0, 'avg_age' => null, 'avg_duration' => null, 'nhit_pct' => null, 'male_pct' => null, 'female_pct' => null];
$gender = json_decode($agg['gender'], true);
$ageRaw = json_decode($agg['age'], true);
$qualification = json_decode($agg['qualification'], true);
$region = json_decode($agg['region'], true);
$business = json_decode($agg['business'], true);
$partner = json_decode($agg['partner'], true);
$stateAll = json_decode($agg['state'], true);
$duration = json_decode($agg['duration'], true);
$intakeRows = json_decode($agg['intake'], true);
$canIdRows = json_decode($agg['canid'], true);
$regionPartnerRows = json_decode($agg['regionpartner'], true);

$ageMap = [];
foreach ($ageRaw as $r) { $ageMap[$r['name']] = (int) $r['count']; }
$ageBuckets = [];
foreach (['18-20', '21-23', '24-26', '27-29'] as $b) {
    $ageBuckets[] = ['name' => $b, 'count' => $ageMap[$b] ?? 0];
}

$state = array_slice($stateAll, 0, 12);
$stateOther = array_sum(array_map(fn($r) => (int) $r['count'], array_slice($stateAll, 12)));
if ($stateOther > 0) $state[] = ['name' => 'Other', 'count' => $stateOther];

$businessNames = array_map(fn($r) => $r['name'], $business);
$partnerNames = array_map(fn($r) => $r['name'], $partner);

/* Day-by-day bars stop being readable once a few weeks of intake pile up,
   so days are folded into ~10-day windows (1st-10th / 11th-20th / 21st-end
   of each month). The very first window starts at the earliest date in the
   data rather than the 1st/11th/21st boundary, and the window holding the
   most recent date is always left open-ended ("...to Till Date") since
   it's still accumulating new candidates. */
function ordinal(int $n): string {
    if ($n % 100 >= 11 && $n % 100 <= 13) return $n . 'th';
    return $n . (['th', 'st', 'nd', 'rd'][$n % 10] ?? 'th');
}
function decanKey(string $date): string {
    [$y, $m, $d] = array_map('intval', explode('-', $date));
    $decan = $d <= 10 ? 1 : ($d <= 20 ? 2 : 3);
    return sprintf('%04d-%02d-%d', $y, $m, $decan);
}
function decanBounds(string $key): array {
    [$y, $m, $decan] = array_map('intval', explode('-', $key));
    if ($decan === 1) return [1, 10, $y, $m];
    if ($decan === 2) return [11, 20, $y, $m];
    return [21, (int) date('t', mktime(0, 0, 0, $m, 1, $y)), $y, $m];
}

$bucketSums = [];
$bucketOrder = [];
$allDates = [];
foreach ($intakeRows as $r) {
    $date = $r['date'];
    $allDates[] = $date;
    $key = decanKey($date);
    if (!isset($bucketSums[$key])) { $bucketSums[$key] = []; $bucketOrder[] = $key; }
    $bucketSums[$key][$r['business']] = ($bucketSums[$key][$r['business']] ?? 0) + (int) $r['count'];
}
sort($bucketOrder);

$dailyIntake = [];
if ($allDates) {
    sort($allDates);
    $minDate = $allDates[0];
    $maxDate = end($allDates);
    $minKey = decanKey($minDate);
    $maxKey = decanKey($maxDate);

    foreach ($bucketOrder as $key) {
        [$startDay, $endDay, $y, $m] = decanBounds($key);
        $effectiveStart = ($key === $minKey) ? (int) substr($minDate, 8, 2) : $startDay;
        $mon = date('M', mktime(0, 0, 0, $m, 1, $y));
        $label = ($key === $maxKey)
            ? ordinal($effectiveStart) . " $mon to Till Date"
            : ordinal($effectiveStart) . " to " . ordinal($endDay) . " $mon";
        $entry = ['label' => $label];
        foreach ($businessNames as $bname) { $entry[$bname] = $bucketSums[$key][$bname] ?? 0; }
        $dailyIntake[] = $entry;
    }
}

$canIdMap = [];
foreach ($canIdRows as $r) { $canIdMap[$r['name']] = (int) $r['count']; }
$canIdByBusiness = [];
foreach ($businessNames as $bname) { $canIdByBusiness[] = ['name' => $bname, 'count' => $canIdMap[$bname] ?? 0]; }

$topRegions = array_slice(array_map(fn($r) => $r['name'], $region), 0, 8);
$rpMap = [];
foreach ($regionPartnerRows as $r) { $rpMap[$r['region']][$r['partner']] = (int) $r['count']; }
$regionPartner = [];
foreach ($topRegions as $rname) {
    $entry = ['label' => $rname];
    foreach ($partnerNames as $pname) { $entry[$pname] = $rpMap[$rname][$pname] ?? 0; }
    $regionPartner[] = $entry;
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
    'canIdByBusiness' => $canIdByBusiness,
    'partner' => $partner,
    'duration' => $duration,
    'dailyIntake' => $dailyIntake,
    'businessNames' => $businessNames,
    'regionPartner' => $regionPartner,
    'partnerNames' => $partnerNames,
    'filterOptions' => $filterOptions,
    'session' => ['username' => $_SESSION['username'], 'role' => $_SESSION['role'], 'displayName' => $_SESSION['display_name']],
], JSON_UNESCAPED_UNICODE);
