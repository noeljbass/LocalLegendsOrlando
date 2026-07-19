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
