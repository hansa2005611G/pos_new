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

$type = $_GET['type'] ?? 'top';
$order = $type == 'slow' ? 'ASC' : 'DESC';
$stmt = $pdo->prepare("SELECT p.name, SUM(soi.quantity) AS qty_out
FROM stock_out_items soi
JOIN products p ON soi.product_id = p.id
GROUP BY soi.product_id
ORDER BY qty_out $order
LIMIT 10");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<table class="min-w-full bg-white rounded shadow text-sm">
<thead>
<tr class="bg-gray-100"><th>Product</th><th>Qty Out</th></tr>
</thead>
<tbody>
<?php foreach($rows as $row): ?>
<tr>
<td><?=htmlspecialchars($row['name'])?></td>
<td><?=$row['qty_out']?></td>
</tr>
<?php endforeach; ?>
</tbody></table>