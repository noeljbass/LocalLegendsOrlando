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
    $token = $_SESSION['csrf'] ??= bin2hex(random_bytes(32));

    // Keep a same-site double-submit cookie as a fallback for hosts that do not
    // persist PHP sessions reliably between the GET that renders a form and its
    // POST. The session token remains the primary validation mechanism.
    if (($_COOKIE['csrf_token'] ?? '') !== $token) {
        setcookie('csrf_token', $token, [
            'expires' => time() + 7200,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
    }

    return $token;
}

function verify_csrf(): void {
    start_session();
    $submittedToken = $_POST['csrf'] ?? '';
    $sessionToken = $_SESSION['csrf'] ?? '';
    $cookieToken = $_COOKIE['csrf_token'] ?? '';
    $matchesSession = is_string($submittedToken) && is_string($sessionToken) && $sessionToken !== '' && hash_equals($sessionToken, $submittedToken);
    $matchesCookie = is_string($submittedToken) && is_string($cookieToken) && $cookieToken !== '' && hash_equals($cookieToken, $submittedToken);

    if (!$matchesSession && !$matchesCookie) {
        http_response_code(403);
        exit('Invalid form request. Please try again.');
    }
}
