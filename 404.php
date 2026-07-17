<?php
http_response_code(404);
$pageTitle = 'This story is still being written | Local Legends Orlando';
$metaDescription = 'This Local Legends Orlando page is not available yet. Discover local stories or tell us about your business.';
$robots = 'noindex,follow';
require __DIR__ . '/includes/header.php';
?>
<section class="not-found" aria-labelledby="not-found-title">
    <div class="not-found-copy">
        <p class="eyebrow">404 · Not found</p>
        <h1 id="not-found-title">This content doesn’t exist yet.</h1>
        <p class="lede">We’re still getting to know every remarkable business, maker, and neighbor in Central Florida. This page may be on its way—or it may be a story we haven’t heard yet.</p>
        <div class="not-found-actions">
            <a class="button" href="<?= url('get-featured/') ?>">Feature your business <span>→</span></a>
            <a class="text-link" href="<?= url() ?>">Back to home <span>→</span></a>
        </div>
    </div>
    <div class="not-found-art" aria-hidden="true">
        <span class="not-found-number">404</span>
        <span class="not-found-sun"></span>
        <span class="not-found-arch not-found-arch-one"></span>
        <span class="not-found-arch not-found-arch-two"></span>
        <p>More local legends<br><em>coming soon.</em></p>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
