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


$rows = $pdo->query("SELECT id, name, stock, reorder_level FROM products WHERE stock <= reorder_level ORDER BY stock ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<table class="min-w-full bg-white rounded shadow text-sm">
<thead>
<tr class="bg-gray-100">
<th>Name</th><th>Stock</th><th>Reorder Level</th>
</tr></thead>
<tbody>
<?php foreach($rows as $row): ?>
<tr>
<td><?=htmlspecialchars($row['name'])?></td>
<td><?=htmlspecialchars($row['stock'])?></td>
<td><?=htmlspecialchars($row['reorder_level'])?></td>
</tr>
<?php endforeach; ?>
</tbody></table>