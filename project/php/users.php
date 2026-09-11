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
    $action = $_POST['_action'] ?? '';

    if ($action === 'toggle_lock') {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId === (int)$_SESSION['user_id']) {
            $formError = 'You cannot lock your own account.';
        } elseif ($userId > 0) {
            $stmt = $pdo->prepare("SELECT is_locked, full_name FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $u = $stmt->fetch();
            if ($u) {
                $newStatus = $u['is_locked'] ? 0 : 1;
                $pdo->prepare("UPDATE users SET is_locked = ? WHERE id = ?")->execute([$newStatus, $userId]);
                $formSuccess = 'User "' . htmlspecialchars($u['full_name']) . '" is now ' . ($newStatus ? 'locked.' : 'active.');
            }
        }
    } elseif ($method === 'POST') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = in_array($_POST['role'] ?? '', ['admin','staff']) ? $_POST['role'] : 'staff';

        if (empty($fullName)) {
            $formError = 'Full name is required.';
        } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $formError = 'A valid email address is required.';
        } elseif (strlen($password) < 6) {
            $formError = 'Password must be at least 6 characters.';
        } else {
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $checkStmt->execute([$email]);
            if ($checkStmt->fetch()) {
                $formError = 'An account with this email already exists.';
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)"
                );
                $stmt->execute([$fullName, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
                $formSuccess = 'User "' . htmlspecialchars($fullName) . '" created successfully.';
            }
        }
    } elseif ($method === 'PUT') {
        $userId   = (int)($_POST['user_id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $role     = in_array($_POST['role'] ?? '', ['admin','staff']) ? $_POST['role'] : 'staff';
        $isLocked = (int)($_POST['is_locked'] ?? 0);
        $password = $_POST['password'] ?? '';

        if (empty($fullName) || $userId === 0) {
            $formError = 'Full name is required.';
        } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $formError = 'A valid email address is required.';
        } else {
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $checkStmt->execute([$email, $userId]);
            if ($checkStmt->fetch()) {
                $formError = 'Another account with this email already exists.';
            } else {
                if (!empty($password)) {
                    if (strlen($password) < 6) {
                        $formError = 'Password must be at least 6 characters.';
                    } else {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, role = ?, is_locked = ?, password = ? WHERE id = ?");
                        $stmt->execute([$fullName, $email, $role, $isLocked, $hash, $userId]);
                        $formSuccess = 'User "' . htmlspecialchars($fullName) . '" updated successfully.';
                    }
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, role = ?, is_locked = ? WHERE id = ?");
                    $stmt->execute([$fullName, $email, $role, $isLocked, $userId]);
                    $formSuccess = 'User "' . htmlspecialchars($fullName) . '" updated successfully.';
                }

                if ($userId === (int)$_SESSION['user_id']) {
                    $_SESSION['full_name'] = $fullName;
                    $_SESSION['email']     = $email;
                    $_SESSION['role']      = $role;
                }
            }
        }
    } elseif ($method === 'DELETE') {
        $userId = (int)($_POST['user_id'] ?? 0);

        $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

        $targetStmt = $pdo->prepare("SELECT role, is_locked FROM users WHERE id = ?");
        $targetStmt->execute([$userId]);
        $targetUser = $targetStmt->fetch();

        if ($userId === (int)$_SESSION['user_id']) {
            $formError = 'You cannot delete your own account.';
        } elseif ($targetUser && $targetUser['is_locked']) {
            $formError = 'This account is locked and cannot be deleted.';
        } elseif ($targetUser && $targetUser['role'] === 'admin' && $adminCount <= 1) {
            $formError = 'Cannot delete the last admin account.';
        } elseif ($userId > 0) {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
            $formSuccess = 'User account deleted successfully.';
        }
    }
}

