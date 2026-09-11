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
        $name    = trim($_POST['name']    ?? '');
        $phone   = trim($_POST['phone']   ?? '');
        $email   = trim($_POST['email']   ?? '');
        $address = trim($_POST['address'] ?? '');

        if (empty($name)) {
            $formError = 'Supplier name is required.';
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO suppliers (name, phone, email, address) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$name, $phone, $email, $address]);
            $formSuccess = 'Supplier "' . htmlspecialchars($name) . '" added successfully.';
        }
    }

    if ($method === 'PUT' && $_SESSION['role'] === 'admin') {
        $supplierId = (int)($_POST['supplier_id'] ?? 0);
        $name       = trim($_POST['name']    ?? '');
        $phone      = trim($_POST['phone']   ?? '');
        $email      = trim($_POST['email']   ?? '');
        $address    = trim($_POST['address'] ?? '');

        if (empty($name) || $supplierId === 0) {
            $formError = 'Supplier name is required.';
        } else {
            $stmt = $pdo->prepare(
                "UPDATE suppliers SET name=?, phone=?, email=?, address=? WHERE id=?"
            );
            $stmt->execute([$name, $phone, $email, $address, $supplierId]);
            $formSuccess = 'Supplier updated successfully.';
        }
    }

    if ($method === 'DELETE' && $_SESSION['role'] === 'admin') {
        $supplierId = (int)($_POST['supplier_id'] ?? 0);
        if ($supplierId > 0) {
            $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
            $stmt->execute([$supplierId]);
            $formSuccess = 'Supplier deleted successfully.';
        }
    }
}

$stmt = $pdo->query(
    "SELECT s.id, s.name, s.phone, s.email, s.address, s.created_at,
            COUNT(p.id)          AS total_purchases,
            MAX(p.purchase_date) AS last_purchase_date
     FROM suppliers s
     LEFT JOIN purchases p ON p.supplier_id = s.id
     GROUP BY s.id, s.name, s.phone, s.email, s.address, s.created_at
     ORDER BY s.name ASC"
);
$suppliers = $stmt->fetchAll();

$statsTotalSuppliers = count($suppliers);
$statsActiveSuppliers = 0;
$statsTotalPurchasesCount = 0;

foreach ($suppliers as $sup) {
    $pCount = (int)$sup['total_purchases'];
    $statsTotalPurchasesCount += $pCount;
    if ($pCount > 0) {
        $statsActiveSuppliers++;
    }
}
$statsTotalSpentSuppliers = (float)$pdo->query("SELECT COALESCE(SUM(quantity * unit_cost), 0) FROM purchases WHERE supplier_id IS NOT NULL")->fetchColumn();

$page_title  = 'Suppliers';
$active_page = 'suppliers';
require_once __DIR__ . '/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-truck"></i> Suppliers
        <span class="badge rounded-pill bg-light text-secondary border ms-2"><?= $statsTotalSuppliers ?> total</span>
    </h2>
    <?php if ($_SESSION['role'] === 'admin'): ?>
    <button class="btn btn-gims-primary"
            data-bs-toggle="modal" data-bs-target="#supplierModal"
            onclick="openAddSupplierForm()">
        <i class="bi bi-plus-lg"></i> Add Supplier
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
                    <div class="stat-number" style="color:#2563eb;"><?= $statsTotalSuppliers ?></div>
                    <div class="stat-label" style="color:#2563eb;">Total Suppliers</div>
                </div>
                <i class="bi bi-truck stat-icon" style="color:#2563eb;"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-0" style="background:#f0fdfa;">
            <div class="card-body">
                <div>
                    <div class="stat-number" style="color:#0d9488;"><?= $statsActiveSuppliers ?></div>
                    <div class="stat-label" style="color:#0d9488;">Active Partners</div>
                </div>
                <i class="bi bi-person-check stat-icon" style="color:#0d9488;"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-0" style="background:#f0fdf4;">
            <div class="card-body">
                <div>
                    <div class="stat-number" style="color:#16a34a;"><?= number_format($statsTotalPurchasesCount) ?></div>
                    <div class="stat-label" style="color:#16a34a;">Orders Placed</div>
                </div>
                <i class="bi bi-cart-check stat-icon" style="color:#16a34a;"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-0" style="background:#fffbeb;">
            <div class="card-body">
                <div>
                    <div class="stat-number" style="color:#d97706;">NRs. <?= number_format($statsTotalSpentSuppliers, 0) ?></div>
                    <div class="stat-label" style="color:#d97706;">Total Paid</div>
                </div>
                <i class="bi bi-wallet2 stat-icon" style="color:#d97706;"></i>
            </div>
        </div>
    </div>
