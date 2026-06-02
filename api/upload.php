<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// ============================================================
// FreeHub.Live — Chunked Upload API (Deferred-Publish Workflow)
// ============================================================
// Chunks are uploaded against an upload_sessions row.
// NO videos record exists until finalization succeeds.
// POST?session_id=...&token=...           : upload chunk
// POST?session_id=...&token=...&finalize=1 : finalize + publish in transaction
// GET?session_id=...&action=status         : returns uploaded bytes
// ============================================================

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

// ── GET: Check upload status ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'status') {
    if (!is_logged_in()) json_error('Unauthorized', 401);
    $sid = (int)($_GET['session_id'] ?? 0);
    if (!$sid) json_error('Missing session_id');
    $session = db_fetch('SELECT id, user_id, token FROM upload_sessions WHERE id=?', [$sid]);
    if (!$session) json_error('Not found', 404);
    if ($session['token'] !== ($_GET['token'] ?? '')) json_error('Forbidden', 403);
    if ((int)$session['user_id'] !== (int)auth_user()['id'] && !is_admin()) json_error('Forbidden', 403);
    $temp = VIDEO_PATH . '._upload_' . $sid . '.part';
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

    if (!is_dir(VIDEO_PATH)) mkdir(VIDEO_PATH, 0755, true);
    $tempPath = VIDEO_PATH . '._upload_' . $sid . '.part';

    // Read chunk data
    if (isset($_FILES['chunk'])) {
        $chunk = $_FILES['chunk'];
        if ($chunk['error'] !== UPLOAD_ERR_OK) json_error('Upload chunk error');
        $in = fopen($chunk['tmp_name'], 'rb');
    } else {
        $in = fopen('php://input', 'rb');
    }
    if (!$in) json_error('No chunk data');

    // Append to temp file
    $out = fopen($tempPath, file_exists($tempPath) ? 'ab' : 'wb');
    if (!$out) { fclose($in); json_error('Server file error'); }
    stream_copy_to_stream($in, $out);
    fclose($in); fclose($out);

    // ── Finalize: create video record in a single transaction ──
    if (!empty($_GET['finalize'])) {
        $ext = pathinfo($_GET['filename'] ?? '', PATHINFO_EXTENSION) ?: 'mp4';
        $finalName = unique_filename($ext);
        $finalPath = VIDEO_PATH . $finalName;
        if (!rename($tempPath, $finalPath)) json_error('Could not finalize upload', 500);

        $fsize = filesize($finalPath);
        if ($fsize <= 0) {
            @unlink($finalPath);
            db_update('upload_sessions', ['status' => 'failed'], 'id=?', [$sid]);
            json_error('Verification failed: empty file.');
        }

        // Read metadata from session
        $meta = json_decode($session['meta_json'] ?? '{}', true) ?: [];
        $title       = $meta['title'] ?? 'Untitled Video';
        $description = $meta['description'] ?? '';
        $tags        = $meta['tags'] ?? '';
        $visibility  = $meta['visibility'] ?? 'public';
        $is_reel     = (int)($meta['is_reel'] ?? 0);
        $duration    = (int)($meta['duration'] ?? 0);
        $category_ids = $meta['category_ids'] ?? [];
        $first_cat   = !empty($category_ids) ? (int)$category_ids[0] : null;
        $thumbnail   = $session['temp_thumb'] ?? null;

        // Generate unique slug
        $slug = slugify($title);
        $base_slug = $slug;
        $i = 1;
        while (db_fetch("SELECT id FROM videos WHERE slug=?", [$slug])) {
            $slug = $base_slug . '-' . $i++;
        }

        $uid = (int)$session['user_id'];

        // ── BEGIN TRANSACTION: create video + categories atomically ──
        global $pdo;
        $pdo->beginTransaction();
        try {
            $video_id = db_insert('videos', [
                'user_id'      => $uid,
                'category_id'  => $first_cat,
                'title'        => $title,
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
                'is_reel'      => $is_reel,
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
            'video_url'  => video_url($finalName),
            'file_size'  => $fsize,
        ]);
    }

    // Non-finalize chunk: return current uploaded size
    json_success(['uploaded' => is_file($tempPath) ? filesize($tempPath) : 0]);
}

json_error('Invalid request', 400);
