<?php
require_once __DIR__ . '/../config.php';
function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function url(string $path = ''): string { return SITE_URL . '/' . ltrim($path, '/'); }
function normalize_article_text(?string $text): string {
    return str_replace(['\\r\\n', '\\n', '\\r'], ["\n", "\n", "\n"], (string) $text);
}
function excerpt(string $text, int $length = 145): string { return mb_strimwidth(trim(strip_tags(normalize_article_text($text))), 0, $length, '…'); }
function render_article_content(?string $content): string {
    $content = trim(normalize_article_text($content));
    if ($content === '') return '';
    if ($content !== strip_tags($content)) return $content;
    $paragraphs = preg_split('/\n\s*\n/', $content) ?: [];
    return implode('', array_map(fn($paragraph) => '<p>' . e(trim($paragraph)) . '</p>', array_filter($paragraphs, fn($paragraph) => trim($paragraph) !== '')));
}
function article_columns(): array {
    static $columns = null;
    if ($columns === null) {
        try { $columns = array_column(db()->query('SHOW COLUMNS FROM articles')->fetchAll(), 'Field'); }
        catch (Throwable $exception) { $columns = []; }
    }
    return $columns;
}
function optional_article_column(string $column, string $alias = ''): string {
    return in_array($column, article_columns(), true) ? ', a.' . $column . ($alias ? ' AS ' . $alias : '') : ', NULL AS ' . ($alias ?: $column);
}
function article_profile_select_sql(): string {
    return in_array('profile_image_id', article_columns(), true) ? ', pm.file_name AS profile_image' : ', NULL AS profile_image';
}
function article_profile_join_sql(): string {
    return in_array('profile_image_id', article_columns(), true) ? ' LEFT JOIN media_uploads pm ON pm.id=a.profile_image_id' : '';
}
function article_public_type_select_sql(): string {
    if (in_array('public_type', article_columns(), true)) return ', a.public_type';
    return in_array('profile_type', article_columns(), true) ? ", CASE WHEN a.profile_type = 'author' THEN 'article' ELSE 'story' END AS public_type" : ", 'story' AS public_type";
}
function article_public_path(array $article): string {
    $type = ($article['public_type'] ?? '') === 'article' ? 'article' : 'story';
    return $type . '/' . ($article['slug'] ?? '') . '/';
}
function article_public_url(array $article): string { return url(article_public_path($article)); }
function media_url(?string $fileName, string $fallback = 'assets/images/market.svg'): string {
    if (!$fileName) return url($fallback);
    return url(str_starts_with($fileName, 'assets/') ? $fileName : 'uploads/' . $fileName);
}


function format_external_url(string $url): string {
    $url = trim($url);
    if ($url === '') return '';
    return preg_match('/^https?:\/\//i', $url) ? $url : 'https://' . $url;
}


function google_maps_address_url(string $address): string {
    $address = trim($address);
    if ($address === '') return '';
    return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($address);
}

function phone_link_url(string $phone): string {
    $phone = trim($phone);
    if ($phone === '') return '';
    $normalized = preg_replace('/[^0-9+]+/', '', $phone) ?: '';
    return $normalized === '' ? '' : 'tel:' . $normalized;
}

function parse_social_links(?string $links): array {
    $items = [];
    foreach (preg_split('/[\r\n,]+/', normalize_article_text($links)) ?: [] as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $label = '';
        $href = $line;
        if (preg_match('/^([A-Za-z0-9 .&+-]+):\s*(.+)$/', $line, $parts)) {
            $label = trim($parts[1]);
            $href = trim($parts[2]);
            if (preg_match('/^\/\//', $href)) $href = 'https:' . $href;
            elseif (!preg_match('/^https?:\/\//i', $href) && preg_match('/^[^\s]+\.[^\s]+/', $href)) $href = 'https://' . $href;
        }
        if (!preg_match('/^https?:\/\//i', $href) && preg_match('/https?:\/\/\S+/i', $line, $match)) $href = $match[0];
        if ($label === '') $label = social_platform_from_url($href);
        $items[] = ['label' => $label ?: 'Social profile', 'url' => format_external_url($href), 'platform' => social_platform_from_url($label . ' ' . $href)];
    }
    return $items;
}

