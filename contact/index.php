<?php
require '../includes/auth.php';
prevent_form_caching();
$pageTitle = 'Contact | Local Legends Orlando';
$sent = false; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        if (!empty($_POST['company'])) throw new RuntimeException('Unable to send this message.');
        $name = trim($_POST['name'] ?? ''); $email = trim($_POST['email'] ?? ''); $message = trim($_POST['message'] ?? '');
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '') throw new RuntimeException('Please complete each field with a valid email address.');
        enforce_form_rate_limit('contact');
        send_site_mail(ADMIN_EMAIL, 'New Local Legends contact message', "From: $name\nEmail: $email\n\n$message", $email);
        $sent = true;
    } catch (Throwable $exception) { $error = $exception->getMessage(); }
}
require '../includes/header.php';
?>
<section class="form-page"><p class="eyebrow">Let’s connect</p><h1>Have a local story to share?</h1><p>Whether you have a question, a collaboration idea, or a neighbor we should know, we’d be glad to hear from you.</p><?php if ($sent): ?><div class="notice"><strong>Thanks for reaching out.</strong><br>We’ll be in touch soon.</div><?php else: ?><?php if ($error): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?><form class="contact-form" method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><label class="honeypot">Company<input name="company" tabindex="-1" autocomplete="off"></label><label>Name<input required name="name" autocomplete="name"></label><label>Email<input required type="email" name="email" autocomplete="email"></label><label>Message<textarea required name="message" rows="5"></textarea></label><button class="button">Send message <span>→</span></button></form><?php endif; ?></section>
<?php require '../includes/footer.php'; ?>
