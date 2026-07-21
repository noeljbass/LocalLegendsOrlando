<?php
function config(string $key, $default = null) {
    static $config;
    if ($config === null) {
        $path = __DIR__ . '/config.php';
        if (!file_exists($path)) { $path = dirname(__DIR__) . '/config.example.php'; }
        $config = require $path;
    }
    $value = $config;
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) { return $default; }
        $value = $value[$part];
    }
    return $value;
}
function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function redirect(string $path): void { header('Location: ' . $path); exit; }
function first_name(string $name): string { $parts = preg_split('/\s+/', trim($name)); return $parts[0] ?? ''; }
function log_error_message(string $message): void {
    $dir = dirname(__DIR__) . '/logs';
    if (!is_dir($dir)) { mkdir($dir, 0755, true); }
    error_log('[' . date('c') . '] ' . $message . PHP_EOL, 3, $dir . '/app.log');
}
function client_ip(): ?string { return $_SERVER['REMOTE_ADDR'] ?? null; }
function unsubscribe_url(string $token): string { return rtrim(config('site_url'), '/') . '/public/unsubscribe.php?token=' . urlencode($token); }
function render_template(string $text, array $subscriber, array $client): string {
    $tags = [
        '{{first_name}}' => first_name($subscriber['name'] ?? ''),
        '{{name}}' => $subscriber['name'] ?? '',
        '{{email}}' => $subscriber['email'] ?? '',
        '{{unsubscribe_url}}' => unsubscribe_url($subscriber['unsubscribe_token'] ?? ''),
        '{{client_name}}' => $client['name'] ?? '',
    ];
    return strtr($text, $tags);
}
function require_post(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405); header('Allow: POST'); echo json_encode(['success'=>false,'message'=>'POST required']); exit;
    }
}
