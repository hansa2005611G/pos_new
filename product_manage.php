<?php
ob_start(); // Start output buffering
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: /login.php');
    exit;
}
// Only allow Admins/Managers to add/edit/delete
$can_manage_products = in_array($_SESSION['role'], ['admin', 'manager']);

// Handle Product Add/Edit/Delete
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$alert = '';
$errors = [];

// Handle image upload
function handle_image_upload($file) {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($ext, $allowed)) return null;
    $fname = uniqid('prod_', true) . '.' . $ext;
    $target = __DIR__ . "/../uploads/products/$fname";
    if (!move_uploaded_file($file['tmp_name'], $target)) return null;
    return $fname;
}

// Add Product
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $sku = trim($_POST['sku']);
    $barcode = trim($_POST['barcode']);
    $category_id = intval($_POST['category_id']);
    $desc = trim($_POST['description']);
    $unit_id = intval($_POST['unit_id']);
    $stock = intval($_POST['stock']);
    $reorder = intval($_POST['reorder_level']);
    $img = handle_image_upload($_FILES['image']);

    // Validation
    if (!$name) $errors[] = "Name required.";
    if (!$sku) $errors[] = "SKU required.";

    // Insert if no errors
    if (!$errors) {
        $stmt = $pdo->prepare("INSERT INTO products (name, sku, barcode, category_id, description, image, unit_id, stock, reorder_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $sku, $barcode, $category_id ?: null, $desc, $img, $unit_id ?: null, $stock, $reorder]);
        $alert = "Product added!";
    }
}

// Edit Product
if ($action === 'edit' && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    // Fetch existing
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $stmt->execute([$product_id]);
    $editProduct = $stmt->fetch();
    if (!$editProduct) $alert = "Product not found!";
    // Update logic (on POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name']);
        $sku = trim($_POST['sku']);
        $barcode = trim($_POST['barcode']);
        $category_id = intval($_POST['category_id']);
        $desc = trim($_POST['description']);
        $unit_id = intval($_POST['unit_id']);
        $stock = intval($_POST['stock']);
        $reorder = intval($_POST['reorder_level']);
        $img = $editProduct['image'];
        if (!empty($_FILES['image']['name'])) {
            $img = handle_image_upload($_FILES['image']) ?: $img;
        }
        if (!$name) $errors[] = "Name required.";
        if (!$sku) $errors[] = "SKU required.";
        if (!$errors) {
            $stmt = $pdo->prepare("UPDATE products SET name=?, sku=?, barcode=?, category_id=?, description=?, image=?, unit_id=?, stock=?, reorder_level=? WHERE id=?");
            $stmt->execute([$name, $sku, $barcode, $category_id ?: null, $desc, $img, $unit_id ?: null, $stock, $reorder, $product_id]);
            $alert = "Product updated!";
            // Refresh product for form
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
            $stmt->execute([$product_id]);
            $editProduct = $stmt->fetch();
        }
    }
}

// Delete Product
if ($action === 'delete' && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
    $stmt->execute([$product_id]);
    $alert = "Product deleted!";
    header("Location: product_manage.php?deleted=1");
    exit;
}

// Fetch categories and units for forms
$categories = $pdo->query("SELECT * FROM product_categories ORDER BY name")->fetchAll();
$units = $pdo->query("SELECT * FROM units ORDER BY name")->fetchAll();

