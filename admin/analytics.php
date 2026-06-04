<?php
// Admin — Full Analytics Dashboard
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

// ── Date Filters ─────────────────────────────────────────────
$period  = $_GET['period'] ?? '30'; // 7, 30, 90, custom
$from    = $_GET['from'] ?? '';
$to      = $_GET['to'] ?? '';

if ($from && $to) {
    $dateWhere  = "DATE(created_at) BETWEEN ? AND ?";
    $dateParams = [$from, $to];
    $label      = "($from to $to)";
    $days       = max(1, (int)((strtotime($to) - strtotime($from)) / 86400) + 1);
} else {
    $days       = in_array((int)$period, [7, 14, 30, 60, 90]) ? (int)$period : 30;
    $dateWhere  = "created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
    $dateParams = [$days];
    $label      = "Last $days days";
}

// ── Global Totals ─────────────────────────────────────────────
$totals = [
    'users_total'        => db_count('users'),
    'viewers_total'      => db_count('users', "role IN ('viewer','affiliate')"),
    'creators_total'     => db_count('users', "role='creator'"),
    'videos_total'       => db_count('videos'),
    'videos_published'   => db_count('videos', "status='published'"),
    'videos_pending'     => db_count('videos', "status='pending'"),
    'total_views'        => (int)(db_fetch("SELECT COALESCE(SUM(views),0) AS t FROM videos")['t'] ?? 0),
];

// ── Period-based Trends ───────────────────────────────────────
$daily = [];
if ($from && $to) {
    $start = strtotime($from);
    $end   = strtotime($to);
    for ($ts = $start; $ts <= $end; $ts += 86400) {
        $d = date('Y-m-d', $ts);
        $daily[] = [
            'date'     => date('M j', $ts),
            'views'    => db_count('video_views', "DATE(created_at)=?", [$d]),
            'users'    => db_count('users', "DATE(created_at)=?", [$d]),
        ];
    }
} else {
    for ($i = $days - 1; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $daily[] = [
            'date'     => date('M j', strtotime("-$i days")),
            'views'    => db_count('video_views', "DATE(created_at)=?", [$d]),
            'users'    => db_count('users', "DATE(created_at)=?", [$d]),
        ];
    }
}

$maxViews    = max(1, max(array_column($daily, 'views')));
$maxUsers    = max(1, max(array_column($daily, 'users')));

// ── Top Content ───────────────────────────────────────────────
$top_videos = db_fetchAll(
    "SELECT v.title, v.views, v.watch_time, u.channel_name
     FROM videos v JOIN users u ON u.id=v.user_id WHERE v.status='published'
     ORDER BY v.views DESC LIMIT 10"
);
$top_creators = db_fetchAll(
    "SELECT u.username, u.channel_name,
     (SELECT COUNT(*) FROM videos WHERE user_id=u.id AND status='published') AS vid_count,
     (SELECT COALESCE(SUM(views),0) FROM videos WHERE user_id=u.id) AS total_views
     FROM users u WHERE u.role='creator'
     ORDER BY total_views DESC LIMIT 8"
);

// ── New Users This Period ─────────────────────────────────────
$period_new_users    = db_count('users', $dateWhere, $dateParams);
$period_new_videos   = db_count('videos', $dateWhere, $dateParams);

