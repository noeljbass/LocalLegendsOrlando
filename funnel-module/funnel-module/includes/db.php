<?php
require_once __DIR__ . '/functions.php';
function db(): PDO {
    static $pdo;
    if ($pdo instanceof PDO) { return $pdo; }
    $dsn = 'mysql:host=' . config('db.host') . ';dbname=' . config('db.name') . ';charset=' . config('db.charset', 'utf8mb4');
    $pdo = new PDO($dsn, config('db.user'), config('db.pass'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
