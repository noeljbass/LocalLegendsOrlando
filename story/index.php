<?php
require '../includes/functions.php';
$slug = basename(rtrim($_SERVER['REQUEST_URI'], '/'));
$articles = public_articles(3);
$article = published_article_by_slug($slug) ?: current(array_filter($articles, fn($item) => $item['slug'] === $slug));
if (!$article) { http_response_code(404); exit('Story not found.'); }
$isDatabaseArticle = isset($article['id']);
$pageTitle = (($article['seo_title'] ?? '') ?: $article['title']) . ' | Local Legends Orlando';
$metaDescription = ($article['meta_description'] ?? '') ?: $article['excerpt'];
require '../includes/header.php';
$categories = $isDatabaseArticle ? article_categories((int) $article['id']) : [];
$tags = $isDatabaseArticle ? article_tags((int) $article['id']) : [];
$gallery = $isDatabaseArticle ? article_media_gallery((int) $article['id']) : [];
$image = media_url($article['image'] ?? null, 'assets/images/market.svg');
$profileImage = media_url($article['profile_image'] ?? ($article['image'] ?? null), 'assets/images/market.svg');
$backlink = format_external_url((string) ($article['profile_backlink_url'] ?? ''));
$socialLinks = parse_social_links($article['profile_social_links'] ?? '');
$businessPhone = trim((string) ($article['business_phone'] ?? ''));
$businessPhoneUrl = phone_link_url($businessPhone);
$businessAddress = trim((string) ($article['business_address'] ?? ''));
$businessMapUrl = google_maps_address_url($businessAddress);
$publisherName = preg_replace('/:\s*A Local Legends Story$/i', '', (string) ($article['title'] ?? '')) ?: ($categories ? $categories[0]['name'] : 'Local Legends Orlando');
?>
<article class="article" itemscope itemtype="https://schema.org/Article">
    <header><p class="eyebrow"><?= $categories ? e($categories[0]['name']) : 'Community spotlight' ?></p><h1 itemprop="headline"><?= e($article['title']) ?></h1><p class="article-dek" itemprop="description"><?= e(normalize_article_text($article['excerpt'])) ?></p><div class="byline"><time itemprop="datePublished" datetime="<?= e($article['published_at']) ?>"><?= date('F j, Y', strtotime($article['published_at'])) ?></time></div></header>
    <img class="article-image" src="<?= e($image) ?>" alt="" loading="eager" itemprop="image">
    <div class="article-layout">
        <aside class="article-profile" aria-label="Featured company profile"><img src="<?= e($profileImage) ?>" alt="" loading="lazy"><h2><?= e($publisherName) ?></h2><?php if ($backlink): ?><a class="profile-link" href="<?= e($backlink) ?>" rel="nofollow noopener" target="_blank">Visit website <span>→</span></a><?php endif; ?><?php if ($businessPhone && $businessPhoneUrl): ?><a class="profile-link" href="<?= e($businessPhoneUrl) ?>">Call <?= e($businessPhone) ?></a><?php endif; ?><?php if ($businessAddress): ?><a class="profile-link profile-address-link" href="<?= e($businessMapUrl) ?>" rel="nofollow noopener" target="_blank"><span><?= e($businessAddress) ?></span> <span>Map →</span></a><?php endif; ?><?php if ($socialLinks): ?><nav class="profile-social-icons" aria-label="Company social profiles"><?php foreach ($socialLinks as $social): ?><a class="social-icon social-icon-<?= e($social['platform']) ?>" href="<?= e($social['url']) ?>" rel="nofollow noopener" target="_blank" aria-label="<?= e($social['label']) ?>"><?= e(social_icon($social['platform'])) ?></a><?php endforeach; ?></nav><?php endif; ?></aside>
        <div class="article-content" itemprop="articleBody"><?php if ($isDatabaseArticle): ?><?= render_article_content($article['content']) ?><?php else: ?><p class="intro">The best stories in our city often begin with someone who sees possibility where others see an ordinary day.</p><p>Across Central Florida, local business owners are creating places where people can belong. Their work brings care, imagination, and a welcome sense of connection to our neighborhoods.</p><h2>Built with the community in mind</h2><p>What makes a local legend is more than a great idea. It is the patience to show up, the courage to keep learning, and the generosity to make room for others. That spirit is what we hope to celebrate in every story we tell.</p><blockquote>“The places we remember are the ones that make us feel welcome.”</blockquote><p>As Orlando continues to grow, these human-scale stories keep us rooted. We’re grateful to share them—and to invite you to discover your next favorite local place.</p><?php endif; ?></div>
    </div>
    <?php if ($gallery): ?><section class="article-gallery" aria-labelledby="article-gallery-heading"><h2 id="article-gallery-heading">Photos from <?= e($publisherName) ?></h2><div><?php foreach ($gallery as $item): $galleryUrl = media_url($item['file_name']); ?><a href="<?= e($galleryUrl) ?>" target="_blank" rel="noopener"><img src="<?= e($galleryUrl) ?>" alt="<?= e($item['alt_text'] ?: $publisherName . ' photo') ?>" loading="lazy"></a><?php endforeach; ?></div></section><?php endif; ?>
    <?php if ($tags): ?><p class="article-tags"><?php foreach ($tags as $tag): ?><a href="<?= url('tag/' . $tag['slug'] . '/') ?>">#<?= e($tag['name']) ?></a><?php endforeach; ?></p><?php endif; ?>
</article>
<script type="application/ld+json"><?= json_encode(['@context'=>'https://schema.org','@type'=>'Article','headline'=>$article['title'],'description'=>normalize_article_text($article['excerpt']),'datePublished'=>$article['published_at'],'mainEntityOfPage'=>url('story/'.$article['slug'].'/'),'image'=>$image,'author'=>['@type'=>'Organization','name'=>'Local Legends Orlando']], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script><section class="section related"><p class="eyebrow">Keep exploring</p><h2>More stories to love</h2><a class="button" href="<?= url('stories/') ?>">Browse all stories <span>→</span></a></section>
<?php require '../includes/footer.php'; ?>
