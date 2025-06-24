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


if (isset($_GET['stock_out_id'])) {
    $id = intval($_GET['stock_out_id']);
    $stmt = $pdo->prepare("SELECT * FROM stock_out WHERE id=?");
    $stmt->execute([$id]);
    $so = $stmt->fetch(PDO::FETCH_ASSOC);
    $items = [];
    $stmt2 = $pdo->prepare("SELECT * FROM stock_out_items WHERE stock_out_id=?");
    $stmt2->execute([$id]);
    $items = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode([
        'id'=>$so['id'],
        'date'=>$so['date'],
        'type'=>$so['type'],
        'reference'=>$so['reference'],
        'items'=>$items
    ]);
    exit;
}

$stock_outs = $pdo->query("SELECT * FROM stock_out ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$products = $pdo->query("SELECT id, name FROM products")->fetchAll(PDO::FETCH_KEY_PAIR);

?>
<div class="overflow-x-auto">
    <table class="min-w-full bg-white rounded shadow text-sm">
        <thead>
            <tr class="bg-gray-100">
                <th class="py-2 px-4">#</th>
                <th class="py-2 px-4">Date</th>
                <th class="py-2 px-4">Type</th>
                <th class="py-2 px-4">Reference</th>
                <th class="py-2 px-4">Products</th>
                <th class="py-2 px-4">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($stock_outs as $so): ?>
            <?php
            // Get products for this record
            $so_items = $pdo->prepare("SELECT * FROM stock_out_items WHERE stock_out_id=?");
            $so_items->execute([$so['id']]);
            $prods = [];
            foreach ($so_items as $item) {
                $prods[] = $products[$item['product_id']] . " (x" . $item['quantity'] . ")";
            }
            ?>
            <tr>
                <td class="py-2 px-4 font-semibold">#<?= $so['id'] ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($so['date']) ?></td>
                <td class="py-2 px-4"><?= ucfirst($so['type']) ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($so['reference']) ?></td>
                <td class="py-2 px-4"><?= implode('<br>', $prods) ?></td>
                <td class="py-2 px-4 flex gap-2">
                    <button class="editStockOutBtn text-blue-600 hover:underline" data-id="<?= $so['id'] ?>">Edit</button>
                    <button class="deleteStockOutBtn text-red-600 hover:underline" data-id="<?= $so['id'] ?>">Delete</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>