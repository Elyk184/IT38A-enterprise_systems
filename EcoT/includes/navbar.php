<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$baseUrl = '/IT38A-enterprise_systems/EcoT';
?>

<!-- Font Awesome (for icons) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<nav class="navbar">
    <div class="navbar-logo">
        <a href="<?= $baseUrl ?>/index.php" class="logo-link">
            <i class="fas fa-leaf"></i>
            <span>EcoT</span>
        </a>
    </div>

    <ul class="navbar-links">
        <li><a href="<?= $baseUrl ?>/index.php"><i class="fas fa-house"></i> Home</a></li>
        <li><a href="<?= $baseUrl ?>/pages/about.php"><i class="fas fa-circle-info"></i> About</a></li>
        <?php if (isset($_SESSION['user_id'])): ?>
            <li><a href="<?= $baseUrl ?>/process/logout.php"><i class="fas fa-right-from-bracket"></i> Logout</a></li>
        <?php else: ?>
            <li><a href="<?= $baseUrl ?>/pages/login.php"><i class="fas fa-right-to-bracket"></i> Login</a></li>
        <?php endif; ?>
    </ul>
</nav>

<style>
    .navbar {
        width: 100%;
        background: #fff;
        box-shadow: 0 2px 12px rgba(0, 195, 195, 0.07);
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 64px;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 10;
        padding: 0 24px;
        box-sizing: border-box;
    }

    .logo-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        color: #2d7d46;
        font-size: 1.5rem;
        font-weight: 700;
    }

    .logo-link i {
        color: #42c7d9;
        font-size: 1.3rem;
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
        line-height: 64px;
        padding: 0 8px;
        display: inline-flex;
        align-items: center;
        gap: 7px;
    }

    .navbar-links li a:hover {
        color: #2d7d46;
        text-decoration: underline;
        background: rgba(66, 199, 217, 0.07);
    }
</style>
