<?php
require '../includes/auth.php';
prevent_form_caching();

const INTERVIEW_ALLOWED_MIME = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
const INTERVIEW_MAX_UPLOAD_SIZE = 5242880;

function interview_upload(string $field, string $type, int $interviewId, array &$uploadedFiles): void {
    $files = $_FILES[$field] ?? null;
    if (!$files || !is_array($files['name'])) return;
    foreach ($files['name'] as $index => $originalName) {
        if ($files['error'][$index] === UPLOAD_ERR_NO_FILE) continue;
        if ($files['error'][$index] !== UPLOAD_ERR_OK || $files['size'][$index] > INTERVIEW_MAX_UPLOAD_SIZE) throw new RuntimeException('Each photo must be a JPG, PNG, or WebP under 5 MB.');
        $temp = $files['tmp_name'][$index];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temp);
        if (!isset(INTERVIEW_ALLOWED_MIME[$mime])) throw new RuntimeException('Each photo must be a JPG, PNG, or WebP under 5 MB.');
        $fileName = bin2hex(random_bytes(16)) . '.' . INTERVIEW_ALLOWED_MIME[$mime];
        $path = __DIR__ . '/../uploads/' . $fileName;
        if (!move_uploaded_file($temp, $path)) throw new RuntimeException('We could not save one of your photos. Your written answers are still saved in this browser; please try again.');
        $uploadedFiles[] = $path;
        $media = db()->prepare('INSERT INTO media_uploads (file_name, original_name, mime_type, file_size, alt_text) VALUES (?, ?, ?, ?, ?)');
        $media->execute([$fileName, basename($originalName), $mime, $files['size'][$index], $type . ' for interview']);
        db()->prepare('INSERT INTO interview_media (interview_id, media_id, media_type) VALUES (?, ?, ?)')->execute([$interviewId, db()->lastInsertId(), $type]);
    }
}

