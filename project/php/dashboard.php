<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db.php';
$pdo = get_db_connection();

$validPeriods  = ['today', 'week', 'month', 'all'];
$period        = in_array($_GET['period'] ?? '', $validPeriods) ? $_GET['period'] : 'today';
$periodLabels  = ['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'all' => 'All Time'];

$periodWhere = match($period) {
    'today' => "AND DATE(sale_date) = CURDATE()",
    'week'  => "AND DATE(sale_date) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)",
    'month' => "AND DATE(sale_date) >= DATE_FORMAT(CURDATE(), '%Y-%m-01')",
    default => "",
};

$stmt          = $pdo->query("SELECT COUNT(*) AS cnt FROM products");
$totalProducts = (int)$stmt->fetch()['cnt'];

$stmt           = $pdo->query("SELECT COUNT(*) AS cnt FROM suppliers");
$totalSuppliers = (int)$stmt->fetch()['cnt'];

$stmt          = $pdo->query("SELECT COUNT(*) AS cnt FROM products WHERE quantity < min_quantity");
$lowStockCount = (int)$stmt->fetch()['cnt'];

$stmt        = $pdo->query("SELECT COUNT(*) AS cnt FROM sales WHERE voided_at IS NULL $periodWhere");
$periodSales = (int)$stmt->fetch()['cnt'];

$stmt = $pdo->query(
    "SELECT COALESCE(SUM((quantity * unit_price - discount) + vat_amount), 0) AS total
     FROM sales WHERE voided_at IS NULL $periodWhere"
);
$periodRevenue = (float)$stmt->fetch()['total'];

$stmt = $pdo->query(
    "SELECT p.name, p.quantity, p.min_quantity, p.unit, c.name AS category_name
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.quantity < p.min_quantity
     ORDER BY p.quantity ASC"
);
$lowStockProducts = $stmt->fetchAll();

$stmt = $pdo->query(
    "SELECT c.name AS category_name, COALESCE(SUM(p.quantity), 0) AS total_stock
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.id, c.name
     ORDER BY c.name ASC"
);
$categoryStockRows = $stmt->fetchAll();

$chartLabels = [];
$chartValues = [];

foreach ($categoryStockRows as $row) {
    $chartLabels[] = $row['category_name'];
    $chartValues[] = (int)$row['total_stock'];
}

$chartLabelsJson = json_encode($chartLabels);
$chartValuesJson = json_encode($chartValues);

$stmt = $pdo->query(
    "SELECT DATE(sale_date) AS day, SUM((quantity * unit_price - discount) + vat_amount) AS revenue
     FROM sales
     WHERE voided_at IS NULL
       AND DATE(sale_date) >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
     GROUP BY DATE(sale_date)
     ORDER BY day ASC"
);
$dashRevRows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$stmt = $pdo->query(
    "SELECT DATE(sale_date) AS day, COUNT(*) AS cnt
     FROM sales
     WHERE voided_at IS NULL
       AND DATE(sale_date) >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
     GROUP BY DATE(sale_date)
     ORDER BY day ASC"
);
$dashSalesRows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$dashRevLabels = [];
$dashRevValues = [];
$dashSalesValues = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $dashRevLabels[] = date('d M', strtotime($d));
    $dashRevValues[] = isset($dashRevRows[$d]) ? (float)$dashRevRows[$d] : 0;
    $dashSalesValues[] = isset($dashSalesRows[$d]) ? (int)$dashSalesRows[$d] : 0;
}
$dashRevLabelsJson  = json_encode($dashRevLabels);
$dashRevValuesJson  = json_encode($dashRevValues);
$dashSalesValuesJson = json_encode($dashSalesValues);

