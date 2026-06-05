<?php
// FreeHub.Live — Lightweight Tracking Engine API
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$action = $_GET['action'] ?? '';

// Helpers for User-Agent Parsing
function parse_user_agent($ua) {
    $ua = strtolower($ua);
    
    // 1. Device Type
    $device = 'desktop';
    if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $ua)) {
        $device = 'tablet';
    } elseif (preg_match('/(mobi|ipod|iphone|opera mini|iemobile|blackberry|fennec|windvane)/i', $ua)) {
        $device = 'mobile';
    }

    // 2. Operating System
    $os = 'Other';
    if (str_contains($ua, 'windows')) $os = 'Windows';
    elseif (str_contains($ua, 'android')) $os = 'Android';
    elseif (str_contains($ua, 'iphone') || str_contains($ua, 'ipad') || str_contains($ua, 'ipod')) $os = 'iOS';
    elseif (str_contains($ua, 'macintosh') || str_contains($ua, 'mac os')) $os = 'macOS';
    elseif (str_contains($ua, 'linux')) $os = 'Linux';

    // 3. Browser
    $browser = 'Other';
    if (str_contains($ua, 'edge') || str_contains($ua, 'edg')) $browser = 'Edge';
    elseif (str_contains($ua, 'firefox')) $browser = 'Firefox';
    elseif (str_contains($ua, 'chrome') && !str_contains($ua, 'chromium')) $browser = 'Chrome';
    elseif (str_contains($ua, 'safari') && !str_contains($ua, 'chrome') && !str_contains($ua, 'chromium')) $browser = 'Safari';
    elseif (str_contains($ua, 'opera') || str_contains($ua, 'opr')) $browser = 'Opera';

    return [$device, $os, $browser];
}

// Helper for Traffic Source Parsing
function get_traffic_source($referer) {
    if (empty($referer)) {
        return 'direct';
    }
    
    $ref_host = parse_url($referer, PHP_URL_HOST);
    if (!$ref_host) {
        return 'direct';
    }
    
    // Check if internal
    $self_host = parse_url(BASE_URL, PHP_URL_HOST);
    if ($ref_host === $self_host) {
        return 'internal';
    }

    $ref_host = strtolower($ref_host);
    
    // Search Engines
    $search_engines = ['google.com', 'bing.com', 'yahoo.com', 'baidu.com', 'duckduckgo.com', 'yandex.ru', 'ask.com'];
    foreach ($search_engines as $engine) {
        if (str_contains($ref_host, $engine)) {
            return 'search';
        }
    }

    // Social Media
    $social_networks = ['facebook.com', 'instagram.com', 'twitter.com', 't.co', 'youtube.com', 'reddit.com', 'linkedin.com', 'pinterest.com', 'tiktok.com', 'whatsapp.com'];
    foreach ($social_networks as $social) {
        if (str_contains($ref_host, $social)) {
            return 'social';
        }
    }

    return 'referral';
}

// Helper for Geolocation
function get_geo_location() {
    $ip = get_ip();
    
    // Fallbacks for Cloudflare geolocation headers
    if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
        return [strtoupper($_SERVER['HTTP_CF_IPCOUNTRY']), 'Unknown'];
    }

    // If localhost, default to US
    if ($ip === '127.0.0.1' || $ip === '::1') {
        return ['US', 'Localhost'];
    }

    // Attempt lightweight api lookup (only if not cached)
    // Note: We use a short cache file in PHP temp/cache directory to prevent rate-limiting the local/external IP
    $cache_file = __DIR__ . '/../cache/geo_' . md5($ip) . '.json';
    if (is_file($cache_file) && (time() - filemtime($cache_file) < 86400 * 7)) {
        $cached = json_decode(@file_get_contents($cache_file), true);
        if ($cached) return [$cached['country'], $cached['city']];
    }

    try {
        $ctx = stream_context_create(['http' => ['timeout' => 2]]);
        $res = @file_get_contents("https://ipapi.co/{$ip}/json/", false, $ctx);
        if ($res) {
            $data = json_decode($res, true);
            if (!empty($data['country_code'])) {
                $country = strtoupper($data['country_code']);
                $city = $data['city'] ?? 'Unknown';
                @file_put_contents($cache_file, json_encode(['country' => $country, 'city' => $city]));
                return [$country, $city];
            }
        }
    } catch (Throwable $e) {}

    return ['US', 'Unknown'];
}

