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
    $name = trim($_POST['name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $products = $_POST['products'] ?? [];
    if (!$name) {
        echo json_encode(['success' => false, 'error' => 'Name required.']); exit;
    }
    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO suppliers (name, contact, email, address, active) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$name, $contact, $email, $address]);
        $sid = $pdo->lastInsertId();
    } else {
        $sid = intval($_POST['id']);
        $stmt = $pdo->prepare("UPDATE suppliers SET name=?, contact=?, email=?, address=? WHERE id=?");
        $stmt->execute([$name, $contact, $email, $address, $sid]);
        $pdo->prepare("DELETE FROM supplier_products WHERE supplier_id=?")->execute([$sid]);
    }
    // Insert supplier products
    if (!empty($products)) {
        $spstmt = $pdo->prepare("INSERT INTO supplier_products (supplier_id, product_id) VALUES (?, ?)");
        foreach ($products as $pid) {
            $spstmt->execute([$sid, intval($pid)]);
        }
    }
    echo json_encode(['success' => true, 'message' => 'Supplier saved!']);
    exit;
} elseif ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM suppliers WHERE id=?")->execute([$id]);
    echo json_encode(['success' => true, 'message' => 'Supplier deleted!']); exit;
} elseif ($action === 'activate' || $action === 'deactivate') {
    $id = intval($_POST['id'] ?? 0);
    $active = $action === 'activate' ? 1 : 0;
    $pdo->prepare("UPDATE suppliers SET active=? WHERE id=?")->execute([$active, $id]);
    echo json_encode(['success' => true, 'message' => 'Supplier status updated!']); exit;
}
echo json_encode(['success' => false, 'error' => 'Invalid request.']);