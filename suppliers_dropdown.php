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

$suppliers = $pdo->query("SELECT id, name FROM suppliers WHERE active=1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
foreach ($suppliers as $sup) {
    echo '<option value="'.$sup['id'].'">'.htmlspecialchars($sup['name']).'</option>';
}