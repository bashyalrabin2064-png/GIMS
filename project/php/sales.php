<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db.php';
$pdo = get_db_connection();

define('VAT_RATE', 0.13);

$formError   = '';
$formSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['_action'] ?? 'record';

    if ($action === 'void' && $_SESSION['role'] === 'admin') {
        $saleId = (int)($_POST['sale_id'] ?? 0);
        if ($saleId > 0) {
            $saleStmt = $pdo->prepare("SELECT product_id, quantity, voided_at FROM sales WHERE id = ?");
            $saleStmt->execute([$saleId]);
            $sale = $saleStmt->fetch();

            if (!$sale) {
                $formError = 'Sale not found.';
            } elseif ($sale['voided_at'] !== null) {
                $formError = 'This sale has already been voided.';
            } else {
                $pdo->prepare("UPDATE sales SET voided_at = NOW(), voided_by = ? WHERE id = ?")
                    ->execute([$_SESSION['user_id'], $saleId]);
                $pdo->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?")
                    ->execute([$sale['quantity'], $sale['product_id']]);
                $formSuccess = 'Sale #' . $saleId . ' voided successfully. Stock restored.';
            }
        }
    } else {
        $productId    = (int)($_POST['product_id']    ?? 0);
        $quantity     = (int)($_POST['quantity']      ?? 0);
        $unitPrice    = (float)($_POST['unit_price']  ?? 0);
        $discount     = (float)($_POST['discount']    ?? 0);
        $discountType = in_array($_POST['discount_type'] ?? 'flat', ['flat','percent']) ? $_POST['discount_type'] : 'flat';
        $saleDate     = trim($_POST['sale_date'] ?? date('Y-m-d'));

        $parsedDate = DateTime::createFromFormat('Y-m-d', $saleDate);
        if (!$parsedDate) $saleDate = date('Y-m-d');
        else $saleDate = $parsedDate->format('Y-m-d');

        $discountAmt  = ($discountType === 'flat') ? max(0, $discount) : max(0, $unitPrice * $discount / 100);
        $effectivePrice = $unitPrice - $discountAmt;
        $taxableAmount  = max(0, $quantity * $effectivePrice);
        $vatAmount      = round($taxableAmount * VAT_RATE, 2);

        if ($productId === 0) {
            $formError = 'Please select a product.';
        } elseif ($quantity < 1) {
            $formError = 'Quantity must be at least 1.';
        } elseif ($unitPrice < 0) {
            $formError = 'Unit price cannot be negative.';
        } elseif ($effectivePrice < 0) {
            $formError = 'Discount cannot exceed the unit price.';
        } else {
            $stockStmt = $pdo->prepare("SELECT quantity, name, unit FROM products WHERE id = ?");
            $stockStmt->execute([$productId]);
            $productRow = $stockStmt->fetch();

            if (!$productRow) {
                $formError = 'Product not found.';
            } elseif ($productRow['quantity'] <= 0) {
                $formError = 'Product "' . htmlspecialchars($productRow['name']) . '" is completely out of stock (0 available). You cannot make a sale.';
            } elseif ($productRow['quantity'] < $quantity) {
                $formError = 'Not enough stock. Only ' . format_quantity($productRow['quantity'], $productRow['unit'] ?? 'pcs') . ' available of ' . htmlspecialchars($productRow['name']) . '.';
            } else {
                try {
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare(
                        "INSERT INTO sales (product_id, quantity, unit_price, discount, discount_type, vat_amount, sold_by, sale_date)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                    $stmt->execute([
                        $productId,
                        $quantity,
                        $unitPrice,
                        $discountAmt,
                        $discountType,
                        $vatAmount,
                        $_SESSION['user_id'],
                        $saleDate
                    ]);
                    $updateStock = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?");
                    $updateStock->execute([$quantity, $productId, $quantity]);

                    if ($updateStock->rowCount() === 0) {
                        $pdo->rollBack();
                        $formError = 'Stock error: Cannot complete sale because insufficient inventory remains.';
                    } else {
                        $pdo->commit();
                        $netTotal = $taxableAmount + $vatAmount;
                        $formSuccess = 'Sale recorded. Net Total: NRs. ' . number_format($netTotal, 2) . ' (incl. 13% VAT: NRs. ' . number_format($vatAmount, 2) . '). Stock updated.';
                    }
                } catch (PDOException $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $formError = 'Database error: ' . $e->getMessage();
                }
            }
        }
    }
}

