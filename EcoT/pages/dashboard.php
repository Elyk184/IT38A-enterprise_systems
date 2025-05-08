<?php
session_start();
// Simulating login user info
$user = $_SESSION['user'] ?? 'Guest';
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
        <input type="text" placeholder="Search">
        <button><i class="fas fa-search"></i></button>
    </div>
    <div class="nav-icons">
        <i class="fas fa-home"></i>
        <i class="fas fa-shopping-cart"></i>
        <i class="fas fa-bell"></i>
        <i class="fas fa-user"></i>
    </div>
</div>

<div class="product-grid">

    <!-- Product Card -->
    <div class="product-card">
        <img src="../images/ballpen.png" alt="Ballpen">
        <h3>Ballpen</h3>
        <p>Description: A writing instrument... reliable writing experience.</p>
        <div class="price">₱10.00</div>
        <div class="actions">
            <span class="stars">⭐⭐⭐⭐</span>
            <button>Buy now</button>
        </div>
    </div>

    <div class="product-card">
        <img src="../images/folder.png" alt="Folder">
        <h3>Folder</h3>
        <p>Description: A container used... manage information.</p>
        <div class="price">₱12.00</div>
        <div class="actions">
            <span class="stars">⭐⭐⭐</span>
            <button>Buy now</button>
        </div>
    </div>

    <div class="product-card">
        <img src="../images/notebook.png" alt="Notebook">
        <h3>Binder Notebook</h3>
        <p>Description: A wire-bound... several subjects in one.</p>
        <div class="price">₱85.00</div>
        <div class="actions">
            <span class="stars">⭐⭐⭐⭐</span>
            <button>Buy now</button>
        </div>
    </div>

    <div class="product-card">
        <img src="../images/printer-ink.png" alt="Printer Ink">
        <h3>Printer Ink</h3>
        <p>Description: Dye-based ink... mass-free refills.</p>
        <div class="price">₱295.00</div>
        <div class="actions">
            <span class="stars">⭐⭐⭐⭐</span>
            <button>Buy now</button>
        </div>
    </div>

</div>

</body>
</html>
