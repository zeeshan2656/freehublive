<?php
// ============================================================
// FreeHub.Live — Shared Header
// ============================================================
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$user        = auth_user();
$site_name   = setting('site_name', 'FreeHub');
$site_theme  = setting('active_theme', 'dark-minimal');
$primary     = setting('primary_color', '#6366f1');
$ref_code    = get_ref_code();
if ($ref_code) track_affiliate_click($ref_code, (int)($_GET['v'] ?? 0));

// Maintenance Mode Guard
if (setting('maintenance', '0') === '1') {
    $is_admin = is_logged_in() && is_admin();
    $is_auth_page = str_contains($_SERVER['PHP_SELF'], '/auth/') || str_contains($_SERVER['PHP_SELF'], 'login.php') || str_contains($_SERVER['PHP_SELF'], 'logout.php');
    if (!$is_admin && !$is_auth_page) {
        $is_api = str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/') || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
        if ($is_api) {
            header('Content-Type: application/json');
            http_response_code(503);
            echo json_encode(['error' => 'Maintenance Mode is active']);
            exit;
        }
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Under Maintenance - <?= htmlspecialchars($site_name) ?></title>
            <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
            <style>
                * {
                    box-sizing: border-box;
                    margin: 0;
                    padding: 0;
                }
                body {
                    font-family: 'Outfit', sans-serif;
                    background: radial-gradient(circle at 50% 50%, #0f172a 0%, #020617 100%);
                    color: #f8fafc;
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 24px;
                    overflow: hidden;
                    position: relative;
                }
                body::before, body::after {
                    content: '';
                    position: absolute;
                    width: 350px;
                    height: 350px;
                    border-radius: 50%;
                    background: #6366f1;
                    filter: blur(120px);
                    opacity: 0.15;
                    z-index: 1;
                    animation: pulseGlow 8s infinite alternate;
                }
                body::before {
                    top: -50px;
                    left: -50px;
                }
                body::after {
                    bottom: -50px;
                    right: -50px;
                    background: #ec4899;
                }
                @keyframes pulseGlow {
                    0% { transform: scale(1); opacity: 0.12; }
                    100% { transform: scale(1.2); opacity: 0.2; }
                }
                .maintenance-card {
                    background: rgba(15, 23, 42, 0.45);
                    border: 1px solid rgba(255, 255, 255, 0.08);
                    backdrop-filter: blur(24px);
                    -webkit-backdrop-filter: blur(24px);
                    border-radius: 28px;
                    padding: 48px 40px;
                    max-width: 620px;
                    width: 100%;
                    text-align: center;
                    box-shadow: 0 30px 60px rgba(0, 0, 0, 0.6), 
                                0 0 100px rgba(99, 102, 241, 0.1);
                    z-index: 10;
                    position: relative;
                    transform: translateY(0);
                    animation: float 6s ease-in-out infinite;
                }
                @keyframes float {
                    0%, 100% { transform: translateY(0px); }
                    50% { transform: translateY(-10px); }
                }
                .icon-container {
                    width: 96px;
                    height: 96px;
                    background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(236, 72, 153, 0.1) 100%);
                    border: 1px solid rgba(255, 255, 255, 0.15);
                    border-radius: 24px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 32px;
                    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.25);
                    position: relative;
                }
                .icon-container::after {
                    content: '';
                    position: absolute;
                    inset: -4px;
                    border-radius: 28px;
                    background: linear-gradient(135deg, #6366f1, #ec4899);
                    z-index: -1;
                    opacity: 0.4;
                    filter: blur(8px);
                }
                .icon-svg {
                    width: 48px;
                    height: 48px;
                    color: #6366f1;
                    animation: spin 10s linear infinite;
                }
                @keyframes spin {
                    100% { transform: rotate(360deg); }
                }
                h1 {
                    font-size: 2.2rem;
                    font-weight: 800;
                    letter-spacing: 0.1em;
                    background: linear-gradient(135deg, #a5b4fc 0%, #6366f1 50%, #4338ca 100%);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    background-clip: text;
                    margin-bottom: 24px;
                    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
                }
                p {
                    font-size: 1.15rem;
                    line-height: 1.8;
                    color: #94a3b8;
                    font-weight: 400;
                    margin-bottom: 32px;
                }
                .brand-badge {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    background: rgba(255, 255, 255, 0.04);
                    border: 1px solid rgba(255, 255, 255, 0.08);
                    padding: 8px 18px;
                    border-radius: 99px;
                    font-size: 0.85rem;
                    font-weight: 600;
                    color: #cbd5e1;
                    letter-spacing: 0.05em;
                }
                .pulse-dot {
                    width: 8px;
                    height: 8px;
                    background-color: #10b981;
                    border-radius: 50%;
                    box-shadow: 0 0 10px #10b981;
                    animation: pulse-ring 1.5s cubic-bezier(0.215, 0.610, 0.355, 1) infinite;
                }
                @keyframes pulse-ring {
                    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
                    70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
                    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
                }
            </style>
        </head>
        <body>
            <div class="maintenance-card">
                <div class="icon-container">
                    <svg class="icon-svg" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.43l-1.003.828c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.43l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.991l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.645-.869L9.594 3.94z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <h1>PLEASE WAIT</h1>
                <p><?= htmlspecialchars(trim(setting('maintenance_message', '')) ?: 'MAINTANCE MODE IS ON , I THINK ADMINSTRATOR OR MANAGEMENT TEAM IS MAINTANING SOME, I THINK SOMETHING AMAZING IS COMMING OVER THE SITE') ?></p>
                <div class="brand-badge">
                    <span class="pulse-dot"></span>
                    <span><?= htmlspecialchars($site_name) ?> System Update</span>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

$nav_cat_limit = (int)setting('dropdown_cat_limit', 8);
$is_search_page = basename($_SERVER['PHP_SELF'] ?? '') === 'search.php';

$current_page = basename($_SERVER['PHP_SELF'] ?? '');
$current_q = $_GET['q'] ?? '';
$current_sort = $_GET['sort'] ?? '';
$is_home = ($current_page === 'index.php' && !isset($_GET['cat']));
$is_trending = ($current_page === 'search.php' && ($current_q === 'trending' || $current_sort === 'views')) || ($current_page === 'index.php' && isset($_GET['sort']) && $_GET['sort'] === 'views');
$is_latest = ($current_page === 'search.php' && $current_sort === 'latest') || ($current_page === 'index.php' && isset($_GET['sort']) && $_GET['sort'] === 'latest');
$is_categories = ($current_page === 'categories.php');
$is_reels = ($current_page === 'reels.php' && !str_contains($_SERVER['PHP_SELF'] ?? '', '/admin/') && !str_contains($_SERVER['PHP_SELF'] ?? '', '/creator/'));
$nav_cats = db_fetchAll(
    "SELECT id, name, slug, image, color FROM categories WHERE is_active=1 ORDER BY sort_order LIMIT ?",
    [$nav_cat_limit]
);

function get_sidebar_path(): string {
    if (!is_logged_in()) {
        return __DIR__ . '/partials/viewer_sidebar.php';
    }

    $role = auth_user()['role'] ?? 'viewer';

    if (is_admin() && isset($_GET['uid']) && (int)$_GET['uid'] > 0) {
        $preview = db_fetch('SELECT role FROM users WHERE id=?', [(int)$_GET['uid']]);
        if ($preview && !empty($preview['role'])) {
            $role = $preview['role'];
        }
    }

    return match ($role) {
        'admin' => __DIR__ . '/partials/admin_sidebar.php',
        'creator' => __DIR__ . '/partials/creator_sidebar.php',
        default => __DIR__ . '/partials/viewer_sidebar.php',
    };
}

$meta_title  = $meta_title  ?? $site_name;
$meta_desc   = $meta_desc   ?? 'Watch, share and earn — ' . $site_name;
$meta_image  = $meta_image  ?? BASE_URL . '/assets/img/og-default.jpg';

$is_dashboard_page = (
    str_contains($_SERVER['PHP_SELF'], '/admin/') ||
    str_contains($_SERVER['PHP_SELF'], '/creator/') ||
    str_contains($_SERVER['PHP_SELF'], '/affiliate/') ||
    basename($_SERVER['PHP_SELF']) === 'dashboard.php' ||
    basename($_SERVER['PHP_SELF']) === 'settings.php' ||
    basename($_SERVER['PHP_SELF']) === 'profile.php'
);

// Global Access Guard for Dashboard Pages
if ($is_dashboard_page && is_logged_in() && !is_admin()) {
    $status = auth_user()['status'] ?? 'pending';
    if ($status !== 'active') {
        $currPage = basename($_SERVER['PHP_SELF']);
        if ($currPage !== 'status.php' && $currPage !== 'logout.php') {
            header('Location: ' . BASE_URL . '/auth/status.php');
            exit;
        }
    }
}
?>

<?php /* Note: currency vars kept for earnings display but dropdown removed */ ?>

<?php
$active_currency  = fh_user_currency();
$currencies_list  = fh_currencies();
$currency_symbol  = $currencies_list[$active_currency]['symbol'] ?? $active_currency;
?>

<!DOCTYPE html>
<html lang="en" data-theme="<?= e($site_theme) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="<?= e($primary) ?>">
<title><?= e($meta_title) ?></title>
<meta name="description" content="<?= e($meta_desc) ?>">
<?php if (!empty($meta_keywords)): ?>
<meta name="keywords" content="<?= e($meta_keywords) ?>">
<?php endif; ?>
<meta property="og:title"       content="<?= e($meta_title) ?>">
<meta property="og:description" content="<?= e($meta_desc) ?>">
<meta property="og:image"       content="<?= e($meta_image) ?>">
<meta property="og:type"        content="website">
<meta name="twitter:card"       content="summary_large_image">
<meta name="base-url" content="<?= e(BASE_URL) ?>">
<link rel="canonical" href="<?= e(BASE_URL . ($_SERVER['REQUEST_URI'] ?? '')) ?>">

<!-- ── Resource Hints ── -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="dns-prefetch" href="https://fonts.googleapis.com">
<link rel="dns-prefetch" href="https://fonts.gstatic.com">
<link rel="dns-prefetch" href="https://i.ytimg.com">
<link rel="dns-prefetch" href="https://www.youtube.com">

<!-- ── Critical CSS (inline for instant first paint) ── -->
<style>
@media (min-width: 769px) {
  .reels-mobile-btn { display: none !important; }
}
/* Reset + Variables — inlined to eliminate render-blocking CSS */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth;-webkit-text-size-adjust:100%}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);line-height:1.5;min-height:100vh;overflow-x:hidden}
img,video{max-width:100%;display:block}a{color:inherit;text-decoration:none}
button{cursor:pointer;border:none;background:none;font-family:inherit}
input,select,textarea{font-family:inherit;outline:none}
:root{--bg:#141414;--bg2:#1a1a1a;--bg3:#222222;--surface:#1e1e1e;--border:rgba(255,255,255,.1);--text:#efefef;--text2:#999;--text3:#555;--accent:#ff6600;--accent2:#ff8533;--red:#e53935;--green:#43a047;--yellow:#ffb300;--radius:3px;--radius-lg:4px;--shadow:0 4px 16px rgba(0,0,0,.6);--shadow-sm:0 2px 6px rgba(0,0,0,.4);--font:'Inter',sans-serif;--font2:'Outfit',sans-serif;--nav-h:56px;--trans:.15s ease}
[data-theme="light-white"]{--bg:#f8fafc;--bg2:#fff;--bg3:#f1f5f9;--surface:#fff;--accent:#3b82f6;--accent2:#60a5fa;--text:#0f172a;--text2:#475569;--border:rgba(0,0,0,.08);--shadow:0 4px 12px rgba(0,0,0,.04);--radius:4px;--radius-lg:6px}
[data-theme="dark-minimal"]{--bg:#0a0f1d;--bg2:#111827;--bg3:#1f2937;--surface:#111827;--accent:#3b82f6;--accent2:#60a5fa;--text:#f3f4f6;--text2:#9ca3af;--border:rgba(255,255,255,.08);--radius:4px;--radius-lg:6px}
/* Navbar */
.navbar{position:sticky;top:0;z-index:100;height:var(--nav-h);background:var(--bg2)!important;border-bottom:1px solid var(--border);box-shadow:0 2px 10px rgba(0,0,0,0.16)}
.navbar-inner{height:100%;display:flex;align-items:center;gap:16px;padding:0 20px}
/* Layout */
.container{width:100%;max-width:1400px;margin:0 auto;padding:0 20px}
.layout{display:flex;min-height:calc(100vh - var(--nav-h))}
.main-content{flex:1;min-width:0;padding:20px}
.grid{display:grid;gap:16px}
.grid-6{grid-template-columns:repeat(6,1fr)}
.flex{display:flex;align-items:center}
/* Prevent FOUC */
.site-logo{display:inline-flex;align-items:center;gap:10px;text-decoration:none;flex-shrink:0;min-width:0;color:inherit}
</style>

<!-- ── Main CSS (async — non-blocking) ── -->
<link rel="stylesheet" href="<?= fh_asset_url('assets/css/main.css') ?>" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="<?= fh_asset_url('assets/css/main.css') ?>"></noscript>



<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@400;600;700;800&display=swap">
<link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/img/logo.svg">
<script src="<?= fh_asset_url('assets/js/reels-cache.js') ?>"></script>
<style>
:root{--accent:<?= e($primary) ?>;--accent2:<?= e($primary) ?>cc}
/* Responsive Header Right Actions */
.nav-actions {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 12px;
}
.nav-actions .btn {
  height: 34px;
  border-radius: 17px;
  font-size: 0.82rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0 14px;
}
.nav-actions .btn-icon {
  width: 34px;
  height: 34px;
  border-radius: 50%;
}
.nav-actions .avatar {
  border-radius: 50%;
  object-fit: cover;
  transition: transform 0.2s;
}
.nav-actions .avatar:hover {
  transform: scale(1.05);
}

/* Categories dropdown in navbar */
#nav-category-dropdown {
  position: relative;
}

@media(min-width: 769px) {
  .mobile-only-block,
  .mobile-only {
    display: none !important;
  }
  /* Sub-header search bar — desktop only hidden */
  .sub-header-search { display: none !important; }
}

/* ── Sub-header search bar (mobile) ── */
.sub-header-search {
  position: sticky;
  top: var(--nav-h);
  z-index: 99;
  background: var(--bg2);
  border-bottom: 1px solid var(--border);
  padding: 8px 12px;
  display: none; /* hidden by default on mobile */
  align-items: center;
}
.sub-header-search.active {
  display: flex !important;
}
.sub-header-search form {
  display: flex;
  align-items: center;
  width: 100%;
  background: var(--bg3);
  border: 1px solid var(--border);
  border-radius: 20px;
  overflow: hidden;
  padding: 0 4px 0 14px;
  height: 38px;
}
.sub-header-search input {
  flex: 1;
  background: none;
  border: none;
  color: var(--text);
  font-size: .88rem;
  outline: none;
  min-width: 0;
}
.sub-header-search input::placeholder { color: var(--text2); }
.sub-header-search button {
  background: none;
  border: none;
  color: var(--text2);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 6px;
  cursor: pointer;
  flex-shrink: 0;
}

/* ── Mobile navbar layout: hamburger | logo (center) | user ── */
@media(max-width:768px) {
  .navbar-inner {
    position: relative;
    justify-content: flex-start;
  }
  /* Hide desktop search bar inside navbar on mobile */
  .navbar-search-desktop { display: none !important; }
  /* Hide desktop nav links on mobile */
  .header-nav-links { display: none !important; }
  /* Logo next to menu toggle */
  .site-logo--nav {
    margin-left: 8px;
    display: inline-flex;
    align-items: center;
    flex-shrink: 0;
  }
}
/* ── Dashboard Layout & Navigation System Styles ── */
.dashboard-layout {
  display: flex;
  min-height: 100vh;
  background: var(--bg);
  font-family: var(--font);
  color: var(--text);
}
.dashboard-sidebar-container {
  width: 240px;
  flex-shrink: 0;
  background: var(--bg2);
  border-right: 1px solid var(--border);
  height: 100vh;
  position: sticky;
  top: 0;
  display: flex;
  flex-direction: column;
  z-index: 101;
  overflow-y: auto;
  transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1), left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.dashboard-main-viewport {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  height: 100vh;
  overflow-y: auto;
}
.sticky-action-bar {
  position: sticky;
  top: 0;
  z-index: 99;
  background: var(--bg2);
  border-bottom: 1px solid var(--border);
  min-height: 50px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 6px 20px;
}
.dashboard-content-scroll {
  padding: 24px;
  flex: 1;
}

/* Sidebar Branding & Items */
.sidebar-brand-area {
  padding: 16px;
  border-bottom: 1px solid var(--border);
}
.sidebar-brand-subtitle {
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--text2);
  margin-top: 4px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.studio-nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 6px 14px;
  color: var(--text2);
  font-size: 0.88rem;
  font-weight: 500;
  transition: all 0.15s;
  text-decoration: none;
  border-radius: 8px;
  margin: 1px 8px;
}
.studio-nav-item:hover {
  background: var(--bg3);
  color: var(--text);
}
.studio-nav-item.active {
  background: rgba(99, 102, 241, 0.12);
  color: var(--accent);
  font-weight: 600;
}
.studio-nav-item.logout-link:hover {
  background: rgba(239, 68, 68, 0.08);
}

/* Hide public navbar and footer on dashboard page */
.dashboard-page #main-navbar,
.dashboard-page #sub-header-search,
.dashboard-page .site-footer {
  display: none !important;
}

/* Responsive sidebar */
@media(max-width: 768px) {
  .dashboard-sidebar-container {
    position: fixed;
    top: 0;
    left: -240px;
    bottom: 0;
    transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    height: 100vh;
    background: var(--bg2);
  }
  .dashboard-sidebar-container.open {
    left: 0;
  }
  .sticky-action-bar {
    padding: 8px 16px;
  }
  .dashboard-content-scroll {
    padding: 16px;
  }
  
  .dashboard-layout {
    min-height: 100vh;
  }
}

/* Desktop sidebar collapsed rules */
@media (min-width: 769px) {
  body.sidebar-collapsed .dashboard-sidebar-container {
    margin-left: -240px;
  }
}

#dashboard-sidebar-toggle {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
#dashboard-sidebar-toggle:hover {
  opacity: 0.8;
  transform: scale(1.05);
}
</style>
<?php
// ── Age Verification Gate ──────────────────────────────────────
$_fh_adult_mode = setting('adult_mode', '0') === '1';
$_fh_adult_exempt = is_logged_in() && is_admin();
$_fh_show_age_gate = $_fh_adult_mode && !$_fh_adult_exempt;
?>
<?php if ($_fh_show_age_gate): ?>
<style>
/* Hide ALL body content until verified — prevents content flash */
body.age-unverified > *:not(#fh-age-gate) { display: none !important; }
body.age-unverified { overflow: hidden; }

#fh-age-gate {
  position: fixed; inset: 0; z-index: 999999;
  display: flex; align-items: center; justify-content: center;
  background: var(--bg1, #0a0a0f);
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}
#fh-age-gate .age-gate-backdrop {
  position: absolute; inset: 0;
  background: radial-gradient(ellipse at center, rgba(99,102,241,.08) 0%, transparent 70%);
  pointer-events: none;
}
#fh-age-gate .age-gate-card {
  position: relative; z-index: 1;
  max-width: 440px; width: 92%; padding: 44px 36px;
  border-radius: 16px;
  background: var(--bg2, #16161d);
  border: 1px solid var(--border, rgba(255,255,255,.08));
  box-shadow: 0 24px 80px rgba(0,0,0,.5), 0 0 0 1px rgba(255,255,255,.04);
  text-align: center;
  animation: ageGateFadeIn .4s ease-out;
}
@keyframes ageGateFadeIn {
  from { opacity: 0; transform: translateY(20px) scale(.96); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}
#fh-age-gate .age-gate-icon {
  font-size: 3.2rem; margin-bottom: 16px;
  filter: drop-shadow(0 0 16px rgba(239,68,68,.3));
}
#fh-age-gate h2 {
  font-family: var(--font2, 'Inter', sans-serif);
  font-size: 1.6rem; font-weight: 800;
  color: var(--text, #e8e8f0);
  margin: 0 0 8px;
}
#fh-age-gate .age-gate-subtitle {
  color: var(--text2, #8b8b9e);
  font-size: .92rem; line-height: 1.55;
  margin-bottom: 28px;
}
#fh-age-gate .age-gate-sitename {
  color: var(--accent, #6366f1);
  font-weight: 700;
}
#fh-age-gate .age-gate-actions {
  display: flex; gap: 12px;
}
#fh-age-gate .age-gate-btn {
  flex: 1; height: 48px; border-radius: 24px;
  font-size: .95rem; font-weight: 700;
  border: none; cursor: pointer;
  display: inline-flex; align-items: center; justify-content: center;
  transition: all .2s ease;
  letter-spacing: .02em;
}
#fh-age-gate .age-gate-btn-yes {
  background: linear-gradient(135deg, var(--accent, #6366f1), #8b5cf6);
  color: #fff;
  box-shadow: 0 4px 20px rgba(99,102,241,.35);
}
#fh-age-gate .age-gate-btn-yes:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 28px rgba(99,102,241,.45);
}
#fh-age-gate .age-gate-btn-no {
  background: transparent;
  color: var(--text2, #8b8b9e);
  border: 1px solid var(--border, rgba(255,255,255,.1));
}
#fh-age-gate .age-gate-btn-no:hover {
  border-color: rgba(239,68,68,.5);
  color: var(--red, #ef4444);
  background: rgba(239,68,68,.06);
}
#fh-age-gate .age-gate-legal {
  margin-top: 20px;
  font-size: .72rem; color: var(--text2, #8b8b9e);
  opacity: .6; line-height: 1.5;
}
/* Blocked state */
#fh-age-gate .age-gate-blocked { display: none; }
#fh-age-gate.blocked .age-gate-main { display: none; }
#fh-age-gate.blocked .age-gate-blocked { display: block; }
#fh-age-gate .age-gate-blocked h2 { color: var(--red, #ef4444); }
#fh-age-gate .age-gate-blocked-msg {
  color: var(--text2, #8b8b9e);
  font-size: .9rem; line-height: 1.6;
  margin-bottom: 24px;
}
#fh-age-gate .age-gate-btn-leave {
  display: inline-flex; align-items: center; justify-content: center;
  height: 44px; padding: 0 28px; border-radius: 22px;
  background: rgba(239,68,68,.1);
  color: var(--red, #ef4444); border: 1px solid rgba(239,68,68,.25);
  font-weight: 600; font-size: .88rem;
  text-decoration: none; transition: all .2s ease;
}
#fh-age-gate .age-gate-btn-leave:hover { background: rgba(239,68,68,.18); }
@media (max-width: 480px) {
  #fh-age-gate .age-gate-card { padding: 32px 24px; }
  #fh-age-gate h2 { font-size: 1.35rem; }
  #fh-age-gate .age-gate-actions { flex-direction: column; }
  #fh-age-gate .age-gate-btn { height: 44px; }
}
</style>
<?php endif; ?>
<?php
$is_admin_page = str_contains($_SERVER['PHP_SELF'] ?? '', '/admin/');
if (!$is_admin_page && setting('ad_code_header_enabled', '0') === '1' && !empty($ad_code_h = setting('ad_code_header'))):
    echo $ad_code_h . "\n";
