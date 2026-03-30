<?php
// ============================================
// pages/logout.php — Logout handler
// ============================================
require_once __DIR__ . '/../includes/auth.php';
logout_user();
header('Location: ' . SITE_URL . '/pages/login.php');
exit;
