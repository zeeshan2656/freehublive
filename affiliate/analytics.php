<?php
// Affiliate — Analytics (detailed)
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['affiliate','admin']);
$site_theme = setting('active_theme', 'dark-minimal');
$primary    = setting('primary_color', '#6366f1');
$uid = auth_user()['id'];

$range = (int)($_GET['days'] ?? 30);
$range = in_array($range,[7,14,30,90]) ? $range : 30;

$chart = [];
for ($i = $range-1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chart[] = [
        'date'   => date($range<=14?'M j':'M j', strtotime("-$i days")),
        'clicks' => db_count('affiliate_clicks','affiliate_id=? AND DATE(created_at)=?',[$uid,$d]),
        'views'  => db_count('video_views','affiliate_id=? AND DATE(created_at)=?',[$uid,$d]),
    ];
}

// Top performing videos
$top = db_fetchAll(
    "SELECT v.title,v.id,COUNT(vv.id) as ref_views
     FROM video_views vv JOIN videos v ON v.id=vv.video_id
     WHERE vv.affiliate_id=? GROUP BY v.id ORDER BY ref_views DESC LIMIT 10", [$uid]
);

// Device breakdown
$devices = db_fetchAll(
    "SELECT device,COUNT(*) as cnt FROM affiliate_clicks WHERE affiliate_id=? GROUP BY device", [$uid]
);
$total_clicks = array_sum(array_column($devices,'cnt')) ?: 1;