endif;
?>
</head>

<?php

$header_ad_html = '';
$header_ad_height = 55; // Default fallback
$is_mobile = function_exists('detect_device') ? (detect_device() === 'mobile') : false;
$show_header_ad = !$is_reels && (!$is_dashboard_page || $is_mobile);

if ($show_header_ad && function_exists('render_ad_placeholder')) {
    $header_ad_html = render_ad_placeholder('home_mobile_top');
    if (!empty($header_ad_html)) {
        $now = date('Y-m-d');
        $ad_placement = db_fetch(
            "SELECT COALESCE(ap.ad_height, a.ad_height) AS height 
             FROM ads a
             JOIN ad_placements ap ON ap.assigned_ad_id = a.id
             WHERE ap.key_name = 'home_mobile_top' AND a.is_active = 1
               AND (a.start_date IS NULL OR a.start_date <= ?)
               AND (a.end_date IS NULL OR a.end_date >= ?)
             LIMIT 1",
            [$now, $now]
        );
        if ($ad_placement && $ad_placement['height']) {
            $header_ad_height = (int)$ad_placement['height'];
        }
    }
}

$sidebar_path = get_sidebar_path();
?>

<body class="<?= $is_dashboard_page ? 'dashboard-page' : 'public-page' ?><?= $_fh_show_age_gate ? ' age-unverified' : '' ?><?= isset($is_watch) && $is_watch ? ' watch-page' : '' ?><?= !empty($header_ad_html) ? ' has-header-ad' : '' ?>" style="--ad-h: <?= (int)$header_ad_height ?>px;">
<?php if ($is_dashboard_page): ?>
<script>
  if (localStorage.getItem('sidebar_collapsed') === '1' && window.innerWidth > 768) {
    document.body.classList.add('sidebar-collapsed');
  }
