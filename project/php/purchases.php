<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db.php';
$pdo = get_db_connection();

$formError   = '';
$formSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $productId    = (int)($_POST['product_id']  ?? 0);
    $supplierId   = (int)($_POST['supplier_id'] ?? 0) ?: null;
    $quantity     = (int)($_POST['quantity']    ?? 0);
    $unitCost     = (float)($_POST['unit_cost'] ?? 0);
    $purchaseDate = trim($_POST['purchase_date'] ?? date('Y-m-d'));

    $parsedDate = DateTime::createFromFormat('Y-m-d', $purchaseDate);
    if (!$parsedDate) $purchaseDate = date('Y-m-d');
    else $purchaseDate = $parsedDate->format('Y-m-d');

    if ($productId === 0) {
        $formError = 'Please select a product.';
    } elseif ($quantity < 1) {
        $formError = 'Quantity must be at least 1.';
    } elseif ($unitCost < 0) {
        $formError = 'Unit cost cannot be negative.';
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO purchases (product_id, supplier_id, quantity, unit_cost, purchased_by, purchase_date)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $productId,
                $supplierId,
                $quantity,
                $unitCost,
                $_SESSION['user_id'],
                $purchaseDate
            ]);
            $updateStock = $pdo->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
            $updateStock->execute([$quantity, $productId]);
            $pdo->commit();
            $formSuccess = 'Purchase recorded successfully. Stock updated.';
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $formError = 'Database error: ' . $e->getMessage();
        }
    }
}

$filterSupplierId = (int)($_GET['supplier_id'] ?? 0);
$filterFrom       = trim($_GET['from'] ?? '');
$filterTo         = trim($_GET['to']   ?? '');
$page             = max(1, (int)($_GET['page'] ?? 1));
$perPage          = 25;

if ($filterFrom && !DateTime::createFromFormat('Y-m-d', $filterFrom)) $filterFrom = '';
if ($filterTo   && !DateTime::createFromFormat('Y-m-d', $filterTo))   $filterTo   = '';

$baseSql     = "FROM purchases pu
                LEFT JOIN products  p ON p.id = pu.product_id
                LEFT JOIN suppliers s ON s.id = pu.supplier_id
                LEFT JOIN users     u ON u.id = pu.purchased_by
                WHERE 1=1";
$queryParams = [];

if ($filterSupplierId > 0) {
    $baseSql      .= " AND pu.supplier_id = ?";
    $queryParams[] = $filterSupplierId;
}
if (!empty($filterFrom)) {
    $baseSql      .= " AND DATE(pu.purchase_date) >= ?";
    $queryParams[] = $filterFrom;
}
if (!empty($filterTo)) {
    $baseSql      .= " AND DATE(pu.purchase_date) <= ?";
    $queryParams[] = $filterTo;
}

$countStmt = $pdo->prepare("SELECT COUNT(*) " . $baseSql);
$countStmt->execute($queryParams);
$totalPurchases = (int)$countStmt->fetchColumn();
$totalPages     = max(1, (int)ceil($totalPurchases / $perPage));
$page           = min($page, $totalPages);
$offset         = ($page - 1) * $perPage;

$sql  = "SELECT pu.id, pu.quantity, pu.unit_cost, pu.purchase_date,
                p.name  AS product_name,
                p.unit  AS product_unit,
                s.name  AS supplier_name,
                u.full_name AS purchased_by_name ";
$sql .= $baseSql . " ORDER BY pu.purchase_date DESC, pu.id DESC LIMIT $perPage OFFSET $offset";

$stmt      = $pdo->prepare($sql);
$stmt->execute($queryParams);
$purchases = $stmt->fetchAll();

$productStmt  = $pdo->query("SELECT id, name, quantity, unit FROM products ORDER BY name");
$products     = $productStmt->fetchAll();

$supplierStmt = $pdo->query("SELECT id, name FROM suppliers ORDER BY name");
$suppliers    = $supplierStmt->fetchAll();

$statsTotalSpent = (float)$pdo->query("SELECT COALESCE(SUM(quantity * unit_cost), 0) FROM purchases")->fetchColumn();
$statsTotalUnits = (int)$pdo->query("SELECT COALESCE(SUM(quantity), 0) FROM purchases")->fetchColumn();
$statsTodaySpent = (float)$pdo->query("SELECT COALESCE(SUM(quantity * unit_cost), 0) FROM purchases WHERE DATE(purchase_date) = CURDATE()")->fetchColumn();

