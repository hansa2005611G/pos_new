<?php
session_start();
if (!isset($_SESSION['user_id'], $_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header('Location: /login.php');
    exit;
}
require_once 'C:\inetpub\wwwroot\pos_new\db.php';

// Get users except yourself and superadmin
$users = $pdo->query("SELECT id, username, role FROM users WHERE id != {$_SESSION['user_id']} AND role != 'superadmin' ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

// Define all permissions
$all_permissions = [
    'manage_products'    => 'Manage Products',
    'manage_categories'  => 'Manage Categories',
    'manage_suppliers'   => 'Manage Suppliers',
    'manage_purchases'   => 'Purchase/Stock In',
    'manage_stockout'    => 'Stock Out / Sales',
    'manage_adjustment'  => 'Stock Adjustment',
    'view_reports'       => 'View Reports',
    'manage_users'       => 'User Management',
    'export_import'      => 'Export / Import'
];

// Get user permissions from DB (assume table: user_permissions with user_id, permission columns)
$permissions = [];
$q = $pdo->query("SELECT user_id, permission FROM user_permissions");
while($row = $q->fetch(PDO::FETCH_ASSOC)) {
    $permissions[$row['user_id']][] = $row['permission'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Roles & Permissions</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex">
<?php include 'sidebar.php'; ?>
<main class="flex-1 p-8">
  <h1 class="text-2xl font-bold mb-8 flex items-center gap-2">🧑‍💻 User Roles & Permissions</h1>
  <div id="permsMsg" class="mb-4"></div>
  <div class="bg-white p-6 rounded shadow max-w-3xl mx-auto">
    <form id="permForm">
      <label class="block font-semibold mb-2">Select User:</label>
      <select name="user_id" id="userSelect" required class="border px-3 py-2 rounded mb-6 w-full">
        <option value="">--Choose User--</option>
        <?php foreach($users as $u): ?>
          <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?> (<?= $u['role'] ?>)</option>
        <?php endforeach; ?>
      </select>
      <div id="permArea" class="grid grid-cols-1 sm:grid-cols-2 gap-4"></div>
      <div class="mt-6">
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded">Save Permissions</button>
      </div>
    </form>
  </div>
</main>
<script>
const allPerms = <?= json_encode($all_permissions) ?>;
const userPerms = <?= json_encode($permissions) ?>;

document.getElementById('userSelect').addEventListener('change', function() {
    let uid = this.value;
    let area = document.getElementById('permArea');
    area.innerHTML = "";
    if(!uid) return;
    let userPermSet = new Set(userPerms[uid] ?? []);
    Object.entries(allPerms).forEach(([key, label]) => {
        let checked = userPermSet.has(key) ? "checked" : "";
        area.innerHTML += `
            <label class="flex items-center gap-2">
              <input type="checkbox" name="perms[]" value="${key}" class="h-4 w-4" ${checked}>
              <span>${label}</span>
            </label>
        `;
    });
});

document.getElementById('permForm').onsubmit = function(e){
    e.preventDefault();
    let fd = new FormData(this);
    fetch('ajax/update_user_permissions.php', {
        method: 'POST',
        body: fd
    })
    .then(r=>r.json())
    .then(res=>{
        document.getElementById('permsMsg').innerHTML = res.success 
            ? '<span class="text-green-700">'+res.message+'</span>'
            : '<span class="text-red-700">'+res.error+'</span>';
        if(res.success) setTimeout(()=>document.getElementById('permsMsg').innerHTML='', 4000);
    })
    .catch(()=>document.getElementById('permsMsg').innerHTML = '<span class="text-red-700">Error updating permissions</span>');
};
</script>
</body>
</html>