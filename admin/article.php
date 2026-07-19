<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

function selected_ids(string $table, int $articleId): array {
    $column = $table === 'categories' ? 'category_id' : 'tag_id';
    $statement = db()->prepare("SELECT $column FROM article_{$table} WHERE article_id=?");
    $statement->execute([$articleId]);
    return array_column($statement->fetchAll(), $column);
}

$id = (int) ($_GET['id'] ?? 0);
$article = ['title'=>'','slug'=>'','excerpt'=>'','content'=>'','seo_title'=>'','meta_description'=>'','status'=>'draft','is_featured'=>0,'published_at'=>'','featured_image_id'=>null];
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
    $imageId = (int) ($_POST['featured_image_id'] ?? 0) ?: null;
    $values = [$title, $slug, trim($_POST['excerpt']), trim($_POST['content']), trim($_POST['seo_title']), trim($_POST['meta_description']), $status, (int) isset($_POST['is_featured']), $publishedAt, $imageId];
    if ($id) { $values[] = $id; db()->prepare('UPDATE articles SET title=?,slug=?,excerpt=?,content=?,seo_title=?,meta_description=?,status=?,is_featured=?,published_at=?,featured_image_id=? WHERE id=?')->execute($values); }
    else { array_splice($values, 3, 0, [admin_user()['id']]); db()->prepare('INSERT INTO articles (title,slug,excerpt,author_id,content,seo_title,meta_description,status,is_featured,published_at,featured_image_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)')->execute($values); $id = (int) db()->lastInsertId(); }
    foreach (['categories' => 'category_id', 'tags' => 'tag_id'] as $table => $column) {
        db()->prepare("DELETE FROM article_$table WHERE article_id=?")->execute([$id]);
        $link = db()->prepare("INSERT INTO article_$table (article_id,$column) VALUES (?,?)");
        foreach (array_unique(array_map('intval', $_POST[$table] ?? [])) as $itemId) if ($itemId) $link->execute([$id, $itemId]);
    }
    header('Location: ' . url('admin/article.php?id=' . $id . '&saved=1')); exit;
}
$categories = db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$tags = db()->query('SELECT * FROM tags ORDER BY name')->fetchAll();
$media = db()->query('SELECT id,file_name,alt_text FROM media_uploads ORDER BY created_at DESC')->fetchAll();
$articleCategories = $id ? selected_ids('categories', $id) : []; $articleTags = $id ? selected_ids('tags', $id) : [];
require __DIR__ . '/partials/header.php';
?>
<div class="admin-heading"><div><p class="eyebrow">Editorial</p><h1><?= $id ? 'Edit article' : 'New article' ?></h1></div><a href="<?= url('admin/articles.php') ?>">← All articles</a></div><?php if (isset($_GET['saved'])): ?><p class="notice">Article saved.</p><?php endif; ?>
<form class="editor-form" method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><div><label>Title<input required name="title" value="<?= e($article['title']) ?>"></label><label>Slug<input name="slug" value="<?= e($article['slug']) ?>" placeholder="Generated from title if left blank"></label><label>Short excerpt<textarea name="excerpt" rows="3"><?= e($article['excerpt']) ?></textarea></label><label>Story content <small>HTML is supported for headings and paragraphs.</small><textarea required name="content" rows="16"><?= e($article['content']) ?></textarea></label></div><aside><label>Publishing status<select name="status"><?php foreach (['draft','published','archived'] as $status): ?><option value="<?= $status ?>" <?= $article['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option><?php endforeach; ?></select></label><label>Publish date<input type="datetime-local" name="published_at" value="<?= e($article['published_at'] ? date('Y-m-d\TH:i', strtotime($article['published_at'])) : '') ?>"></label><label class="checkbox"><input type="checkbox" name="is_featured" <?= $article['is_featured'] ? 'checked' : '' ?>> Featured story</label><label>Featured image<select name="featured_image_id"><option value="">No image selected</option><?php foreach ($media as $item): ?><option value="<?= $item['id'] ?>" <?= (int)$article['featured_image_id'] === (int)$item['id'] ? 'selected' : '' ?>><?= e($item['alt_text'] ?: $item['file_name']) ?></option><?php endforeach; ?></select></label><fieldset class="taxonomy-checks"><legend>Categories</legend><?php foreach ($categories as $category): ?><label class="checkbox"><input type="checkbox" name="categories[]" value="<?= $category['id'] ?>" <?= in_array($category['id'], $articleCategories) ? 'checked' : '' ?>><?= e($category['name']) ?></label><?php endforeach; ?></fieldset><fieldset class="taxonomy-checks"><legend>Tags</legend><?php foreach ($tags as $tag): ?><label class="checkbox"><input type="checkbox" name="tags[]" value="<?= $tag['id'] ?>" <?= in_array($tag['id'], $articleTags) ? 'checked' : '' ?>><?= e($tag['name']) ?></label><?php endforeach; ?></fieldset><label>SEO title<input name="seo_title" value="<?= e($article['seo_title']) ?>"></label><label>Meta description<textarea name="meta_description" rows="4"><?= e($article['meta_description']) ?></textarea></label><button class="button">Save article</button><?php if ($id): ?><button class="delete-button" name="delete" value="1" onclick="return confirm('Delete this article permanently?')">Delete article</button><?php endif; ?></aside></form>
<?php require __DIR__ . '/partials/footer.php'; ?>
