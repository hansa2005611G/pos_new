<?php
ini_set('display_errors', 1); // comment out in production
ini_set('display_startup_errors', 1);
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
$response = ['success' => false];

function handle_icon_upload($file) {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($ext, $allowed)) return null;
    $fname = uniqid('cat_', true) . '.' . $ext;
    $target = __DIR__ . "C:\inetpub\wwwroot\pos_new\uploads\category_icons$fname";
    if (!move_uploaded_file($file['tmp_name'], $target)) return null;
    return $fname;
}
if ($action === 'add' || $action === 'edit') {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $icon = null;
    if ($action === 'edit' && isset($_POST['id'])) {
        $stmt = $pdo->prepare("SELECT icon FROM product_categories WHERE id=?");
        $stmt->execute([intval($_POST['id'])]);
        $icon = $stmt->fetchColumn();
    }
    if (!empty($_FILES['icon']['name'])) {
        $icon = handle_icon_upload($_FILES['icon']);
        if ($icon === null) {
            echo json_encode(['success' => false, 'error' => 'Invalid icon file.']); exit;
        }
    }
    if (!$name) {
        echo json_encode(['success' => false, 'error' => 'Name required.']); exit;
    }
    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO product_categories (name, description, icon) VALUES (?, ?, ?)");
        $stmt->execute([$name, $desc, $icon]);
        echo json_encode(['success' => true, 'message' => 'Category added!']); exit;
    } elseif ($action === 'edit' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $stmt = $pdo->prepare("UPDATE product_categories SET name=?, description=?, icon=? WHERE id=?");
        $stmt->execute([$name, $desc, $icon, $id]);
        echo json_encode(['success' => true, 'message' => 'Category updated!']); exit;
    }
} elseif ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM product_categories WHERE id=?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true, 'message' => 'Category deleted!']); exit;
}
echo json_encode($response);