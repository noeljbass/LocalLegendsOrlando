<?php
require '../includes/functions.php';
$slug = basename(rtrim($_SERVER['REQUEST_URI'], '/')); $label = ucwords(str_replace('-', ' ', $slug));
$pageTitle = '#' . $label . ' | Local Legends Orlando'; require '../includes/header.php';
$articles = public_articles(24, null, $slug);
?>
<section class="page-intro"><p class="eyebrow">Tag</p><h1>#<?= e($label) ?></h1><p>Stories connected by the people and places that make Orlando special.</p></section><section class="section"><?php if ($articles): ?><div class="story-grid story-grid-three"><?php foreach ($articles as $article): ?><article class="story-card"><a class="card-image" href="<?= url('story/'.$article['slug'].'/') ?>"><img src="<?= e(media_url($article['image'] ?? null)) ?>" alt="" loading="lazy"></a><div class="card-body"><h2><a href="<?= url('story/'.$article['slug'].'/') ?>"><?= e($article['title']) ?></a></h2><p><?= e($article['excerpt']) ?></p><a class="text-link" href="<?= url('story/'.$article['slug'].'/') ?>">Read the story →</a></div></article><?php endforeach; ?></div><?php else: ?><p>There are no published stories for this tag yet. <a href="<?= url('stories/') ?>">Browse all stories</a>.</p><?php endif; ?></section>
<?php require '../includes/footer.php'; ?>
