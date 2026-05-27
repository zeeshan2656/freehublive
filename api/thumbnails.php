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
if ($action === 'save_thumbnail') {
    $input = json_decode(file_get_contents('php://input'), true);
    $video_id  = (int)($input['video_id'] ?? 0);
    $dataUrl   = $input['data_url'] ?? '';

    if (!$video_id || !$dataUrl) json_error('Missing params');

    // Verify ownership
    $uid = auth_user()['id'];
    $video = db_fetch("SELECT id, user_id, thumbnail FROM videos WHERE id=?", [$video_id]);
    if (!$video || ($video['user_id'] != $uid && auth_user()['role'] !== 'admin')) {
        json_error('Forbidden', 403);
    }

    // Decode base64 image
    if (!preg_match('#^data:image/jpeg;base64,#', $dataUrl)) json_error('Invalid image data');
    $b64  = substr($dataUrl, strpos($dataUrl, ',') + 1);
    $data = base64_decode($b64);
    if (!$data) json_error('Corrupt image data');

    if (!is_dir(THUMB_PATH)) mkdir(THUMB_PATH, 0755, true);
    $filename = unique_filename('jpg');
    file_put_contents(THUMB_PATH . $filename, $data);

    // Delete old thumbnail if it was an auto-generated one
    $old = $video['thumbnail'];
    if ($old && !str_starts_with($old, 'http')) {
        $oldPath = THUMB_PATH . $old;
        if (file_exists($oldPath)) @unlink($oldPath);
    }

    db_update('videos', ['thumbnail' => $filename], 'id=?', [$video_id]);
    json_response(['success' => true, 'thumbnail_url' => thumb_url($filename)]);
}

// ── Save video duration (from browser metadata on upload / watch) ──
if ($action === 'save_duration') {
    $input    = json_decode(file_get_contents('php://input'), true);
    $video_id = (int)($input['video_id'] ?? 0);
    $seconds  = (int)round((float)($input['duration'] ?? 0));

    if (!$video_id || $seconds < 1) {
        json_error('Missing params');
    }

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
