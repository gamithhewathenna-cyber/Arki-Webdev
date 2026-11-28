<?php
declare(strict_types=1);

/* ---------------------------------------------------------------------
 *  Output helpers
 * ------------------------------------------------------------------- */

/** Escape for HTML output. */
function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Absolute-path URL inside the site, e.g. url('admin/') → /admin/ */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/** Static asset URL with cache-busting version based on file modification time. */
function asset(string $path): string
{
    $file = ROOT_PATH . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? (string)filemtime($file) : '1';
    return url('assets/' . ltrim($path, '/')) . '?v=' . $v;
}

/**
 * Make a CMS-entered link safe for href. Allows relative paths, anchors,
 * http(s), mailto and tel. Anything else (javascript:, data: …) becomes '#'.
 */
function safe_url(?string $u): string
{
    $u = trim((string)$u);
    if ($u === '') {
        return '#';
    }
    if (preg_match('#^(https?://|mailto:|tel:|/|\#|\?)#i', $u)) {
        return $u;
    }
    if (!preg_match('#^[a-z][a-z0-9+.\-]*:#i', $u)) {
        return url($u); // relative path like "projects/"
    }
    return '#';
}

/** Attributes for external links. */
function link_attrs(?string $u): string
{
    return preg_match('#^https?://#i', (string)$u) && !str_contains((string)$u, (string)($_SERVER['HTTP_HOST'] ?? '#'))
        ? ' target="_blank" rel="noopener noreferrer"'
        : '';
}

/** Convert plain text with blank-line separated paragraphs into <p> elements. */
function paragraphs(?string $text, string $class = ''): string
{
    $text = trim(str_replace("\r\n", "\n", (string)$text));
    if ($text === '') {
        return '';
    }
    $cls = $class !== '' ? ' class="' . e($class) . '"' : '';
    $out = '';
    foreach (preg_split('/\n\s*\n/', $text) as $p) {
        $out .= '<p' . $cls . '>' . nl2br(e(trim($p)), false) . '</p>';
    }
    return $out;
}

/** Single text value with manual line breaks preserved. */
function lines(?string $text): string
{
    return nl2br(e(trim((string)$text)), false);
}

/** Public URL of an uploaded file stored as a relative path ("uploads/…"). */
function upload_url(?string $path): string
{
    $path = ltrim((string)$path, '/');
    return $path === '' ? '' : url($path);
}

/**
 * Render a responsive, lazy-loaded image. Serves a WebP sibling when one
 * exists and always includes intrinsic width/height to prevent layout shift.
 */
function picture(?string $path, string $alt = '', array $opts = []): string
{
    $path = ltrim((string)$path, '/');
    if ($path === '' || !is_file(ROOT_PATH . '/' . $path)) {
        return '';
    }
    $file  = ROOT_PATH . '/' . $path;
    $class = isset($opts['class']) ? ' class="' . e($opts['class']) . '"' : '';
    $lazy  = ($opts['eager'] ?? false) ? ' fetchpriority="high"' : ' loading="lazy"';
    $size  = '';
    if ($dim = @getimagesize($file)) {
        $size = ' width="' . (int)$dim[0] . '" height="' . (int)$dim[1] . '"';
    }
    $v   = '?v=' . filemtime($file);
    $img = '<img src="' . e(url($path) . $v) . '" alt="' . e($alt) . '"' . $size . $class . $lazy . ' decoding="async">';

    $webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $path);
    if ($webp !== $path && is_file(ROOT_PATH . '/' . $webp)) {
        return '<picture><source srcset="' . e(url($webp) . '?v=' . filemtime(ROOT_PATH . '/' . $webp)) . '" type="image/webp">' . $img . '</picture>';
    }
    return $img;
}

