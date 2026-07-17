<?php
require '../includes/auth.php';

const INTERVIEW_ALLOWED_MIME = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

function interview_upload(string $field, string $type, int $interviewId): void {
    $files = $_FILES[$field] ?? null;
    if (!$files || !is_array($files['name'])) return;
    foreach ($files['name'] as $index => $originalName) {
        if ($files['error'][$index] === UPLOAD_ERR_NO_FILE) continue;
        if ($files['error'][$index] !== UPLOAD_ERR_OK || $files['size'][$index] > 5 * 1024 * 1024) continue;
        $temp = $files['tmp_name'][$index];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temp);
        if (!isset(INTERVIEW_ALLOWED_MIME[$mime])) continue;
        $fileName = bin2hex(random_bytes(16)) . '.' . INTERVIEW_ALLOWED_MIME[$mime];
        if (!move_uploaded_file($temp, __DIR__ . '/../uploads/' . $fileName)) continue;
        $media = db()->prepare('INSERT INTO media_uploads (file_name, original_name, mime_type, file_size, alt_text) VALUES (?, ?, ?, ?, ?)');
        $media->execute([$fileName, basename($originalName), $mime, $files['size'][$index], $type . ' for interview']);
        db()->prepare('INSERT INTO interview_media (interview_id, media_id, media_type) VALUES (?, ?, ?)')->execute([$interviewId, db()->lastInsertId(), $type]);
    }
}

$submitted = false; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        enforce_form_rate_limit('feature_interview', 120);
        $fields = ['business_name','owner_name','email','phone','website','social_links','story','origin_story','uniqueness','biggest_challenge','proudest_achievement','entrepreneur_advice','excited_about'];
        $values = array_map(fn($field) => trim($_POST[$field] ?? ''), $fields);
        if (!$values[0] || !$values[1] || !filter_var($values[2], FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Please complete your business name, name, and a valid email address.');
        $statement = db()->prepare('INSERT INTO interview_submissions (' . implode(',', $fields) . ') VALUES (' . implode(',', array_fill(0, count($fields), '?')) . ')');
        $statement->execute($values); $interviewId = (int) db()->lastInsertId();
        interview_upload('logo', 'logo', $interviewId); interview_upload('owner_photo', 'owner_photo', $interviewId);
        interview_upload('team_photo', 'team_photo', $interviewId); interview_upload('business_photos', 'business_photo', $interviewId);
        send_site_mail(ADMIN_EMAIL, 'New Local Legends interview', 'A completed feature interview is ready to review.', $values[2]);
        $submitted = true;
    } catch (Throwable $exception) { $error = $exception->getMessage() ?: 'We could not submit your interview. Please try again.'; }
}
$pageTitle = 'Your Local Legends Feature Interview'; $robots = 'noindex,nofollow'; require '../includes/header.php';
?>
<section class="interview-shell"><div class="interview-intro"><p class="eyebrow">Your Local Legends feature</p><h1>Tell us the story behind your work.</h1><p>Thank you for inviting us in. This guided interview helps us write a thoughtful, accurate feature about your business.</p><p class="interview-note">Your draft is saved only when you submit this form. Please set aside about 10–15 minutes to complete it.</p></div><?php if ($submitted): ?><div class="notice"><strong>Thank you—we have your interview.</strong><br>Our editorial team will review it with care and follow up if we need anything else.</div><?php else: ?><form class="interview-form" method="post" enctype="multipart/form-data" data-interview-form><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><div class="interview-progress" aria-label="Interview progress"><span class="active">1. Business</span><span>2. Your story</span><span>3. Photos</span><span>4. Review</span></div><fieldset data-step><legend>Business information</legend><label>Business name<input required name="business_name"></label><label>Your name<input required name="owner_name"></label><label>Email address<input required name="email" type="email"></label><label>Phone number<input name="phone" type="tel"></label><label>Website<input name="website" type="url"></label><label>Social media links<textarea name="social_links" rows="3" placeholder="Instagram, Facebook, LinkedIn…"></textarea></label></fieldset><fieldset data-step hidden><legend>Your story</legend><label>Tell us your story.<textarea required name="story" rows="5"></textarea></label><label>How did your business begin?<textarea required name="origin_story" rows="5"></textarea></label><label>What makes your business unique?<textarea required name="uniqueness" rows="5"></textarea></label><label>What has been your biggest challenge?<textarea required name="biggest_challenge" rows="5"></textarea></label><label>What achievement are you most proud of?<textarea required name="proudest_achievement" rows="5"></textarea></label><label>What advice would you give aspiring entrepreneurs?<textarea required name="entrepreneur_advice" rows="5"></textarea></label><label>What are you excited about right now?<textarea required name="excited_about" rows="5"></textarea></label></fieldset><fieldset data-step hidden><legend>Help us show your world</legend><p>Upload JPG, PNG, or WebP files up to 5 MB each. Please only share images you have permission to use.</p><label>Logo<input name="logo[]" type="file" accept="image/jpeg,image/png,image/webp"></label><label>Owner photo<input name="owner_photo[]" type="file" accept="image/jpeg,image/png,image/webp"></label><label>Team photo<input name="team_photo[]" type="file" accept="image/jpeg,image/png,image/webp"></label><label>Business photos<input name="business_photos[]" type="file" accept="image/jpeg,image/png,image/webp" multiple></label></fieldset><fieldset data-step hidden><legend>Review and submit</legend><p>Thank you for taking the time to share your work. When you submit, our team receives your answers and images for editorial review.</p><p class="interview-note">By submitting, you confirm that the information is accurate and that you have permission to share any uploaded images.</p><label class="checkbox"><input required type="checkbox"> I confirm the above.</label></fieldset><p class="form-error" data-interview-error <?= $error ? '' : 'hidden' ?>><?= e($error) ?></p><div class="interview-actions"><button type="button" class="text-link" data-previous hidden>← Previous</button><button type="button" class="button" data-next>Continue <span>→</span></button><button type="submit" class="button" data-submit hidden>Submit interview <span>→</span></button></div></form><?php endif; ?></section>
<?php require '../includes/footer.php'; ?>
