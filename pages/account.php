<?php
// ============================================
// pages/account.php — Customer Account
// ============================================
require_once __DIR__ . '/../includes/auth.php';
require_login('/pages/login.php');

$user = current_user();
$pdo  = db();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf()) { flash('error', 'Security check failed.'); header('Location: account.php'); exit; }

    if ($_POST['action'] === 'update_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        if (!$full_name || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Invalid input.');
        } else {
            // Check email uniqueness
            $dup = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $dup->execute([$email, $user['id']]);
            if ($dup->fetch()) {
                flash('error', 'Email already in use.');
            } else {
                $pdo->prepare('UPDATE users SET full_name = ?, email = ? WHERE id = ?')
                    ->execute([$full_name, $email, $user['id']]);
                flash('success', 'Profile updated.');
            }
        }
        header('Location: account.php'); exit;
    }

    if ($_POST['action'] === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new_pw  = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (!password_verify($current, $user['password_hash'])) {
            flash('error', 'Current password is incorrect.');
        } elseif (strlen($new_pw) < 8) {
            flash('error', 'New password must be at least 8 characters.');
        } elseif ($new_pw !== $confirm) {
            flash('error', 'Passwords do not match.');
        } else {
            $hash = password_hash($new_pw, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $user['id']]);
            flash('success', 'Password changed successfully.');
        }
        header('Location: account.php'); exit;
    }
}

// Fetch orders
$orders = $pdo->prepare('
    SELECT o.*, COUNT(oi.id) AS item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
');
$orders->execute([$user['id']]);
$orders = $orders->fetchAll();

// Refresh user data
$user = current_user();
$page_title = 'My Account — ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="account-grid">
    <!-- Sidebar -->
    <aside class="account-sidebar">
        <div class="avatar-circle">
            <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
        </div>
        <h3 style="font-size:1.2rem;margin-bottom:0.2rem;"><?= sanitize($user['full_name']) ?></h3>
        <p class="text-muted" style="font-size:0.82rem;">@<?= sanitize($user['username']) ?></p>
        <span class="badge badge-<?= $user['role'] ?> mt-2"><?= ucfirst($user['role']) ?></span>
        <hr class="divider">
        <p class="text-dim" style="font-size:0.78rem;">Member since<br><?= date('F Y', strtotime($user['created_at'])) ?></p>
        <p class="text-dim" style="font-size:0.78rem;margin-top:0.5rem;">Last login<br>
            <?= $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'N/A' ?>
        </p>
    </aside>

    <!-- Main -->
    <div>
        <!-- Edit Profile -->
        <div class="card">
            <div class="card-header">Edit Profile</div>
            <form action="" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_profile">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control"
                               value="<?= sanitize($user['full_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= sanitize($user['email']) ?>" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
            </form>
        </div>

        <!-- Change Password -->
        <div class="card">
            <div class="card-header">Change Password</div>
            <form action="" method="POST" style="max-width:400px;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="change_password">
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" id="password" class="form-control" required>
                    <div style="font-size:0.75rem;margin-top:0.3rem;" id="pw-strength"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-secondary btn-sm">Update Password</button>
            </form>
        </div>

        <!-- Order History -->
        <div class="card">
            <div class="card-header">Order History (<?= count($orders) ?>)</div>
            <?php if (empty($orders)): ?>
                <div class="empty-state" style="padding:2rem;">
                    <div class="empty-icon">📦</div>
                    <h3>No orders yet</h3>
                    <p>Browse our collection to get started.</p>
                    <a href="<?= SITE_URL ?>/index.php" class="btn btn-primary mt-2">Shop Now</a>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td class="td-name">#<?= $order['id'] ?></td>
                                    <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                                    <td><?= $order['item_count'] ?></td>
                                    <td class="text-gold">Rs. <?= number_format($order['total_amount'], 2) ?></td>
                                    <td><span class="badge badge-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
