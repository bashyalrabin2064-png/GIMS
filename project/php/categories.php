<?php
$required_role = 'admin';
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db.php';
$pdo = get_db_connection();

$formError   = '';
$formSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $method = $_POST['_method'] ?? 'POST';

    if ($method === 'POST') {
        $name = trim($_POST['name'] ?? '');

        if (empty($name)) {
            $formError = 'Category name is required.';
        } else {
            $checkStmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
            $checkStmt->execute([$name]);
            if ($checkStmt->fetch()) {
                $formError = 'A category with this name already exists.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                $stmt->execute([$name]);
                $formSuccess = 'Category "' . htmlspecialchars($name) . '" added successfully.';
            }
        }
    }

    if ($method === 'PUT') {
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $name       = trim($_POST['name'] ?? '');

        if (empty($name) || $categoryId === 0) {
            $formError = 'Category name is required.';
        } else {
            $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?");
            $stmt->execute([$name, $categoryId]);
            $formSuccess = 'Category updated successfully.';
        }
    }

    if ($method === 'DELETE') {
        $categoryId = (int)($_POST['category_id'] ?? 0);
        if ($categoryId > 0) {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$categoryId]);
            $formSuccess = 'Category deleted. Linked products are now uncategorized.';
        }
    }
}

$stmt = $pdo->query(
    "SELECT c.id, c.name, c.created_at,
            COUNT(p.id) AS product_count
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.id, c.name, c.created_at
     ORDER BY c.name ASC"
);
$categories = $stmt->fetchAll();

$totalCategories = count($categories);
$totalCategorizedProducts = 0;
$topCategoryName = 'None';
$maxCount = -1;

foreach ($categories as $cat) {
    $cCount = (int)$cat['product_count'];
    $totalCategorizedProducts += $cCount;
    if ($cCount > $maxCount && $cCount > 0) {
        $maxCount = $cCount;
        $topCategoryName = $cat['name'];
    }
}

$uncategorizedCount = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE category_id IS NULL")->fetchColumn();

$page_title  = 'Categories';
$active_page = 'categories';
require_once __DIR__ . '/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-tags"></i> Categories
        <span class="badge rounded-pill bg-light text-secondary border ms-2"><?= $totalCategories ?> categories</span>
    </h2>
    <button class="btn btn-gims-primary"
            data-bs-toggle="modal" data-bs-target="#categoryModal"
            onclick="openAddCategoryForm()">
        <i class="bi bi-plus-lg"></i> Add Category
    </button>
</div>

<?php if (!empty($formSuccess)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($formSuccess) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (!empty($formError)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($formError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-0" style="background:#eff6ff;">
            <div class="card-body">
                <div>
                    <div class="stat-number" style="color:#2563eb;"><?= $totalCategories ?></div>
                    <div class="stat-label" style="color:#2563eb;">Total Categories</div>
                </div>
                <i class="bi bi-tags stat-icon" style="color:#2563eb;"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-0" style="background:#f0fdfa;">
            <div class="card-body">
                <div>
                    <div class="stat-number" style="color:#0d9488;"><?= number_format($totalCategorizedProducts) ?></div>
                    <div class="stat-label" style="color:#0d9488;">Categorized Items</div>
                </div>
                <i class="bi bi-box-seam stat-icon" style="color:#0d9488;"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-0" style="background:#f0fdf4;">
            <div class="card-body">
                <div>
                    <div class="stat-number text-truncate" style="color:#16a34a; max-width: 140px;" title="<?= htmlspecialchars($topCategoryName) ?>"><?= htmlspecialchars($topCategoryName) ?></div>
                    <div class="stat-label" style="color:#16a34a;">Top Category</div>
                </div>
                <i class="bi bi-award stat-icon" style="color:#16a34a;"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-0" style="background:#fffbeb;">
            <div class="card-body">
                <div>
                    <div class="stat-number" style="color:#d97706;"><?= $uncategorizedCount ?></div>
                    <div class="stat-label" style="color:#d97706;">Uncategorized</div>
                </div>
                <i class="bi bi-question-circle stat-icon" style="color:#d97706;"></i>
            </div>
        </div>
    </div>
</div>

<div class="content-card card">
    <div class="card-header flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="card-header-icon"><i class="bi bi-tags"></i></span>
            <div>
                <span class="fw-bold">All Categories</span>
                <span class="badge rounded-pill bg-light text-secondary border ms-2"><?= $totalCategories ?> total</span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="input-group input-group-sm" style="width: 220px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" class="form-control border-start-0 table-search-input" data-table="categories-table" placeholder="Filter categories...">
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()" title="Print table">
                <i class="bi bi-printer"></i>
            </button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="categories-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 45px;">#</th>
                    <th>Category Name</th>
                    <th class="text-center">Products Count</th>
                    <th>Created On</th>
                    <th class="text-center" style="width: 90px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="5" class="empty-state">
                            <i class="bi bi-tags"></i>
                            No categories yet. Click "Add Category" above.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php
                    $rowNumber = 1;
                    foreach ($categories as $category):
                    ?>
                    <tr>
                        <td class="text-center text-muted small"><?= $rowNumber++ ?></td>
                        <td>
                            <span class="fw-semibold text-dark"><?= htmlspecialchars($category['name']) ?></span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-primary-subtle text-primary fw-bold px-2 py-1">
                                <i class="bi bi-box-seam me-1"></i><?= $category['product_count'] ?> products
                            </span>
                        </td>
                        <td>
                            <span class="text-muted small"><i class="bi bi-calendar3 me-1"></i><?= date('d M Y', strtotime($category['created_at'])) ?></span>
                        </td>
                        <td class="text-center">
                            <div class="d-inline-flex gap-1">
                                <button class="btn btn-outline-primary btn-action-icon"
                                        title="Edit Category"
                                        onclick="openEditCategoryForm(<?= $category['id'] ?>, '<?= addslashes($category['name']) ?>')">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="categories.php" style="display:inline"
                                      onsubmit="return confirm('Delete category <?= addslashes(htmlspecialchars($category['name'])) ?>? Products in it will become uncategorized.')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_method" value="DELETE">
                                    <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-action-icon" title="Delete Category">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryModalLabel">
                    <i class="bi bi-tags me-2"></i>Add Category
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="categories.php" id="category-form" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="_method" id="cat-form-method" value="POST">
                <input type="hidden" name="category_id" id="cat-form-id" value="">
                <div class="modal-body">
                    <div id="cat-modal-error" class="alert alert-danger d-none"></div>
                    <div class="mb-3">
                        <label for="cat-name" class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="cat-name" name="name" required placeholder="e.g. Beverages">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gims-primary" id="cat-submit-btn">
                        <i class="bi bi-check-lg"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
