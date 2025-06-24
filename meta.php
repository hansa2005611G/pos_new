<?php
require_once '../db.php';
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

header('Content-Type: application/json');
$categories = $pdo->query("SELECT id, name FROM product_categories ORDER BY name")->fetchAll();
$units = $pdo->query("SELECT id, name, abbreviation FROM units ORDER BY name")->fetchAll();
echo json_encode(['categories'=>$categories, 'units'=>$units]);