</script>
<?php endif; ?>
<?php
if (!$is_admin_page && setting('ad_code_body_enabled', '0') === '1' && setting('ad_code_body_placement', 'bottom') === 'top' && !empty($ad_code_b = setting('ad_code_body'))):
    echo $ad_code_b . "\n";
endif;
?>
<div id="monetization-warning-banner" style="display:none; margin: 16px 20px 0; text-align: center; border-radius: 8px; font-weight: 600; z-index: 9999; position: relative; padding: 12px 24px; background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.25); color: #fca5a5; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.1); font-family: var(--font2, inherit); animation: fadeIn 0.3s ease;"></div>

<?php if ($_fh_show_age_gate): ?>
<!-- Age Verification Gate — renders BEFORE any site content -->
<div id="fh-age-gate">
  <div class="age-gate-backdrop"></div>
  <div class="age-gate-card">
    <!-- Main verification view -->
    <div class="age-gate-main">
      <div class="age-gate-icon">🔞</div>
      <h2>Age Verification Required</h2>
      <p class="age-gate-subtitle">
        <span class="age-gate-sitename"><?= e($site_name) ?></span> contains age-restricted content.<br>
        You must be <strong>18 years or older</strong> to access this website.
      </p>
      <div class="age-gate-actions">
        <button class="age-gate-btn age-gate-btn-yes" id="age-gate-yes" type="button">
          ✓ Yes, I'm 18+
        </button>
        <button class="age-gate-btn age-gate-btn-no" id="age-gate-no" type="button">
          ✕ No, I'm Not
        </button>
      </div>
      <p class="age-gate-legal">
        By entering, you confirm you are of legal age in your jurisdiction
        and agree to our Terms of Service.
      </p>
    </div>
    <!-- Blocked / Denied view -->
    <div class="age-gate-blocked">
      <div class="age-gate-icon">🚫</div>
      <h2>Access Denied</h2>
      <p class="age-gate-blocked-msg">
        You must be 18 years or older to access <strong><?= e($site_name) ?></strong>.<br>
        This site contains age-restricted content that is not suitable for minors.
      </p>
      <a href="https://www.google.com" class="age-gate-btn-leave" id="age-gate-leave">Leave This Site</a>
    </div>
  </div>