$fields = ['business_name', 'owner_name', 'email', 'phone', 'website', 'social_links', 'story', 'origin_story', 'uniqueness', 'biggest_challenge', 'proudest_achievement', 'entrepreneur_advice', 'excited_about'];
$values = array_fill_keys($fields, '');
$submitted = false;
$error = '';
start_session();
if (isset($_SESSION['feature_interview_success'])) {
    $submitted = true;
    unset($_SESSION['feature_interview_success']);
} elseif (isset($_SESSION['feature_interview_draft']) && is_array($_SESSION['feature_interview_draft'])) {
    $values = array_replace($values, $_SESSION['feature_interview_draft']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadedFiles = [];
    $databaseTransaction = false;
    try {
        verify_csrf();
        enforce_form_rate_limit('feature_interview', 120);
        foreach ($fields as $field) $values[$field] = is_string($_POST[$field] ?? null) ? trim($_POST[$field]) : '';
        if ($values['business_name'] === '' || $values['owner_name'] === '' || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Please complete your business name, name, and a valid email address.');
        if (mb_strlen($values['business_name']) > 180 || mb_strlen($values['owner_name']) > 150 || mb_strlen($values['email']) > 190 || mb_strlen($values['phone']) > 40 || mb_strlen($values['website']) > 255) throw new RuntimeException('One of your responses is too long. Please shorten it and try again.');

        db()->beginTransaction();
        $databaseTransaction = true;
        $statement = db()->prepare('INSERT INTO interview_submissions (' . implode(',', $fields) . ') VALUES (' . implode(',', array_fill(0, count($fields), '?')) . ')');
        $statement->execute(array_values($values));
        $interviewId = (int) db()->lastInsertId();
        interview_upload('logo', 'logo', $interviewId, $uploadedFiles);
        interview_upload('owner_photo', 'owner_photo', $interviewId, $uploadedFiles);
        interview_upload('team_photo', 'team_photo', $interviewId, $uploadedFiles);
        interview_upload('business_photos', 'business_photo', $interviewId, $uploadedFiles);
        db()->commit();
        record_form_submission('feature_interview');
        if (!send_site_mail(ADMIN_EMAIL, 'New Local Legends interview', 'A completed feature interview is ready to review.', $values['email'])) error_log('Feature interview notification email failed for interview #' . $interviewId . '.');
        unset($_SESSION['feature_interview_draft']);
        $_SESSION['feature_interview_success'] = true;
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'), true, 303);
        exit;
    } catch (Throwable $exception) {
        if ($databaseTransaction && db()->inTransaction()) db()->rollBack();
        foreach ($uploadedFiles as $file) if (is_file($file)) unlink($file);
        $_SESSION['feature_interview_draft'] = $values;
        if ($exception instanceof PDOException) {
            error_log('Feature interview database failure: ' . $exception->getMessage());
            if (queue_form_fallback('feature-interview', $values) !== null) {
                unset($_SESSION['feature_interview_draft']);
                $_SESSION['feature_interview_success'] = true;
                header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'), true, 303);
                exit;
            }
        }
        $error = $exception instanceof PDOException ? 'We could not save your interview right now. Your answers are still saved on this device—please try again shortly.' : ($exception->getMessage() ?: 'We could not submit your interview. Your answers are still saved on this device—please try again.');
    }
}
$pageTitle = 'Your Local Legends Feature Interview'; $robots = 'noindex,nofollow'; require '../includes/header.php';
?>
<section class="interview-shell"><div class="interview-intro"><p class="eyebrow">Your Local Legends feature</p><h1>Tell us the story behind your work.</h1><p>Thank you for inviting us in. This guided interview helps us write a thoughtful, accurate feature about your business.</p><p class="interview-note">Your written answers are saved automatically on this device while you work. Photos need to be selected again if a submission error occurs.</p></div><?php if ($submitted): ?><div class="notice" role="status" data-clear-draft-key="feature-interview"><strong>Thank you—we have your interview.</strong><br>Our editorial team will review it with care and follow up if we need anything else.</div><?php else: ?><form class="interview-form" method="post" enctype="multipart/form-data" data-interview-form data-draft-form="feature-interview"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><div class="interview-progress" aria-label="Interview progress"><span class="active">1. Business</span><span>2. Your story</span><span>3. Photos</span><span>4. Review</span></div><fieldset data-step><legend>Business information</legend><label>Business name<input required maxlength="180" name="business_name" value="<?= e($values['business_name']) ?>"></label><label>Your name<input required maxlength="150" name="owner_name" value="<?= e($values['owner_name']) ?>"></label><label>Email address<input required maxlength="190" name="email" type="email" value="<?= e($values['email']) ?>"></label><label>Phone number<input maxlength="40" name="phone" type="tel" value="<?= e($values['phone']) ?>"></label><label>Website<input maxlength="255" name="website" type="text" value="<?= e($values['website']) ?>" placeholder="yourbusiness.com"></label><label>Social media links<textarea name="social_links" rows="3" placeholder="Instagram, Facebook, LinkedIn…"><?= e($values['social_links']) ?></textarea></label></fieldset><fieldset data-step hidden><legend>Your story</legend><?php foreach (['story' => 'Tell us your story.', 'origin_story' => 'How did your business begin?', 'uniqueness' => 'What makes your business unique?', 'biggest_challenge' => 'What has been your biggest challenge?', 'proudest_achievement' => 'What achievement are you most proud of?', 'entrepreneur_advice' => 'What advice would you give aspiring entrepreneurs?', 'excited_about' => 'What are you excited about right now?'] as $field => $label): ?><label><?= e($label) ?><textarea required name="<?= e($field) ?>" rows="5"><?= e($values[$field]) ?></textarea></label><?php endforeach; ?></fieldset><fieldset data-step hidden><legend>Help us show your world</legend><p>Upload JPG, PNG, or WebP files up to 5 MB each. Please only share images you have permission to use.</p><label>Logo<input name="logo[]" type="file" accept="image/jpeg,image/png,image/webp"></label><label>Owner photo<input name="owner_photo[]" type="file" accept="image/jpeg,image/png,image/webp"></label><label>Team photo<input name="team_photo[]" type="file" accept="image/jpeg,image/png,image/webp"></label><label>Business photos<input name="business_photos[]" type="file" accept="image/jpeg,image/png,image/webp" multiple></label></fieldset><fieldset data-step hidden><legend>Review and submit</legend><p>Thank you for taking the time to share your work. When you submit, our team receives your answers and images for editorial review.</p><p class="interview-note">By submitting, you confirm that the information is accurate and that you have permission to share any uploaded images.</p><label class="checkbox"><input required type="checkbox"> I confirm the above.</label></fieldset><p class="form-error" data-interview-error role="alert" <?= $error ? '' : 'hidden' ?>><?= e($error) ?></p><div class="interview-actions"><button type="button" class="text-link" data-previous hidden>← Previous</button><button type="button" class="button" data-next>Continue <span>→</span></button><button type="submit" class="button" data-submit hidden>Submit interview <span>→</span></button></div></form><?php endif; ?></section>
<?php require '../includes/footer.php'; ?>
