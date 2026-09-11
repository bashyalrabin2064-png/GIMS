<?php
$required_role = 'admin';
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db.php';
$pdo = get_db_connection();

$validPeriods = ['today', 'week', 'month', 'all'];
$period       = in_array($_GET['period'] ?? '', $validPeriods) ? $_GET['period'] : 'all';
$filterFrom   = trim($_GET['from'] ?? '');
$filterTo     = trim($_GET['to']   ?? '');
$sortBy       = in_array($_GET['sort'] ?? '', ['name','revenue','sold','stock','profit','margin']) ? $_GET['sort'] : 'name';

if ($filterFrom && !DateTime::createFromFormat('Y-m-d', $filterFrom)) $filterFrom = '';
if ($filterTo   && !DateTime::createFromFormat('Y-m-d', $filterTo))   $filterTo   = '';

$periodCondition = match($period) {
    'today' => "AND DATE(s.sale_date) = CURDATE()",
    'week'  => "AND DATE(s.sale_date) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)",
    'month' => "AND DATE(s.sale_date) >= DATE_FORMAT(CURDATE(), '%Y-%m-01')",
    default => "",
};

$salesWhere = "WHERE s.voided_at IS NULL " . $periodCondition;
$sParams    = [];

if ($filterFrom) {
    $salesWhere .= " AND DATE(s.sale_date) >= ?";
    $sParams[]   = $filterFrom;
}
if ($filterTo) {
    $salesWhere .= " AND DATE(s.sale_date) <= ?";
    $sParams[]   = $filterTo;
}

$sortMap = [
    'name'    => 'p.name ASC',
    'revenue' => 'total_revenue DESC',
    'sold'    => 'total_sold DESC',
    'stock'   => 'p.quantity DESC',
    'profit'  => 'profit DESC',
    'margin'  => 'margin_pct DESC',
];
$orderBy = $sortMap[$sortBy];

$sql = "
SELECT
    p.id,
    p.name          AS product_name,
    p.sku,
    p.unit,
    c.name          AS category_name,
    p.quantity      AS current_stock,
    p.min_quantity,
    p.cost_price,
    p.sell_price,
    COALESCE(sd.total_sold,       0) AS total_sold,
    COALESCE(sd.total_revenue,    0) AS total_revenue,
    COALESCE(sd.total_discount,   0) AS total_discount,
    COALESCE(sd.total_vat,        0) AS total_vat,
    (p.quantity * p.cost_price)      AS stock_value,
    (COALESCE(sd.total_sold, 0) * p.cost_price) AS total_cogs,
    (COALESCE(sd.total_revenue, 0) - COALESCE(sd.total_discount, 0) - (COALESCE(sd.total_sold, 0) * p.cost_price)) AS profit,
    CASE WHEN p.cost_price > 0
         THEN ROUND(((p.sell_price - p.cost_price) / p.cost_price) * 100, 1)
         ELSE NULL END AS margin_pct
FROM products p
LEFT JOIN categories c ON c.id = p.category_id
LEFT JOIN (
    SELECT s.product_id,
           SUM(s.quantity)                 AS total_sold,
           SUM(s.quantity * s.unit_price)  AS total_revenue,
           SUM(s.discount)                 AS total_discount,
           SUM(s.vat_amount)               AS total_vat
    FROM sales s
    $salesWhere
    GROUP BY s.product_id
) sd ON sd.product_id = p.id
ORDER BY $orderBy
";

$stmt    = $pdo->prepare($sql);
$stmt->execute($sParams);
$rows    = $stmt->fetchAll();

$grandRevenue  = 0;
$grandCogs     = 0;
$grandProfit   = 0;
$grandSold     = 0;
$grandStockVal = 0;
$grandDiscount = 0;
$grandVat      = 0;

foreach ($rows as $r) {
    $grandRevenue  += $r['total_revenue'];
    $grandCogs     += $r['total_cogs'];
    $grandProfit   += $r['profit'];
    $grandSold     += $r['total_sold'];
    $grandStockVal += $r['stock_value'];
    $grandDiscount += $r['total_discount'];
    $grandVat      += $r['total_vat'];
}

