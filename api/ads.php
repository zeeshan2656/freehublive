<?php
// ============================================================
// FreeHub.Live — Ads API (AJAX endpoints)
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-cache, no-store, must-revalidate');

$action = $_GET['action'] ?? '';

// ── GET: Fetch a single active ad for a placement ───────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_ad') {
    $placement = $_GET['placement'] ?? 'between_sections';
    $device    = $_GET['device'] ?? 'desktop';

    $now = date('Y-m-d');
    $ad = db_fetch(
        "SELECT a.id, a.title, a.content_type, a.content, a.target_url, a.image_url,
                COALESCE(ap.ad_width, a.ad_width) AS ad_width,
                COALESCE(ap.ad_height, a.ad_height) AS ad_height,
                ap.reload_interval AS reload_interval,
                a.device_target
         FROM ads a
         JOIN ad_placements ap ON ap.assigned_ad_id = a.id
         WHERE ap.key_name = ?
           AND (ap.device_target = ? OR ap.device_target = 'all')
           AND a.is_active = 1
           AND (a.device_target = ? OR a.device_target = 'all')
           AND (a.start_date IS NULL OR a.start_date <= ?)
           AND (a.end_date IS NULL OR a.end_date >= ?)
         ORDER BY (ap.device_target = ?) DESC, RAND()
         LIMIT 1",
        [$placement, $device, $device, $now, $now, $device]
    );

    if ($ad) {
        // Track impression
        db_query("UPDATE ads SET impressions=impressions+1 WHERE id=?", [$ad['id']]);
        // Fix image URL
        $ad['image_url'] = $ad['image_url']
            ? (str_starts_with($ad['image_url'], 'http') ? $ad['image_url'] : BASE_URL . '/uploads/ads/' . $ad['image_url'])
            : null;
        json_response(['success' => true, 'ad' => $ad]);
    } else {
        json_response(['success' => false, 'ad' => null]);
    }
}

// ── GET: Fetch multiple active ads for a placement ───────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'fetch') {
    $placement = $_GET['placement'] ?? 'between_sections';
    $device    = $_GET['device'] ?? 'desktop';
    $limit     = min(10, max(1, (int)($_GET['limit'] ?? 3)));

    $now = date('Y-m-d');
    $ads = db_fetchAll(
        "SELECT a.id, a.title, a.content_type, a.content, a.target_url, a.image_url,
                COALESCE(ap.ad_width, a.ad_width) AS ad_width,
                COALESCE(ap.ad_height, a.ad_height) AS ad_height,
                ap.reload_interval AS reload_interval,
                a.device_target
         FROM ads a
         JOIN ad_placements ap ON ap.assigned_ad_id = a.id
         WHERE ap.key_name = ?
           AND (ap.device_target = ? OR ap.device_target = 'all')
           AND a.is_active = 1
           AND (a.device_target = ? OR a.device_target = 'all')
           AND (a.start_date IS NULL OR a.start_date <= ?)
           AND (a.end_date IS NULL OR a.end_date >= ?)
         ORDER BY (ap.device_target = ?) DESC, RAND()
         LIMIT $limit",
        [$placement, $device, $device, $now, $now, $device]
    );

    foreach ($ads as &$ad) {
        db_query("UPDATE ads SET impressions=impressions+1 WHERE id=?", [$ad['id']]);
        $ad['image_url'] = $ad['image_url']
            ? (str_starts_with($ad['image_url'], 'http') ? $ad['image_url'] : BASE_URL . '/uploads/ads/' . $ad['image_url'])
            : null;
    }
    unset($ad);

    json_response(['ads' => $ads]);
}

// ── POST: Track ad impression ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'track_impression') {
    $ad_id = (int)($_GET['id'] ?? 0);
    if ($ad_id) {
        db_query("UPDATE ads SET impressions=impressions+1 WHERE id=?", [$ad_id]);
        json_success(['tracked' => true]);
    }
    json_error('Invalid ad');
}

// ── POST: Track ad click ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'track_click') {
    $ad_id = (int)($_GET['id'] ?? 0);
    if ($ad_id) {
        db_query("UPDATE ads SET clicks=clicks+1 WHERE id=?", [$ad_id]);
        fh_credit_admin_ad_revenue($ad_id);
        json_success(['tracked' => true]);
    }
    json_error('Invalid ad');
}

// ── GET: Fetch categories for dropdown ───────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'categories') {
    $limit = (int)(setting('dropdown_cat_limit', '8'));
    $cats = db_fetchAll(
        "SELECT id, name, slug, image, color
         FROM categories
         WHERE is_active=1
         ORDER BY sort_order
         LIMIT $limit"
    );
    foreach ($cats as &$c) {
        $c['image_url'] = category_image_url($c['image']);
    }
    unset($c);
    json_response(['categories' => $cats]);
}

json_error('Not found', 404);
