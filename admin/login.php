<?php
require_once __DIR__ . '/../includes/auth.php';
if (admin_user()) { header('Location: ' . url('admin/')); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $statement = db()->prepare("SELECT id, name, email, password_hash FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
    $statement->execute([trim($_POST['email'] ?? '')]);
    $user = $statement->fetch();
    if ($user && password_verify($_POST['password'] ?? '', $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin'] = ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email']];
        header('Location: ' . url('admin/'));
        exit;
    }
    $error = 'We could not sign you in with those details.';
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Admin sign in | Local Legends Orlando</title><link rel="stylesheet" href="<?= url('assets/css/site.css') ?>"></head><body><main class="admin-login"><a class="brand" href="<?=url()?>"><span>Local Legends</span><strong>Orlando</strong></a><h1>Welcome back</h1><p>Sign in to manage your publication.</p><?php if ($error): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?><form class="contact-form" method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><label>Email<input name="email" type="email" autocomplete="email" required></label><label>Password<input name="password" type="password" autocomplete="current-password" required></label><button class="button">Sign in <span>→</span></button></form></main></body></html>
