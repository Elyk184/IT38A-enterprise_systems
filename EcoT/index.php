<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: /IT38A-enterprise_systems/EcoT/pages/dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoT - Eco-Friendly Marketplace</title>
    <link rel="stylesheet" href="/IT38A-enterprise_systems/EcoT/CSS/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .hero-section {
            background: #fff;
            padding: 48px 32px 40px;
            border-radius: 18px;
            box-shadow: 0 4px 32px rgba(0, 195, 195, 0.10);
            max-width: 480px;
            margin: 110px auto 0;
            text-align: center;
            position: relative;
            z-index: 2;
            display: block !important;
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
            margin-bottom: 0;
        }
        @media (max-width: 600px) {
            .hero-section {
                margin: 90px 10px 0;
                padding: 32px 12px 24px;
            }
            .hero-title {
                font-size: 1.4rem;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="hero-section">
        <div class="hero-logo"><i class="fas fa-leaf"></i></div>
        <div class="hero-title">Welcome to EcoT</div>
        <div class="hero-desc">
            Discover and shop eco-friendly products for a sustainable future.<br>
            Join our community and make a difference today!
        </div>
    </div>
</body>
</html>