function social_platform_from_url(string $value): string {
    $value = strtolower($value);
    foreach (['facebook', 'instagram', 'tiktok', 'youtube', 'linkedin', 'pinterest', 'x.com', 'twitter'] as $platform) {
        if (str_contains($value, $platform)) return $platform === 'x.com' || $platform === 'twitter' ? 'x' : $platform;
    }
    return 'other';
}

function social_icon(string $platform): string {
    $labels = ['facebook'=>'f', 'instagram'=>'◎', 'tiktok'=>'♪', 'youtube'=>'▶', 'linkedin'=>'in', 'pinterest'=>'p', 'x'=>'𝕏', 'other'=>'↗'];
    return $labels[$platform] ?? $labels['other'];
}

function article_media_gallery(int $articleId): array {
    try {
        $statement = db()->prepare('SELECT m.* FROM media_uploads m JOIN article_media am ON am.media_id=m.id WHERE am.article_id=? ORDER BY am.sort_order, m.id');
        $statement->execute([$articleId]);
        return $statement->fetchAll();
    } catch (Throwable $exception) { return []; }
}

function slugify(string $value, string $fallback = 'story'): string {
    $value = str_replace(["'", "’", "‘", "`"], '', $value);
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $value), '-'));
    return $slug ?: $fallback;
}

function compact_slug(string $slug): string {
    return preg_replace('/[^a-z0-9]+/', '', strtolower($slug)) ?: '';
}

function homepage_categories(): array {
    return [
        ['name' => 'Food & drink', 'slug' => 'restaurants', 'description' => 'Restaurants, cafés, and local flavor.'],
        ['name' => 'Health & wellness', 'slug' => 'health-wellness', 'description' => 'People helping Orlando feel its best.'],
        ['name' => 'Home & services', 'slug' => 'home-services', 'description' => 'The trusted teams behind everyday life.'],
        ['name' => 'Makers & creatives', 'slug' => 'makers-creatives', 'description' => 'Big ideas, beautiful work, and bold makers.'],
    ];
}

function ensure_homepage_categories(): void {
    try {
        $statement = db()->prepare('INSERT INTO categories (name, slug, description) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name), slug=VALUES(slug), description=VALUES(description)');
        foreach (homepage_categories() as $category) {
            $statement->execute([$category['name'], $category['slug'], $category['description']]);
        }
    } catch (Throwable $exception) {}
}
function get_articles(int $limit = 12, ?string $category = null, ?string $tag = null, ?string $publicType = null): array {
    $sql = "SELECT DISTINCT a.*, m.file_name AS image, u.name AS author" . article_profile_select_sql() . optional_article_column('profile_backlink_url') . optional_article_column('profile_social_links') . optional_article_column('business_phone') . optional_article_column('business_address') . optional_article_column('profile_display_name') . optional_article_column('profile_label') . optional_article_column('profile_bio') . optional_article_column('profile_type') . article_public_type_select_sql() . " FROM articles a LEFT JOIN media_uploads m ON m.id=a.featured_image_id LEFT JOIN users u ON u.id=a.author_id" . article_profile_join_sql() . " LEFT JOIN article_categories ac ON ac.article_id=a.id LEFT JOIN categories c ON c.id=ac.category_id LEFT JOIN article_tags at ON at.article_id=a.id LEFT JOIN tags t ON t.id=at.tag_id WHERE a.status='published'";
    $params = [];
    if ($category) { $sql .= ' AND c.slug = ?'; $params[] = $category; }
    if ($tag) { $sql .= ' AND t.slug = ?'; $params[] = $tag; }
    if ($publicType) {
        if (in_array('public_type', article_columns(), true)) {
            $sql .= ' AND a.public_type = ?';
            $params[] = $publicType;
        } elseif (in_array('profile_type', article_columns(), true)) {
            $sql .= $publicType === 'article' ? " AND a.profile_type = 'author'" : " AND (a.profile_type IS NULL OR a.profile_type <> 'author')";
        } elseif ($publicType === 'article') {
            return [];
        }
    }
    $sql .= ' ORDER BY a.is_featured DESC, a.published_at DESC LIMIT ' . (int)$limit;
    $stmt = db()->prepare($sql); $stmt->execute($params); return $stmt->fetchAll();
}
function article_categories(int $articleId): array { $s=db()->prepare('SELECT c.* FROM categories c JOIN article_categories ac ON ac.category_id=c.id WHERE ac.article_id=?'); $s->execute([$articleId]); return $s->fetchAll(); }
function article_tags(int $articleId): array { $s=db()->prepare('SELECT t.* FROM tags t JOIN article_tags at ON at.tag_id=t.id WHERE at.article_id=?'); $s->execute([$articleId]); return $s->fetchAll(); }
function demo_articles(): array { return [
 ['title'=>'How East End Market Continues to Grow Community Through Food','slug'=>'east-end-market-community-through-food','excerpt'=>'Inside the local gathering place where Orlando makers, food lovers, and neighbors connect.','image'=>'assets/images/market.svg','author'=>'Local Legends Team','published_at'=>'2026-06-30','public_type'=>'story'],
 ['title'=>'Meet the Makers Bringing Color to Winter Park','slug'=>'winter-park-makers','excerpt'=>'These creative small-business owners are making every corner of the city feel more personal.','image'=>'assets/images/makers.svg','author'=>'Local Legends Team','published_at'=>'2026-06-21','public_type'=>'story'],
 ['title'=>'The Family Behind a Beloved Orlando Coffee Ritual','slug'=>'orlando-coffee-ritual','excerpt'=>'A conversation about hospitality, heritage, and finding joy in the everyday cup.','image'=>'assets/images/coffee.svg','author'=>'Local Legends Team','published_at'=>'2026-06-12','public_type'=>'story']
]; }

