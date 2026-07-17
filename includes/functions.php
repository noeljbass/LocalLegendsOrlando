<?php
require_once __DIR__ . '/../config.php';
function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function url(string $path = ''): string { return SITE_URL . '/' . ltrim($path, '/'); }
function excerpt(string $text, int $length = 145): string { return mb_strimwidth(trim(strip_tags($text)), 0, $length, '…'); }
function media_url(?string $fileName, string $fallback = 'assets/images/market.svg'): string {
    if (!$fileName) return url($fallback);
    return url(str_starts_with($fileName, 'assets/') ? $fileName : 'uploads/' . $fileName);
}
function get_articles(int $limit = 12, ?string $category = null, ?string $tag = null): array {
    $sql = "SELECT DISTINCT a.*, m.file_name AS image, u.name AS author FROM articles a LEFT JOIN media_uploads m ON m.id=a.featured_image_id LEFT JOIN users u ON u.id=a.author_id LEFT JOIN article_categories ac ON ac.article_id=a.id LEFT JOIN categories c ON c.id=ac.category_id LEFT JOIN article_tags at ON at.article_id=a.id LEFT JOIN tags t ON t.id=at.tag_id WHERE a.status='published'";
    $params = [];
    if ($category) { $sql .= ' AND c.slug = ?'; $params[] = $category; }
    if ($tag) { $sql .= ' AND t.slug = ?'; $params[] = $tag; }
    $sql .= ' ORDER BY a.is_featured DESC, a.published_at DESC LIMIT ' . (int)$limit;
    $stmt = db()->prepare($sql); $stmt->execute($params); return $stmt->fetchAll();
}
function article_categories(int $articleId): array { $s=db()->prepare('SELECT c.* FROM categories c JOIN article_categories ac ON ac.category_id=c.id WHERE ac.article_id=?'); $s->execute([$articleId]); return $s->fetchAll(); }
function article_tags(int $articleId): array { $s=db()->prepare('SELECT t.* FROM tags t JOIN article_tags at ON at.tag_id=t.id WHERE at.article_id=?'); $s->execute([$articleId]); return $s->fetchAll(); }
function demo_articles(): array { return [
 ['title'=>'How East End Market Continues to Grow Community Through Food','slug'=>'east-end-market-community-through-food','excerpt'=>'Inside the local gathering place where Orlando makers, food lovers, and neighbors connect.','image'=>'assets/images/market.svg','author'=>'Local Legends Team','published_at'=>'2026-06-30'],
 ['title'=>'Meet the Makers Bringing Color to Winter Park','slug'=>'winter-park-makers','excerpt'=>'These creative small-business owners are making every corner of the city feel more personal.','image'=>'assets/images/makers.svg','author'=>'Local Legends Team','published_at'=>'2026-06-21'],
 ['title'=>'The Family Behind a Beloved Orlando Coffee Ritual','slug'=>'orlando-coffee-ritual','excerpt'=>'A conversation about hospitality, heritage, and finding joy in the everyday cup.','image'=>'assets/images/coffee.svg','author'=>'Local Legends Team','published_at'=>'2026-06-12']
]; }

function public_articles(int $limit = 12, ?string $category = null, ?string $tag = null): array {
    try {
        $articles = get_articles($limit, $category, $tag);
        return $articles ?: (!$category && !$tag ? demo_articles() : []);
    } catch (Throwable $exception) {
        return !$category && !$tag ? demo_articles() : [];
    }
}

function published_article_by_slug(string $slug): ?array {
    try {
        $stmt = db()->prepare("SELECT a.*, m.file_name AS image, u.name AS author FROM articles a LEFT JOIN media_uploads m ON m.id=a.featured_image_id LEFT JOIN users u ON u.id=a.author_id WHERE a.status='published' AND a.slug=? LIMIT 1");
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    } catch (Throwable $exception) {
        return null;
    }
}

function search_articles(string $query, int $limit = 30): array {
    $query = trim($query);
    if ($query === '') return [];
    $pattern = '%' . $query . '%';
    $sql = "SELECT DISTINCT a.*, m.file_name AS image, u.name AS author
        FROM articles a
        LEFT JOIN users u ON u.id=a.author_id
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
    $_SESSION['form_rate_limits'][$key] = time();
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
