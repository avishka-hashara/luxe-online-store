<?php
// ============================================
// includes/auth.php — Authentication Helpers
// ============================================

require_once __DIR__ . '/db.php';

// ---- CSRF ----
function csrf_token(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function csrf_field(): string {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . csrf_token() . '">';
}

function verify_csrf(): bool {
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    return hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', $token);
}

// ---- User helpers ----
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function is_admin(): bool {
    return is_logged_in() && ($_SESSION['user_role'] ?? '') === 'admin';
}

function current_user(): ?array {
    if (!is_logged_in()) return null;
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function require_login(string $redirect = '/pages/login.php'): void {
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = 'Please log in to continue.';
        header('Location: ' . SITE_URL . $redirect);
        exit;
    }
}

function require_admin(): void {
    if (!is_admin()) {
        $_SESSION['flash_error'] = 'Access denied. Admins only.';
        header('Location: ' . SITE_URL . '/index.php');
        exit;
    }
}

// ---- Registration ----
function register_user(string $username, string $email, string $password, string $full_name): array {
    $pdo = db();

    // Validate
    if (strlen($username) < 3) return ['success' => false, 'message' => 'Username must be at least 3 characters.'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return ['success' => false, 'message' => 'Invalid email address.'];
    if (strlen($password) < 8) return ['success' => false, 'message' => 'Password must be at least 8 characters.'];
    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        return ['success' => false, 'message' => 'Password must contain at least one uppercase letter and one number.'];
    }

    // Check uniqueness
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) return ['success' => false, 'message' => 'Username or email already registered.'];

    // Insert
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, full_name) VALUES (?, ?, ?, ?)');
    $stmt->execute([$username, $email, $hash, $full_name]);

    return ['success' => true, 'message' => 'Account created successfully!'];
}

// ---- Login ----
function login_user(string $login, string $password): array {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1 LIMIT 1');
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Invalid credentials.'];
    }

    // Renew session ID to prevent fixation
    session_regenerate_id(true);

    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_name'] = $user['username'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['full_name'] = $user['full_name'];

    // Update last login
    $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);

    return ['success' => true, 'role' => $user['role']];
}

// ---- Logout ----
function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// ---- Flash messages ----
function flash(string $type, string $message): void {
    $_SESSION['flash_' . $type] = $message;
}

function get_flash(string $type): ?string {
    $msg = $_SESSION['flash_' . $type] ?? null;
    unset($_SESSION['flash_' . $type]);
    return $msg;
}

function sanitize(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}
