<?php
// Admin — Advanced Analytics Dashboard (Google Analytics Style)
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

// ── Date Filters & Presets ────────────────────────────────────
$preset = $_GET['preset'] ?? '30days';
$today_date = date('Y-m-d');
$preset_label = 'Last 30 Days';

switch ($preset) {
    case 'today':
        $from = $today_date;
        $to = $today_date;
        $preset_label = 'Today';
        break;
    case 'yesterday':
        $from = date('Y-m-d', strtotime('-1 day'));
        $to = $from;
        $preset_label = 'Yesterday';
        break;
    case '7days':
        $from = date('Y-m-d', strtotime('-6 days'));
        $to = $today_date;
        $preset_label = 'Last 7 Days';
        break;
    case '30days':
        $from = date('Y-m-d', strtotime('-29 days'));
        $to = $today_date;
        $preset_label = 'Last 30 Days';
        break;
    case 'this_month':
        $from = date('Y-m-01');
        $to = $today_date;
        $preset_label = 'This Month';
        break;
    case 'last_month':
        $from = date('Y-m-d', strtotime('first day of last month'));
        $to = date('Y-m-d', strtotime('last day of last month'));
        $preset_label = 'Last Month';
        break;
    case 'this_year':
        $from = date('Y-01-01');
        $to = $today_date;
        $preset_label = 'This Year';
        break;
    case 'custom':
        $from = $_GET['from'] ?? date('Y-m-d', strtotime('-29 days'));
        $to = $_GET['to'] ?? $today_date;
        $preset_label = 'Custom Range';
        break;
    default:
        $from = date('Y-m-d', strtotime('-29 days'));
        $to = $today_date;
        $preset_label = 'Last 30 Days';
        $preset = '30days';
}

// ── Fetch & Merge Historical + Live Stats ────────────────────
$daily_stats = [];

// 1. Fetch historical from analytics_daily_stats
$hist_rows = db_fetchAll(
    "SELECT date, visits, visitors, pageviews, video_views, reel_views, avg_duration, bounce_rate 
     FROM analytics_daily_stats 
     WHERE date >= ? AND date <= ? AND date < ? 
     ORDER BY date ASC",
    [$from, $to, $today_date]
);
foreach ($hist_rows as $row) {
    $daily_stats[$row['date']] = [
        'date' => $row['date'],
        'visits' => (int)$row['visits'],
        'visitors' => (int)$row['visitors'],
        'pageviews' => (int)$row['pageviews'],
        'video_views' => (int)$row['video_views'],
        'reel_views' => (int)$row['reel_views'],
        'avg_duration' => (float)$row['avg_duration'],
        'bounce_rate' => (float)$row['bounce_rate']
    ];
}

