<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");
    exit();
}

// Check if product_id is provided
if (!isset($_POST['product_id'])) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: ../pages/cart.php");
    exit();
}

$product_id = $_POST['product_id'];

// Check if product exists in cart
if (!isset($_SESSION['cart'][$product_id])) {
    $_SESSION['error'] = "Product not found in cart.";
    header("Location: ../pages/cart.php");
    exit();
}

// Remove product from cart
unset($_SESSION['cart'][$product_id]);

$_SESSION['success'] = "Product removed from cart.";
header("Location: ../pages/cart.php");
exit(); 