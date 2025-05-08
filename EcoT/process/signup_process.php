<?php
session_start();
require_once '../config/db.php'; // Database connection

// Collect form data
$name = trim($_POST['name']);
$email = trim($_POST['email']);
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];

// Input validation
if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
    $_SESSION['error'] = "All fields are required.";
    header("Location: ../pages/sign_up.php");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Invalid email format.";
    header("Location: ../pages/sign_up.php");
    exit();
}

if ($password !== $confirm_password) {
    $_SESSION['error'] = "Passwords do not match.";
    header("Location: ../pages/sign_up.php");
    exit();
}

// Check if email already exists
try {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Email is already registered.";
        header("Location: ../pages/sign_up.php");
        exit();
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $insert = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, 'user')");
    $insert->bindParam(':name', $name);
    $insert->bindParam(':email', $email);
    $insert->bindParam(':password', $hashedPassword);
    $insert->execute();

    // Set session and redirect to user dashboard
    $_SESSION['user_id'] = $conn->lastInsertId();
    $_SESSION['name'] = $name;
    $_SESSION['role'] = 'user';
    
    header("Location: ../pages/login.php");
    exit();

} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: ../pages/sign_up.php");
    exit();
}
