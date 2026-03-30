<?php
// ============================================
// includes/config.php — App Configuration
// ============================================

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'luxe_store');
define('DB_USER', 'root');       // Change to your MySQL username
define('DB_PASS', '');           // Change to your MySQL password
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', 'LUXE STORE');
define('SITE_URL', 'http://localhost/store');
define('ADMIN_URL', SITE_URL . '/admin');

// Session lifetime in seconds (2 hours)
define('SESSION_LIFETIME', 7200);

// Security
define('CSRF_TOKEN_NAME', '_csrf_token');

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'secure'   => false, // Set true in production with HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
