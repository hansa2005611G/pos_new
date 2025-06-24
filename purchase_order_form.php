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
$can_manage = in_array($_SESSION['role'] ?? '', ['admin', 'manager']);
if (!$can_manage) {
    http_response_code(403); echo json_encode(['error' => 'Permission denied']); exit;
}
$action = $_POST['action'] ?? '';
if ($action === 'add' || $action === 'edit') {
    $po_id = intval($_POST['po_id'] ?? 0);
    $supplier_id = intval($_POST['supplier_id'] ?? 0);
    $po_date = $_POST['po_date'] ?? date('Y-m-d');
    $products = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $unit_costs = $_POST['unit_cost'] ?? [];
    if (!$supplier_id || !$po_date || !$products || !$quantities || !$unit_costs) {
        echo json_encode(['success' => false, 'error' => 'All fields required.']); exit;
    }
    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO purchase_orders (supplier_id, po_date) VALUES (?, ?)");
        $stmt->execute([$supplier_id, $po_date]);
        $po_id = $pdo->lastInsertId();
    } else {
        $stmt = $pdo->prepare("UPDATE purchase_orders SET supplier_id=?, po_date=? WHERE id=?");
        $stmt->execute([$supplier_id, $po_date, $po_id]);
        $pdo->prepare("DELETE FROM purchase_order_items WHERE purchase_order_id=?")->execute([$po_id]);
    }
    $itemStmt = $pdo->prepare("INSERT INTO purchase_order_items (purchase_order_id, product_id, quantity, unit_cost) VALUES (?, ?, ?, ?)");
    for ($i=0; $i<count($products); $i++) {
        $pid = intval($products[$i]);
        $qty = intval($quantities[$i]);
        $cost = floatval($unit_costs[$i]);
        $itemStmt->execute([$po_id, $pid, $qty, $cost]);
    }
    echo json_encode(['success' => true, 'message' => 'Purchase order saved!']);
    exit;
} elseif ($action === 'confirm') {
    $po_id = intval($_POST['po_id'] ?? 0);
    $pdo->prepare("UPDATE purchase_orders SET status='confirmed' WHERE id=?")->execute([$po_id]);
    // Update stock for each item
    $items = $pdo->prepare("SELECT product_id, quantity FROM purchase_order_items WHERE purchase_order_id=?");
    $items->execute([$po_id]);
    foreach ($items as $item) {
        // Update product stock (assumes `stock` column in products table)
        $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id=?")->execute([$item['quantity'], $item['product_id']]);
    }
    echo json_encode(['success' => true, 'message' => 'Purchase order confirmed and stock updated!']);
    exit;
} elseif ($action === 'delete') {
    $po_id = intval($_POST['po_id'] ?? 0);
    $pdo->prepare("DELETE FROM purchase_orders WHERE id=?")->execute([$po_id]);
    echo json_encode(['success' => true, 'message' => 'Purchase order deleted!']); exit;
}
echo json_encode(['success' => false, 'error' => 'Invalid request.']);