<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");
    exit();
}

// Check if required parameters are provided
if (!isset($_POST['product_id']) || !isset($_POST['action'])) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: ../pages/cart.php");
    exit();
}

$product_id = $_POST['product_id'];
$action = $_POST['action'];

// Check if product exists in cart
if (!isset($_SESSION['cart'][$product_id])) {
    $_SESSION['error'] = "Product not found in cart.";
    header("Location: ../pages/cart.php");
    exit();
}

// Update quantity
if ($action === 'increase') {
    $_SESSION['cart'][$product_id]['quantity']++;
} elseif ($action === 'decrease') {
    if ($_SESSION['cart'][$product_id]['quantity'] > 1) {
        $_SESSION['cart'][$product_id]['quantity']--;
    }
}

$_SESSION['success'] = "Cart updated successfully.";
header("Location: ../pages/cart.php");
exit(); 