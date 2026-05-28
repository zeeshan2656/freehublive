<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_q = $_GET['q'] ?? '';
$current_sort = $_GET['sort'] ?? '';
$sidebar_role = $sidebar_role ?? (auth_user()['role'] ?? 'viewer');

// Check which nav-item should be active
$is_home = ($current_page === 'index.php' && !isset($_GET['cat']));
$is_trending = ($current_page === 'search.php' && ($current_q === 'trending' || $current_sort === 'views')) || ($current_page === 'index.php' && isset($_GET['sort']) && $_GET['sort'] === 'views');
$is_latest = ($current_page === 'search.php' && $current_sort === 'latest') || ($current_page === 'index.php' && isset($_GET['sort']) && $_GET['sort'] === 'latest');
$is_categories = ($current_page === 'categories.php');
?>
<!-- ── Sidebar ── -->
<aside class="sidebar" aria-label="Site navigation">
  
  <nav style="padding-top:12px">
    <?php if (is_logged_in() && !empty($is_dashboard_page)): ?>
      <!-- Dashboard navigation items (ONLY visible in dashboard context) -->
      <?php if ($sidebar_role === 'admin'): ?>
        <a href="<?= BASE_URL ?>/admin/" class="nav-item <?= $current_page === 'index.php' ? 'active' : '' ?>">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
          <span>Dashboard</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/analytics.php" class="nav-item">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          <span>Analytics</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/earnings.php" class="nav-item">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          <span>Earnings</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/withdrawals.php?status=pending" class="nav-item">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
          <span>Withdrawal</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/settings.php" class="nav-item">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
          <span>Settings</span>
        </a>
        <a href="<?= BASE_URL ?>/profile.php" class="nav-item <?= $current_page === 'profile.php' ? 'active' : '' ?>">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <span>Profile</span>
        </a>
      <?php elseif ($sidebar_role === 'creator'): ?>
        <a href="<?= BASE_URL ?>/creator/" class="nav-item <?= $current_page === 'index.php' && str_contains($_SERVER['PHP_SELF'], '/creator/') ? 'active' : '' ?>">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          <span>Dashboard</span>
        </a>
        <a href="<?= BASE_URL ?>/creator/analytics.php" class="nav-item <?= $current_page === 'analytics.php' ? 'active' : '' ?>">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          <span>Analytics</span>
        </a>
        <a href="<?= BASE_URL ?>/creator/upload.php" class="nav-item <?= $current_page === 'upload.php' ? 'active' : '' ?>">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          <span>Upload Video</span>
        </a>
        <a href="<?= BASE_URL ?>/creator/videos.php" class="nav-item <?= ($current_page === 'videos.php' || $current_page === 'edit.php') && str_contains($_SERVER['PHP_SELF'], '/creator/') ? 'active' : '' ?>">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
          <span>My Videos</span>
        </a>
        <a href="<?= BASE_URL ?>/dashboard.php" class="nav-item <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          <span>Earnings</span>
        </a>
        <a href="<?= BASE_URL ?>/withdrawal.php" class="nav-item <?= $current_page === 'withdrawal.php' ? 'active' : '' ?>">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
          <span>Withdrawal</span>
        </a>
        <a href="<?= BASE_URL ?>/profile.php" class="nav-item <?= $current_page === 'profile.php' ? 'active' : '' ?>">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <span>Profile</span>
        </a>
        <a href="<?= BASE_URL ?>/channel.php?id=<?= auth_user()['id'] ?>" class="nav-item <?= $current_page === 'channel.php' && ($_GET['id'] ?? 0) == auth_user()['id'] ? 'active' : '' ?>">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/><polygon points="10 7 15 10 10 13 10 7"/></svg>
          <span>My Channel</span>
        </a>
        <a href="<?= BASE_URL ?>/settings.php" class="nav-item <?= $current_page === 'settings.php' ? 'active' : '' ?>">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
          <span>Settings</span>
        </a>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/dashboard.php" class="nav-item <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          <span>Dashboard</span>
        </a>
        <a href="<?= BASE_URL ?>/withdrawal.php" class="nav-item <?= $current_page === 'withdrawal.php' ? 'active' : '' ?>">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
          <span>Withdrawal</span>
        </a>
        <a href="<?= BASE_URL ?>/settings.php" class="nav-item <?= $current_page === 'settings.php' ? 'active' : '' ?>">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
          <span>Settings</span>
        </a>
        <a href="<?= BASE_URL ?>/profile.php" class="nav-item <?= $current_page === 'profile.php' ? 'active' : '' ?>">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <span>Profile</span>
        </a>
      <?php endif; ?>
      
      <hr style="border-color:var(--border);margin:12px 0">
      <a href="<?= BASE_URL ?>/auth/logout.php" class="nav-item" style="color:var(--red)">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        <span>Logout</span>
      </a>
    <?php else: ?>
      <?php if (is_logged_in() && auth_user()['role'] === 'creator'): ?>
        <div class="nav-section-title">Creator</div>
        <a href="<?= BASE_URL ?>/channel.php?id=<?= auth_user()['id'] ?>" class="nav-item <?= $current_page === 'channel.php' && ($_GET['id'] ?? 0) == auth_user()['id'] ? 'active' : '' ?>">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/><polygon points="10 7 15 10 10 13 10 7"/></svg>
          <span>My Channel</span>
        </a>
        <a href="<?= BASE_URL ?>/creator/" class="nav-item">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          <span>Creator Studio</span>
        </a>
        <hr style="border-color:var(--border);margin:12px 0">
      <?php endif; ?>
      <!-- Public sidebar: ONLY categories list is rendered here -->
      <div class="nav-section-title">Categories</div>
      <div class="sidebar-categories-list" style="display:flex; flex-direction:column; gap:2px; margin-bottom:12px">
        <?php
        $sidebar_cats = db_fetchAll("SELECT id, name, slug, image FROM categories WHERE is_active=1 ORDER BY sort_order");
        $sel_cat = (int)($_GET['cat'] ?? $_COOKIE['fh_category'] ?? 0);
        ?>
        <a href="#" class="nav-item category-nav-item category-select-item <?= !$sel_cat ? 'active' : '' ?>" data-cat-id="0" data-cat-name="All Categories">
          <span class="flex-shrink-0" style="width:18px; height:18px; border-radius:50%; background:var(--accent); display:flex; align-items:center; justify-content:center; font-size:.65rem; font-weight:bold; color:#fff">All</span>
          <span>All Categories</span>
        </a>
        <?php foreach ($sidebar_cats as $sc): ?>
        <a href="#" class="nav-item category-nav-item category-select-item <?= $sel_cat === (int)$sc['id'] ? 'active' : '' ?>" data-cat-id="<?= $sc['id'] ?>" data-cat-name="<?= e($sc['name']) ?>">
          <img src="<?= category_image_url($sc['image']) ?>" alt="<?= e($sc['name']) ?>" style="width:18px; height:18px; border-radius:50%; object-fit:cover; flex-shrink:0" loading="lazy">
          <span><?= e($sc['name']) ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </nav>
</aside>
