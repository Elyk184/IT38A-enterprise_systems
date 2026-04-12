<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get search query
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Prepare the SQL query
$sql = "SELECT * FROM products";
if (!empty($search)) {
    $sql .= " WHERE name LIKE :search OR description LIKE :search";
}
$sql .= " ORDER BY created_at DESC";

try {
    $stmt = $conn->prepare($sql);
    if (!empty($search)) {
        $searchParam = "%$search%";
        $stmt->bindParam(':search', $searchParam);
    }
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../CSS/userdashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="home-page">
<div class="header">
    <div class="search-bar" aria-label="Search products">
        <form action="" method="GET" role="search">
            <input type="text" name="search" placeholder="Search sustainable products..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" aria-label="Search"><i class="fas fa-search"></i></button>
        </form>
    </div>
    <div class="nav-icons">
        <a href="dashboard.php" title="Home" class="active"><i class="fas fa-home"></i><span>Home</span></a>
        <a href="cart.php" title="Cart"><i class="fas fa-shopping-cart"></i><span>Cart</span></a>
        <a href="my_orders.php" title="My Orders"><i class="fas fa-box"></i><span>Orders</span></a>
        <a href="notifications.php" title="Notifications"><i class="fas fa-bell"></i><span>Notifications</span></a>
        <a href="profile.php" title="Profile"><i class="fas fa-user"></i><span>Profile</span></a>
        <a href="../process/logout.php" title="Logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
</div>

<div class="dashboard-shell">
<div class="welcome-message">
    <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h1>
    <p>Find your next eco-friendly supply from curated picks and new arrivals.</p>
</div>

<div class="product-grid">
    <?php if (empty($products)): ?>
        <div class="no-products">
            <p>No products found.</p>
        </div>
    <?php else: ?>
        <?php foreach ($products as $product): ?>
            <div class="product-card">
                <a href="product_details.php?id=<?php echo $product['id']; ?>" class="product-link">
                    <div class="product-image-container">
                        <img src="<?php echo $product['image'] ? '../uploads/' . htmlspecialchars($product['image']) : 'https://via.placeholder.com/300x200?text=No+Image'; ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             class="product-image">
                    </div>
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                </a>
                <p><?php echo htmlspecialchars($product['description']); ?></p>
                <div class="price">₱<?php echo number_format($product['price'], 2); ?></div>
                <div class="actions">
                    <span class="stars">
                        <?php
                        for ($i = 0; $i < $product['rating']; $i++) {
                            echo '<i class="fas fa-star"></i>';
                        }
                        if ((int)$product['rating'] === 0) {
                            echo '<span class="no-rating">New</span>';
                        }
                        ?>
                    </span>
                    <div class="action-buttons">
                        <form action="../process/add_to_cart.php" method="POST">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <button type="submit" class="cart-btn"><i class="fas fa-cart-plus"></i><span>Add to Cart</span></button>
                        </form>
                        <form action="../process/add_to_cart.php" method="POST">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="hidden" name="buy_now" value="1">
                            <button type="submit" class="buy-now-btn"><i class="fas fa-bolt"></i><span>Buy Now</span></button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</div>

</body>
</html>
