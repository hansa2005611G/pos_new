<?php
header('Content-Type: application/json');
// TODO: Fetch top selling products from sales data
echo json_encode([
    'labels' => ['Product A', 'Product B', 'Product C', 'Product D'],
    'sales' => [120, 95, 80, 60]
]);