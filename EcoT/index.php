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
    <?php include 'includes/navbar.php'; ?>
    <div class="hero-section">
        <div class="hero-logo">
            <i class="fas fa-leaf"></i>
        </div>
        <div class="hero-title">Welcome to EcoT</div>
        <div class="hero-desc">
            Discover and shop eco-friendly products for a sustainable future.<br>
            Join our community and make a difference today!
        </div>
        <!-- Buttons removed as requested -->
    </div>
</body>
<style>
    .navbar {
        width: 100vw;
        background: #fff;
        box-shadow: 0 2px 12px rgba(0, 195, 195, 0.07);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 32px;
        height: 64px;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 10;
    }
    .navbar-logo {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2d7d46;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .navbar-links {
        list-style: none;
        display: flex;
        gap: 28px;
        margin: 0;
        padding: 0;
    }
    .navbar-links li a {
        color: #42c7d9;
        text-decoration: none;
        font-weight: 500;
        font-size: 1rem;
        transition: color 0.2s;
    }
    .navbar-links li a:hover {
        color: #2d7d46;
        text-decoration: underline;
    }
    .navbar-actions {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .navbar-btn {
        background: #42c7d9;
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 8px 22px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: background 0.2s;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 2px 8px rgba(66, 199, 217, 0.08);
    }
    .navbar-btn:hover {
        background: #36b0c2;
    }
    .hero-section {
        margin-top: 100px !important;
    }
    @media (max-width: 700px) {
        .navbar {
            flex-direction: column;
            height: auto;
            padding: 0 8px;
        }
        .navbar-links {
            gap: 12px;
        }
        .hero-section {
            margin-top: 120px !important;
        }
    }
</style>
</html>
