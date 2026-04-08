<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <link rel="stylesheet" href="../EcoT/CSS/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .welcome-box {
            max-width: 400px;
            margin: 80px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 40px 30px 30px 30px;
            text-align: center;
        }
        .welcome-box h1 {
            color: #2d7d46;
        }
        .welcome-box p {
            color: #555;
        }
        .welcome-box .btn {
            display: inline-block;
            margin: 20px 10px 0 10px;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            background: #2d7d46;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s;
        }
        .welcome-box .btn:hover {
            background: #256a39;
        }
    </style>
</head>
<body>
    <div class="welcome-box">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?>!</h1>
        <p>You have successfully logged in.</p>
        <a href="logout.php" class="btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</body>
</html>
