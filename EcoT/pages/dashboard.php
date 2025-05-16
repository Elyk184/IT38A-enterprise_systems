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
<body>

<div class="header">
    <div class="search-bar">
        <form action="" method="GET">
            <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
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

<div class="product-grid">
    <?php if (empty($products)): ?>
        <div class="no-products">
            <p>No products found.</p>
        </div>
    <?php else: ?>
        <?php foreach ($products as $product): ?>
            <div class="product-card">
                <img src="../images/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                <p><?php echo htmlspecialchars($product['description']); ?></p>
                <div class="price">₱<?php echo number_format($product['price'], 2); ?></div>
                <div class="actions">
                    <span class="stars">
                        <?php
                        for ($i = 0; $i < $product['rating']; $i++) {
                            echo "⭐";
                        }
                        ?>
                    </span>
                    <form action="../process/add_to_cart.php" method="POST">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <button type="submit">Add to Cart</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>
