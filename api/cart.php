<?php
// ============================================
// api/cart.php — Cart AJAX API
// ============================================
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// Initialize cart in session
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function cart_count(): int {
    return array_sum(array_column($_SESSION['cart'] ?? [], 'quantity'));
}

switch ($action) {

    // ---- ADD item ------------------------------------------------
    case 'add':
        if (!verify_csrf()) {
            echo json_encode(['success' => false, 'message' => 'Invalid request.']);
            exit;
        }
        $product_id = (int)($_POST['product_id'] ?? 0);
        $qty        = max(1, (int)($_POST['quantity'] ?? 1));

        if ($product_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product.']);
            exit;
        }

        $pdo  = db();
        $stmt = $pdo->prepare('SELECT id, name, price, stock, image, is_active FROM products WHERE id = ? AND is_active = 1');
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found.']);
            exit;
        }
        if ($product['stock'] <= 0) {
            echo json_encode(['success' => false, 'message' => 'This item is out of stock.']);
            exit;
        }

        $current_qty = $_SESSION['cart'][$product_id]['quantity'] ?? 0;
        $new_qty     = min($current_qty + $qty, $product['stock']);

        $_SESSION['cart'][$product_id] = [
            'id'       => $product['id'],
            'name'     => $product['name'],
            'price'    => (float)$product['price'],
            'stock'    => (int)$product['stock'],
            'image'    => $product['image'],
            'quantity' => $new_qty,
        ];

        echo json_encode([
            'success' => true,
            'message' => sanitize($product['name']) . ' added to cart.',
            'cart_count' => cart_count(),
        ]);
        break;

    // ---- UPDATE quantity ----------------------------------------
    case 'update':
        if (!verify_csrf()) {
            echo json_encode(['success' => false, 'message' => 'Invalid request.']);
            exit;
        }
        $product_id = (int)($_POST['product_id'] ?? 0);
        $qty        = (int)($_POST['quantity'] ?? 1);

        if (!isset($_SESSION['cart'][$product_id])) {
            echo json_encode(['success' => false, 'message' => 'Item not in cart.']);
            exit;
        }

        if ($qty <= 0) {
            unset($_SESSION['cart'][$product_id]);
        } else {
            $max = $_SESSION['cart'][$product_id]['stock'];
            $_SESSION['cart'][$product_id]['quantity'] = min($qty, $max);
        }

        $subtotal = array_reduce($_SESSION['cart'], fn($carry, $item) => $carry + $item['price'] * $item['quantity'], 0);

        echo json_encode([
            'success'    => true,
            'cart_count' => cart_count(),
            'item_total' => isset($_SESSION['cart'][$product_id])
                ? number_format($_SESSION['cart'][$product_id]['price'] * $_SESSION['cart'][$product_id]['quantity'], 2)
                : '0.00',
            'subtotal'   => number_format($subtotal, 2),
        ]);
        break;

    // ---- REMOVE item --------------------------------------------
    case 'remove':
        if (!verify_csrf()) {
            echo json_encode(['success' => false, 'message' => 'Invalid request.']);
            exit;
        }
        $product_id = (int)($_POST['product_id'] ?? 0);
        unset($_SESSION['cart'][$product_id]);

        $subtotal = array_reduce($_SESSION['cart'], fn($carry, $item) => $carry + $item['price'] * $item['quantity'], 0);

        echo json_encode([
            'success'    => true,
            'cart_count' => cart_count(),
            'subtotal'   => number_format($subtotal, 2),
        ]);
        break;

    // ---- GET cart summary (for navbar badge) --------------------
    case 'get':
        echo json_encode([
            'success'    => true,
            'cart_count' => cart_count(),
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}
