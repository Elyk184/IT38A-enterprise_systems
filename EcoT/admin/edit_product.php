<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit();
}

// Check if product ID is provided
if (!isset($_GET['id'])) {
    header("Location: Manage_Products.php");
    exit();
}

$product_id = $_GET['id'];

// Get product details
try {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->bindParam(':id', $product_id);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $_SESSION['error'] = "Product not found.";
        header("Location: Manage_Products.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching product: " . $e->getMessage();
    header("Location: Manage_Products.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $image = $product['image']; // Keep existing image by default

    // Validate input
    $errors = [];
    if (empty($name)) {
        $errors[] = "Product name is required.";
    }
    if (empty($description)) {
        $errors[] = "Product description is required.";
    }
    if ($price <= 0) {
        $errors[] = "Price must be greater than 0.";
    }
    if ($stock < 0) {
        $errors[] = "Stock cannot be negative.";
    }

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $errors[] = "Invalid image type. Only JPG, PNG, and GIF are allowed.";
        } elseif ($_FILES['image']['size'] > $max_size) {
            $errors[] = "Image size must be less than 5MB.";
        } else {
            $upload_dir = '../uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Delete old image if exists
            if ($product['image'] && file_exists($upload_dir . $product['image'])) {
                unlink($upload_dir . $product['image']);
            }

            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $image;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $errors[] = "Failed to upload image.";
            }
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("UPDATE products SET name = :name, description = :description, price = :price, stock = :stock, image = :image WHERE id = :id");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':stock', $stock);
            $stmt->bindParam(':image', $image);
            $stmt->bindParam(':id', $product_id);
            $stmt->execute();

            $_SESSION['success'] = "Product updated successfully.";
            header("Location: Manage_Products.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = "Error updating product: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product</title>
    <link rel="stylesheet" href="../CSS/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="admin-header">
    <div class="admin-title">
        <h1>Edit Product</h1>
    </div>
    <div class="admin-nav">
        <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="Manage_Products.php"><i class="fas fa-box"></i> Products</a>
        <a href="Orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
        <a href="../process/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert error">
        <?php foreach ($errors as $error): ?>
            <p><?php echo $error; ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="form-container">
    <form action="" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="name">Product Name</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" required><?php echo htmlspecialchars($product['description']); ?></textarea>
        </div>

        <div class="form-group">
            <label for="price">Price (₱)</label>
            <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars($product['price']); ?>" required>
        </div>

        <div class="form-group">
            <label for="stock">Stock</label>
            <input type="number" id="stock" name="stock" min="0" value="<?php echo isset($product['stock']) ? htmlspecialchars($product['stock']) : '0'; ?>" required>
        </div>

        <div class="form-group">
            <label for="image">Product Image</label>
            <?php if ($product['image']): ?>
                <div class="current-image">
                    <img src="../uploads/<?php echo $product['image']; ?>" alt="Current product image" style="max-width: 200px; margin: 10px 0;">
                </div>
            <?php endif; ?>
            <input type="file" id="image" name="image" accept="image/*">
            <small>Max file size: 5MB. Allowed types: JPG, PNG, GIF</small>
        </div>

        <div class="form-actions">
            <button type="submit" class="submit-btn">Update Product</button>
            <a href="Manage_Products.php" class="cancel-btn">Cancel</a>
        </div>
    </form>
</div>

</body>
</html> 