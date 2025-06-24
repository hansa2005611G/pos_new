<?php
header('Content-Type: application/json');
// TODO: Replace stub data with real DB queries.
echo json_encode([
    'total_products' => 250,
    'low_stock' => 12,
    'out_of_stock' => 3,
    'inventory_value' => '$45,000',
    'todays_sales' => '$2,300',
    'purchase_orders' => 5,
    'suppliers' => 18
]);