// Fetch all products
$products = $pdo->query("SELECT p.*, c.name as category_name, u.abbreviation as unit_abbr 
    FROM products p
    LEFT JOIN product_categories c ON p.category_id = c.id
    LEFT JOIN units u ON p.unit_id = u.id
    ORDER BY p.name")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex">
    <!-- Sidebar (can be included as before) -->
    <?php include 'sidebar.php'; ?>
    <!-- Main Content -->
    <main class="flex-1 p-10">
        <h1 class="text-2xl font-bold mb-6 flex items-center gap-2">📦 Product Management</h1>
        <?php if ($alert): ?>
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded"><?= htmlspecialchars($alert) ?></div>
        <?php endif; ?>
        <?php if ($errors): ?>
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
                <?php foreach($errors as $e) echo htmlspecialchars($e) . "<br>"; ?>
            </div>
        <?php endif; ?>

        <!-- Add/Edit Product Form -->
        <div class="mb-8">
            <h2 class="font-semibold mb-2"><?= isset($editProduct) ? 'Edit Product' : 'Add Product' ?></h2>
            <form method="post" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-white p-6 rounded shadow">
                <input type="hidden" name="action" value="<?= isset($editProduct) ? 'edit' : 'add' ?>">
                <div>
                    <label class="block font-medium">Product Name</label>
                    <input name="name" required class="w-full border rounded px-3 py-2" value="<?= $editProduct['name'] ?? '' ?>">
                </div>
                <div>
                    <label class="block font-medium">SKU</label>
                    <input name="sku" required class="w-full border rounded px-3 py-2" value="<?= $editProduct['sku'] ?? '' ?>">
                </div>
                <div>
                    <label class="block font-medium">Barcode</label>
                    <input name="barcode" class="w-full border rounded px-3 py-2" value="<?= $editProduct['barcode'] ?? '' ?>">
                    <?php if (!empty($editProduct['barcode'])): ?>
                        <img src="barcode.php?barcode=<?= urlencode($editProduct['barcode']) ?>" class="mt-2 h-12">
                    <?php endif; ?>
                </div>
                <div>
                    <label class="block font-medium">Category</label>
                    <select name="category_id" class="w-full border rounded px-3 py-2">
                        <option value="">-- Select --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= (isset($editProduct) && $editProduct['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block font-medium">Unit</label>
                    <select name="unit_id" class="w-full border rounded px-3 py-2">
                        <option value="">-- Select --</option>
                        <?php foreach ($units as $unit): ?>
                            <option value="<?= $unit['id'] ?>" <?= (isset($editProduct) && $editProduct['unit_id'] == $unit['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($unit['name']) ?> (<?= $unit['abbreviation'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block font-medium">Stock Level</label>
                    <input type="number" name="stock" min="0" required class="w-full border rounded px-3 py-2" value="<?= $editProduct['stock'] ?? 0 ?>">
                </div>
                <div>
                    <label class="block font-medium">Reorder Level</label>
                    <input type="number" name="reorder_level" min="0" required class="w-full border rounded px-3 py-2" value="<?= $editProduct['reorder_level'] ?? 0 ?>">
                </div>
                <div>
                    <label class="block font-medium">Description</label>
                    <textarea name="description" class="w-full border rounded px-3 py-2"><?= $editProduct['description'] ?? '' ?></textarea>
                </div>
                <div>
                    <label class="block font-medium">Image</label>
                    <input type="file" name="image" accept="image/*" class="w-full border rounded px-3 py-2">
                    <?php if (isset($editProduct) && $editProduct['image']): ?>
                        <img src="/uploads/products/<?= htmlspecialchars($editProduct['image']) ?>" alt="Product photo as uploaded for this item in the product management system form, shown above the file input. The image appears in a clean business dashboard environment. If the image contains text, it is not transcribed here." class="mt-2 h-16">
                    <?php endif; ?>
                </div>
                <div class="md:col-span-2 flex gap-3">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
                        <?= isset($editProduct) ? 'Update' : 'Add' ?> Product
                    </button>
                    <?php if (isset($editProduct)): ?>
                        <a href="product_manage.php" class="ml-3 text-gray-600 underline">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Products Table -->
        <div>
            <h2 class="font-semibold mb-2">Products List</h2>
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
                            <td class="py-2 px-4 font-mono">
                                <?= htmlspecialchars($prod['barcode']) ?>
                                <?php if ($prod['barcode']): ?>
                                    <br>
                                    <img src="barcode.php?barcode=<?= urlencode($prod['barcode']) ?>" class="h-8 mt-1">
                                <?php endif; ?>
                            </td>
                            <td class="py-2 px-4"><?= htmlspecialchars($prod['category_name']) ?></td>
                            <td class="py-2 px-4"><?= htmlspecialchars($prod['unit_abbr']) ?></td>
                            <td class="py-2 px-4"><?= $prod['stock'] ?></td>
                            <td class="py-2 px-4"><?= $prod['reorder_level'] ?></td>
                            <td class="py-2 px-4 flex gap-2">
                                <a href="?action=edit&id=<?= $prod['id'] ?>" class="text-blue-600 hover:underline">Edit</a>
                                <a href="?action=delete&id=<?= $prod['id'] ?>" class="text-red-600 hover:underline" onclick="return confirm('Delete this product?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-2 text-xs text-red-700">Red rows: at or below reorder level!</div>
        </div>
    </main>
</body>
</html>