/** Inline SVG icon set (kept tiny, no external requests). */
function icon(string $name): string
{
    $icons = [
        'arrow-up-right' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="12" fill="currentColor"/><path d="M9 15l6-6M10 9h5v5" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'chevron-down'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'chevron-left'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'chevron-right'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'search'         => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2"/><path d="M20 20l-4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
        'cart'           => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2.4 10.2a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.8L20 8H6.2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="19" r="1.6" fill="currentColor"/><circle cx="17" cy="19" r="1.6" fill="currentColor"/></svg>',
        'user'           => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4" fill="currentColor"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7" fill="currentColor"/></svg>',
        'menu'           => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
        'close'          => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
        'location'       => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 3L3 10.5l7.5 3 3 7.5L21 3z" fill="currentColor"/></svg>',
        'phone'          => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25 11.4 11.4 0 0 0 3.6.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.57a1 1 0 0 1-.25 1L6.6 10.8z" fill="currentColor"/></svg>',
        'email'          => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zm9 7.5L3.5 7v.6L12 13l8.5-5.4V7L12 12.5z" fill="currentColor"/></svg>',
        'facebook'       => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 8V6.5c0-.7.2-1.2 1.2-1.2H17V2.2A24 24 0 0 0 14.6 2C12.2 2 10.6 3.4 10.6 6.1V8H8v3.4h2.6V22H14V11.4h2.7L17 8h-3z" fill="currentColor"/></svg>',
        'instagram'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.5" cy="6.5" r="1.2" fill="currentColor"/></svg>',
        'linkedin'       => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 3a2 2 0 1 1 0 4 2 2 0 0 1 0-4zM2.3 9h3.4v12H2.3zM9 9h3.3v1.7c.5-.9 1.7-2 3.6-2 3.8 0 4.5 2.5 4.5 5.8V21H17v-5.8c0-1.4 0-3.2-2-3.2s-2.3 1.5-2.3 3.1V21H9z" fill="currentColor"/></svg>',
        'truck'          => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 6.5h11v9h-11zM13.5 9.5h4l3 3.2v2.8h-7M1 9h4M0.5 12h3" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="6.5" cy="17" r="1.9" fill="none" stroke="currentColor" stroke-width="1.6"/><circle cx="17" cy="17" r="1.9" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>',
        'heart'          => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-7.5-10.2A4.3 4.3 0 0 1 12 7.6a4.3 4.3 0 0 1 7.5 2.7c0 5.6-7.5 10.2-7.5 10.2z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>',
        'link'           => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
    ];
    return $icons[$name] ?? '';
}

/* ---------------------------------------------------------------------
 *  Products
 * ------------------------------------------------------------------- */

/** "$ 1,950" – decimals only when needed. Empty string for no price. */
function money($amount, string $currency = 'AUD'): string
{
    if ($amount === null || $amount === '') {
        return '';
    }
    $symbols = ['AUD' => '$', 'NZD' => '$', 'USD' => '$', 'EUR' => '€', 'GBP' => '£'];
    $n = (float)$amount;
    return ($symbols[$currency] ?? $currency) . ' ' . number_format($n, floor($n) == $n ? 0 : 2);
}

/** Price markup for cards and the product page: optional "From", sale price with the old price struck through. */
function price_html(array $p): string
{
    $retail = $p['retail_price'] ?? null;
    $sale = $p['sale_price'] ?? null;
    if ($retail === null && $sale === null) {
        return '';
    }
    $cur = (string)($p['currency'] ?? 'AUD');
    $html = !empty($p['price_from']) ? '<small>From</small> ' : '';
    if ($sale !== null && $retail !== null && (float)$sale < (float)$retail) {
        $html .= '<del>' . e(money($retail, $cur)) . '</del> ' . e(money($sale, $cur));
    } else {
        $html .= e(money($sale ?? $retail, $cur));
    }
    return $html;
}

/** Link to a product page (keeps the storefront the visitor came from). */
function product_url(string $slug, string $from = 'store'): string
{
    return url('product/?p=' . rawurlencode($slug) . ($from !== 'store' && $from !== '' ? '&from=' . rawurlencode($from) : ''));
}

/** Link to a storefront's product listing, with optional query parameters. */
function listing_url(string $store, array $params = []): string
{
    $q = http_build_query(array_filter($params, static fn($v) => $v !== '' && $v !== null && $v !== []));
    return url($store . '/products/') . ($q !== '' ? '?' . $q : '');
}

/** Embed URL for a YouTube / Vimeo link (null when not recognised). */
function video_embed_url(?string $u): ?string
{
    $u = trim((string)$u);
    if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/)|youtu\.be/)([\w-]{11})~i', $u, $m)) {
        return 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?autoplay=1&rel=0';
    }
    if (preg_match('~vimeo\.com/(?:video/)?(\d+)~i', $u, $m)) {
        return 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1';
    }
    return null;
}

