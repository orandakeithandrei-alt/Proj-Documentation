<?php
// Start session FIRST
session_start();

require_once 'database.php';

// Redirect if already logged in
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $username = trim($username);
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Plain text password comparison
        if ($user && isset($user['password_hash']) && $password === $user['password_hash']) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            // Update last login
            $stmt2 = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $stmt2->execute([$user['user_id']]);
            
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UM Canteen - Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="login-page">
    <div class="login-box">
        <div class="logo-frame">
            <img class="login-logo" src="assets/um-logo.png" alt="University of Mindanao logo">
        </div>
        <h1>University of Mindanao Digos</h1>
        <p class="login-subtitle">Canteen Inventory & POS System</p>

        <?php if ($error): ?>
            <div class="error-message" style="background:#ffebee;padding:10px;border-radius:5px;margin-bottom:15px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <label class="form-label" for="username">Username</label>
            <input class="form-control" id="username" name="username" type="text" placeholder="Enter username" required>
            <label class="form-label" for="password">Password</label>
            <input class="form-control" id="password" name="password" type="password" placeholder="Enter password" required>
            <button class="login-button" type="submit">LOGIN</button>
        </form>
        <p class="demo">Demo Account: admin / admin123</p>
    </div>
</div>
</body>
</html>