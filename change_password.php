<?php
session_start();
require 'db.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // Fetch current password hash
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($current, $user['password'])) {
        $error = "Current password is incorrect.";
    } elseif (strlen($new) < 8) {
        $error = "New password must be at least 8 characters.";
    } elseif ($new !== $confirm) {
        $error = "New passwords do not match.";
    } elseif (password_verify($new, $user['password'])) {
        $error = "New password must be different from the current password.";
    } else {
        // Update password
        $new_hash = password_hash($new, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->execute([$new_hash, $_SESSION['user_id']]);
        $success = "Password updated successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <form action="change_password.php" method="post" class="bg-white p-8 rounded shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-center">Change Password</h2>
        <?php if ($error): ?>
            <div class="mb-4 text-red-600 text-sm bg-red-100 p-2 rounded"><?= htmlspecialchars($error) ?></div>
        <?php elseif ($success): ?>
            <div class="mb-4 text-green-600 text-sm bg-green-100 p-2 rounded"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <div class="mb-4">
            <label for="current_password" class="block mb-1 font-semibold">Current Password</label>
            <input type="password" name="current_password" id="current_password" required class="w-full border px-3 py-2 rounded focus:outline-none focus:ring focus:border-blue-300">
        </div>
        <div class="mb-4">
            <label for="new_password" class="block mb-1 font-semibold">New Password</label>
            <input type="password" name="new_password" id="new_password" required minlength="8" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring focus:border-blue-300">
            <span class="text-xs text-gray-500">At least 8 characters.</span>
        </div>
        <div class="mb-6">
            <label for="confirm_password" class="block mb-1 font-semibold">Confirm New Password</label>
            <input type="password" name="confirm_password" id="confirm_password" required minlength="8" class="w-full border px-3 py-2 rounded focus:outline-none focus:ring focus:border-blue-300">
        </div>
        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">Update Password</button>
        <a href="/dashboard/" class="block mt-4 text-gray-700 hover:underline text-center">Back to Dashboard</a>
    </form>
</body>
</html>