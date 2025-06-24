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

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$stmt = $pdo->prepare("
SELECT 'Stock In' AS type, po.po_date as date, p.name, poi.quantity
FROM purchase_orders po
JOIN purchase_order_items poi ON po.id=poi.purchase_order_id
JOIN products p ON poi.product_id=p.id
WHERE po.po_date BETWEEN ? AND ?
UNION ALL
SELECT 
  CASE 
    WHEN so.type='sale' THEN 'Sale'
    WHEN so.type='damage' THEN 'Damage'
    WHEN so.type='return' THEN 'Return'
    ELSE so.type END AS type, 
  so.date, p.name, soi.quantity
FROM stock_out so
JOIN stock_out_items soi ON so.id=soi.stock_out_id
JOIN products p ON soi.product_id=p.id
WHERE so.date BETWEEN ? AND ?
ORDER BY date, name
");
$stmt->execute([$from, $to, $from, $to]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<table class="min-w-full bg-white rounded shadow text-sm">
<thead>
<tr class="bg-gray-100">
<th>Date</th><th>Type</th><th>Product</th><th>Qty</th>
</tr></thead>
<tbody>
<?php foreach($rows as $r): ?>
<tr>
<td><?=htmlspecialchars($r['date'])?></td>
<td><?=htmlspecialchars($r['type'])?></td>
<td><?=htmlspecialchars($r['name'])?></td>
<td><?=htmlspecialchars($r['quantity'])?></td>
</tr>
<?php endforeach; ?>
</tbody></table>