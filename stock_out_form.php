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
$can_manage = in_array($_SESSION['role'] ?? '', ['admin', 'manager', 'cashier']);
if (!$can_manage) {
    http_response_code(403); echo json_encode(['error' => 'Permission denied']); exit;
}
$action = $_POST['action'] ?? '';
if ($action === 'add' || $action === 'edit') {
    $stock_out_id = intval($_POST['stock_out_id'] ?? 0);
    $date = $_POST['date'] ?? date('Y-m-d');
    $type = $_POST['type'] ?? 'sale';
    $reference = $_POST['reference'] ?? '';
    $products = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    if (!$date || !$type || !$products || !$quantities) {
        echo json_encode(['success' => false, 'error' => 'All fields required.']); exit;
    }
    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO stock_out (date, type, reference) VALUES (?, ?, ?)");
        $stmt->execute([$date, $type, $reference]);
        $stock_out_id = $pdo->lastInsertId();
    } else {
        $stmt = $pdo->prepare("UPDATE stock_out SET date=?, type=?, reference=? WHERE id=?");
        $stmt->execute([$date, $type, $reference, $stock_out_id]);
        $pdo->prepare("DELETE FROM stock_out_items WHERE stock_out_id=?")->execute([$stock_out_id]);
    }
    $itemStmt = $pdo->prepare("INSERT INTO stock_out_items (stock_out_id, product_id, quantity) VALUES (?, ?, ?)");
    for ($i=0; $i<count($products); $i++) {
        $pid = intval($products[$i]);
        $qty = intval($quantities[$i]);
        $itemStmt->execute([$stock_out_id, $pid, $qty]);
        // Deduct stock from products table
        $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id=?")->execute([$qty, $pid]);
    }
    echo json_encode(['success' => true, 'message' => 'Stock out recorded!']);
    exit;
} elseif ($action === 'delete') {
    $stock_out_id = intval($_POST['stock_out_id'] ?? 0);
    // restore stock before deleting
    $stmt = $pdo->prepare("SELECT product_id, quantity FROM stock_out_items WHERE stock_out_id=?");
    $stmt->execute([$stock_out_id]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id=?")->execute([$item['quantity'], $item['product_id']]);
    }
    $pdo->prepare("DELETE FROM stock_out WHERE id=?")->execute([$stock_out_id]);
    echo json_encode(['success' => true, 'message' => 'Stock out deleted and stock restored!']); exit;
}
echo json_encode(['success' => false, 'error' => 'Invalid request.']);