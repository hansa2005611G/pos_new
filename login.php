<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) session_start();

// If already logged in, redirect based on role
if (isset($_SESSION['role'])) {
    header('Location: /dashboard/');
    exit;
}

// Optional: Show error from previous attempt
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>POS Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <form action="/auth.php" method="post" class="bg-white p-8 rounded shadow-md w-full max-w-sm">
        <h2 class="text-2xl font-bold mb-6 text-center">Login</h2>
        <?php if ($error): ?>
            <div class="mb-4 text-red-600 text-sm bg-red-100 p-2 rounded"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <div class="mb-4">
            <label for="username" class="block mb-1 font-semibold">Username</label>
            <input type="text" name="username" id="username" required class="w-full border px-3 py-2 rounded focus:outline-none focus:ring focus:border-blue-300">
        </div>
        <div class="mb-6">
            <label for="password" class="block mb-1 font-semibold">Password</label>
            <input type="password" name="password" id="password" required class="w-full border px-3 py-2 rounded focus:outline-none focus:ring focus:border-blue-300">
        </div>
        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">Login</button>
    </form>
</body>
</html>