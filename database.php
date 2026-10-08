<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'um_canteen');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create connection
function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Session management - FIXED
function startSecureSession() {
    // Only start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isLoggedIn() {
    startSecureSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    startSecureSession();
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        header('Location: index.php');
        exit();
    }
}

function getCurrentUser() {
    startSecureSession();
    return $_SESSION['user_id'] ?? null;
}
?>