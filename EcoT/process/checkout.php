<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");
    exit();
}

// Check if cart is empty
if (empty($_SESSION['cart'])) {
    $_SESSION['error'] = "Your cart is empty.";
    header("Location: ../pages/cart.php");
    exit();
}

// Redirect to checkout page
header("Location: ../pages/chekout.php");
exit(); 