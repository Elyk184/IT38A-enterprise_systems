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
    <div class="search-bar">
        <form action="dashboard.php" method="GET">
            <input type="text" name="search" placeholder="Search products...">
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
    </div>
    <div class="nav-icons">
        <a href="dashboard.php"><i class="fas fa-home"></i></a>
        <a href="cart.php"><i class="fas fa-shopping-cart"></i></a>
        <a href="notifications.php"><i class="fas fa-bell"></i></a>
        <a href="profile.php"><i class="fas fa-user"></i></a>
        <a href="../process/logout.php"><i class="fas fa-sign-out-alt"></i></a>
    </div>
</div>

<div class="welcome-message">
    Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!
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
    <h2>Shopping Cart</h2>
    
    <?php if (empty($_SESSION['cart'])): ?>
        <div class="empty-cart">
            <i class="fas fa-shopping-cart"></i>
            <p>Your cart is empty</p>
            <a href="dashboard.php" class="continue-shopping">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="cart-items">
            <?php foreach ($_SESSION['cart'] as $product_id => $item): ?>
                <div class="cart-item">
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