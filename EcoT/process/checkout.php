<?php
session_start();
require_once '../config/db.php';
require_once '../includes/notification_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");
    exit();
}

// Check if cart is empty
if (empty($_SESSION['cart'])) {
    $_SESSION['error'] = "Your cart is empty";
    header("Location: ../pages/cart.php");
    exit();
}

try {
    $conn->beginTransaction();

    // Calculate total amount
    $total_amount = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total_amount += $item['price'] * $item['quantity'];
    }

    // Get user information
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Create order
    $stmt = $conn->prepare("
        INSERT INTO orders (user_id, total_amount, status) 
        VALUES (?, ?, 'pending')
    ");
    $stmt->execute([$_SESSION['user_id'], $total_amount]);
    $order_id = $conn->lastInsertId();

    // Add order items and update stock
    foreach ($_SESSION['cart'] as $product_id => $item) {
        // Add order item
        $stmt = $conn->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$order_id, $product_id, $item['quantity'], $item['price']]);

        // Update product stock
        $stmt = $conn->prepare("
            UPDATE products 
            SET stock = stock - ? 
            WHERE id = ?
        ");
        $stmt->execute([$item['quantity'], $product_id]);
    }

    // Create notification for order placement
    $message = "Your order #" . $order_id . " has been placed successfully!\n\n";
    $message .= "Total Amount: ₱" . number_format($total_amount, 2) . "\n";
    $message .= "Status: Pending\n\n";
    $message .= "You can track your order status in the Orders section.";
    
    createNotification($_SESSION['user_id'], $order_id, $message, 'order_placed');

    // Clear cart
    unset($_SESSION['cart']);

    $conn->commit();
    $_SESSION['success'] = "Order placed successfully!";
    header("Location: ../pages/place_order.php");
    exit();

} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['error'] = "Error placing order: " . $e->getMessage();
    header("Location: ../pages/cart.php");
    exit();
}
?> 