$page_title   = 'Dashboard';
$active_page  = 'dashboard';
$load_chartjs = true;
require_once __DIR__ . '/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-0" style="background:#eff6ff;">
            <div class="card-body">
                <div>
                    <div class="stat-number" style="color:#2563eb;"><?= $totalProducts ?></div>
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
                    <div class="stat-number" style="color:#0d9488;"><?= $totalSuppliers ?></div>
                    <div class="stat-label" style="color:#0d9488;">Suppliers</div>
                </div>
                <i class="bi bi-truck stat-icon" style="color:#0d9488;"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-0" style="background:#f0fdf4;">
            <div class="card-body">
                <div>
                    <div class="stat-number" style="color:#16a34a;"><?= $periodSales ?></div>
                    <div class="stat-label" style="color:#16a34a;">Sales — <?= $periodLabels[$period] ?></div>
                </div>
                <i class="bi bi-receipt stat-icon" style="color:#16a34a;"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-0" style="background:<?= $lowStockCount > 0 ? '#fef2f2' : '#f8fafc' ?>;">
            <div class="card-body">
                <div>
                    <div class="stat-number" style="color:<?= $lowStockCount > 0 ? '#dc2626' : '#64748b' ?>;"><?= $lowStockCount ?></div>
                    <div class="stat-label" style="color:<?= $lowStockCount > 0 ? '#dc2626' : '#64748b' ?>;">Low Stock Items</div>
                </div>
                <i class="bi bi-exclamation-triangle stat-icon" style="color:<?= $lowStockCount > 0 ? '#dc2626' : '#94a3b8' ?>;"></i>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-light border mb-4 p-3" style="background:#f0fdf4; border-color:rgba(22,163,74,0.2) !important; border-radius: var(--radius-md);">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-cash-coin fs-5" style="color:#16a34a;"></i>
            <strong class="fs-6 text-dark">Revenue — <?= $periodLabels[$period] ?> (incl. VAT):</strong>
            <span class="fs-5 fw-bold ms-2" style="color:#16a34a;">NRs. <?= number_format($periodRevenue, 2) ?></span>
        </div>
        <div class="d-flex align-items-center gap-1">
            <span class="text-muted small fw-semibold me-1">Revenue Period:</span>
            <?php foreach ($periodLabels as $key => $label): ?>
            <a href="?period=<?= $key ?>" class="btn btn-sm <?= $period === $key ? 'btn-gims-primary' : 'btn-outline-secondary' ?>">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-5">
        <div class="content-card card h-100" id="low-stock">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <span class="card-header-icon" style="background:#fef2f2; color:#dc2626;"><i class="bi bi-exclamation-triangle"></i></span>
                    <span class="fw-bold">Low Stock Alerts</span>
                </div>
                <span class="badge bg-danger"><?= $lowStockCount ?></span>
            </div>

            <?php if (empty($lowStockProducts)): ?>
                <div class="card-body">
                    <div class="empty-state">
                        <i class="bi bi-check-circle" style="color:#16a34a;"></i>
                        <p class="mt-2 mb-0" style="color:#16a34a;">All products are well stocked!</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th class="text-center">Stock</th>
                                <th class="text-center">Min</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lowStockProducts as $item):
                                $itemUnit = $item['unit'] ?? 'pcs';
                            ?>
                            <tr class="row-low-stock">
                                <td class="fw-semibold text-dark"><?= htmlspecialchars($item['name']) ?></td>
                                <td><span class="badge-category"><?= htmlspecialchars($item['category_name'] ?? '—') ?></span></td>
                                <td class="text-center"><span class="badge-stock-low"><?= format_quantity($item['quantity'], $itemUnit) ?></span></td>
                                <td class="text-center text-muted"><?= format_quantity($item['min_quantity'], $itemUnit) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer" style="background:#fff; border-top:1px solid var(--border);">
                    <a href="purchases.php" class="btn btn-sm btn-gims-primary">
                        <i class="bi bi-cart-plus"></i> Record Purchase to Restock
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-12 col-lg-7">
        <div class="content-card card h-100">
            <div class="card-header">
                <div class="d-flex align-items-center gap-2">
                    <span class="card-header-icon"><i class="bi bi-bar-chart"></i></span>
                    <span class="fw-bold">Current Stock by Category</span>
                </div>
            </div>
            <div class="card-body">
                <canvas id="stockChart" style="max-height: 280px;"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-2">
    <div class="col-12 col-lg-7">
        <div class="content-card card h-100">
            <div class="card-header">
                <div class="d-flex align-items-center gap-2">
                    <span class="card-header-icon" style="background:#f0fdf4; color:#16a34a;"><i class="bi bi-graph-up"></i></span>
                    <span class="fw-bold">Revenue — Last 14 Days (incl. VAT)</span>
                </div>
            </div>
            <div class="card-body">
                <canvas id="dashRevenueChart" style="max-height:220px;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-5">
        <div class="content-card card h-100">
            <div class="card-header">
                <div class="d-flex align-items-center gap-2">
                    <span class="card-header-icon" style="background:#eff6ff; color:#2563eb;"><i class="bi bi-receipt"></i></span>
                    <span class="fw-bold">Sales Transactions — Last 14 Days</span>
                </div>
            </div>
            <div class="card-body">
                <canvas id="dashSalesChart" style="max-height:220px;"></canvas>
            </div>
        </div>
    </div>
</div>

<div id="dashboard-chart-data"
     data-labels='<?= htmlspecialchars($chartLabelsJson) ?>'
     data-values='<?= htmlspecialchars($chartValuesJson) ?>'
     data-rev-labels='<?= htmlspecialchars($dashRevLabelsJson) ?>'
     data-rev-values='<?= htmlspecialchars($dashRevValuesJson) ?>'
     data-sales-values='<?= htmlspecialchars($dashSalesValuesJson) ?>'
     style="display:none">
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
