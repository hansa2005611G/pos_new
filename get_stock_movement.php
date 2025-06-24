<?php
header('Content-Type: application/json');
// TODO: Fetch from stock movement logs
echo json_encode([
    'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    'stock_in' => [10, 25, 12, 8, 15, 20, 5],
    'stock_out' => [5, 12, 10, 6, 9, 14, 2]
]);