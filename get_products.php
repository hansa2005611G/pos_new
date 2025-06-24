<?php
require_once '../db.php';

$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;
$q = trim($_GET['q'] ?? '');

$where = [];
$params = [];
if ($q) {
    $where[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ? OR c.name LIKE ? OR s.name LIKE ?)";
    $params = array_fill(0, 5, "%$q%");
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Total count for pagination
$total_sql = "
    SELECT COUNT(*) FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN suppliers s ON p.supplier_id = s.id
    $where_sql
";
$total_stmt = $pdo->prepare($total_sql);
$total_stmt->execute($params);
$count = $total_stmt->fetchColumn();

// Fetch paginated products with joins
$sql = "
    SELECT
        p.id, p.name, p.sku, p.barcode, p.stock, p.reorder_level, p.cost, p.price,
        c.name AS category,
        s.name AS supplier,
        u.name AS unit, u.abbreviation AS unit_abbr
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN suppliers s ON p.supplier_id = s.id
    LEFT JOIN units u ON p.unit_id = u.id
    $where_sql
    ORDER BY p.id DESC
    LIMIT $per_page OFFSET " . (($page - 1) * $per_page);

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$data = [];
foreach ($products as $row) {
    $data[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'sku' => $row['sku'],
        'barcode' => $row['barcode'],
        'category' => $row['category'] ?? '',
        'supplier' => $row['supplier'] ?? '',
        'unit' => $row['unit_abbr'] ?: $row['unit'] ?: '',
        'stock' => $row['stock'],
        'reorder_level' => $row['reorder_level'],
        'cost' => number_format($row['cost'], 2),
        'price' => number_format($row['price'], 2),
    ];
}

echo json_encode([
    'products' => $data,
    'pages' => max(1, ceil($count / $per_page)),
    'start' => ($page - 1) * $per_page + 1
]);