// ── ACTION: Initialize Pageview ──────────────────────────────
if ($action === 'init') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: [];

    $url = trim($data['url'] ?? '');
    if (empty($url)) {
        json_error('URL is required');
    }

    // Generate/Reuse visitor tracking session cookie
    if (empty($_COOKIE['fh_analytics_sess'])) {
        $sessId = bin2hex(random_bytes(16));
        setcookie('fh_analytics_sess', $sessId, time() + 86400 * 30, '/');
    } else {
        $sessId = $_COOKIE['fh_analytics_sess'];
    }

    $referer = trim($data['referer'] ?? '');
    $traffic_source = get_traffic_source($referer);
    
    // Prevent logging internal routing as referrers
    if ($traffic_source === 'internal') {
        $traffic_source = 'direct'; 
    }

    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    list($device, $os, $browser) = parse_user_agent($ua);
    list($country, $city) = get_geo_location();

    $is_reel = !empty($data['is_reel']) ? 1 : 0;
    $is_video = !empty($data['is_video']) ? 1 : 0;
    $content_id = !empty($data['content_id']) ? (int)$data['content_id'] : null;

    $ipHash = hash_ip(get_ip());
    $updated_views = null;
    if ($is_reel && $content_id) {
        $viewed = db_fetch(
            "SELECT id FROM analytics_pageviews 
             WHERE is_reel = 1 AND content_id = ? AND session_id = ?
               AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
             LIMIT 1",
            [$content_id, $sessId]
        );
        if (!$viewed) {
            db_query("UPDATE reels SET views = views + 1 WHERE id = ?", [$content_id]);
        }
        $updated_views = (int)db_fetch("SELECT views FROM reels WHERE id = ?", [$content_id])['views'];
    } elseif ($is_video && $content_id) {
        $updated_views = (int)db_fetch("SELECT views FROM videos WHERE id = ?", [$content_id])['views'];
    }

    $viewId = db_insert('analytics_pageviews', [
        'session_id'     => $sessId,
        'user_id'        => auth_user()['id'] ?? null,
        'ip_hash'        => hash_ip(get_ip()),
        'url'            => substr($url, 0, 255),
        'referer'        => empty($referer) ? null : substr($referer, 0, 255),
        'traffic_source' => $traffic_source,
        'device_type'    => $device,
        'os'             => $os,
        'browser'        => $browser,
        'country'        => $country,
        'city'           => $city,
        'duration'       => 0,
        'is_reel'        => $is_reel,
        'is_video'       => $is_video,
        'content_id'     => $content_id,
        'created_at'     => date('Y-m-d H:i:s'),
    ]);

    json_response([
        'success'     => true,
        'pageview_id' => $viewId,
        'views'       => $updated_views
    ]);
}

// ── ACTION: Heartbeat Tick ────────────────────────────────────
if ($action === 'heartbeat') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: [];

    $viewId = (int)($data['pageview_id'] ?? 0);
    $inc = (int)($data['inc'] ?? 10); // increments by 10s usually

    if (!$viewId || $inc <= 0) {
        json_error('Invalid params');
    }

    // Rate limit heartbeats to prevent overload
    $ip = hash_ip(get_ip());
    if (!rate_limit('ahb_' . $ip . '_' . $viewId, 120, 60)) {
        json_error('Rate limit exceeded', 429);
    }

    db_query(
        "UPDATE analytics_pageviews SET duration = duration + ? WHERE id = ?",
        [$inc, $viewId]
    );

    json_response(['success' => true]);
}

// ── ACTION: Live Stats (Admin-Only Dashboard Auto-Refresh) ──────
if ($action === 'live_stats') {
    if (!is_logged_in() || auth_user()['role'] !== 'admin') {
        json_error('Forbidden', 403);
    }

    // Active users in the last 5 minutes (300 seconds)
    $active_time = date('Y-m-d H:i:s', time() - 300);
    
    $stats = db_fetch(
        "SELECT 
            COUNT(DISTINCT session_id) AS active_now,
            COUNT(DISTINCT CASE WHEN user_id IS NOT NULL THEN session_id END) AS logged_in,
            COUNT(DISTINCT CASE WHEN user_id IS NULL THEN session_id END) AS guests,
            COUNT(CASE WHEN is_reel = 0 AND is_video = 0 THEN 1 END) AS page_views,
            COUNT(CASE WHEN is_reel = 1 THEN 1 END) AS reel_views,
            COUNT(CASE WHEN is_video = 1 THEN 1 END) AS video_views
         FROM analytics_pageviews 
         WHERE created_at >= ?",
        [$active_time]
    );

    // Get latest 10 live activities/events
    $activities = db_fetchAll(
        "SELECT p.id, p.user_id, u.username, p.url, p.is_reel, p.is_video, p.content_id, p.created_at, p.country
         FROM analytics_pageviews p
         LEFT JOIN users u ON u.id = p.user_id
         ORDER BY p.id DESC LIMIT 10"
    );

    $formatted_activities = array_map(function($act) {
        $time = date('H:i:s', strtotime($act['created_at']));
        $country = $act['country'] ?? 'US';
        $user = $act['username'] ?? 'Guest';
        
        $desc = 'visited page';
        if ($act['is_reel']) {
            $desc = 'viewed a Reel (#' . $act['content_id'] . ')';
        } elseif ($act['is_video']) {
            $desc = 'watched a Video (#' . $act['content_id'] . ')';
        } else {
            $pagename = basename(parse_url($act['url'], PHP_URL_PATH));
            if ($pagename === 'index.php' || $pagename === '') $desc = 'visited Home';
            elseif ($pagename === 'login.php') $desc = 'on Login Page';
            elseif ($pagename === 'register.php') $desc = 'on Registration Page';
            else $desc = 'visited ' . ($pagename ?: 'Home');
        }

        return [
            'time' => $time,
            'user' => $user,
            'desc' => $desc,
            'flag' => strtolower($country),
            'country' => $country
        ];
    }, $activities);

    json_response([
        'success'    => true,
        'live'       => [
            'active_now'  => (int)$stats['active_now'],
            'logged_in'   => (int)$stats['logged_in'],
            'guests'      => (int)$stats['guests'],
            'page_views'  => (int)$stats['page_views'],
            'reel_views'  => (int)$stats['reel_views'],
            'video_views' => (int)$stats['video_views']
        ],
        'activities' => $formatted_activities
    ]);
}

json_error('Unknown action', 404);
