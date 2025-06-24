<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'C:\inetpub\wwwroot\pos_new\db.php';
require_once 'C:\inetpub\wwwroot\pos_new\includes\log_activity.php';

session_start();
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: /login.php');
    exit;
}

$role = $_SESSION['role'];

// Example enforcement:
$can_admin   = ($role === 'admin');
$can_manage  = ($role === 'admin' || $role === 'manager');
$can_view    = in_array($role, ['admin', 'manager', 'staff']);

session_start();
header('Content-Type: application/json');
$can_adjust = in_array($_SESSION['role'] ?? '', ['admin', 'manager']);
if (!$can_adjust) {
    http_response_code(403); echo json_encode(['error' => 'Permission denied']); exit;
}
$action = $_POST['action'] ?? '';
if ($action === 'add') {
    $date = $_POST['date'] ?? date('Y-m-d');
    $product_id = intval($_POST['product_id'] ?? 0);
    $new_stock = intval($_POST['new_stock'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    $user_id = intval($_SESSION['user_id']);
    if (!$date || !$product_id || $reason === '' || $new_stock < 0) {
        echo json_encode(['success' => false, 'error' => 'All fields required and stock >= 0']); exit;
    }
    $stmt = $pdo->prepare("SELECT stock FROM products WHERE id=?");
    $stmt->execute([$product_id]);
    $old_stock = $stmt->fetchColumn();
    if ($old_stock === false) {
        echo json_encode(['success' => false, 'error' => 'Product not found']); exit;
    }
    // Update product stock
    $pdo->prepare("UPDATE products SET stock=? WHERE id=?")->execute([$new_stock, $product_id]);
    // Record adjustment
    $pdo->prepare("INSERT INTO stock_adjustments (date, product_id, old_stock, new_stock, reason, user_id) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([$date, $product_id, $old_stock, $new_stock, $reason, $user_id]);
    echo json_encode(['success' => true, 'message' => 'Stock adjustment recorded!']);
    exit;
}
echo json_encode(['success' => false, 'error' => 'Invalid request.']);