$page_title  = 'Purchases';
$active_page = 'purchases';
require_once __DIR__ . '/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-cart-plus"></i> Purchases (Stock In)
        <span class="badge rounded-pill bg-light text-secondary border ms-2"><?= number_format($totalPurchases) ?> records</span>
    </h2>
    <button class="btn btn-gims-primary" data-bs-toggle="modal" data-bs-target="#purchaseModal">
        <i class="bi bi-plus-lg"></i> Record Purchase
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
                    <div class="stat-number" style="color:#2563eb;"><?= number_format($totalPurchases) ?></div>
                    <div class="stat-label" style="color:#2563eb;">Total Purchases</div>
                </div>
                <i class="bi bi-cart-check stat-icon" style="color:#2563eb;"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-0" style="background:#f0fdfa;">
            <div class="card-body">
                <div>
                    <div class="stat-number" style="color:#0d9488;">NRs. <?= number_format($statsTotalSpent, 0) ?></div>
                    <div class="stat-label" style="color:#0d9488;">Total Expenditure</div>
                </div>
                <i class="bi bi-wallet2 stat-icon" style="color:#0d9488;"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-0" style="background:#f0fdf4;">
            <div class="card-body">
                <div>
                    <div class="stat-number" style="color:#16a34a;"><?= number_format($statsTotalUnits) ?></div>
                    <div class="stat-label" style="color:#16a34a;">Units Received</div>
                </div>
                <i class="bi bi-box-arrow-in-down stat-icon" style="color:#16a34a;"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-0" style="background:#fffbeb;">
            <div class="card-body">
                <div>
                    <div class="stat-number" style="color:#d97706;">NRs. <?= number_format($statsTodaySpent, 0) ?></div>
                    <div class="stat-label" style="color:#d97706;">Purchases Today</div>
                </div>
                <i class="bi bi-calendar-event stat-icon" style="color:#d97706;"></i>
            </div>
        </div>
    </div>
</div>

<form method="GET" action="purchases.php" class="filter-bar">
    <div>
        <label>Supplier</label>
        <select name="supplier_id" class="form-select form-select-sm" style="min-width:170px">
            <option value="0">All Suppliers</option>
            <?php foreach ($suppliers as $sup): ?>
                <option value="<?= $sup['id'] ?>" <?= $filterSupplierId === (int)$sup['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($sup['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label>From Date</label>
        <input type="date" name="from" class="form-control form-control-sm"
               value="<?= htmlspecialchars($filterFrom) ?>">
    </div>
    <div>
        <label>To Date</label>
        <input type="date" name="to" class="form-control form-control-sm"
               value="<?= htmlspecialchars($filterTo) ?>">
    </div>
    <div class="d-flex align-items-end gap-1">
        <button type="submit" class="btn btn-gims-primary btn-sm">
            <i class="bi bi-funnel"></i> Filter
        </button>
        <?php if ($filterSupplierId > 0 || !empty($filterFrom) || !empty($filterTo)): ?>
        <a href="purchases.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-x"></i> Clear
        </a>
        <?php endif; ?>
    </div>
</form>

<div class="content-card card">
    <div class="card-header flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="card-header-icon"><i class="bi bi-cart-plus"></i></span>
            <div>
                <span class="fw-bold">Purchase Logs</span>
                <span class="badge rounded-pill bg-light text-secondary border ms-2"><?= number_format($totalPurchases) ?> records</span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="input-group input-group-sm" style="width: 220px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" class="form-control border-start-0 table-search-input" data-table="purchases-table" placeholder="Filter purchases...">
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()" title="Print table">
                <i class="bi bi-printer"></i>
            </button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="purchases-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 45px;">#</th>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Supplier</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Unit Cost</th>
                    <th class="text-end">Total Cost</th>
                    <th>Recorded By</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($purchases)): ?>
                    <tr>
                        <td colspan="8" class="empty-state">
                            <i class="bi bi-cart"></i>
                            No purchase records found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php
                    $rowNumber  = ($page - 1) * $perPage + 1;
                    $pageTotal  = 0;
                    foreach ($purchases as $purchase):
                        $totalCost = $purchase['quantity'] * $purchase['unit_cost'];
                        $pageTotal += $totalCost;
                        $purchUnit = $purchase['product_unit'] ?? 'pcs';
                    ?>
                    <tr>
                        <td class="text-center text-muted small"><?= $rowNumber++ ?></td>
                        <td>
                            <span class="text-muted small"><i class="bi bi-calendar3 me-1"></i><?= date('d M Y', strtotime($purchase['purchase_date'])) ?></span>
                        </td>
                        <td class="fw-semibold text-dark"><?= htmlspecialchars($purchase['product_name'] ?? '—') ?></td>
                        <td>
                            <span class="badge-category"><?= htmlspecialchars($purchase['supplier_name'] ?? 'Direct') ?></span>
                        </td>
                        <td class="text-center fw-bold" style="color:#0d9488;">
                            <?= format_quantity($purchase['quantity'], $purchUnit) ?>
                        </td>
                        <td class="text-end text-muted">
                            NRs. <?= number_format($purchase['unit_cost'], 2) ?>
                            <span class="text-muted small" style="font-size:0.75rem;">/ <?= htmlspecialchars($purchUnit) ?></span>
                        </td>
                        <td class="text-end fw-bold text-dark">NRs. <?= number_format($totalCost, 2) ?></td>
                        <td>
                            <span class="text-muted"><i class="bi bi-person me-1"></i><?= htmlspecialchars($purchase['purchased_by_name'] ?? '—') ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="table-totals-row">
                        <td colspan="6" class="text-end fw-bold text-uppercase small text-muted">
                            <i class="bi bi-calculator me-1"></i> Page Total Cost:
                        </td>
                        <td class="text-end fw-bold text-dark">NRs. <?= number_format($pageTotal, 2) ?></td>
                        <td></td>
                    </tr>
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

