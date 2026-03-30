<?php
// ============================================
// admin/orders.php — Order Management
// ============================================
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$pdo = db();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) { flash('error', 'Security token invalid.'); header('Location: orders.php'); exit; }

    $action   = $_POST['action'] ?? '';
    $order_id = (int)($_POST['order_id'] ?? 0);

    if ($action === 'update_status' && $order_id) {
        $valid_statuses = ['pending','processing','shipped','delivered','cancelled'];
        $status = $_POST['status'] ?? '';
        if (in_array($status, $valid_statuses)) {
            $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $order_id]);
            flash('success', "Order #$order_id status updated to '$status'.");
        }
    } elseif ($action === 'delete' && $order_id) {
        $pdo->prepare('DELETE FROM orders WHERE id = ?')->execute([$order_id]);
        flash('success', "Order #$order_id deleted.");
    }
    header('Location: orders.php'); exit;
}

// Filters
$status_filter = $_GET['status'] ?? '';
$search        = trim($_GET['q'] ?? '');

$q = '
    SELECT o.*, u.username, u.full_name, u.email,
           COUNT(oi.id) AS item_count
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE 1=1
';
$params = [];

if ($status_filter) { $q .= ' AND o.status = ?'; $params[] = $status_filter; }
if ($search) {
    $q .= ' AND (u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ?)';
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
$q .= ' GROUP BY o.id ORDER BY o.created_at DESC';
$stmt = $pdo->prepare($q);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Revenue summary
$revenue = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
$by_status = $pdo->query("SELECT status, COUNT(*) as cnt FROM orders GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

// Order detail (for expand)
$detail_order = null;
$detail_items = [];
if (isset($_GET['view'])) {
    $d = $pdo->prepare('SELECT o.*, u.username, u.full_name, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?');
    $d->execute([(int)$_GET['view']]);
    $detail_order = $d->fetch();
    if ($detail_order) {
        $di = $pdo->prepare('
            SELECT oi.*, p.name AS product_name
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ');
        $di->execute([$detail_order['id']]);
        $detail_items = $di->fetchAll();
    }
}

$page_title = 'Orders — Admin';
require_once __DIR__ . '/header.php';
?>

<div class="page-header">
    <h1>Orders</h1>
    <span class="text-gold" style="font-family:'Cormorant Garamond',serif;font-size:1.4rem;">
        Rs. <?= number_format($revenue, 2) ?> Revenue
    </span>
</div>

<!-- Status summary -->
<div class="stats-grid" style="grid-template-columns:repeat(5,1fr);margin-bottom:1.5rem;">
    <?php
    $statuses = ['pending','processing','shipped','delivered','cancelled'];
    $status_icons = ['pending'=>'⏳','processing'=>'⚙️','shipped'=>'🚚','delivered'=>'✅','cancelled'=>'❌'];
    foreach ($statuses as $s):
        $cnt = $by_status[$s] ?? 0;
    ?>
        <div class="stat-card" style="text-align:center;padding:1rem;">
            <div style="font-size:1.4rem;margin-bottom:0.4rem;"><?= $status_icons[$s] ?></div>
            <div class="stat-value" style="font-size:1.6rem;"><?= $cnt ?></div>
            <div class="stat-label"><?= ucfirst($s) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="card">
    <form action="" method="GET" class="flex gap-2 items-center" style="flex-wrap:wrap;">
        <input type="text" name="q" value="<?= sanitize($search) ?>"
               placeholder="Search by customer…" class="form-control" style="max-width:240px;">
        <select name="status" class="form-control" style="max-width:160px;">
            <option value="">All Statuses</option>
            <?php foreach ($statuses as $s): ?>
                <option value="<?= $s ?>" <?= $status_filter === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        <?php if ($search || $status_filter): ?>
            <a href="orders.php" class="btn btn-secondary btn-sm">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Order Detail Panel -->
<?php if ($detail_order): ?>
    <div class="card" style="border-color:var(--gold);background:var(--bg-3);">
        <div class="flex justify-between items-center mb-3">
            <h3 style="font-size:1.3rem;">Order #<?= $detail_order['id'] ?> — Details</h3>
            <a href="orders.php" class="btn btn-secondary btn-sm">✕ Close</a>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">
            <div>
                <p class="text-dim" style="font-size:0.72rem;letter-spacing:0.1em;text-transform:uppercase;margin-bottom:0.5rem;">Customer</p>
                <p class="td-name"><?= sanitize($detail_order['full_name']) ?></p>
                <p class="text-muted" style="font-size:0.85rem;">@<?= sanitize($detail_order['username']) ?></p>
                <p class="text-muted" style="font-size:0.85rem;"><?= sanitize($detail_order['email']) ?></p>
            </div>
            <div>
                <p class="text-dim" style="font-size:0.72rem;letter-spacing:0.1em;text-transform:uppercase;margin-bottom:0.5rem;">Order Info</p>
                <p class="text-muted" style="font-size:0.85rem;">Placed: <?= date('M d, Y H:i', strtotime($detail_order['created_at'])) ?></p>
                <p class="text-muted" style="font-size:0.85rem;">Status: <span class="badge badge-<?= $detail_order['status'] ?>"><?= ucfirst($detail_order['status']) ?></span></p>
                <p class="text-gold" style="font-size:1.1rem;font-family:'Cormorant Garamond',serif;margin-top:0.4rem;">
                    Total: Rs. <?= number_format($detail_order['total_amount'], 2) ?>
                </p>
            </div>
        </div>
        <?php if ($detail_order['shipping_address']): ?>
            <p class="text-dim" style="font-size:0.72rem;letter-spacing:0.1em;text-transform:uppercase;margin-bottom:0.4rem;">Shipping Address</p>
            <p class="text-muted" style="font-size:0.85rem;margin-bottom:1rem;"><?= sanitize($detail_order['shipping_address']) ?></p>
        <?php endif; ?>
        <p class="text-dim" style="font-size:0.72rem;letter-spacing:0.1em;text-transform:uppercase;margin-bottom:0.8rem;">Items</p>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
                <tbody>
                    <?php foreach ($detail_items as $item): ?>
                        <tr>
                            <td class="td-name"><?= sanitize($item['product_name']) ?></td>
                            <td><?= $item['quantity'] ?></td>
                            <td>Rs. <?= number_format($item['unit_price'], 2) ?></td>
                            <td class="text-gold">Rs. <?= number_format($item['unit_price'] * $item['quantity'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Orders Table -->
<div class="card">
    <div class="card-header"><?= count($orders) ?> Orders</div>
    <?php if (empty($orders)): ?>
        <div class="empty-state" style="padding:2rem;">
            <div class="empty-icon">📦</div>
            <h3>No orders found</h3>
            <p>Adjust your filters to see more results.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td>
                                <a href="orders.php?view=<?= $o['id'] ?>" class="text-gold">#<?= $o['id'] ?></a>
                            </td>
                            <td>
                                <div class="td-name"><?= sanitize($o['full_name']) ?></div>
                                <div class="text-dim" style="font-size:0.78rem;"><?= sanitize($o['email']) ?></div>
                            </td>
                            <td><?= $o['item_count'] ?></td>
                            <td class="text-gold">Rs. <?= number_format($o['total_amount'], 2) ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                    <select name="status" class="form-control"
                                            style="padding:0.25rem 0.5rem;font-size:0.78rem;max-width:130px;"
                                            onchange="this.form.submit()">
                                        <?php foreach ($statuses as $s): ?>
                                            <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>>
                                                <?= ucfirst($s) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                            <td class="text-dim"><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
                            <td>
                                <div class="flex gap-1">
                                    <a href="orders.php?view=<?= $o['id'] ?>" class="btn btn-secondary btn-sm" title="View Details">🔍</a>
                                    <form method="POST" data-confirm-form="Delete Order #<?= $o['id'] ?>? This cannot be undone.">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">🗑</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
