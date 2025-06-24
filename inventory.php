<?php
session_start();
// Protect the page: only allow logged-in users
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: /login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r shadow-md flex flex-col min-h-screen">
        <div class="p-6 border-b">
            <span class="text-xl font-bold text-blue-600">Inventory</span>
            <span class="block text-gray-500 text-sm mt-1">
                <?= htmlspecialchars(ucfirst($_SESSION['role'])) ?>
            </span>
        </div>
        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="product_manage.php" class="flex items-center p-3 rounded hover:bg-blue-50 transition group">
                <span class="text-2xl mr-3">📦</span>
                <span class="font-semibold group-hover:text-blue-700">Product Management</span>
            </a>
            <a href="categories_manage.php" class="flex items-center p-3 rounded hover:bg-blue-50 transition group">
                <span class="text-2xl mr-3">📂</span>
                <span class="font-semibold group-hover:text-blue-700">Category Management</span>
            </a>
            <a href="suppliers_manage.php" class="flex items-center p-3 rounded hover:bg-blue-50 transition group">
                <span class="text-2xl mr-3">📇</span>
                <span class="font-semibold group-hover:text-blue-700">Supplier Management</span>
            </a>
            <a href="purchasing_manage.php" class="flex items-center p-3 rounded hover:bg-blue-50 transition group">
                <span class="text-2xl mr-3">📥</span>
                <span class="font-semibold group-hover:text-blue-700">Purchasing / Stock In</span>
            </a>
            <a href="stock_out_manage.php" class="flex items-center p-3 rounded hover:bg-blue-50 transition group">
                <span class="text-2xl mr-3">📤</span>
                <span class="font-semibold group-hover:text-blue-700">Stock Out / Sales Integration</span>
            </a>
            <a href="stock_adjustment_manage.php" class="flex items-center p-3 rounded hover:bg-blue-50 transition group">
                <span class="text-2xl mr-3">🔁</span>
                <span class="font-semibold group-hover:text-blue-700">Stock Adjustment / Audits</span>
            </a>
            <a href="reports.php" class="flex items-center p-3 rounded hover:bg-blue-50 transition group">
                <span class="text-2xl mr-3">📊</span>
                <span class="font-semibold group-hover:text-blue-700">Reports & Analytics</span>
            </a>
            <!-- <a href="user_permissions.php" class="flex items-center p-3 rounded hover:bg-blue-50 transition group">
                <span class="text-2xl mr-3">🧑‍💻</span>
                <span class="font-semibold group-hover:text-blue-700">User Roles & Permissions</span>
            </a> -->
            <!-- <a href="activity_logs.php"" class="flex items-center p-3 rounded hover:bg-blue-50 transition group">
                <span class="text-2xl mr-3">🕓</span>
                <span class="font-semibold group-hover:text-blue-700">Activity Logs</span>
            </a> -->
            <a href="export_import.php" class="flex items-center p-3 rounded hover:bg-blue-50 transition group">
                <span class="text-2xl mr-3">🧾</span>
                <span class="font-semibold group-hover:text-blue-700">Export / Import</span>
            </a>
        </nav>
        <div class="border-t p-4">
            <a href="/dashboard/" class="block text-gray-600 hover:text-blue-600 mb-2">← Main Dashboard</a>
            <a href="/logout.php" class="block text-red-600 hover:underline">Logout</a>
        </div>
    </aside>
    <!-- Main Content Area -->
    <main class="flex-1 p-10">
        <h1 class="text-3xl font-bold mb-6">Inventory Dashboard</h1>
        <div class="p-8 bg-blue-50 rounded shadow text-blue-700 text-center">
            Select a function from the sidebar to begin managing inventory.
        </div>
    </main>
</body>
</html>