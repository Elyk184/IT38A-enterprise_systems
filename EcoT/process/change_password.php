<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");
    exit();
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/profile.php");
    exit();
}

// Get form data
$current_password = $_POST['current_password'];
$new_password = $_POST['new_password'];
$confirm_password = $_POST['confirm_password'];

// Input validation
if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    $_SESSION['error'] = "All fields are required.";
    header("Location: ../pages/profile.php");
    exit();
}

if ($new_password !== $confirm_password) {
    $_SESSION['error'] = "New passwords do not match.";
    header("Location: ../pages/profile.php");
    exit();
}

if (strlen($new_password) < 6) {
    $_SESSION['error'] = "New password must be at least 6 characters long.";
    header("Location: ../pages/profile.php");
    exit();
}

try {
    // Get current user data
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = :id");
    $stmt->bindParam(':id', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        $_SESSION['error'] = "Current password is incorrect.";
        header("Location: ../pages/profile.php");
        exit();
    }

    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password
    $stmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :id");
    $stmt->bindParam(':password', $hashed_password);
    $stmt->bindParam(':id', $_SESSION['user_id']);
    $stmt->execute();

    $_SESSION['success'] = "Password changed successfully.";
} catch (PDOException $e) {
    $_SESSION['error'] = "Error changing password: " . $e->getMessage();
}

header("Location: ../pages/profile.php");
exit(); 