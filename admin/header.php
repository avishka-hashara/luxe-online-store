<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Admin — ' . SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-inner">
        <a class="brand" href="<?= SITE_URL ?>/admin/index.php">
            <span class="brand-icon">◆</span>
            LUXE ADMIN
        </a>
        <div class="nav-links">
            <a href="<?= SITE_URL ?>/index.php" class="text-muted">← View Store</a>
            <span class="text-dim" style="font-size:0.82rem;"><?= sanitize($_SESSION['full_name'] ?? '') ?></span>
            <a href="<?= SITE_URL ?>/pages/logout.php" class="btn-nav-outline">Sign Out</a>
        </div>
    </div>
</nav>

<div class="main-content">
<?php
foreach (['success','error','info'] as $type) {
    $msg = get_flash($type);
    if ($msg): ?>
    <div class="flash flash-<?= $type ?>"><?= sanitize($msg) ?></div>
    <?php endif;
}
?>
<div class="admin-layout">

    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <h3>Navigation</h3>
        <?php
        $current = basename($_SERVER['PHP_SELF']);
        $links = [
            'index.php'    => ['icon' => '📊', 'label' => 'Dashboard'],
            'users.php'    => ['icon' => '👥', 'label' => 'Users'],
            'products.php' => ['icon' => '🛍',  'label' => 'Products'],
            'categories.php' => ['icon' => '🗂', 'label' => 'Categories'],
            'orders.php'   => ['icon' => '📦', 'label' => 'Orders'],
        ];
        foreach ($links as $file => $info): ?>
            <a href="<?= SITE_URL ?>/admin/<?= $file ?>"
               class="sidebar-link <?= $current === $file ? 'active' : '' ?>">
                <span class="icon"><?= $info['icon'] ?></span>
                <?= $info['label'] ?>
            </a>
        <?php endforeach; ?>
    </aside>

    <!-- Main admin content -->
    <div class="admin-content">
