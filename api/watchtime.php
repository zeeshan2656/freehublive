<?php
// Watch-time heartbeat (JSON API — no SEO impact)
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store, no-cache, must-revalidate');

// GET — current user stats (dashboard refresh, no SEO impact)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'stats') {
    if (!is_logged_in()) {
        json_error('Login required', 401);
    }
    if ((auth_user()['status'] ?? 'pending') !== 'active') {
        json_error('Account not active', 403);
    }
    $stats = fh_user_watch_stats((int)auth_user()['id']);
    json_response([
        'success' => true,
        'stats'   => [
            'total_watch_seconds'      => $stats['total_watch_seconds'],
            'watch_hours'              => $stats['watch_hours'],
            'watch_time_formatted'     => format_duration($stats['total_watch_seconds']),
            'balance_usd'              => $stats['balance_usd'],
            'balance_formatted'        => $stats['balance_formatted'],
            'lifetime_watch_usd'       => $stats['lifetime_watch_usd'],
            'lifetime_watch_formatted' => $stats['lifetime_watch_formatted'],
        ],
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$viewId   = (int)($data['view_id'] ?? 0);
$videoId  = (int)($data['video_id'] ?? 0);
$seconds  = (int)($data['seconds'] ?? 0);
$playing  = !empty($data['playing']);

if (!$viewId || !$videoId) {
    json_error('Missing view_id or video_id');
}

if (!rate_limit('wt_' . hash_ip(get_ip()) . '_' . $viewId, 120, 3600)) {
    json_error('Rate limit exceeded', 429);
}

$result = fh_process_watch_time($viewId, $videoId, $seconds, $playing);

if (!empty($result['error'])) {
    json_error('Invalid watch session', 400);
}

$payload = ['success' => true, 'data' => $result];
if (is_logged_in()) {
    $stats = fh_user_watch_stats((int)auth_user()['id']);
    $payload['stats'] = [
        'total_watch_seconds'      => $stats['total_watch_seconds'],
        'watch_hours'              => $stats['watch_hours'],
        'watch_time_formatted'     => format_duration($stats['total_watch_seconds']),
        'balance_usd'              => $stats['balance_usd'],
        'balance_formatted'        => $stats['balance_formatted'],
        'lifetime_watch_usd'       => $stats['lifetime_watch_usd'],
        'lifetime_watch_formatted' => $stats['lifetime_watch_formatted'],
    ];
}
json_response($payload);
