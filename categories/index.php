<?php
$pageTitle = 'Categories | Local Legends Orlando'; require '../includes/header.php';
$categories = public_categories();
$fallback = [
    ['name'=>'Food & drink','description'=>'Restaurants, cafés, and local flavor.','slug'=>'restaurants'],
    ['name'=>'Health & wellness','description'=>'People helping Orlando feel its best.','slug'=>'health-wellness'],
    ['name'=>'Home & services','description'=>'The trusted teams behind everyday life.','slug'=>'home-services'],
    ['name'=>'Makers & creatives','description'=>'Big ideas, beautiful work, and bold makers.','slug'=>'makers-creatives'],
];
$categories = $categories ?: $fallback;
?>
<section class="page-intro"><p class="eyebrow">Discover local</p><h1>Every kind of good work.</h1><p>Find the people and places making an impact in the corners of our community.</p></section><section class="section"><div class="category-grid"><?php foreach ($categories as $index => $category): ?><a class="category-card cat-<?= ($index % 4) + 1 ?>" href="<?= url('category/'.$category['slug'].'/') ?>"><span><?= e($category['name']) ?></span><small><?= e($category['description'] ?: 'Discover local stories in this community.') ?></small><b><?= isset($category['article_count']) ? e((string)$category['article_count']) . ' stories' : 'Explore' ?> →</b></a><?php endforeach; ?></div></section><section class="section tag-directory"><p class="eyebrow">Follow an interest</p><h2>Browse by tag</h2><div class="tag-cloud"><?php foreach (public_tags() as $tag): ?><a href="<?= url('tag/'.$tag['slug'].'/') ?>">#<?= e($tag['name']) ?> <small><?= e((string)$tag['article_count']) ?></small></a><?php endforeach; ?></div></section>
<?php require '../includes/footer.php'; ?>
