<?php
require '../includes/auth.php';
prevent_form_caching();
$sent = false; $error = ''; $submissionReference = ''; $values = [];

function ensure_article_submission_table(): void {
    db()->exec("CREATE TABLE IF NOT EXISTS article_submissions (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, author_name VARCHAR(150) NOT NULL, author_title VARCHAR(180) NULL, email VARCHAR(190) NOT NULL, website VARCHAR(255) NULL, social_links TEXT NULL, bio TEXT NULL, article_title VARCHAR(255) NOT NULL, article_summary TEXT NULL, answer_expertise TEXT NULL, answer_local TEXT NULL, answer_advice TEXT NULL, answer_resources TEXT NULL, status ENUM('new','reviewing','converted','declined') NOT NULL DEFAULT 'new', article_id BIGINT UNSIGNED NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX article_submission_status (status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}


start_session();
if (isset($_SESSION['article_submission_success'])) {
    $sent = true;
    $submissionReference = (string) $_SESSION['article_submission_success'];
    unset($_SESSION['article_submission_success']);
}
$values = $_SESSION['article_submission_draft'] ?? $values;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        guard_post_payload();
        verify_csrf(true);
        if (!empty($_POST['topic_extra']) || !empty($_POST['company'])) throw new RuntimeException('We could not submit your article right now. Please refresh this page and try again.');
        enforce_form_rate_limit('article_submission');
        foreach (['author_name','author_title','email','website','social_links','bio','article_title','article_summary','answer_expertise','answer_local','answer_advice','answer_resources'] as $field) {
            $values[$field] = is_string($_POST[$field] ?? null) ? trim($_POST[$field]) : '';
        }
        if ($values['author_name'] === '' || !filter_var($values['email'], FILTER_VALIDATE_EMAIL) || $values['article_title'] === '' || $values['answer_expertise'] === '') throw new RuntimeException('Please provide your name, a valid email address, an article title, and your main answer.');
        if (mb_strlen($values['author_name']) > 150 || mb_strlen($values['author_title']) > 180 || mb_strlen($values['email']) > 190 || mb_strlen($values['website']) > 255 || mb_strlen($values['article_title']) > 255) throw new RuntimeException('One of your responses is too long. Please shorten it and try again.');
        ensure_article_submission_table();
        $stmt = db()->prepare('INSERT INTO article_submissions (author_name,author_title,email,website,social_links,bio,article_title,article_summary,answer_expertise,answer_local,answer_advice,answer_resources) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$values['author_name'],$values['author_title'],$values['email'],$values['website'],$values['social_links'],$values['bio'],$values['article_title'],$values['article_summary'],$values['answer_expertise'],$values['answer_local'],$values['answer_advice'],$values['answer_resources']]);
        $submissionReference = (string) db()->lastInsertId();
        record_form_submission('article_submission');

        if (!send_site_mail(ADMIN_EMAIL, 'New Local Legends guest article submission', 'A new guest article has been submitted by ' . $values['author_name'] . ': ' . $values['article_title'] . '.', $values['email'])) {
            error_log('Guest article notification email failed for submission #' . $submissionReference . '.');
        }

        unset($_SESSION['article_submission_draft']);
        $_SESSION['article_submission_success'] = $submissionReference;
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'), true, 303);
        exit;
    } catch (Throwable $exception) {
        $_SESSION['article_submission_draft'] = $values;
        if ($exception instanceof PDOException) {
            error_log('Article submission database failure: ' . $exception->getMessage());
            $submissionReference = queue_form_fallback('article-submission', $values);
            if ($submissionReference !== null) {
                unset($_SESSION['article_submission_draft']);
                $_SESSION['article_submission_success'] = $submissionReference;
                header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'), true, 303);
                exit;
            }
        }
        $error = $exception instanceof PDOException ? 'We could not save your article right now. Please try again soon.' : $exception->getMessage();
    }
}
$pageTitle='Submit an Article | Local Legends Orlando';
$metaDescription='Submit a helpful guest article for Local Legends Orlando review and earn an approved author backlink.';
require '../includes/header.php';
?>
<section class="form-page"><p class="eyebrow">Share your expertise</p><h1>Submit a helpful article for Orlando readers.</h1><p>Answer a few guided questions, tell readers who you are, and include the website you would like us to link to if your article is approved and published.</p><?php if ($sent): ?><div class="notice" role="status" data-clear-draft-key="article-submission"><strong>Thank you for your article submission.</strong><br>We saved your submission<?php if ($submissionReference !== ''): ?> (reference #<?= e($submissionReference) ?>)<?php endif; ?>. Our editorial team will review it before anything is drafted or published.</div><?php else: ?><?php if ($error): ?><p class="form-error" role="alert"><?=e($error)?></p><?php endif; ?><form class="contact-form" method="post" data-submission-form data-draft-form="article-submission"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><label class="honeypot" aria-hidden="true">Leave this field empty<input name="topic_extra" tabindex="-1" autocomplete="off"></label><label>Your name<input required maxlength="150" name="author_name" value="<?= e($values['author_name'] ?? '') ?>" autocomplete="name"></label><label>Your title or company <small>(optional)</small><input maxlength="180" name="author_title" value="<?= e($values['author_title'] ?? '') ?>" placeholder="Founder, designer, advisor…"></label><label>Email<input required maxlength="190" type="email" name="email" value="<?= e($values['email'] ?? '') ?>" autocomplete="email"></label><label>Website for your backlink <small>(optional)</small><input maxlength="255" name="website" type="text" value="<?= e($values['website'] ?? '') ?>" placeholder="https://example.com" autocomplete="url"></label><label>Social links <small>(optional)</small><textarea name="social_links" rows="2" placeholder="LinkedIn, Instagram, portfolio…"><?= e($values['social_links'] ?? '') ?></textarea></label><label>Short author bio <small>(optional)</small><textarea name="bio" rows="3"><?= e($values['bio'] ?? '') ?></textarea></label><label>Proposed article title<input required maxlength="255" name="article_title" value="<?= e($values['article_title'] ?? '') ?>"></label><label>Quick summary <small>(optional)</small><textarea name="article_summary" rows="3"><?= e($values['article_summary'] ?? '') ?></textarea></label><label>What expertise or answer would you like to share?<textarea required name="answer_expertise" rows="6"><?= e($values['answer_expertise'] ?? '') ?></textarea></label><label>Why does this matter to Orlando or Central Florida readers? <small>(optional)</small><textarea name="answer_local" rows="4"><?= e($values['answer_local'] ?? '') ?></textarea></label><label>What practical advice should readers take away? <small>(optional)</small><textarea name="answer_advice" rows="4"><?= e($values['answer_advice'] ?? '') ?></textarea></label><label>Helpful resources or references <small>(optional)</small><textarea name="answer_resources" rows="3" placeholder="Links, citations, or examples we should review."><?= e($values['answer_resources'] ?? '') ?></textarea></label><button class="button">Submit article for review <span>→</span></button></form><?php endif;?></section>
<?php require '../includes/footer.php'; ?>
