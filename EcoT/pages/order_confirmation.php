<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if order ID is provided
if (!isset($_GET['order_id'])) {
    header("Location: dashboard.php");
    exit();
}

$order_id = $_GET['order_id'];

// Get order details
try {
    $stmt = $conn->prepare("
        SELECT o.*, u.name, u.address, u.phone 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = :order_id AND o.user_id = :user_id
    ");
    $stmt->bindParam(':order_id', $order_id);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        header("Location: dashboard.php");
        exit();
    }

    // Get order items
    $stmt = $conn->prepare("
        SELECT oi.*, p.name, p.image 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = :order_id
    ");
    $stmt->bindParam(':order_id', $order_id);
    $stmt->execute();
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching order details: " . $e->getMessage();
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1" name="viewport"/>
    <title>Order Confirmation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet"/>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-[#f7f7f7] relative min-h-screen flex items-center justify-center p-6 overflow-hidden">
    <!-- Decorative circles -->
    <img alt="Decorative teal circle top left large" class="absolute top-0 left-0 w-28 h-28 rounded-full opacity-40 -translate-x-1/2 -translate-y-1/2 pointer-events-none select-none" src="https://storage.googleapis.com/a1aa/image/87d15db6-e1d6-4c5d-9e45-b043a9769a42.jpg"/>
    <img alt="Decorative teal circle top left small" class="absolute top-0 left-0 w-20 h-20 rounded-full opacity-40 -translate-x-1/4 -translate-y-1/4 pointer-events-none select-none" src="https://storage.googleapis.com/a1aa/image/edb35462-fd91-4f92-3e18-ff1060fdb6a5.jpg"/>
    <img alt="Decorative teal circle bottom right large" class="absolute bottom-0 right-0 w-28 h-28 rounded-full opacity-40 translate-x-1/2 translate-y-1/2 pointer-events-none select-none" src="https://storage.googleapis.com/a1aa/image/ae1e2941-8fa2-4269-60f1-eadbd3160189.jpg"/>
    <img alt="Decorative teal circle bottom right small" class="absolute bottom-0 right-0 w-20 h-20 rounded-full opacity-40 translate-x-1/4 translate-y-1/4 pointer-events-none select-none" src="https://storage.googleapis.com/a1aa/image/1c1a21cd-8f1b-41cf-8dbc-6b0df5ec1696.jpg"/>

    <main class="bg-white max-w-3xl w-full p-8 md:p-12 rounded-sm shadow-sm">
        <div class="flex flex-col items-center mb-8">
            <div class="bg-green-600 rounded-full p-3 mb-3">
                <i class="fas fa-check text-white text-xl"></i>
            </div>
            <p class="text-center font-bold text-sm leading-tight">
                Thank You!<br/>
                Your order has been placed.
            </p>
        </div>

        <section class="flex flex-col md:flex-row border border-gray-200 divide-y md:divide-y-0 md:divide-x divide-gray-300">
            <!-- Left side: Items -->
            <div class="flex-1 p-6 space-y-6">
                <?php foreach ($order_items as $item): ?>
                    <div class="flex space-x-4">
                        <img alt="<?php echo htmlspecialchars($item['name']); ?>" 
                             class="w-20 h-20 object-contain" 
                             src="<?php echo $item['image'] ? '../uploads/' . htmlspecialchars($item['image']) : 'https://via.placeholder.com/80'; ?>"/>
                        <div>
                            <p class="font-bold text-sm mb-1">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </p>
                            <p class="text-xs mb-1">
                                <?php echo $item['quantity']; ?>×₱<?php echo number_format($item['price'], 2); ?>
                            </p>
                            <p class="text-xs text-gray-700">
                                Subtotal: ₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Right side: Shipping info -->
            <div class="flex-1 p-6 text-xs leading-tight">
                <p class="font-bold text-sm mb-3">Shipped to</p>
                <p class="mb-2"><?php echo htmlspecialchars($order['name']); ?></p>
                <p class="mb-2">
                    <?php echo htmlspecialchars($order['address'] ?? 'No address provided'); ?>
                </p>
                <p><?php echo htmlspecialchars($order['phone'] ?? 'No phone provided'); ?></p>
            </div>
        </section>

        <section class="flex flex-col md:flex-row border-t border-gray-300 mt-6 pt-6 text-xs">
            <div class="flex-1 flex items-center justify-between mb-4 md:mb-0 md:border-r border-gray-300 pr-6 font-bold">
                <span>Total</span>
                <span>₱<?php echo number_format($order['total_amount'], 2); ?></span>
            </div>
            <div class="flex-1 flex space-x-4 justify-center md:justify-start">
                <a href="dashboard.php" class="bg-blue-600 text-white text-xs font-semibold py-2 px-6 rounded-sm hover:bg-blue-700 transition">
                    View Orders
                </a>
                <a href="index.php" class="border border-gray-700 text-gray-900 text-xs font-semibold py-2 px-6 rounded-sm hover:bg-gray-100 transition">
                    Continue Shopping
                </a>
            </div>
        </section>
    </main>
</body>
</html> 