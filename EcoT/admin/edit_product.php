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

    <style>
        :root{
            --bg: linear-gradient(180deg, #f7fffe 0%, #eefbf8 100%);
            --card: #ffffff;
            --border: rgba(15,118,110,.12);
            --text: #0f172a;
            --muted: #64748b;
            --primary: #14b8a6;
            --primary-dark: #0f766e;
            --danger: #ef4444;
            --shadow: 0 14px 34px rgba(15, 23, 42, .08);
        }

        body{
            margin: 0;
            font-family: "Inter","Segoe UI",Tahoma,sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .admin-content{
            margin-left: 266px;
            padding: 22px;
            min-height: 100vh;
            box-sizing: border-box;
        }

        .admin-header{
            margin: 0 0 14px;
            padding: 18px 20px;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(20,184,166,.14), rgba(255,255,255,.96));
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
        }

        .admin-title h1{
            margin: 0;
            color: var(--primary-dark);
            font-size: 1.55rem;
            font-weight: 800;
        }

        .form-container{
            max-width: 920px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 22px;
            box-shadow: var(--shadow);
        }

        .form-group{
            margin-bottom: 14px;
        }

        .form-group label{
            display: block;
            margin-bottom: 7px;
            font-size: .92rem;
            font-weight: 700;
            color: #334155;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="file"],
        .form-group textarea{
            width: 100%;
            border: 1px solid #dbe7e5;
            border-radius: 12px;
            padding: 11px 12px;
            font-size: .95rem;
            color: var(--text);
            background: #fff;
            box-sizing: border-box;
            transition: border-color .18s ease, box-shadow .18s ease;
        }

        .form-group textarea{
            min-height: 130px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group textarea:focus{
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(20,184,166,.15);
        }

        .field-row{
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .current-image{
            margin: 6px 0 10px;
            padding: 10px;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            display: inline-block;
            background: #f8fafc;
        }

        .current-image img{
            width: 170px;
            max-width: 100%;
            border-radius: 10px;
            display: block;
        }

        .form-group small{
            display: block;
            margin-top: 6px;
            color: var(--muted);
            font-size: .8rem;
        }

        .form-actions{
            margin-top: 16px;
            display: flex;
            gap: 10px;
        }

        .submit-btn,
        .cancel-btn{
            border: 0;
            border-radius: 12px;
            padding: 10px 16px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .submit-btn{
            color: #fff;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            box-shadow: 0 10px 20px rgba(20,184,166,.25);
        }

        .cancel-btn{
            color: #334155;
            background: #eef2f7;
        }

        .submit-btn:hover,
        .cancel-btn:hover{
            transform: translateY(-1px);
        }

        .alert.error{
            margin-bottom: 12px;
            border-radius: 12px;
            padding: 12px 14px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
        }

        .alert.error p{
            margin: 4px 0;
        }

        @media (max-width: 992px){
            .admin-content{ margin-left: 84px; padding: 16px; }
            .field-row{ grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="admin-content">
    <div class="admin-header">
        <div class="admin-title">
            <h1>Edit Product</h1>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
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

            <div class="field-row">
                <div class="form-group">
                    <label for="price">Price (₱)</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars($product['price']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="stock">Stock</label>
                    <input type="number" id="stock" name="stock" min="0" value="<?php echo isset($product['stock']) ? htmlspecialchars($product['stock']) : '0'; ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="image">Product Image</label>
                <?php if ($product['image']): ?>
                    <div class="current-image">
                        <img src="../uploads/<?php echo $product['image']; ?>" alt="Current product image">
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
</div>

</body>
</html>