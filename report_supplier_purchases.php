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

$supplier_id = intval($_GET['supplier_id'] ?? 0);
$stmt = $pdo->prepare("SELECT po.id, po.po_date, p.name as product, poi.quantity, poi.unit_cost
FROM purchase_orders po
JOIN purchase_order_items poi ON po.id=poi.purchase_order_id
JOIN products p ON poi.product_id=p.id
WHERE po.supplier_id = ?
ORDER BY po.po_date DESC");
$stmt->execute([$supplier_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<table class="min-w-full bg-white rounded shadow text-sm">
<thead>
<tr class="bg-gray-100"><th>Date</th><th>PO #</th><th>Product</th><th>Qty</th><th>Unit Cost</th></tr>
</thead>
<tbody>
<?php foreach($rows as $row): ?>
<tr>
<td><?=htmlspecialchars($row['po_date'])?></td>
<td>#<?=$row['id']?></td>
<td><?=htmlspecialchars($row['product'])?></td>
<td><?=$row['quantity']?></td>
<td><?=number_format($row['unit_cost'],2)?></td>
</tr>
<?php endforeach; ?>
</tbody></table>