<?php
session_start();
require_once 'db.php'; // includes $pdo

// Input validation
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    header('Location: /login.php?error=' . urlencode('Please fill in both fields.'));
    exit;
}

// Prepare and execute user lookup
$stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    // Password correct: set session
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    // Redirect by role
    header('Location: /dashboard/');
    exit;
} else {
    // Wrong username or password
    header('Location: /login.php?error=' . urlencode('Invalid username or password.'));
    exit;
}
?>