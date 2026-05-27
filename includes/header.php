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

$nav_cat_limit = (int)setting('dropdown_cat_limit', 8);
$is_search_page = basename($_SERVER['PHP_SELF'] ?? '') === 'search.php';

$current_page = basename($_SERVER['PHP_SELF'] ?? '');
$current_q = $_GET['q'] ?? '';
$current_sort = $_GET['sort'] ?? '';
$is_home = ($current_page === 'index.php' && !isset($_GET['cat']));
$is_trending = ($current_page === 'search.php' && ($current_q === 'trending' || $current_sort === 'views')) || ($current_page === 'index.php' && isset($_GET['sort']) && $_GET['sort'] === 'views');
$is_latest = ($current_page === 'search.php' && $current_sort === 'latest') || ($current_page === 'index.php' && isset($_GET['sort']) && $_GET['sort'] === 'latest');
$is_categories = ($current_page === 'categories.php');
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
<meta property="og:title"       content="<?= e($meta_title) ?>">
<meta property="og:description" content="<?= e($meta_desc) ?>">
<meta property="og:image"       content="<?= e($meta_image) ?>">
<meta property="og:type"        content="website">
<meta name="twitter:card"       content="summary_large_image">
<meta name="base-url" content="<?= e(BASE_URL) ?>">
<link rel="canonical" href="<?= e(BASE_URL . $_SERVER['REQUEST_URI']) ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css?v=<?= filemtime(__DIR__ . '/../assets/css/main.css') ?>">
<link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/img/logo.svg">
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
</style>
</head>

<?php
$is_dashboard_page = (
    str_contains($_SERVER['PHP_SELF'], '/admin/') ||
    str_contains($_SERVER['PHP_SELF'], '/creator/') ||
    basename($_SERVER['PHP_SELF']) === 'dashboard.php' ||
    basename($_SERVER['PHP_SELF']) === 'withdrawal.php' ||
    basename($_SERVER['PHP_SELF']) === 'settings.php' ||
    basename($_SERVER['PHP_SELF']) === 'profile.php'
);
$sidebar_path = get_sidebar_path();
?>

<body class="<?= $is_dashboard_page ? 'dashboard-page' : 'public-page' ?>">

