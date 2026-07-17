<?php
require_once __DIR__ . '/functions.php';
security_headers();

function start_session(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
        session_start();
    }
}

function admin_user(): ?array {
    start_session();
    return $_SESSION['admin'] ?? null;
}

function require_admin(): void {
    if (!admin_user()) {
        header('Location: ' . url('admin/login.php'));
        exit;
    }
}

function csrf_token(): string {
    start_session();
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function verify_csrf(): void {
    start_session();
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Invalid form request. Please try again.');
    }
}
