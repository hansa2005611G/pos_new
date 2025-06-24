<?php
session_start();
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: /login.php');
    exit;
}

// Optional: redirect to role-specific page
$role = $_SESSION['role'];
$dashboardTitles = [
    'admin' => 'Admin Dashboard',
    'manager' => 'Manager Dashboard',
    'cashier' => 'Cashier Dashboard'
];
$title = $dashboardTitles[$role] ?? 'Dashboard';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-2xl mx-auto p-8 mt-10 bg-white rounded shadow">
        <h1 class="text-3xl font-bold mb-4"><?= htmlspecialchars($title) ?></h1>
        <p class="mb-2">Welcome, <span class="font-semibold"><?= htmlspecialchars($_SESSION['username']) ?></span>!</p>
        <p class="mb-4">Your role: <span class="font-mono bg-gray-200 rounded px-2 py-1"><?= htmlspecialchars($role) ?></span></p>

        <?php if ($role === 'admin'): ?>
            <div class="mb-4 p-4 bg-blue-100 rounded">[Admin-specific content]</div>
        <?php elseif ($role === 'manager'): ?>
            <div class="mb-4 p-4 bg-green-100 rounded">[Manager-specific content]</div>
        <?php elseif ($role === 'cashier'): ?>
            <div class="mb-4 p-4 bg-yellow-100 rounded">[Cashier-specific content]</div>
        <?php endif; ?>

        <a href="/logout.php" class="inline-block mt-4 text-red-600 hover:underline">Logout</a>
    </div>
</body>
</html>