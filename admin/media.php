<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (($_POST['action'] ?? '') === 'delete') {
        $id = (int) ($_POST['id'] ?? 0); $statement = db()->prepare('SELECT file_name FROM media_uploads WHERE id=?'); $statement->execute([$id]); $item = $statement->fetch();
        if ($item) { db()->prepare('DELETE FROM media_uploads WHERE id=?')->execute([$id]); $path = __DIR__ . '/../uploads/' . $item['file_name']; if (is_file($path)) unlink($path); $message = 'Image deleted.'; }
    } else {
        $file = $_FILES['image'] ?? null; $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
        if (!$file || $file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) $message = 'Choose an image under 5 MB.';
        else {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']); $dimensions = @getimagesize($file['tmp_name']);
            if (!isset($allowed[$mime]) || !$dimensions) $message = 'Choose a valid JPG, PNG, WebP, or GIF image.';
            else { $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime]; if (move_uploaded_file($file['tmp_name'], __DIR__ . '/../uploads/' . $name)) { db()->prepare('INSERT INTO media_uploads (file_name,original_name,mime_type,file_size,alt_text,uploaded_by) VALUES (?,?,?,?,?,?)')->execute([$name,basename($file['name']),$mime,$file['size'],trim($_POST['alt_text']),admin_user()['id']]); $message = 'Image uploaded.'; } }
        }
    }
}
$media = db()->query('SELECT * FROM media_uploads ORDER BY created_at DESC')->fetchAll(); require __DIR__ . '/partials/header.php';
?>
<div class="admin-heading"><div><p class="eyebrow">Asset library</p><h1>Media</h1></div></div><?php if ($message): ?><p class="notice"><?= e($message) ?></p><?php endif; ?><section class="admin-panel"><form class="inline-form" enctype="multipart/form-data" method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="upload"><input required type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif"><input name="alt_text" placeholder="Describe this image"><button class="button">Upload</button></form></section><div class="media-grid"><?php foreach ($media as $item): ?><figure><img src="<?= url('uploads/'.$item['file_name']) ?>" alt="<?= e($item['alt_text']) ?>" loading="lazy"><figcaption><?= e($item['original_name']) ?></figcaption><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $item['id'] ?>"><button class="delete-button" onclick="return confirm('Delete this image?')">Delete</button></form></figure><?php endforeach; ?></div>
<?php require __DIR__ . '/partials/footer.php'; ?>