$stmt  = $pdo->query("SELECT id, full_name, email, role, is_locked, created_at FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

$statsTotalUsers  = count($users);
$statsAdminUsers  = 0;
$statsStaffUsers  = 0;
$statsActiveUsers = 0;

foreach ($users as $u) {
    if ($u['role'] === 'admin') {
        $statsAdminUsers++;
    } else {
        $statsStaffUsers++;
    }
    if (empty($u['is_locked'])) {
        $statsActiveUsers++;
    }
}

$page_title  = 'User Management';
$active_page = 'users';
require_once __DIR__ . '/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-people"></i> User Management
        <span class="badge rounded-pill bg-light text-secondary border ms-2"><?= $statsTotalUsers ?> users</span>
    </h2>
    <button class="btn btn-gims-primary"
            data-bs-toggle="modal" data-bs-target="#userModal"
            onclick="openAddUserForm()">
        <i class="bi bi-person-plus"></i> Add New User
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
                    <div class="stat-number" style="color:#2563eb;"><?= $statsTotalUsers ?></div>
                    <div class="stat-label" style="color:#2563eb;">Total Users</div>
                </div>
                <i class="bi bi-people stat-icon" style="color:#2563eb;"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-0" style="background:#fef2f2;">
            <div class="card-body">
                <div>
                    <div class="stat-number" style="color:#dc2626;"><?= $statsAdminUsers ?></div>
                    <div class="stat-label" style="color:#dc2626;">Administrators</div>
                </div>
                <i class="bi bi-shield-lock stat-icon" style="color:#dc2626;"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-0" style="background:#f0fdfa;">
            <div class="card-body">
                <div>
                    <div class="stat-number" style="color:#0d9488;"><?= $statsStaffUsers ?></div>
                    <div class="stat-label" style="color:#0d9488;">Staff Members</div>
                </div>
                <i class="bi bi-person-badge stat-icon" style="color:#0d9488;"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-0" style="background:#f0fdf4;">
            <div class="card-body">
                <div>
                    <div class="stat-number" style="color:#16a34a;"><?= $statsActiveUsers ?></div>
                    <div class="stat-label" style="color:#16a34a;">Active Accounts</div>
                </div>
                <i class="bi bi-check-circle stat-icon" style="color:#16a34a;"></i>
            </div>
        </div>
    </div>
</div>

<div class="content-card card">
    <div class="card-header flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="card-header-icon"><i class="bi bi-people"></i></span>
            <div>
                <span class="fw-bold">User Accounts</span>
                <span class="badge rounded-pill bg-light text-secondary border ms-2"><?= $statsTotalUsers ?> total</span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="input-group input-group-sm" style="width: 220px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" class="form-control border-start-0 table-search-input" data-table="users-table" placeholder="Filter users...">
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()" title="Print table">
                <i class="bi bi-printer"></i>
            </button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="users-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 45px;">#</th>
                    <th>Full Name</th>
                    <th>Email Address</th>
                    <th>Role</th>
                    <th class="text-center">Status</th>
                    <th>Joined</th>
                    <th class="text-center" style="width: 140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" class="empty-state">
                            <i class="bi bi-people"></i> No users found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php
                    $rowNumber = 1;
                    foreach ($users as $user):
                    ?>
                    <tr>
                        <td class="text-center text-muted small"><?= $rowNumber++ ?></td>
                        <td class="fw-semibold text-dark">
                            <?= htmlspecialchars($user['full_name']) ?>
                            <?php if ($user['id'] === $_SESSION['user_id']): ?>
                                <span class="badge bg-primary ms-1">You</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="text-muted"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($user['email']) ?></span>
                        </td>
                        <td>
                            <?php if ($user['role'] === 'admin'): ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold">Admin</span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fw-semibold">Staff</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($user['is_locked']): ?>
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle fw-semibold"><i class="bi bi-lock-fill"></i> Locked</span>
                            <?php else: ?>
                                <span class="badge-stock-ok"><i class="bi bi-check-circle"></i> Active</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="text-muted small"><i class="bi bi-calendar3 me-1"></i><?= date('d M Y', strtotime($user['created_at'])) ?></span>
                        </td>
                        <td class="text-center">
                            <div class="d-inline-flex gap-1">
                                <button class="btn btn-outline-primary btn-action-icon"
                                        title="Edit User"
                                        onclick="openEditUserForm(
                                            <?= $user['id'] ?>,
                                            '<?= addslashes($user['full_name']) ?>',
                                            '<?= addslashes($user['email']) ?>',
                                            '<?= $user['role'] ?>',
                                            <?= (int)$user['is_locked'] ?>
                                        )">
                                    <i class="bi bi-pencil"></i>
                                </button>

                                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                    <form method="POST" action="users.php" style="display:inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="_action" value="toggle_lock">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn btn-action-icon <?= $user['is_locked'] ? 'btn-outline-success' : 'btn-outline-warning' ?>"
                                                title="<?= $user['is_locked'] ? 'Unlock Account' : 'Lock Account' ?>">
                                            <i class="bi <?= $user['is_locked'] ? 'bi-unlock' : 'bi-lock' ?>"></i>
                                        </button>
                                    </form>

                                    <form method="POST" action="users.php" style="display:inline"
                                          onsubmit="return confirm('Delete user <?= addslashes(htmlspecialchars($user['full_name'])) ?>?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="_method" value="DELETE">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-action-icon" title="Delete User">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">
                    <i class="bi bi-person-plus me-2"></i>Add New User
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="users.php" id="user-form" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="_method" id="user-method" value="POST">
                <input type="hidden" name="user_id" id="user-id" value="">

                <div class="modal-body">
                    <div id="user-modal-error" class="alert alert-danger d-none"></div>

                    <div class="mb-3">
                        <label for="user-fullname" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="user-fullname" name="full_name" required placeholder="e.g. Ram Shrestha">
                    </div>

                    <div class="mb-3">
                        <label for="user-email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="user-email" name="email" required placeholder="ram@example.com">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="user-role" class="form-label fw-semibold">Role</label>
                            <select class="form-select" id="user-role" name="role">
                                <option value="staff">Staff</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>

                        <div class="col-6" id="status-group">
                            <label for="user-status" class="form-label fw-semibold">Status</label>
                            <select class="form-select" id="user-status" name="is_locked">
                                <option value="0">Active</option>
                                <option value="1">Locked</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="user-password" class="form-label fw-semibold" id="user-password-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="user-password"
                               name="password" minlength="6"
                               placeholder="Minimum 6 characters">
                        <div class="form-text" id="user-password-help" style="display:none">Leave blank to keep existing password when editing.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gims-primary" id="user-submit-btn">
                        <i class="bi bi-check-lg"></i> Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
