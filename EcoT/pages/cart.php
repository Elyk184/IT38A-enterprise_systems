<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Initialize total
$total = 0;
$cart_items = [];

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    try {
        $product_ids = array_keys($_SESSION['cart']);
        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
        $stmt = $conn->prepare("SELECT id, image FROM products WHERE id IN ($placeholders)");
        $stmt->execute($product_ids);
        $product_images = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $product) {
            $product_images[$product['id']] = $product['image'];
        }

        foreach ($_SESSION['cart'] as $product_id => $item) {
            $cart_items[$product_id] = $item;
            if (empty($cart_items[$product_id]['image']) && isset($product_images[$product_id])) {
                $cart_items[$product_id]['image'] = $product_images[$product_id];
            }
        }
    } catch (PDOException $e) {
        $cart_items = $_SESSION['cart'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="../CSS/userdashboard.css">
    <link rel="stylesheet" href="../CSS/cart.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

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

<div class="welcome-message">
    <h1>Shopping Cart</h1>
    <p>Review your selected items, adjust quantities, and proceed when ready.</p>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert success">
        <?php 
        echo $_SESSION['success'];
        unset($_SESSION['success']);
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert error">
        <?php 
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
    </div>
<?php endif; ?>

<div class="cart-container">
    <?php if (empty($_SESSION['cart'])): ?>
        <div class="empty-cart">
            <i class="fas fa-shopping-cart"></i>
            <h2>Your cart is empty</h2>
            <p>Add products from the dashboard to begin building your order.</p>
            <a href="dashboard.php" class="continue-shopping">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="cart-items">
            <?php foreach ($cart_items as $product_id => $item): ?>
                <div class="cart-item">
                    <div class="item-image">
                        <?php if (!empty($item['image'])): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <?php else: ?>
                            <div class="image-placeholder">
                                <i class="fas fa-image"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="item-details">
                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                        <p class="price">₱<?php echo number_format($item['price'], 2); ?></p>
                    </div>
                    <div class="quantity-controls">
                        <form action="../process/update_cart.php" method="POST" class="quantity-form">
                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                            <button type="submit" name="action" value="decrease" <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>>-</button>
                            <span class="quantity"><?php echo $item['quantity']; ?></span>
                            <button type="submit" name="action" value="increase">+</button>
                        </form>
                    </div>
                    <div class="item-total">
                        <span>Item total</span>
                        ₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                    </div>
                    <form action="../process/remove_from_cart.php" method="POST" class="remove-form">
                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                        <button type="submit" class="remove-btn"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
                <?php $total += $item['price'] * $item['quantity']; ?>
            <?php endforeach; ?>
        </div>
        
        <div class="cart-summary">
            <div class="total">
                <span>Total:</span>
                <span>₱<?php echo number_format($total, 2); ?></span>
            </div>
            <div class="cart-actions">
                <a href="dashboard.php" class="continue-shopping">Continue Shopping</a>
                <form action="checkout.php" method="POST">
                    <button type="submit" class="checkout-btn">Proceed to Checkout</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

</body>
</html> 