<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$product_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: dashboard.php');
    exit();
}

try {
    $stmt = $conn->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
    $stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header('Location: dashboard.php');
        exit();
    }

    $related_stmt = $conn->prepare('SELECT id, name, price, image FROM products WHERE id != :id ORDER BY created_at DESC LIMIT 4');
    $related_stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
    $related_stmt->execute();
    $related_products = $related_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error fetching product details: ' . $e->getMessage();
    header('Location: dashboard.php');
    exit();
}

$rating = (int)($product['rating'] ?? 0);
$stock = isset($product['stock']) ? (int)$product['stock'] : 0;
$stock_label = $stock > 0 ? 'In Stock' : 'Out of Stock';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - EcoT</title>
    <link rel="stylesheet" href="../CSS/userdashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .details-shell {
            width: min(1260px, 94vw);
            margin: 0 auto 56px;
        }

        body {
            padding-top: 118px;
            min-height: 100vh;
            background:
                radial-gradient(circle at 10% 0%, rgba(20, 184, 166, 0.18) 0%, rgba(20, 184, 166, 0) 28%),
                radial-gradient(circle at 90% 8%, rgba(245, 158, 11, 0.16) 0%, rgba(245, 158, 11, 0) 24%),
                linear-gradient(180deg, #f7fbfb 0%, #eef6f6 100%);
        }

        .details-hero {
            margin-top: 22px;
            background: linear-gradient(135deg, rgba(20, 184, 166, 0.14), rgba(255, 255, 255, 0.96));
            border: 1px solid rgba(20, 184, 166, 0.12);
            border-radius: 24px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
            padding: 24px 28px;
        }

        .details-hero h1 {
            margin: 0;
            font-size: clamp(1.35rem, 2.2vw, 2rem);
            font-weight: 800;
            color: #0f172a;
        }

        .details-hero p {
            margin: 8px 0 0;
            color: #64748b;
        }

        .details-card {
            margin-top: 20px;
            background: rgba(255, 255, 255, 0.98);
            border: 1px solid rgba(226, 232, 240, 0.95);
            border-radius: 24px;
            box-shadow: 0 16px 38px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .details-grid {
            display: grid;
            grid-template-columns: minmax(340px, 1fr) minmax(380px, 1fr);
        }

        .details-image-wrap {
            padding: 28px;
            background: linear-gradient(135deg, #f8fbfb, #edf9f7);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 520px;
            position: sticky;
            top: 136px;
            align-self: start;
        }

        .details-image {
            width: 100%;
            max-width: 480px;
            aspect-ratio: 1 / 1;
            object-fit: contain;
            border-radius: 26px;
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(203, 213, 225, 0.82);
            padding: 22px;
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.08);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .details-image:hover {
            transform: scale(1.02);
            box-shadow: 0 18px 34px rgba(15, 23, 42, 0.12);
        }

        .details-content {
            padding: 30px;
        }

        .product-name {
            margin: 0;
            font-size: clamp(1.6rem, 2.3vw, 2.3rem);
            font-weight: 800;
            color: #0f172a;
        }

        .product-meta {
            margin-top: 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
        }

        .meta-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 12px;
            border-radius: 999px;
            background: #f8fafc;
            border: 1px solid rgba(226, 232, 240, 0.95);
            font-size: 0.84rem;
            font-weight: 800;
            color: #334155;
        }

        .meta-pill.in-stock {
            background: #ecfdf5;
            color: #15803d;
            border-color: #bbf7d0;
        }

        .meta-pill.out-stock {
            background: #fef2f2;
            color: #b91c1c;
            border-color: #fecaca;
        }

        .price-row {
            margin-top: 20px;
            display: flex;
            align-items: baseline;
            gap: 12px;
            flex-wrap: wrap;
            padding: 14px 16px;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(20, 184, 166, 0.08), rgba(255, 255, 255, 0.96));
            border: 1px solid rgba(20, 184, 166, 0.12);
        }

        .price-main {
            font-size: 2rem;
            font-weight: 800;
            color: #0f766e;
        }

        .price-note {
            color: #64748b;
            font-size: 0.92rem;
        }

        .description-card {
            margin-top: 18px;
            padding: 20px;
            border-radius: 20px;
            background: #f8fafc;
            border: 1px solid rgba(226, 232, 240, 0.9);
            color: #334155;
            line-height: 1.7;
        }

        .purchase-panel {
            margin-top: 20px;
            padding: 20px;
            border-radius: 20px;
            background: #fff;
            border: 1px solid rgba(226, 232, 240, 0.95);
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.06);
        }

        .purchase-label {
            display: block;
            margin-bottom: 10px;
            font-size: 0.8rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #334155;
        }

        .qty-control {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid rgba(203, 213, 225, 0.95);
            background: #f8fafc;
        }

        .qty-control button {
            width: 34px;
            height: 34px;
            border: none;
            border-radius: 999px;
            background: #fff;
            font-weight: 800;
            color: #0f172a;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
        }

        .qty-control input {
            width: 56px;
            text-align: center;
            border: none;
            background: transparent;
            font-weight: 800;
            color: #0f172a;
            outline: none;
        }

        .action-row {
            margin-top: 20px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .details-button,
        .buy-now-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 13px 16px;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 800;
            transition: transform 0.2s ease, filter 0.2s ease, box-shadow 0.2s ease;
            min-width: 150px;
            box-shadow: 0 8px 16px rgba(15, 23, 42, 0.08);
        }

        .details-button {
            background: linear-gradient(135deg, #14b8a6, #0f766e);
            color: white;
            border: none;
            cursor: pointer;
        }

        .buy-now-button {
            background: linear-gradient(135deg, #38bdf8, #2563eb);
            color: white;
            border: none;
            cursor: pointer;
        }

        .details-button:hover,
        .buy-now-button:hover {
            transform: translateY(-1px);
            filter: brightness(1.03);
            box-shadow: 0 12px 22px rgba(15, 23, 42, 0.12);
        }

        .related-section {
            margin-top: 20px;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(226, 232, 240, 0.95);
            border-radius: 24px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
            padding: 24px;
        }

        .related-section h2 {
            margin: 0 0 16px;
            font-size: 1.05rem;
            font-weight: 800;
            color: #0f172a;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .related-card {
            border: 1px solid rgba(226, 232, 240, 0.95);
            border-radius: 18px;
            background: #fff;
            padding: 14px;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.06);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .related-card:hover h3 {
            color: #0f766e;
        }

        .related-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 26px rgba(15, 23, 42, 0.1);
        }

        .related-card img {
            width: 100%;
            height: 150px;
            object-fit: contain;
            border-radius: 14px;
            background: linear-gradient(135deg, #f8fafc, #eefbf8);
            padding: 8px;
            margin-bottom: 10px;
        }

        .related-card h3 {
            margin: 0 0 6px;
            font-size: 0.96rem;
            color: #0f172a;
        }

        .related-card .price {
            color: #0f766e;
            font-weight: 800;
        }

        @media (max-width: 980px) {
            body {
                padding-top: 156px;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }

            .details-image-wrap {
                min-height: 340px;
                position: static;
                top: auto;
            }

            .related-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            body {
                padding-top: 166px;
            }

            .details-shell {
                width: 94vw;
            }

            .details-hero,
            .details-content,
            .details-image-wrap,
            .related-section {
                padding-left: 18px;
                padding-right: 18px;
            }

            .action-row {
                flex-direction: column;
            }

            .details-button,
            .buy-now-button {
                width: 100%;
            }

            .related-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-shell" style="position: fixed; top: 0; left: 0; right: 0; z-index: 20; width: min(1260px, 94vw); margin: 16px auto 0;">
        <div class="header">
            <div class="search-bar" aria-label="Search products">
                <form action="dashboard.php" method="GET" role="search">
                    <input type="text" name="search" placeholder="Search sustainable products..." value="<?php echo htmlspecialchars($product['name']); ?>">
                    <button type="submit" aria-label="Search"><i class="fas fa-search"></i></button>
                </form>
            </div>
            <div class="nav-icons">
                <a href="dashboard.php" title="Home"><i class="fas fa-home"></i><span>Home</span></a>
                <a href="cart.php" title="Cart"><i class="fas fa-shopping-cart"></i><span>Cart</span></a>
                <a href="my_orders.php" title="My Orders"><i class="fas fa-box"></i><span>Orders</span></a>
                <a href="notifications.php" title="Notifications"><i class="fas fa-bell"></i><span>Notifications</span></a>
                <a href="profile.php" title="Profile"><i class="fas fa-user"></i><span>Profile</span></a>
                <a href="../process/logout.php" title="Logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </div>
        </div>
    </div>

    <main class="details-shell">
        <section class="details-hero">
            <h1>Product details</h1>
            <p>Review the item before adding it to your cart.</p>
        </section>

        <section class="details-card">
            <div class="details-grid">
                <div class="details-image-wrap">
                    <img
                        class="details-image"
                        src="<?php echo $product['image'] ? '../uploads/' . htmlspecialchars($product['image']) : 'https://via.placeholder.com/500x500?text=No+Image'; ?>"
                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                    >
                </div>

                <div class="details-content">
                    <h1 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h1>

                    <div class="product-meta">
                        <span class="meta-pill <?php echo $stock > 0 ? 'in-stock' : 'out-stock'; ?>">
                            <i class="fas fa-box"></i> <?php echo $stock_label; ?>
                        </span>
                        <span class="meta-pill">
                            <i class="fas fa-star"></i> <?php echo number_format($rating); ?> Rating
                        </span>
                        <span class="meta-pill">
                            <i class="fas fa-tag"></i> Product ID #<?php echo (int)$product['id']; ?>
                        </span>
                    </div>

                    <div class="price-row">
                        <div class="price-main">₱<?php echo number_format($product['price'], 2); ?></div>
                        <div class="price-note">per item</div>
                    </div>

                    <div class="description-card">
                        <?php echo nl2br(htmlspecialchars($product['description'] ?? 'No description available.')); ?>
                    </div>

                    <div class="purchase-panel">
                        <form action="../process/add_to_cart.php" method="POST">
                            <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                            <label class="purchase-label" for="quantity">Quantity</label>
                            <div class="qty-control">
                                <button type="button" id="qtyMinus" aria-label="Decrease quantity">-</button>
                                <input id="quantity" name="quantity" type="number" min="1" max="<?php echo $stock > 0 ? $stock : 1; ?>" value="1">
                                <button type="button" id="qtyPlus" aria-label="Increase quantity">+</button>
                            </div>

                            <div class="action-row">
                                <button type="submit" class="details-button" <?php echo $stock <= 0 ? 'disabled' : ''; ?>>
                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                </button>
                                <button type="submit" name="buy_now" value="1" class="buy-now-button" <?php echo $stock <= 0 ? 'disabled' : ''; ?>>
                                    <i class="fas fa-bolt"></i> Buy Now
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <?php if (!empty($related_products)): ?>
            <section class="related-section">
                <h2>More products</h2>
                <div class="related-grid">
                    <?php foreach ($related_products as $related): ?>
                        <a class="related-card" href="product_details.php?id=<?php echo (int)$related['id']; ?>">
                            <img src="<?php echo $related['image'] ? '../uploads/' . htmlspecialchars($related['image']) : 'https://via.placeholder.com/300x300?text=No+Image'; ?>" alt="<?php echo htmlspecialchars($related['name']); ?>">
                            <h3><?php echo htmlspecialchars($related['name']); ?></h3>
                            <div class="price">₱<?php echo number_format($related['price'], 2); ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <script>
        const quantityInput = document.getElementById('quantity');
        const qtyMinus = document.getElementById('qtyMinus');
        const qtyPlus = document.getElementById('qtyPlus');
        const maxQuantity = <?php echo $stock > 0 ? (int)$stock : 1; ?>;

        function clampQuantity(value) {
            const numericValue = Number.parseInt(value, 10) || 1;
            return Math.min(Math.max(numericValue, 1), maxQuantity);
        }

        qtyMinus.addEventListener('click', () => {
            quantityInput.value = clampQuantity(Number.parseInt(quantityInput.value, 10) - 1);
        });

        qtyPlus.addEventListener('click', () => {
            quantityInput.value = clampQuantity(Number.parseInt(quantityInput.value, 10) + 1);
        });

        quantityInput.addEventListener('change', () => {
            quantityInput.value = clampQuantity(quantityInput.value);
        });
    </script>
</body>
</html>
