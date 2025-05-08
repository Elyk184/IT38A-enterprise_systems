<?php
session_start();
require_once '../config/db.php'; // Your database connection file

// Get form data
$email = trim($_POST['email']);
$password = $_POST['password'];

// Input validation
if (empty($email) || empty($password)) {
    $_SESSION['error'] = "All fields are required.";
    header("Location: ../pages/login.php");
    exit();
}

try {
    // Prepare SQL statement
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    // Check if user exists
    if ($stmt->rowCount() === 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: ../dashboard/admin_dashboard.php");
            } else {
                header("Location: ../dashboard/user_dashboard.php");
            }
            exit();
        } else {
            $_SESSION['error'] = "Invalid password.";
            header("Location: ../pages/login.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "User not found.";
        header("Location: ../pages/login.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: ../pages/login.php");
    exit();
}
