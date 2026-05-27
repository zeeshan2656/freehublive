<?php
// Creator — Analytics
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['creator','admin']);
$site_theme = setting('active_theme', 'dark-minimal');
$primary    = setting('primary_color', '#6366f1');
$uid = auth_user()['id'];

$videos = db_fetchAll("SELECT id,title,views,likes,comments_count,watch_time,ad_impressions,ad_clicks,created_at FROM videos WHERE user_id=? AND status='published' ORDER BY views DESC", [$uid]);
$video_ids = array_column($videos, 'id');
$earnings_map = fh_creator_video_earnings_map((int)$uid, $video_ids);
$creator_cpm = (float)setting('creator_cpm', '1.00');
$creator_cpc = (float)setting('creator_cpc', '50.00');

// Chart data
$chart = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $ids = array_column($videos, 'id');
    $views = 0;
    if ($ids) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $views = db_count('video_views', "video_id IN ($in) AND DATE(created_at)=?", [...$ids, $d]);
    }
    $chart[] = ['date' => date('M j', strtotime("-$i days")), 'views' => $views];
}
$maxV = max(1, max(array_column($chart,'views')));
$meta_title = 'Analytics';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">

    <?php $watch_stats_user_id = (int)$uid; $watch_stats_compact = true; require __DIR__ . '/../includes/partials/watch_earnings_stats.php'; ?>

    <!-- Stats -->
    <div class="stat-grid-4" style="margin-bottom:28px">
      <div class="stat-card"><div class="stat-value"><?= count($videos) ?></div><div class="stat-label">Published Videos</div></div>
      <div class="stat-card"><div class="stat-value"><?= format_number(array_sum(array_column($videos,'views'))) ?></div><div class="stat-label">Total Views</div></div>
      <div class="stat-card"><div class="stat-value"><?= format_number(array_sum(array_column($videos,'likes'))) ?></div><div class="stat-label">Total Likes</div></div>
      <div class="stat-card"><div class="stat-value"><?= format_number(array_sum(array_column($videos,'comments_count'))) ?></div><div class="stat-label">Comments</div></div>
    </div>

    <!-- 7-day Chart -->
    <div class="card" style="margin-bottom:24px">
      <h3 style="font-weight:700;margin-bottom:16px">Views — Last 7 Days</h3>
      <div style="display:flex;align-items:flex-end;gap:12px;height:140px">
        <?php foreach ($chart as $d):
          $h = round(($d['views']/$maxV)*120);
        ?>
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;height:140px;justify-content:flex-end">
          <span style="font-size:.72rem;color:var(--text2)"><?= $d['views'] ?></span>
          <div style="width:100%;height:<?= $h ?>px;background:linear-gradient(var(--accent),var(--accent2));border-radius:4px 4px 0 0" title="<?= $d['views'] ?> views"></div>
          <span style="font-size:.72rem;color:var(--text2)"><?= $d['date'] ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Video Performance -->
    <?php if ($videos): ?>
    <div class="card">
      <h3 style="font-weight:700;margin-bottom:16px">Video Ad Performance</h3>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Video</th>
              <th style="text-align:right">Ad Impressions</th>
              <th style="text-align:right">Ad Clicks</th>
              <th style="text-align:right">CPM Rate</th>
              <th style="text-align:right">CPC Rate</th>
              <th style="text-align:right">Estimated Revenue</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($videos as $v):
            $vid = (int)$v['id'];
            $earned = $earnings_map[$vid] ?? 0.0;
          ?>
          <tr>
            <td style="font-size:.83rem;font-weight:500;max-width:200px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis" title="<?= e($v['title']) ?>">
              <?= e($v['title']) ?>
            </td>
            <td class="text-sm" style="text-align:right"><?= format_number((int)$v['ad_impressions']) ?></td>
            <td class="text-sm" style="text-align:right"><?= format_number((int)$v['ad_clicks']) ?></td>
            <td class="text-xs text-muted" style="text-align:right">$<?= number_format($creator_cpm, 2) ?></td>
            <td class="text-xs text-muted" style="text-align:right">$<?= number_format($creator_cpc, 2) ?></td>
            <td class="text-sm font-semibold" style="text-align:right;color:var(--green)">$<?= number_format($earned, 4) ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