$filterFrom = trim($_GET['from'] ?? '');
$filterTo   = trim($_GET['to']   ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 25;

if ($filterFrom && !DateTime::createFromFormat('Y-m-d', $filterFrom)) $filterFrom = '';
if ($filterTo   && !DateTime::createFromFormat('Y-m-d', $filterTo))   $filterTo   = '';

$baseSql     = "FROM sales sa
                LEFT JOIN products p ON p.id = sa.product_id
                LEFT JOIN users    u ON u.id = sa.sold_by
                WHERE 1=1";
$queryParams = [];

if (!empty($filterFrom)) {
    $baseSql      .= " AND DATE(sa.sale_date) >= ?";
    $queryParams[] = $filterFrom;
}
if (!empty($filterTo)) {
    $baseSql      .= " AND DATE(sa.sale_date) <= ?";
    $queryParams[] = $filterTo;
}

$countStmt = $pdo->prepare("SELECT COUNT(*) " . $baseSql);
$countStmt->execute($queryParams);
$totalSales = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalSales / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$sql = "SELECT sa.id, sa.quantity, sa.unit_price, sa.discount, sa.discount_type, sa.vat_amount, sa.sale_date, sa.voided_at,
               p.name      AS product_name,
               p.unit      AS product_unit,
               u.full_name AS sold_by_name ";
$sql .= $baseSql . " ORDER BY sa.sale_date DESC, sa.id DESC LIMIT $perPage OFFSET $offset";

$stmt  = $pdo->prepare($sql);
$stmt->execute($queryParams);
$sales = $stmt->fetchAll();

$productStmt = $pdo->query("SELECT id, name, quantity, sell_price, unit FROM products ORDER BY name");
$products    = $productStmt->fetchAll();

$statsTotalRevenue = (float)$pdo->query("SELECT COALESCE(SUM((quantity * unit_price - discount) + vat_amount), 0) FROM sales WHERE voided_at IS NULL")->fetchColumn();
$statsActiveSales  = (int)$pdo->query("SELECT COUNT(*) FROM sales WHERE voided_at IS NULL")->fetchColumn();
$statsUnitsSold    = (int)$pdo->query("SELECT COALESCE(SUM(quantity), 0) FROM sales WHERE voided_at IS NULL")->fetchColumn();
$statsTotalVat     = (float)$pdo->query("SELECT COALESCE(SUM(vat_amount), 0) FROM sales WHERE voided_at IS NULL")->fetchColumn();
$statsTodayRevenue = (float)$pdo->query("SELECT COALESCE(SUM((quantity * unit_price - discount) + vat_amount), 0) FROM sales WHERE DATE(sale_date) = CURDATE() AND voided_at IS NULL")->fetchColumn();

$page_title  = 'Sales';
$active_page = 'sales';
require_once __DIR__ . '/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-receipt"></i> Sales (Stock Out)
        <span class="badge rounded-pill bg-light text-secondary border ms-2"><?= number_format($totalSales) ?> recorded</span>
    </h2>
    <button class="btn btn-gims-primary" data-bs-toggle="modal" data-bs-target="#saleModal">
        <i class="bi bi-plus-lg"></i> Record Sale
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
        <div class="card stat-card h-100 border-0" style="background:#f0fdf4;">
            <div class="card-body">
                <div>
                    <div class="stat-number" style="color:#16a34a;">NRs. <?= number_format($statsTotalRevenue, 0) ?></div>
                    <div class="stat-label" style="color:#16a34a;">Total Revenue (incl. VAT)</div>
                </div>
                <i class="bi bi-cash-stack stat-icon" style="color:#16a34a;"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-0" style="background:#eff6ff;">
            <div class="card-body">
                <div>
                    <div class="stat-number" style="color:#2563eb;"><?= number_format($statsActiveSales) ?></div>
                    <div class="stat-label" style="color:#2563eb;">Completed Sales</div>
                </div>
                <i class="bi bi-receipt stat-icon" style="color:#2563eb;"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-0" style="background:#f0fdfa;">
            <div class="card-body">
                <div>
                    <div class="stat-number" style="color:#0d9488;"><?= number_format($statsUnitsSold) ?></div>
                    <div class="stat-label" style="color:#0d9488;">Units Sold</div>
                </div>
                <i class="bi bi-bag-check stat-icon" style="color:#0d9488;"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-0" style="background:#fffbeb;">
            <div class="card-body">
                <div>
                    <div class="stat-number" style="color:#d97706;">NRs. <?= number_format($statsTotalVat, 0) ?></div>
                    <div class="stat-label" style="color:#d97706;">Total VAT Collected</div>
                </div>
                <i class="bi bi-percent stat-icon" style="color:#d97706;"></i>
            </div>
        </div>
    </div>
</div>

<form method="GET" action="sales.php" class="filter-bar">
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
        <?php if (!empty($filterFrom) || !empty($filterTo)): ?>
        <a href="sales.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-x"></i> Clear
        </a>
        <?php endif; ?>
    </div>
</form>

<div class="content-card card">
    <div class="card-header flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="card-header-icon"><i class="bi bi-receipt"></i></span>
            <div>
                <span class="fw-bold">Sales History</span>
                <span class="badge rounded-pill bg-light text-secondary border ms-2"><?= number_format($totalSales) ?> records</span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="input-group input-group-sm" style="width: 220px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" class="form-control border-start-0 table-search-input" data-table="sales-table" placeholder="Filter sales...">
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()" title="Print table">
                <i class="bi bi-printer"></i>
            </button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="sales-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 45px;">#</th>
                    <th>Date</th>
                    <th>Product</th>
                    <th class="text-center">Qty Sold</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">Discount</th>
                    <th class="text-end">VAT (13%)</th>
                    <th class="text-end">Net Total</th>
                    <th>Recorded By</th>
                    <th class="text-center">Status</th>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                    <th class="text-center" style="width: 80px;">Action</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sales)): ?>
                    <tr>
                        <td colspan="<?= $_SESSION['role'] === 'admin' ? 11 : 10 ?>" class="empty-state">
                            <i class="bi bi-receipt"></i>
                            No sales records found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php
                    $rowNumber  = ($page - 1) * $perPage + 1;
                    $grandTotal = 0;
                    foreach ($sales as $sale):
                        $subtotal    = $sale['quantity'] * $sale['unit_price'];
                        $discAmt     = (float)$sale['discount'];
                        $vatAmt      = (float)$sale['vat_amount'];
                        $netTotal    = ($subtotal - $discAmt) + $vatAmt;
                        $isVoided    = $sale['voided_at'] !== null;
                        if (!$isVoided) $grandTotal += $netTotal;
                    ?>
                    <tr class="<?= $isVoided ? 'table-voided' : '' ?>">
                        <td class="text-center text-muted small"><?= $rowNumber++ ?></td>
                        <td>
                            <span class="text-muted small"><i class="bi bi-calendar3 me-1"></i><?= date('d M Y', strtotime($sale['sale_date'])) ?></span>
                        </td>
                        <td class="fw-semibold text-dark"><?= htmlspecialchars($sale['product_name'] ?? '—') ?></td>
                        <td class="text-center fw-bold" style="color:#2563eb;">
                            <?= format_quantity($sale['quantity'], $sale['product_unit'] ?? 'pcs') ?>
                        </td>
                        <td class="text-end text-muted">
                            NRs. <?= number_format($sale['unit_price'], 2) ?>
                            <span class="text-muted small" style="font-size:0.75rem;">/ <?= htmlspecialchars($sale['product_unit'] ?? 'pcs') ?></span>
                        </td>
                        <td class="text-end">
                            <?php if ($discAmt > 0): ?>
                                <span class="text-danger small">-NRs. <?= number_format($discAmt, 2) ?></span>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <span class="text-warning small">NRs. <?= number_format($vatAmt, 2) ?></span>
                        </td>
                        <td class="text-end fw-bold text-dark">NRs. <?= number_format($netTotal, 2) ?></td>
                        <td>
                            <span class="text-muted"><i class="bi bi-person me-1"></i><?= htmlspecialchars($sale['sold_by_name'] ?? '—') ?></span>
                        </td>
                        <td class="text-center">
                            <?php if ($isVoided): ?>
                                <span class="badge-voided"><i class="bi bi-slash-circle"></i> Voided</span>
                            <?php else: ?>
                                <span class="badge-stock-ok"><i class="bi bi-check-circle"></i> Completed</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                        <td class="text-center">
                            <?php if (!$isVoided): ?>
                            <form method="POST" action="sales.php" style="display:inline"
                                  onsubmit="return confirm('Void this sale? Stock will be restored.')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="_action" value="void">
                                <input type="hidden" name="sale_id" value="<?= $sale['id'] ?>">
                                <button type="submit" class="btn btn-outline-warning btn-action-icon" title="Void Sale">
                                    <i class="bi bi-slash-circle"></i>
                                </button>
                            </form>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="table-totals-row">
                        <td colspan="7" class="text-end fw-bold text-uppercase small text-muted">
                            <i class="bi bi-calculator me-1"></i> Page Active Total:
                        </td>
                        <td class="text-end fw-bold text-success">NRs. <?= number_format($grandTotal, 2) ?></td>
                        <td colspan="<?= $_SESSION['role'] === 'admin' ? 3 : 2 ?>"></td>
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

