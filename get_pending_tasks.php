<?php
header('Content-Type: application/json');
// TODO: Replace with real pending tasks from DB.
echo json_encode([
    "2 pending purchase orders",
    "5 low stock warnings",
    "1 item awaiting stock verification"
]);