</div>
<script>
(function(){
  var gate = document.getElementById('fh-age-gate');
  var KEY = 'fh_age_verified';
  // Check sessionStorage for existing verification
  if (sessionStorage.getItem(KEY) === '1') {
    gate.remove();
    document.body.classList.remove('age-unverified');
    return;
  }
  // Yes button — grant access
  document.getElementById('age-gate-yes').addEventListener('click', function(){
    sessionStorage.setItem(KEY, '1');
    gate.style.transition = 'opacity .3s ease';
    gate.style.opacity = '0';
    setTimeout(function(){
      gate.remove();
      document.body.classList.remove('age-unverified');
    }, 300);
  });
  // No button — block access
  document.getElementById('age-gate-no').addEventListener('click', function(){
    gate.classList.add('blocked');
  });
})();
</script>
<?php endif; ?>

<?php if (isset($is_reels) && $is_reels): ?>
<div class="reels-page-wrapper">
<?php elseif ($is_dashboard_page): ?>
<div class="dashboard-layout">
  <!-- Left Sidebar -->
  <aside class="dashboard-sidebar-container" id="dashboard-sidebar">
    <div class="sidebar-brand-area">
      <?= render_site_logo('studio') ?>
      <div class="sidebar-brand-subtitle">
        <?php
        $db_role = auth_user()['role'] ?? 'viewer';
        if ($db_role === 'admin') echo 'Admin Dashboard';
        elseif ($db_role === 'creator') echo 'Creator Dashboard';
        else echo 'Viewer Dashboard';
        ?>
      </div>
    </div>
    
    <nav style="padding: 12px 0; display:flex; flex-direction:column; flex:1">
      <?php
      $current_page = basename($_SERVER['PHP_SELF']);
      $current_tab = $_GET['tab'] ?? '';
      
      if ($db_role === 'admin'): ?>
        <!-- Admin Menu -->
        <a href="<?= BASE_URL ?>/admin/index.php" class="studio-nav-item <?= str_contains($_SERVER['PHP_SELF'], '/admin/') && $current_page === 'index.php' ? 'active' : '' ?>">
          <span>🏠 Dashboard</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/videos.php" class="studio-nav-item <?= $current_page === 'videos.php' && ($_GET['filter'] ?? '') !== 'pending' ? 'active' : '' ?>">
          <span>📹 Videos</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/reels.php" class="studio-nav-item <?= $current_page === 'reels.php' ? 'active' : '' ?>">
          <span>📱 Reels</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/analytics.php" class="studio-nav-item <?= $current_page === 'analytics.php' ? 'active' : '' ?>">
          <span>📈 Analytics</span>
        </a>

        <!-- Ads Dropdown -->
        <div class="studio-nav-group-ads" style="margin-bottom: 6px;">
          <a href="#" class="studio-nav-item <?= ($current_page === 'ads.php' || $current_page === 'ad_placements.php') ? 'active' : '' ?>" id="ads-menu-toggle" style="margin-bottom: 2px; display: flex; justify-content: space-between; align-items: center;">
            <span>📺 Ads</span>
            <span class="ads-arrow" style="font-size: 0.65rem; transition: transform 0.25s; transform: <?= ($current_page === 'ads.php' || $current_page === 'ad_placements.php') ? 'rotate(90deg)' : 'rotate(0deg)' ?>;">▶</span>
          </a>
          <div id="ads-submenu" style="margin-left: 20px; display: <?= ($current_page === 'ads.php' || $current_page === 'ad_placements.php') ? 'flex' : 'none' ?>; flex-direction: column; gap: 2px; border-left: 1px solid var(--border); padding-left: 6px; margin-top: 2px;">
            <a href="<?= BASE_URL ?>/admin/ads.php" class="studio-nav-item <?= $current_page === 'ads.php' ? 'active' : '' ?>" style="margin: 0; padding: 4px 10px; font-size: 0.8rem; height: auto;">
              <span>• All Ads</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/ad_placements.php" class="studio-nav-item <?= $current_page === 'ad_placements.php' ? 'active' : '' ?>" style="margin: 0; padding: 4px 10px; font-size: 0.8rem; height: auto;">
              <span>• Placement Areas</span>
            </a>
          </div>
        </div>

        <!-- Users Link -->
        <a href="<?= BASE_URL ?>/admin/users.php" class="studio-nav-item <?= $current_page === 'users.php' ? 'active' : '' ?>" style="margin-bottom: 6px;">
          <span>👥 Users</span>
        </a>

        <!-- Categories Link -->
        <a href="<?= BASE_URL ?>/admin/categories.php" class="studio-nav-item <?= $current_page === 'categories.php' ? 'active' : '' ?>" style="margin-bottom: 6px;">
          <span>📁 Categories</span>
        </a>

        <!-- Creator Studio Link -->
        <a href="<?= BASE_URL ?>/creator/" class="studio-nav-item" style="margin-bottom: 6px;">
          <span>🎨 Creator Studio</span>
        </a>


        <!-- Library Dropdown (Saved Videos, Subscriptions) -->
        <div class="studio-nav-group-library" style="margin-bottom: 6px;">
          <a href="#" class="studio-nav-item <?= ($current_page === 'dashboard.php' && ($current_tab === 'saved' || $current_tab === 'subscriptions')) ? 'active' : '' ?>" id="library-menu-toggle" style="margin-bottom: 2px; display: flex; justify-content: space-between; align-items: center;">
            <span>📥 Library</span>
            <span class="library-arrow" style="font-size: 0.65rem; transition: transform 0.25s; transform: <?= ($current_page === 'dashboard.php' && ($current_tab === 'saved' || $current_tab === 'subscriptions')) ? 'rotate(90deg)' : 'rotate(0deg)' ?>;">▶</span>
          </a>
          <div id="library-submenu" style="margin-left: 20px; display: <?= ($current_page === 'dashboard.php' && ($current_tab === 'saved' || $current_tab === 'subscriptions')) ? 'flex' : 'none' ?>; flex-direction: column; gap: 2px; border-left: 1px solid var(--border); padding-left: 6px; margin-top: 2px;">
            <a href="<?= BASE_URL ?>/dashboard.php?tab=saved" class="studio-nav-item <?= $current_page === 'dashboard.php' && $current_tab === 'saved' ? 'active' : '' ?>" style="margin: 0; padding: 4px 10px; font-size: 0.8rem; height: auto;">
              <span>• Saved Videos</span>
            </a>
            <a href="<?= BASE_URL ?>/dashboard.php?tab=subscriptions" class="studio-nav-item <?= $current_page === 'dashboard.php' && $current_tab === 'subscriptions' ? 'active' : '' ?>" style="margin: 0; padding: 4px 10px; font-size: 0.8rem; height: auto;">
              <span>• Subscriptions</span>
            </a>
          </div>
        </div>

        <a href="<?= BASE_URL ?>/profile.php" class="studio-nav-item <?= $current_page === 'profile.php' ? 'active' : '' ?>">
          <span>👤 Profile</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/settings.php" class="studio-nav-item <?= $current_page === 'settings.php' && str_contains($_SERVER['PHP_SELF'], '/admin/') ? 'active' : '' ?>">
          <span>⚙️ Settings</span>
        </a>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
          const dropdowns = [
            { toggleId: 'ads-menu-toggle', subId: 'ads-submenu', arrowClass: 'ads-arrow' },
            { toggleId: 'users-menu-toggle', subId: 'users-submenu', arrowClass: 'users-arrow' },
            { toggleId: 'finances-menu-toggle', subId: 'finances-submenu', arrowClass: 'finances-arrow' },
            { toggleId: 'content-menu-toggle', subId: 'content-submenu', arrowClass: 'content-arrow' },
            { toggleId: 'system-menu-toggle', subId: 'system-submenu', arrowClass: 'system-arrow' },
            { toggleId: 'library-menu-toggle', subId: 'library-submenu', arrowClass: 'library-arrow' }
          ];
          
          dropdowns.forEach(function(item) {
            const toggle = document.getElementById(item.toggleId);
            const sub = document.getElementById(item.subId);
            if (toggle && sub) {
              toggle.addEventListener('click', function(e) {
                e.preventDefault();
                const arrow = this.querySelector('.' + item.arrowClass);
                if (sub.style.display === 'none' || sub.style.display === '') {
                  sub.style.display = 'flex';
                  if (arrow) arrow.style.transform = 'rotate(90deg)';
                } else {
                  sub.style.display = 'none';
                  if (arrow) arrow.style.transform = 'rotate(0deg)';
                }
              });
            }
          });
        });
        </script>

      <?php elseif ($db_role === 'creator'): ?>
        <!-- Creator Menu -->
        <a href="<?= BASE_URL ?>/creator/index.php" class="studio-nav-item <?= $current_page === 'index.php' && str_contains($_SERVER['PHP_SELF'], '/creator/') ? 'active' : '' ?>">
          <span>🏠 Dashboard</span>
        </a>
        <a href="<?= BASE_URL ?>/creator/analytics.php" class="studio-nav-item <?= $current_page === 'analytics.php' ? 'active' : '' ?>">
          <span>📈 Analytics</span>
        </a>
        <a href="<?= BASE_URL ?>/creator/upload.php?mode=video" class="studio-nav-item <?= $current_page === 'upload.php' && ($_GET['mode'] ?? '') !== 'reel' ? 'active' : '' ?>">
          <span>⬆️ Upload Video</span>
        </a>
        <a href="<?= BASE_URL ?>/creator/upload.php?mode=reel" class="studio-nav-item <?= $current_page === 'upload.php' && ($_GET['mode'] ?? '') === 'reel' ? 'active' : '' ?>">
          <span>🎥 Upload Reel</span>
        </a>
        <a href="<?= BASE_URL ?>/creator/videos.php" class="studio-nav-item <?= $current_page === 'videos.php' || $current_page === 'edit.php' ? 'active' : '' ?>">
          <span>🎬 My Videos</span>
        </a>
        <a href="<?= BASE_URL ?>/creator/reels.php" class="studio-nav-item <?= $current_page === 'reels.php' || $current_page === 'edit_reel.php' ? 'active' : '' ?>">
          <span>📱 My Reels</span>
        </a>
        <a href="<?= BASE_URL ?>/dashboard.php?tab=saved" class="studio-nav-item <?= $current_page === 'dashboard.php' && ($_GET['tab'] ?? '') === 'saved' ? 'active' : '' ?>">
          <span>📥 Saved Videos</span>
        </a>
        <a href="<?= BASE_URL ?>/dashboard.php?tab=subscriptions" class="studio-nav-item <?= $current_page === 'dashboard.php' && ($_GET['tab'] ?? '') === 'subscriptions' ? 'active' : '' ?>">
          <span>🔔 Subscribed Channels</span>
        </a>
        <a href="<?= BASE_URL ?>/profile.php" class="studio-nav-item <?= $current_page === 'profile.php' ? 'active' : '' ?>">
          <span>👤 Profile</span>
        </a>
        <a href="<?= BASE_URL ?>/channel.php?id=<?= auth_user()['id'] ?>" class="studio-nav-item <?= $current_page === 'channel.php' && ($_GET['id'] ?? 0) == auth_user()['id'] ? 'active' : '' ?>">
          <span>📺 My Channel</span>
        </a>
        <a href="<?= BASE_URL ?>/settings.php" class="studio-nav-item <?= $current_page === 'settings.php' && !str_contains($_SERVER['PHP_SELF'], '/admin/') ? 'active' : '' ?>">
          <span>⚙️ Settings</span>
        </a>

      <?php else: ?>
        <!-- Viewer Menu -->
        <a href="<?= BASE_URL ?>/dashboard.php" class="studio-nav-item <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
          <span>🏠 Dashboard</span>
        </a>
        <a href="<?= BASE_URL ?>/dashboard.php?tab=saved" class="studio-nav-item <?= $current_page === 'dashboard.php' && ($_GET['tab'] ?? '') === 'saved' ? 'active' : '' ?>">
          <span>📥 Saved Videos</span>
        </a>
        <a href="<?= BASE_URL ?>/dashboard.php?tab=subscriptions" class="studio-nav-item <?= $current_page === 'dashboard.php' && ($_GET['tab'] ?? '') === 'subscriptions' ? 'active' : '' ?>">
          <span>🔔 Subscribed Channels</span>
        </a>
        <a href="<?= BASE_URL ?>/profile.php" class="studio-nav-item <?= $current_page === 'profile.php' ? 'active' : '' ?>">
          <span>👤 Profile</span>
        </a>
        <a href="<?= BASE_URL ?>/settings.php" class="studio-nav-item <?= $current_page === 'settings.php' && !str_contains($_SERVER['PHP_SELF'], '/admin/') ? 'active' : '' ?>">
          <span>⚙️ Settings</span>
        </a>
      <?php endif; ?>
      
      <hr style="border:0; border-top:1px solid var(--border); margin:12px 8px">
      <a href="<?= BASE_URL ?>/" class="studio-nav-item">
        <span>🏠 View Site</span>
      </a>
      <a href="<?= BASE_URL ?>/auth/logout.php" class="studio-nav-item logout-link" style="color:var(--red); margin-top:auto">
        <span>↵ Logout</span>
      </a>
    </nav>
  </aside>
  
  <div class="sidebar-backdrop" id="dashboard-sidebar-backdrop"></div>
  
  <!-- Right Viewport -->
  <div class="dashboard-main-viewport">
    
    <!-- Sticky Page Header / Action Bar -->
    <section class="sticky-action-bar">
      <div style="display:flex; align-items:center; gap:12px">
        <!-- Sidebar toggle (collapses on desktop, overlay on mobile) -->
        <button class="btn-icon" id="dashboard-sidebar-toggle" aria-label="Toggle Sidebar"
                style="background:none; border:none; color:var(--text); cursor:pointer; display:flex; align-items:center; justify-content:center; padding:0; margin-right:4px">
          <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <h2 id="page-title-label" style="font-size:1.15rem; font-weight:800; margin:0"><?= e($meta_title ?? 'Dashboard') ?></h2>
      </div>

      <div style="display:flex; align-items:center; gap:12px">
        <div id="page-actions-container" class="flex gap-2">
          <!-- Action buttons will be loaded dynamically or initially -->
          <?php if (isset($header_actions)) echo $header_actions; ?>
        </div>

        <!-- Right controls (Theme + User Dropdown) -->
        <div style="display:flex; align-items:center; gap:12px; border-left:1px solid var(--border); padding-left:12px; margin-left:4px">
          <!-- Theme Toggle -->
          <button class="btn btn-outline btn-sm btn-icon" id="dashboard-theme-toggle"
                  aria-label="Change theme" title="Change theme"
                  style="width:30px; height:30px; border-radius:50%; padding:0; display:flex; align-items:center; justify-content:center; background:var(--bg3); border:1px solid var(--border)">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
          </button>

          <!-- User avatar dropdown -->
          <div class="dropdown" id="dashboard-user-dropdown" style="position:relative">
            <button class="flex" id="dashboard-user-dropdown-btn" aria-expanded="false" aria-haspopup="true"
                    style="align-items:center; cursor:pointer; background:none; border:none; padding:0" aria-label="User Menu">
              <img src="<?= avatar_url(auth_user()['avatar'] ?? '') ?>" alt="Avatar"
                   class="avatar avatar-sm" width="30" height="30"
                   style="border:2px solid var(--accent); border-radius:50%; display:block">
            </button>
            <div class="dropdown-menu" id="dashboard-user-dropdown-menu" role="menu"
                 style="position:absolute; right:0; top:38px; min-width:200px;
                        background:var(--bg2); border:1px solid var(--border);
                        border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,.18); z-index: 1000; padding:8px 0">
              <div style="padding:10px 16px; border-bottom:1px solid var(--border); margin-bottom:6px">
                <div style="font-weight:700; font-size:.9rem; color:var(--text); overflow:hidden; white-space:nowrap; text-overflow:ellipsis"><?= e(auth_user()['username'] ?? '') ?></div>
                <div style="font-size:.78rem; color:var(--text2); overflow:hidden; white-space:nowrap; text-overflow:ellipsis"><?= e(auth_user()['email'] ?? '') ?></div>
              </div>

              <?php if (is_admin()): ?>
                <a href="<?= BASE_URL ?>/admin/" class="dropdown-item" role="menuitem">
                  <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                  <span>Admin Panel</span>
                </a>
              <?php endif; ?>

              <?php if (is_creator()): ?>
                <a href="<?= BASE_URL ?>/creator/" class="dropdown-item" role="menuitem">
                  <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                  <span>Dashboard</span>
                </a>
                <a href="<?= BASE_URL ?>/channel.php?id=<?= auth_user()['id'] ?>" class="dropdown-item" role="menuitem">
                  <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                  <span>My Channel</span>
                </a>
                <a href="<?= BASE_URL ?>/creator/upload.php?mode=video" class="dropdown-item" role="menuitem">
                  <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                  <span>Upload Video</span>
                </a>
                <a href="<?= BASE_URL ?>/creator/upload.php?mode=reel" class="dropdown-item" role="menuitem">
                  <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><rect x="2" y="2" width="20" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12" y2="6"/><line x1="6" y1="12" x2="18" y2="12"/></svg>
                  <span>Upload Reel</span>
                </a>
                <a href="<?= BASE_URL ?>/creator/videos.php" class="dropdown-item" role="menuitem">
                  <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                  <span>My Videos</span>
                </a>
                <a href="<?= BASE_URL ?>/creator/reels.php" class="dropdown-item" role="menuitem">
                  <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><rect x="2" y="2" width="20" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12" y2="6"/><line x1="6" y1="12" x2="18" y2="12"/></svg>
                  <span>My Reels</span>
                </a>
              <?php endif; ?>

              <?php if (!is_admin() && !is_creator()): ?>
                <a href="<?= BASE_URL ?>/dashboard.php" class="dropdown-item" role="menuitem">
                  <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                  <span>Dashboard</span>
                </a>
              <?php endif; ?>

              <a href="<?= BASE_URL ?>/profile.php" class="dropdown-item" role="menuitem">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                <span>Edit Profile</span>
              </a>
              <a href="<?= BASE_URL ?>/settings.php" class="dropdown-item" role="menuitem">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                <span>Settings</span>
              </a>

              <div class="dropdown-divider"></div>

              <a href="<?= BASE_URL ?>/auth/logout.php" class="dropdown-item" role="menuitem" style="color:var(--red)">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                <span>Logout</span>
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>
    
    <!-- AJAX Content Area -->
    <div class="dashboard-content-scroll" id="dashboard-ajax-content">