function public_articles(int $limit = 12, ?string $category = null, ?string $tag = null, ?string $publicType = null): array {
    try {
        $articles = get_articles($limit, $category, $tag, $publicType);
        if ($articles) return $articles;
    } catch (Throwable $exception) {}

    if ($category || $tag) return [];
    $demoArticles = demo_articles();
    if ($publicType) $demoArticles = array_values(array_filter($demoArticles, fn($article) => ($article['public_type'] ?? 'story') === $publicType));
    return array_slice($demoArticles, 0, $limit);
}

function public_stories(int $limit = 12, ?string $category = null, ?string $tag = null): array {
    return public_articles($limit, $category, $tag, 'story');
}

function public_editorial_articles(int $limit = 12, ?string $category = null, ?string $tag = null): array {
    return public_articles($limit, $category, $tag, 'article');
}

function published_article_by_slug(string $slug): ?array {
    try {
        $sql = "SELECT a.*, m.file_name AS image, u.name AS author" . article_profile_select_sql() . optional_article_column('profile_backlink_url') . optional_article_column('profile_social_links') . optional_article_column('business_phone') . optional_article_column('business_address') . optional_article_column('profile_display_name') . optional_article_column('profile_label') . optional_article_column('profile_bio') . optional_article_column('profile_type') . article_public_type_select_sql() . " FROM articles a LEFT JOIN media_uploads m ON m.id=a.featured_image_id LEFT JOIN users u ON u.id=a.author_id" . article_profile_join_sql() . " WHERE a.status='published' AND a.slug=? LIMIT 1";
        $stmt = db()->prepare($sql);
        $stmt->execute([$slug]);
        $article = $stmt->fetch();
        if ($article) return $article;

        $compactSlug = compact_slug($slug);
        if ($compactSlug === '') return null;
        $stmt = db()->query("SELECT a.*, m.file_name AS image, u.name AS author" . article_profile_select_sql() . optional_article_column('profile_backlink_url') . optional_article_column('profile_social_links') . optional_article_column('business_phone') . optional_article_column('business_address') . optional_article_column('profile_display_name') . optional_article_column('profile_label') . optional_article_column('profile_bio') . optional_article_column('profile_type') . article_public_type_select_sql() . " FROM articles a LEFT JOIN media_uploads m ON m.id=a.featured_image_id LEFT JOIN users u ON u.id=a.author_id" . article_profile_join_sql() . " WHERE a.status='published'");
        foreach ($stmt->fetchAll() as $candidate) {
            if (compact_slug((string) $candidate['slug']) === $compactSlug || slugify((string) $candidate['title']) === $slug) return $candidate;
        }
        return null;
    } catch (Throwable $exception) {
        return null;
    }
}