</div>

<div class="content-card card">
    <div class="card-header flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="card-header-icon"><i class="bi bi-truck"></i></span>
            <div>
                <span class="fw-bold">Suppliers Directory</span>
                <span class="badge rounded-pill bg-light text-secondary border ms-2"><?= $statsTotalSuppliers ?> partners</span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="input-group input-group-sm" style="width: 220px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" class="form-control border-start-0 table-search-input" data-table="suppliers-table" placeholder="Filter suppliers...">
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()" title="Print table">
                <i class="bi bi-printer"></i>
            </button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="suppliers-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 45px;">#</th>
                    <th>Supplier Name</th>
                    <th>Contact Phone</th>
                    <th>Email Address</th>
                    <th>Location / Address</th>
                    <th class="text-center">Orders</th>
                    <th>Last Order</th>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                    <th class="text-center" style="width: 90px;">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($suppliers)): ?>
                    <tr>
                        <td colspan="<?= $_SESSION['role'] === 'admin' ? 8 : 7 ?>" class="empty-state">
                            <i class="bi bi-truck"></i>
                            No suppliers found. Click "Add Supplier" above.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php
                    $rowNumber = 1;
                    foreach ($suppliers as $supplier):
                    ?>
                    <tr>
                        <td class="text-center text-muted small"><?= $rowNumber++ ?></td>
                        <td class="fw-semibold text-dark"><?= htmlspecialchars($supplier['name']) ?></td>
                        <td>
                            <?php if (!empty($supplier['phone'])): ?>
                                <span class="text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($supplier['phone']) ?></span>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($supplier['email'])): ?>
                                <span class="text-muted"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($supplier['email']) ?></span>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($supplier['address'])): ?>
                                <span class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($supplier['address']) ?></span>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-primary-subtle text-primary fw-bold px-2 py-1">
                                <?= $supplier['total_purchases'] ?> orders
                            </span>
                        </td>
                        <td>
                            <?php if ($supplier['last_purchase_date']): ?>
                                <span class="text-muted small"><i class="bi bi-calendar3 me-1"></i><?= date('d M Y', strtotime($supplier['last_purchase_date'])) ?></span>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                        <td class="text-center">
                            <div class="d-inline-flex gap-1">
                                <button class="btn btn-outline-primary btn-action-icon"
                                        title="Edit Supplier"
                                        onclick="openEditSupplierForm(
                                            <?= $supplier['id'] ?>,
                                            '<?= addslashes($supplier['name']) ?>',
                                            '<?= addslashes($supplier['phone'] ?? '') ?>',
                                            '<?= addslashes($supplier['email'] ?? '') ?>',
                                            '<?= addslashes($supplier['address'] ?? '') ?>'
                                        )">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="suppliers.php" style="display:inline"
                                      onsubmit="return confirm('Delete supplier <?= addslashes(htmlspecialchars($supplier['name'])) ?>?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_method" value="DELETE">
                                    <input type="hidden" name="supplier_id" value="<?= $supplier['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-action-icon" title="Delete Supplier">
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
</div>

<?php if ($_SESSION['role'] === 'admin'): ?>
<div class="modal fade" id="supplierModal" tabindex="-1" aria-labelledby="supplierModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="supplierModalLabel">
                    <i class="bi bi-truck me-2"></i>Add Supplier
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="suppliers.php" id="supplier-form" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="_method"     id="sup-form-method" value="POST">
                <input type="hidden" name="supplier_id" id="sup-form-id"     value="">
                <div class="modal-body">
                    <div id="sup-modal-error" class="alert alert-danger d-none"></div>
                    <div class="mb-3">
                        <label for="sup-name" class="form-label fw-semibold">Supplier Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="sup-name" name="name" required placeholder="e.g. Rawbin Wholesalers">
                    </div>
                    <div class="row g-2">
                        <div class="col-6 mb-3">
                            <label for="sup-phone" class="form-label fw-semibold">Phone</label>
                            <input type="text" class="form-control" id="sup-phone" name="phone" placeholder="98XXXXXXXX">
                        </div>
                        <div class="col-6 mb-3">
                            <label for="sup-email" class="form-label fw-semibold">Email</label>
                            <input type="email" class="form-control" id="sup-email" name="email" placeholder="contact@supplier.com">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="sup-address" class="form-label fw-semibold">Address / City</label>
                        <input type="text" class="form-control" id="sup-address" name="address" placeholder="Kathmandu, Nepal">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gims-primary" id="sup-submit-btn">
                        <i class="bi bi-check-lg"></i> Save Supplier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
