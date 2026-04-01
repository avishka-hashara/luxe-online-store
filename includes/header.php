<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-inner">
        <a class="brand" href="<?= SITE_URL ?>/index.php">
            <span class="brand-icon">◆</span>
            LUXE STORE
        </a>
        <div class="nav-links">
            <a href="<?= SITE_URL ?>/index.php">Shop</a>
            <!-- Cart Icon -->
            <?php
                $cart_count = array_sum(array_column($_SESSION['cart'] ?? [], 'quantity'));
            ?>
            <a href="<?= SITE_URL ?>/pages/cart.php" class="nav-cart" id="nav-cart">
                🛒
                <span class="cart-badge" id="cart-badge" <?= $cart_count === 0 ? 'style="display:none;"' : '' ?>>
                    <?= $cart_count ?>
                </span>
            </a>
            <?php if (is_logged_in()): ?>
                <a href="<?= SITE_URL ?>/pages/account.php">My Account</a>
                <?php if (is_admin()): ?>
                    <a href="<?= SITE_URL ?>/admin/index.php" class="nav-admin">Admin Panel</a>
                <?php endif; ?>
                <a href="<?= SITE_URL ?>/pages/logout.php" class="btn-nav-outline">Sign Out</a>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/pages/login.php">Sign In</a>
                <a href="<?= SITE_URL ?>/pages/register.php" class="btn-nav">Join</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="main-content">
<?php
// Display flash messages
foreach (['success','error','info'] as $type) {
    $msg = get_flash($type);
    if ($msg): ?>
    <div class="flash flash-<?= $type ?>"><?= sanitize($msg) ?></div>
    <?php endif;
}
?>
