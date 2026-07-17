<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

function interview_draft_content(array $interview): string {
    $sections = [
        'Their story' => $interview['story'],
        'How it began' => $interview['origin_story'],
        'What makes them unique' => $interview['uniqueness'],
        'A challenge they have overcome' => $interview['biggest_challenge'],
        'A proud moment' => $interview['proudest_achievement'],
        'Advice for aspiring entrepreneurs' => $interview['entrepreneur_advice'],
        'What is next' => $interview['excited_about'],
    ];
    $content = '';
    foreach ($sections as $heading => $answer) {
        if ($answer !== '') $content .= '<h2>' . e($heading) . '</h2>\n<p>' . nl2br(e($answer)) . '</p>\n';
    }
    return $content;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(); $id = (int) ($_POST['id'] ?? 0);
    $statement = db()->prepare('SELECT * FROM interview_submissions WHERE id=?'); $statement->execute([$id]);
    $interview = $statement->fetch();
    if (!$interview) { http_response_code(404); exit('Interview not found.'); }
    if ($_POST['action'] === 'delete') {
        db()->prepare('DELETE FROM interview_submissions WHERE id=?')->execute([$id]);
        header('Location: ' . url('admin/interviews.php?notice=deleted')); exit;
    }
    if ($_POST['action'] === 'convert' && !$interview['article_id']) {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $interview['business_name']), '-'));
        $slug .= '-' . $interview['id'];
        $excerpt = excerpt($interview['story'] ?: $interview['origin_story'], 180);
        $insert = db()->prepare('INSERT INTO articles (title,slug,excerpt,content,author_id,seo_title,meta_description,status) VALUES (?,?,?,?,?,?,?,?)');
        $insert->execute([$interview['business_name'] . ': A Local Legends Story', $slug, $excerpt, interview_draft_content($interview), admin_user()['id'], $interview['business_name'] . ' | Local Legends Orlando', $excerpt, 'draft']);
        $articleId = (int) db()->lastInsertId();
        db()->prepare("UPDATE interview_submissions SET status='converted', article_id=? WHERE id=?")->execute([$articleId, $id]);
        header('Location: ' . url('admin/article.php?id=' . $articleId)); exit;
    }
    $status = in_array($_POST['status'] ?? '', ['new','reviewing','declined'], true) ? $_POST['status'] : 'new';
    db()->prepare('UPDATE interview_submissions SET status=? WHERE id=?')->execute([$status, $id]);
    header('Location: ' . url('admin/interviews.php')); exit;
}
$interviews = db()->query('SELECT * FROM interview_submissions ORDER BY created_at DESC')->fetchAll();
require __DIR__ . '/partials/header.php';
?>
<div class="admin-heading"><div><p class="eyebrow">Feature interviews</p><h1>Stories waiting to be shaped.</h1></div></div>
<?php if (($_GET['notice'] ?? '') === 'deleted'): ?><p class="notice">Interview deleted.</p><?php endif; ?>
<section class="admin-panel submission-list"><?php if (!$interviews): ?><p>No interview submissions have arrived yet.</p><?php endif; ?><?php foreach ($interviews as $item): ?><article><div><h2><?= e($item['business_name']) ?></h2><p><strong><?= e($item['owner_name']) ?></strong> · <a href="mailto:<?= e($item['email']) ?>"><?= e($item['email']) ?></a></p><p><?= e(excerpt($item['story'] ?: $item['origin_story'], 240)) ?></p><?php if ($item['article_id']): ?><a href="<?= url('admin/article.php?id=' . $item['article_id']) ?>">Open article draft →</a><?php endif; ?></div><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= e((string) $item['id']) ?>"><?php if (!$item['article_id']): ?><label>Status<select name="status"><?php foreach(['new','reviewing','declined'] as $status): ?><option value="<?= $status ?>" <?= $status === $item['status'] ? 'selected' : '' ?>><?= ucfirst($status) ?></option><?php endforeach; ?></select></label><button name="action" value="status">Update</button><button class="button" name="action" value="convert">Create article draft</button><?php else: ?><span class="status published">Converted</span><?php endif; ?><button class="delete-button" name="action" value="delete" onclick="return confirm('Delete this interview? This cannot be undone.')">Delete interview</button></form></article><?php endforeach; ?></section>
<?php require __DIR__ . '/partials/footer.php'; ?>
