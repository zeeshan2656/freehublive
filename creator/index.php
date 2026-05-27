<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['creator','admin']);

$site_theme = setting('active_theme', 'dark-minimal');
$primary    = setting('primary_color', '#6366f1');

$uid  = auth_user()['id'];
$user = db_fetch("SELECT * FROM users WHERE id=?", [$uid]);

$watch_stats = fh_user_watch_stats((int)$uid);
$stats = [
    'videos'         => db_count('videos', "user_id=?", [$uid]),
    'ad_impressions' => db_fetch("SELECT SUM(ad_impressions) as t FROM videos WHERE user_id=?", [$uid])['t'] ?? 0,
    'ad_clicks'      => db_fetch("SELECT SUM(ad_clicks) as t FROM videos WHERE user_id=?", [$uid])['t'] ?? 0,
    'subscribers'    => $user['subscribers'],
];

$my_videos = db_fetchAll(
    "SELECT * FROM videos WHERE user_id=? ORDER BY created_at DESC LIMIT 10", [$uid]
);

$meta_title = 'Creator Studio';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">

    <?php $watch_stats_user_id = (int)$uid; $creator_context = true; require __DIR__ . '/../includes/partials/watch_earnings_stats.php'; ?>

    <!-- Channel stats -->
    <div class="stat-grid-4" style="margin-bottom:28px">
      <div class="stat-card">
        <div class="stat-value"><?= $stats['videos'] ?></div>
        <div class="stat-label">Videos</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?= format_number($stats['ad_impressions']) ?></div>
        <div class="stat-label">Ad Impressions</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?= format_number($stats['ad_clicks']) ?></div>
        <div class="stat-label">Ad Clicks</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?= format_number($stats['subscribers']) ?></div>
        <div class="stat-label">Subscribers</div>
      </div>
    </div>

    <!-- Upload CTA if no videos -->
    <?php if (!$my_videos): ?>
    <div class="upload-cta">
      <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 12px;color:var(--accent)"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
      <h2 style="font-size:1.1rem;font-weight:700;margin-bottom:6px">Upload Your First Video</h2>
      <p class="text-muted text-sm" style="margin-bottom:16px">Share your content with the world and start earning</p>
      <a href="<?= BASE_URL ?>/creator/upload.php" class="btn btn-primary">Upload Now</a>
    </div>
    <?php else: ?>
    <!-- My Videos -->
    <div class="card">
      <div class="section-header" style="margin-bottom:16px">
        <h3 style="font-weight:700">My Videos</h3>
        <a href="<?= BASE_URL ?>/creator/videos.php" class="see-all">Manage All &rarr;</a>
      </div>
      <?php foreach ($my_videos as $v): ?>
      <div class="video-row">
        <img src="<?= thumb_url($v['thumbnail']) ?>" style="width:96px;aspect-ratio:16/9;border-radius:6px;object-fit:cover;flex-shrink:0" loading="lazy">
        <div style="flex:1;min-width:0">
          <div style="font-weight:600;font-size:.88rem;overflow:hidden;white-space:nowrap;text-overflow:ellipsis"><?= e($v['title']) ?></div>
          <div class="flex gap-3 text-muted text-xs" style="margin-top:4px">
            <span><?= format_number((int)$v['views']) ?> views</span>
            <span><?= format_number((int)$v['likes']) ?> likes</span>
            <span><?= format_duration((int)$v['duration']) ?></span>
            <span><?= time_ago($v['created_at']) ?></span>
          </div>
        </div>
        <div class="flex gap-2">
          <span class="badge badge-<?= $v['status']==='published'?'green':($v['status']==='pending'?'yellow':'gray') ?>"><?= $v['status'] ?></span>
          <a href="<?= BASE_URL ?>/watch.php?v=<?= $v['id'] ?>" class="btn btn-outline btn-sm" target="_blank">View</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
