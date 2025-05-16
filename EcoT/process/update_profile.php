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
$name = trim($_POST['name']);
$email = trim($_POST['email']);

// Input validation
if (empty($name) || empty($email)) {
    $_SESSION['error'] = "All fields are required.";
    header("Location: ../pages/profile.php");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Invalid email format.";
    header("Location: ../pages/profile.php");
    exit();
}

try {
    // Check if email is already taken by another user
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':id', $_SESSION['user_id']);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Email is already taken.";
        header("Location: ../pages/profile.php");
        exit();
    }

    // Update user information
    $stmt = $conn->prepare("UPDATE users SET name = :name, email = :email WHERE id = :id");
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':id', $_SESSION['user_id']);
    $stmt->execute();

    // Update session
    $_SESSION['name'] = $name;

    $_SESSION['success'] = "Profile updated successfully.";
} catch (PDOException $e) {
    $_SESSION['error'] = "Error updating profile: " . $e->getMessage();
}

header("Location: ../pages/profile.php");
exit(); 