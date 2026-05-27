<?php
// Affiliate — Reports Export
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['affiliate','admin']);
$site_theme = setting('active_theme', 'dark-minimal');
$primary    = setting('primary_color', '#6366f1');
$uid = auth_user()['id'];

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date']   ?? date('Y-m-d');
$format     = $_GET['format']     ?? '';

// Fetch stats in range
$clicks = db_fetchAll(
    "SELECT ac.*, v.title as video_title 
     FROM affiliate_clicks ac 
     LEFT JOIN videos v ON v.id = ac.video_id 
     WHERE ac.affiliate_id = ? AND DATE(ac.created_at) BETWEEN ? AND ? 
     ORDER BY ac.created_at DESC", 
    [$uid, $start_date, $end_date]
);

// If format is CSV, output CSV and exit
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=affiliate_report_' . $start_date . '_to_' . $end_date . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date/Time', 'Video Title', 'IP Hash', 'Device', 'Referrer', 'Converted']);
    foreach ($clicks as $c) {
        fputcsv($output, [
            $c['created_at'],
            $c['video_title'] ?: 'General Referral',
            $c['ip_hash'],
            $c['device'],
            $c['referer'],
            $c['converted'] ? 'Yes' : 'No'
        ]);
    }
    fclose($output);
    exit;
}

$meta_title = 'Traffic Reports';
?><!DOCTYPE html>
<html lang="en" data-theme="<?= e($site_theme) ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reports — Affiliate</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
<style>:root{--accent:<?= e($primary) ?>;--accent2:<?= e($primary) ?>cc}</style>
<script>const _st=localStorage.getItem('fh_theme');if(_st)document.documentElement.setAttribute('data-theme',_st);</script>
<style>
.aff-layout{display:grid;grid-template-columns:220px 1fr;min-height:100vh}
.aff-sidebar{background:var(--bg2);border-right:1px solid var(--border);padding:20px 12px}
.aff-nav-item{display:flex;align-items:center;gap:9px;padding:9px 12px;border-radius:8px;color:var(--text2);font-size:.875rem;font-weight:500;transition:all .15s;margin-bottom:2px}
.aff-nav-item:hover{background:var(--bg3);color:var(--text)}
.aff-nav-item.active{background:rgba(99,102,241,.12);color:var(--accent)}
</style>
</head>
<body>
<div class="studio-sidebar-backdrop" id="studio-sidebar-backdrop"></div>
<div class="studio-mobile-bar" style="display:none; height:48px; background:var(--bg2); border-bottom:1px solid var(--border); align-items:center; padding:0 16px; position:fixed; top:0; left:0; right:0; z-index:90">
  <button class="btn-icon" id="studio-sidebar-toggle" style="margin-right:8px; display:flex; align-items:center; justify-content:center" aria-label="Toggle Menu">
    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
  </button>
  <span style="font-weight:700; font-size:.9rem">Affiliate Panel</span>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const toggleBtn = document.getElementById('studio-sidebar-toggle');
  const sidebar = document.querySelector('.aff-sidebar');
  const backdrop = document.getElementById('studio-sidebar-backdrop');
  
  toggleBtn?.addEventListener('click', function(e) {
    e.stopPropagation();
    sidebar?.classList.toggle('open');
    backdrop?.classList.toggle('active');
  });
  
  backdrop?.addEventListener('click', function() {
    sidebar?.classList.remove('open');
    this.classList.remove('active');
  });
});
</script>
<div class="aff-layout">
  <aside class="aff-sidebar">
    <div style="padding:4px 4px 20px"><?= render_site_logo('studio') ?></div>
    <a href="<?= BASE_URL ?>/affiliate/" class="aff-nav-item">&#128202; Dashboard</a>
    <a href="<?= BASE_URL ?>/affiliate/links.php" class="aff-nav-item">&#128279; My Links</a>
    <a href="<?= BASE_URL ?>/affiliate/analytics.php" class="aff-nav-item">&#128200; Analytics</a>
    <a href="<?= BASE_URL ?>/affiliate/earnings.php" class="aff-nav-item">&#128176; Earnings</a>
    <a href="<?= BASE_URL ?>/affiliate/reports.php" class="aff-nav-item active">&#128203; Reports</a>
    <a href="<?= BASE_URL ?>/profile.php" class="aff-nav-item">&#128100; Edit Profile</a>
    <a href="<?= BASE_URL ?>/auth/logout.php" class="aff-nav-item" style="color:var(--red)">&#x21B5; Logout</a>
  </aside>

  <main style="padding:28px">
    <h1 style="font-size:1.2rem;font-weight:800;margin-bottom:8px">Traffic Reports</h1>
    <p class="text-muted text-sm" style="margin-bottom:24px">Filter, search, and export your affiliate link traffic data.</p>

    <!-- Filter Form -->
    <div class="card" style="margin-bottom:24px">
      <form method="GET" class="flex gap-3" style="flex-wrap:wrap;align-items:flex-end">
        <div class="form-group" style="margin:0;width:auto">
          <label class="form-label" style="margin-bottom:4px">Start Date</label>
          <input class="form-input" type="date" name="start_date" value="<?= e($start_date) ?>" style="width:160px">
        </div>
        <div class="form-group" style="margin:0;width:auto">
          <label class="form-label" style="margin-bottom:4px">End Date</label>
          <input class="form-input" type="date" name="end_date" value="<?= e($end_date) ?>" style="width:160px">
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <a href="?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&format=csv" class="btn btn-outline btn-sm" style="border-color:var(--green);color:var(--green)">
          &#128190; Export CSV
        </a>
      </form>
    </div>

    <!-- Data Table -->
    <div class="card">
      <h3 style="font-weight:700;margin-bottom:16px">Traffic Logs (<?= count($clicks) ?> rows)</h3>
      <?php if ($clicks): ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Date/Time</th>
              <th>Video</th>
              <th>IP Hash</th>
              <th>Device</th>
              <th>Referrer</th>
              <th>Converted</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($clicks as $c): ?>
          <tr>
            <td class="text-xs text-muted"><?= e($c['created_at']) ?></td>
            <td class="text-sm" style="font-weight:600"><?= e($c['video_title'] ?: 'General Referral') ?></td>
            <td><code style="font-size:.72rem;background:var(--bg3);padding:2px 6px;border-radius:4px"><?= e(substr($c['ip_hash'], 0, 10)) ?>...</code></td>
            <td><span class="badge badge-blue"><?= e($dv['device'] ?? $c['device']) ?></span></td>
            <td class="text-xs text-muted" style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= e($c['referer']) ?>"><?= e($c['referer'] ?: 'Direct') ?></td>
            <td><span class="badge badge-<?= $c['converted'] ? 'green' : 'gray' ?>"><?= $c['converted'] ? 'Yes' : 'No' ?></span></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <p class="text-muted text-sm">No traffic data found for this range.</p>
      <?php endif; ?>
    </div>
  </main>
</div>
</body></html>
