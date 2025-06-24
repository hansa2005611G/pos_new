<?php
header('Content-Type: application/json');
// TODO: Fetch recent activities from activity_logs table.
echo json_encode([
    ['timestamp' => '2025-06-23 10:23', 'user' => 'admin', 'action' => 'Added Product ABC'],
    ['timestamp' => '2025-06-23 09:55', 'user' => 'storeman', 'action' => 'Stock Out: Product XYZ'],
    ['timestamp' => '2025-06-23 09:30', 'user' => 'admin', 'action' => 'Modified Supplier DEF']
]);