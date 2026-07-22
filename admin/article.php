<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();


function ensure_article_profile_columns(): void {
    try {
        $columns = array_column(db()->query('SHOW COLUMNS FROM articles')->fetchAll(), 'Field');
        if (!in_array('profile_image_id', $columns, true)) db()->exec('ALTER TABLE articles ADD profile_image_id BIGINT UNSIGNED NULL AFTER featured_image_id');
        if (!in_array('profile_backlink_url', $columns, true)) db()->exec('ALTER TABLE articles ADD profile_backlink_url VARCHAR(255) NULL AFTER profile_image_id');
        if (!in_array('profile_social_links', $columns, true)) db()->exec('ALTER TABLE articles ADD profile_social_links TEXT NULL AFTER profile_backlink_url');
        if (!in_array('business_phone', $columns, true)) db()->exec('ALTER TABLE articles ADD business_phone VARCHAR(40) NULL AFTER profile_social_links');
        if (!in_array('business_address', $columns, true)) db()->exec('ALTER TABLE articles ADD business_address VARCHAR(255) NULL AFTER business_phone');
        if (!in_array('profile_display_name', $columns, true)) db()->exec('ALTER TABLE articles ADD profile_display_name VARCHAR(180) NULL AFTER business_address');
        if (!in_array('profile_label', $columns, true)) db()->exec('ALTER TABLE articles ADD profile_label VARCHAR(120) NULL AFTER profile_display_name');
        if (!in_array('profile_bio', $columns, true)) db()->exec('ALTER TABLE articles ADD profile_bio TEXT NULL AFTER profile_label');
        if (!in_array('profile_type', $columns, true)) db()->exec("ALTER TABLE articles ADD profile_type ENUM('company','author') NOT NULL DEFAULT 'company' AFTER profile_bio");
        if (!in_array('public_type', $columns, true)) db()->exec("ALTER TABLE articles ADD public_type ENUM('story','article') NOT NULL DEFAULT 'story' AFTER profile_type");
    } catch (Throwable $exception) {}
}
function create_taxonomy_item(string $table, string $name): ?int {
    $name = trim($name);
    if ($name === '') return null;
    $slug = slugify($name);
    $statement = db()->prepare("SELECT id FROM $table WHERE slug=? OR name=? LIMIT 1");
    $statement->execute([$slug, $name]);
    $existing = $statement->fetchColumn();
    if ($existing) return (int) $existing;
    db()->prepare("INSERT INTO $table (name,slug) VALUES (?,?)")->execute([$name, $slug]);
    return (int) db()->lastInsertId();
}
ensure_article_profile_columns();
ensure_homepage_categories();

function article_source_interview(int $articleId): ?array {
    $statement = db()->prepare('SELECT * FROM interview_submissions WHERE article_id=? LIMIT 1');
    $statement->execute([$articleId]);
    $interview = $statement->fetch();
    return $interview ?: null;
}

function interview_answer_snippets(array $interview): array {
    return [
        'Story' => ['heading' => 'Their story', 'answer' => $interview['story'] ?? ''],
        'Origin story' => ['heading' => 'How it began', 'answer' => $interview['origin_story'] ?? ''],
        'What makes them unique' => ['heading' => 'What makes them unique', 'answer' => $interview['uniqueness'] ?? ''],
        'Biggest challenge' => ['heading' => 'A challenge they have overcome', 'answer' => $interview['biggest_challenge'] ?? ''],
        'Proudest achievement' => ['heading' => 'A proud moment', 'answer' => $interview['proudest_achievement'] ?? ''],
        'Advice' => ['heading' => 'Advice for aspiring entrepreneurs', 'answer' => $interview['entrepreneur_advice'] ?? ''],
        'What they are excited about' => ['heading' => 'What is next', 'answer' => $interview['excited_about'] ?? ''],
    ];
}

function selected_ids(string $table, int $articleId): array {
    $column = $table === 'categories' ? 'category_id' : 'tag_id';
    $statement = db()->prepare("SELECT $column FROM article_{$table} WHERE article_id=?");
    $statement->execute([$articleId]);
    return array_column($statement->fetchAll(), $column);
}