<div class="modal fade" id="purchaseModal" tabindex="-1" aria-labelledby="purchaseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="purchaseModalLabel">
                    <i class="bi bi-cart-plus me-2"></i>Record Purchase
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="purchases.php" id="purchase-form" novalidate>
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div id="purchase-modal-error" class="alert alert-danger d-none"></div>

                    <div class="mb-3">
                        <label for="purch-product" class="form-label fw-semibold">Product <span class="text-danger">*</span></label>
                        <select class="form-select" id="purch-product" name="product_id" required>
                            <option value="">— Select Product —</option>
                            <?php foreach ($products as $prod):
                                $prodUnit = $prod['unit'] ?? 'pcs';
                            ?>
                                <option value="<?= $prod['id'] ?>"
                                        data-stock="<?= $prod['quantity'] ?>"
                                        data-unit="<?= htmlspecialchars($prodUnit) ?>">
                                    <?= htmlspecialchars($prod['name']) ?>
                                    (Stock: <?= format_quantity($prod['quantity'], $prodUnit) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="current-stock-info" class="alert alert-info d-none small py-2">
                        Current stock: <strong id="current-stock-value">0</strong> <span id="current-stock-unit">pcs</span>
                    </div>

                    <div class="mb-3">
                        <label for="purch-supplier" class="form-label fw-semibold">Supplier</label>
                        <select class="form-select" id="purch-supplier" name="supplier_id">
                            <option value="0">— Direct Purchase / No Supplier —</option>
                            <?php foreach ($suppliers as $sup): ?>
                                <option value="<?= $sup['id'] ?>"><?= htmlspecialchars($sup['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2">
                        <div class="col-6 mb-3">
                            <label for="purch-qty" class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="purch-qty"
                                       name="quantity" min="1" required value="1">
                                <span class="input-group-text bg-light" id="purch-qty-unit">pcs</span>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <label for="purch-cost" class="form-label fw-semibold">Unit Cost (NRs.)</label>
                            <input type="number" class="form-control" id="purch-cost"
                                   name="unit_cost" min="0" step="0.01" value="0">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="purch-date" class="form-label fw-semibold">Purchase Date</label>
                        <input type="date" class="form-control" id="purch-date"
                               name="purchase_date" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gims-primary">
                        <i class="bi bi-check-lg"></i> Record Purchase
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
