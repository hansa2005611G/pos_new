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

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id=?");
    $stmt->execute([$id]);
    $supplier = $stmt->fetch();
    header('Content-Type: application/json');
    echo json_encode(['supplier' => $supplier]);
    exit;
}

// Fetch all suppliers
$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Pre-fetch all product names indexed by id
$productNames = [];
$products = $pdo->query("SELECT id, name FROM products")->fetchAll(PDO::FETCH_ASSOC);
foreach ($products as $p) {
    $productNames[$p['id']] = $p['name'];
}

// For each supplier, fetch their supplied product IDs
$supplierProducts = [];
$supIds = array_column($suppliers, 'id');
if ($supIds) {
    $placeholders = implode(',', array_fill(0, count($supIds), '?'));
    $stmt = $pdo->prepare("SELECT supplier_id, product_id FROM supplier_products WHERE supplier_id IN ($placeholders)");
    $stmt->execute($supIds);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $supplierProducts[$row['supplier_id']][] = $row['product_id'];
    }
}
?>
<div class="overflow-x-auto">
    <table class="min-w-full bg-white rounded shadow text-sm">
        <thead>
            <tr class="bg-gray-100">
                <th class="py-2 px-4">Name</th>
                <th class="py-2 px-4">Contact</th>
                <th class="py-2 px-4">Email</th>
                <th class="py-2 px-4">Address</th>
                <th class="py-2 px-4">Products</th>
                <th class="py-2 px-4">Status</th>
                <th class="py-2 px-4">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($suppliers as $sup): ?>
            <tr>
                <td class="py-2 px-4 font-semibold"><?= htmlspecialchars($sup['name']) ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($sup['contact']) ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($sup['email']) ?></td>
                <td class="py-2 px-4"><?= nl2br(htmlspecialchars($sup['address'])) ?></td>
                <td class="py-2 px-4">
                    <?php
                    if (!empty($supplierProducts[$sup['id']])) {
                        $names = [];
                        foreach ($supplierProducts[$sup['id']] as $pid) {
                            if (isset($productNames[$pid])) $names[] = htmlspecialchars($productNames[$pid]);
                        }
                        echo implode(', ', $names);
                    } else {
                        echo '<span class="text-gray-400 italic">None</span>';
                    }
                    ?>
                </td>
                <td class="py-2 px-4">
                    <?php if ($sup['active']): ?>
                        <span class="bg-green-200 text-green-800 px-2 rounded text-xs">Active</span>
                    <?php else: ?>
                        <span class="bg-gray-200 text-gray-800 px-2 rounded text-xs">Inactive</span>
                    <?php endif; ?>
                </td>
                <td class="py-2 px-4 flex gap-2">
                    <button class="editSupplierBtn text-blue-600 hover:underline" data-id="<?= $sup['id'] ?>">Edit</button>
                    <button class="toggleActiveBtn text-<?= $sup['active']?'gray':'green' ?>-600 hover:underline" data-id="<?= $sup['id'] ?>" data-active="<?= $sup['active'] ?>">
                        <?= $sup['active'] ? "Deactivate" : "Activate" ?>
                    </button>
                    <button class="deleteSupplierBtn text-red-600 hover:underline" data-id="<?= $sup['id'] ?>">Delete</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>