// 2. Fetch today's live stats if today is within selected range
if ($to >= $today_date && $from <= $today_date) {
    $basic_today = db_fetch("
        SELECT 
            COUNT(*) AS total_pageviews,
            COUNT(DISTINCT session_id) AS total_visits,
            COUNT(DISTINCT ip_hash) AS total_visitors,
            SUM(CASE WHEN is_video = 1 THEN 1 ELSE 0 END) AS total_video_views,
            SUM(CASE WHEN is_reel = 1 THEN 1 ELSE 0 END) AS total_reel_views,
            COALESCE(AVG(duration), 0) AS avg_duration
        FROM analytics_pageviews
        WHERE DATE(created_at) = ?
    ", [$today_date]);

    $bounce_today = db_fetch("
        SELECT 
            COUNT(DISTINCT session_id) as total_sessions,
            SUM(CASE WHEN pv_count = 1 AND max_dur <= 10 THEN 1 ELSE 0 END) as bounce_sessions
        FROM (
            SELECT session_id, COUNT(*) as pv_count, MAX(duration) as max_dur
            FROM analytics_pageviews
            WHERE DATE(created_at) = ?
            GROUP BY session_id
        ) as session_summaries
    ", [$today_date]);

    $t_visits = (int)($basic_today['total_visits'] ?? 0);
    $t_visitors = (int)($basic_today['total_visitors'] ?? 0);
    $t_pageviews = (int)($basic_today['total_pageviews'] ?? 0);
    $t_video_views = (int)($basic_today['total_video_views'] ?? 0);
    $t_reel_views = (int)($basic_today['total_reel_views'] ?? 0);
    $t_avg_duration = (float)($basic_today['avg_duration'] ?? 0.00);
    
    $t_total_sessions = (int)($bounce_today['total_sessions'] ?? 0);
    $t_bounce_sessions = (int)($bounce_today['bounce_sessions'] ?? 0);
    $t_bounce_rate = $t_total_sessions > 0 ? ($t_bounce_sessions / $t_total_sessions) * 100 : 0.00;

    $daily_stats[$today_date] = [
        'date' => $today_date,
        'visits' => $t_visits,
        'visitors' => $t_visitors,
        'pageviews' => $t_pageviews,
        'video_views' => $t_video_views,
        'reel_views' => $t_reel_views,
        'avg_duration' => $t_avg_duration,
        'bounce_rate' => $t_bounce_rate
    ];
}

// Generate continuous timeline to prevent gaps
$cur = new DateTime($from);
$end_dt = new DateTime($to);
while ($cur <= $end_dt) {
    $d_str = $cur->format('Y-m-d');
    if (!isset($daily_stats[$d_str])) {
        $daily_stats[$d_str] = [
            'date' => $d_str,
            'visits' => 0,
            'visitors' => 0,
            'pageviews' => 0,
            'video_views' => 0,
            'reel_views' => 0,
            'avg_duration' => 0.0,
            'bounce_rate' => 0.0
        ];
    }
    $cur->modify('+1 day');
}
ksort($daily_stats);

// Compute Totals
$total_visits = 0;
$total_visitors = 0;
$total_pageviews = 0;
$total_video_views = 0;
$total_reel_views = 0;
$sum_duration = 0.0;
$sum_bounce_rate = 0.0;
$num_days_with_data = 0;

foreach ($daily_stats as $stat) {
    $total_visits += $stat['visits'];
    $total_visitors += $stat['visitors'];
    $total_pageviews += $stat['pageviews'];
    $total_video_views += $stat['video_views'];
    $total_reel_views += $stat['reel_views'];
    if ($stat['visits'] > 0) {
        $sum_duration += $stat['avg_duration'];
        $sum_bounce_rate += $stat['bounce_rate'];
        $num_days_with_data++;
    }
}

$avg_duration = $num_days_with_data > 0 ? ($sum_duration / $num_days_with_data) : 0.0;
$avg_bounce_rate = $num_days_with_data > 0 ? ($sum_bounce_rate / $num_days_with_data) : 0.0;

// ── Geographic, Device, Sources Breakdown ────────────────────
$device_counts = ['desktop' => 0, 'mobile' => 0, 'tablet' => 0];
$os_counts = [];
$browser_counts = [];
$geo_counts = [];
$source_counts = ['direct' => 0, 'search' => 0, 'social' => 0, 'referral' => 0];

// 1. Fetch historical device stats
$device_hist = db_fetchAll(
    "SELECT device_type, os, browser, SUM(count) as total
     FROM analytics_device_stats
     WHERE date >= ? AND date <= ? AND date < ?
     GROUP BY device_type, os, browser",
    [$from, $to, $today_date]
);
foreach ($device_hist as $row) {
    $dev = strtolower($row['device_type']);
    if (isset($device_counts[$dev])) $device_counts[$dev] += (int)$row['total'];
    $os = $row['os'] ?: 'Other';
    $browser = $row['browser'] ?: 'Other';
    $os_counts[$os] = ($os_counts[$os] ?? 0) + (int)$row['total'];
    $browser_counts[$browser] = ($browser_counts[$browser] ?? 0) + (int)$row['total'];
}

// 2. Fetch historical geo stats
$geo_hist = db_fetchAll(
    "SELECT country, city, SUM(count) as total
     FROM analytics_geo_stats
     WHERE date >= ? AND date <= ? AND date < ?
     GROUP BY country, city",
    [$from, $to, $today_date]
);
foreach ($geo_hist as $row) {
    $country = strtoupper($row['country']);
    $city = $row['city'] ?: 'Unknown';
    $key = $country . '|' . $city;
    $geo_counts[$key] = ($geo_counts[$key] ?? 0) + (int)$row['total'];
}

// 3. Fetch historical source stats
$source_hist = db_fetchAll(
    "SELECT source, SUM(count) as total
     FROM analytics_source_stats
     WHERE date >= ? AND date <= ? AND date < ?
     GROUP BY source",
    [$from, $to, $today_date]
);
foreach ($source_hist as $row) {
    $src = strtolower($row['source']);
    if (isset($source_counts[$src])) {
        $source_counts[$src] += (int)$row['total'];
    } else {
        $source_counts['referral'] += (int)$row['total'];
    }
}

// 4. Merge live today records
if ($to >= $today_date && $from <= $today_date) {
    $live_pvs = db_fetchAll(
        "SELECT device_type, os, browser, country, city, traffic_source
         FROM analytics_pageviews
         WHERE DATE(created_at) = ?",
        [$today_date]
    );
    foreach ($live_pvs as $row) {
        $dev = strtolower($row['device_type']);
        if (isset($device_counts[$dev])) $device_counts[$dev]++;
        
        $os = $row['os'] ?: 'Other';
        $os_counts[$os] = ($os_counts[$os] ?? 0) + 1;
        
        $browser = $row['browser'] ?: 'Other';
        $browser_counts[$browser] = ($browser_counts[$browser] ?? 0) + 1;
        
        $country = strtoupper($row['country']);
        $city = $row['city'] ?: 'Unknown';
        $key = $country . '|' . $city;
        $geo_counts[$key] = ($geo_counts[$key] ?? 0) + 1;
        
        $src = strtolower($row['traffic_source']);
        if (isset($source_counts[$src])) {
            $source_counts[$src]++;
        } else {
            $source_counts['referral']++;
        }
    }
}

arsort($os_counts);
arsort($browser_counts);

// Process countries & cities list
$country_totals = [];
$city_totals = [];
foreach ($geo_counts as $key => $count) {
    list($country, $city) = explode('|', $key);
    $country_totals[$country] = ($country_totals[$country] ?? 0) + $count;
    if ($city !== 'Unknown' && $city !== 'Localhost') {
        $city_totals[$city] = ($city_totals[$city] ?? 0) + $count;
    }
}
arsort($country_totals);
arsort($city_totals);

// ── Unique, Returning, New Visitors ─────────────────────────
$from_datetime = $from . ' 00:00:00';
$to_datetime = $to . ' 23:59:59';
$visitor_type = db_fetch("
    SELECT 
        COUNT(DISTINCT p.session_id) as total_sessions,
        COUNT(DISTINCT CASE WHEN first_visits.min_created >= ? THEN p.session_id END) as new_sessions
    FROM analytics_pageviews p
    JOIN (
        SELECT session_id, MIN(created_at) as min_created
        FROM analytics_pageviews
        GROUP BY session_id
    ) as first_visits ON first_visits.session_id = p.session_id
    WHERE p.created_at BETWEEN ? AND ?
", [$from_datetime, $from_datetime, $to_datetime]);

$v_total = (int)($visitor_type['total_sessions'] ?? 0);
$v_new = (int)($visitor_type['new_sessions'] ?? 0);
$v_returning = max(0, $v_total - $v_new);

// ── Content Performance Lists ─────────────────────────────────
// Top Videos
$top_videos = db_fetchAll(
    "SELECT 
        v.id,
        v.title,
        v.views as total_views_meta,
        COUNT(DISTINCT p.session_id) as unique_viewers,
        COUNT(p.id) as watch_count,
        COALESCE(SUM(p.duration), 0) as watch_duration,
        v.duration as video_length,
        u.channel_name
     FROM videos v
     JOIN users u ON u.id = v.user_id
     LEFT JOIN analytics_pageviews p ON p.content_id = v.id AND p.is_video = 1
     WHERE v.status = 'published'
     GROUP BY v.id, v.title, v.views, v.duration, u.channel_name
     ORDER BY watch_count DESC, total_views_meta DESC
     LIMIT 10"
);

// Top Reels
$top_reels = db_fetchAll(
    "SELECT 
        r.id,
        r.title,
        r.views as total_views_meta,
        COUNT(DISTINCT p.session_id) as unique_viewers,
        COUNT(p.id) as watch_count,
        COALESCE(SUM(p.duration), 0) as watch_duration,
        COUNT(CASE WHEN p.duration >= 15 THEN 1 END) as completed_watches,
        u.channel_name
     FROM reels r
     JOIN users u ON u.id = r.user_id
     LEFT JOIN analytics_pageviews p ON p.content_id = r.id AND p.is_reel = 1
     WHERE r.status = 'published'
     GROUP BY r.id, r.title, r.views, u.channel_name
     ORDER BY watch_count DESC, total_views_meta DESC
     LIMIT 10"
);

// ── Helpers ───────────────────────────────────────────────────
function get_country_flag_emoji($country_code) {
    $code = strtoupper($country_code);
    if (strlen($code) !== 2) return '🏳️';
    $copypasta = [
        'A' => '🇦', 'B' => '🇧', 'C' => '🇨', 'D' => '🇩', 'E' => '🇪', 'F' => '🇫', 'G' => '🇬', 'H' => '🇭',
        'I' => '🇮', 'J' => '🇯', 'K' => '🇰', 'L' => '🇱', 'M' => '🇲', 'N' => '🇳', 'O' => '🇴', 'P' => '🇵',
        'Q' => '🇶', 'R' => '🇷', 'S' => '🇸', 'T' => '🇹', 'U' => '🇺', 'V' => '🇻', 'W' => '🇼', 'X' => '🇽',
        'Y' => '🇾', 'Z' => '🇿'
    ];
    return ($copypasta[$code[0]] ?? '') . ($copypasta[$code[1]] ?? '');
}

function get_country_name($code) {
    $countries = [
        'US' => 'United States', 'GB' => 'United Kingdom', 'CA' => 'Canada', 'DE' => 'Germany',
        'FR' => 'France', 'IN' => 'India', 'AU' => 'Australia', 'BR' => 'Brazil', 'JP' => 'Japan',
        'CN' => 'China', 'RU' => 'Russia', 'ZA' => 'South Africa', 'ES' => 'Spain', 'IT' => 'Italy',
        'NL' => 'Netherlands', 'SG' => 'Singapore', 'PK' => 'Pakistan', 'AE' => 'United Arab Emirates',
        'SA' => 'Saudi Arabia', 'MX' => 'Mexico', 'TR' => 'Turkey', 'ID' => 'Indonesia',
        'MY' => 'Malaysia', 'PH' => 'Philippines',
    ];
    return $countries[strtoupper($code)] ?? strtoupper($code);
}

function format_seconds($seconds) {
    $seconds = (int)$seconds;
    if ($seconds <= 0) return '0s';
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    $s = $seconds % 60;
    
    if ($h > 0) return sprintf('%dh %dm %ds', $h, $m, $s);
    if ($m > 0) return sprintf('%dm %ds', $m, $s);
    return sprintf('%ds', $s);
}

// Prepare Chart.js data variables
$chart_labels = [];
$chart_visits = [];
$chart_pageviews = [];
$chart_video_views = [];
$chart_reel_views = [];
foreach ($daily_stats as $stat) {
    $chart_labels[] = date('M j', strtotime($stat['date']));
    $chart_visits[] = $stat['visits'];
    $chart_pageviews[] = $stat['pageviews'];
    $chart_video_views[] = $stat['video_views'];
    $chart_reel_views[] = $stat['reel_views'];
}

$js_labels = json_encode($chart_labels);
$js_visits = json_encode($chart_visits);
$js_pageviews = json_encode($chart_pageviews);
$js_video_views = json_encode($chart_video_views);
$js_reel_views = json_encode($chart_reel_views);

$meta_title = 'Advanced Analytics Dashboard';
require_once __DIR__ . '/partials/admin_head.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
/* Core Styling - Premium GA4 Minimalist Dark/Light hybrid feel */
.analytics-dashboard {
  --bg-card: #111119;
  --border-color: #20202e;
  --text-primary: #f8fafc;
  --text-secondary: #94a3b8;
  --text-muted: #64748b;
  --color-primary: #6366f1;
  --color-success: #10b981;
  --color-warning: #f59e0b;
  --color-danger: #ef4444;
  --color-pink: #ec4899;
  --color-blue: #3b82f6;
  
  font-family: 'Inter', system-ui, sans-serif;
  color: var(--text-primary);
  margin-top: 10px;
}

.analytics-dashboard .card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 14px;
  padding: 20px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
  margin-bottom: 24px;
}

/* Header filter controls */
.filter-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 16px;
  background: var(--bg-card);
  padding: 12px 20px;
  border-radius: 12px;
  border: 1px solid var(--border-color);
  margin-bottom: 24px;
}

.preset-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.preset-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 6px 14px;
  border-radius: 20px;
  background: #191924;
  color: var(--text-secondary);
  font-size: 0.82rem;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.2s ease;
  border: 1px solid transparent;
}

