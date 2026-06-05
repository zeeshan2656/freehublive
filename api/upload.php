<?php
// ============================================================
// FreeHub.Live — Turbo Chunked Upload API (Deferred-Publish)
// ============================================================
// Optimized for speed: large chunks, parallel writes, minimal overhead.
// POST?session_id=...&token=...           : upload chunk
// POST?session_id=...&token=...&finalize=1 : finalize + publish
// GET?session_id=...&action=status         : returns uploaded bytes
// ============================================================

// ── Runtime PHP tuning for large uploads ──
@ini_set('max_execution_time', 0);
@ini_set('memory_limit', '512M');
@ini_set('upload_max_filesize', '256M');
@ini_set('post_max_size', '260M');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

// ── GET: Check upload status ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'status') {
    if (!is_logged_in()) json_error('Unauthorized', 401);
    $sid = (int)($_GET['session_id'] ?? 0);
    if (!$sid) json_error('Missing session_id');
    $session = db_fetch('SELECT id, user_id, token, meta_json FROM upload_sessions WHERE id=?', [$sid]);
    if (!$session) json_error('Not found', 404);
    if ($session['token'] !== ($_GET['token'] ?? '')) json_error('Forbidden', 403);
    if ((int)$session['user_id'] !== (int)auth_user()['id'] && !is_admin()) json_error('Forbidden', 403);
    $meta = json_decode($session['meta_json'] ?? '{}', true) ?: [];
    $basePath = ((int)($meta['is_reel'] ?? 0) === 1) ? REEL_PATH : VIDEO_PATH;
    $temp = $basePath . '._upload_' . $sid . '.part';
    $size = is_file($temp) ? filesize($temp) : 0;
    json_success(['uploaded' => $size]);
}

