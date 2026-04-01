<?php
// ============================================
// pages/order-confirmation.php — Order Success
// ============================================
require_once __DIR__ . '/../includes/auth.php';
require_login();

$page_title = 'Order Confirmed — LUXE STORE';
$pdo = db();

$order_id = (int)($_GET['order'] ?? 0);

if (!$order_id) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

// Fetch order (must belong to current user)
$stmt = $pdo->prepare('
    SELECT o.*, u.full_name, u.email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ? AND o.user_id = ?
');
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    flash('error', 'Order not found.');
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

// Fetch order items
$items_stmt = $pdo->prepare('
    SELECT oi.quantity, oi.unit_price, p.name, p.image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
');
$items_stmt->execute([$order_id]);
$items = $items_stmt->fetchAll();

// Decode shipping address
$address = json_decode($order['shipping_address'], true) ?? [];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="confirmation-page">

    <div class="confirmation-hero">
        <div class="confirmation-check">✓</div>
        <h1>Order Confirmed!</h1>
        <p>Thank you, <strong><?= sanitize($order['full_name']) ?></strong>. Your order has been placed successfully.</p>
        <p class="order-number">Order #<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></p>
    </div>

    <div class="confirmation-layout">

        <!-- Order Items -->
        <div class="card">
            <div class="card-header">Items Ordered</div>
            <?php foreach ($items as $item):
                $img_url = !empty($item['image'])
                    ? (preg_match('/^https?:\/\//i', $item['image']) ? $item['image'] : SITE_URL . '/' . ltrim($item['image'], '/'))
                    : null;
            ?>
                <div class="confirm-item">
                    <div class="confirm-item-img">
                        <?php if ($img_url): ?>
                            <img src="<?= sanitize($img_url) ?>" alt="<?= sanitize($item['name']) ?>">
                        <?php else: ?>
                            <span style="font-size:1.5rem;">📦</span>
                        <?php endif; ?>
                    </div>
                    <div class="confirm-item-info">
                        <div class="confirm-item-name"><?= sanitize($item['name']) ?></div>
                        <div class="confirm-item-meta">Qty: <?= $item['quantity'] ?> × Rs. <?= number_format($item['unit_price'], 2) ?></div>
                    </div>
                    <div class="confirm-item-price">
                        Rs. <?= number_format($item['unit_price'] * $item['quantity'], 2) ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <hr class="divider">
            <div class="summary-row">
                <span>Subtotal</span>
                <span>Rs. <?= number_format($order['total_amount'] - 350, 2) ?></span>
            </div>
            <div class="summary-row">
                <span>Shipping</span>
                <span>Rs. 350.00</span>
            </div>
            <hr class="divider">
            <div class="summary-row summary-total">
                <span>Total Paid</span>
                <span>Rs. <?= number_format($order['total_amount'], 2) ?></span>
            </div>
        </div>

        <!-- Shipping + Status -->
        <div>
            <div class="card mb-3">
                <div class="card-header">Shipping Address</div>
                <?php if ($address): ?>
                    <p style="font-size:0.9rem; line-height:1.8; color:var(--text-muted);">
                        <strong style="color:var(--text);"><?= sanitize($address['full_name'] ?? '') ?></strong><br>
                        <?= sanitize($address['address_line'] ?? '') ?><br>
                        <?= sanitize($address['city'] ?? '') ?>, <?= sanitize($address['postal_code'] ?? '') ?><br>
                        <?= sanitize($address['phone'] ?? '') ?>
                    </p>
                <?php endif; ?>
            </div>

            <div class="card mb-3">
                <div class="card-header">Order Status</div>
                <div class="status-timeline">
                    <div class="status-step active">
                        <span class="status-dot">●</span>
                        <span>Order Placed</span>
                    </div>
                    <div class="status-step <?= in_array($order['status'], ['processing','shipped','delivered']) ? 'active' : '' ?>">
                        <span class="status-dot">●</span>
                        <span>Processing</span>
                    </div>
                    <div class="status-step <?= in_array($order['status'], ['shipped','delivered']) ? 'active' : '' ?>">
                        <span class="status-dot">●</span>
                        <span>Shipped</span>
                    </div>
                    <div class="status-step <?= $order['status'] === 'delivered' ? 'active' : '' ?>">
                        <span class="status-dot">●</span>
                        <span>Delivered</span>
                    </div>
                </div>
                <p class="text-muted mt-2" style="font-size:0.8rem;">
                    A confirmation has been sent to <strong><?= sanitize($order['email']) ?></strong>.
                </p>
            </div>

            <div class="flex gap-2">
                <a href="<?= SITE_URL ?>/pages/account.php" class="btn btn-secondary" style="flex:1; justify-content:center;">
                    View Orders
                </a>
                <a href="<?= SITE_URL ?>/index.php" class="btn btn-primary" style="flex:1; justify-content:center;">
                    Continue Shopping
                </a>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
