<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db.php';
$pdo = get_db_connection();

$formError   = '';
$formSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $method = $_POST['_method'] ?? 'POST';

    if ($method === 'POST' && $_SESSION['role'] === 'admin') {
        $name        = trim($_POST['name'] ?? '');
        $categoryId  = (int)($_POST['category_id'] ?? 0) ?: null;
        $supplierId  = (int)($_POST['supplier_id'] ?? 0) ?: null;
        $unit        = trim($_POST['unit'] ?? 'pcs') ?: 'pcs';
        $costPrice   = (float)($_POST['cost_price'] ?? 0);
        $sellPrice   = (float)($_POST['sell_price'] ?? 0);
        $quantity    = (int)($_POST['quantity'] ?? 0);
        $minQuantity = (int)($_POST['min_quantity'] ?? 5);
        $sku         = trim($_POST['sku'] ?? '') ?: null;

        if (empty($name)) {
            $formError = 'Product name is required.';
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO products
                    (name, sku, category_id, supplier_id, unit, cost_price, sell_price, quantity, min_quantity)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$name, $sku, $categoryId, $supplierId, $unit, $costPrice, $sellPrice, $quantity, $minQuantity]);
            $formSuccess = 'Product "' . htmlspecialchars($name) . '" added successfully.';
        }
    }

    if ($method === 'PUT' && $_SESSION['role'] === 'admin') {
        $productId   = (int)($_POST['product_id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $categoryId  = (int)($_POST['category_id'] ?? 0) ?: null;
        $supplierId  = (int)($_POST['supplier_id'] ?? 0) ?: null;
        $unit        = trim($_POST['unit'] ?? 'pcs') ?: 'pcs';
        $costPrice   = (float)($_POST['cost_price'] ?? 0);
        $sellPrice   = (float)($_POST['sell_price'] ?? 0);
        $minQuantity = (int)($_POST['min_quantity'] ?? 5);
        $sku         = trim($_POST['sku'] ?? '') ?: null;

        if (empty($name) || $productId === 0) {
            $formError = 'Product name is required.';
        } else {
            $stmt = $pdo->prepare(
                "UPDATE products
                 SET name=?, sku=?, category_id=?, supplier_id=?, unit=?, cost_price=?, sell_price=?, min_quantity=?
                 WHERE id=?"
            );
            $stmt->execute([$name, $sku, $categoryId, $supplierId, $unit, $costPrice, $sellPrice, $minQuantity, $productId]);
            $formSuccess = 'Product updated successfully.';
        }
    }

    if ($method === 'DELETE' && $_SESSION['role'] === 'admin') {
        $productId = (int)($_POST['product_id'] ?? 0);
        if ($productId > 0) {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $formSuccess = 'Product deleted successfully.';
        }
    }
}

$searchTerm  = trim($_GET['search'] ?? '');
$filterCatId = (int)($_GET['category_id'] ?? 0);
$page        = max(1, (int)($_GET['page'] ?? 1));
$perPage     = 20;

$baseSql     = "FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN suppliers  s ON s.id = p.supplier_id
                WHERE 1=1";
$queryParams = [];

if (!empty($searchTerm)) {
    $baseSql      .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
    $queryParams[] = '%' . $searchTerm . '%';
    $queryParams[] = '%' . $searchTerm . '%';
}
if ($filterCatId > 0) {
    $baseSql      .= " AND p.category_id = ?";
    $queryParams[] = $filterCatId;
}

$countStmt = $pdo->prepare("SELECT COUNT(*) " . $baseSql);
$countStmt->execute($queryParams);
$totalProducts = (int)$countStmt->fetchColumn();
$totalPages    = max(1, (int)ceil($totalProducts / $perPage));
$page          = min($page, $totalPages);
$offset        = ($page - 1) * $perPage;

$sql  = "SELECT p.id, p.name, p.sku, p.category_id, p.supplier_id, p.unit,
                p.cost_price, p.sell_price, p.quantity, p.min_quantity,
                c.name AS category_name,
                s.name AS supplier_name ";
$sql .= $baseSql . " ORDER BY p.name ASC LIMIT $perPage OFFSET $offset";

$stmt     = $pdo->prepare($sql);
$stmt->execute($queryParams);
$products = $stmt->fetchAll();

$categoryStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
$categories   = $categoryStmt->fetchAll();

$supplierStmt = $pdo->query("SELECT id, name FROM suppliers ORDER BY name");
$suppliers    = $supplierStmt->fetchAll();

$statsTotalProd  = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$statsTotalStock = (int)$pdo->query("SELECT COALESCE(SUM(quantity), 0) FROM products")->fetchColumn();
$statsTotalValue = (float)$pdo->query("SELECT COALESCE(SUM(quantity * sell_price), 0) FROM products")->fetchColumn();
$statsLowStock   = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE quantity < min_quantity")->fetchColumn();

$page_title  = 'Products';
$active_page = 'products';
require_once __DIR__ . '/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-box-seam"></i> Products
        <span class="badge rounded-pill bg-light text-secondary border ms-2"><?= number_format($totalProducts) ?> total</span>
    </h2>
    <?php if ($_SESSION['role'] === 'admin'): ?>
    <button class="btn btn-gims-primary"
            data-bs-toggle="modal" data-bs-target="#productModal"
            id="btn-add-product"
            onclick="openAddProductForm()">
        <i class="bi bi-plus-lg"></i> Add Product
    </button>
    <?php endif; ?>
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
                    <div class="stat-number" style="color:#2563eb;"><?= number_format($statsTotalProd) ?></div>
                    <div class="stat-label" style="color:#2563eb;">Total Products</div>
                </div>
                <i class="bi bi-box-seam stat-icon" style="color:#2563eb;"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-0" style="background:#f0fdfa;">
            <div class="card-body">
                <div>
                    <div class="stat-number" style="color:#0d9488;"><?= number_format($statsTotalStock) ?></div>
                    <div class="stat-label" style="color:#0d9488;">Total Stock Count</div>
                </div>
                <i class="bi bi-boxes stat-icon" style="color:#0d9488;"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-0" style="background:#f0fdf4;">
            <div class="card-body">
                <div>
                    <div class="stat-number" style="color:#16a34a;">NRs. <?= number_format($statsTotalValue, 0) ?></div>
                    <div class="stat-label" style="color:#16a34a;">Inventory Value</div>
                </div>
                <i class="bi bi-wallet2 stat-icon" style="color:#16a34a;"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-0" style="background:<?= $statsLowStock > 0 ? '#fef2f2' : '#f8fafc' ?>;">
            <div class="card-body">
                <div>
                    <div class="stat-number" style="color:<?= $statsLowStock > 0 ? '#dc2626' : '#64748b' ?>;"><?= $statsLowStock ?></div>
                    <div class="stat-label" style="color:<?= $statsLowStock > 0 ? '#dc2626' : '#64748b' ?>;">Low Stock Items</div>
                </div>
                <i class="bi bi-exclamation-triangle stat-icon" style="color:<?= $statsLowStock > 0 ? '#dc2626' : '#94a3b8' ?>;"></i>
            </div>
        </div>
    </div>
</div>

<form method="GET" action="products.php" class="filter-bar" id="filter-form">
    <div>
        <label>Search Query</label>
        <input type="text"
               name="search"
               id="search-input"
               class="form-control form-control-sm"
               placeholder="Search product or SKU..."
               value="<?= htmlspecialchars($searchTerm) ?>"
               style="min-width:220px">
    </div>
    <div>
        <label>Category</label>
        <select name="category_id" id="category-filter" class="form-select form-select-sm" style="min-width:170px">
            <option value="0">All Categories</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= $category['id'] ?>"
                    <?= $filterCatId === (int)$category['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($category['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="d-flex align-items-end gap-1">
        <button type="submit" class="btn btn-gims-primary btn-sm">
            <i class="bi bi-funnel"></i> Apply
        </button>
        <?php if (!empty($searchTerm) || $filterCatId > 0): ?>
        <a href="products.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-x"></i> Clear
        </a>
        <?php endif; ?>
    </div>
</form>

<div class="content-card card">
    <div class="card-header flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="card-header-icon"><i class="bi bi-box-seam"></i></span>
            <div>
                <span class="fw-bold">Product Inventory</span>
                <span class="badge rounded-pill bg-light text-secondary border ms-2"><?= number_format($totalProducts) ?> products</span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="input-group input-group-sm" style="width: 220px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" class="form-control border-start-0 table-search-input" data-table="products-table" placeholder="Filter list...">
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()" title="Print table">
                <i class="bi bi-printer"></i>
            </button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="products-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 45px;">#</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Supplier</th>
                    <th class="text-center" style="width: 80px;">Unit</th>
                    <th class="text-end">Cost Price</th>
                    <th class="text-end">Sell Price</th>
                    <th class="text-center">Stock Qty</th>
                    <th class="text-center">Min Alert</th>
                    <th class="text-center">Status</th>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                    <th class="text-center" style="width: 90px;">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="<?= $_SESSION['role'] === 'admin' ? 11 : 10 ?>" class="empty-state">
                            <i class="bi bi-box"></i>
                            No products found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php
                    $rowNumber = ($page - 1) * $perPage + 1;
                    foreach ($products as $product):
                        $isLowStock = $product['quantity'] < $product['min_quantity'];
                        $prodUnit = $product['unit'] ?? 'pcs';
                    ?>
                    <tr class="<?= $isLowStock ? 'row-low-stock' : '' ?>">
                        <td class="text-center text-muted small"><?= $rowNumber++ ?></td>
                        <td>
                            <div class="fw-semibold text-dark"><?= htmlspecialchars($product['name']) ?></div>
                            <?php if (!empty($product['sku'])): ?>
                            <span class="badge bg-light text-muted border font-monospace py-0 px-1" style="font-size:0.72rem;"><?= htmlspecialchars($product['sku']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge-category"><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></span>
                        </td>
                        <td>
                            <span class="text-muted"><?= htmlspecialchars($product['supplier_name'] ?? '—') ?></span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border font-monospace px-2 py-1"><?= htmlspecialchars($prodUnit) ?></span>
                        </td>
                        <td class="text-end text-muted">
                            NRs. <?= number_format($product['cost_price'], 2) ?>
                            <div class="text-muted" style="font-size:0.72rem;">per <?= htmlspecialchars($prodUnit) ?></div>
                        </td>
                        <td class="text-end fw-semibold text-dark">
                            NRs. <?= number_format($product['sell_price'], 2) ?>
                            <div class="text-muted fw-normal" style="font-size:0.72rem;">per <?= htmlspecialchars($prodUnit) ?></div>
                        </td>
                        <td class="text-center">
                            <span class="fw-bold <?= $isLowStock ? 'text-danger' : 'text-dark' ?>">
                                <?= format_quantity($product['quantity'], $prodUnit) ?>
                            </span>
                        </td>
                        <td class="text-center text-muted"><?= format_quantity($product['min_quantity'], $prodUnit) ?></td>
                        <td class="text-center">
                            <?php if ($isLowStock): ?>
                                <span class="badge-stock-low">
                                    <i class="bi bi-exclamation-triangle"></i> Low Stock
                                </span>
                            <?php else: ?>
                                <span class="badge-stock-ok">
                                    <i class="bi bi-check-circle"></i> In Stock
                                </span>
                            <?php endif; ?>
                        </td>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                        <td class="text-center">
                            <div class="d-inline-flex gap-1">
                                <button class="btn btn-outline-primary btn-action-icon"
                                        title="Edit Product"
                                        onclick="openEditProductForm(
                                            <?= $product['id'] ?>,
                                            '<?= addslashes($product['name']) ?>',
                                            '<?= addslashes($product['sku'] ?? '') ?>',
                                            <?= (int)($product['category_id'] ?? 0) ?>,
                                            <?= (int)($product['supplier_id'] ?? 0) ?>,
                                            '<?= addslashes($prodUnit) ?>',
                                            <?= $product['cost_price'] ?>,
                                            <?= $product['sell_price'] ?>,
                                            <?= $product['min_quantity'] ?>
                                        )">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="products.php"
                                      style="display:inline"
                                      onsubmit="return confirm('Delete product <?= addslashes(htmlspecialchars($product['name'])) ?>? This cannot be undone.')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_method" value="DELETE">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-action-icon" title="Delete Product">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="card-footer bg-transparent">
        <nav>
            <ul class="pagination mb-0">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php if ($_SESSION['role'] === 'admin'): ?>
<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productModalLabel">
                    <i class="bi bi-box-seam me-2"></i>Add Product
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="products.php" id="product-form" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="_method" id="form-method" value="POST">
                <input type="hidden" name="product_id" id="form-product-id" value="">
                <div class="modal-body">
                    <div id="modal-error" class="alert alert-danger d-none"></div>

                    <div class="mb-3">
                        <label for="form-name" class="form-label fw-semibold">Product Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="form-name" name="name" required placeholder="e.g. Basmati Rice, Red Bull, Masoor Dal">
                    </div>

                    <div class="row g-2">
                        <div class="col-6 mb-3">
                            <label for="form-sku" class="form-label fw-semibold">SKU / Code <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="text" class="form-control" id="form-sku" name="sku" placeholder="e.g. RICE-001">
                        </div>
                        <div class="col-6 mb-3">
                            <label for="form-unit" class="form-label fw-semibold">Unit of Measure <span class="text-danger">*</span></label>
                            <select class="form-select" id="form-unit" name="unit" required>
                                <?php foreach (get_product_units() as $uKey => $uLabel): ?>
                                    <option value="<?= $uKey ?>" <?= $uKey === 'pcs' ? 'selected' : '' ?>><?= htmlspecialchars($uLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-6 mb-3">
                            <label for="form-category" class="form-label fw-semibold">Category</label>
                            <select class="form-select" id="form-category" name="category_id">
                                <option value="0">— Select Category —</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label for="form-supplier" class="form-label fw-semibold">Supplier</label>
                            <select class="form-select" id="form-supplier" name="supplier_id">
                                <option value="0">— Select Supplier —</option>
                                <?php foreach ($suppliers as $sup): ?>
                                    <option value="<?= $sup['id'] ?>"><?= htmlspecialchars($sup['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-6 mb-3">
                            <label for="form-cost" class="form-label fw-semibold">Cost Price (NRs.)</label>
                            <input type="number" class="form-control" id="form-cost" name="cost_price" min="0" step="0.01" value="0">
                        </div>
                        <div class="col-6 mb-3">
                            <label for="form-sell" class="form-label fw-semibold">Sell Price (NRs.)</label>
                            <input type="number" class="form-control" id="form-sell" name="sell_price" min="0" step="0.01" value="0">
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-6 mb-3" id="qty-field-wrapper">
                            <label for="form-qty" class="form-label fw-semibold">Opening Stock</label>
                            <input type="number" class="form-control" id="form-qty" name="quantity" min="0" value="0">
                        </div>
                        <div class="col-6 mb-3">
                            <label for="form-minqty" class="form-label fw-semibold">Min Qty Alert</label>
                            <input type="number" class="form-control" id="form-minqty" name="min_quantity" min="0" value="5">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gims-primary" id="form-submit-btn">
                        <i class="bi bi-check-lg"></i> Save Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