$meta_title = 'Analytics';
require_once __DIR__ . '/partials/admin_head.php';
?>
<div class="admin-content">
  <div class="admin-page-header" style="justify-content:flex-end">
    <!-- Period Selector -->
    <form method="GET" class="flex gap-2" style="flex-wrap:wrap;align-items:center">
      <div class="smart-date-filter" data-preset="<?= e($_GET['date_preset'] ?? '') ?>">
        <button type="button" class="btn btn-outline smart-date-btn" style="justify-content:space-between; height:32px; font-size:.8rem; min-width: 180px; padding: 0 10px; border-radius: 4px; display: inline-flex; align-items: center;">
          <span>📅 <?= !empty($from) && !empty($to) ? e($from) . ' - ' . e($to) : 'Select Date Range' ?></span>
          <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-left: 8px;"><path d="m6 9 6 6 6-6"/></svg>
        </button>
        <input type="hidden" name="from" class="smart-from-val" value="<?= e($from) ?>">
        <input type="hidden" name="to" class="smart-to-val" value="<?= e($to) ?>">
        <input type="hidden" name="date_preset" value="<?= e($_GET['date_preset'] ?? '') ?>">
      </div>
      <button type="submit" class="btn btn-primary btn-sm">Apply</button>
      <?php if ($from || $to): ?><a href="?" class="btn btn-outline btn-sm">Reset</a><?php endif; ?>
    </form>
  </div>

  <!-- Global Stats Grid -->
  <div class="stat-grid-4" style="margin-bottom:28px">
    <div class="stat-card">
      <div class="stat-value"><?= format_number($totals['users_total']) ?></div>
      <div class="stat-label">Total Users</div>
      <div class="text-xs text-muted" style="margin-top:4px">
        👁️ <?= format_number($totals['viewers_total']) ?> viewers &nbsp;
        🎬 <?= format_number($totals['creators_total']) ?> creators
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= format_number($totals['videos_total']) ?></div>
      <div class="stat-label">Total Videos</div>
      <div class="text-xs text-muted" style="margin-top:4px">
        ✅ <?= $totals['videos_published'] ?> live &nbsp;
        ⏳ <?= $totals['videos_pending'] ?> pending
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= format_number($totals['total_views']) ?></div>
      <div class="stat-label">Total Views</div>
      <div class="text-xs text-muted" style="margin-top:4px">📺 Imps: <?= format_number($totals['total_ad_impressions']) ?> · 🖱️ Clicks: <?= format_number($totals['total_ad_clicks']) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-value" style="color:var(--accent)"><?= $period_new_users ?></div>
      <div class="stat-label">New Users (<?= $label ?>)</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= $period_new_videos ?></div>
      <div class="stat-label">New Videos (<?= $label ?>)</div>
    </div>
  </div>

  <!-- Period Highlights -->
  <div class="card" style="margin-bottom:24px;background:linear-gradient(135deg,rgba(99,102,241,.1),rgba(139,92,246,.05));border-color:rgba(99,102,241,.3)">
    <div class="flex gap-4" style="flex-wrap:wrap;justify-content:space-around;text-align:center">
      <div><div style="font-size:1.5rem;font-weight:800;color:var(--accent)"><?= $period_new_users ?></div><div class="text-sm text-muted">New Signups</div></div>
      <div><div style="font-size:1.5rem;font-weight:800"><?= $period_new_videos ?></div><div class="text-sm text-muted">Videos Uploaded</div></div>
    </div>
  </div>

  <!-- Views Chart -->
  <div class="card" style="margin-bottom:24px">
    <div class="flex" style="justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px">
      <h3 style="font-weight:700">Daily Views — <?= $label ?></h3>
      <div class="flex gap-3 text-xs text-muted">
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:var(--accent);margin-right:4px"></span>Views</span>
      </div>
    </div>
    <div style="display:flex;align-items:flex-end;gap:3px;height:140px;overflow-x:auto">
      <?php foreach ($daily as $d):
        $h = round(($d['views']/$maxViews)*130);
      ?>
      <div style="flex:1;min-width:12px;display:flex;flex-direction:column;align-items:center;gap:3px;height:140px;justify-content:flex-end">
        <div style="width:100%;height:<?= $h ?>px;background:var(--accent);border-radius:3px 3px 0 0;cursor:pointer;transition:opacity .15s"
             title="<?= $d['date'] ?>: <?= $d['views'] ?> views" onmouseover="this.style.opacity='.7'" onmouseout="this.style.opacity='1'"></div>
        <span style="font-size:.55rem;color:var(--text3);writing-mode:vertical-rl;transform:rotate(180deg);height:28px;overflow:hidden"><?= $d['date'] ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>


  <!-- New Users Chart -->
  <div class="card" style="margin-bottom:24px">
    <h3 style="font-weight:700;margin-bottom:12px">Daily New Users — <?= $label ?></h3>
    <div style="display:flex;align-items:flex-end;gap:3px;height:80px;overflow-x:auto">
      <?php foreach ($daily as $d):
        $h = round(($d['users']/$maxUsers)*70);
      ?>
      <div style="flex:1;min-width:12px;height:80px;display:flex;flex-direction:column;align-items:center;justify-content:flex-end">
        <div style="width:100%;height:<?= $h ?>px;background:var(--yellow);border-radius:3px 3px 0 0;opacity:.85"
             title="<?= $d['date'] ?>: <?= $d['users'] ?> new users"></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Top Videos & Creators -->
  <div class="stat-grid-2">
    <!-- Top Videos -->
    <div class="card">
      <h3 style="font-weight:700;margin-bottom:16px">🏆 Top Videos by Views</h3>
      <div class="table-wrap">
        <table>
          <thead><tr><th>#</th><th>Title</th><th>Creator</th><th>Views</th></tr></thead>
          <tbody>
          <?php foreach ($top_videos as $i => $v): ?>
          <tr>
            <td class="text-muted text-sm"><?= $i+1 ?></td>
            <td style="font-size:.83rem;font-weight:500;max-width:140px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis"><?= e($v['title']) ?></td>
            <td class="text-sm text-muted"><?= e($v['channel_name']) ?></td>
            <td class="text-sm"><?= format_number((int)$v['views']) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$top_videos): ?><tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text3)">No videos yet</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Top Creators -->
    <div class="card">
      <h3 style="font-weight:700;margin-bottom:16px">🎬 Top Creators</h3>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Creator</th><th>Videos</th><th>Views</th></tr></thead>
          <tbody>
          <?php foreach ($top_creators as $c): ?>
          <tr>
            <td style="font-size:.83rem;font-weight:600"><a href="<?= BASE_URL ?>/admin/users.php?role=creator&s=<?= urlencode($c['username']) ?>" style="color:var(--accent)"><?= e($c['channel_name']??$c['username']) ?></a></td>
            <td class="text-sm"><?= $c['vid_count'] ?></td>
            <td class="text-sm"><?= format_number((int)$c['total_views']) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$top_creators): ?><tr><td colspan="4" style="text-align:center;padding:20px;color:var(--text3)">No creators yet</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/partials/admin_foot.php'; ?>
