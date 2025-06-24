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
    $stmt = $pdo->prepare("SELECT * FROM product_categories WHERE id=?");
    $stmt->execute([$id]);
    $cat = $stmt->fetch();
    header('Content-Type: application/json');
    echo json_encode(['category' => $cat]);
    exit;
}
$cats = $pdo->query("SELECT * FROM product_categories ORDER BY name")->fetchAll();
?>
<div class="overflow-x-auto">
    <table class="min-w-full bg-white rounded shadow text-sm">
        <thead>
            <tr class="bg-gray-100">
                <th class="py-2 px-4">Icon</th>
                <th class="py-2 px-4">Name</th>
                <th class="py-2 px-4">Description</th>
                <th class="py-2 px-4">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($cats as $cat): ?>
            <tr>
                <td class="py-2 px-4">
                    <?php if ($cat['icon']): ?>
                        <img src="C:\inetpub\wwwroot\pos_new\uploads\category_icons<?= htmlspecialchars($cat['icon']) ?>" class="h-10">
                    <?php endif; ?>
                </td>
                <td class="py-2 px-4 font-semibold"><?= htmlspecialchars($cat['name']) ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($cat['description']) ?></td>
                <td class="py-2 px-4 flex gap-2">
                    <button class="editCatBtn text-blue-600 hover:underline" data-id="<?= $cat['id'] ?>">Edit</button>
                    <button class="deleteCatBtn text-red-600 hover:underline" data-id="<?= $cat['id'] ?>">Delete</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>