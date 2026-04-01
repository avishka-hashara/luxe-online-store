<?php
// ============================================
// pages/login.php — User Login
// ============================================
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    $goto = ($_GET['redirect'] ?? '') === 'checkout' ? SITE_URL . '/pages/checkout.php' : SITE_URL . '/index.php';
    header('Location: ' . $goto);
    exit;
}

$error = '';
$login_val = '';
$redirect_param = $_GET['redirect'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Security check failed. Please try again.';
    } else {
        $login_val = trim($_POST['login'] ?? '');
        $password  = $_POST['password'] ?? '';
        $redirect_param = $_POST['redirect_param'] ?? '';

        if (empty($login_val) || empty($password)) {
            $error = 'Please fill in all fields.';
        } else {
            $result = login_user($login_val, $password);
            if ($result['success']) {
                flash('success', 'Welcome back, ' . $_SESSION['full_name'] . '!');
                if ($result['role'] === 'admin') {
                    $redirect = SITE_URL . '/admin/index.php';
                } elseif ($redirect_param === 'checkout') {
                    $redirect = SITE_URL . '/pages/checkout.php';
                } else {
                    $redirect = SITE_URL . '/index.php';
                }
                header('Location: ' . $redirect);
                exit;
            } else {
                $error = $result['message'];
            }
        }
    }
}

$page_title = 'Sign In — ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="form-card">
    <h2>Welcome Back</h2>
    <p class="subtitle">Sign in to your account to continue.</p>

    <?php if ($error): ?>
        <div class="flash flash-error"><?= sanitize($error) ?></div>
    <?php endif; ?>

    <form action="" method="POST" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="redirect_param" value="<?= sanitize($redirect_param) ?>">

        <div class="form-group">
            <label class="form-label" for="login">Username or Email</label>
            <input type="text" id="login" name="login" class="form-control"
                   value="<?= sanitize($login_val) ?>" required autofocus autocomplete="username"
                   placeholder="Enter your username or email">
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <div class="flex gap-1 items-center">
                <input type="password" id="password" name="password" class="form-control"
                       required autocomplete="current-password" placeholder="Your password">
                <button type="button" class="toggle-pw btn btn-secondary btn-sm" style="flex-shrink:0">👁</button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-full mt-2">Sign In</button>

        <div class="form-divider">or</div>
        <div class="text-center">
            <span class="text-muted" style="font-size:0.85rem;">New here?</span>
            <a href="<?= SITE_URL ?>/pages/register.php" style="margin-left:0.5rem;font-size:0.85rem;">Create an Account</a>
        </div>
    </form>

    <!-- Demo credentials hint -->
    <div class="card mt-3" style="background:var(--bg-3);padding:1rem;">
        <div class="card-header">Demo Credentials</div>
        <p style="font-size:0.8rem;color:var(--text-muted);margin-bottom:0.4rem;">
            <strong class="text-gold">Admin:</strong> admin / Admin@123
        </p>
        <p style="font-size:0.8rem;color:var(--text-muted);">
            <strong class="text-gold">Customer:</strong> johndoe / User@123
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