.preset-pill:hover {
  background: var(--border-color);
  color: var(--text-primary);
}

.preset-pill.active {
  background: var(--color-primary);
  color: #fff;
  box-shadow: 0 0 10px rgba(99, 102, 241, 0.4);
}

.custom-range-inputs {
  display: flex;
  gap: 8px;
  align-items: center;
}

.custom-range-inputs input[type="date"] {
  background: #191924;
  border: 1px solid var(--border-color);
  color: #fff;
  border-radius: 6px;
  padding: 4px 8px;
  font-size: 0.8rem;
}

/* Realtime panel */
.realtime-grid {
  display: grid;
  grid-template-columns: 1fr 1.2fr;
  gap: 24px;
  margin-bottom: 24px;
}

@media (max-width: 992px) {
  .realtime-grid {
    grid-template-columns: 1fr;
  }
}

.rt-card {
  background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.08), transparent), var(--bg-card);
  border: 1px solid var(--border-color);
}

.rt-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  background: rgba(16, 185, 129, 0.12);
  color: var(--color-success);
  font-size: 0.72rem;
  font-weight: 800;
  border-radius: 12px;
  letter-spacing: 0.05em;
}

.rt-dot {
  width: 7px;
  height: 7px;
  background: var(--color-success);
  border-radius: 50%;
  box-shadow: 0 0 6px var(--color-success);
  animation: pulse-dot 1.8s infinite;
}

