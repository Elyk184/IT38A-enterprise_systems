<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");
    exit();
}

// Check if product_id is provided
if (!isset($_POST['product_id'])) {
    $_SESSION['error'] = "Invalid product selection.";
    header("Location: ../pages/dashboard.php");
    exit();
}

$product_id = $_POST['product_id'];

try {
    // Get product details
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->bindParam(':id', $product_id);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception("Product not found.");
    }

    // Initialize cart if it doesn't exist
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }

    // Add product to cart
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity']++;
    } else {
        $_SESSION['cart'][$product_id] = array(
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => 1
        );
    }

    $_SESSION['success'] = "Product added to cart successfully!";
} catch (Exception $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
}

header("Location: ../pages/dashboard.php");
exit(); 