<?php
if (!isset($pdo)) {
    require_once __DIR__ . '/db.php';
    $pdo = get_db_connection();
}

$stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM products WHERE quantity < min_quantity");
$lowStockCount = (int) $stmt->fetch()['cnt'];

$pageTitle = isset($page_title) ? $page_title . ' — GIMS' : 'GIMS';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark gims-navbar">
    <div class="container-fluid">

        <a class="navbar-brand fw-bold" href="dashboard.php">
            <i class="bi bi-shop"></i> GIMS
        </a>

        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <li class="nav-item">
                    <a class="nav-link <?= ($active_page ?? '') === 'dashboard' ? 'active' : '' ?>"
                       href="dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= ($active_page ?? '') === 'products' ? 'active' : '' ?>"
                       href="products.php">
                        <i class="bi bi-box-seam"></i> Products
                    </a>
                </li>

                <?php if ($_SESSION['role'] === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= ($active_page ?? '') === 'categories' ? 'active' : '' ?>"
                       href="categories.php">
                        <i class="bi bi-tags"></i> Categories
                    </a>
                </li>
                <?php endif; ?>

                <li class="nav-item">
                    <a class="nav-link <?= ($active_page ?? '') === 'suppliers' ? 'active' : '' ?>"
                       href="suppliers.php">
                        <i class="bi bi-truck"></i> Suppliers
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= ($active_page ?? '') === 'purchases' ? 'active' : '' ?>"
                       href="purchases.php">
                        <i class="bi bi-cart-plus"></i> Purchases
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= ($active_page ?? '') === 'sales' ? 'active' : '' ?>"
                       href="sales.php">
                        <i class="bi bi-receipt"></i> Sales
                    </a>
                </li>

                <?php if ($_SESSION['role'] === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= ($active_page ?? '') === 'reports' ? 'active' : '' ?>"
                       href="reports.php">
                        <i class="bi bi-graph-up"></i> Reports
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($active_page ?? '') === 'users' ? 'active' : '' ?>"
                       href="users.php">
                        <i class="bi bi-people"></i> Users
                    </a>
                </li>
                <?php endif; ?>

            </ul>

            <ul class="navbar-nav">
                <?php if ($lowStockCount > 0): ?>
                <li class="nav-item d-flex align-items-center me-2">
                    <a href="dashboard.php#low-stock" class="badge bg-danger text-decoration-none">
                        <i class="bi bi-exclamation-triangle"></i>
                        <?= $lowStockCount ?> Low Stock
                    </a>
                </li>
                <?php endif; ?>

                <li class="nav-item d-flex align-items-center me-1">
                    <a class="nav-link <?= ($active_page ?? '') === 'profile' ? 'active' : '' ?>"
                       href="profile.php" title="My Profile">
                        <i class="bi bi-person-circle"></i>
                        <span class="d-none d-lg-inline ms-1"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
                        <span class="badge bg-secondary ms-1"><?= $_SESSION['role'] ?></span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </li>
                <li class="nav-item d-flex align-items-center ms-1">
                    <span class="badge bg-dark text-light border border-secondary" style="font-size:0.7rem; cursor:default; opacity:0.65;" title="Press F to enter fullscreen, ESC to exit">
                        <i class="bi bi-fullscreen" style="font-size:0.65rem;"></i> F
                    </span>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="container-fluid py-4 px-4">

<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle"></i>
        <?= htmlspecialchars($_SESSION['flash_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php
    unset($_SESSION['flash_error']);
endif;

if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i>
        <?= htmlspecialchars($_SESSION['flash_success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php
    unset($_SESSION['flash_success']);
endif;
?>