@keyframes pulse-dot {
  0% { transform: scale(0.95); opacity: 0.5; }
  50% { transform: scale(1.2); opacity: 1; }
  100% { transform: scale(0.95); opacity: 0.5; }
}

.rt-counter-row {
  display: flex;
  align-items: baseline;
  gap: 12px;
  margin: 18px 0;
}

.rt-big-num {
  font-size: 3.8rem;
  font-weight: 900;
  color: #fff;
  line-height: 1;
}

.rt-stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  border-top: 1px solid var(--border-color);
  padding-top: 18px;
  margin-top: 15px;
}

.rt-mini-card {
  text-align: center;
}

.rt-mini-val {
  font-size: 1.25rem;
  font-weight: 800;
  color: #fff;
}

.rt-mini-lbl {
  font-size: 0.7rem;
  color: var(--text-muted);
  text-transform: uppercase;
  margin-top: 3px;
}

/* Activity feed list */
.activity-feed-box {
  max-height: 220px;
  overflow-y: auto;
  padding-right: 6px;
}

.activity-feed-box::-webkit-scrollbar {
  width: 4px;
}
.activity-feed-box::-webkit-scrollbar-thumb {
  background: var(--border-color);
  border-radius: 2px;
}

.activity-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  background: rgba(255, 255, 255, 0.015);
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.02);
  margin-bottom: 8px;
  font-size: 0.8rem;
}

.act-time {
  color: var(--text-muted);
  font-weight: 500;
  font-size: 0.72rem;
  white-space: nowrap;
}

.act-user {
  font-weight: 700;
  color: var(--color-primary);
}

.act-desc {
  color: var(--text-secondary);
  flex-grow: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* KPIs cards */
.kpis-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}

.kpi-card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
}

.kpi-val {
  font-size: 2.2rem;
  font-weight: 850;
  color: #fff;
  line-height: 1.2;
}

.kpi-lbl {
  font-size: 0.75rem;
  color: var(--text-secondary);
  text-transform: uppercase;
  font-weight: 700;
  letter-spacing: 0.03em;
  margin-top: 4px;
}

/* Line chart styling */
.chart-card-large {
  position: relative;
  height: 320px;
}

/* Secondary breakdowns layout */
.breakdown-row {
  display: grid;
  grid-template-columns: 1.2fr 0.8fr;
  gap: 24px;
  margin-bottom: 24px;
}

@media (max-width: 992px) {
  .breakdown-row {
    grid-template-columns: 1fr;
  }
}

.details-column {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 15px;
}

.details-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.85rem;
}

.details-bar-wrap {
  flex-grow: 1;
  margin: 0 12px;
  background: #191924;
  height: 6px;
  border-radius: 3px;
  overflow: hidden;
  position: relative;
}

.details-bar {
  background: var(--color-primary);
  height: 100%;
  border-radius: 3px;
}

.details-name {
  width: 140px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: var(--text-secondary);
}

.details-val {
  width: 50px;
  text-align: right;
  font-weight: 700;
  color: #fff;
}

/* Content tabs */
.tab-headers {
  display: flex;
  gap: 8px;
  border-bottom: 1px solid var(--border-color);
  padding-bottom: 8px;
  margin-bottom: 16px;
}

.tab-btn {
  background: transparent;
  border: none;
  color: var(--text-secondary);
  font-weight: 700;
  font-size: 0.9rem;
  padding: 8px 16px;
  cursor: pointer;
  border-radius: 6px;
  transition: all 0.2s;
}

.tab-btn:hover {
  background: #191924;
  color: #fff;
}

.tab-btn.active {
  background: var(--color-primary);
  color: #fff;
}

.tab-pane {
  display: none;
}

.tab-pane.active {
  display: block;
}

.table-wrap table {
  width: 100%;
  border-collapse: collapse;
}

.table-wrap th {
  text-align: left;
  font-size: 0.72rem;
  text-transform: uppercase;
  color: var(--text-muted);
  padding: 8px 12px;
  border-bottom: 1px solid var(--border-color);
}

.table-wrap td {
  padding: 12px;
  font-size: 0.85rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.02);
}

