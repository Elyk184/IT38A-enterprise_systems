<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit();
}

// Handle product deletion
if (isset($_POST['delete_product'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM products WHERE id = :id");
        $stmt->bindParam(':id', $_POST['product_id']);
        $stmt->execute();
        $_SESSION['success'] = "Product deleted successfully.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting product: " . $e->getMessage();
    }
    header("Location: Manage_Products.php");
    exit();
}

// Get all products
try {
    $stmt = $conn->query("SELECT * FROM products ORDER BY created_at DESC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching products: " . $e->getMessage();
    $products = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Products</title>
    <link rel="stylesheet" href="../CSS/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="admin-header">
    <div class="admin-title">
        <h1>Manage Products</h1>
    </div>
    <div class="admin-nav">
        <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="Manage_Products.php" class="active"><i class="fas fa-box"></i> Products</a>
        <a href="Orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
        <a href="../process/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
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

<div class="dashboard-container">
    <div class="action-bar">
        <a href="add_product.php" class="add-btn">
            <i class="fas fa-plus"></i> Add New Product
        </a>
    </div>

    <div class="products-table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td>#<?php echo $product['id']; ?></td>
                        <td>
                            <?php if ($product['image']): ?>
                                <img src="../uploads/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-thumbnail">
                            <?php else: ?>
                                <div class="no-image">No Image</div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td>₱<?php echo number_format($product['price'], 2); ?></td>
                        <td><?php echo $product['stock']; ?></td>
                        <td>
                            <span class="status-badge <?php echo $product['stock'] > 0 ? 'completed' : 'cancelled'; ?>">
                                <?php echo $product['stock'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="edit-btn">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="" method="POST" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" name="delete_product" class="delete-btn">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
