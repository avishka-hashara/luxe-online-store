<?php
// ============================================
// pages/register.php — User Registration
// ============================================
require_once __DIR__ . '/../includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$errors = [];
$values = ['username' => '', 'email' => '', 'full_name' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Security check failed. Please try again.';
    } else {
        $values['username']  = trim($_POST['username'] ?? '');
        $values['email']     = trim($_POST['email'] ?? '');
        $values['full_name'] = trim($_POST['full_name'] ?? '');
        $password            = $_POST['password'] ?? '';
        $confirm             = $_POST['confirm_password'] ?? '';

        if (empty($values['full_name'])) $errors[] = 'Full name is required.';
        if ($password !== $confirm)       $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            $result = register_user($values['username'], $values['email'], $password, $values['full_name']);
            if ($result['success']) {
                flash('success', 'Account created! Please sign in.');
                header('Location: ' . SITE_URL . '/pages/login.php');
                exit;
            } else {
                $errors[] = $result['message'];
            }
        }
    }
}

$page_title = 'Create Account — ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="form-card">
    <h2>Create Account</h2>
    <p class="subtitle">Join us for access to exclusive collections.</p>

    <?php if ($errors): ?>
        <div class="flash flash-error"><?= implode('<br>', array_map('sanitize', $errors)) ?></div>
    <?php endif; ?>

    <form action="" method="POST" novalidate>
        <?= csrf_field() ?>

        <div class="form-group">
            <label class="form-label" for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" class="form-control"
                   value="<?= sanitize($values['full_name']) ?>" required autocomplete="name" placeholder="Jane Doe">
        </div>

        <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <input type="text" id="username" name="username" class="form-control"
                   value="<?= sanitize($values['username']) ?>" required autocomplete="username" placeholder="janedoe">
        </div>

        <div class="form-group">
            <label class="form-label" for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control"
                   value="<?= sanitize($values['email']) ?>" required autocomplete="email" placeholder="jane@example.com">
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <div class="flex gap-1 items-center">
                <input type="password" id="password" name="password" class="form-control"
                       required autocomplete="new-password" placeholder="Min. 8 chars, 1 uppercase, 1 number">
                <button type="button" class="toggle-pw btn btn-secondary btn-sm" style="flex-shrink:0">👁</button>
            </div>
            <div style="font-size:0.75rem;margin-top:0.3rem;" id="pw-strength"></div>
        </div>

        <div class="form-group">
            <label class="form-label" for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                   required autocomplete="new-password" placeholder="Repeat your password">
        </div>

        <button type="submit" class="btn btn-primary btn-full mt-2">Create Account</button>

        <div class="form-divider">or</div>
        <div class="text-center">
            <span class="text-muted" style="font-size:0.85rem;">Already have an account?</span>
            <a href="<?= SITE_URL ?>/pages/login.php" style="margin-left:0.5rem;font-size:0.85rem;">Sign In</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
