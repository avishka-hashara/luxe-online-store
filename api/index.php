<?php
declare(strict_types=1);

// Vercel PHP functions run from /api. Route requests to existing app entrypoints.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if ($path === '/' || $path === '/index.php') {
    require __DIR__ . '/../index.php';
    exit;
}

if ($path === '/admin' || $path === '/admin/') {
    require __DIR__ . '/../admin/index.php';
    exit;
}

if (preg_match('#^/admin/([A-Za-z0-9_-]+)\\.php$#', $path, $m)) {
    $target = __DIR__ . '/../admin/' . $m[1] . '.php';
    if (is_file($target)) {
        require $target;
        exit;
    }
}

if (preg_match('#^/pages/([A-Za-z0-9_-]+)\\.php$#', $path, $m)) {
    $target = __DIR__ . '/../pages/' . $m[1] . '.php';
    if (is_file($target)) {
        require $target;
        exit;
    }
}

if ($path === '/admin-reset.php') {
    require __DIR__ . '/../admin-reset.php';
    exit;
}

http_response_code(404);
header('Content-Type: text/plain; charset=UTF-8');
echo 'Not Found';
