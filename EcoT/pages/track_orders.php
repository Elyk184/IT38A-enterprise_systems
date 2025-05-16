<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle order confirmation
if (isset($_POST['confirm_receipt'])) {
    try {
        $stmt = $conn->prepare("UPDATE orders SET status = 'completed' WHERE id = :order_id AND user_id = :user_id");
        $stmt->bindParam(':order_id', $_POST['order_id']);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "Order marked as received successfully!";
        } else {
            $_SESSION['error'] = "Unable to update order status.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating order: " . $e->getMessage();
    }
    header("Location: track_orders.php");
    exit();
}

// Handle item receipt confirmation
if (isset($_POST['receive_item'])) {
    try {
        $stmt = $conn->prepare("
            UPDATE order_items 
            SET received = 1, received_at = CURRENT_TIMESTAMP 
            WHERE id = :item_id AND order_id IN (
                SELECT id FROM orders WHERE user_id = :user_id
            )
        ");
        $stmt->bindParam(':item_id', $_POST['item_id']);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "Item marked as received successfully!";
        } else {
            $_SESSION['error'] = "Unable to update item status.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating item: " . $e->getMessage();
    }
    header("Location: track_orders.php");
    exit();
}

// Get user's orders with item details
try {
    $stmt = $conn->prepare("
        SELECT o.*, 
               oi.id as item_id,
               oi.quantity,
               oi.price,
               oi.received,
               oi.received_at,
               p.name as product_name,
               p.image
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE o.user_id = :user_id
        ORDER BY o.created_at DESC, oi.id ASC
    ");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group items by order
    $orders = [];
    foreach ($order_items as $item) {
        $order_id = $item['id'];
        if (!isset($orders[$order_id])) {
            $orders[$order_id] = [
                'id' => $order_id,
                'created_at' => $item['created_at'],
                'status' => $item['status'],
                'total_amount' => $item['total_amount'],
                'items' => []
            ];
        }
        $orders[$order_id]['items'][] = $item;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching orders: " . $e->getMessage();
    $orders = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Orders</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"/>
</head>
<body class="bg-gray-100 min-h-screen">
    <header class="bg-[#4dc1c7] flex justify-end items-center gap-6 px-8 py-4">
        <a href="dashboard.php"><i class="fas fa-home text-black text-xl"></i></a>
        <a href="cart.php"><i class="fas fa-shopping-cart text-black text-xl"></i></a>
        <a href="notifications.php"><i class="fas fa-bell text-black text-xl"></i></a>
        <a href="profile.php"><i class="fas fa-user text-black text-xl"></i></a>
    </header>

    <main class="max-w-4xl mx-auto mt-8 p-6">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Track Your Orders</h1>

            <?php if (empty($orders)): ?>
                <div class="text-center py-8">
                    <i class="fas fa-box-open text-gray-400 text-5xl mb-4"></i>
                    <p class="text-gray-600">You haven't placed any orders yet.</p>
                    <a href="dashboard.php" class="inline-block mt-4 text-blue-600 hover:text-blue-800">
                        Start Shopping
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($orders as $order): ?>
                        <div class="border rounded-lg p-4">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="font-semibold">Order #<?php echo $order['id']; ?></h3>
                                    <p class="text-sm text-gray-600">
                                        Placed on <?php echo date('F j, Y', strtotime($order['created_at'])); ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="inline-block px-3 py-1 rounded-full text-sm font-medium
                                        <?php
                                        switch($order['status']) {
                                            case 'pending':
                                                echo 'bg-yellow-100 text-yellow-800';
                                                break;
                                            case 'processing':
                                                echo 'bg-blue-100 text-blue-800';
                                                break;
                                            case 'completed':
                                                echo 'bg-green-100 text-green-800';
                                                break;
                                            case 'cancelled':
                                                echo 'bg-red-100 text-red-800';
                                                break;
                                            default:
                                                echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="mb-4">
                                <p class="text-sm text-gray-600 mb-2">
                                    <strong>Total Amount:</strong> ₱<?php echo number_format($order['total_amount'], 2); ?>
                                </p>
                                
                                <div class="mt-4 space-y-3">
                                    <?php foreach ($order['items'] as $item): ?>
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                            <div class="flex items-center space-x-3">
                                                <?php if ($item['image']): ?>
                                                    <img src="../uploads/<?php echo htmlspecialchars($item['image']); ?>" 
                                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                         class="w-12 h-12 object-cover rounded">
                                                <?php else: ?>
                                                    <div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center">
                                                        <i class="fas fa-image text-gray-400"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <p class="font-medium"><?php echo htmlspecialchars($item['product_name']); ?></p>
                                                    <p class="text-sm text-gray-600">
                                                        Quantity: <?php echo $item['quantity']; ?> × ₱<?php echo number_format($item['price'], 2); ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <?php if ($item['received']): ?>
                                                    <span class="text-green-600 text-sm">
                                                        <i class="fas fa-check-circle"></i> Received
                                                        <br>
                                                        <span class="text-xs">
                                                            <?php echo date('M j, Y', strtotime($item['received_at'])); ?>
                                                        </span>
                                                    </span>
                                                <?php elseif ($order['status'] === 'processing'): ?>
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                                        <button type="submit" name="receive_item" 
                                                                class="bg-green-600 text-white px-3 py-1 rounded-md hover:bg-green-700 transition text-sm">
                                                            Receive Item
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <?php if ($order['status'] === 'processing'): ?>
                                <form method="POST" class="mt-4">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <button type="submit" name="confirm_receipt" 
                                            class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition">
                                        Confirm All Items Received
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html> 