$top10Stmt = $pdo->prepare(
    "SELECT p.name, SUM(s.quantity) AS total_sold
     FROM sales s
     LEFT JOIN products p ON p.id = s.product_id
     WHERE s.voided_at IS NULL
     GROUP BY s.product_id, p.name
     ORDER BY total_sold DESC
     LIMIT 10"
);
$top10Stmt->execute();
$top10 = $top10Stmt->fetchAll();

$revStmt = $pdo->prepare(
    "SELECT DATE(sale_date) AS day, SUM((quantity * unit_price - discount) + vat_amount) AS revenue
     FROM sales
     WHERE voided_at IS NULL
       AND DATE(sale_date) >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
     GROUP BY DATE(sale_date)
     ORDER BY day ASC"
);
$revStmt->execute();
$revRows = $revStmt->fetchAll(PDO::FETCH_KEY_PAIR);

$revLabels = [];
$revValues = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $revLabels[] = date('d M', strtotime($d));
    $revValues[] = isset($revRows[$d]) ? (float)$revRows[$d] : 0;
}

$chartTop10Labels = json_encode(array_column($top10, 'name'));
$chartTop10Values = json_encode(array_map(fn($r) => (int)$r['total_sold'], $top10));
$chartRevLabels   = json_encode($revLabels);
$chartRevValues   = json_encode($revValues);

$periodLabels = ['all' => 'All Time', 'month' => 'This Month', 'week' => 'This Week', 'today' => 'Today'];

$page_title   = 'Reports & Analytics';
$active_page  = 'reports';
$load_chartjs = true;
require_once __DIR__ . '/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-graph-up"></i> Reports & Analytics</h2>
    <span class="badge rounded-pill bg-light text-secondary border">Admin Overview</span>
</div>

<form method="GET" action="reports.php" class="filter-bar">
    <input type="hidden" name="period" value="<?= htmlspecialchars($period) ?>">
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
    <div>
        <label>Sort Metric</label>
        <select name="sort" class="form-select form-select-sm" style="min-width:160px">
            <option value="name"    <?= $sortBy === 'name'    ? 'selected' : '' ?>>Product Name</option>
            <option value="revenue" <?= $sortBy === 'revenue' ? 'selected' : '' ?>>Revenue ↓</option>
            <option value="sold"    <?= $sortBy === 'sold'    ? 'selected' : '' ?>>Units Sold ↓</option>
            <option value="stock"   <?= $sortBy === 'stock'   ? 'selected' : '' ?>>Stock ↓</option>
            <option value="profit"  <?= $sortBy === 'profit'  ? 'selected' : '' ?>>Profit ↓</option>
            <option value="margin"  <?= $sortBy === 'margin'  ? 'selected' : '' ?>>Margin % ↓</option>
        </select>
    </div>
    <div class="d-flex align-items-end gap-1">
        <button type="submit" class="btn btn-gims-primary btn-sm">
            <i class="bi bi-funnel"></i> Apply
        </button>
        <?php if (!empty($filterFrom) || !empty($filterTo) || $sortBy !== 'name'): ?>
        <a href="reports.php?period=<?= htmlspecialchars($period) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-x"></i> Reset
        </a>
        <?php endif; ?>
    </div>
