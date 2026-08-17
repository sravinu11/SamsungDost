<?php

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/filters.php";
require_once __DIR__ . "/auth.php";
require_login_json();
header("Content-Type: application/json");

$activeFilters = apply_role_lock(activeFilters($DIMS));
$pdo = get_pdo();

[$whereSql, $params] = whereClause($DIMS, $activeFilters, null);

$page = max(1, (int) ($_GET['page'] ?? 1));
$pageSize = min(200, max(10, (int) ($_GET['pageSize'] ?? 50)));
$offset = ($page - 1) * $pageSize;

$cols = get_table_columns($pdo, 'dost2026');

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM dost2026 $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$colList = implode(", ", array_map(fn($c) => "\"$c\"", $cols));
$stmt = $pdo->prepare("SELECT $colList FROM dost2026 $whereSql
                        ORDER BY NULLIF(sr_no,'')::int NULLS LAST
                        LIMIT :limit OFFSET :offset");
foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
$stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_NUM);

echo json_encode([
    'total' => $total,
    'page' => $page,
    'pageSize' => $pageSize,
    'pages' => max(1, (int) ceil($total / $pageSize)),
    'columns' => $cols,
    'rows' => $rows,
], JSON_UNESCAPED_UNICODE);
