<?php
require_once __DIR__ . '/../config/db.php';

function createNotification($user_id, $message, $type = 'system', $order_id = null) {
    global $conn;
    try {
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, message, type, order_id)
            VALUES (:user_id, :message, :type, :order_id)
        ");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':order_id', $order_id);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}

function createOrderNotification($user_id, $order_id, $status) {
    $messages = [
        'pending' => 'Your order has been placed and is pending confirmation.',
        'processing' => 'Your order is now being processed.',
        'completed' => 'Your order has been completed.',
        'cancelled' => 'Your order has been cancelled.'
    ];

    $message = $messages[$status] ?? 'Your order status has been updated.';
    return createNotification($user_id, $message, 'order', $order_id);
}

function createTrackingNotification($user_id, $order_id, $tracking_number) {
    $message = "Your order has been shipped! Tracking number: $tracking_number";
    return createNotification($user_id, $message, 'status', $order_id);
}

function getUnreadNotificationCount($user_id) {
    global $conn;
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(*) 
            FROM notifications 
            WHERE user_id = :user_id AND is_read = FALSE
        ");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error getting unread notification count: " . $e->getMessage());
        return 0;
    }
}
?> 