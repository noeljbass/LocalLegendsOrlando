<?php
require_once __DIR__ . '/db.php';
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
function current_admin(): ?array {
    if (empty($_SESSION['admin_id'])) { return null; }
    $stmt = db()->prepare('SELECT id,email,name FROM admin_users WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);
    return $stmt->fetch() ?: null;
}
function require_admin(): void { if (!current_admin()) { redirect('login.php'); } }
function login_admin(string $email, string $password): bool {
    $stmt = db()->prepare('SELECT * FROM admin_users WHERE email = ?');
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password_hash'])) { session_regenerate_id(true); $_SESSION['admin_id'] = $admin['id']; return true; }
    return false;
}
function logout_admin(): void { $_SESSION = []; session_destroy(); }
