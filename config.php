<?php
/** Configure these values in the IONOS hosting control panel. */
define('SITE_NAME', 'Local Legends Orlando');
define('SITE_URL', rtrim(getenv('SITE_URL') ?: 'https://locallegendsorlando.com', '/'));
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'local_legends_orlando');
define('DB_USER', getenv('DB_USER') ?: '');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('ADMIN_EMAIL', getenv('ADMIN_EMAIL') ?: 'hello@locallegendsorlando.com');

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
