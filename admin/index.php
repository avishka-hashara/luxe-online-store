<?php
// ============================================
// admin/index.php — Admin Dashboard
// ============================================
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$pdo = db();

// Stats
$stats = [
    'users'    => $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'products' => $pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn(),
    'orders'   => $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'revenue'  => $pdo->query('SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status != "cancelled"')->fetchColumn(),
];

// Recent orders
$recent_orders = $pdo->query('
    SELECT o.id, o.total_amount, o.status, o.created_at, u.username, u.full_name
    FROM orders o JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC LIMIT 5
')->fetchAll();

// Recent users
$recent_users = $pdo->query('
    SELECT id, username, full_name, role, created_at, is_active
    FROM users ORDER BY created_at DESC LIMIT 5
')->fetchAll();

$page_title = 'Dashboard — Admin';
require_once __DIR__ . '/header.php';
?>

<div class="page-header">
    <h1>Dashboard</h1>
    <span class="text-dim" style="font-size:0.82rem;"><?= date('l, F j, Y') ?></span>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-value"><?= number_format($stats['users']) ?></div>
        <div class="stat-label">Total Users</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">🛍</div>
        <div class="stat-value"><?= number_format($stats['products']) ?></div>
        <div class="stat-label">Active Products</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">📦</div>
        <div class="stat-value"><?= number_format($stats['orders']) ?></div>
        <div class="stat-label">Total Orders</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">💰</div>
        <div class="stat-value">Rs. <?= number_format($stats['revenue'], 0) ?></div>
        <div class="stat-label">Revenue</div>
    </div>
</div>

<!-- Recent Orders -->
<div class="card">
    <div class="card-header">Recent Orders</div>
    <?php if (empty($recent_orders)): ?>
        <p class="text-muted">No orders yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Order #</th><th>Customer</th><th>Amount</th><th>Status</th><th>Date</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_orders as $o): ?>
                        <tr>
                            <td class="td-name">#<?= $o['id'] ?></td>
                            <td><?= sanitize($o['full_name']) ?> <span class="text-dim">(@<?= sanitize($o['username']) ?>)</span></td>
                            <td class="text-gold">Rs. <?= number_format($o['total_amount'], 2) ?></td>
                            <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                            <td class="text-dim"><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <a href="orders.php" class="btn btn-secondary btn-sm mt-2">View All Orders</a>
    <?php endif; ?>
</div>

<!-- Recent Users -->
<div class="card">
    <div class="card-header">Recent Users</div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Name</th><th>Username</th><th>Role</th><th>Status</th><th>Joined</th></tr>
            </thead>
            <tbody>
                <?php foreach ($recent_users as $u): ?>
                    <tr>
                        <td class="td-name"><?= sanitize($u['full_name']) ?></td>
                        <td class="text-muted">@<?= sanitize($u['username']) ?></td>
                        <td><span class="badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                        <td><span class="badge badge-<?= $u['is_active'] ? 'active' : 'inactive' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                        <td class="text-dim"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <a href="users.php" class="btn btn-secondary btn-sm mt-2">Manage Users</a>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
