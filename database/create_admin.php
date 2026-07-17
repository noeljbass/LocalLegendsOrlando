<?php
/**
 * Run from the command line after importing schema.sql:
 * php database/create_admin.php "Editor Name" editor@example.com "a-long-unique-password"
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('This setup utility is CLI-only.'); }
if ($argc !== 4) {
    fwrite(STDERR, "Usage: php database/create_admin.php \"Name\" email@example.com \"password\"\n");
    exit(1);
}
require_once __DIR__ . '/../config.php';
try {
    $statement = db()->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?,? ,?,'admin')");
    $statement->execute([$argv[1], strtolower(trim($argv[2])), password_hash($argv[3], PASSWORD_DEFAULT)]);
    fwrite(STDOUT, "Admin account created for {$argv[2]}.\n");
} catch (PDOException $exception) {
    fwrite(STDERR, "Could not create the admin account. Confirm database configuration and use a unique email.\n");
    exit(1);
}
