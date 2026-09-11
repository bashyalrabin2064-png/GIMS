<?php
session_start();

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/db.php';
$pdo = get_db_connection();

$adminCheck      = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$no_admin_exists = ($adminCheck == 0);

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($no_admin_exists) {
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email']     ?? '');
        $password = $_POST['password']       ?? '';

        if (empty($fullName) || empty($email) || empty($password)) {
            $errorMessage = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = 'A valid email address is required.';
        } elseif (strlen($password) < 6) {
            $errorMessage = 'Password must be at least 6 characters.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'admin')");
            $stmt->execute([$fullName, $email, $hashedPassword]);

            session_regenerate_id(true);
            $_SESSION['user_id']   = $pdo->lastInsertId();
            $_SESSION['full_name'] = $fullName;
            $_SESSION['email']     = $email;
            $_SESSION['role']      = 'admin';

            header('Location: dashboard.php');
            exit;
        }
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $errorMessage = 'Please enter both email and password.';
        } else {
            $stmt = $pdo->prepare(
                "SELECT id, full_name, email, password, role FROM users WHERE email = ?"
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user === false || !password_verify($password, $user['password'])) {
                $errorMessage = 'Invalid email or password.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email']     = $user['email'];
                $_SESSION['role']      = $user['role'];

                header('Location: dashboard.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — GIMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="login-wrapper">
    <div class="login-card card">

        <div class="card-header">
            <h1><i class="bi bi-shop"></i> GIMS</h1>
            <p class="mb-0 mt-1">
                <?= $no_admin_exists ? 'Create First Admin Account' : 'Grocery Inventory Management System' ?>
            </p>
        </div>

        <div class="card-body p-4">

            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger" role="alert" id="login-error">
                    <i class="bi bi-exclamation-circle"></i>
                    <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>

            <?php if ($no_admin_exists): ?>
                <form method="POST" action="index.php" id="login-form" novalidate>
                    <input type="hidden" name="create_first_admin" value="1">

                    <div class="mb-3">
                        <label for="fullname" class="form-label fw-semibold">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text"
                                   id="fullname"
                                   name="full_name"
                                   class="form-control"
                                   placeholder="Full Name"
                                   value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                                   required
                                   autofocus>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email"
                                   id="email"
                                   name="email"
                                   class="form-control"
                                   placeholder="Email Address"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                   required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password"
                                   id="password"
                                   name="password"
                                   class="form-control"
                                   placeholder="Minimum 6 characters"
                                   required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-gims-primary w-100 py-2 fw-semibold" id="login-btn">
                        <i class="bi bi-person-check"></i> Register Admin
                    </button>
                </form>
            <?php else: ?>
                <form method="POST" action="index.php" id="login-form" novalidate>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email"
                                   id="email"
                                   name="email"
                                   class="form-control"
                                   placeholder="Email Address"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                   required
                                   autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password"
                                   id="password"
                                   name="password"
                                   class="form-control"
                                   placeholder="Enter password"
                                   required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-gims-primary w-100 py-2 fw-semibold" id="login-btn">
                        <i class="bi bi-box-arrow-in-right"></i> Sign In
                    </button>

                </form>
            <?php endif; ?>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
var loginForm = document.getElementById('login-form');
if (loginForm) {
    loginForm.addEventListener('submit', function(event) {
        var emailInput = document.getElementById('email');
        var passInput  = document.getElementById('password');
        if (emailInput && passInput) {
            if (emailInput.value.trim() === '' || passInput.value === '') {
                event.preventDefault();
                var errorDiv = document.getElementById('login-error');
                if (!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.id = 'login-error';
                    errorDiv.className = 'alert alert-danger';
                    loginForm.insertBefore(errorDiv, loginForm.firstChild);
                }
                errorDiv.innerHTML = '<i class="bi bi-exclamation-circle"></i> Please fill in all required fields.';
            }
        }
    });
}
</script>

</body>
</html>
