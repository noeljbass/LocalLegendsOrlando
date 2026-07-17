<?php
require_once __DIR__ . '/../includes/auth.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'status') {
        $status = in_array($_POST['status'] ?? '', ['new', 'reviewing', 'approved', 'declined'], true)
            ? $_POST['status']
            : 'new';
        db()->prepare('UPDATE submissions SET status=? WHERE id=?')->execute([$status, $id]);
        header('Location: ' . url('admin/submissions.php'));
        exit;
    }

    if ($action === 'invite') {
        $statement = db()->prepare('SELECT business_name, owner_name, email FROM submissions WHERE id=?');
        $statement->execute([$id]);
        $submission = $statement->fetch();

        if (!$submission || !filter_var($submission['email'], FILTER_VALIDATE_EMAIL)) {
            header('Location: ' . url('admin/submissions.php?notice=invite-error'));
            exit;
        }

        $interviewUrl = url('feature-interview/');
        $message = "Hello {$submission['owner_name']},\n\n"
            . "We would love to learn more about {$submission['business_name']} for a possible Local Legends Orlando feature. "
            . "Please complete our short interview when you are ready:\n{$interviewUrl}\n\n"
            . "Thank you,\nLocal Legends Orlando";

        try {
            $sent = send_site_mail(
                $submission['email'],
                'You are invited to share your Local Legends story',
                $message
            );
            if ($sent) {
                db()->prepare('UPDATE submissions SET invited_at=NOW() WHERE id=?')->execute([$id]);
            }
        } catch (Throwable $exception) {
            $sent = false;
        }

        header('Location: ' . url('admin/submissions.php?notice=' . ($sent ? 'invited' : 'invite-error')));
        exit;
    }

    header('Location: ' . url('admin/submissions.php?notice=invalid-action'));
    exit;
}

$submissions = db()->query('SELECT * FROM submissions ORDER BY created_at DESC')->fetchAll();
$queuedSubmissions = queued_form_fallbacks('feature-application');
require __DIR__ . '/partials/header.php';
?>
<div class="admin-heading"><div><p class="eyebrow">Community</p><h1>Feature submissions</h1></div></div>
<?php if (($_GET['notice'] ?? '') === 'invited'): ?>
    <p class="notice">Interview invitation sent.</p>
<?php elseif (($_GET['notice'] ?? '') === 'invite-error'): ?>
    <p class="form-error">We could not send the interview invitation. Please try again.</p>
<?php elseif (($_GET['notice'] ?? '') === 'invalid-action'): ?>
    <p class="form-error">That submission action is not available.</p>
<?php endif; ?>
<?php if ($queuedSubmissions): ?>
<section class="admin-panel submission-list"><h2>Recovered submissions</h2><p>These were safely queued while the database was unavailable. Add them to the database when service is restored, then remove the recovery file from the server.</p><?php foreach ($queuedSubmissions as $item): $values = $item['values']; ?><article><div><h2><?= e($values['business_name'] ?? 'Unknown business') ?></h2><p><strong><?= e($values['owner_name'] ?? '') ?></strong> · <a href="mailto:<?= e($values['email'] ?? '') ?>"><?= e($values['email'] ?? '') ?></a></p><p><?= nl2br(e($values['message'] ?? '')) ?></p><p><small>Recovery reference <?= e($item['reference'] ?? '') ?> · <?= e($item['created_at'] ?? '') ?></small></p></div></article><?php endforeach; ?></section>
<?php endif; ?>
<section class="admin-panel submission-list">
    <?php foreach ($submissions as $item): ?>
        <article>
            <div>
                <h2><?= e($item['business_name']) ?></h2>
                <p><strong><?= e($item['owner_name']) ?></strong> · <a href="mailto:<?= e($item['email']) ?>"><?= e($item['email']) ?></a></p>
                <p><?= nl2br(e($item['message'])) ?></p>
                <?php if ($item['website']): ?><a href="<?= e($item['website']) ?>" rel="noopener" target="_blank">Website ↗</a><?php endif; ?>
                <?php if ($item['invited_at']): ?><p><small>Interview invitation sent <?= e(date('M j, Y g:i a', strtotime($item['invited_at']))) ?>.</small></p><?php endif; ?>
            </div>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= e((string) $item['id']) ?>">
                <label>Status<select name="status"><?php foreach (['new', 'reviewing', 'approved', 'declined'] as $status): ?><option value="<?= $status ?>" <?= $status === $item['status'] ? 'selected' : '' ?>><?= ucfirst($status) ?></option><?php endforeach; ?></select></label>
                <button name="action" value="status">Update</button>
                <button class="button" name="action" value="invite">Send interview invitation</button>
            </form>
        </article>
    <?php endforeach; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
