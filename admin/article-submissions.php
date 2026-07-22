<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

function ensure_guest_article_workflow(): void {
    try {
        db()->exec("CREATE TABLE IF NOT EXISTS article_submissions (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, author_name VARCHAR(150) NOT NULL, author_title VARCHAR(180) NULL, email VARCHAR(190) NOT NULL, website VARCHAR(255) NULL, social_links TEXT NULL, bio TEXT NULL, article_title VARCHAR(255) NOT NULL, article_summary TEXT NULL, answer_expertise TEXT NULL, answer_local TEXT NULL, answer_advice TEXT NULL, answer_resources TEXT NULL, status ENUM('new','reviewing','converted','declined') NOT NULL DEFAULT 'new', article_id BIGINT UNSIGNED NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX article_submission_status (status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $columns = array_column(db()->query('SHOW COLUMNS FROM articles')->fetchAll(), 'Field');
        if (!in_array('profile_display_name', $columns, true)) db()->exec('ALTER TABLE articles ADD profile_display_name VARCHAR(180) NULL AFTER business_address');
        if (!in_array('profile_label', $columns, true)) db()->exec('ALTER TABLE articles ADD profile_label VARCHAR(120) NULL AFTER profile_display_name');
        if (!in_array('profile_bio', $columns, true)) db()->exec('ALTER TABLE articles ADD profile_bio TEXT NULL AFTER profile_label');
        if (!in_array('profile_type', $columns, true)) db()->exec("ALTER TABLE articles ADD profile_type ENUM('company','author') NOT NULL DEFAULT 'company' AFTER profile_bio");
        if (!in_array('public_type', $columns, true)) db()->exec("ALTER TABLE articles ADD public_type ENUM('story','article') NOT NULL DEFAULT 'story' AFTER profile_type");
    } catch (Throwable $exception) {}
}

function guest_article_draft_content(array $submission): string {
    $sections = [
        'Expert answer' => $submission['answer_expertise'],
        'Why it matters locally' => $submission['answer_local'],
        'Practical takeaways' => $submission['answer_advice'],
        'Resources to review' => $submission['answer_resources'],
    ];
    $content = '';
    foreach ($sections as $heading => $answer) if (trim((string) $answer) !== '') $content .= '<h2>' . e($heading) . '</h2>\n<p>' . nl2br(e($answer)) . '</p>\n';
    return $content;
}

ensure_guest_article_workflow();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $statement = db()->prepare('SELECT * FROM article_submissions WHERE id=?'); $statement->execute([$id]);
    $submission = $statement->fetch();
    if (!$submission) { http_response_code(404); exit('Article submission not found.'); }
    if ($_POST['action'] === 'delete') {
        db()->prepare('DELETE FROM article_submissions WHERE id=?')->execute([$id]);
        header('Location: ' . url('admin/article-submissions.php?notice=deleted')); exit;
    }
    if ($_POST['action'] === 'convert' && !$submission['article_id']) {
        $slug = slugify($submission['article_title']) . '-' . $submission['id'];
        $excerpt = excerpt($submission['article_summary'] ?: $submission['answer_expertise'], 180);
        $insert = db()->prepare('INSERT INTO articles (title,slug,excerpt,content,author_id,seo_title,meta_description,status,profile_backlink_url,profile_social_links,profile_display_name,profile_label,profile_bio,profile_type,public_type) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $insert->execute([$submission['article_title'], $slug, $excerpt, guest_article_draft_content($submission), admin_user()['id'], $submission['article_title'] . ' | Local Legends Orlando', $excerpt, 'draft', format_external_url((string) $submission['website']), $submission['social_links'], $submission['author_name'], $submission['author_title'], $submission['bio'], 'author', 'article']);
        $articleId = (int) db()->lastInsertId();
        db()->prepare("UPDATE article_submissions SET status='converted', article_id=? WHERE id=?")->execute([$articleId, $id]);
        header('Location: ' . url('admin/article.php?id=' . $articleId)); exit;
    }
    $status = in_array($_POST['status'] ?? '', ['new','reviewing','declined'], true) ? $_POST['status'] : 'new';
    db()->prepare('UPDATE article_submissions SET status=? WHERE id=?')->execute([$status, $id]);
    header('Location: ' . url('admin/article-submissions.php')); exit;
}
$submissions = db()->query('SELECT * FROM article_submissions ORDER BY created_at DESC')->fetchAll();
$queuedSubmissions = queued_form_fallbacks('article-submission');
require __DIR__ . '/partials/header.php';
?>
<div class="admin-heading"><div><p class="eyebrow">Guest articles</p><h1>Article submissions</h1></div></div>
<?php if (($_GET['notice'] ?? '') === 'deleted'): ?><p class="notice">Article submission deleted.</p><?php endif; ?>
<?php if ($queuedSubmissions): ?><section class="admin-panel submission-list"><h2>Recovered article submissions</h2><?php foreach ($queuedSubmissions as $item): $values = $item['values']; ?><article><div><h2><?= e($values['article_title'] ?? 'Untitled article') ?></h2><p><strong><?= e($values['author_name'] ?? '') ?></strong> · <a href="mailto:<?= e($values['email'] ?? '') ?>"><?= e($values['email'] ?? '') ?></a></p><p><?= nl2br(e($values['answer_expertise'] ?? '')) ?></p><p><small>Recovery reference <?= e($item['reference'] ?? '') ?> · <?= e($item['created_at'] ?? '') ?></small></p></div></article><?php endforeach; ?></section><?php endif; ?>
<section class="admin-panel submission-list"><?php if (!$submissions): ?><p>No guest article submissions have arrived yet.</p><?php endif; ?><?php foreach ($submissions as $item): ?><article><div><h2><?= e($item['article_title']) ?></h2><p><strong><?= e($item['author_name']) ?></strong><?php if ($item['author_title']): ?>, <?= e($item['author_title']) ?><?php endif; ?> · <a href="mailto:<?= e($item['email']) ?>"><?= e($item['email']) ?></a></p><p><?= e(excerpt($item['answer_expertise'] ?: $item['article_summary'], 240)) ?></p><?php if ($item['website']): ?><a href="<?= e(format_external_url($item['website'])) ?>" rel="noopener" target="_blank">Requested backlink ↗</a><?php endif; ?><?php if ($item['article_id']): ?><p><a href="<?= url('admin/article.php?id=' . $item['article_id']) ?>">Open article draft →</a></p><?php endif; ?></div><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= e((string) $item['id']) ?>"><?php if (!$item['article_id']): ?><label>Status<select name="status"><?php foreach(['new','reviewing','declined'] as $status): ?><option value="<?= $status ?>" <?= $status === $item['status'] ? 'selected' : '' ?>><?= ucfirst($status) ?></option><?php endforeach; ?></select></label><button name="action" value="status">Update</button><button class="button" name="action" value="convert">Create article draft</button><?php else: ?><span class="status published">Converted</span><?php endif; ?><button class="delete-button" name="action" value="delete" onclick="return confirm('Delete this article submission? This cannot be undone.')">Delete submission</button></form></article><?php endforeach; ?></section>
<?php require __DIR__ . '/partials/footer.php'; ?>