// ── POST: Receive chunk or finalize ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_logged_in()) json_error('Unauthorized', 401);
    $sid   = (int)($_GET['session_id'] ?? 0);
    $token = $_GET['token'] ?? '';
    if (!$sid || !$token) json_error('Missing params');

    $session = db_fetch('SELECT id, user_id, token, meta_json, temp_thumb, status FROM upload_sessions WHERE id=?', [$sid]);
    if (!$session) json_error('Not found', 404);
    if ($session['token'] !== $token) json_error('Forbidden', 403);
    if ((int)$session['user_id'] !== (int)auth_user()['id'] && !is_admin()) json_error('Forbidden', 403);

    $meta = json_decode($session['meta_json'] ?? '{}', true) ?: [];
    $is_reel = (int)($meta['is_reel'] ?? 0);
    $targetPath = ($is_reel === 1) ? REEL_PATH : VIDEO_PATH;
    if (!is_dir($targetPath)) mkdir($targetPath, 0755, true);
    $tempPath = $targetPath . '._upload_' . $sid . '.part';

    // Read chunk data
    if (isset($_FILES['chunk'])) {
        $chunk = $_FILES['chunk'];
        if ($chunk['error'] !== UPLOAD_ERR_OK) json_error('Upload chunk error');
        $in = fopen($chunk['tmp_name'], 'rb');
    } else {
        $in = fopen('php://input', 'rb');
    }
    if (!$in) json_error('No chunk data');

    // ── Write chunk: support Content-Range for parallel offset writes ──
    $rangeHeader = $_SERVER['HTTP_CONTENT_RANGE'] ?? '';
    if ($rangeHeader && preg_match('/bytes (\d+)-(\d+)\//', $rangeHeader, $m)) {
        // Parallel write at specific offset — use c+b (create-or-open, no truncate)
        $offset = (int)$m[1];
        $out = fopen($tempPath, 'c+b');
        if (!$out) { fclose($in); json_error('Server file error'); }
        // Lock the region we're writing to avoid corruption
        flock($out, LOCK_EX);
        fseek($out, $offset);
    } else {
        // Sequential append (default)
        $out = fopen($tempPath, 'ab');
        if (!$out) { fclose($in); json_error('Server file error'); }
        flock($out, LOCK_EX);
    }
    // Use large buffer for faster streaming (256KB reads)
    $bufSize = 262144;
    while (!feof($in)) {
        $data = fread($in, $bufSize);
        if ($data !== false && $data !== '') fwrite($out, $data);
    }
    flock($out, LOCK_UN);
    fclose($in); fclose($out);

    // ── Finalize: create video or reel record in a single transaction ──
    if (!empty($_GET['finalize'])) {
        $ext = pathinfo($_GET['filename'] ?? '', PATHINFO_EXTENSION) ?: 'mp4';
        $finalName = unique_filename($ext);
        $finalPath = $targetPath . $finalName;
        if (!rename($tempPath, $finalPath)) json_error('Could not finalize upload', 500);

        $fsize = filesize($finalPath);
        if ($fsize <= 0) {
            @unlink($finalPath);
            db_update('upload_sessions', ['status' => 'failed'], 'id=?', [$sid]);
            json_error('Verification failed: empty file.');
        }

        // Read metadata from session
        $meta = json_decode($session['meta_json'] ?? '{}', true) ?: [];
        $title       = $meta['title'] ?? '';
        $description = $meta['description'] ?? '';
        $tags        = $meta['tags'] ?? '';
        $visibility  = $meta['visibility'] ?? 'public';
        $is_reel     = (int)($meta['is_reel'] ?? 0);
        $duration    = (int)($meta['duration'] ?? 0);
        if ($is_reel === 1 && $duration > 60) {
            $is_reel = 0;
        }
        $category_ids = $meta['category_ids'] ?? [];
        $first_cat   = !empty($category_ids) ? (int)$category_ids[0] : null;
        if ($is_reel === 1) {
            $thumbnail = null;
        } else {
            $thumbnail   = !empty($session['temp_thumb']) ? $session['temp_thumb'] : 'default-thumb.jpg';
        }

        $uid = (int)$session['user_id'];

        // ── BEGIN TRANSACTION: create video or reel record atomically ──
        global $pdo;
        $pdo->beginTransaction();
        try {
            if ($is_reel === 1) {
                $video_id = db_insert('reels', [
                    'user_id'    => $uid,
                    'video_url'  => $finalName,
                    'title'      => empty($title) ? null : $title,
                    'status'     => 'published',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            } else {
                // Generate unique slug
                $slug = slugify($title ?: 'Untitled Video');
                $base_slug = $slug;
                $i = 1;
                while (db_fetch("SELECT id FROM videos WHERE slug=?", [$slug])) {
                    $slug = $base_slug . '-' . $i++;
                }

                $video_id = db_insert('videos', [
                    'user_id'      => $uid,
                    'category_id'  => $first_cat,
                    'title'        => $title ?: 'Untitled Video',
                    'slug'         => $slug,
                    'description'  => $description,
                    'tags'         => $tags,
                    'video_url'    => $finalName,
                    'thumbnail'    => $thumbnail,
                    'file_size'    => $fsize,
                    'duration'     => $duration,
                    'visibility'   => $visibility,
                    'status'       => 'published',
                    'published_at' => date('Y-m-d H:i:s'),
                    'is_reel'      => 0,
                ]);

                // Insert category mappings
                if ($video_id && !empty($category_ids)) {
                    foreach ($category_ids as $cid) {
                        db_insert('video_categories', [
                            'video_id'    => $video_id,
                            'category_id' => (int)$cid,
                        ]);
                    }
                }
            }

            // Mark upload session as completed
            db_update('upload_sessions', [
                'video_id' => $video_id,
                'status'   => 'completed',
            ], 'id=?', [$sid]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            // Clean up the finalized video file on transaction failure
            @unlink($finalPath);
            if ($thumbnail && !str_starts_with($thumbnail, 'http')) {
                @unlink(THUMB_PATH . $thumbnail);
            }
            db_update('upload_sessions', ['status' => 'failed'], 'id=?', [$sid]);
            json_error('Publish failed: ' . $e->getMessage(), 500);
        }

        @ignore_user_abort(true);
        json_success([
            'finalized'  => true,
            'video_id'   => $video_id,
            'video_url'  => $is_reel === 1 ? reel_url($finalName) : video_url($finalName),
            'file_size'  => $fsize,
        ]);
    }

    // Non-finalize chunk: return current uploaded size immediately
    // Flush response ASAP so client can send next chunk without waiting
    $response = json_encode(['success' => true, 'data' => ['uploaded' => is_file($tempPath) ? filesize($tempPath) : 0]]);
    header('Content-Length: ' . strlen($response));
    echo $response;
    if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
    exit;
}

json_error('Invalid request', 400);
