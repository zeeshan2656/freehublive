<?php
// ============================================================
// FreeHub.Live — Affiliate Dashboard
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['affiliate','admin']);

$site_theme = setting('active_theme', 'dark-minimal');
$primary    = setting('primary_color', '#6366f1');

$uid = auth_user()['id'];
$user = db_fetch("SELECT * FROM users WHERE id=?", [$uid]);
$watch_stats = fh_user_watch_stats((int)$uid);

// Stats
$today = date('Y-m-d');
$stats = [
    'clicks_today'  => db_count('affiliate_clicks', "affiliate_id=? AND DATE(created_at)=?", [$uid, $today]),
    'clicks_total'  => db_count('affiliate_clicks', "affiliate_id=?", [$uid]),
    'views_today'   => db_count('video_views', "affiliate_id=? AND DATE(created_at)=?", [$uid, $today]),
    'views_total'   => db_count('video_views', "affiliate_id=?", [$uid]),
    'earnings'      => db_fetch("SELECT SUM(amount) as t FROM earnings WHERE user_id=? AND status='approved'", [$uid])['t'] ?? 0,
    'pending'       => db_fetch("SELECT SUM(amount) as t FROM earnings WHERE user_id=? AND status='pending'", [$uid])['t'] ?? 0,
];

// Chart data — last 7 days
$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_data[] = [
        'date'   => date('M j', strtotime("-$i days")),
        'clicks' => db_count('affiliate_clicks', "affiliate_id=? AND DATE(created_at)=?", [$uid, $date]),
        'views'  => db_count('video_views',      "affiliate_id=? AND DATE(created_at)=?", [$uid, $date]),
    ];
}

// Recent clicks
$recent = db_fetchAll(
    "SELECT ac.*,v.title FROM affiliate_clicks ac
     LEFT JOIN videos v ON v.id=ac.video_id
     WHERE ac.affiliate_id=? ORDER BY ac.created_at DESC LIMIT 10", [$uid]
);

