<?php
// Unified sidebar for both creators and viewers
$current_page = basename($_SERVER['PHP_SELF']);
$current_tab = $_GET['tab'] ?? 'profile';
$role = $sidebar_role ?? (auth_user()['role'] ?? 'viewer');
?>
<aside class="studio-sidebar">
  <div style="padding:4px 4px 20px">
    <?= render_site_logo('studio') ?>
    <div style="font-size:.7rem;color:var(--text2);margin-top:2px;padding-left:2px">
      <?= $role === 'creator' ? 'Creator Studio' : 'Viewer Dashboard' ?>
    </div>
  </div>
  <a href="<?= BASE_URL ?>/dashboard.php" class="studio-nav-item <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">&#127968; Dashboard</a>
  <a href="<?= BASE_URL ?>/analytics.php" class="studio-nav-item <?= $current_page === 'analytics.php' ? 'active' : '' ?>">&#128200; Analytics</a>
  <a href="<?= BASE_URL ?>/upload.php" class="studio-nav-item <?= $current_page === 'upload.php' ? 'active' : '' ?>">&#11014; Upload Video</a>
  <a href="<?= BASE_URL ?>/videos.php" class="studio-nav-item <?= $current_page === 'videos.php' ? 'active' : '' ?>">&#127916; My Videos</a>
  <a href="<?= BASE_URL ?>/earnings.php" class="studio-nav-item <?= $current_page === 'earnings.php' ? 'active' : '' ?>">&#128176; Earnings</a>
  <a href="<?= BASE_URL ?>/withdrawal.php" class="studio-nav-item <?= $current_page === 'withdrawal.php' ? 'active' : '' ?>">&#128179; Withdrawal</a>
  <a href="<?= BASE_URL ?>/profile.php?tab=profile" class="studio-nav-item <?= ($current_page === 'profile.php' && $current_tab === 'profile') ? 'active' : '' ?>">&#128100; Profile</a>
  <a href="<?= BASE_URL ?>/profile.php?tab=settings" class="studio-nav-item <?= ($current_page === 'profile.php' && $current_tab === 'settings') ? 'active' : '' ?>">&#9881; Settings</a>
  <hr style="border-color:var(--border);margin:12px 0">
  <a href="<?= BASE_URL ?>/" class="studio-nav-item">&#127968; View Site</a>
  <a href="<?= BASE_URL ?>/auth/logout.php" class="studio-nav-item" style="color:var(--red);margin-top:auto">&#x21B5; Logout</a>
</aside>
