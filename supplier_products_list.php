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

// All products
$products = $pdo->query("SELECT id, name FROM products ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$supplier_products = [];
if (isset($_GET['supplier_id'])) {
    $sid = intval($_GET['supplier_id']);
    $stmt = $pdo->prepare("SELECT product_id FROM supplier_products WHERE supplier_id=?");
    $stmt->execute([$sid]);
    $supplier_products = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'product_id');
}
foreach ($products as $prod): ?>
    <label class="flex items-center gap-2">
        <input type="checkbox" name="products[]" value="<?= $prod['id'] ?>"
            <?= in_array($prod['id'], $supplier_products) ? 'checked' : '' ?>/>
        <?= htmlspecialchars($prod['name']) ?>
    </label>
<?php endforeach; ?>