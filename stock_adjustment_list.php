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


$adjustments = $pdo->query("SELECT a.*, p.name AS product_name, u.username 
    FROM stock_adjustments a 
    LEFT JOIN products p ON a.product_id=p.id 
    LEFT JOIN users u ON a.user_id=u.id
    ORDER BY a.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="overflow-x-auto">
    <table class="min-w-full bg-white rounded shadow text-sm">
        <thead>
            <tr class="bg-gray-100">
                <th class="py-2 px-4">#</th>
                <th class="py-2 px-4">Date</th>
                <th class="py-2 px-4">Product</th>
                <th class="py-2 px-4">Old Stock</th>
                <th class="py-2 px-4">New Stock</th>
                <th class="py-2 px-4">Reason</th>
                <th class="py-2 px-4">User</th>
                <th class="py-2 px-4">Time</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($adjustments as $adj): ?>
            <tr>
                <td class="py-2 px-4 font-semibold">#<?= $adj['id'] ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($adj['date']) ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($adj['product_name']) ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($adj['old_stock']) ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($adj['new_stock']) ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($adj['reason']) ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($adj['username']) ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($adj['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>