$maxVal = max(1, max(array_map(fn($d)=>max($d['clicks'],$d['views']), $chart)));
?><!DOCTYPE html>
<html lang="en" data-theme="<?= e($site_theme) ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Analytics — Affiliate</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
<style>:root{--accent:<?= e($primary) ?>;--accent2:<?= e($primary) ?>cc}</style>
<script>const _st=localStorage.getItem('fh_theme');if(_st)document.documentElement.setAttribute('data-theme',_st);</script>
<style>
.aff-layout{display:grid;grid-template-columns:220px 1fr;min-height:100vh}
.aff-sidebar{background:var(--bg2);border-right:1px solid var(--border);padding:20px 12px}
.aff-nav-item{display:flex;align-items:center;gap:9px;padding:9px 12px;border-radius:8px;color:var(--text2);font-size:.875rem;font-weight:500;transition:all .15s;margin-bottom:2px}
.aff-nav-item:hover{background:var(--bg3);color:var(--text)}
.aff-nav-item.active{background:rgba(99,102,241,.12);color:var(--accent)}
.donut-bar{display:flex;align-items:center;gap:12px;margin-bottom:10px}
.donut-fill{height:8px;border-radius:4px;flex-shrink:0}
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
    <a href="<?= BASE_URL ?>/affiliate/analytics.php" class="aff-nav-item active">&#128200; Analytics</a>
    <a href="<?= BASE_URL ?>/affiliate/earnings.php" class="aff-nav-item">&#128176; Earnings</a>
    <a href="<?= BASE_URL ?>/profile.php" class="aff-nav-item">&#128100; Edit Profile</a>
    <a href="<?= BASE_URL ?>/auth/logout.php" class="aff-nav-item" style="color:var(--red)">&#x21B5; Logout</a>
  </aside>

  <main style="padding:28px">
    <div class="flex" style="justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
      <h1 style="font-size:1.2rem;font-weight:800">Detailed Analytics</h1>
      <div class="flex gap-2">
        <?php foreach([7=>'7D',14=>'14D',30=>'30D',90=>'90D'] as $d=>$l): ?>
        <a href="?days=<?= $d ?>" class="btn btn-sm <?= $range===$d?'btn-primary':'btn-outline' ?>"><?= $l ?></a>
        <?php endforeach; ?>
      </div>
    </div>

    <?php $watch_stats_user_id = (int)$uid; $watch_stats_compact = true; require __DIR__ . '/../includes/partials/watch_earnings_stats.php'; ?>

    <!-- Chart -->
    <div class="card" style="margin-bottom:24px">
      <h3 style="font-weight:700;margin-bottom:16px">Clicks vs Views — Last <?= $range ?> Days</h3>
      <div style="display:flex;align-items:flex-end;gap:<?= $range>14?'3':'8' ?>px;height:160px;overflow-x:auto">
        <?php foreach($chart as $d):
          $ch = round(($d['clicks']/$maxVal)*140);
          $vh = round(($d['views']/$maxVal)*140);
        ?>
        <div style="flex:1;min-width:<?= $range>14?'12':'24' ?>px;display:flex;flex-direction:column;align-items:center;gap:2px;height:160px;justify-content:flex-end">
          <div style="display:flex;align-items:flex-end;gap:1px;height:140px">
            <div style="width:<?= $range>14?'7':'12' ?>px;height:<?= $ch ?>px;background:var(--accent);border-radius:2px 2px 0 0" title="<?= $d['clicks'] ?> clicks"></div>
            <div style="width:<?= $range>14?'7':'12' ?>px;height:<?= $vh ?>px;background:var(--green);border-radius:2px 2px 0 0"  title="<?= $d['views'] ?> views"></div>
          </div>
          <?php if($range<=30): ?><span style="font-size:.6rem;color:var(--text3);white-space:nowrap"><?= $d['date'] ?></span><?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="flex gap-4" style="margin-top:12px">
        <div class="flex gap-2"><div style="width:12px;height:12px;background:var(--accent);border-radius:2px"></div><span class="text-xs text-muted">Clicks</span></div>
        <div class="flex gap-2"><div style="width:12px;height:12px;background:var(--green);border-radius:2px"></div><span class="text-xs text-muted">Views</span></div>
      </div>
    </div>

    <div class="stat-grid-2">
      <!-- Top Videos -->
      <div class="card">
        <h3 style="font-weight:700;margin-bottom:16px">Top Videos via Your Links</h3>
        <?php if($top): ?>
        <?php foreach($top as $i=>$v): ?>
        <div class="flex gap-3" style="margin-bottom:12px;align-items:center">
          <span style="font-size:1rem;font-weight:800;color:var(--text3);width:20px;flex-shrink:0"><?= $i+1 ?></span>
          <div style="min-width:0;flex:1">
            <a href="<?= BASE_URL ?>/watch.php?v=<?= $v['id'] ?>" class="text-sm" style="font-weight:600;color:var(--text);overflow:hidden;white-space:nowrap;text-overflow:ellipsis;display:block"><?= e(truncate($v['title'],45)) ?></a>
          </div>
          <span class="badge badge-blue"><?= format_number((int)$v['ref_views']) ?> views</span>
        </div>
        <?php endforeach; ?>
        <?php else: ?><p class="text-muted text-sm">No data yet.</p><?php endif; ?>
      </div>

      <!-- Device breakdown -->
      <div class="card">
        <h3 style="font-weight:700;margin-bottom:16px">Audience Devices</h3>
        <?php foreach($devices as $dv):
          $pct = round(($dv['cnt']/$total_clicks)*100);
          $colors = ['desktop'=>'var(--accent)','mobile'=>'var(--green)','tablet'=>'var(--yellow)'];
          $col = $colors[$dv['device']] ?? 'var(--text2)';
        ?>
        <div class="donut-bar">
          <div style="width:12px;height:12px;background:<?= $col ?>;border-radius:3px;flex-shrink:0"></div>
          <span style="font-size:.875rem;text-transform:capitalize;flex:1"><?= e($dv['device']) ?></span>
          <span style="font-weight:700;font-size:.875rem"><?= $pct ?>%</span>
          <span class="text-muted text-xs">(<?= format_number((int)$dv['cnt']) ?>)</span>
        </div>
        <div class="progress" style="height:6px;margin-bottom:12px">
          <div class="progress-bar-fill" style="width:<?= $pct ?>%;background:<?= $col ?>"></div>
        </div>
        <?php endforeach; ?>
        <?php if(!$devices): ?><p class="text-muted text-sm">No clicks recorded yet.</p><?php endif; ?>
      </div>
    </div>
  </main>
</div>
</body></html>
