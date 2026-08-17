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
$colList = implode(", ", array_map(fn($c) => "\"$c\"", $cols));

/* COUNT(*) OVER() rides along with the page query so both the total and
   the rows come back in one round trip instead of two. */
$stmt = $pdo->prepare("SELECT $colList, COUNT(*) OVER() AS __total FROM dost2026 $whereSql
                        ORDER BY NULLIF(sr_no,'')::int NULLS LAST
                        LIMIT :limit OFFSET :offset");
foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
$stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$fetched = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($fetched) {
    $total = (int) $fetched[0]['__total'];
    $rows = array_map(function ($r) {
        unset($r['__total']);
        return array_values($r);
    }, $fetched);
} else {
    /* No rows on this page — either the filter matched nothing, or the
       page number is past the end. Either way, a plain count resolves it. */
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM dost2026 $whereSql");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();
    $rows = [];
}

echo json_encode([
    'total' => $total,
    'page' => $page,
    'pageSize' => $pageSize,
    'pages' => max(1, (int) ceil($total / $pageSize)),
    'columns' => $cols,
    'rows' => $rows,
], JSON_UNESCAPED_UNICODE);
