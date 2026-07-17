<?php
require '../includes/auth.php';
prevent_form_caching();
$sent = false; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        if (!empty($_POST['company'])) throw new RuntimeException('We could not submit your story right now.');
        enforce_form_rate_limit('feature_application');
        if (!filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Please provide a valid email address.');
        $stmt = db()->prepare('INSERT INTO submissions (business_name,owner_name,email,phone,website,social_links,message) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([trim($_POST['business_name']), trim($_POST['owner_name']), trim($_POST['email']), trim($_POST['phone'] ?? ''), trim($_POST['website'] ?? ''), trim($_POST['social_links'] ?? ''), trim($_POST['message'] ?? '')]);
        send_site_mail(ADMIN_EMAIL, 'New Local Legends feature application', 'A new application has been received from ' . trim($_POST['business_name']) . '.', trim($_POST['email'])); $sent = true;
    } catch (Throwable $exception) { $error = $exception instanceof PDOException ? 'We could not submit your story right now. Please try again soon.' : $exception->getMessage(); }
}
$pageTitle='Get Featured | Local Legends Orlando'; require '../includes/header.php';
?>
<section class="form-page"><p class="eyebrow">Share your good work</p><h1>Could your business be a Local Legend?</h1><p>Tell us a little about what you’re building. Every feature begins with a real conversation.</p><?php if ($sent): ?><div class="notice"><strong>Thank you for sharing your story.</strong><br>Our team will be in touch soon.</div><?php else: ?><?php if ($error): ?><p class="form-error"><?=e($error)?></p><?php endif; ?><form class="contact-form" method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><label class="honeypot">Company<input name="company" tabindex="-1" autocomplete="off"></label><label>Business name<input required name="business_name"></label><label>Owner name<input required name="owner_name"></label><label>Email<input required type="email" name="email"></label><label>Phone<input name="phone" type="tel"></label><label>Website<input name="website" type="url"></label><label>Social media links<textarea name="social_links" rows="2" placeholder="Instagram, Facebook, LinkedIn…"></textarea></label><label>What should we know about your story?<textarea name="message" rows="5"></textarea></label><button class="button">Submit your story <span>→</span></button></form><?php endif;?></section>
<?php require '../includes/footer.php'; ?>
