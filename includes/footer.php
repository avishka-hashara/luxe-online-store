</main>

<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <span class="brand-icon">◆</span> LUXE STORE
            <p>Curated luxury for the discerning eye.</p>
        </div>
        <div class="footer-links">
            <h4>Shop</h4>
            <a href="<?= SITE_URL ?>/index.php?category=electronics">Electronics</a>
            <a href="<?= SITE_URL ?>/index.php?category=fashion">Fashion</a>
            <a href="<?= SITE_URL ?>/index.php?category=home-living">Home &amp; Living</a>
            <a href="<?= SITE_URL ?>/index.php?category=beauty">Beauty</a>
        </div>
        <div class="footer-links">
            <h4>Account</h4>
            <?php if (is_logged_in()): ?>
                <a href="<?= SITE_URL ?>/pages/account.php">My Account</a>
                <a href="<?= SITE_URL ?>/pages/logout.php">Sign Out</a>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/pages/login.php">Sign In</a>
                <a href="<?= SITE_URL ?>/pages/register.php">Register</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</p>
    </div>
</footer>

<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