.table-wrap tr:hover td {
  background: rgba(255, 255, 255, 0.01);
}

.content-title-cell {
  font-weight: 600;
  color: #fff;
  max-width: 250px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>

<div class="analytics-dashboard">
  
  <!-- Date Selector and Title Row -->
  <div class="filter-bar">
    <div style="display:flex; flex-direction:column; gap:4px">
      <h2 style="font-weight:800; font-size:1.35rem; margin:0">Advanced Analytics Dashboard</h2>
      <span style="font-size:0.75rem; color:var(--text-muted)">Tracking Platform Performance & User Activity</span>
    </div>
    
    <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap">
      <!-- Date Presets -->
      <div class="preset-pills">
        <a href="?preset=today" class="preset-pill <?= $preset === 'today' ? 'active' : '' ?>">Today</a>
        <a href="?preset=yesterday" class="preset-pill <?= $preset === 'yesterday' ? 'active' : '' ?>">Yesterday</a>
        <a href="?preset=7days" class="preset-pill <?= $preset === '7days' ? 'active' : '' ?>">7 Days</a>
        <a href="?preset=30days" class="preset-pill <?= $preset === '30days' ? 'active' : '' ?>">30 Days</a>
        <a href="?preset=this_month" class="preset-pill <?= $preset === 'this_month' ? 'active' : '' ?>">This Month</a>
        <a href="?preset=last_month" class="preset-pill <?= $preset === 'last_month' ? 'active' : '' ?>">Last Month</a>
        <a href="?preset=this_year" class="preset-pill <?= $preset === 'this_year' ? 'active' : '' ?>">This Year</a>
        <a href="javascript:void(0)" onclick="toggleCustomRange()" class="preset-pill <?= $preset === 'custom' ? 'active' : '' ?>">Custom Range</a>
      </div>
      
      <!-- Custom range form inline -->
      <form method="GET" id="custom-range-form" style="display: <?= $preset === 'custom' ? 'flex' : 'none' ?>; align-items:center; gap:8px">
        <input type="hidden" name="preset" value="custom">
        <div class="custom-range-inputs">
          <input type="date" name="from" value="<?= e($from) ?>" required>
          <span style="font-size:0.75rem; color:var(--text-muted)">to</span>
          <input type="date" name="to" value="<?= e($to) ?>" required>
          <button type="submit" class="btn btn-primary btn-sm" style="font-weight:700; font-size:0.75rem; padding: 4px 10px;">Apply</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Real-Time Activity Panel -->
  <div class="realtime-grid">
    <!-- Live User Stats -->
    <div class="card rt-card">
      <div style="display:flex; justify-content:space-between; align-items:flex-start">
        <div style="display:flex; flex-direction:column; gap:4px">
          <h3 style="font-size:1.05rem; font-weight:800; color:#fff; margin:0">Real-Time Traffic</h3>
          <span style="font-size:0.72rem; color:var(--text-muted)">Active traffic monitored live in the last 5 minutes</span>
        </div>
        <div class="rt-badge">
          <span class="rt-dot"></span>
          <span>Live</span>
        </div>
      </div>
      
      <div class="rt-counter-row">
        <span class="rt-big-num" id="rt-active-count">0</span>
        <div style="display:flex; flex-direction:column">
          <span style="font-weight:700; color:#fff; font-size:0.95rem">Active Visitors</span>
          <span style="color:var(--text-muted); font-size:0.72rem">browsing the site right now</span>
        </div>
      </div>
      
      <div class="rt-stats-grid">
        <div class="rt-mini-card">
          <div class="rt-mini-val" id="rt-logged-in">0</div>
          <div class="rt-mini-lbl">Logged-In</div>
        </div>
        <div class="rt-mini-card">
          <div class="rt-mini-val" id="rt-guests">0</div>
          <div class="rt-mini-lbl">Guests</div>
        </div>
        <div class="rt-mini-card" style="border-left:1px dashed var(--border-color)">
          <div class="rt-mini-val" id="rt-views-total">0</div>
          <div class="rt-mini-lbl">Total Views</div>
        </div>
      </div>
      
      <div style="display:flex; justify-content:space-between; margin-top:20px; padding-top:15px; border-top:1px solid var(--border-color); font-size:0.75rem; color:var(--text-secondary)">
        <span>📄 Page Views: <strong id="rt-pageviews" style="color:#fff">0</strong></span>
        <span>🎥 Video Views: <strong id="rt-videoviews" style="color:#fff">0</strong></span>
        <span>📱 Reel Views: <strong id="rt-reelviews" style="color:#fff">0</strong></span>
      </div>
    </div>
    
    <!-- Live Activity Feed -->
    <div class="card">
      <h3 style="font-size:1.05rem; font-weight:800; color:#fff; margin-bottom:12px">Live Activity Feed</h3>
      <div class="activity-feed-box" id="rt-activity-feed">
        <!-- Live events injected here -->
        <div style="text-align:center; padding: 40px 0; color:var(--text-muted); font-size:0.85rem">
          Waiting for activity...
        </div>
      </div>
    </div>
  </div>

  <!-- Historical KPIs Grid -->
  <div class="kpis-row">
    <div class="card kpi-card">
      <span class="kpi-val"><?= format_number($total_visits) ?></span>
      <span class="kpi-lbl">Total Sessions</span>
    </div>
    <div class="card kpi-card">
      <span class="kpi-val" style="color:var(--color-blue)"><?= format_number($total_visitors) ?></span>
      <span class="kpi-lbl">Unique Visitors</span>
    </div>
    <div class="card kpi-card">
      <span class="kpi-val"><?= format_number($total_pageviews) ?></span>
      <span class="kpi-lbl">Page Views</span>
    </div>
    <div class="card kpi-card">
      <span class="kpi-val" style="color:var(--color-success)"><?= format_seconds($avg_duration) ?></span>
      <span class="kpi-lbl">Avg Duration</span>
    </div>
    <div class="card kpi-card">
      <span class="kpi-val" style="color:var(--color-warning)"><?= number_format($avg_bounce_rate, 1) ?>%</span>
      <span class="kpi-lbl">Bounce Rate</span>
    </div>
  </div>

  <!-- Main Traffic & Views Chart -->
  <div class="card">
    <h3 style="font-size:1.05rem; font-weight:800; color:#fff; margin-bottom:18px">Traffic & Content Consumption Trends</h3>
    <div class="chart-card-large">
      <canvas id="trafficChart" style="width:100%; height:100%"></canvas>
    </div>
  </div>

  <!-- Breakdowns (Geo, Sources, Devices, Browser) -->
  <div class="breakdown-row">
    <!-- Left: Geo & Sources lists -->
    <div class="card" style="display:grid; grid-template-columns:1fr 1fr; gap:24px">
      <!-- Top Countries -->
      <div>
        <h3 style="font-size:0.95rem; font-weight:800; color:#fff; margin-bottom:12px">🌍 Top Countries</h3>
        <div class="details-column">
          <?php
          $max_country = max(1, count($country_totals) > 0 ? max($country_totals) : 1);
          $country_idx = 0;
          foreach (array_slice($country_totals, 0, 5) as $code => $count):
              $pct = ($count / $max_country) * 100;
              $country_name = get_country_name($code);
              $flag = get_country_flag_emoji($code);
              $country_idx++;
          ?>
            <div class="details-row">
              <span class="details-name"><?= $flag ?> <?= e($country_name) ?></span>
              <div class="details-bar-wrap">
                <div class="details-bar" style="width: <?= $pct ?>%"></div>
              </div>
              <span class="details-val"><?= format_number($count) ?></span>
            </div>
          <?php endforeach; ?>
          <?php if (empty($country_totals)): ?>
            <div style="text-align:center; padding:20px; color:var(--text-muted); font-size:0.8rem">No country records</div>
          <?php endif; ?>
        </div>
      </div>
      
      <!-- Top Cities -->
      <div>
        <h3 style="font-size:0.95rem; font-weight:800; color:#fff; margin-bottom:12px">📍 Top Cities</h3>
        <div class="details-column">
          <?php
          $max_city = max(1, count($city_totals) > 0 ? max($city_totals) : 1);
          $city_idx = 0;
          foreach (array_slice($city_totals, 0, 5) as $name => $count):
              $pct = ($count / $max_city) * 100;
              $city_idx++;
          ?>
            <div class="details-row">
              <span class="details-name"><?= e($name) ?></span>
              <div class="details-bar-wrap">
                <div class="details-bar" style="width: <?= $pct ?>%; background: var(--color-blue)"></div>
              </div>
              <span class="details-val"><?= format_number($count) ?></span>
            </div>
          <?php endforeach; ?>
          <?php if (empty($city_totals)): ?>
            <div style="text-align:center; padding:20px; color:var(--text-muted); font-size:0.8rem">No city records</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    
    <!-- Right: Visitor Loyalty Doughnut Chart -->
    <div class="card">
      <h3 style="font-size:0.95rem; font-weight:800; color:#fff; margin-bottom:18px">👥 Visitor Loyalty</h3>
      <div style="height:160px; display:flex; justify-content:center; align-items:center">
        <canvas id="visitorDoughnut" style="max-height:100%; max-width:180px;"></canvas>
      </div>
      <div style="display:flex; justify-content:space-around; font-size:0.75rem; margin-top:15px; color:var(--text-secondary)">
        <span>🔵 New: <strong><?= $v_total > 0 ? round(($v_new / $v_total) * 100, 1) : 0 ?>%</strong></span>
        <span>🟢 Returning: <strong><?= $v_total > 0 ? round(($v_returning / $v_total) * 100, 1) : 0 ?>%</strong></span>
      </div>
    </div>
  </div>

  <div class="breakdown-row">
    <!-- Left: Traffic Sources Doughnut -->
    <div class="card">
      <h3 style="font-size:0.95rem; font-weight:800; color:#fff; margin-bottom:18px">🔗 Traffic Sources</h3>
      <div style="height:160px; display:flex; justify-content:center; align-items:center">
        <canvas id="sourceDoughnut" style="max-height:100%; max-width:180px;"></canvas>
      </div>
      <div style="display:flex; flex-wrap:wrap; justify-content:space-around; font-size:0.72rem; margin-top:15px; color:var(--text-secondary); gap:8px">
        <span>🔵 Direct: <strong><?= format_number($source_counts['direct']) ?></strong></span>
        <span>🟢 Search: <strong><?= format_number($source_counts['search']) ?></strong></span>
        <span>🟡 Social: <strong><?= format_number($source_counts['social']) ?></strong></span>
        <span>🔴 Referral: <strong><?= format_number($source_counts['referral']) ?></strong></span>
      </div>
    </div>

    <!-- Right: Devices & OS List breakdowns -->
    <div class="card" style="display:grid; grid-template-columns:1fr 1fr; gap:24px">
      <!-- Devices -->
      <div>
        <h3 style="font-size:0.95rem; font-weight:800; color:#fff; margin-bottom:12px">💻 Device Types</h3>
        <div class="details-column">
          <?php
          $total_devs = max(1, array_sum($device_counts));
          foreach ($device_counts as $dev => $count):
              $pct = ($count / $total_devs) * 100;
          ?>
            <div class="details-row">
              <span class="details-name" style="text-transform:capitalize"><?= e($dev) ?></span>
              <div class="details-bar-wrap">
                <div class="details-bar" style="width: <?= $pct ?>%; background: var(--color-warning)"></div>
              </div>
              <span class="details-val"><?= round($pct, 1) ?>%</span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      
      <!-- OS & Browsers lists -->
      <div>
        <h3 style="font-size:0.95rem; font-weight:800; color:#fff; margin-bottom:12px">🖥️ Top Browsers</h3>
        <div class="details-column">
          <?php
          $total_browsers = max(1, array_sum($browser_counts));
          foreach (array_slice($browser_counts, 0, 3) as $browser => $count):
              $pct = ($count / $total_browsers) * 100;
          ?>
            <div class="details-row">
              <span class="details-name"><?= e($browser) ?></span>
              <div class="details-bar-wrap">
                <div class="details-bar" style="width: <?= $pct ?>%; background: var(--color-pink)"></div>
              </div>
              <span class="details-val"><?= round($pct, 1) ?>%</span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Content Analytics Tabs -->
  <div class="card">
    <div class="tab-headers">
      <button class="tab-btn active" onclick="switchTab(event, 'videos-tab')">🎥 Video Performance</button>
      <button class="tab-btn" onclick="switchTab(event, 'reels-tab')">📱 Reel Performance</button>
    </div>
    
    <!-- Videos Tab -->
    <div class="tab-pane active" id="videos-tab">
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th style="width:50px">Rank</th>
              <th>Video Title</th>
              <th>Creator</th>
              <th style="text-align:right">Total Views</th>
              <th style="text-align:right">Unique Viewers</th>
              <th style="text-align:right">Total Duration</th>
              <th style="text-align:right">Engagement Rate</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($top_videos as $idx => $v):
                $views = $v['watch_count'] > 0 ? $v['watch_count'] : $v['total_views_meta'];
                $length = (int)$v['video_length'];
                $avg_dur = $v['watch_count'] > 0 ? ($v['watch_duration'] / $v['watch_count']) : 0;
                $eng_rate = $length > 0 ? min(100.0, ($avg_dur / $length) * 100) : 0;
            ?>
              <tr>
                <td style="font-weight:700; color:var(--text-muted)">#<?= $idx + 1 ?></td>
                <td class="content-title-cell" title="<?= e($v['title']) ?>"><?= e($v['title']) ?></td>
                <td style="color:var(--text-secondary)"><?= e($v['channel_name']) ?></td>
                <td style="text-align:right; font-weight:700"><?= format_number($views) ?></td>
                <td style="text-align:right"><?= format_number($v['unique_viewers']) ?></td>
                <td style="text-align:right"><?= format_seconds($v['watch_duration']) ?></td>
                <td style="text-align:right; font-weight:700; color:var(--color-success)"><?= number_format($eng_rate, 1) ?>%</td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($top_videos)): ?>
              <tr>
                <td colspan="7" style="text-align:center; padding:30px; color:var(--text-muted)">No video traffic logged yet.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Reels Tab -->
    <div class="tab-pane" id="reels-tab">
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th style="width:50px">Rank</th>
              <th>Reel Title</th>
              <th>Creator</th>
              <th style="text-align:right">Total Views</th>
              <th style="text-align:right">Unique Viewers</th>
              <th style="text-align:right">Total Duration</th>
              <th style="text-align:right">Completed Views (≥15s)</th>
              <th style="text-align:right">Completion Rate</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($top_reels as $idx => $r):
                $views = $r['watch_count'] > 0 ? $r['watch_count'] : $r['total_views_meta'];
                $comp_rate = $r['watch_count'] > 0 ? min(100.0, ($r['completed_watches'] / $r['watch_count']) * 100) : 0;
            ?>
              <tr>
                <td style="font-weight:700; color:var(--text-muted)">#<?= $idx + 1 ?></td>
                <td class="content-title-cell" title="<?= e($r['title']) ?>"><?= e($r['title']) ?></td>
                <td style="color:var(--text-secondary)"><?= e($r['channel_name']) ?></td>
                <td style="text-align:right; font-weight:700"><?= format_number($views) ?></td>
                <td style="text-align:right"><?= format_number($r['unique_viewers']) ?></td>
                <td style="text-align:right"><?= format_seconds($r['watch_duration']) ?></td>
                <td style="text-align:right"><?= format_number($r['completed_watches']) ?></td>
                <td style="text-align:right; font-weight:700; color:var(--color-pink)"><?= number_format($comp_rate, 1) ?>%</td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($top_reels)): ?>
              <tr>
                <td colspan="8" style="text-align:center; padding:30px; color:var(--text-muted)">No reel traffic logged yet.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