$meta_title = 'Affiliate Dashboard';
?><!DOCTYPE html>
<html lang="en" data-theme="<?= e($site_theme) ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($meta_title) ?> — <?= e(setting('site_name','FreeHub')) ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
<style>:root{--accent:<?= e($primary) ?>;--accent2:<?= e($primary) ?>cc}</style>
<script>const _st=localStorage.getItem('fh_theme');if(_st)document.documentElement.setAttribute('data-theme',_st);</script>
<style>
.aff-layout{display:grid;grid-template-columns:220px 1fr;min-height:100vh}
.aff-sidebar{background:var(--bg2);border-right:1px solid var(--border);padding:20px 12px}
.aff-main{padding:28px;overflow-y:auto}
.aff-nav-item{display:flex;align-items:center;gap:9px;padding:9px 12px;border-radius:8px;color:var(--text2);font-size:.875rem;font-weight:500;transition:all .15s;margin-bottom:2px}
.aff-nav-item:hover{background:var(--bg3);color:var(--text)}
.aff-nav-item.active{background:rgba(99,102,241,.12);color:var(--accent)}
.ref-box{background:linear-gradient(135deg,rgba(99,102,241,.2),rgba(129,140,248,.1));border:1px solid rgba(99,102,241,.3);border-radius:var(--radius-lg);padding:24px;margin-bottom:28px}
.ref-label{font-size:.8rem;color:var(--text2);margin-bottom:6px;font-weight:600;text-transform:uppercase;letter-spacing:.05em}
.ref-link{font-size:.9rem;background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:10px 14px;word-break:break-all;margin-bottom:10px;color:var(--text)}
.chart-bar{background:var(--accent);border-radius:4px 4px 0 0;min-width:28px;transition:height .4s;position:relative;cursor:pointer}
.chart-bar:hover::after{content:attr(data-val);position:absolute;top:-24px;left:50%;transform:translateX(-50%);background:var(--bg3);color:var(--text);font-size:.72rem;padding:2px 6px;border-radius:4px;white-space:nowrap}
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
    <div style="padding:4px 12px 20px">
      <?= render_site_logo('studio') ?>
    </div>
    <a href="<?= BASE_URL ?>/affiliate/" class="aff-nav-item active">&#128202; Dashboard</a>
    <a href="<?= BASE_URL ?>/affiliate/links.php" class="aff-nav-item">&#128279; My Links</a>
    <a href="<?= BASE_URL ?>/affiliate/analytics.php" class="aff-nav-item">&#128200; Analytics</a>
    <a href="<?= BASE_URL ?>/affiliate/earnings.php" class="aff-nav-item">&#128176; Earnings</a>
        <a href="<?= BASE_URL ?>/profile.php" class="aff-nav-item">&#128100; Edit Profile</a>
    <a href="<?= BASE_URL ?>/affiliate/reports.php" class="aff-nav-item">&#128203; Reports</a>
    <hr style="border-color:var(--border);margin:12px 0">
    <a href="<?= BASE_URL ?>/" class="aff-nav-item">&#127968; Back to Site</a>
    <a href="<?= BASE_URL ?>/auth/logout.php" class="aff-nav-item" style="color:var(--red)">&#x21B5; Logout</a>
  </aside>

  <main class="aff-main">
    <div class="flex" style="justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
      <div>
        <h1 style="font-size:1.4rem;font-weight:800;margin-bottom:2px">Affiliate Dashboard</h1>
        <p class="text-muted text-sm">Welcome back, <?= e($user['username']) ?></p>
      </div>
      <div class="flex gap-2">
        <span class="badge badge-green" style="font-size:.85rem;padding:6px 14px">Active Affiliate</span>
      </div>
    </div>

    <!-- Referral link box -->
    <div class="ref-box">
      <div class="ref-label">Your Referral Link</div>
      <div class="ref-link" id="ref-link-display"><?= BASE_URL ?>/?ref=<?= e($user['ref_code']) ?></div>
      <div class="flex gap-2">
        <button class="btn btn-primary btn-sm" onclick="
          navigator.clipboard.writeText('<?= BASE_URL ?>/?ref=<?= e($user['ref_code']) ?>');
          this.textContent='Copied! ✓';setTimeout(()=>this.textContent='Copy Link',2000)">Copy Link</button>
        <a href="https://wa.me/?text=<?= urlencode('Watch amazing videos on ' . setting('site_name','FreeHub') . '! ' . BASE_URL . '/?ref=' . $user['ref_code']) ?>"
           target="_blank" class="btn btn-outline btn-sm">Share on WhatsApp</a>
        <a href="https://twitter.com/intent/tweet?url=<?= urlencode(BASE_URL . '/?ref=' . $user['ref_code']) ?>&text=<?= urlencode('Check out ' . setting('site_name','FreeHub') . '!') ?>"
           target="_blank" class="btn btn-outline btn-sm">Share on X</a>
      </div>
    </div>

    <?php $watch_stats_user_id = (int)$uid; require __DIR__ . '/../includes/partials/watch_earnings_stats.php'; ?>

    <p class="text-sm text-muted" style="margin:-12px 0 20px">Lifetime watch earnings above update automatically while you watch videos (<?= e($watch_stats['rate_formatted']) ?>).</p>

    <!-- Referral stats -->
    <div class="stat-grid-3" style="margin-bottom:28px">
      <div class="stat-card">
        <div class="stat-value"><?= format_number($stats['clicks_total']) ?></div>
        <div class="stat-label">Total Clicks</div>
        <div class="stat-change up">+<?= $stats['clicks_today'] ?> today</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?= format_number($stats['views_total']) ?></div>
        <div class="stat-label">Views Generated</div>
        <div class="stat-change up">+<?= $stats['views_today'] ?> today</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?= fh_format_money((float)$stats['earnings'], $user['preferred_currency'] ?? 'USD') ?></div>
        <div class="stat-label">Referral Ledger (legacy)</div>
        <div class="stat-change" style="color:var(--yellow)"><?= fh_format_money((float)$stats['pending'], $user['preferred_currency'] ?? 'USD') ?> pending</div>
      </div>
    </div>

    <!-- Chart -->
    <div class="card" style="margin-bottom:24px">
      <h3 style="font-weight:700;margin-bottom:16px">Last 7 Days — Clicks vs Views</h3>
      <div style="display:flex;align-items:flex-end;gap:8px;height:140px;padding-bottom:4px">
        <?php
        $max = max(1, max(array_map(fn($d) => max($d['clicks'],$d['views']), $chart_data)));
        foreach ($chart_data as $d):
          $ch = round(($d['clicks']/$max)*120);
          $vh = round(($d['views']/$max)*120);
        ?>
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:3px">
          <div style="display:flex;align-items:flex-end;gap:2px;height:120px">
            <div class="chart-bar" style="height:<?= $ch ?>px;background:var(--accent)" data-val="<?= $d['clicks'] ?> clicks" title="<?= $d['clicks'] ?> clicks"></div>
            <div class="chart-bar" style="height:<?= $vh ?>px;background:var(--green)" data-val="<?= $d['views'] ?> views" title="<?= $d['views'] ?> views"></div>
          </div>
          <span style="font-size:.7rem;color:var(--text2)"><?= $d['date'] ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="flex gap-4" style="margin-top:12px">
        <div class="flex gap-2"><div style="width:12px;height:12px;background:var(--accent);border-radius:2px"></div><span class="text-xs text-muted">Clicks</span></div>
        <div class="flex gap-2"><div style="width:12px;height:12px;background:var(--green);border-radius:2px"></div><span class="text-xs text-muted">Views</span></div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="card">
      <h3 style="font-weight:700;margin-bottom:16px">Recent Activity</h3>
      <?php if ($recent): ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Video</th><th>Device</th><th>Converted</th><th>Time</th></tr></thead>
          <tbody>
          <?php foreach ($recent as $r): ?>
          <tr>
            <td class="text-sm"><?= e(truncate($r['title'] ?? 'General Visit', 40)) ?></td>
            <td><span class="badge badge-blue"><?= e($r['device']) ?></span></td>
            <td><span class="badge badge-<?= $r['converted'] ? 'green' : 'gray' ?>"><?= $r['converted'] ? 'Yes' : 'No' ?></span></td>
            <td class="text-xs text-muted"><?= time_ago($r['created_at']) ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <p class="text-muted text-sm">No clicks yet. Start sharing your link!</p>
      <?php endif; ?>
    </div>
  </main>
</div>
</body></html>
