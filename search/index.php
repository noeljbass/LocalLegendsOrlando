<?php
require_once __DIR__ . '/../includes/functions.php';

$rawQuery = $_GET['q'] ?? '';
$query = is_string($rawQuery) ? trim($rawQuery) : '';
$results = search_articles($query);
$pageTitle = $query ? 'Search: ' . $query . ' | Local Legends Orlando' : 'Search stories | Local Legends Orlando';
require '../includes/header.php';
?>
<section class="page-intro search-intro"><p class="eyebrow">Find a local story</p><h1>Search Local Legends</h1><p>Search stories by business name, category, tag, or words from the story itself.</p><form class="search-form" method="get" role="search"><label class="sr-only" for="site-search">Search stories</label><input id="site-search" name="q" type="search" value="<?= e($query) ?>" placeholder="Try “family-owned” or “coffee”"><button class="button">Search <span>→</span></button></form></section>
<?php if ($query): ?><section class="section"><div class="section-heading"><div><p class="eyebrow">Search results</p><h2><?= count($results) ? count($results) . ' stories for “' . e($query) . '”' : 'No stories found' ?></h2></div></div><?php if ($results): ?><div class="story-grid story-grid-three"><?php foreach ($results as $article): ?><article class="story-card"><a class="card-image" href="<?= url('story/'.$article['slug'].'/') ?>"><img src="<?= e(media_url($article['image'] ?? null)) ?>" alt="" loading="lazy"></a><div class="card-body"><p class="kicker">Community spotlight</p><h2><a href="<?= url('story/'.$article['slug'].'/') ?>"><?= e($article['title']) ?></a></h2><p><?= e($article['excerpt'] ?: excerpt($article['content'])) ?></p><a class="text-link" href="<?= url('story/'.$article['slug'].'/') ?>">Read the story →</a></div></article><?php endforeach; ?></div><?php else: ?><p>Try a business name, a service, a category, or a tag. You can also <a href="<?= url('stories/') ?>">browse all stories</a>.</p><?php endif; ?></section><?php endif; ?>
<?php require '../includes/footer.php'; ?>
