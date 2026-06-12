<?php
// Admin shared head partial
$site_theme = setting('active_theme', 'dark-minimal');
$primary    = setting('primary_color', '#6366f1');
$admin_page = basename($_SERVER['PHP_SELF'] ?? '');

// Sidebar alert counts
$admin_pending_videos = db_count('videos', "status='pending'");
$admin_pending_creators = db_count('users', "role='creator' AND status='pending'");

// Set layout variables for the main shell
$is_dashboard_page = true;
$sidebar_role = 'admin';

// Require the main header
require_once __DIR__ . '/../../includes/header.php';
?>
<style>
/* Two-column admin pages (categories, ads, etc.) */
.admin-split-layout{
  display:grid;
  grid-template-columns:1fr 360px;
  gap:24px;
  align-items:start;
}
.admin-split-sidebar{min-width:0}
.admin-split-layout--wide{grid-template-columns:1fr 400px}

.admin-page-header{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
  flex-wrap:wrap;
  margin-bottom:20px;
}
.admin-page-header h1{
  font-size:1.2rem;
  font-weight:800;
  margin:0;
  min-width:0;
}
.admin-page-action{
  display:inline-flex;
  align-items:center;
  gap:6px;
  white-space:nowrap;
  flex-shrink:0;
}
.admin-page-action svg{flex-shrink:0}
#admin-add-panel{
  scroll-margin-top:72px;
  transition:box-shadow .25s ease;
}
#admin-add-panel.admin-add-panel--focus{
  box-shadow:0 0 0 2px var(--accent);
}

.form-row-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

@media (max-width: 768px) {
  .admin-col-hide-sm {
    display: none !important;
  }
}

@media (max-width: 576px) {
  .form-row-grid {
    grid-template-columns: 1fr;
    gap: 0;
  }
  .modal {
    width: 95%;
  }
  .modal-header, .modal-body {
    padding: 16px !important;
  }
}
</style>
<div class="container">
