</main>
<footer class="site-footer">
    <div>
        <a class="brand" href="<?= url() ?>" aria-label="Local Legends Orlando home">
            <img src="<?= url('assets/images/local-legends-orlando-footer-logo.webp') ?>" alt="Local Legends Orlando">
        </a>
        <p>Celebrating the people and places that make Central Florida feel like home.</p>
    </div>
    <div>
        <h2>Explore</h2>
        <a href="<?= url('stories/') ?>">Stories</a>
        <a href="<?= url('categories/') ?>">Categories</a>
        <a href="<?= url('get-featured/') ?>">Be featured</a>
    </div>
    <div>
        <h2>Stay connected</h2>
        <p>Fresh local stories, delivered with heart.</p>
        <a href="<?= url('contact/') ?>">Say hello</a>
        <a href="<?= url('privacy/') ?>">Privacy</a>
        <a href="<?= url('terms/') ?>">Terms</a>
    </div>
    <p class="copyright">© <?= date('Y') ?> Local Legends Orlando. Made for our community.</p>
    <p class="footer-credit">Brought to you by <span class="footer-credit-name">BastionTech</span>.</p>
</footer>
<script src="<?= url('assets/js/site.js') ?>?v=<?= @filemtime(__DIR__ . '/../assets/js/site.js') ?: 1 ?>" defer></script>
</body>
</html>
