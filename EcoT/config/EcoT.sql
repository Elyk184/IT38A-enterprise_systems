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
    <title>Register Page</title>
    <link rel="stylesheet" href="../CSS/login.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="login-container">
    <div class="form-container">
    <h1 style="margin-bottom: 20px;">Register</h1>


        <form action="../process/register_process.php" method="POST">
            <!-- Name Field -->
            <div class="input-group-icon">
                <i class="fas fa-user"></i>
                <input type="text" id="name" name="name" placeholder="Enter your name" required>
            </div>

            <!-- Email Field -->
            <div class="input-group-icon">
                <i class="fas fa-envelope"></i>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>

            <!-- Password Field -->
            <div class="input-group-icon password-group">
                <i class="fas fa-lock"></i>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" placeholder="Enter password" required>
                    <span class="toggle-password" onclick="togglePassword('password', 'eye-icon')">
                        <i class="fas fa-eye-slash" id="eye-icon"></i>
                    </span>
                </div>
            </div>

            <!-- Confirm Password Field -->
            <div class="input-group-icon password-group">
                <i class="fas fa-lock"></i>
                <div class="password-wrapper">
                    <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm password" required>
                    <span class="toggle-password" onclick="togglePassword('confirm-password', 'eye-icon2')">
                        <i class="fas fa-eye-slash" id="eye-icon2"></i>
                    </span>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="input-group">
                <button type="submit">Register</button>
            </div>

            <!-- Login Redirect -->
            <div class="forgot-password">
                <p>Already have an account? <a href="../pages/login.php">Login</a></p>
            </div>
        </form>
    </div>
</div>

<script>
    function togglePassword(inputId, iconId) {
        const passwordInput = document.getElementById(inputId);
        const eyeIcon = document.getElementById(iconId);
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            eyeIcon.classList.remove("fa-eye-slash");
            eyeIcon.classList.add("fa-eye");
        } else {
            passwordInput.type = "password";
            eyeIcon.classList.remove("fa-eye");
            eyeIcon.classList.add("fa-eye-slash");
        }
    }
</script>
</body>
</html>
