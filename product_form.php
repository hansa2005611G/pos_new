<?php
require_once '../db.php';
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
$can_manage_products = in_array($_SESSION['role'] ?? '', ['admin', 'manager']);
if (!$can_manage_products) {
    http_response_code(403); echo json_encode(['error' => 'Permission denied']); exit;
}
$action = $_POST['action'] ?? '';
$response = ['success' => false];

function handle_image_upload($file) {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($ext, $allowed)) return null;
    $fname = uniqid('prod_', true) . '.' . $ext;
    $target = __DIR__ . "/../uploads/products/$fname";
    if (!move_uploaded_file($file['tmp_name'], $target)) return null;
    return $fname;
}

if ($action === 'add' || $action === 'edit') {
    $name = trim($_POST['name'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $barcode = trim($_POST['barcode'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0) ?: null;
    $desc = trim($_POST['description'] ?? '');
    $unit_id = intval($_POST['unit_id'] ?? 0) ?: null;
    $stock = intval($_POST['stock'] ?? 0);
    $reorder = intval($_POST['reorder_level'] ?? 0);
    $img = null;
    if (!empty($_FILES['image']['name'])) {
        $img = handle_image_upload($_FILES['image']);
        if ($img === null) {
            echo json_encode(['error' => 'Invalid image uploaded.']); exit;
        }
    }
    if (!$name || !$sku) {
        echo json_encode(['error' => 'Name and SKU required.']); exit;
    }
    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO products (name, sku, barcode, category_id, description, image, unit_id, stock, reorder_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $sku, $barcode, $category_id, $desc, $img, $unit_id, $stock, $reorder]);
        $response['success'] = true;
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id=?");
        $stmt->execute([$id]);
        $old = $stmt->fetch();
        if ($img === null && $old) $img = $old['image'];
        $stmt = $pdo->prepare("UPDATE products SET name=?, sku=?, barcode=?, category_id=?, description=?, image=?, unit_id=?, stock=?, reorder_level=? WHERE id=?");
        $stmt->execute([$name, $sku, $barcode, $category_id, $desc, $img, $unit_id, $stock, $reorder, $id]);
        $response['success'] = true;
    }
} elseif ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
    $stmt->execute([$id]);
    $response['success'] = true;
}
echo json_encode($response);