<?php
// ============================================
// admin/users.php — User Management
// ============================================
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$pdo = db();
$msg = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) { flash('error', 'Security token invalid.'); header('Location: users.php'); exit; }

    $action  = $_POST['action'] ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);

    // Protect current admin
    if ($user_id === (int)$_SESSION['user_id'] && in_array($action, ['delete', 'toggle_active', 'change_role'])) {
        flash('error', 'You cannot modify your own account here.');
        header('Location: users.php'); exit;
    }

    if ($action === 'add_user') {
        $result = register_user(
            trim($_POST['username'] ?? ''),
            trim($_POST['email'] ?? ''),
            $_POST['password'] ?? '',
            trim($_POST['full_name'] ?? '')
        );
        if ($result['success']) {
            // Set role if admin
            if (($_POST['role'] ?? 'customer') === 'admin') {
                $pdo->prepare("UPDATE users SET role = 'admin' WHERE username = ?")
                    ->execute([trim($_POST['username'])]);
            }
            flash('success', 'User created successfully.');
        } else {
            flash('error', $result['message']);
        }
    } elseif ($action === 'delete' && $user_id) {
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$user_id]);
        flash('success', 'User deleted.');
    } elseif ($action === 'toggle_active' && $user_id) {
        $pdo->prepare('UPDATE users SET is_active = NOT is_active WHERE id = ?')->execute([$user_id]);
        flash('success', 'User status updated.');
    } elseif ($action === 'change_role' && $user_id) {
        $role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'customer';
        $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $user_id]);
        flash('success', 'User role updated.');
    }
    header('Location: users.php'); exit;
}

// Search
$search = trim($_GET['q'] ?? '');
$role_filter = $_GET['role'] ?? '';
$query = 'SELECT * FROM users WHERE 1=1';
$params = [];
if ($search) {
    $query .= ' AND (username LIKE ? OR email LIKE ? OR full_name LIKE ?)';
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}
if ($role_filter) {
    $query .= ' AND role = ?';
    $params[] = $role_filter;
}
$query .= ' ORDER BY created_at DESC';
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

$page_title = 'User Management — Admin';
require_once __DIR__ . '/header.php';
?>

<div class="page-header">
    <h1>User Management</h1>
    <button onclick="document.getElementById('add-user-modal').style.display='flex'" class="btn btn-primary btn-sm">+ Add User</button>
</div>

<!-- Filter / Search -->
<div class="card">
    <form action="" method="GET" class="flex gap-2 items-center" style="flex-wrap:wrap;">
        <input type="text" name="q" value="<?= sanitize($search) ?>" placeholder="Search name, email, username…" class="form-control" style="max-width:280px;">
        <select name="role" class="form-control" style="max-width:140px;">
            <option value="">All Roles</option>
            <option value="admin"    <?= $role_filter === 'admin'    ? 'selected' : '' ?>>Admin</option>
            <option value="customer" <?= $role_filter === 'customer' ? 'selected' : '' ?>>Customer</option>
        </select>
        <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        <?php if ($search || $role_filter): ?>
            <a href="users.php" class="btn btn-secondary btn-sm">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-header"><?= count($users) ?> Users Found</div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="text-dim"><?= $u['id'] ?></td>
                        <td class="td-name"><?= sanitize($u['full_name']) ?></td>
                        <td class="text-muted">@<?= sanitize($u['username']) ?></td>
                        <td class="text-muted"><?= sanitize($u['email']) ?></td>
                        <td>
                            <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                                <form method="POST" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="change_role">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <select name="role" class="form-control" style="padding:0.25rem 0.5rem;font-size:0.78rem;"
                                            onchange="this.form.submit()">
                                        <option value="customer" <?= $u['role'] === 'customer' ? 'selected' : '' ?>>Customer</option>
                                        <option value="admin"    <?= $u['role'] === 'admin'    ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                </form>
                            <?php else: ?>
                                <span class="badge badge-admin">Admin (You)</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-<?= $u['is_active'] ? 'active' : 'inactive' ?>">
                                <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="text-dim">
                            <?= $u['last_login'] ? date('M d, Y', strtotime($u['last_login'])) : 'Never' ?>
                        </td>
                        <td>
                            <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                                <div class="flex gap-1">
                                    <!-- Toggle active -->
                                    <form method="POST">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_active">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn btn-secondary btn-sm"
                                                title="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                            <?= $u['is_active'] ? '🔒' : '🔓' ?>
                                        </button>
                                    </form>
                                    <!-- Delete -->
                                    <form method="POST" data-confirm-form="Delete user <?= sanitize($u['username']) ?>? This cannot be undone.">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">🗑</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="text-dim" style="font-size:0.75rem;">Current</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add User Modal -->
<div id="add-user-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:200;align-items:center;justify-content:center;">
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:2rem;max-width:460px;width:100%;max-height:90vh;overflow-y:auto;">
        <div class="flex justify-between items-center mb-3">
            <h2 style="font-size:1.4rem;">Add New User</h2>
            <button onclick="document.getElementById('add-user-modal').style.display='none'" class="btn btn-secondary btn-sm">✕</button>
        </div>
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_user">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" required placeholder="Jane Doe">
            </div>
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required placeholder="janedoe">
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required placeholder="jane@example.com">
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" required placeholder="Min 8 chars, 1 uppercase, 1 number">
                <div style="font-size:0.75rem;margin-top:0.3rem;" id="pw-strength"></div>
            </div>
            <div class="form-group">
                <label class="form-label">Role</label>
                <select name="role" class="form-control">
                    <option value="customer">Customer</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Create User</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
