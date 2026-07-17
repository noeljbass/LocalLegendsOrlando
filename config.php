<?php
/** Configure values through the IONOS control panel or a root-level .env file. */
function load_environment_file(string $file): void {
    if (!is_readable($file)) return;
    $values = parse_ini_file($file, false, INI_SCANNER_RAW);
    if ($values === false) return;
    foreach ($values as $key => $value) {
        if (getenv($key) === false && is_string($value)) putenv($key . '=' . $value);
    }
}
load_environment_file(__DIR__ . '/.env');

function env(string $key, string $default = ''): string {
    $value = getenv($key);
    return $value === false ? $default : $value;
}

define('SITE_NAME', 'Local Legends Orlando');
define('SITE_URL', rtrim(env('SITE_URL', 'https://locallegendsorlando.com'), '/'));
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'local_legends_orlando'));
define('DB_USER', env('DB_USER'));
define('DB_PASS', env('DB_PASS'));
define('ADMIN_EMAIL', env('ADMIN_EMAIL', 'hello@locallegendsorlando.com'));

function db(): PDO {
    static $pdo;
    if (!$pdo) {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}
