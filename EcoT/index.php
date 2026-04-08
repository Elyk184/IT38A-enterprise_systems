<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: pages/dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoT - Eco-Friendly Marketplace</title>
    <link rel="stylesheet" href="CSS/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .hero-section {
            background: white;
            padding: 48px 32px 40px 32px;
            border-radius: 18px;
            box-shadow: 0 4px 32px rgba(0, 195, 195, 0.10);
            max-width: 480px;
            margin: 80px auto;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        .hero-logo {
            font-size: 60px;
            color: #42c7d9;
            margin-bottom: 18px;
        }
        .hero-title {
            font-size: 2.2rem;
            font-weight: 700;
            color: #2d7d46;
            margin-bottom: 12px;
        }
        .hero-desc {
            color: #555;
            font-size: 1.1rem;
            margin-bottom: 32px;
        }
        .hero-actions {
            display: flex;
            justify-content: center;
            gap: 18px;
        }
        .hero-btn {
            background: #42c7d9;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 14px 32px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s;
            box-shadow: 0 2px 8px rgba(66, 199, 217, 0.08);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .hero-btn:hover {
            background: #36b0c2;
        }
        @media (max-width: 600px) {
            .hero-section {
                margin: 32px 8px;
                padding: 32px 8px 24px 8px;
            }
            .hero-title {
                font-size: 1.4rem;
            }
        }
    </style>
</head>
<body>
    <div class="hero-section">
        <div class="hero-logo">
            <i class="fas fa-leaf"></i>
        </div>
        <div class="hero-title">Welcome to EcoT</div>
        <div class="hero-desc">
            Discover and shop eco-friendly products for a sustainable future.<br>
            Join our community and make a difference today!
        </div>
        <div class="hero-actions">
            <a href="pages/login.php" class="hero-btn"><i class="fas fa-sign-in-alt"></i> Login</a>
            <a href="pages/sign_up.php" class="hero-btn"><i class="fas fa-user-plus"></i> Register</a>
        </div>
    </div>
</body>
</html>
