<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: ' . (strpos($_SERVER['PHP_SELF'], '/php/') !== false ? '' : 'php/') . 'index.php');
    exit;
}

if (isset($required_role) && $required_role === 'admin' && $_SESSION['role'] !== 'admin') {
    $_SESSION['flash_error'] = 'You do not have permission to access that page.';
    header('Location: dashboard.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) . '">';
}

function verify_csrf(): void {
    $submitted = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $submitted)) {
        http_response_code(403);
        die('<h3 style="font-family:sans-serif;color:#dc2626;padding:2rem;">Security check failed. Please go back and try again.</h3>');
    }
}