/** URL of an uploaded image's WebP copy, or '' when there is none. */
function webp_url(?string $path): string
{
    $path = ltrim((string)$path, '/');
    $webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $path);
    return ($webp !== $path && is_file(ROOT_PATH . '/' . $webp)) ? url($webp) : '';
}

/** Long thin arrow used after "View all …" links. */
function more_arrow(): string
{
    return '<svg viewBox="0 0 34 10" aria-hidden="true"><path d="M0 5h32M28 1l4 4-4 4" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}

/* ---------------------------------------------------------------------
 *  Session, security headers and CSRF
 * ------------------------------------------------------------------- */

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (int)($_SERVER['SERVER_PORT'] ?? 80) === 443;
}

function start_secure_session(string $name): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name($name);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => (BASE_URL ?: '') . '/',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }
    header_remove('X-Powered-By');
    // Development stage: keep every page out of search engines.
    header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet, noimageindex');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
    // frame-src: product videos (YouTube privacy mode / Vimeo) and showroom maps (Google Maps); media-src: uploaded product videos.
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; media-src 'self' blob:; style-src 'self'; script-src 'self'; font-src 'self'; connect-src 'self'; frame-src https://www.youtube-nocookie.com https://player.vimeo.com https://www.google.com https://maps.google.com; frame-ancestors 'self'; form-action 'self'; base-uri 'self'; object-src 'none'");
    if (is_https()) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $sent = (string)($_POST['_csrf'] ?? '');
    if ($sent === '' || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Your session has expired. Please go back, refresh the page and try again.');
    }
}

function client_ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

/**
 * Simple DB-backed throttle. Returns true if the action is allowed.
 * Used for both admin login and the development-lock password.
 */
function throttle_allowed(string $scope, int $max = 5, int $minutes = 15): bool
{
    Database::run('DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)');
    $n = (int)Database::value(
        'SELECT COUNT(*) FROM login_attempts WHERE scope = ? AND ip = ? AND attempted_at > (NOW() - INTERVAL ? MINUTE)',
        [$scope, client_ip(), $minutes]
    );
    return $n < $max;
}

function throttle_hit(string $scope): void
{
    Database::run('INSERT INTO login_attempts (scope, ip, attempted_at) VALUES (?, ?, NOW())', [$scope, client_ip()]);
}

function throttle_clear(string $scope): void
{
    Database::run('DELETE FROM login_attempts WHERE scope = ? AND ip = ?', [$scope, client_ip()]);
}

/* ---------------------------------------------------------------------
 *  Templates
 * ------------------------------------------------------------------- */

/**
 * Render an inner page (Work, Learn …) defined in app/schema/pages.php.
 * Used by each page's front controller, e.g. learn/index.php.
 */
function render_inner_page(string $page, ?string $pageTitle = null): void
{
    Construction::enforce();

    ContentRepository::sync($page);              // creates missing sections from app/schema/seeds.php
    $sections = ContentRepository::page($page);
    $label = (string)(ContentRepository::schema()[$page]['label'] ?? '');
    $title = trim(preg_replace('/\s+/', ' ', (string)($pageTitle ?? $sections['banner']['data']['heading'] ?? $label)));

    render('layout/page', [
        'pageTitle' => ($title !== '' ? $title . ' – ' : '') . Settings::get('site_name', 'ARKI Environments'),
        // The header is drawn over the banner image (white logo + menu) when the banner is shown.
        'bodyClass' => 'page-' . $page . (isset($sections['banner']) ? ' has-banner' : ''),
        'global'    => ContentRepository::page('global'),
        'sections'  => $sections,
        'pageKey'   => $page,
    ]);
}

/** Render a template file with an isolated variable scope. */
function render(string $template, array $vars = []): void
{
    $file = TEMPLATE_PATH . '/' . $template . '.php';
    if (!is_file($file)) {
        if (APP_DEBUG) {
            echo '<!-- missing template: ' . e($template) . ' -->';
        }
        return;
    }
    extract($vars, EXTR_SKIP);
    include $file;
}
