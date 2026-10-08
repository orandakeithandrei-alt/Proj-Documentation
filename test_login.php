<?php
require_once 'database.php';

$pdo = getDBConnection();

echo "<h2>Database Connection Test</h2>";

// Check users table
$stmt = $pdo->query("SELECT * FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Users in database:</h3>";
echo "<pre>";
print_r($users);
echo "</pre>";

// Test login with admin
echo "<h3>Testing Login:</h3>";
$username = 'admin';
$password = 'admin123';

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "✅ User found: " . $user['username'] . "<br>";
    echo "Stored password: '" . $user['password_hash'] . "'<br>";
    echo "Entered password: '$password'<br>";
    
    if ($password === $user['password_hash']) {
        echo "<span style='color:green;font-weight:bold;'>✅ PASSWORDS MATCH! Login should work.</span><br>";
    } else {
        echo "<span style='color:red;font-weight:bold;'>❌ PASSWORDS DO NOT MATCH!</span><br>";
        echo "Update the password: UPDATE users SET password_hash = 'admin123' WHERE username = 'admin';";
    }
} else {
    echo "❌ User 'admin' not found!<br>";
    echo "Insert admin: INSERT INTO users (username, password_hash, full_name, role) VALUES ('admin', 'admin123', 'System Administrator', 'admin');";
}
?>