<?php
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

$total = $pdo->query("SELECT SUM(stock * unit_cost) AS total_value FROM products")->fetchColumn();
?>
<div class="p-4 bg-white rounded shadow text-xl">
<strong>Total Inventory Value:</strong> <?=number_format($total,2)?>
</div>