function search_articles(string $query, int $limit = 30): array {
    $query = trim($query);
    if ($query === '') return [];
    $pattern = '%' . $query . '%';
    $sql = "SELECT DISTINCT a.*, m.file_name AS image, u.name AS author" . article_public_type_select_sql() . "
        FROM articles a
        LEFT JOIN users u ON u.id=a.author_id" . article_profile_join_sql() . "
        LEFT JOIN media_uploads m ON m.id=a.featured_image_id
        LEFT JOIN article_categories ac ON ac.article_id=a.id
        LEFT JOIN categories c ON c.id=ac.category_id
        LEFT JOIN article_tags at ON at.article_id=a.id
        LEFT JOIN tags t ON t.id=at.tag_id
        WHERE a.status='published' AND (a.title LIKE ? OR a.content LIKE ? OR c.name LIKE ? OR t.name LIKE ?)
        ORDER BY a.is_featured DESC, a.published_at DESC LIMIT " . (int) $limit;
    try { $statement = db()->prepare($sql); $statement->execute([$pattern, $pattern, $pattern, $pattern]); return $statement->fetchAll(); }
    catch (Throwable $exception) { return []; }
}

function send_site_mail(string $to, string $subject, string $message, ?string $replyTo = null): bool {
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . SITE_NAME . ' <' . ADMIN_EMAIL . '>',
    ];
    if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) $headers[] = 'Reply-To: ' . $replyTo;
    return mail($to, str_replace(["\r", "\n"], '', $subject), $message, implode("\r\n", $headers));
}

function ini_bytes(string $value): int {
    $value = trim($value);
    if ($value === '' || $value === '-1' || $value === '0') return PHP_INT_MAX;
    $unit = strtolower(substr($value, -1));
    $number = (float) $value;
    return (int) match ($unit) {
        'g' => $number * 1073741824,
        'm' => $number * 1048576,
        'k' => $number * 1024,
        default => $number,
    };
}

/**
 * When a POST body exceeds post_max_size, PHP silently discards $_POST and
 * $_FILES. Without this guard the CSRF check then fails with a bare 403 page,
 * which is what visitors uploading large phone photos were hitting.
 */
function guard_post_payload(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') return;
    $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($contentLength <= 0) return;
    if ($contentLength > ini_bytes((string) ini_get('post_max_size')) || (empty($_POST) && empty($_FILES))) {
        throw new RuntimeException('Your submission was too large for the server to accept. Please use photos under 5 MB each, or fewer photos, and try again. Your written answers are still saved on this device.');
    }
}

function prevent_form_caching(): void {
    if (headers_sent()) return;
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Vary: Cookie');
}

function enforce_form_rate_limit(string $key, int $seconds = 45): void {
    start_session();
    $last = (int) ($_SESSION['form_rate_limits'][$key] ?? 0);
    if ($last && time() - $last < $seconds) throw new RuntimeException('Thanks—we received your message. Please wait a moment before submitting again.');
}

function record_form_submission(string $key): void {
    start_session();
    $_SESSION['form_rate_limits'][$key] = time();
}

function queue_form_fallback(string $type, array $values): ?string {
    $reference = strtoupper($type) . '-' . bin2hex(random_bytes(5));
    $record = json_encode([
        'reference' => $reference,
        'type' => $type,
        'created_at' => gmdate('c'),
        'values' => $values,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($record === false) return null;
    $path = __DIR__ . '/../uploads/form-fallbacks.ndjson';
    return file_put_contents($path, $record . PHP_EOL, FILE_APPEND | LOCK_EX) === false ? null : $reference;
}

function queued_form_fallbacks(string $type): array {
    $path = __DIR__ . '/../uploads/form-fallbacks.ndjson';
    if (!is_readable($path)) return [];
    $items = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $item = json_decode($line, true);
        if (is_array($item) && ($item['type'] ?? '') === $type) $items[] = $item;
    }
    return array_reverse($items);
}

function security_headers(): void {
    if (headers_sent()) return;
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

function public_categories(): array {
    try {
        return db()->query("SELECT c.*, COUNT(DISTINCT a.id) AS article_count FROM categories c LEFT JOIN article_categories ac ON ac.category_id=c.id LEFT JOIN articles a ON a.id=ac.article_id AND a.status='published' GROUP BY c.id ORDER BY c.name")->fetchAll();
    } catch (Throwable $exception) { return []; }
}

function public_tags(): array {
    try {
        return db()->query("SELECT t.*, COUNT(DISTINCT a.id) AS article_count FROM tags t LEFT JOIN article_tags at ON at.tag_id=t.id LEFT JOIN articles a ON a.id=at.article_id AND a.status='published' GROUP BY t.id ORDER BY t.name")->fetchAll();
    } catch (Throwable $exception) { return []; }
}