<div class="modal fade" id="saleModal" tabindex="-1" aria-labelledby="saleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="saleModalLabel">
                    <i class="bi bi-receipt me-2"></i>Record Sale
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="sales.php" id="sale-form" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="_action" value="record">
                <div class="modal-body">
                    <div id="sale-modal-error" class="alert alert-danger d-none"></div>

                    <div class="mb-3">
                        <label for="sale-product" class="form-label fw-semibold">Product <span class="text-danger">*</span></label>
                        <select class="form-select" id="sale-product" name="product_id" required>
                            <option value="">— Select Product —</option>
                            <?php foreach ($products as $prod):
                                $isOutOfStock = $prod['quantity'] <= 0;
                                $prodUnit = $prod['unit'] ?? 'pcs';
                            ?>
                                <option value="<?= $prod['id'] ?>"
                                        data-stock="<?= $prod['quantity'] ?>"
                                        data-price="<?= $prod['sell_price'] ?>"
                                        data-unit="<?= htmlspecialchars($prodUnit) ?>"
                                        data-cost="0"
                                        <?= $isOutOfStock ? 'class="text-danger"' : '' ?>>
                                    <?= htmlspecialchars($prod['name']) ?>
                                    <?= $isOutOfStock ? '— [OUT OF STOCK: 0 ' . htmlspecialchars($prodUnit) . ']' : '(' . format_quantity($prod['quantity'], $prodUnit) . ' available)' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="sale-stock-info" class="alert d-none small py-2">
                        <span id="sale-stock-text">Available in inventory: <strong id="sale-stock-value">0</strong> <span id="sale-stock-unit">pcs</span></span>
                    </div>

                    <div class="row g-2">
                        <div class="col-6 mb-3">
                            <label for="sale-qty" class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="sale-qty"
                                       name="quantity" min="1" required value="1">
                                <span class="input-group-text bg-light" id="sale-qty-unit">pcs</span>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <label for="sale-price" class="form-label fw-semibold">Unit Price (NRs.)</label>
                            <input type="number" class="form-control" id="sale-price"
                                   name="unit_price" min="0" step="0.01" value="0">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="sale-discount" class="form-label fw-semibold">Discount</label>
                            <input type="number" class="form-control" id="sale-discount"
                                   name="discount" min="0" step="0.01" value="0" placeholder="0">
                        </div>
                        <div class="col-6">
                            <label for="sale-discount-type" class="form-label fw-semibold">Discount Type</label>
                            <select class="form-select" id="sale-discount-type" name="discount_type">
                                <option value="flat">Flat Amount (NRs.)</option>
                                <option value="percent">Percentage (%)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="sale-date" class="form-label fw-semibold">Sale Date</label>
                        <input type="date" class="form-control" id="sale-date"
                               name="sale_date" value="<?= date('Y-m-d') ?>">
                    </div>

                    <div id="sale-loss-warning" class="alert alert-danger d-none py-2 small">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        <strong>Selling below cost price!</strong> <span id="sale-loss-detail"></span>
                    </div>

                    <div class="card border-0" style="background:#f8fafc;">
                        <div class="card-body py-2 px-3">
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>Subtotal:</span>
                                <span id="sale-preview-subtotal">NRs. 0.00</span>
                            </div>
                            <div class="d-flex justify-content-between small text-danger mb-1">
                                <span>Discount:</span>
                                <span id="sale-preview-discount">- NRs. 0.00</span>
                            </div>
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>Taxable Amount:</span>
                                <span id="sale-preview-taxable">NRs. 0.00</span>
                            </div>
                            <div class="d-flex justify-content-between small text-warning mb-2">
                                <span>VAT (13%):</span>
                                <span id="sale-preview-vat">+ NRs. 0.00</span>
                            </div>
                            <div class="d-flex justify-content-between fw-bold border-top pt-2">
                                <span>Net Total:</span>
                                <strong class="fs-6 text-primary" id="sale-total-value">NRs. 0.00</strong>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gims-primary" id="sale-submit-btn">
                        <i class="bi bi-check-lg"></i> Complete Sale
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
