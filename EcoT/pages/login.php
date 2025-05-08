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
    <title>Login Page</title>
    <link rel="stylesheet" href="../CSS/login.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="login-container">
    <!-- Form goes first -->
    
      
    <div class="form-container">
   
    <h2>Login here</h2>
    <form action="../process/login_process.php" method="POST">
        <!-- Email Field with Icon -->
        <div class="input-group-icon">
            <i class="fas fa-envelope"></i>
            <input type="email" id="email" name="email" placeholder="Email" required>
        </div>

        <!-- Password Field with Icon & Eye Icon Inside -->
        <div class="input-group-icon password-group">
            <i class="fas fa-lock"></i>
            <div class="password-wrapper">
                <input type="password" id="password" name="password" placeholder="Password" required>
                <span class="toggle-password" onclick="togglePassword()">
                    <i class="fas fa-eye-slash" id="eye-icon"></i>
                </span>
            </div>
        </div>
    </form>
</div>


            <!-- Forgot Password Link -->
            <div class="forgot-password">
                <a href="forgot_password.php">Forgot Password?</a>
            </div>

            <!-- Login Button -->
            <div class="input-group">
                <button type="submit">Login</button>
            </div>
        </form>

           <!-- Forgot Password Link -->
           <div class="forgot-password">
              
        <p>Dont have an account?  <a href="../pages/sign_up.php">Sign up</a></p>
            </div>
    </div>



</body>
</html>