// ── Interactive Custom Date Range Toggle ──────────────────────
function toggleCustomRange() {
  const form = document.getElementById('custom-range-form');
  const pills = document.querySelectorAll('.preset-pill');
  
  if (form.style.display === 'flex') {
    form.style.display = 'none';
  } else {
    form.style.display = 'flex';
    pills.forEach(p => p.classList.remove('active'));
    event.currentTarget.classList.add('active');
  }
}

// ── Tab Switching ─────────────────────────────────────────────
function switchTab(e, tabId) {
  const btns = document.querySelectorAll('.tab-btn');
  const panes = document.querySelectorAll('.tab-pane');
  
  btns.forEach(b => b.classList.remove('active'));
  panes.forEach(p => p.classList.remove('active'));
  
  e.currentTarget.classList.add('active');
  document.getElementById(tabId).classList.add('active');
}

// ── Real-Time Analytics Dynamic Refresher ─────────────────────
function updateRealTimeStats() {
  fetch('<?= BASE_URL ?>/api/tracker.php?action=live_stats')
    .then(res => res.json())
    .then(data => {
      if (data && data.success) {
        // Update live counters
        document.getElementById('rt-active-count').textContent = data.live.active_now;
        document.getElementById('rt-logged-in').textContent = data.live.logged_in;
        document.getElementById('rt-guests').textContent = data.live.guests;
        document.getElementById('rt-views-total').textContent = data.live.page_views + data.live.video_views + data.live.reel_views;
        
        document.getElementById('rt-pageviews').textContent = data.live.page_views;
        document.getElementById('rt-videoviews').textContent = data.live.video_views;
        document.getElementById('rt-reelviews').textContent = data.live.reel_views;
        
        // Update activity feed
        const feed = document.getElementById('rt-activity-feed');
        if (data.activities && data.activities.length > 0) {
          let html = '';
          data.activities.forEach(act => {
            const flagEmoji = getFlagEmoji(act.country);
            html += `
              <div class="activity-item">
                <span class="act-time">${act.time}</span>
                <span class="act-user">${escapeHtml(act.user)}</span>
                <span class="act-desc">${escapeHtml(act.desc)}</span>
                <span class="act-flag" title="${act.country}">${flagEmoji}</span>
              </div>
            `;
          });
          feed.innerHTML = html;
        } else {
          feed.innerHTML = `
            <div style="text-align:center; padding: 40px 0; color:var(--text-muted); font-size:0.85rem">
              No recent activity recorded.
            </div>
          `;
        }
      }
    })
    .catch(err => console.warn('Real-time stats sync error:', err));
}

