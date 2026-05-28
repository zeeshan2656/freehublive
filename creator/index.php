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
    <div class="card" style="padding: 20px;">
      <style>
        .studio-table-row:hover {
          background-color: rgba(255, 255, 255, 0.02) !important;
        }
        [data-theme="light-white"] .studio-table-row:hover,
        [data-theme="light-blue"] .studio-table-row:hover,
        [data-theme="light-green"] .studio-table-row:hover,
        [data-theme="pink"] .studio-table-row:hover {
          background-color: rgba(0, 0, 0, 0.015) !important;
        }
      </style>
      <div class="section-header" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
        <h3 style="font-weight:700; font-size: 1.05rem; color: var(--text);">My Videos</h3>
        <a href="<?= BASE_URL ?>/creator/videos.php" class="see-all" style="font-size: 0.82rem; color: var(--accent); font-weight: 600;">Manage All &rarr;</a>
      </div>
      
      <div class="table-wrap" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
          <thead>
            <tr style="border-bottom: 1px solid var(--border); color: var(--text2); font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em;">
              <th style="padding: 10px 8px; font-weight: 600;">Video</th>
              <th style="padding: 10px 8px; font-weight: 600;">Status</th>
              <th style="padding: 10px 8px; font-weight: 600;">Views</th>
              <th style="padding: 10px 8px; font-weight: 600;">Likes</th>
              <th style="padding: 10px 8px; font-weight: 600;">Duration</th>
              <th style="padding: 10px 8px; font-weight: 600;">Date</th>
              <th style="padding: 10px 8px; font-weight: 600; text-align: right;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($my_videos as $v): ?>
            <tr class="studio-table-row" style="border-bottom: 1px solid var(--border); transition: background-color 0.15s;">
              <td style="padding: 10px 8px;">
                <div class="flex gap-3" style="align-items: center;">
                  <div style="position: relative; flex-shrink: 0; width: 72px; aspect-ratio: 16/9; border-radius: 4px; overflow: hidden; background: #0c0c0d;">
                    <img src="<?= thumb_url($v['thumbnail']) ?>" style="width: 100%; height: 100%; object-fit: cover;" loading="lazy">
                    <span style="position: absolute; bottom: 3px; right: 3px; background: rgba(0,0,0,0.85); color: #fff; font-size: 0.6rem; font-weight: 700; padding: 1px 3px; border-radius: 2px; letter-spacing: 0.2px;">
                      <?= format_duration((int)$v['duration']) ?>
                    </span>
                  </div>
                  <div style="min-width: 0;">
                    <div style="font-weight: 600; font-size: 0.82rem; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 260px;" title="<?= e($v['title']) ?>">
                      <?= e($v['title']) ?>
                    </div>
                    <div style="font-size: 0.7rem; color: var(--text2); margin-top: 2px;">
                      <?= e(ucfirst($v['visibility'] ?? 'public')) ?>
                    </div>
                  </div>
                </div>
              </td>
              <td style="padding: 10px 8px; vertical-align: middle;">
                <span class="badge badge-<?= $v['status']==='published'?'green':($v['status']==='pending'?'yellow':'gray') ?>" style="font-size: 0.65rem; font-weight: 700; padding: 2px 7px; border-radius: 4px; text-transform: uppercase;">
                  <?= e($v['status']) ?>
                </span>
              </td>
              <td style="padding: 10px 8px; font-size: 0.8rem; color: var(--text); font-weight: 500; vertical-align: middle;">
                <?= format_number((int)$v['views']) ?>
              </td>
              <td style="padding: 10px 8px; font-size: 0.8rem; color: var(--text2); vertical-align: middle;">
                <?= format_number((int)$v['likes']) ?>
              </td>
              <td style="padding: 10px 8px; font-size: 0.8rem; color: var(--text2); vertical-align: middle;">
                <?= format_duration((int)$v['duration']) ?>
              </td>
              <td style="padding: 10px 8px; font-size: 0.72rem; color: var(--text2); vertical-align: middle;">
                <?= date('M j, Y', strtotime($v['created_at'])) ?>
              </td>
              <td style="padding: 10px 8px; text-align: right; vertical-align: middle;">
                <div class="flex gap-1" style="justify-content: flex-end; align-items: center;">
                  <a href="<?= BASE_URL ?>/creator/edit.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-outline" style="padding: 3px 6px; font-size: 0.72rem; border-radius: 4px; display: inline-flex; align-items: center; gap: 3px;" title="Edit Video">
                    <span>&#9998;</span> Edit
                  </a>
                  <a href="<?= BASE_URL ?>/watch.php?v=<?= $v['id'] ?>" class="btn btn-sm btn-outline" style="padding: 3px 5px; font-size: 0.72rem; border-radius: 4px; display: inline-flex; align-items: center;" target="_blank" title="View Video">
                    <span>&#128065;</span>
                  </a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
