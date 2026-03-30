<?php
// ============================================
// admin-reset.php — Reset Admin Credentials
// ============================================
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$pdo = db();

// Check if database and tables exist
try {
    $check = $pdo->query("SELECT 1 FROM users LIMIT 1");
    $check->fetch();
    echo "✓ Database connection successful<br>";
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "<br>";
    exit;
}

// Reset admin user
$username = 'admin';
$email = 'admin@luxestore.com';
$password = 'Admin@123';
$full_name = 'Store Administrator';

$password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

try {
    // Delete existing admin user if exists
    $pdo->prepare("DELETE FROM users WHERE username = ? OR email = ?")->execute([$username, $email]);
    
    // Create new admin user
    $pdo->prepare("
        INSERT INTO users (username, email, password_hash, full_name, role, is_active)
        VALUES (?, ?, ?, ?, 'admin', 1)
    ")->execute([$username, $email, $password_hash, $full_name]);
    
    echo "✓ Admin user created/reset successfully!<br>";
    echo "<br>Login credentials:<br>";
    echo "Username: <strong>admin</strong><br>";
    echo "Password: <strong>Admin@123</strong><br>";
    echo "<br><a href='pages/login.php'>Go to Login</a>";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}
?>