<?php else: ?>
<?php if (!isset($is_reels) || !$is_reels): ?>

<!-- ── Navbar ── -->
<nav class="navbar" id="main-navbar" role="navigation" aria-label="Main navigation">
  <div class="navbar-inner">

    <!-- LEFT: sidebar toggle (always visible) -->
    <button class="btn-icon" id="mobile-sidebar-toggle" aria-label="Toggle Navigation Menu">
      <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>

    <!-- CENTER: logo -->
    <?= render_site_logo('nav') ?>

    <!-- DESKTOP: search bar (hidden on mobile via CSS) -->
    <form class="search-bar navbar-search-desktop" action="<?= BASE_URL ?>/search.php" method="GET" role="search" id="navbar-search-form">
      <input type="search" name="q" placeholder="Search videos…"
             value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off"
             aria-label="Search videos" id="main-search">
      <button type="submit" aria-label="Search">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      </button>
    </form>

    <!-- DESKTOP: Nav Links -->
    <div class="header-nav-links desktop-only" style="display:flex; align-items:center; gap:8px; margin-left:16px">
      <a href="<?= BASE_URL ?>/" class="btn <?= $is_home ? 'btn-primary' : 'btn-outline' ?> btn-sm" style="border-radius:18px; padding:6px 16px; font-weight:600; font-size:.82rem">Home</a>
      <a href="<?= BASE_URL ?>/reels.php" class="btn <?= $is_reels ? 'btn-primary' : 'btn-outline' ?> btn-sm" style="border-radius:18px; padding:6px 16px; font-weight:600; font-size:.82rem">Reels</a>
    </div>

    <!-- RIGHT: mobile user/login icon -->
    <!-- RIGHT: mobile user/login icon and dropdown (like desktop mode) -->
    <div class="navbar-end">


      <!-- Mobile search toggle -->
      <button class="btn btn-outline btn-sm btn-icon" id="search-toggle-mobile" aria-label="Toggle Search" title="Toggle Search" style="width:34px;height:34px;border-radius:50%;padding:0;display:inline-flex;align-items:center;justify-content:center;color:var(--text2);background:var(--bg3)">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      </button>

      <!-- Mobile Reels toggle -->
      <a href="<?= BASE_URL ?>/reels.php" class="btn btn-outline btn-sm btn-icon reels-mobile-btn" id="reels-toggle-mobile" aria-label="Open Reels" title="Open Reels" style="width:34px;height:34px;border-radius:50%;padding:0;display:inline-flex;align-items:center;justify-content:center;color:var(--text2);background:var(--bg3);margin-left:6px">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <rect width="18" height="18" x="3" y="3" rx="4" />
          <path stroke-linecap="round" stroke-linejoin="round" d="m10 9 5 3-5 3V9Z" fill="currentColor"/>
          <path d="M3 7h18M3 17h18M7 3v4M17 3v4M7 17v5M17 17v5"/>
        </svg>
      </a>

      <!-- Mobile theme toggle -->
      <button class="btn btn-outline btn-sm btn-icon theme-toggle-btn" id="theme-toggle-mobile" aria-label="Change theme" title="Change theme" style="width:34px;height:34px;border-radius:50%;padding:0;display:inline-flex;align-items:center;justify-content:center;color:var(--text2);background:var(--bg3)">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
      </button>

      <?php if (is_logged_in() && isset($user)): ?>
        <div class="dropdown" id="user-profile-dropdown-mobile" style="display:inline-block">
          <button class="flex" id="user-profile-dropdown-btn-mobile" aria-expanded="false" aria-haspopup="true" style="align-items:center; margin-left:4px; cursor:pointer; background:none; border:none; padding:0" aria-label="User Menu">
            <img src="<?= avatar_url($user['avatar']) ?>" alt="<?= e($user['username']) ?>" class="avatar avatar-sm" width="32" height="32" style="border:2px solid var(--accent)">
          </button>
          <div class="dropdown-menu" id="user-profile-dropdown-menu-mobile" role="menu" style="min-width:200px; padding:8px 0; right:0; left:auto; background-color:var(--bg2)">
            <div style="padding:10px 16px; border-bottom:1px solid var(--border); margin-bottom:6px">
              <div style="font-weight:700; font-size:.9rem; color:var(--text); text-overflow:ellipsis; overflow:hidden; white-space:nowrap"><?= e($user['username']) ?></div>
              <div style="font-size:.78rem; color:var(--text2); text-overflow:ellipsis; overflow:hidden; white-space:nowrap"><?= e($user['email']) ?></div>
            </div>
            
            <?php if (is_admin()): ?>
              <a href="<?= BASE_URL ?>/admin/" class="dropdown-item" role="menuitem">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span>Admin Panel</span>
              </a>
            <?php endif; ?>
            
            <?php if (is_creator()): ?>
              <a href="<?= BASE_URL ?>/creator/" class="dropdown-item" role="menuitem">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                <span>Dashboard</span>
              </a>
              <a href="<?= BASE_URL ?>/channel.php?id=<?= $user['id'] ?>" class="dropdown-item" role="menuitem">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span>My Channel</span>
              </a>
              <a href="<?= BASE_URL ?>/creator/upload.php?mode=video" class="dropdown-item" role="menuitem">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <span>Upload Video</span>
              </a>
              <a href="<?= BASE_URL ?>/creator/upload.php?mode=reel" class="dropdown-item" role="menuitem">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><rect x="2" y="2" width="20" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12" y2="6"/><line x1="6" y1="12" x2="18" y2="12"/></svg>
                <span>Upload Reel</span>
              </a>
              <a href="<?= BASE_URL ?>/creator/videos.php" class="dropdown-item" role="menuitem">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                <span>My Videos</span>
              </a>
              <a href="<?= BASE_URL ?>/creator/reels.php" class="dropdown-item" role="menuitem">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><rect x="2" y="2" width="20" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12" y2="6"/><line x1="6" y1="12" x2="18" y2="12"/></svg>
                <span>My Reels</span>
              </a>
            <?php endif; ?>
            
            <?php if (!is_admin() && !is_creator()): ?>
              <a href="<?= BASE_URL ?>/dashboard.php" class="dropdown-item" role="menuitem">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                <span>Dashboard</span>
              </a>
            <?php endif; ?>
            
            <a href="<?= BASE_URL ?>/profile.php" class="dropdown-item" role="menuitem">
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              <span>Edit Profile</span>
            </a>
            <a href="<?= BASE_URL ?>/settings.php" class="dropdown-item" role="menuitem">
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
              <span>Settings</span>
            </a>
            
            <div class="dropdown-divider"></div>
            
            <a href="<?= BASE_URL ?>/auth/logout.php" class="dropdown-item" role="menuitem" style="color:var(--red)">
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
              <span>Logout</span>
            </a>
          </div>
        </div>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/auth/login.php" aria-label="Sign In" title="Sign In" style="display:flex;width:34px;height:34px;border-radius:50%;border:1.5px solid var(--border);align-items:center;justify-content:center;color:var(--text2);flex-shrink:0;background:var(--bg3)">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </a>
      <?php endif; ?>
    </div>

    <div class="nav-actions">
      <!-- Categories dropdown selector (Desktop only) -->
      <div class="dropdown" id="nav-category-dropdown">
        <button class="btn btn-outline btn-sm flex gap-2" id="nav-category-dropdown-btn" aria-expanded="false" aria-haspopup="true">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
          <span id="nav-selected-category-label">All Categories</span>
          <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
        </button>
        <div class="dropdown-menu" id="nav-category-dropdown-menu" role="menu" style="min-width:220px;max-height:300px;overflow-y:auto;padding:4px 0;background-color:var(--bg2)">
          <a href="#" class="dropdown-item category-select-item" data-cat-id="0" data-cat-name="All Categories" role="menuitem" style="padding:6px 10px; display:flex; align-items:center; gap:6px; font-weight:600; font-size:.75rem">
            <span class="flex-shrink-0" style="width:18px; height:18px; border-radius:50%; background:var(--accent); display:flex; align-items:center; justify-content:center; font-size:.6rem; font-weight:bold; color:#fff">All</span>
            <span>All Categories</span>
          </a>
          <?php foreach ($nav_cats as $nc): ?>
          <a href="#" class="dropdown-item category-select-item" data-cat-id="<?= $nc['id'] ?>" data-cat-name="<?= e($nc['name']) ?>" role="menuitem" style="padding:6px 10px; display:flex; align-items:center; gap:6px; font-size:.75rem">
            <img src="<?= category_image_url($nc['image']) ?>" alt="<?= e($nc['name']) ?>" style="width:18px; height:18px; border-radius:50%; object-fit:cover; flex-shrink:0" loading="lazy">
            <span><?= e($nc['name']) ?></span>
          </a>
          <?php endforeach; ?>
        </div>
      </div>

      <?php if (is_logged_in() && isset($user)): ?>
        <!-- Theme toggle for logged-in users -->
        <button class="btn btn-outline btn-sm btn-icon" id="theme-toggle" aria-label="Change theme" title="Change theme" style="width:34px;height:34px;border-radius:50%;padding:0;display:inline-flex;align-items:center;justify-content:center">
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
        </button>

        <!-- User avatar dropdown menu -->
        <div class="dropdown" id="user-profile-dropdown" style="display:inline-block">
          <button class="flex" id="user-profile-dropdown-btn" aria-expanded="false" aria-haspopup="true" style="align-items:center; margin-left:4px; cursor:pointer" aria-label="User Menu">
            <img src="<?= avatar_url($user['avatar']) ?>" alt="<?= e($user['username']) ?>" class="avatar avatar-sm" width="34" height="34" style="border: 2px solid var(--accent)">
          </button>
          <div class="dropdown-menu" id="user-profile-dropdown-menu" role="menu" style="min-width:200px; padding:8px 0; right:0; left:auto; background-color:var(--bg2)">
            <div style="padding:10px 16px; border-bottom:1px solid var(--border); margin-bottom:6px">
              <div style="font-weight:700; font-size:.9rem; color:var(--text); text-overflow:ellipsis; overflow:hidden; white-space:nowrap"><?= e($user['username']) ?></div>
              <div style="font-size:.78rem; color:var(--text2); text-overflow:ellipsis; overflow:hidden; white-space:nowrap"><?= e($user['email']) ?></div>
            </div>
            
            <?php if (is_admin()): ?>
              <a href="<?= BASE_URL ?>/admin/" class="dropdown-item" role="menuitem">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span>Admin Panel</span>
              </a>
            <?php endif; ?>
            
            <?php if (is_creator()): ?>
              <a href="<?= BASE_URL ?>/creator/" class="dropdown-item" role="menuitem">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                <span>Dashboard</span>
              </a>
              <a href="<?= BASE_URL ?>/channel.php?id=<?= $user['id'] ?>" class="dropdown-item" role="menuitem">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span>My Channel</span>
              </a>
              <a href="<?= BASE_URL ?>/creator/upload.php?mode=video" class="dropdown-item" role="menuitem">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <span>Upload Video</span>
              </a>
              <a href="<?= BASE_URL ?>/creator/upload.php?mode=reel" class="dropdown-item" role="menuitem">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><rect x="2" y="2" width="20" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12" y2="6"/><line x1="6" y1="12" x2="18" y2="12"/></svg>
                <span>Upload Reel</span>
              </a>
              <a href="<?= BASE_URL ?>/creator/videos.php" class="dropdown-item" role="menuitem">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                <span>My Videos</span>
              </a>
              <a href="<?= BASE_URL ?>/creator/reels.php" class="dropdown-item" role="menuitem">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><rect x="2" y="2" width="20" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12" y2="6"/><line x1="6" y1="12" x2="18" y2="12"/></svg>
                <span>My Reels</span>
              </a>
            <?php endif; ?>
            
            <?php if (!is_admin() && !is_creator()): ?>
              <a href="<?= BASE_URL ?>/dashboard.php" class="dropdown-item" role="menuitem">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                <span>Dashboard</span>
              </a>
            <?php endif; ?>
            
            <a href="<?= BASE_URL ?>/profile.php" class="dropdown-item" role="menuitem">
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              <span>Edit Profile</span>
            </a>
            <a href="<?= BASE_URL ?>/settings.php" class="dropdown-item" role="menuitem">
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
              <span>Settings</span>
            </a>
            
            <div class="dropdown-divider"></div>
            
            <a href="<?= BASE_URL ?>/auth/logout.php" class="dropdown-item" role="menuitem" style="color:var(--red)">
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
              <span>Logout</span>
            </a>
          </div>
        </div>
      <?php else: ?>
        <!-- Theme toggle for guests too -->
        <button class="btn btn-outline btn-sm btn-icon" id="theme-toggle" aria-label="Change theme" title="Change theme" style="width:34px;height:34px;border-radius:50%;padding:0;display:inline-flex;align-items:center;justify-content:center">
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
        </button>
        
        <!-- User outline icon instead of buttons -->
        <a href="<?= BASE_URL ?>/auth/login.php" class="btn-icon" aria-label="Sign In" title="Sign In" style="width:34px; height:34px; border-radius:50%; border:1px solid var(--border); display:inline-flex; align-items:center; justify-content:center; color:var(--text2)">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- ── Sub-header search bar (mobile only) ── -->
