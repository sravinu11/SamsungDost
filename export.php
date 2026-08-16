<?php

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/filters.php";
require_once __DIR__ . "/auth.php";
require_login();

$activeFilters = apply_role_lock(activeFilters($DIMS));
$pdo = get_pdo();

[$whereSql, $params] = whereClause($DIMS, $activeFilters, null);

$cols = $ALL_COLUMNS;
$colList = implode(", ", array_map(fn($c) => "\"$c\"", $cols));

$stmt = $pdo->prepare("SELECT $colList FROM dost2026 $whereSql ORDER BY NULLIF(sr_no,'')::int NULLS LAST");
$stmt->execute($params);

$filename = "ojt_candidates_" . date("Y-m-d_His") . ".csv";
header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

$out = fopen("php://output", "w");
fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel renders non-ASCII correctly
fputcsv($out, array_map(fn($c) => ucwords(str_replace('_', ' ', $c)), $cols));
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    fputcsv($out, $row);
}
fclose($out);
