<?php
require_once '../db.php';
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
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    header('Content-Type: application/json');
    echo json_encode(['product' => $product]);
    exit;
}
$products = $pdo->query("SELECT p.*, c.name as category_name, u.abbreviation as unit_abbr 
    FROM products p
    LEFT JOIN product_categories c ON p.category_id = c.id
    LEFT JOIN units u ON p.unit_id = u.id
    ORDER BY p.name")->fetchAll();
?>
<div class="overflow-x-auto">
    <table class="min-w-full bg-white rounded shadow text-sm">
        <thead>
            <tr class="bg-gray-100">
                <th class="py-2 px-4">Image</th>
                <th class="py-2 px-4">Name</th>
                <th class="py-2 px-4">SKU</th>
                <th class="py-2 px-4">Barcode</th>
                <th class="py-2 px-4">Category</th>
                <th class="py-2 px-4">Unit</th>
                <th class="py-2 px-4">Stock</th>
                <th class="py-2 px-4">Reorder</th>
                <th class="py-2 px-4">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($products as $prod): ?>
            <tr class="<?= $prod['stock'] <= $prod['reorder_level'] ? 'bg-red-50' : '' ?>">
                <td class="py-2 px-4">
                    <?php if ($prod['image']): ?>
                        <img src="/uploads/products/<?= htmlspecialchars($prod['image']) ?>" class="h-10">
                    <?php endif; ?>
                </td>
                <td class="py-2 px-4"><?= htmlspecialchars($prod['name']) ?></td>
                <td class="py-2 px-4 font-mono"><?= htmlspecialchars($prod['sku']) ?></td>
                <td class="py-2 px-4 font-mono"><?= htmlspecialchars($prod['barcode']) ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($prod['category_name']) ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($prod['unit_abbr']) ?></td>
                <td class="py-2 px-4"><?= $prod['stock'] ?></td>
                <td class="py-2 px-4"><?= $prod['reorder_level'] ?></td>
                <td class="py-2 px-4 flex gap-2">
                    <button class="editBtn text-blue-600 hover:underline" data-id="<?= $prod['id'] ?>">Edit</button>
                    <button class="deleteBtn text-red-600 hover:underline" data-id="<?= $prod['id'] ?>">Delete</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div class="mt-2 text-xs text-red-700">Red rows: at or below reorder level!</div>