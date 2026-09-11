<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db.php';
$pdo = get_db_connection();

$targetUserId = (int)($_GET['id'] ?? $_SESSION['user_id']);
$isAdmin = ($_SESSION['role'] === 'admin');

if ($targetUserId !== (int)$_SESSION['user_id'] && !$isAdmin) {
    $targetUserId = (int)$_SESSION['user_id'];
}

$isSelf = ($targetUserId === (int)$_SESSION['user_id']);

$stmt = $pdo->prepare("SELECT id, full_name, email, role, is_locked, created_at FROM users WHERE id = ?");
$stmt->execute([$targetUserId]);
$targetUser = $stmt->fetch();

if (!$targetUser) {
    $_SESSION['flash_error'] = 'User not found.';
    header('Location: dashboard.php');
    exit;
}

$formError   = '';
$formSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['_action'] ?? 'update_profile';

    if ($action === 'reset_system_records') {
        if (!$isAdmin) {
            $formError = 'Access denied. Only administrators can perform a system reset.';
        } else {
            $adminPass = $_POST['admin_password'] ?? '';
            $resetScope = $_POST['reset_scope'] ?? 'products_and_history';

            $passStmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $passStmt->execute([$_SESSION['user_id']]);
            $currentAdmin = $passStmt->fetch();

            if (!$currentAdmin || !password_verify($adminPass, $currentAdmin['password'])) {
                $formError = 'Incorrect admin password. System reset authorization failed.';
            } else {
                try {
                    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                    $pdo->exec("TRUNCATE TABLE stock_adjustments");
                    $pdo->exec("TRUNCATE TABLE sales");
                    $pdo->exec("TRUNCATE TABLE purchases");

                    if ($resetScope === 'transactions_only') {
                        $pdo->exec("UPDATE products SET quantity = 0");
                        $formSuccess = 'All transaction history (sales and purchases) cleared. Product quantities reset to 0.';
                    } elseif ($resetScope === 'full_wipe') {
                        $pdo->exec("TRUNCATE TABLE products");
                        $pdo->exec("TRUNCATE TABLE categories");
                        $pdo->exec("TRUNCATE TABLE suppliers");
                        $formSuccess = 'Full system reset complete. All products, categories, suppliers, and transaction records have been erased.';
                    } else {
                        $pdo->exec("TRUNCATE TABLE products");
                        $formSuccess = 'All products, inventory history, sales, and purchase records have been completely cleared back to initial state.';
                    }

                    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                } catch (PDOException $e) {
                    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                    $formError = 'Database error during reset: ' . $e->getMessage();
                }
            }
        }
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $newPass  = $_POST['new_password'] ?? '';
        $confirmP = $_POST['confirm_password'] ?? '';

        $email    = trim($_POST['email'] ?? $targetUser['email']);
        $role     = ($isAdmin && isset($_POST['role']) && in_array($_POST['role'], ['admin', 'staff'])) ? $_POST['role'] : $targetUser['role'];
        $isLocked = ($isAdmin && isset($_POST['is_locked'])) ? (int)$_POST['is_locked'] : (int)$targetUser['is_locked'];

        if (empty($fullName)) {
            $formError = 'Full name cannot be empty.';
        } elseif ($isAdmin && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $formError = 'A valid email address is required.';
        } elseif (!empty($newPass) && strlen($newPass) < 6) {
            $formError = 'New password must be at least 6 characters long.';
        } elseif (!empty($newPass) && $newPass !== $confirmP) {
            $formError = 'New password and confirm password do not match.';
        } else {
            if ($email !== $targetUser['email']) {
                $checkEmail = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $checkEmail->execute([$email, $targetUserId]);
                if ($checkEmail->fetch()) {
                    $formError = 'An account with this email address already exists.';
                }
            }

            if (empty($formError)) {
                try {
                    if ($isAdmin) {
                        if (!empty($newPass)) {
                            $hash = password_hash($newPass, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, role = ?, is_locked = ?, password = ? WHERE id = ?");
                            $stmt->execute([$fullName, $email, $role, $isLocked, $hash, $targetUserId]);
                        } else {
                            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, role = ?, is_locked = ? WHERE id = ?");
                            $stmt->execute([$fullName, $email, $role, $isLocked, $targetUserId]);
                        }
                    } else {
                        if (!empty($newPass)) {
                            $hash = password_hash($newPass, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, password = ? WHERE id = ?");
                            $stmt->execute([$fullName, $hash, $targetUserId]);
                        } else {
                            $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?");
                            $stmt->execute([$fullName, $targetUserId]);
                        }
                    }

                    if ($isSelf) {
                        $_SESSION['full_name'] = $fullName;
                        $_SESSION['email']     = $email;
                        $_SESSION['role']      = $role;
                    }

                    $stmt = $pdo->prepare("SELECT id, full_name, email, role, is_locked, created_at FROM users WHERE id = ?");
                    $stmt->execute([$targetUserId]);
                    $targetUser = $stmt->fetch();

                    $formSuccess = 'Profile updated successfully.';
                } catch (PDOException $e) {
                    $formError = 'Database error updating profile: ' . $e->getMessage();
                }
            }
        }
    }
}

$active_page = 'profile';
$page_title  = $isSelf ? 'My Profile' : htmlspecialchars($targetUser['full_name']) . ' — Profile';
require_once __DIR__ . '/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">

        <div class="page-header mb-3">
            <h2>
                <i class="bi bi-person-circle"></i>
                <?= $isSelf ? 'My Profile' : 'User Profile' ?>
            </h2>
            <?php if ($isAdmin && !$isSelf): ?>
                <a href="users.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Users
                </a>
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

        <div class="content-card card">
            <div class="card-header">
                <div class="d-flex align-items-center gap-2">
                    <span class="card-header-icon"><i class="bi bi-person"></i></span>
                    <span class="fw-bold">Account Details</span>
                </div>
                <div>
                    <?php if ($targetUser['role'] === 'admin'): ?>
                        <span class="badge bg-danger">Admin</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Staff</span>
                    <?php endif; ?>

                    <?php if ($targetUser['is_locked']): ?>
                        <span class="badge bg-warning text-dark ms-1">Locked</span>
                    <?php else: ?>
                        <span class="badge-stock-ok ms-1">Active</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-body p-4">
                <form method="POST" action="profile.php<?= !$isSelf ? '?id=' . $targetUserId : '' ?>">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="full_name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text"
                               id="full_name"
                               name="full_name"
                               class="form-control"
                               value="<?= htmlspecialchars($targetUser['full_name']) ?>"
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email Address</label>
                        <?php if ($isAdmin): ?>
                            <input type="email"
                                   id="email"
                                   name="email"
                                   class="form-control"
                                   value="<?= htmlspecialchars($targetUser['email']) ?>"
                                   required>
                        <?php else: ?>
                            <input type="email"
                                   id="email"
                                   class="form-control bg-light"
                                   value="<?= htmlspecialchars($targetUser['email']) ?>"
                                   disabled>
                            <div class="form-text">Contact an admin to change your registered email address.</div>
                        <?php endif; ?>
                    </div>

                    <?php if ($isAdmin): ?>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="role" class="form-label fw-semibold">Role</label>
                            <select id="role" name="role" class="form-select">
                                <option value="staff" <?= $targetUser['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                                <option value="admin" <?= $targetUser['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="is_locked" class="form-label fw-semibold">Status</label>
                            <select id="is_locked" name="is_locked" class="form-select">
                                <option value="0" <?= empty($targetUser['is_locked']) ? 'selected' : '' ?>>Active</option>
                                <option value="1" <?= !empty($targetUser['is_locked']) ? 'selected' : '' ?>>Locked</option>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3 text-muted small">
                        <i class="bi bi-calendar3"></i> Member since: <strong><?= date('d M Y', strtotime($targetUser['created_at'])) ?></strong>
                    </div>

                    <hr class="my-4">

                    <h6 class="fw-bold mb-3"><i class="bi bi-key"></i> <?= $isSelf ? 'Change Password' : 'Reset Password' ?></h6>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password"
                               id="new_password"
                               name="new_password"
                               class="form-control"
                               placeholder="Leave blank to keep current password">
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password"
                               id="confirm_password"
                               name="confirm_password"
                               class="form-control"
                               placeholder="Re-type new password">
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <?php if ($isAdmin && !$isSelf): ?>
                            <a href="users.php" class="btn btn-outline-secondary">Cancel</a>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-gims-primary">
                            <i class="bi bi-save"></i> Save Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($isAdmin): ?>
        <div class="content-card card border-danger mt-4">
            <div class="card-header bg-danger text-white">
                <div class="d-flex align-items-center gap-2">
                    <span class="card-header-icon text-white"><i class="bi bi-shield-exclamation"></i></span>
                    <span class="fw-bold">Danger Zone — System Records Reset</span>
                </div>
                <span class="badge bg-white text-danger fw-bold">Admin Only</span>
            </div>
            <div class="card-body p-4">
                <h6 class="fw-bold text-danger mb-2">
                    <i class="bi bi-trash3 me-1"></i> Clear Product History & Transaction Records
                </h6>
                <p class="text-muted small mb-3">
                    Permanently wipe all transaction history (sales & purchases), reset product stock counts, or perform a complete inventory wipe to return the system to an initial blank state.
                </p>
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#resetRecordsModal">
                    <i class="bi bi-trash3-fill me-1"></i> Clear History & Reset Records
                </button>
            </div>
        </div>

        <div class="modal fade" id="resetRecordsModal" tabindex="-1" aria-labelledby="resetRecordsModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content border-danger">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="resetRecordsModalLabel">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>Authorize System Reset
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="profile.php<?= !$isSelf ? '?id=' . $targetUserId : '' ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="_action" value="reset_system_records">
                        <div class="modal-body">
                            <div class="alert alert-danger mb-3">
                                <i class="bi bi-exclamation-octagon-fill me-1"></i>
                                <strong>Warning:</strong> This action cannot be undone. User accounts will be preserved, but selected inventory records and transactions will be cleared.
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Select Reset Scope <span class="text-danger">*</span></label>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="reset_scope" id="scope_products_and_history" value="products_and_history" checked>
                                    <label class="form-check-label" for="scope_products_and_history">
                                        <strong>Clear All Products & History (Recommended)</strong>
                                        <div class="text-muted small">Wipes all products, sales history, purchases, and stock adjustments. Categories and suppliers remain.</div>
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="reset_scope" id="scope_transactions_only" value="transactions_only">
                                    <label class="form-check-label" for="scope_transactions_only">
                                        <strong>Clear Transaction History Only</strong>
                                        <div class="text-muted small">Wipes all sales, purchases, and adjustment history. Keeps product catalog with stock quantities set to 0.</div>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="reset_scope" id="scope_full_wipe" value="full_wipe">
                                    <label class="form-check-label" for="scope_full_wipe">
                                        <strong>Complete Inventory Wipe</strong>
                                        <div class="text-muted small">Wipes all products, categories, suppliers, sales, and purchase records. Only user logins are kept.</div>
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="admin_password_confirm" class="form-label fw-semibold">
                                    Confirm Admin Password <span class="text-danger">*</span>
                                </label>
                                <input type="password" class="form-control" id="admin_password_confirm"
                                       name="admin_password" required placeholder="Enter your admin account password">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash3-fill me-1"></i> Yes, Clear All Records
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
