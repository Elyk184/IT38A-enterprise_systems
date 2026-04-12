<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/forgot_password.php');
    exit();
}

$email = trim($_POST['email'] ?? '');
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($email) || empty($new_password) || empty($confirm_password)) {
    $_SESSION['error'] = 'All fields are required.';
    header('Location: ../pages/forgot_password.php');
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Please enter a valid email address.';
    header('Location: ../pages/forgot_password.php');
    exit();
}

if ($new_password !== $confirm_password) {
    $_SESSION['error'] = 'Passwords do not match.';
    header('Location: ../pages/forgot_password.php');
    exit();
}

if (strlen($new_password) < 6) {
    $_SESSION['error'] = 'Password must be at least 6 characters long.';
    header('Location: ../pages/forgot_password.php');
    exit();
}

try {
    $stmt = $conn->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error'] = 'No account found for that email.';
        header('Location: ../pages/forgot_password.php');
        exit();
    }

    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    $update = $conn->prepare('UPDATE users SET password = :password WHERE id = :id');
    $update->bindParam(':password', $hashed_password);
    $update->bindParam(':id', $user['id']);
    $update->execute();

    $_SESSION['success'] = 'Password reset successful. You can now log in.';
    header('Location: ../pages/login.php');
    exit();
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error resetting password: ' . $e->getMessage();
    header('Location: ../pages/forgot_password.php');
    exit();
}