<?php if ($is_dashboard_page): ?>
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
        <a href="<?= BASE_URL ?>/admin/videos.php?filter=pending" class="studio-nav-item <?= $current_page === 'videos.php' ? 'active' : '' ?>">
          <span>📹 Videos</span>
          <?php if (($admin_pending_videos = db_count('videos', "status='pending'")) > 0): ?>
            <span class="admin-nav-badge" style="background:var(--yellow); color:#1a1000; font-size:.65rem; padding:2px 6px; border-radius:10px; margin-left:auto"><?= $admin_pending_videos ?></span>
          <?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>/admin/users.php" class="studio-nav-item <?= $current_page === 'users.php' && !isset($_GET['role']) ? 'active' : '' ?>">
          <span>👥 Users</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/creators.php?filter=pending" class="studio-nav-item <?= $current_page === 'creators.php' ? 'active' : '' ?>">
          <span>🎬 Creators</span>
          <?php if (($admin_pending_creators = db_count('users', "role='creator' AND status='pending'")) > 0): ?>
            <span class="admin-nav-badge" style="background:var(--yellow); color:#1a1000; font-size:.65rem; padding:2px 6px; border-radius:10px; margin-left:auto"><?= $admin_pending_creators ?></span>
          <?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>/admin/users.php?role=viewer" class="studio-nav-item <?= $current_page === 'users.php' && ($_GET['role'] ?? '') === 'viewer' ? 'active' : '' ?>">
          <span>👁️ Viewers</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/earnings.php" class="studio-nav-item <?= $current_page === 'earnings.php' ? 'active' : '' ?>">
          <span>💰 Earnings</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/withdrawals.php?status=pending" class="studio-nav-item <?= $current_page === 'withdrawals.php' ? 'active' : '' ?>">
          <span>💳 Withdrawals</span>
          <?php if (($admin_pending_withdrawals = (fh_table_exists('withdrawal_requests') ? db_count('withdrawal_requests', "status='pending'") : 0)) > 0): ?>
            <span class="admin-nav-badge" style="background:var(--yellow); color:#1a1000; font-size:.65rem; padding:2px 6px; border-radius:10px; margin-left:auto"><?= $admin_pending_withdrawals ?></span>
          <?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>/admin/analytics.php" class="studio-nav-item <?= $current_page === 'analytics.php' ? 'active' : '' ?>">
          <span>📈 Analytics</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/categories.php" class="studio-nav-item <?= $current_page === 'categories.php' ? 'active' : '' ?>">
          <span>📁 Categories</span>
        </a>
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
        <script>
        document.addEventListener('DOMContentLoaded', function() {
          const toggle = document.getElementById('ads-menu-toggle');
          const sub = document.getElementById('ads-submenu');
          if (toggle && sub) {
            toggle.addEventListener('click', function(e) {
              e.preventDefault();
              const arrow = this.querySelector('.ads-arrow');
              if (sub.style.display === 'none' || sub.style.display === '') {
                sub.style.display = 'flex';
                arrow.style.transform = 'rotate(90deg)';
              } else {
                sub.style.display = 'none';
                arrow.style.transform = 'rotate(0deg)';
              }
            });
          }
        });
        </script>
        <a href="<?= BASE_URL ?>/admin/seo.php" class="studio-nav-item <?= $current_page === 'seo.php' ? 'active' : '' ?>">
          <span>🔍 SEO</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/settings.php" class="studio-nav-item <?= $current_page === 'settings.php' && str_contains($_SERVER['PHP_SELF'], '/admin/') ? 'active' : '' ?>">
          <span>⚙️ Settings</span>
        </a>
        <a href="<?= BASE_URL ?>/profile.php" class="studio-nav-item <?= $current_page === 'profile.php' ? 'active' : '' ?>">
          <span>👤 Profile</span>
        </a>

      <?php elseif ($db_role === 'creator'): ?>
        <!-- Creator Menu -->
        <a href="<?= BASE_URL ?>/creator/index.php" class="studio-nav-item <?= $current_page === 'index.php' && str_contains($_SERVER['PHP_SELF'], '/creator/') ? 'active' : '' ?>">
          <span>🏠 Dashboard</span>
        </a>
        <a href="<?= BASE_URL ?>/creator/analytics.php" class="studio-nav-item <?= $current_page === 'analytics.php' ? 'active' : '' ?>">
          <span>📈 Analytics</span>
        </a>
        <a href="<?= BASE_URL ?>/creator/upload.php" class="studio-nav-item <?= $current_page === 'upload.php' ? 'active' : '' ?>">
          <span>⬆️ Upload Video</span>
        </a>
        <a href="<?= BASE_URL ?>/creator/videos.php" class="studio-nav-item <?= $current_page === 'videos.php' || $current_page === 'edit.php' ? 'active' : '' ?>">
          <span>🎬 My Videos</span>
        </a>
        <a href="<?= BASE_URL ?>/dashboard.php" class="studio-nav-item <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
          <span>💰 Earnings</span>
        </a>
        <a href="<?= BASE_URL ?>/withdrawal.php" class="studio-nav-item <?= $current_page === 'withdrawal.php' ? 'active' : '' ?>">
          <span>💳 Withdrawal</span>
        </a>
        <a href="<?= BASE_URL ?>/profile.php" class="studio-nav-item <?= $current_page === 'profile.php' ? 'active' : '' ?>">
          <span>👤 Profile</span>
        </a>
        <a href="<?= BASE_URL ?>/settings.php" class="studio-nav-item <?= $current_page === 'settings.php' && !str_contains($_SERVER['PHP_SELF'], '/admin/') ? 'active' : '' ?>">
          <span>⚙️ Settings</span>
        </a>

      <?php else: ?>
        <!-- Viewer Menu -->
        <a href="<?= BASE_URL ?>/dashboard.php" class="studio-nav-item <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
          <span>🏠 Dashboard</span>
        </a>
        <a href="<?= BASE_URL ?>/withdrawal.php" class="studio-nav-item <?= $current_page === 'withdrawal.php' ? 'active' : '' ?>">
          <span>💳 Withdrawal</span>
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
        <!-- Mobile sidebar toggle (hidden on desktop) -->
        <button class="btn-icon mobile-only" id="dashboard-sidebar-toggle" aria-label="Toggle Sidebar"
                style="background:none; border:none; color:var(--text); cursor:pointer; display:flex; align-items:center; justify-content:center; padding:0; margin-right:4px">
          <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <h2 id="page-title-label" style="font-size:1.15rem; font-weight:800; margin:0"><?= e($meta_title ?? 'Dashboard') ?></h2>
      </div>

      <div style="display:flex; align-items:center; gap:12px">
        <div id="page-actions-container" class="flex gap-2">
          <!-- Action buttons will be loaded dynamically or initially -->
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
                <a href="<?= BASE_URL ?>/dashboard.php" class="dropdown-item" role="menuitem">
                  <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                  <span>Earnings</span>
                </a>
                <a href="<?= BASE_URL ?>/channel.php?id=<?= auth_user()['id'] ?>" class="dropdown-item" role="menuitem">
                  <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                  <span>My Channel</span>
                </a>
              <?php endif; ?>

              <?php if (!is_admin() && !is_creator()): ?>
                <a href="<?= BASE_URL ?>/dashboard.php" class="dropdown-item" role="menuitem">
                  <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                  <span>Dashboard</span>
                </a>
              <?php endif; ?>

              <?php if (!is_admin()): ?>
                <a href="<?= BASE_URL ?>/referral.php" class="dropdown-item" role="menuitem">
                  <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                  <span>Refer &amp; Earn</span>
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
      <a href="<?= BASE_URL ?>/search.php?q=trending" class="btn <?= $is_trending ? 'btn-primary' : 'btn-outline' ?> btn-sm" style="border-radius:18px; padding:6px 16px; font-weight:600; font-size:.82rem">Trending</a>
      <a href="<?= BASE_URL ?>/search.php?sort=latest" class="btn <?= $is_latest ? 'btn-primary' : 'btn-outline' ?> btn-sm" style="border-radius:18px; padding:6px 16px; font-weight:600; font-size:.82rem">Latest</a>
      <a href="<?= BASE_URL ?>/categories.php" class="btn <?= $is_categories ? 'btn-primary' : 'btn-outline' ?> btn-sm" style="border-radius:18px; padding:6px 16px; font-weight:600; font-size:.82rem">Categories</a>
    </div>

    <!-- RIGHT: mobile user/login icon -->
    <!-- RIGHT: mobile user/login icon and dropdown (like desktop mode) -->
    <div class="navbar-end">
      <!-- Mobile search toggle -->
      <button class="btn btn-outline btn-sm btn-icon" id="search-toggle-mobile" aria-label="Toggle Search" title="Toggle Search" style="width:34px;height:34px;border-radius:50%;padding:0;display:inline-flex;align-items:center;justify-content:center;color:var(--text2);background:var(--bg3)">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      </button>

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
              <a href="<?= BASE_URL ?>/dashboard.php" class="dropdown-item" role="menuitem">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                <span>Earnings</span>
              </a>
              <a href="<?= BASE_URL ?>/channel.php?id=<?= $user['id'] ?>" class="dropdown-item" role="menuitem">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span>My Channel</span>
              </a>
            <?php endif; ?>
            
            <?php if (!is_admin() && !is_creator()): ?>
              <a href="<?= BASE_URL ?>/dashboard.php" class="dropdown-item" role="menuitem">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                <span>Dashboard</span>
              </a>
            <?php endif; ?>
            
            <?php if (!is_admin()): ?>
              <a href="<?= BASE_URL ?>/referral.php" class="dropdown-item" role="menuitem">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                <span>Refer &amp; Earn</span>
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
              <a href="<?= BASE_URL ?>/dashboard.php" class="dropdown-item" role="menuitem">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                <span>Earnings</span>
              </a>
              <a href="<?= BASE_URL ?>/channel.php?id=<?= $user['id'] ?>" class="dropdown-item" role="menuitem">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span>My Channel</span>
              </a>
            <?php endif; ?>
            
            <?php if (!is_admin() && !is_creator()): ?>
              <a href="<?= BASE_URL ?>/dashboard.php" class="dropdown-item" role="menuitem">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                <span>Dashboard</span>
              </a>
            <?php endif; ?>
            
            <?php if (!is_admin()): ?>
              <a href="<?= BASE_URL ?>/referral.php" class="dropdown-item" role="menuitem">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                <span>Refer &amp; Earn</span>
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

<script>
// Restore saved theme IMMEDIATELY (before DOMContentLoaded) to prevent flash
const _themes=['light-white','dark-minimal','gray','light-blue','pink','red-black','green','light-green'];
const _themeLabels={'light-white':'Light White','dark-minimal':'Minima','gray':'Gray','light-blue':'Light Blue','pink':'Pink','red-black':'Red Black','green':'Green','light-green':'Light Green'};
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
