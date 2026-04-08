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

    <style>
        :root{
            --page-bg: linear-gradient(180deg, #f7fffe 0%, #eefbf8 100%);
            --card-bg: rgba(255,255,255,.96);
            --card-border: rgba(15, 118, 110, .08);
            --text: #0f172a;
            --muted: #64748b;
            --primary: #14b8a6;
            --primary-dark: #0f766e;
            --shadow: 0 14px 34px rgba(15, 23, 42, .08);
        }

        body{
            margin: 0;
            font-family: "Segoe UI", Tahoma, sans-serif;
            background: var(--page-bg);
            color: var(--text);
        }

        .admin-content{
            margin-left: 266px;
            padding: 20px 22px 22px;
            box-sizing: border-box;
            min-height: 100vh;
        }

        .admin-header{
            margin: 0 0 14px 0;
            padding: 18px 20px;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(20,184,166,.12), rgba(255,255,255,.95));
            border: 1px solid var(--card-border);
            box-shadow: var(--shadow);
        }

        .admin-title h1{
            margin: 0;
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--primary-dark);
            letter-spacing: .2px;
        }

        .dashboard-container{
            margin: 0;
            padding: 0;
        }

        .action-bar{
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin: 0 0 14px 0;
        }

        .add-btn{
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            border-radius: 12px;
            text-decoration: none;
            color: #fff;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            font-weight: 700;
            box-shadow: 0 10px 20px rgba(20, 184, 166, .22);
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .add-btn:hover{
            transform: translateY(-1px);
            box-shadow: 0 14px 24px rgba(20, 184, 166, .28);
        }

        .products-table{
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 18px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .products-table table{
            width: 100%;
            border-collapse: collapse;
        }

        .products-table thead th{
            text-align: left;
            padding: 14px 16px;
            font-size: .88rem;
            color: #475569;
            background: #f8fffe;
            border-bottom: 1px solid #e5f2ef;
        }

        .products-table tbody td{
            padding: 14px 16px;
            border-bottom: 1px solid #edf3f2;
            color: #334155;
            vertical-align: middle;
        }

        .products-table tbody tr:hover{
            background: #f6fffd;
        }

        .product-thumbnail{
            width: 52px;
            height: 52px;
            object-fit: cover;
            border-radius: 12px;
            background: #fff;
            border: 1px solid #e5e7eb;
            box-shadow: 0 6px 14px rgba(15, 23, 42, .06);
        }

        .no-image{
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: #f1f5f9;
            color: var(--muted);
            font-size: .75rem;
            border: 1px dashed #cbd5e1;
        }

        .status-badge{
            display: inline-flex;
            align-items: center;
            padding: 6px 11px;
            border-radius: 999px;
            font-size: .78rem;
            font-weight: 800;
        }

        .status-badge.completed{
            background: #ecfdf5;
            color: #047857;
        }

        .status-badge.cancelled{
            background: #fef2f2;
            color: #b91c1c;
        }

        .action-buttons{
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .edit-btn,
        .delete-btn{
            width: 36px;
            height: 36px;
            border: 0;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            text-decoration: none;
            transition: transform .16s ease, box-shadow .16s ease;
        }

        .edit-btn{
            background: #e0f2fe;
            color: #2563eb;
        }

        .delete-btn{
            background: #fee2e2;
            color: #dc2626;
        }

        .edit-btn:hover,
        .delete-btn:hover{
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(15, 23, 42, .10);
        }

        .delete-form{
            margin: 0;
        }

        .alert{
            margin-bottom: 14px;
            padding: 14px 16px;
            border-radius: 14px;
            font-weight: 700;
        }

        .alert.success{
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .alert.error{
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        @media (max-width: 992px){
            .admin-content{
                margin-left: 84px;
                padding: 16px;
            }

            .products-table{
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="admin-content">
    <div class="admin-header">
        <div class="admin-title">
            <h1>Manage Products</h1>
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
                            <td><?php echo isset($product['stock']) ? $product['stock'] : 0; ?></td>
                            <td>
                                <span class="status-badge <?php echo (isset($product['stock']) && $product['stock'] > 0) ? 'completed' : 'cancelled'; ?>">
                                    <?php echo (isset($product['stock']) && $product['stock'] > 0) ? 'In Stock' : 'Out of Stock'; ?>
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
</div>

</body>
</html>
