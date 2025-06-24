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

if (isset($_GET['po_id'])) {
    $id = intval($_GET['po_id']);
    $stmt = $pdo->prepare("SELECT * FROM purchase_orders WHERE id=?");
    $stmt->execute([$id]);
    $po = $stmt->fetch(PDO::FETCH_ASSOC);
    $items = [];
    $stmt2 = $pdo->prepare("SELECT * FROM purchase_order_items WHERE purchase_order_id=?");
    $stmt2->execute([$id]);
    $items = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode([
        'id'=>$po['id'],
        'supplier_id'=>$po['supplier_id'],
        'po_date'=>$po['po_date'],
        'items'=>$items
    ]);
    exit;
}

$purchase_orders = $pdo->query("SELECT po.*, s.name AS supplier_name 
    FROM purchase_orders po LEFT JOIN suppliers s ON po.supplier_id=s.id 
    ORDER BY po.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Get product names
$products = $pdo->query("SELECT id, name FROM products")->fetchAll(PDO::FETCH_KEY_PAIR);

?>
<div class="overflow-x-auto">
    <table class="min-w-full bg-white rounded shadow text-sm">
        <thead>
            <tr class="bg-gray-100">
                <th class="py-2 px-4">PO #</th>
                <th class="py-2 px-4">Supplier</th>
                <th class="py-2 px-4">Date</th>
                <th class="py-2 px-4">Products</th>
                <th class="py-2 px-4">Status</th>
                <th class="py-2 px-4">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($purchase_orders as $po): ?>
            <?php
            // Get products for this order
            $po_items = $pdo->prepare("SELECT * FROM purchase_order_items WHERE purchase_order_id=?");
            $po_items->execute([$po['id']]);
            $prods = [];
            foreach ($po_items as $item) {
                $prods[] = $products[$item['product_id']] . " (x" . $item['quantity'] . ")";
            }
            ?>
            <tr>
                <td class="py-2 px-4 font-semibold">#<?= $po['id'] ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($po['supplier_name']) ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($po['po_date']) ?></td>
                <td class="py-2 px-4"><?= implode('<br>', $prods) ?></td>
                <td class="py-2 px-4">
                    <?php if ($po['status']=='confirmed'): ?>
                        <span class="bg-green-200 text-green-800 px-2 rounded text-xs">Confirmed</span>
                    <?php elseif ($po['status']=='pending'): ?>
                        <span class="bg-yellow-200 text-yellow-800 px-2 rounded text-xs">Pending</span>
                    <?php else: ?>
                        <span class="bg-gray-200 text-gray-800 px-2 rounded text-xs">Cancelled</span>
                    <?php endif; ?>
                </td>
                <td class="py-2 px-4 flex gap-2">
                    <?php if ($po['status']=='pending'): ?>
                        <button class="confirmPOBtn text-green-600 hover:underline" data-id="<?= $po['id'] ?>">Confirm</button>
                        <button class="editPOBtn text-blue-600 hover:underline" data-id="<?= $po['id'] ?>">Edit</button>
                        <button class="deletePOBtn text-red-600 hover:underline" data-id="<?= $po['id'] ?>">Delete</button>
                    <?php else: ?>
                        <span class="text-gray-400 italic">No Actions</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>