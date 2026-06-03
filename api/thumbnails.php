<?php
// FreeHub.Live — Thumbnail Save API
// POST: save auto-generated thumbnail from canvas capture
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if (!is_logged_in() && $action !== 'save_duration') {
    json_error('Unauthorized', 401);
}

// ── Save selected thumbnail ─────────────────────────────────
// Supports both session_id (pre-publish) and video_id (post-publish)
if ($action === 'save_thumbnail') {
    $input = json_decode(file_get_contents('php://input'), true);
    $video_id   = (int)($input['video_id'] ?? 0);
    $session_id = (int)($input['session_id'] ?? 0);
    $dataUrl    = $input['data_url'] ?? '';

    if ((!$video_id && !$session_id) || !$dataUrl) json_error('Missing params');

    // Decode base64 image
    if (!preg_match('#^data:image/jpeg;base64,#', $dataUrl)) json_error('Invalid image data');
    $b64  = substr($dataUrl, strpos($dataUrl, ',') + 1);
    $data = base64_decode($b64);
    if (!$data) json_error('Corrupt image data');

    if (!is_dir(THUMB_PATH)) mkdir(THUMB_PATH, 0755, true);
    $filename = unique_filename('jpg');
    file_put_contents(THUMB_PATH . $filename, $data);

    // ── Pre-publish: store in upload_sessions ──
    if ($session_id && !$video_id) {
        $uid = auth_user()['id'];
        $session = db_fetch('SELECT id, user_id, temp_thumb FROM upload_sessions WHERE id=?', [$session_id]);
        if (!$session || ((int)$session['user_id'] !== (int)$uid && auth_user()['role'] !== 'admin')) {
            @unlink(THUMB_PATH . $filename);
            json_error('Forbidden', 403);
        }

        // Delete old temporary thumbnail
        $old = $session['temp_thumb'];
        if ($old && !str_starts_with($old, 'http') && is_file(THUMB_PATH . $old)) {
            @unlink(THUMB_PATH . $old);
        }

        db_update('upload_sessions', ['temp_thumb' => $filename], 'id=?', [$session_id]);
        json_response(['success' => true, 'thumbnail_url' => thumb_url($filename)]);
    }

    // ── Post-publish: update videos table ──
    $uid = auth_user()['id'];
    $video = db_fetch("SELECT id, user_id, thumbnail FROM videos WHERE id=?", [$video_id]);
    if (!$video || ($video['user_id'] != $uid && auth_user()['role'] !== 'admin')) {
        @unlink(THUMB_PATH . $filename);
        json_error('Forbidden', 403);
    }

    // Delete old thumbnail
    $old = $video['thumbnail'];
    if ($old && !str_starts_with($old, 'http')) {
        $oldPath = THUMB_PATH . $old;
        if (file_exists($oldPath)) @unlink($oldPath);
    }

    db_update('videos', ['thumbnail' => $filename], 'id=?', [$video_id]);
    json_response(['success' => true, 'thumbnail_url' => thumb_url($filename)]);
}

// ── Save video duration (from browser metadata on upload / watch) ──
// Supports both session_id (pre-publish) and video_id (post-publish)
if ($action === 'save_duration') {
    $input      = json_decode(file_get_contents('php://input'), true);
    $video_id   = (int)($input['video_id'] ?? 0);
    $session_id = (int)($input['session_id'] ?? 0);
    $seconds    = (int)round((float)($input['duration'] ?? 0));

    if ((!$video_id && !$session_id) || $seconds < 1) {
        json_error('Missing params');
    }

    // ── Pre-publish: store duration in session meta_json ──
    if ($session_id && !$video_id) {
        $session = db_fetch('SELECT id, user_id, meta_json FROM upload_sessions WHERE id=?', [$session_id]);
        if (!$session) json_error('Session not found', 404);

        $uid = is_logged_in() ? (int)auth_user()['id'] : 0;
        if ($uid > 0 && (int)$session['user_id'] !== $uid && !is_admin()) {
            json_error('Forbidden', 403);
        }

        $meta = json_decode($session['meta_json'] ?? '{}', true) ?: [];
        $meta['duration'] = $seconds;
        db_update('upload_sessions', ['meta_json' => json_encode($meta, JSON_UNESCAPED_UNICODE)], 'id=?', [$session_id]);

        json_response([
            'success'   => true,
            'duration'  => $seconds,
            'formatted' => format_duration($seconds),
        ]);
    }

    // ── Post-publish: update videos table ──
    $video = db_fetch('SELECT id, user_id, duration FROM videos WHERE id=?', [$video_id]);
    if (!$video) {
        json_error('Video not found', 404);
    }

    $uid     = is_logged_in() ? (int)auth_user()['id'] : 0;
    $isOwner = $uid > 0 && (int)$video['user_id'] === $uid;
    $canSet  = $isOwner || ($uid && is_admin()) || (int)$video['duration'] === 0;
    if (!$canSet) {
        json_error('Forbidden', 403);
    }

    $saved = fh_save_video_duration($video_id, $seconds, ($isOwner || is_admin()) ? $uid : null);
    if ($saved < 1 && (int)$video['duration'] > 0) {
        $saved = (int)$video['duration'];
    }
    if ($saved < 1) {
        json_error('Could not save duration', 400);
    }

    json_response([
        'success'   => true,
        'duration'  => $saved,
        'formatted' => format_duration($saved),
    ]);
}

// ── Server probe for one video (logged-in owner/admin) ──
if ($action === 'probe_duration') {
    if (!is_logged_in()) {
        json_error('Unauthorized', 401);
    }
    $video_id = (int)($_GET['video_id'] ?? 0);
    if (!$video_id) {
        json_error('Missing video_id');
    }
    $video = db_fetch('SELECT id, user_id, duration, video_url FROM videos WHERE id=?', [$video_id]);
    if (!$video) {
        json_error('Not found', 404);
    }
    $uid = (int)auth_user()['id'];
    if ((int)$video['user_id'] !== $uid && !is_admin()) {
        json_error('Forbidden', 403);
    }
    $secs = fh_ensure_video_duration($video_id);
    json_response([
        'success'   => true,
        'duration'  => $secs,
        'formatted' => $secs > 0 ? format_duration($secs) : '',
    ]);
}

json_error('Unknown action');
