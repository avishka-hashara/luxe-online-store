<?php
// ============================================
// admin/categories.php — Category Management
// ============================================
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) { flash('error', 'Security token invalid.'); header('Location: categories.php'); exit; }

    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!$name) { flash('error', 'Category name is required.'); header('Location: categories.php'); exit; }

        // Generate slug
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $slug = trim($slug, '-');

        if ($action === 'add') {
            // Check uniqueness
            $exists = $pdo->prepare('SELECT id FROM categories WHERE name = ? OR slug = ?');
            $exists->execute([$name, $slug]);
            if ($exists->fetch()) {
                flash('error', 'A category with that name already exists.');
                header('Location: categories.php'); exit;
            }
            $pdo->prepare('INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)')
                ->execute([$name, $slug, $description]);
            flash('success', "Category '$name' added.");
        } else {
            $cid = (int)($_POST['category_id'] ?? 0);
            $pdo->prepare('UPDATE categories SET name = ?, slug = ?, description = ? WHERE id = ?')
                ->execute([$name, $slug, $description, $cid]);
            flash('success', "Category '$name' updated.");
        }
    } elseif ($action === 'delete') {
        $cid = (int)($_POST['category_id'] ?? 0);
        // Check if products reference this
        $count = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
        $count->execute([$cid]);
        if ($count->fetchColumn() > 0) {
            flash('error', 'Cannot delete: category has products. Remove or reassign them first.');
            header('Location: categories.php'); exit;
        }
        $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$cid]);
        flash('success', 'Category deleted.');
    }

    header('Location: categories.php'); exit;
}

// Fetch categories with product count
$categories = $pdo->query('
    SELECT c.*, COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    GROUP BY c.id
    ORDER BY c.name
')->fetchAll();

// Editing?
$editing = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
    $s->execute([(int)$_GET['edit']]);
    $editing = $s->fetch();
}

$page_title = 'Categories — Admin';
require_once __DIR__ . '/header.php';
?>

<div class="page-header">
    <h1>Categories</h1>
    <button onclick="document.getElementById('cat-modal').style.display='flex'" class="btn btn-primary btn-sm">+ Add Category</button>
</div>

<!-- Categories Table -->
<div class="card">
    <div class="card-header"><?= count($categories) ?> Categories</div>
    <?php if (empty($categories)): ?>
        <div class="empty-state" style="padding:2rem;">
            <div class="empty-icon">🗂</div>
            <h3>No categories yet</h3>
            <p>Add your first category to get started.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Description</th>
                        <th>Products</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td class="text-dim"><?= $cat['id'] ?></td>
                            <td class="td-name"><?= sanitize($cat['name']) ?></td>
                            <td>
                                <code style="font-size:0.78rem;background:var(--bg-3);padding:0.15rem 0.4rem;border-radius:2px;color:var(--text-muted);">
                                    <?= sanitize($cat['slug']) ?>
                                </code>
                            </td>
                            <td class="text-muted" style="max-width:220px;">
                                <?= $cat['description'] ? sanitize(substr($cat['description'], 0, 60)) . (strlen($cat['description']) > 60 ? '…' : '') : '<span class="text-dim">—</span>' ?>
                            </td>
                            <td>
                                <span class="badge badge-<?= $cat['product_count'] > 0 ? 'active' : 'inactive' ?>">
                                    <?= $cat['product_count'] ?> product<?= $cat['product_count'] !== 1 ? 's' : '' ?>
                                </span>
                            </td>
                            <td class="text-dim"><?= date('M d, Y', strtotime($cat['created_at'])) ?></td>
                            <td>
                                <div class="flex gap-1">
                                    <a href="categories.php?edit=<?= $cat['id'] ?>" class="btn btn-secondary btn-sm">✏️</a>
                                    <?php if ($cat['product_count'] == 0): ?>
                                        <form method="POST" data-confirm-form="Delete category '<?= sanitize($cat['name']) ?>'?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">🗑</button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-danger btn-sm" disabled title="Has products" style="opacity:0.4;cursor:not-allowed;">🗑</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Add/Edit Category Modal -->
<?php $ed = $editing ?? []; ?>
<div id="cat-modal" style="display:<?= $editing ? 'flex' : 'none' ?>;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:200;align-items:center;justify-content:center;">
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:2rem;max-width:480px;width:100%;">
        <div class="flex justify-between items-center mb-3">
            <h2 style="font-size:1.4rem;"><?= $editing ? 'Edit Category' : 'Add Category' ?></h2>
            <a href="categories.php" class="btn btn-secondary btn-sm">✕</a>
        </div>
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= $editing ? 'edit' : 'add' ?>">
            <?php if ($editing): ?>
                <input type="hidden" name="category_id" value="<?= $editing['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label">Category Name</label>
                <input type="text" name="name" class="form-control" required autofocus
                       value="<?= sanitize($ed['name'] ?? '') ?>"
                       placeholder="e.g. Electronics">
                <p class="text-dim" style="font-size:0.75rem;margin-top:0.4rem;">Slug will be auto-generated from the name.</p>
            </div>

            <div class="form-group">
                <label class="form-label">Description <span class="text-dim">(optional)</span></label>
                <textarea name="description" class="form-control" placeholder="Short description of this category…"><?= sanitize($ed['description'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-full mt-2">
                <?= $editing ? 'Update Category' : 'Add Category' ?>
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
