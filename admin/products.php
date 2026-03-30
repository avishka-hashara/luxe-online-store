<?php
// ============================================
// admin/products.php — Product Management
// ============================================
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) { flash('error', 'Security token invalid.'); header('Location: products.php'); exit; }

    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $name        = trim($_POST['name'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $price       = (float)($_POST['price'] ?? 0);
        $stock       = (int)($_POST['stock'] ?? 0);
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_active   = isset($_POST['is_active']) ? 1 : 0;
        $uploaded_image = null;

        if (!$name || !$category_id || $price <= 0) {
            flash('error', 'Name, category, and valid price are required.');
            header('Location: products.php'); exit;
        }

        if (isset($_FILES['image_file']) && ($_FILES['image_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {
                flash('error', 'Image upload failed. Please try again.');
                header('Location: products.php'); exit;
            }

            if (($_FILES['image_file']['size'] ?? 0) > 5 * 1024 * 1024) {
                flash('error', 'Image must be 5MB or smaller.');
                header('Location: products.php'); exit;
            }

            $tmp_path = $_FILES['image_file']['tmp_name'];
            $mime = mime_content_type($tmp_path) ?: '';
            $allowed = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
                'image/gif'  => 'gif',
            ];

            if (!isset($allowed[$mime])) {
                flash('error', 'Only JPG, PNG, WEBP, or GIF images are allowed.');
                header('Location: products.php'); exit;
            }

            $upload_dir = __DIR__ . '/../assets/uploads/products';
            if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
                flash('error', 'Could not create upload directory.');
                header('Location: products.php'); exit;
            }

            $file_name = 'product-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
            $dest_path = $upload_dir . '/' . $file_name;

            if (!move_uploaded_file($tmp_path, $dest_path)) {
                flash('error', 'Could not save uploaded image.');
                header('Location: products.php'); exit;
            }

            $uploaded_image = 'assets/uploads/products/' . $file_name;
        }

        // Generate slug
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $slug = trim($slug, '-');

        if ($action === 'add') {
            if (!$uploaded_image) {
                flash('error', 'Product image is required.');
                header('Location: products.php'); exit;
            }

            // Make slug unique
            $i = 0;
            $base_slug = $slug;
            while ($pdo->prepare('SELECT id FROM products WHERE slug = ?')->execute([$slug]) &&
                   $pdo->prepare('SELECT id FROM products WHERE slug = ?')->execute([$slug]) &&
                   $pdo->query("SELECT id FROM products WHERE slug = '$slug'")->fetch()) {
                $slug = $base_slug . '-' . (++$i);
                if ($i > 100) break;
            }
            $pdo->prepare('
                INSERT INTO products (category_id, name, slug, description, image, price, stock, is_featured, is_active)
                VALUES (?,?,?,?,?,?,?,?,?)
            ')->execute([$category_id, $name, $slug, $description, $uploaded_image, $price, $stock, $is_featured, $is_active]);
            flash('success', 'Product added.');
        } else {
            $pid = (int)($_POST['product_id'] ?? 0);
            $current = $pdo->prepare('SELECT image FROM products WHERE id = ? LIMIT 1');
            $current->execute([$pid]);
            $current_product = $current->fetch();
            $final_image = $uploaded_image ?: ($current_product['image'] ?? '');

            if (!$final_image) {
                flash('error', 'Product image is required.');
                header('Location: products.php'); exit;
            }

            $pdo->prepare('
                UPDATE products SET category_id=?, name=?, description=?, image=?, price=?, stock=?, is_featured=?, is_active=? WHERE id=?
            ')->execute([$category_id, $name, $description, $final_image, $price, $stock, $is_featured, $is_active, $pid]);
            flash('success', 'Product updated.');
        }
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([(int)$_POST['product_id']]);
        flash('success', 'Product deleted.');
    } elseif ($action === 'toggle') {
        $pdo->prepare('UPDATE products SET is_active = NOT is_active WHERE id = ?')->execute([(int)$_POST['product_id']]);
        flash('success', 'Product status toggled.');
    }
    header('Location: products.php'); exit;
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$search = trim($_GET['q'] ?? '');
$cat_filter = (int)($_GET['cat'] ?? 0);

$q = 'SELECT p.*, c.name AS cat_name FROM products p JOIN categories c ON p.category_id = c.id WHERE 1=1';
$params = [];
if ($search) { $q .= ' AND (p.name LIKE ? OR p.description LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($cat_filter) { $q .= ' AND p.category_id = ?'; $params[] = $cat_filter; }
$q .= ' ORDER BY p.created_at DESC';
$stmt = $pdo->prepare($q); $stmt->execute($params);
$products = $stmt->fetchAll();

// Editing?
$editing = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $s->execute([(int)$_GET['edit']]);
    $editing = $s->fetch();
}

$page_title = 'Products — Admin';
require_once __DIR__ . '/header.php';
?>

<div class="page-header">
    <h1>Products</h1>
    <button onclick="document.getElementById('product-modal').style.display='flex'" class="btn btn-primary btn-sm">+ Add Product</button>
</div>

<!-- Filter -->
<div class="card">
    <form action="" method="GET" class="flex gap-2 items-center" style="flex-wrap:wrap;">
        <input type="text" name="q" value="<?= sanitize($search) ?>" placeholder="Search products…" class="form-control" style="max-width:240px;">
        <select name="cat" class="form-control" style="max-width:160px;">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $cat_filter === (int)$cat['id'] ? 'selected' : '' ?>><?= sanitize($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        <?php if ($search || $cat_filter): ?><a href="products.php" class="btn btn-secondary btn-sm">Clear</a><?php endif; ?>
    </form>
</div>

<!-- Products Table -->
<div class="card">
    <div class="card-header"><?= count($products) ?> Products</div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>#</th><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Featured</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td class="text-dim"><?= $p['id'] ?></td>
                        <td>
                            <?php if (!empty($p['image'])): ?>
                                <?php $img_src = preg_match('/^https?:\/\//i', $p['image']) ? $p['image'] : SITE_URL . '/' . ltrim($p['image'], '/'); ?>
                                <img src="<?= sanitize($img_src) ?>" alt="<?= sanitize($p['name']) ?>" style="width:52px;height:52px;object-fit:cover;border-radius:8px;border:1px solid var(--border);">
                            <?php else: ?>
                                <span class="text-dim">No image</span>
                            <?php endif; ?>
                        </td>
                        <td class="td-name"><?= sanitize($p['name']) ?></td>
                        <td class="text-muted"><?= sanitize($p['cat_name']) ?></td>
                        <td class="text-gold">Rs. <?= number_format($p['price'], 2) ?></td>
                        <td><?= $p['stock'] ?></td>
                        <td><?= $p['is_featured'] ? '<span class="badge badge-featured">Yes</span>' : '<span class="text-dim">—</span>' ?></td>
                        <td>
                            <span class="badge badge-<?= $p['is_active'] ? 'active' : 'inactive' ?>">
                                <?= $p['is_active'] ? 'Active' : 'Hidden' ?>
                            </span>
                        </td>
                        <td>
                            <div class="flex gap-1">
                                <a href="products.php?edit=<?= $p['id'] ?>" class="btn btn-secondary btn-sm">✏️</a>
                                <form method="POST">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn btn-secondary btn-sm" title="Toggle visibility">
                                        <?= $p['is_active'] ? '🙈' : '👁' ?>
                                    </button>
                                </form>
                                <form method="POST" data-confirm-form="Delete '<?= sanitize($p['name']) ?>'? This cannot be undone.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">🗑</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Product Modal -->
<?php
$modal_action = $editing ? 'edit' : 'add';
$modal_title  = $editing ? 'Edit Product' : 'Add New Product';
$ed = $editing ?? [];
?>
<div id="product-modal" style="display:<?= $editing ? 'flex' : 'none' ?>;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:200;align-items:center;justify-content:center;">
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:2rem;max-width:540px;width:100%;max-height:90vh;overflow-y:auto;">
        <div class="flex justify-between items-center mb-3">
            <h2 style="font-size:1.4rem;"><?= $modal_title ?></h2>
            <a href="products.php" class="btn btn-secondary btn-sm">✕</a>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= $modal_action ?>">
            <?php if ($editing): ?>
                <input type="hidden" name="product_id" value="<?= $editing['id'] ?>">
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Product Name</label>
                    <input type="text" name="name" class="form-control" required
                           value="<?= sanitize($ed['name'] ?? '') ?>" placeholder="Premium Widget">
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-control" required>
                        <option value="">Select…</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($ed['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                <?= sanitize($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Price (Rs.)</label>
                    <input type="number" name="price" class="form-control" step="0.01" min="0" required
                           value="<?= $ed['price'] ?? '' ?>" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label class="form-label">Stock</label>
                    <input type="number" name="stock" class="form-control" min="0" required
                           value="<?= $ed['stock'] ?? '0' ?>">
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control"><?= sanitize($ed['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Product Image <?= $editing ? '' : '<span class="text-gold">*</span>' ?></label>
                    <input type="file" name="image_file" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif" <?= $editing ? '' : 'required' ?>>
                    <?php if (!empty($ed['image'])): ?>
                        <?php $edit_img_src = preg_match('/^https?:\/\//i', $ed['image']) ? $ed['image'] : SITE_URL . '/' . ltrim($ed['image'], '/'); ?>
                        <div class="mt-2">
                            <img src="<?= sanitize($edit_img_src) ?>" alt="Current image" style="width:90px;height:90px;object-fit:cover;border-radius:8px;border:1px solid var(--border);">
                            <div class="text-dim" style="font-size:0.78rem;margin-top:0.35rem;">Leave empty to keep current image.</div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="form-group flex gap-2 items-center">
                    <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;font-size:0.85rem;">
                        <input type="checkbox" name="is_featured" value="1" <?= ($ed['is_featured'] ?? 0) ? 'checked' : '' ?>>
                        <span>Featured</span>
                    </label>
                </div>
                <div class="form-group flex gap-2 items-center">
                    <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;font-size:0.85rem;">
                        <input type="checkbox" name="is_active" value="1" <?= ($ed['is_active'] ?? 1) ? 'checked' : '' ?>>
                        <span>Active (visible in store)</span>
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-full mt-2">
                <?= $editing ? 'Update Product' : 'Add Product' ?>
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