<div class="sub-header-search" id="sub-header-search" role="search" aria-label="Search">
  <form action="<?= BASE_URL ?>/search.php" method="GET" style="width:100%">
    <input type="search" name="q" placeholder="Search videos, channels, tags…"
           value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off" aria-label="Search videos" id="mobile-search">
    <button type="submit" aria-label="Search">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
    </button>
  </form>
</div>

<div class="sidebar-backdrop" id="sidebar-backdrop"></div>
<?php endif; ?>
<?php endif; ?>

<script>
// Restore saved theme IMMEDIATELY (before DOMContentLoaded) to prevent flash
const _themes=['light-white','dark-minimal'];
const _themeLabels={'light-white':'Light White','dark-minimal':'Minima'};
let _savedTheme=localStorage.getItem('fh_theme');
if(_savedTheme && !_themes.includes(_savedTheme)){
  localStorage.removeItem('fh_theme');
  _savedTheme=null;
}
if(_savedTheme){
  document.documentElement.setAttribute('data-theme',_savedTheme);
} else {
  document.documentElement.setAttribute('data-theme','<?= e($site_theme) ?>');
}

function _cycleTheme(){
  const el=document.documentElement;
  const cur=el.getAttribute('data-theme')||'dark-minimal';
  const next=_themes[(_themes.indexOf(cur)+1)%_themes.length];
  el.setAttribute('data-theme',next);
  localStorage.setItem('fh_theme',next);
}

