<?php
require_once __DIR__ . '/includes/auth.php';

$newsletterError = '';
$newsletterSubscribed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'newsletter') {
    try {
        if (!valid_signed_csrf_token($_POST['csrf'] ?? '')) throw new RuntimeException('Your session has expired. Please try again.');
        if (trim($_POST['topic_extra'] ?? '') !== '' || trim($_POST['company'] ?? '') !== '') throw new RuntimeException('Unable to process your signup. Please refresh this page and try again.');
        enforce_form_rate_limit('newsletter');

        $email = trim($_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Please enter a valid email address.');

        $statement = db()->prepare('INSERT INTO newsletter_subscribers (email) VALUES (?) ON DUPLICATE KEY UPDATE id = id');
        $statement->execute([$email]);
        $newsletterSubscribed = true;
    } catch (RuntimeException $exception) {
        $newsletterError = $exception->getMessage();
    } catch (Throwable $exception) {
        $newsletterError = 'We’re unable to save your signup right now. Please try again later.';
    }
}

$pageTitle='Local Legends Orlando | Stories that strengthen our city'; require 'includes/header.php'; $stories = public_stories(3); $articles = public_editorial_articles(3); ?>
<section class="hero hero--image"><div class="hero-copy"><p class="eyebrow">Central Florida, celebrated</p><h1>Celebrating the Businesses That Make Orlando Extraordinary</h1><p class="lede">Discover the stories behind the entrepreneurs, creators, and local businesses shaping our community.</p><div class="hero-actions"><a class="button" href="<?= url('get-featured/') ?>">Get Featured <span>→</span></a><a class="text-link" href="<?= url('stories/') ?>">Read stories <span>→</span></a></div></div></section>
<section class="section featured"><div class="section-heading"><div><p class="eyebrow">Stories worth sharing</p><h2>Featured stories</h2></div><a class="text-link" href="<?= url('stories/') ?>">View all stories <span>→</span></a></div><div class="story-grid"><?php foreach ($stories as $index=>$article): ?><article class="story-card <?= $index===0 ? 'story-card-large' : '' ?>"><a href="<?= article_public_url($article) ?>" class="card-image"><img src="<?= media_url($article['image'] ?? null) ?>" alt="" loading="lazy"></a><div class="card-body"><p class="kicker">Local spotlight</p><h3><a href="<?= article_public_url($article) ?>"><?= e($article['title']) ?></a></h3><p><?= e($article['excerpt']) ?></p><a class="text-link" href="<?= article_public_url($article) ?>">Read the story <span>→</span></a></div></article><?php endforeach; ?></div></section>
<section class="section recent"><div class="section-heading"><div><p class="eyebrow">Local insights</p><h2>Featured articles</h2></div></div><div class="recent-list"><?php foreach ($articles as $article): ?><article><p class="kicker">Article</p><h3><a href="<?= article_public_url($article) ?>"><?= e($article['title']) ?></a></h3><a href="<?= article_public_url($article) ?>">Read article →</a></article><?php endforeach; ?></div></section>
<section class="section category-band"><div class="section-heading"><div><p class="eyebrow">Find your next favorite</p><h2>Explore by category</h2></div></div><div class="category-grid"><?php foreach ([['Food & drink','Restaurants, cafés, and local flavor.','restaurants'],['Health & wellness','People helping Orlando feel its best.','health-wellness'],['Home & services','The trusted teams behind everyday life.','home-services'],['Makers & creatives','Big ideas, beautiful work, and bold makers.','makers-creatives']] as $i=>$cat): ?><a class="category-card cat-<?= $i+1 ?>" href="<?= url('category/'.$cat[2].'/') ?>"><span><?= $cat[0] ?></span><small><?= $cat[1] ?></small><b>Explore →</b></a><?php endforeach; ?></div></section>
<section class="section recent"><div class="section-heading"><div><p class="eyebrow">Fresh from our community</p><h2>Recently published stories</h2></div></div><div class="recent-list"><?php foreach (array_reverse($stories) as $article): ?><article><p class="kicker">June 2026</p><h3><a href="<?= article_public_url($article) ?>"><?= e($article['title']) ?></a></h3><a href="<?= article_public_url($article) ?>">Read more →</a></article><?php endforeach; ?></div></section>
<section class="about-band"><div><p class="eyebrow">This is your city</p><h2>Local Legends is a love letter to the people building Orlando.</h2></div><div><p>We believe every thriving community has a thousand remarkable stories. We’re here to slow down, listen closely, and celebrate the neighbors, dreamers, and doers making Central Florida brighter.</p><a class="button button-light" href="<?= url('about/') ?>">Our story <span>→</span></a></div></section>
<section class="newsletter"><p class="eyebrow">Good things, in your inbox</p><h2>Stay close to the stories that matter.</h2><p>Our newsletter is coming soon. In the meantime, follow along as we celebrate local.</p><?php if ($newsletterSubscribed): ?><div class="notice newsletter-notice" role="status"><strong>You’re on the list.</strong><br>We’ll keep you posted on stories from around Orlando.</div><?php else: ?><form method="post"><input type="hidden" name="form" value="newsletter"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><label class="honeypot" aria-hidden="true">Leave this field empty<input name="topic_extra" tabindex="-1" autocomplete="off"></label><label class="sr-only" for="newsletter-email">Email address</label><input id="newsletter-email" name="email" type="email" placeholder="Your email address" autocomplete="email" required><button class="button" type="submit">Keep me posted</button></form><?php if ($newsletterError): ?><p class="form-error" role="alert"><?= e($newsletterError) ?></p><?php endif; ?><?php endif; ?></section>
<?php require 'includes/footer.php'; ?>
