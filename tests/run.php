<?php
/** Lightweight, dependency-free checks for helpers that do not need MySQL. */
putenv('CSRF_SECRET=tests-only-csrf-secret');
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
start_session();

$failures = 0;
function expect_same(mixed $expected, mixed $actual, string $name): void {
    global $failures;
    if ($expected === $actual) { echo "PASS: $name\n"; return; }
    $failures++; fwrite(STDERR, "FAIL: $name\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
}

expect_same('&lt;Orlando &amp; Co&gt;', e('<Orlando & Co>'), 'HTML escaping');
expect_same('Hello…', excerpt('Hello Orlando', 6), 'excerpt truncation');
expect_same("Line one\nLine two", normalize_article_text('Line one\\nLine two'), 'literal newline markers are normalized');
expect_same('<p>Line one</p><p>Line two</p>', render_article_content('Line one\\n\\nLine two'), 'plain story content renders paragraphs without literal newline markers');
expect_same(SITE_URL . '/assets/images/market.svg', media_url(null), 'media fallback URL');
expect_same(SITE_URL . '/assets/images/coffee.svg', media_url('assets/images/coffee.svg'), 'static media URL');
expect_same(SITE_URL . '/uploads/local-legend.webp', media_url('local-legend.webp'), 'uploaded media URL');
expect_same('zeigers-auto-detailing', slugify("Zeiger's Auto Detailing"), 'possessive article slugs remove apostrophes');
expect_same('zeigersautodetailing', compact_slug('zeiger-s-auto-detailing'), 'compact slug ignores separators');
expect_same('story/east-end-market-community-through-food/', article_public_path(demo_articles()[0]), 'demo stories use story public paths');
expect_same('', article_public_tracked_badge_url(['slug' => '', 'public_type' => 'story']), 'blank badge slug does not generate tracked URL');
expect_same(SITE_URL . '/story/zeigers-auto-detailing/?utm_source=featured_business_website&utm_medium=referral&utm_campaign=featured_on_badge&utm_content=zeigers-auto-detailing', article_public_tracked_badge_url(['slug' => "Zeiger's Auto Detailing", 'public_type' => 'story']), 'story badge tracked URL uses sanitized slug and UTM parameters');
expect_same(SITE_URL . '/article/orlando-marketing-guide/?utm_source=featured_business_website&utm_medium=referral&utm_campaign=featured_on_badge&utm_content=orlando-marketing-guide', article_public_tracked_badge_url(['slug' => 'orlando-marketing-guide', 'public_type' => 'article']), 'article badge tracked URL uses article route');
$badgeHtml = featured_badge_embed_html(['slug' => 'zeigers-auto-detailing', 'public_type' => 'story']);
expect_same(true, str_contains($badgeHtml, 'target="_blank"') && str_contains($badgeHtml, 'rel="noopener noreferrer"') && str_contains($badgeHtml, 'aria-label="Read our feature on Local Legends Orlando"') && str_contains($badgeHtml, featured_badge_logo_url()), 'badge HTML includes logo, accessibility attributes, and new-tab behavior');
expect_same(3, count(public_stories(3)), 'public story helper filters demo stories');
expect_same(0, count(public_editorial_articles(3)), 'public article helper excludes demo stories');
expect_same('https://www.google.com/maps/search/?api=1&query=123%20Main%20St%2C%20Orlando%2C%20FL', google_maps_address_url(' 123 Main St, Orlando, FL '), 'Google Maps address URLs are generated');
expect_same('tel:+14075550100', phone_link_url(' +1 (407) 555-0100 '), 'Phone links are normalized for tel URLs');
expect_same([], search_articles(''), 'blank search short-circuits without database access');
expect_same([], search_articles('coffee'), 'database failures return empty search results');
$csrfToken = csrf_token();
expect_same(true, valid_signed_csrf_token($csrfToken), 'signed CSRF token validates without a persisted session');
expect_same(false, valid_signed_csrf_token($csrfToken . 'x'), 'tampered signed CSRF token is rejected');

unset($_SESSION['form_rate_limits']['test_form']);
enforce_form_rate_limit('test_form');
record_form_submission('test_form');
try {
    enforce_form_rate_limit('test_form');
    expect_same(true, false, 'recorded form submission is rate limited');
} catch (RuntimeException) {
    expect_same(true, true, 'recorded form submission is rate limited');
}

if ($failures) exit(1);
echo "All helper checks passed.\n";
