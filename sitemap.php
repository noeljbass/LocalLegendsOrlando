<?php
require 'includes/functions.php';
header('Content-Type: application/xml; charset=utf-8');
$paths = ['', 'stories/', 'categories/', 'about/', 'contact/', 'get-featured/', 'privacy/', 'terms/'];
try { $articles = db()->query("SELECT slug,updated_at FROM articles WHERE status='published' ORDER BY published_at DESC")->fetchAll(); } catch (PDOException $exception) { $articles = []; }
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($paths as $path): ?><url><loc><?= e(url($path)) ?></loc></url><?php endforeach; ?>
<?php foreach ($articles as $article): ?><url><loc><?= e(url('story/' . $article['slug'] . '/')) ?></loc><lastmod><?= e(date('c', strtotime($article['updated_at']))) ?></lastmod></url><?php endforeach; ?>
</urlset>
