<?php
session_start();
require_once '../config/db.php';
require_once '../includes/notification_functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../pages/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'] ?? null;
    $status = $_POST['status'] ?? null;
    $tracking_number = $_POST['tracking_number'] ?? null;

    if (!$order_id || !$status) {
        $_SESSION['error'] = "Missing required fields";
        header('Location: orders.php');
        exit();
    }

    try {
        $conn->beginTransaction();

        // Update order status
        $stmt = $conn->prepare("
            UPDATE orders 
            SET status = ?, 
                tracking_number = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$status, $tracking_number, $order_id]);

        // Get user_id for the order
        $stmt = $conn->prepare("SELECT user_id FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $user_id = $stmt->fetchColumn();

        // Create notification for status update
        createOrderNotification($user_id, $order_id, $status);

        // If tracking number is provided, create tracking notification
        if ($tracking_number) {
            createTrackingNotification($user_id, $order_id, $tracking_number);
        }

        $conn->commit();
        $_SESSION['success'] = "Order status updated successfully";

    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Error updating order status: " . $e->getMessage();
    }
}

header('Location: orders.php');
exit();
?> 