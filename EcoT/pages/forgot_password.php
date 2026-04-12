<?php
session_start();

$errorMessage = '';
$successMessage = '';

if (isset($_SESSION['error'])) {
    $errorMessage = $_SESSION['error'];
    unset($_SESSION['error']);
}

if (isset($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="../CSS/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="login-container">
    <div class="form-container">
        <?php if (!empty($errorMessage)): ?>
            <div style="background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;border-radius:8px;padding:10px 12px;margin-bottom:12px;">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($successMessage)): ?>
            <div style="background:#ecfdf5;color:#166534;border:1px solid #bbf7d0;border-radius:8px;padding:10px 12px;margin-bottom:12px;">
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>

        <h2>Reset password</h2>
        <form action="../process/forgot_password_process.php" method="POST">
            <div class="input-group-icon">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Registered email" required>
            </div>

            <div class="input-group-icon password-group">
                <i class="fas fa-lock"></i>
                <div class="password-wrapper">
                    <input type="password" id="new_password" name="new_password" placeholder="New password" required minlength="6">
                </div>
            </div>

            <div class="input-group-icon password-group">
                <i class="fas fa-lock"></i>
                <div class="password-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required minlength="6">
                </div>
            </div>

            <div class="input-group">
                <button type="submit">Update Password</button>
            </div>

            <div class="forgot-password" style="margin-top:12px;">
                <a href="login.php">Back to login</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
