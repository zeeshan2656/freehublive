<?php
// ============================================================
// FreeHub.Live — Core Functions & Utilities
// ============================================================

// Auto-detect BASE_URL with SSL & subdirectory auto-detection for localhost and all hosting servers
if (!defined('BASE_URL')) {
    $is_secure = false;
    if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        $is_secure = true;
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        $is_secure = true;
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') {
        $is_secure = true;
    } elseif (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
        $is_secure = true;
    } elseif (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
        $is_secure = true;
    } elseif (!empty($_SERVER['REQUEST_SCHEME']) && strtolower($_SERVER['REQUEST_SCHEME']) === 'https') {
        $is_secure = true;
    }
    $scheme = $is_secure ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Auto-detect subdirectory path
    $project_root = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $doc_root     = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
    
    $subdir = '';
    if ($doc_root && str_starts_with($project_root, $doc_root)) {
        $subdir = substr($project_root, strlen($doc_root));
        $subdir = str_replace('\\', '/', $subdir);
        $subdir = rtrim($subdir, '/');
    }
    
    define('BASE_URL', $scheme . '://' . $host . $subdir);
}
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('THUMB_PATH',  __DIR__ . '/../uploads/thumbnails/');
define('VIDEO_PATH',  __DIR__ . '/../uploads/videos/');
define('CACHE_PATH',  __DIR__ . '/../cache/');
define('VERSION',     '1.0.0');

// ── String helpers ─────────────────────────────────────────
function slugify(string $text): string {
    $text = mb_strtolower(trim($text));
    $text = preg_replace('/[^\w\s-]/', '', $text);
    $text = preg_replace('/[\s_-]+/', '-', $text);
    return trim($text, '-');
}

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function truncate(string $str, int $len = 80): string {
    return mb_strlen($str) > $len ? mb_substr($str, 0, $len) . '…' : $str;
}

function format_number(int|float $n): string {
    if ($n >= 1_000_000_000) return round($n / 1_000_000_000, 1) . 'B';
    if ($n >= 1_000_000)     return round($n / 1_000_000, 1) . 'M';
    if ($n >= 1_000)         return round($n / 1_000, 1) . 'K';
    return (string)$n;
}

function format_duration(int $seconds): string {
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    $s = $seconds % 60;
    return $h > 0
        ? sprintf('%d:%02d:%02d', $h, $m, $s)
        : sprintf('%d:%02d', $m, $s);
}

function time_ago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff/60) . 'm ago';
    if ($diff < 86400)  return floor($diff/3600) . 'h ago';
    if ($diff < 604800) return floor($diff/86400) . 'd ago';
    if ($diff < 2592000)return floor($diff/604800) . 'w ago';
    if ($diff < 31536000)return floor($diff/2592000) . 'mo ago';
    return floor($diff/31536000) . 'y ago';
}

define('CAT_PATH',    __DIR__ . '/../uploads/categories/');

// ── File helpers ────────────────────────────────────────────
function thumb_url(?string $thumb): string {
    if (!$thumb) return BASE_URL . '/assets/img/default-thumb.jpg';
    if (str_starts_with($thumb, 'http')) return $thumb;
    return BASE_URL . '/uploads/thumbnails/' . $thumb;
}

function avatar_url(?string $avatar): string {
    if (!$avatar) return BASE_URL . '/assets/img/default-avatar.jpg';
    if (str_starts_with($avatar, 'http')) return $avatar;
    return BASE_URL . '/uploads/avatars/' . $avatar;
}

function cover_url(?string $cover): string {
    if (!$cover) return '';
    if (str_starts_with($cover, 'http')) return $cover;
    return BASE_URL . '/uploads/covers/' . $cover;
}

function fh_youtube_id(?string $url): ?string {
    if (!$url) return null;
    if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match)) {
        return $match[1];
    }
    if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) {
        return $url;
    }
    return null;
}

function category_image_url(?string $img): string {
    if (!$img) return BASE_URL . '/assets/img/default-cat.jpg';
    if (str_starts_with($img, 'http')) return $img;
    return BASE_URL . '/uploads/categories/' . $img;
}

function video_url(?string $url): string {
    if (!$url) return '';
    if (str_starts_with($url, 'http')) return $url;
    return BASE_URL . '/uploads/videos/' . $url;
}

