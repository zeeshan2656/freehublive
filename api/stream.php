<?php
// ============================================================
// FreeHub.Live — Ultra-Fast Byte-Range Video Streaming
// ============================================================
// Endpoint: /api/stream.php?v=VIDEO_ID  (videos)
//           /api/stream.php?r=REEL_ID   (reels)
//           /api/stream.php?f=FILENAME  (direct filename — admin only)
//
// Supports RFC 7233 HTTP Range Requests (206 Partial Content)
// Enables instant video seeking without full re-download.
// Compatible with Hostinger shared hosting (no FFmpeg required).
// ============================================================

// ── Aggressive runtime config for streaming ──
@ini_set('max_execution_time', 0);     // No timeout during streaming
@ini_set('memory_limit', '128M');
@ini_set('output_buffering', 'Off');
@ini_set('zlib.output_compression', 'Off');

// Disable any output compression — it breaks Range responses
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', '1');
}
@ini_set('zlib.output_compression', 0);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// ── Resolve the file to stream ─────────────────────────────
$filepath   = null;
$mime_type  = 'video/mp4';
$video_row  = null;
$is_private = false;

if (isset($_GET['v']) && (int)$_GET['v'] > 0) {
    // Stream a video by DB ID
    $vid = (int)$_GET['v'];
    $video_row = db_fetch(
        "SELECT id, video_url, visibility, status FROM videos WHERE id=? AND status='published'",
        [$vid]
    );
    if (!$video_row) {
        http_response_code(404);
        exit('Video not found');
    }
    if ($video_row['visibility'] === 'private') {
        if (!is_logged_in()) {
            http_response_code(403);
            exit('Forbidden');
        }
        $owner = db_fetch("SELECT user_id FROM videos WHERE id=?", [$vid]);
        if (!is_admin() && (int)auth_user()['id'] !== (int)($owner['user_id'] ?? 0)) {
            http_response_code(403);
            exit('Forbidden');
        }
    }
    $url = $video_row['video_url'];
    if (str_starts_with($url, 'http')) {
        // External URL — redirect directly
        header("Location: $url", true, 302);
        exit;
    }
    $filepath = VIDEO_PATH . basename($url);
    $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    $mime_type = match($ext) {
        'webm'  => 'video/webm',
        'ogg'   => 'video/ogg',
        'mov'   => 'video/quicktime',
        default => 'video/mp4',
    };

} elseif (isset($_GET['r']) && (int)$_GET['r'] > 0) {
    // Stream a reel by DB ID
    $rid = (int)$_GET['r'];
    $reel_row = db_fetch(
        "SELECT id, video_url, status FROM reels WHERE id=? AND status='published'",
        [$rid]
    );
    if (!$reel_row) {
        http_response_code(404);
        exit('Reel not found');
    }
    $url = $reel_row['video_url'];
    if (str_starts_with($url, 'http')) {
        header("Location: $url", true, 302);
        exit;
    }
    $filepath = REEL_PATH . basename($url);
    $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    $mime_type = match($ext) {
        'webm'  => 'video/webm',
        'ogg'   => 'video/ogg',
        default => 'video/mp4',
    };

} elseif (isset($_GET['f']) && is_admin()) {
    // Direct filename — admin-only raw stream
    $fname = basename($_GET['f']);
    // Try video path then reel path
    if (is_file(VIDEO_PATH . $fname)) {
        $filepath = VIDEO_PATH . $fname;
    } elseif (is_file(REEL_PATH . $fname)) {
        $filepath = REEL_PATH . $fname;
    } else {
        http_response_code(404);
        exit('File not found');
    }
    $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    $mime_type = match($ext) {
        'webm'  => 'video/webm',
        'ogg'   => 'video/ogg',
        'mov'   => 'video/quicktime',
        default => 'video/mp4',
    };
} else {
    http_response_code(400);
    exit('Bad request');
}

// ── Validate file exists ───────────────────────────────────
if (!$filepath || !is_file($filepath)) {
    http_response_code(404);
    exit('File not found on server');
}

// ── File stats ─────────────────────────────────────────────
$file_size = filesize($filepath);
if ($file_size === false || $file_size <= 0) {
    http_response_code(500);
    exit('Cannot stat file');
}

// ── Parse Range header ─────────────────────────────────────
$range_header = $_SERVER['HTTP_RANGE'] ?? null;
$start = 0;
$end   = $file_size - 1;
$is_range_request = false;

if ($range_header && preg_match('/bytes=(\d*)-(\d*)/i', $range_header, $m)) {
    $is_range_request = true;
    $range_start = $m[1] !== '' ? (int)$m[1] : null;
    $range_end   = $m[2] !== '' ? (int)$m[2] : null;

    if ($range_start === null) {
        // bytes=-N — last N bytes
        $start = max(0, $file_size - $range_end);
        $end   = $file_size - 1;
    } else {
        $start = $range_start;
        $end   = $range_end ?? ($file_size - 1);
    }

    // Clamp
    $end = min($end, $file_size - 1);

    if ($start > $end || $start >= $file_size) {
        // 416 Range Not Satisfiable
        header("HTTP/1.1 416 Range Not Satisfiable");
        header("Content-Range: bytes */$file_size");
        exit;
    }
}

$length = $end - $start + 1;

// ── ETag & conditional requests ────────────────────────────
$etag = '"' . md5($filepath . $file_size . filemtime($filepath)) . '"';
$last_modified = gmdate('D, d M Y H:i:s', filemtime($filepath)) . ' GMT';

// Handle conditional GET
if (
    (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) ||
    (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] === $last_modified)
) {
    http_response_code(304);
    exit;
}

// ── Stream response ─────────────────────────────────────────
if ($is_range_request) {
    http_response_code(206); // Partial Content
    header("Content-Range: bytes $start-$end/$file_size");
} else {
    http_response_code(200);
}

// ── Response headers ────────────────────────────────────────
header("Content-Type: $mime_type");
header("Content-Length: $length");
header("Accept-Ranges: bytes");
header("ETag: $etag");
header("Last-Modified: $last_modified");
header("Cache-Control: public, max-age=2592000");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Range");
header("Access-Control-Expose-Headers: Content-Range, Content-Length, Accept-Ranges");
header("X-Content-Type-Options: nosniff");

// Prevent output buffering from breaking the stream
if (ob_get_level()) {
    ob_end_clean();
}

// ── Stream in 256KB chunks ──────────────────────────────────
$chunk_size = 262144; // 256KB — optimal for shared hosting
$fp = @fopen($filepath, 'rb');
if (!$fp) {
    http_response_code(500);
    exit('Cannot open file');
}

if ($start > 0) {
    fseek($fp, $start);
}

$remaining = $length;
while ($remaining > 0 && !feof($fp) && !connection_aborted()) {
    $read = min($chunk_size, $remaining);
    $data = fread($fp, $read);
    if ($data === false) break;
    echo $data;
    $remaining -= strlen($data);
    flush();
}

fclose($fp);
exit;
