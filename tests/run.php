<?php
/** Lightweight, dependency-free checks for helpers that do not need MySQL. */
require_once __DIR__ . '/../includes/functions.php';

$failures = 0;
function expect_same(mixed $expected, mixed $actual, string $name): void {
    global $failures;
    if ($expected === $actual) { echo "PASS: $name\n"; return; }
    $failures++; fwrite(STDERR, "FAIL: $name\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
}

expect_same('&lt;Orlando &amp; Co&gt;', e('<Orlando & Co>'), 'HTML escaping');
expect_same('Hello…', excerpt('Hello Orlando', 6), 'excerpt truncation');
expect_same(SITE_URL . '/assets/images/market.svg', media_url(null), 'media fallback URL');
expect_same(SITE_URL . '/assets/images/coffee.svg', media_url('assets/images/coffee.svg'), 'static media URL');
expect_same(SITE_URL . '/uploads/local-legend.webp', media_url('local-legend.webp'), 'uploaded media URL');
expect_same([], search_articles(''), 'blank search short-circuits without database access');
expect_same([], search_articles('coffee'), 'database failures return empty search results');

if ($failures) exit(1);
echo "All helper checks passed.\n";