// Category Selector (global, available before DOMContentLoaded)
async function selectCategory(catId, catName) {
  document.cookie = "fh_category=" + catId + "; path=/; max-age=31536000";
  localStorage.setItem('fh_selected_category', catId);
  localStorage.setItem('fh_selected_category_name', catName);
  
  // Sync ALL category labels (sidebar + navbar)
  document.querySelectorAll('#selected-category-label, #nav-selected-category-label').forEach(el => el.textContent = catName);
  document.querySelectorAll('.category-nav-item').forEach(el => {
    if (parseInt(el.getAttribute('data-cat-id')) === catId) {
      el.classList.add('active');
    } else {
      el.classList.remove('active');
    }
  });

  const isHome = window.location.pathname === '<?= BASE_URL ?>/' || 
                 window.location.pathname.endsWith('index.php') || 
                 window.location.pathname.split('/').pop() === '';
                 
  if (isHome) {
    const mainContent = document.getElementById('main');
    if (mainContent) {
      mainContent.style.opacity = '0.4';
      mainContent.style.transition = 'opacity 0.2s';
      try {
        const res = await fetch('<?= BASE_URL ?>/index.php?cat=' + catId);
        const htmlText = await res.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(htmlText, 'text/html');
        const newMain = doc.getElementById('main');
        if (newMain) {
          mainContent.innerHTML = newMain.innerHTML;
          document.querySelectorAll('.category-chips .chip').forEach(chip => {
            const href = chip.getAttribute('href');
            if (catId === 0 && (href === '<?= BASE_URL ?>/' || href.includes('cat=0') || !href.includes('cat='))) {
              chip.classList.add('active');
            } else if (href && href.includes('cat=' + catId)) {
              chip.classList.add('active');
            } else {
              chip.classList.remove('active');
            }
          });
          if (window.bindLoadMore) window.bindLoadMore();
          if (window.initAds) window.initAds();
        }
      } catch(e) {
        window.location.reload();
      } finally {
        mainContent.style.opacity = '1';
      }
    }
  } else {
    window.location.href = '<?= BASE_URL ?>/index.php?cat=' + catId;
  }
}
</script>
<?php
if (!empty($header_ad_html)) {
    echo $header_ad_html;
}
?>
