<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get cart items
$cart_items = [];
$subtotal = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    try {
        $placeholders = str_repeat('?,', count($_SESSION['cart']) - 1) . '?';
        $stmt = $conn->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
        $stmt->execute(array_keys($_SESSION['cart']));
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($products as $product) {
            $quantity = $_SESSION['cart'][$product['id']]['quantity'];
            $cart_items[] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity,
                'image' => $product['image']
            ];
            $subtotal += $product['price'] * $quantity;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error fetching cart items: " . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Initialize variables with default values
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $payment_method = isset($_POST['payment']) ? $_POST['payment'] : '';

    // Validate input
    $errors = [];
    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    if (empty($address)) {
        $errors[] = "Address is required.";
    }
    if (empty($phone)) {
        $errors[] = "Phone number is required.";
    }
    if (empty($payment_method)) {
        $errors[] = "Payment method is required.";
    }
    if (empty($cart_items)) {
        $errors[] = "Your cart is empty.";
    }

    if (empty($errors)) {
        try {
            // Start transaction
            $conn->beginTransaction();

            // Create order
            $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'pending')");
            $stmt->execute([$_SESSION['user_id'], $subtotal]);
            $order_id = $conn->lastInsertId();

            // Add order items
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($cart_items as $item) {
                $stmt->execute([$order_id, $item['id'], $item['quantity'], $item['price']]);
                
                // Update product stock
                $update_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $update_stock->execute([$item['quantity'], $item['id']]);
            }

            // Commit transaction
            $conn->commit();

            // Clear cart
            unset($_SESSION['cart']);

            // Store order ID and success message in session
            $_SESSION['order_id'] = $order_id;
            $_SESSION['success'] = "Order placed successfully!";
            header("Location: place_order.php");
            exit();
        } catch (PDOException $e) {
            // Rollback transaction on error
            $conn->rollBack();
            $errors[] = "Error processing order: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"/>
    <style>
        input[type="radio"] {
            accent-color: #2563eb;
        }
    </style>
</head>
<body class="bg-white min-h-screen relative overflow-hidden font-sans">
    <!-- Top teal header with icons -->
    <header class="bg-[#4dc1c7] flex justify-end items-center gap-6 px-8 py-4 relative z-10">
        <a href="dashboard.php"><i class="fas fa-home text-black text-xl cursor-pointer"></i></a>
        <a href="cart.php"><i class="fas fa-shopping-cart text-black text-xl cursor-pointer"></i></a>
        <a href="notifications.php"><i class="fas fa-bell text-black text-xl cursor-pointer"></i></a>
        <a href="profile.php"><i class="fas fa-user text-black text-xl cursor-pointer"></i></a>
    </header>

    <!-- Decorative circles -->
    <img alt="Decorative teal circle top left" class="absolute top-0 left-0 w-[150px] h-[150px] rounded-full opacity-30 -translate-x-1/2 -translate-y-1/2 pointer-events-none select-none" src="https://storage.googleapis.com/a1aa/image/24ea1d2d-4c85-411a-4ffe-b18d3011c45b.jpg"/>
    <img alt="Decorative teal circle bottom right" class="absolute bottom-0 right-0 w-[150px] h-[150px] rounded-full opacity-30 translate-x-1/2 translate-y-1/2 pointer-events-none select-none" src="https://storage.googleapis.com/a1aa/image/877ac420-5079-4edf-d025-70dcf83e6e01.jpg"/>

    <!-- Main content container -->
    <main class="max-w-5xl mx-auto mt-12 bg-white shadow-sm p-8">
        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-8">
            <!-- Products table -->
            <table class="w-full md:w-auto border-collapse text-sm text-black">
                <thead>
                    <tr>
                        <th class="text-left font-normal pb-4 pr-12">Products</th>
                        <th class="text-left font-normal pb-4 pr-12">Price</th>
                        <th class="text-left font-normal pb-4 pr-12">Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                        <tr class="align-top">
                            <td class="pr-12 flex items-center gap-3">
                                <?php if ($item['image']): ?>
                                    <img alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                         class="w-10 h-10 object-contain" 
                                         src="../uploads/<?php echo htmlspecialchars($item['image']); ?>"/>
                                <?php else: ?>
                                    <div class="w-10 h-10 bg-gray-200 flex items-center justify-center">
                                        <i class="fas fa-image text-gray-400"></i>
                                    </div>
                                <?php endif; ?>
                                <span><?php echo htmlspecialchars($item['name']); ?></span>
                            </td>
                            <td class="pr-12">₱<?php echo number_format($item['price'], 2); ?></td>
                            <td class="pr-12 text-xs text-gray-400 select-none">
                                <span class="inline-block border-t border-b border-gray-300 px-2 py-0.5 cursor-default">
                                    <?php echo $item['quantity']; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Shipping and payment form -->
            <form method="POST" class="w-full md:w-[280px] text-xs text-black">
                <fieldset class="mb-4">
                    <legend class="font-bold mb-2">Shipping Address</legend>
                    <input name="name" class="w-full border border-gray-300 text-xs px-2 py-1 mb-2 focus:outline-none focus:ring-1 focus:ring-[#4dc1c7]" 
                           placeholder="Name" type="text" required
                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"/>
                    <input name="address" class="w-full border border-gray-300 text-xs px-2 py-1 mb-2 focus:outline-none focus:ring-1 focus:ring-[#4dc1c7]" 
                           placeholder="Address" type="text" required
                           value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>"/>
                    <input name="phone" class="w-full border border-gray-300 text-xs px-2 py-1 focus:outline-none focus:ring-1 focus:ring-[#4dc1c7]" 
                           placeholder="Phone" type="text" required
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"/>
                </fieldset>

                <fieldset class="mb-4">
                    <legend class="font-bold mb-2">Payment</legend>
                    <label class="flex items-center gap-2 mb-1">
                        <input name="payment" type="radio" value="cod" <?php echo (!isset($_POST['payment']) || $_POST['payment'] === 'cod') ? 'checked' : ''; ?> required/>
                        <span>Cash on delivery</span>
                    </label>
                    <label class="flex items-center gap-2 mb-1">
                        <input name="payment" type="radio" value="gcash" <?php echo (isset($_POST['payment']) && $_POST['payment'] === 'gcash') ? 'checked' : ''; ?> required/>
                        <span>G cash</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input name="payment" type="radio" value="maya" <?php echo (isset($_POST['payment']) && $_POST['payment'] === 'maya') ? 'checked' : ''; ?> required/>
                        <span>Pay Maya</span>
                    </label>
                </fieldset>

                <div class="flex justify-between items-center font-bold text-xs mb-2">
                    <span>Subtotal</span>
                    <span>₱<?php echo number_format($subtotal, 2); ?></span>
                </div>

                <button type="submit" class="bg-[#2563eb] text-white text-xs px-3 py-1 rounded-sm hover:bg-[#1e40af] transition-colors w-full">
                    Place Order
                </button>
            </form>
        </div>
    </main>
</body>
</html>
