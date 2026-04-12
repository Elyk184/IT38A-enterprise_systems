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

    // Initialize field-specific errors
    $field_errors = [
        'name' => '',
        'address' => '',
        'phone' => '',
        'payment' => ''
    ];

    // Validate input
    $has_errors = false;
    if (empty($name)) {
        $field_errors['name'] = "Name is required.";
        $has_errors = true;
    }
    if (empty($address)) {
        $field_errors['address'] = "Address is required.";
        $has_errors = true;
    }
    if (empty($phone)) {
        $field_errors['phone'] = "Phone number is required.";
        $has_errors = true;
    }
    if (empty($payment_method)) {
        $field_errors['payment'] = "Payment method is required.";
        $has_errors = true;
    }
    if (empty($cart_items)) {
        $_SESSION['error'] = "Your cart is empty.";
        header("Location: cart.php");
        exit();
    }

    if (!$has_errors) {
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
            $_SESSION['error'] = "Error processing order: " . $e->getMessage();
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
    <link rel="stylesheet" href="../CSS/userdashboard.css">
    <style>
        :root {
            --checkout-surface: rgba(255, 255, 255, 0.96);
            --checkout-border: rgba(15, 118, 110, 0.12);
            --checkout-shadow: 0 18px 34px rgba(15, 23, 42, 0.08);
            --checkout-text: #0f172a;
            --checkout-muted: #64748b;
            --checkout-accent: #14b8a6;
            --checkout-accent-dark: #0f766e;
        }

        * {
            box-sizing: border-box;
        }

        body {
            padding-top: 118px;
            font-family: 'Inter', sans-serif;
        }

        input[type="radio"] {
            accent-color: #2563eb;
        }

        .checkout-shell {
            width: min(1180px, 94vw);
            margin: 0 auto 44px;
        }

        .checkout-hero {
            background: linear-gradient(135deg, rgba(20, 184, 166, 0.12), rgba(255, 255, 255, 0.96));
            border: 1px solid var(--checkout-border);
            border-radius: 20px;
            padding: 22px 26px;
            box-shadow: var(--checkout-shadow);
            margin-bottom: 18px;
        }

        .checkout-hero h1 {
            margin: 0;
            font-size: clamp(1.35rem, 2vw, 2rem);
            font-weight: 800;
            color: var(--checkout-text);
        }

        .checkout-hero p {
            margin: 8px 0 0;
            color: var(--checkout-muted);
            font-size: 0.98rem;
        }

        .checkout-panel {
            background: var(--checkout-surface);
            border: 1px solid var(--checkout-border);
            border-radius: 22px;
            box-shadow: var(--checkout-shadow);
            overflow: visible;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(320px, 0.9fr);
            gap: 0;
            align-items: start;
        }

        .checkout-products,
        .checkout-form-wrap {
            padding: 24px;
            position: sticky;
            top: 136px;
            align-self: start;
        }

        .checkout-products {
            border-right: 1px solid rgba(226, 232, 240, 0.9);
        }

        .section-title {
            margin: 0 0 14px;
            font-size: 1rem;
            font-weight: 800;
            color: var(--checkout-text);
        }

        .product-table {
            width: 100%;
            border-collapse: collapse;
        }

        .product-table th {
            text-align: left;
            padding: 0 0 12px;
            color: #475569;
            font-size: 0.8rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .product-table td {
            padding: 14px 0;
            border-top: 1px solid rgba(226, 232, 240, 0.85);
            vertical-align: top;
            color: var(--checkout-text);
        }

        .product-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .product-thumb {
            width: 52px;
            height: 52px;
            flex: 0 0 52px;
            border-radius: 14px;
            background: linear-gradient(135deg, #f1f5f9, #e2f8f5);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 1px solid rgba(203, 213, 225, 0.8);
        }

        .product-thumb img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 6px;
        }

        .product-name {
            font-weight: 700;
            margin: 0;
        }

        .product-price {
            color: #0f766e;
            font-weight: 800;
        }

        .qty-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            padding: 6px 10px;
            border-radius: 999px;
            background: #f8fafc;
            border: 1px solid rgba(203, 213, 225, 0.9);
            font-weight: 800;
            color: var(--checkout-text);
        }

        .checkout-form-card {
            background: #fbfeff;
            border: 1px solid rgba(226, 232, 240, 0.95);
            border-radius: 18px;
            padding: 18px;
        }

        .checkout-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            margin-bottom: 16px;
            border-radius: 16px;
            background: linear-gradient(135deg, rgba(20, 184, 166, 0.08), rgba(255, 255, 255, 0.96));
            border: 1px solid rgba(20, 184, 166, 0.12);
        }

        .checkout-summary .label {
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #64748b;
        }

        .checkout-summary .value {
            font-size: 1.2rem;
            font-weight: 800;
            color: #0f172a;
        }

        .field-group {
            margin-bottom: 14px;
        }

        .field-group label,
        .payment-group legend {
            display: block;
            font-size: 0.8rem;
            font-weight: 800;
            color: #334155;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .checkout-input {
            width: 100%;
            border: 1px solid #dbe7e5;
            border-radius: 12px;
            padding: 11px 12px;
            font-size: 0.95rem;
            outline: none;
            background: #fff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .checkout-input:focus {
            border-color: var(--checkout-accent);
            box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.14);
        }

        .field-error {
            margin-top: 6px;
            font-size: 0.82rem;
            color: #b91c1c;
        }

        .payment-group {
            margin-bottom: 14px;
        }

        .payment-group legend {
            margin-bottom: 10px;
        }

        .payment-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #fff;
            margin-bottom: 10px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }

        .payment-option:hover {
            transform: translateY(-1px);
            border-color: rgba(20, 184, 166, 0.28);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
        }

        .payment-option input:checked + span {
            color: #0f766e;
            font-weight: 800;
        }

        .payment-option input {
            transform: scale(1.05);
        }

        .subtotal-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 18px 0 14px;
            padding-top: 14px;
            border-top: 1px solid rgba(226, 232, 240, 0.9);
            font-weight: 800;
            color: var(--checkout-text);
        }

        .checkout-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .checkout-btn {
            width: 100%;
            border: none;
            border-radius: 12px;
            padding: 13px 16px;
            color: #fff;
            font-size: 0.98rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--checkout-accent), var(--checkout-accent-dark));
            cursor: pointer;
            transition: transform 0.2s ease, filter 0.2s ease;
        }

        .checkout-btn:hover {
            transform: translateY(-1px);
            filter: brightness(1.04);
        }

        .empty-checkout {
            text-align: center;
            padding: 42px 24px;
            border: 1px dashed #cbd5e1;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.82);
        }

        .empty-checkout i {
            font-size: 52px;
            color: #94a3b8;
            margin-bottom: 12px;
        }

        .empty-checkout h2 {
            margin: 0 0 8px;
            color: var(--checkout-text);
        }

        .empty-checkout p {
            margin: 0 0 18px;
            color: var(--checkout-muted);
        }

        .empty-checkout a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 11px 18px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 800;
            color: white;
            background: linear-gradient(135deg, var(--checkout-accent), var(--checkout-accent-dark));
        }

        @media (max-width: 900px) {
            body {
                padding-top: 156px;
            }

            .checkout-grid {
                grid-template-columns: 1fr;
            }

            .checkout-products {
                border-right: none;
                border-bottom: 1px solid rgba(226, 232, 240, 0.9);
            }

            .checkout-form-wrap {
                position: static;
                top: auto;
            }
        }

        @media (max-width: 640px) {
            body {
                padding-top: 166px;
            }

            .checkout-shell {
                width: 94vw;
            }

            .checkout-hero,
            .checkout-products,
            .checkout-form-wrap {
                padding: 18px;
            }

            .product-table,
            .product-table thead,
            .product-table tbody,
            .product-table tr,
            .product-table td,
            .product-table th {
                display: block;
                width: 100%;
            }

            .product-table thead {
                display: none;
            }

            .product-table tr {
                padding: 12px 0;
            }

            .product-table td {
                border-top: none;
                padding: 6px 0;
            }

            .product-table td:last-child {
                padding-bottom: 0;
            }

            .checkout-summary {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body class="bg-white min-h-screen relative font-sans">
    <div class="dashboard-shell" style="position: fixed; top: 0; left: 0; right: 0; z-index: 20; width: min(1260px, 94vw); margin: 16px auto 0;">
        <div class="header">
            <div class="search-bar" aria-label="Search products">
                <form action="dashboard.php" method="GET" role="search">
                    <input type="text" name="search" placeholder="Search sustainable products...">
                    <button type="submit" aria-label="Search"><i class="fas fa-search"></i></button>
                </form>
            </div>
            <div class="nav-icons">
                <a href="dashboard.php" title="Home"><i class="fas fa-home"></i><span>Home</span></a>
                <a href="cart.php" title="Cart" class="active"><i class="fas fa-shopping-cart"></i><span>Cart</span></a>
                <a href="my_orders.php" title="My Orders"><i class="fas fa-box"></i><span>Orders</span></a>
                <a href="notifications.php" title="Notifications"><i class="fas fa-bell"></i><span>Notifications</span></a>
                <a href="profile.php" title="Profile"><i class="fas fa-user"></i><span>Profile</span></a>
                <a href="../process/logout.php" title="Logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </div>
        </div>
    </div>

    <!-- Decorative circles -->
    <img alt="Decorative teal circle top left" class="absolute top-0 left-0 w-[150px] h-[150px] rounded-full opacity-30 -translate-x-1/2 -translate-y-1/2 pointer-events-none select-none" src="https://storage.googleapis.com/a1aa/image/24ea1d2d-4c85-411a-4ffe-b18d3011c45b.jpg"/>
    <img alt="Decorative teal circle bottom right" class="absolute bottom-0 right-0 w-[150px] h-[150px] rounded-full opacity-30 translate-x-1/2 translate-y-1/2 pointer-events-none select-none" src="https://storage.googleapis.com/a1aa/image/877ac420-5079-4edf-d025-70dcf83e6e01.jpg"/>

    <!-- Main content container -->
    <main class="checkout-shell">
        <section class="checkout-hero">
            <h1>Checkout</h1>
            <p>Review your order, confirm delivery details, and choose a payment method.</p>
        </section>

        <?php if (!empty($errors)): ?>
            <div class="checkout-form-card" style="margin-bottom: 16px; border-color: rgba(239,68,68,0.25); background: #fff5f5;">
                <?php foreach ($errors as $error): ?>
                    <p class="field-error" style="margin: 0 0 4px; font-size: 0.9rem;"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <section class="checkout-panel">
                <div class="empty-checkout">
                    <i class="fas fa-shopping-bag"></i>
                    <h2>Your cart is empty</h2>
                    <p>Add items from the dashboard before checking out.</p>
                    <a href="dashboard.php">Continue Shopping</a>
                </div>
            </section>
        <?php else: ?>
            <section class="checkout-panel">
                <div class="checkout-grid">
                    <div class="checkout-products">
                        <h2 class="section-title">Products</h2>
                        <table class="product-table">
                            <thead>
                                <tr>
                                    <th>Products</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="product-item">
                                                <div class="product-thumb">
                                                    <?php if ($item['image']): ?>
                                                        <img alt="<?php echo htmlspecialchars($item['name']); ?>" src="../uploads/<?php echo htmlspecialchars($item['image']); ?>"/>
                                                    <?php else: ?>
                                                        <i class="fas fa-image text-gray-400"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <p class="product-name"><?php echo htmlspecialchars($item['name']); ?></p>
                                            </div>
                                        </td>
                                        <td class="product-price">₱<?php echo number_format($item['price'], 2); ?></td>
                                        <td><span class="qty-pill"><?php echo $item['quantity']; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="checkout-form-wrap">
                        <h2 class="section-title">Shipping & Payment</h2>
                        <div class="checkout-form-card">
                            <form method="POST">
                                <fieldset class="payment-group">
                                    <legend>Shipping Address</legend>
                                    <div class="field-group">
                                        <input name="name" class="checkout-input" placeholder="Name" type="text" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"/>
                                        <?php if (!empty($field_errors['name'])): ?>
                                            <p class="field-error"><?php echo $field_errors['name']; ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="field-group">
                                        <input name="address" class="checkout-input" placeholder="Address" type="text" required value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>"/>
                                        <?php if (!empty($field_errors['address'])): ?>
                                            <p class="field-error"><?php echo $field_errors['address']; ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="field-group">
                                        <input name="phone" class="checkout-input" placeholder="Phone" type="text" required value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"/>
                                        <?php if (!empty($field_errors['phone'])): ?>
                                            <p class="field-error"><?php echo $field_errors['phone']; ?></p>
                                        <?php endif; ?>
                                    </div>
                                </fieldset>

                                <fieldset class="payment-group">
                                    <legend>Payment</legend>
                                    <label class="payment-option">
                                        <input name="payment" type="radio" value="cod" <?php echo (!isset($_POST['payment']) || $_POST['payment'] === 'cod') ? 'checked' : ''; ?> required/>
                                        <span><i class="fas fa-truck"></i> Cash on delivery</span>
                                    </label>
                                    <label class="payment-option">
                                        <input name="payment" type="radio" value="gcash" <?php echo (isset($_POST['payment']) && $_POST['payment'] === 'gcash') ? 'checked' : ''; ?> required/>
                                        <span><i class="fas fa-wallet"></i> GCash</span>
                                    </label>
                                    <label class="payment-option">
                                        <input name="payment" type="radio" value="maya" <?php echo (isset($_POST['payment']) && $_POST['payment'] === 'maya') ? 'checked' : ''; ?> required/>
                                        <span><i class="fas fa-credit-card"></i> PayMaya</span>
                                    </label>
                                    <?php if (!empty($field_errors['payment'])): ?>
                                        <p class="field-error"><?php echo $field_errors['payment']; ?></p>
                                    <?php endif; ?>
                                </fieldset>

                                <div class="checkout-summary">
                                    <span class="label">Subtotal</span>
                                    <span class="value">₱<?php echo number_format($subtotal, 2); ?></span>
                                </div>

                                <div class="checkout-actions">
                                    <button type="submit" class="checkout-btn"><i class="fas fa-lock"></i> Place Order</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
