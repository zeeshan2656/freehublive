<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Simple chunked upload endpoint supporting resumable uploads.
// POST?video_id=...&token=... : upload chunk via multipart/form-data field 'chunk' and header 'Content-Range'
// GET?video_id=...&action=status : returns uploaded bytes

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'status') {
    if (!is_logged_in()) json_error('Unauthorized', 401);
    $vid = (int)($_GET['video_id'] ?? 0);
    if (!$vid) json_error('Missing video_id');
    $meta = db_fetch('SELECT file_size FROM videos WHERE id=?', [$vid]);
    if (!$meta) json_error('Not found', 404);
    $us = db_fetch('SELECT token FROM upload_sessions WHERE video_id=? ORDER BY id DESC LIMIT 1', [$vid]);
    if (!$us || $us['token'] !== ($_GET['token'] ?? '')) json_error('Forbidden', 403);
    $temp = VIDEO_PATH . '._upload_' . $vid . '.part';
    $size = is_file($temp) ? filesize($temp) : 0;
    json_success(['uploaded' => $size]);
}

// Receive chunk (Content-Range: bytes start-end/total) or full file
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_logged_in()) json_error('Unauthorized', 401);
    $vid = (int)($_GET['video_id'] ?? 0);
    $token = $_GET['token'] ?? '';
    if (!$vid || !$token) json_error('Missing params');

    $video = db_fetch('SELECT id,user_id FROM videos WHERE id=?', [$vid]);
    if (!$video) json_error('Not found', 404);
    // validate token from upload_sessions
    $us = db_fetch('SELECT token, user_id FROM upload_sessions WHERE video_id=? ORDER BY id DESC LIMIT 1', [$vid]);
    if (!$us || $us['token'] !== $token) json_error('Forbidden', 403);
    if ((int)$video['user_id'] !== (int)auth_user()['id'] && !is_admin()) json_error('Forbidden', 403);

    if (!is_dir(VIDEO_PATH)) mkdir(VIDEO_PATH, 0755, true);
    $tempPath = VIDEO_PATH . '._upload_' . $vid . '.part';

    // If raw PUT (some clients), read php://input
    if (isset($_FILES['chunk'])) {
        $chunk = $_FILES['chunk'];
        if ($chunk['error'] !== UPLOAD_ERR_OK) json_error('Upload chunk error');
        // Append to temp file
        $in = fopen($chunk['tmp_name'], 'rb');
    } else {
        $in = fopen('php://input', 'rb');
    }
    if (!$in) json_error('No chunk data');

    // Optional Content-Range header
    $range = $_SERVER['HTTP_CONTENT_RANGE'] ?? ($_SERVER['HTTP_X_CONTENT_RANGE'] ?? '');

    // Append to temp file
    $out = fopen($tempPath, file_exists($tempPath) ? 'ab' : 'wb');
    if (!$out) { fclose($in); json_error('Server file error'); }
    stream_copy_to_stream($in, $out);
    fclose($in); fclose($out);

    // If client declares it's the final chunk via query param finalize=1
    if (!empty($_GET['finalize'])) {
        // Move temp to final filename
        $ext = pathinfo($_GET['filename'] ?? '', PATHINFO_EXTENSION) ?: 'mp4';
        $finalName = unique_filename($ext);
        $finalPath = VIDEO_PATH . $finalName;
        if (!rename($tempPath, $finalPath)) json_error('Could not finalize upload', 500);

        $fsize = filesize($finalPath);
        $approvalMode  = setting('video_approval_mode', 'manual');
        $initialStatus = ($approvalMode === 'auto') ? 'published' : 'pending';
        // Update video record
        db_update('videos', [
            'video_url' => $finalName,
            'file_size' => $fsize,
            'status'    => $initialStatus
        ], 'id=?', [$vid]);

        // Try to ensure duration in background (best-effort)
        @ignore_user_abort(true);
        // Return success
        json_success(['finalized' => true, 'video_url' => video_url($finalName), 'file_size' => $fsize]);
    }

    json_success(['uploaded' => is_file($tempPath) ? filesize($tempPath) : 0]);
}

json_error('Invalid request', 400);
