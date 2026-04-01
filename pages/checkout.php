<?php
// ============================================
// pages/checkout.php — Checkout & Payment
// ============================================
require_once __DIR__ . '/../includes/auth.php';
require_login('/pages/login.php?redirect=checkout');

$page_title = 'Checkout — LUXE STORE';
$pdo = db();

// Redirect if cart is empty
if (empty($_SESSION['cart'])) {
    flash('info', 'Your cart is empty.');
    header('Location: ' . SITE_URL . '/pages/cart.php');
    exit;
}

$cart     = $_SESSION['cart'];
$subtotal = array_reduce($cart, fn($c, $i) => $c + $i['price'] * $i['quantity'], 0.0);
$shipping = 350.00;
$total    = $subtotal + $shipping;
$user     = current_user();
$errors   = [];

// ---- Handle POST (place order) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? '') || ($errors[] = 'Invalid request token.');

    $full_name    = sanitize(trim($_POST['full_name']    ?? ''));
    $phone        = sanitize(trim($_POST['phone']        ?? ''));
    $address_line = sanitize(trim($_POST['address_line'] ?? ''));
    $city         = sanitize(trim($_POST['city']         ?? ''));
    $postal_code  = sanitize(trim($_POST['postal_code']  ?? ''));
    $payment      = $_POST['payment_method'] ?? '';

    // Shipping validation
    if (!$full_name)    $errors[] = 'Full name is required.';
    if (!$phone)        $errors[] = 'Phone number is required.';
    if (!$address_line) $errors[] = 'Address is required.';
    if (!$city)         $errors[] = 'City is required.';
    if (!$postal_code)  $errors[] = 'Postal code is required.';
    if (!in_array($payment, ['card', 'cod'])) $errors[] = 'Please select a payment method.';

    // Card validation
    if ($payment === 'card' && empty($errors)) {
        $card_number = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
        $card_expiry = trim($_POST['card_expiry'] ?? '');
        $card_cvv    = trim($_POST['card_cvv']    ?? '');
        $card_name   = sanitize(trim($_POST['card_name'] ?? ''));

        if (!preg_match('/^\d{16}$/', $card_number))         $errors[] = 'Card number must be 16 digits.';
        if (!preg_match('/^\d{2}\/\d{2}$/', $card_expiry))   $errors[] = 'Card expiry must be MM/YY format.';
        if (!preg_match('/^\d{3,4}$/', $card_cvv))           $errors[] = 'CVV must be 3 or 4 digits.';
        if (!$card_name)                                      $errors[] = 'Name on card is required.';

        // Check expiry not in the past
        if (empty($errors)) {
            [$exp_m, $exp_y] = explode('/', $card_expiry);
            $exp_ts = mktime(0, 0, 0, (int)$exp_m + 1, 0, (int)('20' . $exp_y));
            if ($exp_ts < time()) $errors[] = 'Card has expired.';
        }
    }

    // --- Verify stock once more before placing order ---
    if (empty($errors)) {
        foreach ($cart as $item) {
            $row = $pdo->prepare('SELECT stock FROM products WHERE id = ? AND is_active = 1');
            $row->execute([$item['id']]);
            $prod = $row->fetch();
            if (!$prod || $prod['stock'] < $item['quantity']) {
                $errors[] = sanitize($item['name']) . ' does not have enough stock.';
            }
        }
    }

    // --- Place the order ---
    if (empty($errors)) {
        $shipping_address = json_encode([
            'full_name'    => $full_name,
            'phone'        => $phone,
            'address_line' => $address_line,
            'city'         => $city,
            'postal_code'  => $postal_code,
        ]);

        try {
            $pdo->beginTransaction();

            // Insert order
            $stmt = $pdo->prepare('
                INSERT INTO orders (user_id, total_amount, status, shipping_address)
                VALUES (?, ?, \'pending\', ?)
            ');
            $stmt->execute([$_SESSION['user_id'], $total, $shipping_address]);
            $order_id = $pdo->lastInsertId();

            // Insert order items & deduct stock
            foreach ($cart as $item) {
                $stmt = $pdo->prepare('
                    INSERT INTO order_items (order_id, product_id, quantity, unit_price)
                    VALUES (?, ?, ?, ?)
                ');
                $stmt->execute([$order_id, $item['id'], $item['quantity'], $item['price']]);

                $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?')
                    ->execute([$item['quantity'], $item['id']]);
            }

            $pdo->commit();

            // Clear cart
            $_SESSION['cart'] = [];

            // Forward to confirmation
            header('Location: ' . SITE_URL . '/pages/order-confirmation.php?order=' . $order_id);
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Something went wrong placing your order. Please try again.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="checkout-page">
    <div class="section-header">
        <h2>Checkout</h2>
        <a href="<?= SITE_URL ?>/pages/cart.php" class="text-muted" style="font-size:0.82rem;">← Back to Cart</a>
    </div>

    <?php foreach ($errors as $err): ?>
        <div class="flash flash-error"><?= $err ?></div>
    <?php endforeach; ?>

    <form method="POST" id="checkout-form">
        <?= csrf_field() ?>
        <input type="hidden" name="payment_method" id="payment_method_hidden" value="cod">

        <div class="checkout-layout">

            <!-- LEFT: Shipping + Payment -->
            <div class="checkout-left">

                <!-- Shipping Address -->
                <div class="card mb-3">
                    <div class="card-header">Shipping Address</div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control"
                                   value="<?= sanitize($_POST['full_name'] ?? $user['full_name'] ?? '') ?>"
                                   placeholder="John Doe" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control"
                                   value="<?= sanitize($_POST['phone'] ?? '') ?>"
                                   placeholder="+94 77 123 4567" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <input type="text" name="address_line" class="form-control"
                               value="<?= sanitize($_POST['address_line'] ?? '') ?>"
                               placeholder="No. 12, Main Street" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control"
                                   value="<?= sanitize($_POST['city'] ?? '') ?>"
                                   placeholder="Colombo" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Postal Code</label>
                            <input type="text" name="postal_code" class="form-control"
                                   value="<?= sanitize($_POST['postal_code'] ?? '') ?>"
                                   placeholder="00100" required>
                        </div>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="card mb-3">
                    <div class="card-header">Payment Method</div>

                    <div class="payment-methods">
                        <label class="payment-option" id="opt-card">
                            <input type="radio" name="payment_choice" value="card"
                                   <?= ($_POST['payment_method'] ?? 'cod') === 'card' ? 'checked' : '' ?>>
                            <span class="payment-option-inner">
                                <span class="payment-icon">💳</span>
                                <span>
                                    <strong>Credit / Debit Card</strong>
                                    <small>Visa, Mastercard, Amex</small>
                                </span>
                                <span class="payment-check">✓</span>
                            </span>
                        </label>

                        <label class="payment-option" id="opt-cod">
                            <input type="radio" name="payment_choice" value="cod"
                                   <?= ($_POST['payment_method'] ?? 'cod') === 'cod' ? 'checked' : '' ?>>
                            <span class="payment-option-inner">
                                <span class="payment-icon">💵</span>
                                <span>
                                    <strong>Cash on Delivery</strong>
                                    <small>Pay when your order arrives</small>
                                </span>
                                <span class="payment-check">✓</span>
                            </span>
                        </label>
                    </div>

                    <!-- Card Form (shown only when card is selected) -->
                    <div id="card-form" style="display:none; margin-top:1.5rem;">
                        <div class="form-group">
                            <label class="form-label">Name on Card</label>
                            <input type="text" name="card_name" id="card_name" class="form-control"
                                   value="<?= sanitize($_POST['card_name'] ?? '') ?>"
                                   placeholder="John Doe" autocomplete="cc-name">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Card Number</label>
                            <input type="text" name="card_number" id="card_number" class="form-control"
                                   value="<?= sanitize($_POST['card_number'] ?? '') ?>"
                                   placeholder="1234 5678 9012 3456"
                                   maxlength="19" autocomplete="cc-number">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Expiry (MM/YY)</label>
                                <input type="text" name="card_expiry" id="card_expiry" class="form-control"
                                       value="<?= sanitize($_POST['card_expiry'] ?? '') ?>"
                                       placeholder="12/27" maxlength="5" autocomplete="cc-exp">
                            </div>
                            <div class="form-group">
                                <label class="form-label">CVV</label>
                                <input type="password" name="card_cvv" id="card_cvv" class="form-control"
                                       value="" placeholder="•••" maxlength="4" autocomplete="cc-csc">
                            </div>
                        </div>
                        <p class="text-muted" style="font-size:0.76rem; margin-top:0.5rem;">
                            🔒 Your payment information is encrypted and secure.
                        </p>
                    </div>
                </div>

            </div>

            <!-- RIGHT: Order Summary -->
            <div class="checkout-right">
                <div class="card">
                    <div class="card-header">Order Summary</div>

                    <div class="checkout-items-list">
                        <?php foreach ($cart as $item): ?>
                            <div class="checkout-item">
                                <span class="checkout-item-name">
                                    <?= sanitize($item['name']) ?>
                                    <small>× <?= $item['quantity'] ?></small>
                                </span>
                                <span class="checkout-item-price">
                                    Rs. <?= number_format($item['price'] * $item['quantity'], 2) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <hr class="divider">

                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>Rs. <?= number_format($subtotal, 2) ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span>Rs. <?= number_format($shipping, 2) ?></span>
                    </div>
                    <hr class="divider">
                    <div class="summary-row summary-total">
                        <span>Total</span>
                        <span>Rs. <?= number_format($total, 2) ?></span>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full mt-3" id="place-order-btn">
                        Place Order →
                    </button>
                    <p class="text-muted text-center mt-2" style="font-size:0.76rem;">
                        By placing your order you agree to our terms of service.
                    </p>
                </div>
            </div>

        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