function getFlagEmoji(countryCode) {
  const code = countryCode.toUpperCase();
  if (code.length !== 2) return '🏳️';
  const copypasta = {
    'A': '🇦', 'B': '🇧', 'C': '🇨', 'D': '🇩', 'E': '🇪', 'F': '🇫', 'G': '🇬', 'H': '🇭',
    'I': '🇮', 'J': '🇯', 'K': '🇰', 'L': '🇱', 'M': '🇲', 'N': '🇳', 'O': '🇴', 'P': '🇵',
    'Q': '🇶', 'R': '🇷', 'S': '🇸', 'T': '🇹', 'U': '🇺', 'V': '🇻', 'W': '🇼', 'X': '🇽',
    'Y': '🇾', 'Z': '🇿'
  };
  // JS mapping fallback or convert code characters
  return code.split('').map(char => {
    const codePoint = char.charCodeAt(0) + 127397;
    return String.fromCodePoint(codePoint);
  }).join('');
}

function escapeHtml(string) {
  return String(string).replace(/[&<>"']/g, function (s) {
    return {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;'
    }[s];
  });
}

// Start polling immediately and trigger every 5 seconds
updateRealTimeStats();
setInterval(updateRealTimeStats, 5000);

// ── Chart.js Configurations ───────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  // 1. Line Chart (Traffic Trends)
  const ctx = document.getElementById('trafficChart').getContext('2d');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: <?= $js_labels ?>,
      datasets: [
        {
          label: 'Page Views',
          data: <?= $js_pageviews ?>,
          borderColor: '#3b82f6',
          backgroundColor: 'rgba(59, 130, 246, 0.05)',
          tension: 0.35,
          fill: true,
          borderWidth: 2,
          pointRadius: 3
        },
        {
          label: 'Total Sessions',
          data: <?= $js_visits ?>,
          borderColor: '#6366f1',
          backgroundColor: 'rgba(99, 102, 241, 0.05)',
          tension: 0.35,
          fill: true,
          borderWidth: 2.5,
          pointRadius: 3.5
        },
        {
          label: 'Video Views',
          data: <?= $js_video_views ?>,
          borderColor: '#10b981',
          borderDash: [5, 5],
          tension: 0.3,
          borderWidth: 1.8,
          pointRadius: 2
        },
        {
          label: 'Reel Views',
          data: <?= $js_reel_views ?>,
          borderColor: '#ec4899',
          borderDash: [5, 5],
          tension: 0.3,
          borderWidth: 1.8,
          pointRadius: 2
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          labels: { color: '#94a3b8', font: { family: 'Inter', size: 11, weight: 'bold' } }
        }
      },
      scales: {
        x: {
          grid: { color: '#20202e' },
          ticks: { color: '#94a3b8', font: { size: 10 } }
        },
        y: {
          grid: { color: '#20202e' },
          ticks: { color: '#94a3b8', font: { size: 10 } },
          beginAtZero: true
        }
      }
    }
  });

  // 2. Loyalty Doughnut Chart
  const loyaltyCtx = document.getElementById('visitorDoughnut').getContext('2d');
  new Chart(loyaltyCtx, {
    type: 'doughnut',
    data: {
      labels: ['New', 'Returning'],
      datasets: [{
        data: [<?= $js_visitor_new ?>, <?= $js_visitor_returning ?>],
        backgroundColor: ['#3b82f6', '#10b981'],
        borderWidth: 1.5,
        borderColor: '#111119'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false }
      },
      cutout: '68%'
    }
  });

  // 3. Traffic Sources Doughnut Chart
  const sourceCtx = document.getElementById('sourceDoughnut').getContext('2d');
  new Chart(sourceCtx, {
    type: 'doughnut',
    data: {
      labels: ['Direct', 'Search', 'Social', 'Referral'],
      datasets: [{
        data: [
          <?= (int)$source_counts['direct'] ?>,
          <?= (int)$source_counts['search'] ?>,
          <?= (int)$source_counts['social'] ?>,
          <?= (int)$source_counts['referral'] ?>
        ],
        backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#ef4444'],
        borderWidth: 1.5,
        borderColor: '#111119'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false }
      },
      cutout: '68%'
    }
  });
});
</script>

<?php require_once __DIR__ . '/partials/admin_foot.php'; ?>
