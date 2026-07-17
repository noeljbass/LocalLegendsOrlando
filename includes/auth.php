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
    // Form pages can be served through host-level caches, and some shared
    // hosts do not retain PHP sessions consistently. A signed token lets a
    // valid form survive that boundary without accepting an unsigned value.
    $timestamp = (string) time();
    $nonce = bin2hex(random_bytes(16));
    $signature = hash_hmac('sha256', $timestamp . '.' . $nonce, csrf_secret());
    return $timestamp . '.' . $nonce . '.' . $signature;
}

function verify_csrf(): void {
    start_session();
    $submittedToken = $_POST['csrf'] ?? '';
    $sessionToken = $_SESSION['csrf'] ?? '';
    $cookieToken = $_COOKIE['csrf_token'] ?? '';
    $matchesSession = is_string($submittedToken) && is_string($sessionToken) && $sessionToken !== '' && hash_equals($sessionToken, $submittedToken);
    $matchesCookie = is_string($submittedToken) && is_string($cookieToken) && $cookieToken !== '' && hash_equals($cookieToken, $submittedToken);
    $matchesSignedToken = is_string($submittedToken) && valid_signed_csrf_token($submittedToken);

    if (!$matchesSession && !$matchesCookie && !$matchesSignedToken) {
        http_response_code(403);
        exit('Invalid form request. Please try again.');
    }
}

function csrf_secret(): string {
    if (CSRF_SECRET === '') throw new RuntimeException('CSRF_SECRET must be configured.');
    return CSRF_SECRET;
}

function valid_signed_csrf_token(string $token): bool {
    $parts = explode('.', $token);
    if (count($parts) !== 3 || !ctype_digit($parts[0]) || !ctype_xdigit($parts[1]) || strlen($parts[1]) !== 32 || !ctype_xdigit($parts[2]) || strlen($parts[2]) !== 64) return false;

    [$timestamp, $nonce, $signature] = $parts;
    if ((int) $timestamp < time() - 604800 || (int) $timestamp > time() + 300) return false;
    $expectedSignature = hash_hmac('sha256', $timestamp . '.' . $nonce, csrf_secret());
    return hash_equals($expectedSignature, $signature);
}
