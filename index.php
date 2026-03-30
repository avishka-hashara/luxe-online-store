<?php
// ============================================
// index.php — Homepage / Shop
// ============================================
require_once __DIR__ . '/includes/auth.php';

$page_title = 'LUXE STORE — Curated Luxury';
$pdo = db();

// Fetch categories
$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();

// Fetch products with optional category filter
$category_slug = $_GET['category'] ?? null;
$search = trim($_GET['q'] ?? '');

$query = '
    SELECT p.*, c.name AS category_name, c.slug AS category_slug
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.is_active = 1
';
$params = [];

if ($category_slug) {
    $query .= ' AND c.slug = ?';
    $params[] = $category_slug;
}
if ($search) {
    $query .= ' AND (p.name LIKE ? OR p.description LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$query .= ' ORDER BY p.is_featured DESC, p.created_at DESC';

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Product emojis by category
$emojis = [
    'electronics' => ['🎧','⌚','🔊','💻','📱'],
    'fashion'     => ['👕','👜','🧣','💍','🕶'],
    'home-living' => ['💡','☕','🪴','🛋','🕯'],
    'beauty'      => ['✨','💄','🌿','🧴','💅'],
];

function get_emoji(string $slug, int $id): string {
    global $emojis;
    $arr = $emojis[$slug] ?? ['📦'];
    return $arr[$id % count($arr)];
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- HERO -->
<section class="hero">
    <p class="hero-eyebrow">New Collection 2025</p>
    <h1>Luxury, <em>Redefined</em><br>for the Modern World</h1>
    <p>Carefully curated pieces that balance exceptional craft with understated elegance.</p>
    <form action="index.php" method="GET" class="search-bar" style="max-width:440px;margin:0 auto 1rem;">
        <input type="text" name="q" value="<?= sanitize($search) ?>" placeholder="Search products…" class="form-control">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</section>

<!-- CATEGORY FILTER -->
<div class="category-filter">
    <a href="index.php" class="cat-btn <?= !$category_slug ? 'active' : '' ?>">All</a>
    <?php foreach ($categories as $cat): ?>
        <a href="index.php?category=<?= $cat['slug'] ?>"
           class="cat-btn <?= $category_slug === $cat['slug'] ? 'active' : '' ?>">
            <?= sanitize($cat['name']) ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- PRODUCTS -->
<div class="section-header">
    <h2><?= $search ? 'Results for "' . sanitize($search) . '"' : 'All Products' ?></h2>
    <span class="text-muted"><?= count($products) ?> items</span>
</div>

<?php if (empty($products)): ?>
    <div class="empty-state">
        <div class="empty-icon">🔍</div>
        <h3>No products found</h3>
        <p>Try a different search term or browse all categories.</p>
        <a href="index.php" class="btn btn-secondary mt-2">View All</a>
    </div>
<?php else: ?>
    <div class="products-grid">
        <?php foreach ($products as $product): ?>
            <div class="product-card" data-category="<?= sanitize($product['category_slug']) ?>">
                <div class="product-image">
                    <?php if ($product['is_featured']): ?>
                        <span class="product-badge">Featured</span>
                    <?php endif; ?>
                    <?php if (!empty($product['image'])): ?>
                        <?php $product_img = preg_match('/^https?:\/\//i', $product['image']) ? $product['image'] : SITE_URL . '/' . ltrim($product['image'], '/'); ?>
                        <img src="<?= sanitize($product_img) ?>" alt="<?= sanitize($product['name']) ?>" style="width:100%;height:100%;object-fit:cover;display:block;">
                    <?php else: ?>
                        <?= get_emoji($product['category_slug'], $product['id']) ?>
                    <?php endif; ?>
                </div>
                <div class="product-info">
                    <div class="product-category"><?= sanitize($product['category_name']) ?></div>
                    <div class="product-name"><?= sanitize($product['name']) ?></div>
                    <div class="product-desc"><?= sanitize($product['description'] ?? '') ?></div>
                    <div class="product-footer">
                        <span class="product-price">Rs. <?= number_format($product['price'], 2) ?></span>
                        <span class="product-stock">
                            <?= $product['stock'] > 10 ? 'In Stock' : ($product['stock'] > 0 ? 'Low Stock' : 'Sold Out') ?>
                        </span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
