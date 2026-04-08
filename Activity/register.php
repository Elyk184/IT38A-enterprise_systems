<?php
session_start();
$errorMessage = '';
if (isset($_SESSION['error'])) {
    $errorMessage = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="../EcoT/CSS/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="login-container">
    <div class="form-container">
        <h1 style="margin-bottom: 20px;">Register</h1>
        <?php if ($errorMessage): ?>
            <div class="error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>
        <form action="../EcoT/process/signup_process.php" method="POST">
            <div class="input-group-icon">
                <i class="fas fa-user"></i>
                <input type="text" id="name" name="name" placeholder="Enter your name" required>
            </div>
            <div class="input-group-icon">
                <i class="fas fa-envelope"></i>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>
            <div class="input-group-icon password-group">
                <i class="fas fa-lock"></i>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" placeholder="Password" required>
                    <span class="toggle-password" onclick="togglePassword('password', 'eye-icon1')">
                        <i class="fas fa-eye-slash" id="eye-icon1"></i>
                    </span>
                </div>
            </div>
            <div class="input-group-icon password-group">
                <i class="fas fa-lock"></i>
                <div class="password-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                    <span class="toggle-password" onclick="togglePassword('confirm_password', 'eye-icon2')">
                        <i class="fas fa-eye-slash" id="eye-icon2"></i>
                    </span>
                </div>
            </div>
            <button type="submit" class="btn">Register</button>
        </form>
        <div style="margin-top: 15px;">
            <a href="login.php">Already have an account? Login</a>
        </div>
    </div>
</div>
<script>
function togglePassword(inputId, eyeId) {
    var passwordInput = document.getElementById(inputId);
    var eyeIcon = document.getElementById(eyeId);
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
    } else {
        passwordInput.type = 'password';
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash');
    }
}
</script>
</body>
</html>
