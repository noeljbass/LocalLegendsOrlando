<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

function taxonomy_slug(string $name): string { return strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $name), '-')); }
function taxonomy_table(string $type): string { return $type === 'tags' ? 'tags' : 'categories'; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(); $type = taxonomy_table($_POST['type'] ?? 'categories'); $id = (int) ($_POST['id'] ?? 0); $action = $_POST['action'] ?? 'create';
    try {
        if ($action === 'delete' && $id) { db()->prepare("DELETE FROM $type WHERE id=?")->execute([$id]); }
        else {
            $name = trim($_POST['name'] ?? ''); if ($name === '') throw new RuntimeException('A name is required.');
            $slug = taxonomy_slug($_POST['slug'] ?: $name); if ($slug === '') throw new RuntimeException('A valid slug is required.');
            if ($action === 'update' && $id) {
                $sql = $type === 'categories' ? 'UPDATE categories SET name=?,slug=?,description=? WHERE id=?' : 'UPDATE tags SET name=?,slug=? WHERE id=?';
                $values = $type === 'categories' ? [$name,$slug,trim($_POST['description'] ?? ''),$id] : [$name,$slug,$id];
                db()->prepare($sql)->execute($values);
            } else {
                $sql = $type === 'categories' ? 'INSERT INTO categories (name,slug,description) VALUES (?,?,?)' : 'INSERT INTO tags (name,slug) VALUES (?,?)';
                $values = $type === 'categories' ? [$name,$slug,trim($_POST['description'] ?? '')] : [$name,$slug];
                db()->prepare($sql)->execute($values);
            }
        }
        header('Location: ' . url('admin/taxonomy.php')); exit;
    } catch (Throwable $exception) { $error = $exception instanceof PDOException ? 'That name or slug is already in use.' : $exception->getMessage(); }
}
$categories = db()->query('SELECT * FROM categories ORDER BY name')->fetchAll(); $tags = db()->query('SELECT * FROM tags ORDER BY name')->fetchAll();
require __DIR__ . '/partials/header.php';
?>
<div class="admin-heading"><div><p class="eyebrow">Organization</p><h1>Categories & tags</h1></div></div><?php if ($error): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>
<div class="taxonomy-grid"><?php foreach (['categories' => $categories, 'tags' => $tags] as $type => $items): ?><section class="admin-panel"><h2><?= ucfirst($type) ?></h2><form class="taxonomy-create" method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="type" value="<?= $type ?>"><input type="hidden" name="action" value="create"><label>Name<input name="name" required placeholder="New <?= rtrim($type, 's') ?> name"></label><?php if ($type === 'categories'): ?><label>Description<textarea name="description" rows="2" placeholder="Optional short description"></textarea></label><?php endif; ?><button class="button">Add <?= rtrim($type, 's') ?></button></form><div class="taxonomy-list"><?php foreach ($items as $item): ?><details><summary><strong><?= e($item['name']) ?></strong><small>/<?= rtrim($type, 's') ?>/<?= e($item['slug']) ?>/</small></summary><form class="taxonomy-edit" method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="type" value="<?= $type ?>"><input type="hidden" name="id" value="<?= $item['id'] ?>"><input type="hidden" name="action" value="update"><label>Name<input name="name" required value="<?= e($item['name']) ?>"></label><label>Slug<input name="slug" required value="<?= e($item['slug']) ?>"></label><?php if ($type === 'categories'): ?><label>Description<textarea name="description" rows="2"><?= e($item['description']) ?></textarea></label><?php endif; ?><button>Save changes</button><button class="delete-button" type="submit" name="action" value="delete" onclick="return confirm('Delete this <?= rtrim($type, 's') ?>? Articles will remain published.')">Delete</button></form></details><?php endforeach; ?></div></section><?php endforeach; ?></div>
<?php require __DIR__ . '/partials/footer.php'; ?>