</form>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-0" style="background:#eff6ff;">
            <div class="card-body">
                <div>
                    <div class="stat-number" style="color:#2563eb;"><?= number_format($grandSold) ?></div>
                    <div class="stat-label" style="color:#2563eb;">Units Sold — <?= $periodLabels[$period] ?></div>
                </div>
                <i class="bi bi-bag-check stat-icon" style="color:#2563eb;"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-0" style="background:#f0fdf4;">
            <div class="card-body">
                <div>
                    <div class="stat-number" style="color:#16a34a;">NRs. <?= number_format($grandRevenue - $grandDiscount + $grandVat, 0) ?></div>
                    <div class="stat-label" style="color:#16a34a;">Revenue (incl. VAT) — <?= $periodLabels[$period] ?></div>
                </div>
                <i class="bi bi-cash-coin stat-icon" style="color:#16a34a;"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-0" style="background:<?= $grandProfit >= 0 ? '#ecfdf5' : '#fef2f2' ?>;">
            <div class="card-body">
                <div>
                    <div class="stat-number" style="color:<?= $grandProfit >= 0 ? '#059669' : '#dc2626' ?>;">
                        <?= $grandProfit < 0 ? '- ' : '' ?>NRs. <?= number_format(abs($grandProfit), 0) ?>
                    </div>
                    <div class="stat-label" style="color:<?= $grandProfit >= 0 ? '#059669' : '#dc2626' ?>;">
                        <?= $grandProfit >= 0 ? 'Net Profit' : 'Net Loss' ?> — <?= $periodLabels[$period] ?>
                    </div>
                </div>
                <i class="bi bi-graph-up-arrow stat-icon" style="color:<?= $grandProfit >= 0 ? '#059669' : '#dc2626' ?>;"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-0" style="background:#fffbeb;">
            <div class="card-body">
                <div>
                    <div class="stat-number" style="color:#d97706;">NRs. <?= number_format($grandVat, 0) ?></div>
                    <div class="stat-label" style="color:#d97706;">VAT Collected (13%) — <?= $periodLabels[$period] ?></div>
                </div>
                <i class="bi bi-percent stat-icon" style="color:#d97706;"></i>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-lg-7">
        <div class="content-card card h-100">
            <div class="card-header flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="card-header-icon"><i class="bi bi-graph-up"></i></span>
                    <span class="fw-bold">Revenue — Last 14 Days (incl. VAT)</span>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <span class="text-muted small fw-semibold me-1">Revenue Period:</span>
                    <?php foreach ($periodLabels as $key => $label): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['period' => $key])) ?>"
                       class="btn btn-sm <?= $period === $key ? 'btn-gims-primary' : 'btn-outline-secondary' ?>">
                        <?= $label ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" style="max-height:260px;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-5">
        <div class="content-card card h-100">
            <div class="card-header">
                <div class="d-flex align-items-center gap-2">
                    <span class="card-header-icon"><i class="bi bi-trophy"></i></span>
                    <span class="fw-bold">Top 10 Products by Units Sold</span>
                </div>
            </div>
            <div class="card-body">
                <canvas id="top10Chart" style="max-height:260px;"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="content-card card">
    <div class="card-header flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="card-header-icon"><i class="bi bi-table"></i></span>
            <div>
                <span class="fw-bold">Per-Item Breakdown</span>
                <span class="badge rounded-pill bg-light text-secondary border ms-2"><?= count($rows) ?> items</span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="input-group input-group-sm" style="width: 220px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="reportTableSearch" class="form-control border-start-0" placeholder="Filter items...">
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()" title="Print table">
                <i class="bi bi-printer"></i>
            </button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="report-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 45px;">#</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th class="text-end">Units Sold</th>
                    <th class="text-end">Revenue (NRs.)</th>
                    <th class="text-end">Discounts</th>
                    <th class="text-end">VAT Collected</th>
                    <th class="text-end">COGS (NRs.)</th>
                    <th class="text-end">Profit / Loss</th>
                    <th class="text-end">Margin %</th>
                    <th class="text-end">Current Stock</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="12" class="empty-state">
                            <i class="bi bi-bar-chart"></i>
                            No data found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $i = 1; foreach ($rows as $row):
                        $rUnit = $row['unit'] ?? 'pcs';
                    ?>
                    <tr>
                        <td class="text-center text-muted small"><?= $i++ ?></td>
                        <td>
                            <div class="fw-semibold text-dark"><?= htmlspecialchars($row['product_name']) ?></div>
                            <div class="d-flex align-items-center gap-1 mt-1">
                                <span class="badge bg-light text-dark border font-monospace py-0 px-1" style="font-size:0.72rem;"><?= htmlspecialchars($rUnit) ?></span>
                                <?php if ($row['sku']): ?>
                                <span class="badge bg-light text-muted border font-monospace py-0 px-1" style="font-size:0.72rem;"><?= htmlspecialchars($row['sku']) ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge-category"><?= htmlspecialchars($row['category_name'] ?? 'Uncategorized') ?></span>
                        </td>
                        <td class="text-end fw-bold" style="color:#2563eb;"><?= format_quantity($row['total_sold'], $rUnit) ?></td>
                        <td class="text-end fw-semibold">NRs. <?= number_format($row['total_revenue'], 2) ?></td>
                        <td class="text-end text-danger small">
                            <?= $row['total_discount'] > 0 ? '-NRs. ' . number_format($row['total_discount'], 2) : '<span class="text-muted">—</span>' ?>
                        </td>
                        <td class="text-end small" style="color:#d97706;">NRs. <?= number_format($row['total_vat'], 2) ?></td>
                        <td class="text-end text-muted">NRs. <?= number_format($row['total_cogs'], 2) ?></td>
                        <td class="text-end">
                            <?php if ($row['profit'] > 0): ?>
                                <span class="profit-badge positive"><i class="bi bi-arrow-up-short"></i>+NRs. <?= number_format($row['profit'], 2) ?></span>
                            <?php elseif ($row['profit'] < 0): ?>
                                <span class="profit-badge negative"><i class="bi bi-arrow-down-short"></i>-NRs. <?= number_format(abs($row['profit']), 2) ?></span>
                            <?php else: ?>
                                <span class="profit-badge neutral">+NRs. 0.00</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if ($row['margin_pct'] !== null): ?>
                                <span class="badge <?= $row['margin_pct'] >= 0 ? 'bg-success' : 'bg-danger' ?> bg-opacity-10
                                      <?= $row['margin_pct'] >= 0 ? 'text-success' : 'text-danger' ?> border
                                      <?= $row['margin_pct'] >= 0 ? 'border-success' : 'border-danger' ?>"
                                      style="font-size:0.8rem;">
                                    <?= $row['margin_pct'] >= 0 ? '+' : '' ?><?= number_format($row['margin_pct'], 1) ?>%
                                </span>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <span class="fw-bold <?= $row['current_stock'] < $row['min_quantity'] ? 'text-danger' : 'text-success' ?>">
                                <?= format_quantity($row['current_stock'], $rUnit) ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <?php if ($row['current_stock'] < $row['min_quantity']): ?>
                                <span class="badge-stock-low"><i class="bi bi-exclamation-triangle"></i> Low</span>
                            <?php else: ?>
                                <span class="badge-stock-ok"><i class="bi bi-check-circle"></i> OK</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="table-totals-row">
                        <td colspan="3" class="text-end fw-bold text-uppercase small text-muted">
                            <i class="bi bi-calculator me-1"></i> Totals:
                        </td>
                        <td class="text-end fw-bold" style="color:#2563eb;"><?= number_format($grandSold) ?></td>
                        <td class="text-end fw-bold text-dark">NRs. <?= number_format($grandRevenue, 2) ?></td>
                        <td class="text-end text-danger fw-semibold">-NRs. <?= number_format($grandDiscount, 2) ?></td>
                        <td class="text-end fw-semibold" style="color:#d97706;">NRs. <?= number_format($grandVat, 2) ?></td>
                        <td class="text-end text-muted fw-semibold">NRs. <?= number_format($grandCogs, 2) ?></td>
                        <td class="text-end">
                            <?php if ($grandProfit > 0): ?>
                                <span class="profit-badge positive"><i class="bi bi-arrow-up-short"></i>+NRs. <?= number_format($grandProfit, 2) ?></span>
                            <?php elseif ($grandProfit < 0): ?>
                                <span class="profit-badge negative"><i class="bi bi-arrow-down-short"></i>-NRs. <?= number_format(abs($grandProfit), 2) ?></span>
                            <?php else: ?>
                                <span class="profit-badge neutral">+NRs. 0.00</span>
                            <?php endif; ?>
                        </td>
                        <td colspan="3"></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="chart-data"
     data-rev-labels='<?= htmlspecialchars($chartRevLabels) ?>'
     data-rev-values='<?= htmlspecialchars($chartRevValues) ?>'
     data-top10-labels='<?= htmlspecialchars($chartTop10Labels) ?>'
     data-top10-values='<?= htmlspecialchars($chartTop10Values) ?>'
     style="display:none">
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
