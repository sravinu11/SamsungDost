<?php

$QUAL_EXPR = "CASE
    WHEN UPPER(REPLACE(TRIM(qualification),' ','')) = '12TH' THEN '12th'
    WHEN UPPER(REPLACE(TRIM(qualification),' ','')) = '12TH+DIPLOMA' THEN '12th + Diploma'
    WHEN UPPER(REPLACE(TRIM(qualification),' ','')) = 'DIPLOMA' THEN 'Diploma'
    WHEN UPPER(REPLACE(TRIM(qualification),' ','')) IN ('UNDERGRADUATE','UG') THEN 'Under Graduate'
    WHEN UPPER(REPLACE(TRIM(qualification),' ','')) IN ('GRADUATE','GRAD') THEN 'Graduate'
    WHEN UPPER(REPLACE(TRIM(qualification),' ','')) IN ('POSTGRADUATE','PG') THEN 'Post Graduate'
    ELSE INITCAP(TRIM(qualification))
END";

$GENDER_EXPR = "CASE
    WHEN UPPER(LEFT(TRIM(gender),1)) = 'M' THEN 'Male'
    WHEN UPPER(LEFT(TRIM(gender),1)) = 'F' THEN 'Female'
    ELSE 'Unknown'
END";

$DIMS = [
    'business'      => "TRIM(business_type)",
    'partner'       => "TRIM(implementation_partner)",
    'vertical'      => "TRIM(vertical_type)",
    'region'        => "INITCAP(TRIM(region))",
    'state'         => "INITCAP(TRIM(state_name))",
    'qualification' => $QUAL_EXPR,
];

/* Every dimension supports one or more values — the client sends them
   comma-separated (?business=MX,DA). A single value works the same way. */
function activeFilters(array $DIMS): array {
    $filters = [];
    foreach (array_keys($DIMS) as $dim) {
        if (!isset($_GET[$dim]) || $_GET[$dim] === '') continue;
        $vals = array_values(array_filter(array_map('trim', explode(',', $_GET[$dim])), fn($v) => $v !== ''));
        if ($vals) $filters[$dim] = $vals;
    }
    return $filters;
}

function whereClause(array $DIMS, array $filters, ?string $exclude): array {
    $conds = [];
    $params = [];
    foreach ($DIMS as $dim => $expr) {
        if ($dim === $exclude) continue;
        if (empty($filters[$dim])) continue;
        $placeholders = [];
        foreach ($filters[$dim] as $i => $val) {
            $key = ":f_{$dim}_{$i}";
            $placeholders[] = $key;
            $params[$key] = $val;
        }
        $conds[] = "$expr IN (" . implode(",", $placeholders) . ")";
    }
    $sql = $conds ? ("WHERE " . implode(" AND ", $conds)) : "";
    return [$sql, $params];
}
