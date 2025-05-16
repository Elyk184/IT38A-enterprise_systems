<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user information
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->bindParam(':id', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching user data.";
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Profile</title>
    <link rel="stylesheet" href="../CSS/userdashboard.css">
    <link rel="stylesheet" href="../CSS/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="header">
    <div class="search-bar">
        <form action="dashboard.php" method="GET">
            <input type="text" name="search" placeholder="Search products...">
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
    </div>
    <div class="nav-icons">
        <a href="dashboard.php"><i class="fas fa-home"></i></a>
        <a href="cart.php"><i class="fas fa-shopping-cart"></i></a>
        <a href="notifications.php"><i class="fas fa-bell"></i></a>
        <a href="profile.php"><i class="fas fa-user"></i></a>
        <a href="../process/logout.php"><i class="fas fa-sign-out-alt"></i></a>
    </div>
</div>

<div class="welcome-message">
    Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!
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

<div class="profile-container">
    <div class="profile-header">
        <div class="profile-avatar">
            <i class="fas fa-user-circle"></i>
        </div>
        <h2><?php echo htmlspecialchars($user['name']); ?></h2>
        <p class="user-role"><?php echo ucfirst($user['role']); ?></p>
    </div>

    <div class="profile-content">
        <div class="profile-section">
            <h3>Account Information</h3>
            <form action="../process/update_profile.php" method="POST" class="profile-form">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <button type="submit" class="update-btn">Update Profile</button>
            </form>
        </div>

        <div class="profile-section">
            <h3>Change Password</h3>
            <form action="../process/change_password.php" method="POST" class="profile-form">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="update-btn">Change Password</button>
            </form>
        </div>
    </div>
</div>

</body>
</html> 