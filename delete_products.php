<?php
require_once '../db.php';
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$id = (int)($data['id'] ?? 0);
if ($id) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $ok = $stmt->execute([$id]);
    echo json_encode(['success' => $ok]);
} else {
    echo json_encode(['success' => false]);
}