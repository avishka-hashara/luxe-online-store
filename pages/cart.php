<?php
// ============================================
// pages/cart.php — Shopping Cart
// ============================================
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Shopping Cart — LUXE STORE';

// Initialize cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cart   = $_SESSION['cart'];
$subtotal = array_reduce($cart, fn($carry, $item) => $carry + $item['price'] * $item['quantity'], 0.0);
$shipping = $subtotal > 0 ? 350.00 : 0.00;   // flat shipping fee
$total    = $subtotal + $shipping;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="cart-page">

    <div class="section-header">
        <h2>Shopping Cart</h2>
        <span class="text-muted"><?= count($cart) ?> item<?= count($cart) !== 1 ? 's' : '' ?></span>
    </div>

    <?php if (empty($cart)): ?>
        <div class="empty-state">
            <div class="empty-icon">🛒</div>
            <h3>Your cart is empty</h3>
            <p>Browse our collection and add something you love.</p>
            <a href="<?= SITE_URL ?>/index.php" class="btn btn-primary mt-2">Continue Shopping</a>
        </div>
    <?php else: ?>

        <div class="cart-layout">
            <!-- Cart Items -->
            <div class="cart-items">
                <?php foreach ($cart as $item): ?>
                    <?php
                        $img_url = !empty($item['image'])
                            ? (preg_match('/^https?:\/\//i', $item['image']) ? $item['image'] : SITE_URL . '/' . ltrim($item['image'], '/'))
                            : null;
                    ?>
                    <div class="cart-row" id="cart-row-<?= $item['id'] ?>">
                        <div class="cart-item-img">
                            <?php if ($img_url): ?>
                                <img src="<?= sanitize($img_url) ?>" alt="<?= sanitize($item['name']) ?>">
                            <?php else: ?>
                                <span style="font-size:2rem;">📦</span>
                            <?php endif; ?>
                        </div>

                        <div class="cart-item-info">
                            <div class="cart-item-name"><?= sanitize($item['name']) ?></div>
                            <div class="cart-item-price">Rs. <?= number_format($item['price'], 2) ?> each</div>
                        </div>

                        <div class="cart-item-qty">
                            <button class="qty-btn" data-action="dec" data-id="<?= $item['id'] ?>">−</button>
                            <input type="number"
                                   class="qty-input"
                                   id="qty-<?= $item['id'] ?>"
                                   value="<?= $item['quantity'] ?>"
                                   min="1"
                                   max="<?= $item['stock'] ?>"
                                   data-id="<?= $item['id'] ?>">
                            <button class="qty-btn" data-action="inc" data-id="<?= $item['id'] ?>" data-max="<?= $item['stock'] ?>">+</button>
                        </div>

                        <div class="cart-item-total" id="item-total-<?= $item['id'] ?>">
                            Rs. <?= number_format($item['price'] * $item['quantity'], 2) ?>
                        </div>

                        <button class="cart-remove" data-id="<?= $item['id'] ?>" title="Remove">✕</button>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Order Summary -->
            <div class="cart-summary">
                <div class="card">
                    <div class="card-header">Order Summary</div>

                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span id="summary-subtotal">Rs. <?= number_format($subtotal, 2) ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span id="summary-shipping">Rs. <?= number_format($shipping, 2) ?></span>
                    </div>
                    <hr class="divider">
                    <div class="summary-row summary-total">
                        <span>Total</span>
                        <span id="summary-total">Rs. <?= number_format($total, 2) ?></span>
                    </div>

                    <a href="<?= SITE_URL ?>/pages/checkout.php"
                       class="btn btn-primary btn-full mt-3"
                       id="checkout-btn">
                        Proceed to Checkout →
                    </a>
                    <a href="<?= SITE_URL ?>/index.php" class="btn btn-secondary btn-full mt-2">
                        Continue Shopping
                    </a>

                    <?php if (!is_logged_in()): ?>
                        <p class="text-muted text-center mt-2" style="font-size:0.78rem;">
                            You'll need to <a href="<?= SITE_URL ?>/pages/login.php?redirect=checkout">sign in</a>
                            or <a href="<?= SITE_URL ?>/pages/register.php">register</a> before checkout.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<!-- Hidden CSRF token for AJAX -->
<input type="hidden" id="csrf_token" value="<?= csrf_token() ?>">

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
