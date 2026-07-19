<?php
require '../includes/auth.php';
prevent_form_caching();
$sent = false; $error = ''; $submissionReference = ''; $values = [];

start_session();
if (isset($_SESSION['feature_application_success'])) {
    $sent = true;
    $submissionReference = (string) $_SESSION['feature_application_success'];
    unset($_SESSION['feature_application_success']);
}
$values = $_SESSION['feature_application_draft'] ?? $values;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        guard_post_payload();
        verify_csrf(true);
        if (!empty($_POST['topic_extra']) || !empty($_POST['company'])) throw new RuntimeException('We could not submit your story right now. Please refresh this page and try again.');
        enforce_form_rate_limit('feature_application');
        foreach (['business_name', 'owner_name', 'email', 'phone', 'website', 'social_links', 'message'] as $field) {
            $values[$field] = is_string($_POST[$field] ?? null) ? trim($_POST[$field]) : '';
        }
        if ($values['business_name'] === '' || $values['owner_name'] === '' || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Please provide your business name, your name, and a valid email address.');
        if (mb_strlen($values['business_name']) > 180 || mb_strlen($values['owner_name']) > 150 || mb_strlen($values['email']) > 190 || mb_strlen($values['phone']) > 40 || mb_strlen($values['website']) > 255) throw new RuntimeException('One of your responses is too long. Please shorten it and try again.');
        $stmt = db()->prepare('INSERT INTO submissions (business_name,owner_name,email,phone,website,social_links,message) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([$values['business_name'], $values['owner_name'], $values['email'], $values['phone'], $values['website'], $values['social_links'], $values['message']]);
        $submissionReference = (string) db()->lastInsertId();
        record_form_submission('feature_application');

        if (!send_site_mail(ADMIN_EMAIL, 'New Local Legends feature application', 'A new application has been received from ' . $values['business_name'] . '.', $values['email'])) {
            error_log('Feature application notification email failed for submission #' . $submissionReference . '.');
        }

        start_session();
        unset($_SESSION['feature_application_draft']);
        $_SESSION['feature_application_success'] = $submissionReference;
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'), true, 303);
        exit;
    } catch (Throwable $exception) {
        $_SESSION['feature_application_draft'] = $values;
        if ($exception instanceof PDOException) {
            error_log('Feature application database failure: ' . $exception->getMessage());
            $submissionReference = queue_form_fallback('feature-application', $values);
            if ($submissionReference !== null) {
                unset($_SESSION['feature_application_draft']);
                $_SESSION['feature_application_success'] = $submissionReference;
                header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'), true, 303);
                exit;
            }
        }
        $error = $exception instanceof PDOException ? 'We could not save your story right now. Please try again soon.' : $exception->getMessage();
    }
}
$pageTitle='Get Featured | Local Legends Orlando'; require '../includes/header.php';
?>
<section class="form-page"><p class="eyebrow">Share your good work</p><h1>Could your business be a Local Legend?</h1><p>Tell us a little about what you’re building. Every feature begins with a real conversation.</p><?php if ($sent): ?><div class="notice" role="status" data-clear-draft-key="feature-application"><strong>Thank you for sharing your story.</strong><br>We saved your submission<?php if ($submissionReference !== ''): ?> (reference #<?= e($submissionReference) ?>)<?php endif; ?>. Our team will be in touch soon.</div><?php else: ?><?php if ($error): ?><p class="form-error" role="alert"><?=e($error)?></p><?php endif; ?><form class="contact-form" method="post" data-submission-form data-draft-form="feature-application"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><label class="honeypot" aria-hidden="true">Leave this field empty<input name="topic_extra" tabindex="-1" autocomplete="off"></label><label>Business name<input required maxlength="180" name="business_name" value="<?= e($values['business_name'] ?? '') ?>" autocomplete="organization"></label><label>Owner name<input required maxlength="150" name="owner_name" value="<?= e($values['owner_name'] ?? '') ?>" autocomplete="name"></label><label>Email<input required maxlength="190" type="email" name="email" value="<?= e($values['email'] ?? '') ?>" autocomplete="email"></label><label>Phone <small>(optional)</small><input maxlength="40" name="phone" type="tel" value="<?= e($values['phone'] ?? '') ?>" autocomplete="tel"></label><label>Website <small>(optional)</small><input maxlength="255" name="website" type="text" value="<?= e($values['website'] ?? '') ?>" placeholder="yourbusiness.com" autocomplete="url"></label><label>Social media links <small>(optional)</small><textarea name="social_links" rows="2" placeholder="Instagram, Facebook, LinkedIn…"><?= e($values['social_links'] ?? '') ?></textarea></label><label>What should we know about your story? <small>(optional)</small><textarea name="message" rows="5"><?= e($values['message'] ?? '') ?></textarea></label><button class="button">Submit your story <span>→</span></button></form><?php endif;?></section>
<?php require '../includes/footer.php'; ?>
