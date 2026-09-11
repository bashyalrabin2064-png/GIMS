<?php
$host     = 'localhost';
$dbname   = 'gims';
$username = 'root';
$password = '';

$admin_name     = 'Rabin Bashyal';
$admin_email    = 'bashyalrabin@gmail.com';
$admin_password = 'rabinbsl';

echo '<!DOCTYPE html><html><head><meta charset="utf-8">
<title>GIMS Setup</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head><body class="bg-light"><div class="container py-5" style="max-width:700px">';
echo '<h2 class="mb-4">🛒 GIMS — One-Click Setup</h2>';

$errors   = [];
$messages = [];

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $messages[] = '✅ Connected to MySQL successfully.';

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");
    $messages[] = '✅ Database "gims" created / selected.';

    $schemaFile = __DIR__ . '/../sql/schema.sql';
    if (!file_exists($schemaFile)) {
        $errors[] = '❌ sql/schema.sql not found. Make sure the file exists.';
    } else {
        $schemaSql = file_get_contents($schemaFile);
        $schemaSql = preg_replace('/--.*\n/', '', $schemaSql);
        $schemaSql = preg_replace('/\/\*.*?\*\//s', '', $schemaSql);

        $queries = array_filter(array_map('trim', explode(';', $schemaSql)));
        foreach ($queries as $query) {
            if (!empty($query)) {
                $pdo->exec($query);
            }
        }
        $messages[] = '✅ Schema tables created successfully.';
    }

    $seedFile = __DIR__ . '/../sql/seed.sql';
    if (!file_exists($seedFile)) {
        $errors[] = '❌ sql/seed.sql not found.';
    } else {
        $seedSql    = file_get_contents($seedFile);
        $statements = array_filter(
            array_map('trim', explode(';', $seedSql))
        );
        foreach ($statements as $statement) {
            if ($statement !== '' && stripos($statement, 'NOTE') !== 0) {
                try {
                    $pdo->exec($statement);
                } catch (PDOException $seedError) {
                    if ($seedError->getCode() !== '23000') {
                        throw $seedError;
                    }
                }
            }
        }
        $messages[] = '✅ Seed data inserted.';
    }

    $pdo->exec("DELETE FROM purchases");
    $pdo->exec("DELETE FROM sales");
    $pdo->exec("DELETE FROM products");
    $messages[] = '✅ All products, purchases, and sales deleted — fresh start.';

    $pdo->exec("DELETE FROM users WHERE role = 'staff'");
    $messages[] = '✅ All staff user accounts removed.';

    $checkAdmin = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $checkAdmin->execute([$admin_email]);
    $existingAdmin = $checkAdmin->fetch();

    if ($existingAdmin) {
        $hashedAdminPassword = password_hash($admin_password, PASSWORD_DEFAULT);
        $updateAdmin = $pdo->prepare("UPDATE users SET full_name = ?, password = ?, role = 'admin' WHERE email = ?");
        $updateAdmin->execute([$admin_name, $hashedAdminPassword, $admin_email]);
        $messages[] = '✅ Admin updated: bashyalrabin@gmail.com / rabinbsl';
    } else {
        $pdo->exec("DELETE FROM users");
        $hashedAdminPassword = password_hash($admin_password, PASSWORD_DEFAULT);
        $insertAdmin = $pdo->prepare(
            "INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'admin')"
        );
        $insertAdmin->execute([$admin_name, $admin_email, $hashedAdminPassword]);
        $messages[] = '✅ Admin created: bashyalrabin@gmail.com / rabinbsl';
    }

} catch (PDOException $exception) {
    $errors[] = '❌ Database error: ' . $exception->getMessage();
}

foreach ($messages as $msg) {
    echo '<div class="alert alert-success py-2">' . htmlspecialchars($msg) . '</div>';
}
foreach ($errors as $err) {
    echo '<div class="alert alert-danger py-2">' . htmlspecialchars($err) . '</div>';
}

if (empty($errors)) {
    echo '<div class="alert alert-info mt-3">
        <strong>Setup complete!</strong><br>
        <a href="index.php" class="btn btn-success mt-2">Go to Login →</a>
        <p class="mt-3 mb-0 text-muted small">
            Admin Credentials:<br>
            Email: <code>bashyalrabin@gmail.com</code><br>
            Password: <code>rabinbsl</code>
        </p>
    </div>
    <div class="alert alert-warning mt-2">
        <strong>Security note:</strong> Delete or rename <code>setup.php</code> after setup.
    </div>';
}

echo '</div></body></html>';
