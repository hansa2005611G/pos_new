<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'], $_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    echo json_encode(['success'=>false,'error'=>'Permission denied']); exit;
}
require_once 'C:\inetpub\wwwroot\pos_new\db.php';
require_once 'C:\inetpub\wwwroot\pos_new\includes\log_activity.php';

$user_id = intval($_POST['user_id'] ?? 0);
$perms = $_POST['perms'] ?? [];

if (!$user_id) {
    echo json_encode(['success'=>false,'error'=>'User not specified.']); exit;
}

// Optionally, block admin users from editing their own permissions
if ($user_id == $_SESSION['user_id']) {
    echo json_encode(['success'=>false, 'error'=>'Cannot edit your own permissions.']); exit;
}

// Only allow known permissions
$all_permissions = [
    'manage_products', 'manage_categories', 'manage_suppliers', 'manage_purchases',
    'manage_stockout', 'manage_adjustment', 'view_reports', 'manage_users', 'export_import'
];
$perms = array_intersect($perms, $all_permissions);

try {
    // Remove existing permissions
    $stmt = $pdo->prepare("DELETE FROM user_permissions WHERE user_id=?");
    $stmt->execute([$user_id]);

    // Bulk insert new permissions
    if (!empty($perms)) {
        $values = [];
        foreach ($perms as $perm) {
            $values[] = "($user_id, " . $pdo->quote($perm) . ")";
        }
        $sql = "INSERT INTO user_permissions (user_id, permission) VALUES " . implode(',', $values);
        $pdo->exec($sql);
    }
    echo json_encode(['success'=>true, 'message'=>'Permissions updated.']);
} catch (Exception $e) {
    echo json_encode(['success'=>false, 'error'=>'Database error: '.$e->getMessage()]);
}