function format_filesize(int $bytes): string {
    if ($bytes >= 1073741824) return round($bytes/1073741824, 2) . ' GB';
    if ($bytes >= 1048576)    return round($bytes/1048576, 2) . ' MB';
    if ($bytes >= 1024)       return round($bytes/1024, 2) . ' KB';
    return $bytes . ' B';
}

// ── JSON helpers ────────────────────────────────────────────
function json_response(array $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_success(mixed $data = null, string $msg = 'OK'): never {
    json_response(['success' => true, 'message' => $msg, 'data' => $data]);
}

function json_error(string $msg, int $code = 400): never {
    json_response(['success' => false, 'message' => $msg], $code);
}

// ── File-based cache ────────────────────────────────────────
function cache_get(string $key): mixed {
    $file = CACHE_PATH . md5($key) . '.cache';
    if (!file_exists($file)) return null;
    $data = unserialize(file_get_contents($file));
    if ($data['expires'] < time()) { unlink($file); return null; }
    return $data['value'];
}

function cache_set(string $key, mixed $value, int $ttl = 300): void {
    if (!is_dir(CACHE_PATH)) mkdir(CACHE_PATH, 0755, true);
    file_put_contents(
        CACHE_PATH . md5($key) . '.cache',
        serialize(['expires' => time() + $ttl, 'value' => $value]),
        LOCK_EX
    );
}

function cache_delete(string $key): void {
    $file = CACHE_PATH . md5($key) . '.cache';
    if (file_exists($file)) unlink($file);
}

// ── IP & device detection ───────────────────────────────────
function get_ip(): string {
    foreach (['HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

function hash_ip(string $ip): string {
    return hash('sha256', $ip . 'fh_salt_2025');
}

function detect_device(): string {
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone'))
        return 'mobile';
    if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad'))
        return 'tablet';
    return 'desktop';
}

// ── Affiliate tracking ──────────────────────────────────────
function get_ref_code(): ?string {
    if (!empty($_GET['ref'])) {
        $_COOKIE['fh_ref'] = $_GET['ref'];
        setcookie('fh_ref', $_GET['ref'], time() + 86400 * 30, '/');
        return $_GET['ref'];
    }
    return $_COOKIE['fh_ref'] ?? null;
}

function track_affiliate_click(string $ref_code, ?int $video_id = null): void {
    // All roles can have referral links now (viewer, creator, admin)
    $affiliate = db_fetch("SELECT id FROM users WHERE ref_code = ?", [$ref_code]);
    if (!$affiliate) return;
    db_insert('affiliate_clicks', [
        'affiliate_id' => $affiliate['id'],
        'video_id'     => $video_id,
        'ip_hash'      => hash_ip(get_ip()),
        'ref_code'     => $ref_code,
        'device'       => detect_device(),
    ]);
}

// ── Pagination ──────────────────────────────────────────────
function paginate(int $total, int $per_page, int $current): array {
    $pages = (int)ceil($total / $per_page);
    return [
        'total'    => $total,
        'per_page' => $per_page,
        'current'  => $current,
        'pages'    => $pages,
        'offset'   => ($current - 1) * $per_page,
        'has_prev' => $current > 1,
        'has_next' => $current < $pages,
    ];
}

// ── Meta tags helper ────────────────────────────────────────
function meta_tags(array $tags): string {
    $out = '';
    foreach ($tags as $name => $content) {
        $out .= '<meta name="' . e($name) . '" content="' . e($content) . '">' . "\n";
    }
    return $out;
}

// ── Redirect helper ─────────────────────────────────────────
function redirect(string $url, int $code = 302): never {
    header("Location: $url", true, $code);
    exit;
}

// ── Upload handlers ─────────────────────────────────────────
function allowed_image(string $mime): bool {
    return in_array($mime, ['image/jpeg','image/png','image/webp','image/gif'], true);
}

function allowed_video(string $mime): bool {
    return in_array($mime, ['video/mp4','video/webm','video/quicktime','video/x-matroska'], true);
}

function unique_filename(string $ext): string {
    return bin2hex(random_bytes(16)) . '.' . $ext;
}

// ── Flash messages ──────────────────────────────────────────
function flash(string $type, string $msg): void {
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function get_flash(): array {
    $msgs = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $msgs;
}

/** Site logo URL (custom upload or default SVG). */
function site_logo_url(): string {
    $custom = trim(setting('site_logo', ''));
    if ($custom !== '') {
        if (str_starts_with($custom, 'http')) {
            return $custom;
        }
        $path = __DIR__ . '/../uploads/branding/' . $custom;
        if (is_file($path)) {
            return BASE_URL . '/uploads/branding/' . $custom;
        }
    }
    return BASE_URL . '/assets/img/logo.svg';
}

/**
 * Responsive site logo (icon + name). Variants: nav, auth, footer, studio.
 */
/** Absolute path to a stored video file. */
function video_file_path(string $videoUrl): string {
    $name = basename($videoUrl);
    return VIDEO_PATH . $name;
}

/** Save detected duration (seconds) for a video; returns stored value. */
function fh_save_video_duration(int $videoId, int $seconds, ?int $ownerId = null): int {
    $seconds = max(0, min(86400, $seconds));
    if ($videoId < 1 || $seconds < 1) {
        return 0;
    }
    $video = db_fetch('SELECT id, user_id, duration FROM videos WHERE id=?', [$videoId]);
    if (!$video) {
        return 0;
    }
    $current = (int)$video['duration'];
    if ($ownerId !== null && (int)$video['user_id'] !== $ownerId && !is_admin()) {
        return 0;
    }
    if ($ownerId === null && $current > 0) {
        return $current;
    }
    if ($current >= $seconds) {
        return $current;
    }
    db_update('videos', ['duration' => $seconds], 'id=?', [$videoId]);
    return $seconds;
}

/** Probe duration from file (ffprobe/ffmpeg, then MP4/WebM header scan). */
function fh_probe_video_duration(string $videoUrl): int {
    $path = video_file_path($videoUrl);
    if (!is_file($path)) {
        return 0;
    }

    $fromFf = fh_probe_duration_ffprobe($path);
    if ($fromFf > 0) {
        return $fromFf;
    }

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if ($ext === 'webm') {
        return fh_webm_duration_seconds($path);
    }

    return fh_mp4_duration_seconds($path);
}

/** Run ffprobe/ffmpeg if available (Windows + Linux paths). */
function fh_probe_duration_ffprobe(string $path): int {
    if (!function_exists('shell_exec')) {
        return 0;
    }

    $escaped = escapeshellarg($path);
    $bins    = ['ffprobe', 'ffmpeg'];
    if (PHP_OS_FAMILY === 'Windows') {
        $bins = array_merge([
            'C:\\ffmpeg\\bin\\ffprobe.exe',
            'C:\\ffmpeg\\bin\\ffmpeg.exe',
            'C:\\xampp\\ffmpeg\\bin\\ffprobe.exe',
            'C:\\xampp\\ffmpeg\\bin\\ffmpeg.exe',
        ], $bins);
    }

    foreach ($bins as $bin) {
        $isProbe = str_contains(strtolower($bin), 'ffprobe');
        $cmd     = $isProbe
            ? "$bin -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 $escaped 2>&1"
            : "$bin -i $escaped 2>&1";
        $out = @shell_exec($cmd);
        if (!$out) {
            continue;
        }
        if ($isProbe && preg_match('/[\d.]+/', trim($out), $m)) {
            $sec = (float)$m[0];
            if ($sec > 0) {
                return max(1, (int)round($sec));
            }
        }
        if (!$isProbe && preg_match('/Duration:\s*(\d+):(\d+):([\d.]+)/', $out, $m)) {
            return max(1, (int)round((int)$m[1] * 3600 + (int)$m[2] * 60 + (float)$m[3]));
        }
    }

    return 0;
}

/** Parse mvhd from MP4/MOV (scans start + end of file — moov is often at the end). */
function fh_mp4_duration_seconds(string $path): int {
    $size = @filesize($path);
    if (!$size || $size < 32) {
        return 0;
    }

    $chunks = [];
    $chunks[] = @file_get_contents($path, false, null, 0, min($size, 4 * 1024 * 1024));
    if ($size > 512 * 1024) {
        $chunks[] = @file_get_contents($path, false, null, max(0, $size - 512 * 1024));
    }
    $blob = implode('', array_filter($chunks, 'is_string'));

    $pos = 0;
    while (($pos = strpos($blob, 'mvhd', $pos)) !== false) {
        $hdr = $pos - 4;
        if ($hdr < 0) {
            $pos++;
            continue;
        }
        $bodyOff = $pos + 4;
        if ($bodyOff + 24 > strlen($blob)) {
            $pos++;
            continue;
        }
        $body    = substr($blob, $bodyOff, 64);
        $version = ord($body[0]);
        if ($version === 0) {
            $timescale = unpack('N', substr($body, 12, 4))[1];
            $units     = unpack('N', substr($body, 16, 4))[1];
        } else {
            $timescale = unpack('N', substr($body, 20, 4))[1];
            $hi        = unpack('N', substr($body, 24, 4))[1];
            $lo        = unpack('N', substr($body, 28, 4))[1];
            $units     = ($hi * 4294967296) + $lo;
        }
        if ($timescale > 0 && $units > 0) {
            return max(1, (int)round($units / $timescale));
        }
        $pos++;
    }

    return 0;
}

/** Parse duration from WebM (EBML Segment/Info). */
function fh_webm_duration_seconds(string $path): int {
    $blob = @file_get_contents($path, false, null, 0, min(filesize($path) ?: 0, 2 * 1024 * 1024));
    if (!$blob) {
        return 0;
    }
    // Float duration element id 0x4489 — followed by 8-byte IEEE double (seconds)
    $needle = "\x44\x89";
    $pos    = 0;
    while (($pos = strpos($blob, $needle, $pos)) !== false) {
        $lenPos = $pos + 2;
        if ($lenPos >= strlen($blob)) {
            break;
        }
        $lenByte = ord($blob[$lenPos]);
        if ($lenByte === 0x88 && $lenPos + 9 <= strlen($blob)) {
            $bytes = substr($blob, $lenPos + 1, 8);
            $parts = unpack('E', $bytes);
            $sec   = (float)($parts[1] ?? 0);
            if ($sec > 0) {
                return max(1, (int)round($sec));
            }
        }
        $pos++;
    }
    return 0;
}

/** Ensure a video row has duration; probe file if still zero. */
function fh_ensure_video_duration(int $videoId): int {
    $video = db_fetch('SELECT id, duration, video_url FROM videos WHERE id=?', [$videoId]);
    if (!$video) {
        return 0;
    }
    $current = (int)$video['duration'];
    if ($current > 0) {
        return $current;
    }
    if (empty($video['video_url'])) {
        return 0;
    }
    $secs = fh_probe_video_duration($video['video_url']);
    if ($secs > 0) {
        db_update('videos', ['duration' => $secs], 'id=?', [$videoId]);
        return $secs;
    }
    return 0;
}

/** Backfill duration for videos that still show 0:00. */
function fh_sync_zero_durations(array $videos, int $limit = 20): void {
    $n = 0;
    foreach ($videos as $v) {
        if ($n >= $limit) {
            break;
        }
        if ((int)($v['duration'] ?? 0) > 0 || empty($v['video_url'])) {
            continue;
        }
        $secs = fh_ensure_video_duration((int)$v['id']);
        if ($secs > 0) {
            $n++;
        }
    }
}

function render_site_logo(string $variant = 'nav', bool $link = true): string {
    $name  = setting('site_name', 'FreeHub');
    $href  = e(BASE_URL . '/');
    $label = e($name . ' Home');
    $classes = 'site-logo site-logo--' . preg_replace('/[^a-z]/', '', $variant);

    // Optimized height sizes for the pill container
    $heights = ['nav' => 38, 'auth' => 52, 'footer' => 34, 'studio' => 32];
    $h = $heights[$variant] ?? 38;

    $font_size = match($variant) {
        'auth'   => '20',
        'footer' => '13',
        'studio' => '12',
        default  => '15',
    };
    $icon_r = match($variant) {
        'auth'   => 14,
        'footer' => 9,
        'studio' => 9,
        default  => 11,
    };

    // Calculate layout spacing inside the pill
    $icon_cx = $icon_r + 6;
    $icon_cy = (int)($h / 2);
    $text_x  = $icon_cx + $icon_r + 8; // gap between play icon and text
    $text_y  = (int)($h * 0.68); // vertical alignment

    // Estimate text width to size SVG viewBox
    $name_len = mb_strlen($name);
    $text_w   = (int)($name_len * (int)$font_size * 0.72);
    $vb_w     = $text_x + $text_w + 14;

    // Play triangle coords (centered in play button circle)
    $tri_size = (int)($icon_r * 0.50);
    $tri_x    = $icon_cx - (int)($tri_size * 0.4);
    $tri_y    = $icon_cy - $tri_size;
    $tri_x2   = $icon_cx + (int)($tri_size * 0.8);
    $tri_y2   = $icon_cy;
    $tri_x3   = $tri_x;
    $tri_y3   = $icon_cy + $tri_size;

    // Premium multi-color pill background (var(--accent) base + neon pink + royal indigo)
    // Works beautifully on both dark and light backgrounds
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $vb_w . ' ' . $h . '" height="' . $h . '" style="display:block;max-width:100%" role="img" aria-label="' . $label . '">
      <defs>
        <!-- Pill Background Gradient: Accent -> Vibrant Pink -> Royal Violet -->
        <linearGradient id="fh-pill-grad-' . $variant . '" x1="0" y1="0" x2="1" y2="0">
          <stop offset="0%" stop-color="var(--accent)"/>
          <stop offset="50%" stop-color="#ff007f"/>
          <stop offset="100%" stop-color="#7000ff"/>
        </linearGradient>
        <!-- Soft premium shadow for depth -->
        <filter id="fh-shadow-' . $variant . '" x="-10%" y="-10%" width="120%" height="120%">
          <feDropShadow dx="0" dy="1" stdDeviation="1" flood-color="#000" flood-opacity="0.35"/>
        </filter>
      </defs>
      <!-- Pill background rect -->
      <rect x="0" y="0" width="' . $vb_w . '" height="' . $h . '" rx="' . ($h / 2) . '" fill="url(#fh-pill-grad-' . $variant . ')" filter="url(#fh-shadow-' . $variant . ')"/>
      <!-- Semi-transparent play circle wrapper -->
      <circle cx="' . $icon_cx . '" cy="' . $icon_cy . '" r="' . $icon_r . '" fill="rgba(255,255,255,0.22)"/>
      <!-- Crisp white play triangle -->
      <polygon points="' . $tri_x . ',' . $tri_y . ' ' . $tri_x2 . ',' . $tri_y2 . ' ' . $tri_x . ',' . $tri_y3 . '" fill="#fff"/>
      <!-- Site name inside pill -->
      <text x="' . $text_x . '" y="' . $text_y . '" font-family="system-ui,-apple-system,sans-serif" font-weight="800" font-size="' . $font_size . '" fill="#fff" letter-spacing="0.6">' . e($name) . '</text>
    </svg>';

    if ($link) {
        return '<a href="' . $href . '" class="' . $classes . '" aria-label="' . $label . '" style="text-decoration:none;display:inline-flex;align-items:center">' . $svg . '</a>';
    }
    return '<span class="' . $classes . '" style="display:inline-flex;align-items:center">' . $svg . '</span>';
}

require_once __DIR__ . '/earnings.php';

/**
 * Video card HTML for grids (homepage, search, channel).
 *
 * @param array<string,mixed> $v Video row (must include id, user_id, duration, etc.)
 * @param array<string,mixed> $opts ref, earnings_usd (float|null)
 */
/** Options for render_video_card (ref link + creator earnings). */
function fh_video_card_opts(array $v, array $earningsMap = [], string $ref = ''): array {
    $opts = ['ref' => $ref];
    if (is_logged_in() && is_creator() && (int)($v['user_id'] ?? 0) === (int)auth_user()['id']) {
        $opts['earnings_usd'] = $earningsMap[(int)$v['id']] ?? 0.0;
    }
    return $opts;
}

function render_video_card(array $v, array $opts = []): string {
    $ref       = $opts['ref'] ?? '';
    $earnings  = isset($opts['earnings_usd']) ? (float)$opts['earnings_usd'] : null;
    $ref_param = $ref ? '&ref=' . urlencode($ref) : '';
    $url       = BASE_URL . '/watch.php?v=' . $v['id'] . $ref_param;
    $thumb     = thumb_url($v['thumbnail'] ?? null);
    $durSec    = (int)($v['duration'] ?? 0);
    $dur       = $durSec > 0 ? format_duration($durSec) : '';
    $views     = format_number((int)($v['views'] ?? 0));
    $ago       = time_ago($v['published_at'] ?? $v['created_at'] ?? 'now');
    $title     = e($v['title'] ?? '');

    $srcAttr  = !empty($v['video_url']) ? ' data-video-src="' . e(video_url($v['video_url'])) . '"' : '';
    $durBadge = $dur !== ''
        ? '<span class="video-duration">' . $dur . '</span>'
        : '<span class="video-duration video-duration--pending" data-video-id="' . (int)$v['id'] . '"' . $srcAttr . '>…</span>';

    $earningsHtml = '';
    if ($earnings !== null && is_logged_in() && is_creator()) {
        $currency = fh_user_currency();
        $label    = e(fh_format_money($earnings, $currency));
        $earningsHtml = '<div class="video-earnings" title="Watch-time earnings on this video" style="margin-top:4px;">'
            . '<svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>'
            . '<span>' . $label . ' earned</span></div>';
    }

    return <<<HTML
<article class="video-card fade-in" onclick="location.href='{$url}'">
  <div class="video-thumb" style="position:relative">
    <img src="{$thumb}" alt="{$title}" loading="lazy" decoding="async" width="320" height="180" class="thumb-main">
    {$durBadge}
  </div>
  <div class="video-info">
    <div style="min-width:0; width: 100%;">
      <div class="video-title">{$title}</div>
      <div class="video-meta">
        <span style="display:inline-flex;align-items:center;gap:3px">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          {$views}
        </span>
        <span>·</span>
        <span>{$ago}</span>
      </div>
      {$earningsHtml}
    </div>
  </div>
</article>
HTML;
}

function render_ad_card(string $placement_key): string {
    global $vid;
    $now = date('Y-m-d');
    $placements = db_fetchAll(
        "SELECT ap.device_target as placement_device, a.*
         FROM ads a
         JOIN ad_placements ap ON ap.assigned_ad_id = a.id
         WHERE ap.key_name = ?
           AND a.is_active = 1
           AND (a.start_date IS NULL OR a.start_date <= ?)
           AND (a.end_date IS NULL OR a.end_date >= ?)",
        [$placement_key, $now, $now]
    );

    if (!$placements) {
        $advertise_url = BASE_URL . '/admin/ads.php';
        return <<<HTML
<article class="video-card ad-card fade-in" style="cursor:pointer; border: 1.5px dashed var(--border); display:flex; flex-direction:column; justify-content:center; align-items:center; min-height:200px; background:rgba(255,255,255,0.01); border-radius:12px; transition:all 0.2s;" onclick="location.href='{$advertise_url}'">
  <div style="text-align:center; padding:20px; color:var(--text2)">
    <div style="font-size:1.8rem; margin-bottom:10px; filter: grayscale(0.2);">📺</div>
    <div style="font-weight:800; font-size:0.9rem; color:var(--text); letter-spacing:0.5px;">Advertise Here</div>
    <div style="font-size:0.75rem; margin-top:4px; opacity:0.8;">Place your ad on FreeHub</div>
  </div>
</article>
HTML;
    }

    $output = '';
    $vid_attr = $vid ? ' data-video-id="' . (int)$vid . '"' : '';

    foreach ($placements as $ad) {
        // Track impression and payouts
        fh_track_ad_event((int)$ad['id'], 'impression', $vid);

        $click_url = $ad['target_url'] ?: '#';
        $ad_id = $ad['id'];
        $title = e($ad['title']);
        $sponsored_tag = '<span style="position:absolute; left:8px; top:8px; background:rgba(0,0,0,0.65); backdrop-filter:blur(4px); -webkit-backdrop-filter:blur(4px); color:#fff; font-size:0.62rem; font-weight:800; padding:3px 8px; border-radius:4px; text-transform:uppercase; letter-spacing:0.5px; z-index:10; border:1px solid rgba(255,255,255,0.15); box-shadow: 0 2px 6px rgba(0,0,0,0.4);">Sponsored</span>';

        $device_class = '';
        if ($ad['placement_device'] === 'mobile' || $ad['device_target'] === 'mobile') {
            $device_class = ' ad-mobile-only';
        } elseif ($ad['placement_device'] === 'desktop' || $ad['device_target'] === 'desktop') {
            $device_class = ' ad-desktop-only';
        }

        $aspect_ratio = ($ad['ad_width'] && $ad['ad_height']) ? ((int)$ad['ad_width'] . '/' . (int)$ad['ad_height']) : '16/9';

        $inner_html = '';
        if ($ad['content_type'] === 'image' && $ad['image_url']) {
            $img_src = str_starts_with($ad['image_url'], 'http') ? $ad['image_url'] : BASE_URL . '/uploads/ads/' . $ad['image_url'];
            $inner_html = <<<HTML
<article class="video-card ad-card fade-in ad-click-link{$device_class}" data-ad-id="{$ad_id}" data-device-target="{$ad['placement_device']}"{$vid_attr} onclick="window.open('{$click_url}', '_blank');" style="cursor:pointer; min-height:auto;">
  <div class="video-thumb" style="position:relative; aspect-ratio:{$aspect_ratio}; background:#0c0c0d; display:flex; align-items:center; justify-content:center; overflow:hidden; border-radius:12px;">
    <img src="{$img_src}" alt="{$title}" loading="lazy" class="thumb-main" style="width:100%; height:100%; object-fit:cover">
    {$sponsored_tag}
    <span class="video-duration" style="background:rgba(0,0,0,0.8); right:8px; bottom:8px; font-weight:800; letter-spacing:0.5px; border-radius:4px; font-size:0.62rem; padding:2px 6px;">AD</span>
  </div>
</article>
HTML;
        } elseif ($ad['content_type'] === 'html') {
            $html_content = $ad['content'];
            
            $aspect_ratio_val = ($ad['ad_width'] && $ad['ad_height']) ? ((int)$ad['ad_width'] . '/' . (int)$ad['ad_height']) : '';
            $card_style = $aspect_ratio_val ? "min-height:auto; aspect-ratio:{$aspect_ratio_val};" : "min-height:200px;";
            $inner_style = $ad['ad_height'] ? "height:" . (int)$ad['ad_height'] . "px;" : "min-height:160px;";
            
            $inner_html = <<<HTML
<article class="video-card ad-card fade-in{$device_class}" data-device-target="{$ad['placement_device']}"{$vid_attr} style="{$card_style} display:flex; flex-direction:column; overflow:hidden; border-radius:12px; position:relative;">
  {$sponsored_tag}
  <div class="ad-html-content" style="flex:1; position:relative; overflow:hidden; z-index:1; {$inner_style}">
    {$html_content}
  </div>
</article>
HTML;
        } else {
            $content_text = e($ad['content']);
            $inner_html = <<<HTML
<article class="video-card ad-card fade-in ad-click-link{$device_class}" data-ad-id="{$ad_id}" data-device-target="{$ad['placement_device']}"{$vid_attr} onclick="window.open('{$click_url}', '_blank');" style="cursor:pointer; background:linear-gradient(135deg, rgba(99,102,241,0.06), rgba(139,92,246,0.03)); display:flex; flex-direction:column; justify-content:space-between; min-height:220px; border:1px solid rgba(99,102,241,0.15); border-radius:12px; transition:all 0.2s; position:relative;">
  {$sponsored_tag}
  <div style="padding:22px; flex:1; display:flex; flex-direction:column; justify-content:center; align-items:center; text-align:center">
    <div style="font-size:2rem; margin-bottom:12px; filter: drop-shadow(0 2px 6px rgba(99,102,241,0.25));">🚀</div>
    <div style="font-size:0.78rem; color:var(--text2); margin-top:8px; line-height:1.4; overflow:hidden; text-overflow:ellipsis; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; opacity:0.85;">
      {$content_text}
    </div>
  </div>
  <div style="border-top:1px solid rgba(99,102,241,0.15); padding:10px; background:rgba(0,0,0,0.12); text-align:center;">
    <span style="font-size:0.8rem; font-weight:700; color:var(--accent); letter-spacing:0.3px;">Learn More &rarr;</span>
  </div>
</article>
HTML;
        }
        $output .= $inner_html;
    }
    return $output;
}

function render_ad_placeholder(string $placement_key): string {
    global $vid;
    $now = date('Y-m-d');
    $placements = db_fetchAll(
        "SELECT ap.device_target as placement_device, ap.ad_width as placement_width, ap.ad_height as placement_height, ap.reload_interval as reload_interval, a.*
         FROM ads a
         JOIN ad_placements ap ON ap.assigned_ad_id = a.id
         WHERE ap.key_name = ?
           AND a.is_active = 1
           AND (a.start_date IS NULL OR a.start_date <= ?)
           AND (a.end_date IS NULL OR a.end_date >= ?)",
         [$placement_key, $now, $now]
    );

    if (!$placements) return '';

    $output = '';
    $vid_attr = $vid ? ' data-video-id="' . (int)$vid . '"' : '';

    foreach ($placements as $ad) {
        // Resolve width and height (placement values override ad values)
        $w = $ad['placement_width'] ?: $ad['ad_width'];
        $h = $ad['placement_height'] ?: $ad['ad_height'];

        $sponsored_label = '';
        if ($placement_key !== 'home_mobile_top' && $placement_key !== 'watch_below_player') {
            $sponsored_label = '<div style="font-size:.68rem;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;display:flex;align-items:center;gap:4px"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M12 8v8M8 12h8"/></svg>Sponsored</div>';
        }

        $size_style = '';
        if ($placement_key === 'home_mobile_top') {
            $size_style .= 'width:100% !important;';
        } else {
            if ($w) $size_style .= 'width:100%;max-width:' . (int)$w . 'px;';
        }
        if ($h) $size_style .= 'height:100%;max-height:' . (int)$h . 'px;';

        $device_class = '';
        if ($ad['placement_device'] === 'mobile' || $ad['device_target'] === 'mobile') {
            $device_class = ' ad-mobile-only';
        } elseif ($ad['placement_device'] === 'desktop' || $ad['device_target'] === 'desktop') {
            $device_class = ' ad-desktop-only';
        }

        $extra_class = '';
        if ($placement_key === 'home_mobile_top') {
            $extra_class = ' ad-full-width-mobile';
            $container_style = 'box-sizing:border-box;display:flex;align-items:center;justify-content:center;overflow:hidden;';
            if ($h) {
                $container_style .= 'height:' . (int)$h . 'px;';
            }
        } elseif ($placement_key === 'watch_below_player') {
            $extra_class = ' ad-watch-below-player';
            $container_style = 'box-sizing:border-box;display:flex;align-items:center;justify-content:center;overflow:hidden;';
            if ($w) {
                $container_style .= 'width:100%;max-width:' . (int)$w . 'px;';
            }
            if ($h) {
                $container_style .= 'height:' . (int)$h . 'px;';
            }
        } else {
            $container_style = 'margin:24px auto;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);text-align:center;box-sizing:border-box;';
            if ($w) {
                $container_style .= 'width:100%;max-width:' . (int)$w . 'px;';
            }
            if ($h) {
                $container_style .= 'height:' . (int)$h . 'px;padding:0 16px;display:flex;flex-direction:column;justify-content:center;';
            } else {
                $container_style .= 'padding:16px;';
            }
        }

        $inner = '';
        if ($ad['content_type'] === 'image' && $ad['image_url']) {
            $img_src = str_starts_with($ad['image_url'], 'http') ? $ad['image_url'] : BASE_URL . '/uploads/ads/' . $ad['image_url'];
            $click_url = $ad['target_url'] ?: '#';
            $inner = '<a href="' . e($click_url) . '" target="_blank" rel="noopener" data-ad-id="' . $ad['id'] . '" class="ad-click-link" style="display:block;width:100%;height:100%;">'
                   . '<img src="' . e($img_src) . '" alt="' . e($ad['title']) . '" style="width:100%;' . ($h ? 'height:100%;object-fit:contain;' : 'height:auto;') . 'display:block;border-radius:4px;margin:0 auto">'
                   . '</a>';
        } elseif ($ad['content_type'] === 'html') {
            $inner = '<div class="ad-html-content" style="width:100%;' . ($h ? 'height:100%;' : '') . 'display:block;margin:0 auto;overflow:hidden;">'
                   . '<template class="ad-html-template">' . $ad['content'] . '</template>'
                   . '</div>';
        } else {
            $click_url = $ad['target_url'] ?: '#';
            $inner = '<a href="' . e($click_url) . '" target="_blank" rel="noopener" data-ad-id="' . $ad['id'] . '" class="ad-click-link" style="font-weight:700;color:var(--accent);text-decoration:underline;font-size:.9rem">'
                   . e($ad['content'] ?: $ad['title']) . '</a>';
        }

        $output .= '<div class="ad-sponsored-container' . $device_class . $extra_class . '" data-placement="' . e($placement_key) . '" data-device-target="' . e($ad['placement_device']) . '" data-reload-interval="' . (int)$ad['reload_interval'] . '" data-ad-id="' . (int)$ad['id'] . '"' . $vid_attr . ' style="' . $container_style . '">'
             . $sponsored_label
             . '<div class="ad-creative-wrapper" style="margin:0 auto;display:block;width:100%;max-width:100%;' . $size_style . '">' . $inner . '</div>'
             . '</div>';
    }

    return $output;
}