function selected_taxonomy_names(string $table, int $articleId): array {
    $joinTable = $table === 'categories' ? 'article_categories' : 'article_tags';
    $column = $table === 'categories' ? 'category_id' : 'tag_id';
    $statement = db()->prepare("SELECT t.name FROM $table t JOIN $joinTable jt ON jt.$column=t.id WHERE jt.article_id=? ORDER BY t.name");
    $statement->execute([$articleId]);
    return array_column($statement->fetchAll(), 'name');
}

function article_submission_media(int $articleId): array {
    $statement = db()->prepare("SELECT DISTINCT m.id,m.file_name,m.alt_text FROM media_uploads m JOIN interview_media im ON im.media_id=m.id JOIN interview_submissions i ON i.id=im.interview_id WHERE i.article_id=? ORDER BY FIELD(im.media_type, 'business_photo', 'owner_photo', 'team_photo', 'logo'), im.id");
    $statement->execute([$articleId]);
    return $statement->fetchAll();
}

$id = (int) ($_GET['id'] ?? 0);
$article = ['title'=>'','slug'=>'','excerpt'=>'','content'=>'','seo_title'=>'','meta_description'=>'','status'=>'draft','is_featured'=>0,'published_at'=>'','featured_image_id'=>null,'profile_image_id'=>null,'profile_backlink_url'=>'','profile_social_links'=>'','business_phone'=>'','business_address'=>'','profile_display_name'=>'','profile_label'=>'','profile_bio'=>'','profile_type'=>'company','public_type'=>'story'];
if ($id) { $statement = db()->prepare('SELECT * FROM articles WHERE id=?'); $statement->execute([$id]); $article = $statement->fetch() ?: exit('Article not found.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (isset($_POST['delete']) && $id) {
        db()->prepare('DELETE FROM articles WHERE id=?')->execute([$id]);
        header('Location: ' . url('admin/articles.php')); exit;
    }
    $title = trim($_POST['title']); $slug = slugify($_POST['slug'] ?: $title);
    $status = in_array($_POST['status'], ['draft','published','archived'], true) ? $_POST['status'] : 'draft';
    $publishedAt = $status === 'published' ? ($_POST['published_at'] ? date('Y-m-d H:i:s', strtotime($_POST['published_at'])) : date('Y-m-d H:i:s')) : null;
    $publicType = in_array($_POST['public_type'] ?? 'story', ['story', 'article'], true) ? $_POST['public_type'] : 'story';
    $imageId = (int) ($_POST['featured_image_id'] ?? 0) ?: null;
    $profileImageId = (int) ($_POST['profile_image_id'] ?? 0) ?: null;
    $values = [$title, $slug, trim($_POST['excerpt']), trim($_POST['content']), trim($_POST['seo_title']), trim($_POST['meta_description']), $status, (int) isset($_POST['is_featured']), $publishedAt, $imageId, $profileImageId, trim($_POST['profile_backlink_url'] ?? ''), trim($_POST['profile_social_links'] ?? ''), trim($_POST['business_phone'] ?? ''), trim($_POST['business_address'] ?? ''), trim($_POST['profile_display_name'] ?? ''), trim($_POST['profile_label'] ?? ''), trim($_POST['profile_bio'] ?? ''), in_array($_POST['profile_type'] ?? 'company', ['company','author'], true) ? $_POST['profile_type'] : 'company', $publicType];
    if ($id) { $values[] = $id; db()->prepare('UPDATE articles SET title=?,slug=?,excerpt=?,content=?,seo_title=?,meta_description=?,status=?,is_featured=?,published_at=?,featured_image_id=?,profile_image_id=?,profile_backlink_url=?,profile_social_links=?,business_phone=?,business_address=?,profile_display_name=?,profile_label=?,profile_bio=?,profile_type=?,public_type=? WHERE id=?')->execute($values); }
    else { array_splice($values, 3, 0, [admin_user()['id']]); db()->prepare('INSERT INTO articles (title,slug,excerpt,author_id,content,seo_title,meta_description,status,is_featured,published_at,featured_image_id,profile_image_id,profile_backlink_url,profile_social_links,business_phone,business_address,profile_display_name,profile_label,profile_bio,profile_type,public_type) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute($values); $id = (int) db()->lastInsertId(); }
    foreach (['categories' => 'new_category', 'tags' => 'tag_names'] as $table => $field) {
        foreach (preg_split('/[,\n]+/', $_POST[$field] ?? '') ?: [] as $name) {
            $newId = create_taxonomy_item($table, $name);
            if ($newId) $_POST[$table][] = $newId;
        }
    }
    foreach (['categories' => 'category_id', 'tags' => 'tag_id'] as $table => $column) {
        db()->prepare("DELETE FROM article_$table WHERE article_id=?")->execute([$id]);
        $link = db()->prepare("INSERT INTO article_$table (article_id,$column) VALUES (?,?)");
        foreach (array_unique(array_map('intval', $_POST[$table] ?? [])) as $itemId) if ($itemId) $link->execute([$id, $itemId]);
    }
    header('Location: ' . url('admin/article.php?id=' . $id . '&saved=1')); exit;
}
$categories = db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$tags = db()->query('SELECT * FROM tags ORDER BY name')->fetchAll();
$sourceInterview = $id ? article_source_interview($id) : null;
$submissionMedia = $id ? article_submission_media($id) : [];
$media = $submissionMedia ?: db()->query('SELECT id,file_name,alt_text FROM media_uploads ORDER BY created_at DESC')->fetchAll();
$articleCategories = $id ? selected_ids('categories', $id) : []; $articleTags = $id ? selected_ids('tags', $id) : [];
$articleTagNames = $id ? selected_taxonomy_names('tags', $id) : [];
$interviewSnippets = $sourceInterview ? interview_answer_snippets($sourceInterview) : [];
require __DIR__ . '/partials/header.php';
?>
<div class="admin-heading"><div><p class="eyebrow">Editorial</p><h1><?= $id ? 'Edit article' : 'New article' ?></h1></div><a href="<?= url('admin/articles.php') ?>">← All articles</a></div><?php if (isset($_GET['saved'])): ?><p class="notice">Article saved.</p><?php endif; ?>
<form class="editor-form" method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><div><label>Title<input required name="title" value="<?= e($article['title']) ?>"></label><label>Slug<input name="slug" value="<?= e($article['slug']) ?>" placeholder="Generated from title if left blank"></label><label>Short excerpt<textarea name="excerpt" rows="3"><?= e($article['excerpt']) ?></textarea></label><label>Story content <small>HTML is supported for headings and paragraphs.</small><textarea required name="content" rows="16" data-article-content><?= e($article['content']) ?></textarea></label><?php if ($sourceInterview): ?><section class="interview-snippets" aria-labelledby="interview-snippets-heading"><h2 id="interview-snippets-heading">Interview answers</h2><p>Use these submitter-provided answers as building blocks. Place your cursor in the story content field, then insert the answer wherever you want it.</p><?php foreach ($interviewSnippets as $label => $snippet): ?><?php if (trim($snippet['answer']) !== ''): ?><?php $snippetHtml = '<h2>' . e($snippet['heading']) . '</h2>\n<p>' . nl2br(e($snippet['answer'])) . '</p>\n'; ?><details><summary><?= e($label) ?></summary><p><?= nl2br(e($snippet['answer'])) ?></p><button type="button" class="button button-small" data-insert-article-html="<?= e($snippetHtml) ?>">Insert here</button></details><?php endif; ?><?php endforeach; ?></section><?php endif; ?></div><aside><label>Publishing status<select name="status"><?php foreach (['draft','published','archived'] as $status): ?><option value="<?= $status ?>" <?= $article['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option><?php endforeach; ?></select></label><label>Publish date<input type="datetime-local" name="published_at" value="<?= e($article['published_at'] ? date('Y-m-d\TH:i', strtotime($article['published_at'])) : '') ?>"></label><label class="checkbox"><input type="checkbox" name="is_featured" <?= $article['is_featured'] ? 'checked' : '' ?>> Featured story</label><fieldset class="image-choices"><legend>Featured image</legend><?php if ($submissionMedia): ?><p class="taxonomy-note">Showing images from this submission.</p><?php endif; ?><label class="image-choice image-choice-empty"><input type="radio" name="featured_image_id" value="" <?= empty($article['featured_image_id']) ? 'checked' : '' ?>><span>No image selected</span></label><?php foreach ($media as $item): $imageLabel = $item['alt_text'] ?: 'Uploaded image'; ?><label class="image-choice"><input type="radio" name="featured_image_id" value="<?= $item['id'] ?>" <?= (int)$article['featured_image_id'] === (int)$item['id'] ? 'checked' : '' ?>><img src="<?= url('uploads/' . $item['file_name']) ?>" alt="<?= e($imageLabel) ?>" loading="lazy"><span><?= e($imageLabel) ?></span></label><?php endforeach; ?></fieldset><label>Public type<select name="public_type"><option value="story" <?= ($article['public_type'] ?? 'story') === 'story' ? 'selected' : '' ?>>Story</option><option value="article" <?= ($article['public_type'] ?? 'story') === 'article' ? 'selected' : '' ?>>Article</option></select></label><label>Sidebar profile type<select name="profile_type"><option value="company" <?= ($article['profile_type'] ?? 'company') === 'company' ? 'selected' : '' ?>>Company</option><option value="author" <?= ($article['profile_type'] ?? 'company') === 'author' ? 'selected' : '' ?>>Author</option></select></label><label>Sidebar display name<input name="profile_display_name" value="<?= e($article['profile_display_name'] ?? '') ?>" placeholder="Defaults to company/story title"></label><label>Sidebar label<input name="profile_label" value="<?= e($article['profile_label'] ?? '') ?>" placeholder="Founder, writer, advisor…"></label><label>Sidebar bio<textarea name="profile_bio" rows="4" placeholder="Short author or company bio"><?= e($article['profile_bio'] ?? '') ?></textarea></label><fieldset class="image-choices"><legend>Sidebar profile picture</legend><label class="image-choice image-choice-empty"><input type="radio" name="profile_image_id" value="" <?= empty($article['profile_image_id']) ? 'checked' : '' ?>><span>Use featured image</span></label><?php foreach ($media as $item): $imageLabel = $item['alt_text'] ?: 'Uploaded image'; ?><label class="image-choice"><input type="radio" name="profile_image_id" value="<?= $item['id'] ?>" <?= (int)($article['profile_image_id'] ?? 0) === (int)$item['id'] ? 'checked' : '' ?>><img src="<?= url('uploads/' . $item['file_name']) ?>" alt="<?= e($imageLabel) ?>" loading="lazy"><span><?= e($imageLabel) ?></span></label><?php endforeach; ?></fieldset><label>Profile backlink URL<input name="profile_backlink_url" value="<?= e($article['profile_backlink_url'] ?? '') ?>" placeholder="https://example.com"></label><label>Profile social links<textarea name="profile_social_links" rows="4" placeholder="Instagram: https://instagram.com/example
Facebook: https://facebook.com/example"><?= e($article['profile_social_links'] ?? '') ?></textarea></label><label>Business phone<input name="business_phone" type="tel" value="<?= e($article['business_phone'] ?? '') ?>" placeholder="(407) 555-0100"></label><label>Business address<input name="business_address" value="<?= e($article['business_address'] ?? '') ?>" placeholder="123 Main St, Orlando, FL"></label><fieldset class="taxonomy-checks"><legend>Categories</legend><p class="taxonomy-note"><strong>Primary homepage categories:</strong> Food & drink, Health & wellness, Home & services, and Makers & creatives control the homepage category destinations.</p><?php foreach ($categories as $category): ?><label class="checkbox"><input type="checkbox" name="categories[]" value="<?= $category['id'] ?>" <?= in_array($category['id'], $articleCategories) ? 'checked' : '' ?>><?= e($category['name']) ?></label><?php endforeach; ?><label>Add categories <small>Comma or line separated.</small><input name="new_category" placeholder="New category"></label></fieldset><fieldset class="taxonomy-checks"><legend>Tags</legend><label>Tags <small>Type tags separated by commas or lines. Existing tags are suggested as you type.</small><input name="tag_names" list="tag-suggestions" value="<?= e(implode(', ', $articleTagNames)) ?>" placeholder="Orlando, family owned, coffee"></label><datalist id="tag-suggestions"><?php foreach ($tags as $tag): ?><option value="<?= e($tag['name']) ?>"></option><?php endforeach; ?></datalist></fieldset><label>SEO title<input name="seo_title" value="<?= e($article['seo_title']) ?>"></label><label>Meta description<textarea name="meta_description" rows="4"><?= e($article['meta_description']) ?></textarea></label><button class="button">Save article</button><?php if ($id): ?><button class="delete-button" name="delete" value="1" onclick="return confirm('Delete this article permanently?')">Delete article</button><?php endif; ?></aside></form>
<?php require __DIR__ . '/partials/footer.php'; ?>
