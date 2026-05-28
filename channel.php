<?php
// Channel / Creator Profile Page
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$channel_id = (int)($_GET['id'] ?? 0);
if (!$channel_id) { redirect(BASE_URL . '/'); }

$channel = db_fetch("SELECT * FROM users WHERE id=? AND status='active'", [$channel_id]);
$is_self = is_logged_in() && auth_user()['id'] == $channel_id;
$has_videos = db_count('videos', "user_id=?", [$channel_id]) > 0;

if (!$channel || $channel['role'] === 'viewer' || (!$is_self && !$has_videos && !in_array($channel['role'],['creator','admin']))) {
    if ($is_self && $channel['role'] === 'viewer') {
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }
    http_response_code(404); die('Channel not found');
}

$tab  = $_GET['tab'] ?? 'videos';
$sort = $_GET['sort'] ?? 'latest';
$page = max(1,(int)($_GET['page']??1));

$order = match($sort) { 'views'=>'views DESC', 'oldest'=>'published_at ASC', default=>'published_at DESC' };

$is_owner = is_logged_in() && auth_user()['id'] == $channel_id;
if ($is_owner) {
    // Show all videos of the owner (even pending, draft, private ones)
    $total  = db_count('videos', "user_id=?", [$channel_id]);
    $pg     = paginate($total, 12, $page);
    $videos = db_fetchAll(
        "SELECT * FROM videos WHERE user_id=?
         ORDER BY $order LIMIT 12 OFFSET {$pg['offset']}", [$channel_id]
    );
} else {
    // Show only public published videos
    $total  = db_count('videos', "user_id=? AND status='published' AND visibility='public'", [$channel_id]);
    $pg     = paginate($total, 12, $page);
    $videos = db_fetchAll(
        "SELECT * FROM videos WHERE user_id=? AND status='published' AND visibility='public'
         ORDER BY $order LIMIT 12 OFFSET {$pg['offset']}", [$channel_id]
    );
}

$is_subscribed = false;
if (is_logged_in()) {
    $is_subscribed = (bool)db_fetch("SELECT id FROM subscriptions WHERE subscriber_id=? AND channel_id=?", [auth_user()['id'],$channel_id]);
}

$earningsMap = [];
if ($is_owner && is_creator() && $videos) {
    // Duration sync removed for performance — synced via watch.php instead
    $earningsMap = fh_creator_video_earnings_map($channel_id, array_column($videos, 'id'));
}

$meta_title = ($channel['channel_name'] ?: $channel['username']) . ' — ' . setting('site_name','FreeHub');
$meta_desc  = truncate($channel['bio'] ?? '', 160);
$meta_image = avatar_url($channel['avatar']);
require_once __DIR__ . '/includes/header.php';
?>

<div class="layout">
<?php require_once __DIR__ . '/includes/sidebar.php'; ?>
<main class="main-content">

<div class="channel-cover">
  <?php if ($channel['cover_image']): ?>
  <img src="<?= cover_url($channel['cover_image']) ?>" alt="Cover">
  <?php else: ?>
  <div style="background:linear-gradient(135deg,rgba(99,102,241,.2),rgba(236,72,153,.1));height:100%;width:100%"></div>
  <?php endif; ?>
</div>

<div class="container channel-page">
  <!-- Channel Header -->
  <div class="channel-header">
    <div class="channel-avatar-sub-row">
      <img src="<?= avatar_url($channel['avatar']) ?>" alt="<?= e($channel['channel_name']??$channel['username']) ?>"
           class="avatar channel-avatar-lg" loading="eager">
      <?php if (is_logged_in() && auth_user()['id'] != $channel_id): ?>
      <button class="btn btn-primary sub-trigger-btn mobile-only-sub-btn" data-channel="<?= $channel_id ?>">
        <?= $is_subscribed ? 'Subscribed ✓' : 'Subscribe' ?>
      </button>
      <?php endif; ?>
    </div>
    
    <div class="channel-header-info">
      <div class="channel-title-row">
        <h1 class="channel-name-title"><?= e($channel['channel_name'] ?: $channel['username']) ?></h1>
        <div class="channel-meta-stats text-muted text-sm">
          <span class="stat-subscribers"><?= format_number((int)$channel['subscribers']) ?> subscribers</span>
          <span class="meta-separator">·</span>
          <span class="stat-videos"><?= $total ?> videos</span>
          <span class="meta-separator joined-sep">·</span>
          <span class="joined-date">Joined <?= date('M Y', strtotime($channel['created_at'])) ?></span>
        </div>
      </div>
      <?php if ($channel['bio']): ?>
      <p class="channel-bio-text"><?= e(truncate($channel['bio'],200)) ?></p>
      <?php endif; ?>
    </div>
    
    <div class="channel-header-action-desktop">
      <?php if (is_logged_in() && auth_user()['id'] != $channel_id): ?>
      <button class="btn btn-primary sub-trigger-btn" id="sub-btn" data-channel="<?= $channel_id ?>">
        <?= $is_subscribed ? 'Subscribed ✓' : 'Subscribe' ?>
      </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- Tabs -->
  <div class="channel-tabs">
    <a href="?id=<?= $channel_id ?>&tab=videos" class="channel-tab <?= $tab==='videos'?'active':'' ?>">Videos</a>
    <a href="?id=<?= $channel_id ?>&tab=playlists" class="channel-tab <?= $tab==='playlists'?'active':'' ?>">Playlists</a>
    <a href="?id=<?= $channel_id ?>&tab=about" class="channel-tab <?= $tab==='about'?'active':'' ?>">About</a>
    
    <!-- Desktop sorting (hidden on mobile) -->
    <div class="channel-sort channel-sort-desktop">
      <?php foreach(['latest'=>'Latest','views'=>'Popular','oldest'=>'Oldest'] as $s=>$l): ?>
      <a href="?id=<?= $channel_id ?>&tab=videos&sort=<?= $s ?>" class="btn btn-sm <?= $sort===$s?'btn-primary':'btn-outline' ?>"><?= $l ?></a>
      <?php endforeach; ?>
    </div>
    
    <!-- Mobile sorting (dropdown, hidden on desktop) -->
    <div class="channel-sort-mobile">
      <select onchange="location = this.value;" class="sort-dropdown-select" aria-label="Sort videos">
        <?php foreach(['latest'=>'Latest','views'=>'Popular','oldest'=>'Oldest'] as $s=>$l): ?>
        <option value="?id=<?= $channel_id ?>&tab=videos&sort=<?= $s ?>" <?= $sort===$s?'selected':'' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <!-- Videos tab -->
  <?php if ($tab==='videos'): ?>
  <?php if ($videos): ?>
  <div class="grid grid-6">
    <?php foreach ($videos as $v):
      $ref = auth_user()['ref_code'] ?? '';
      $url = BASE_URL . '/watch.php?v=' . $v['id'] . ($ref ? '&ref='.$ref : '');
      $thumb = thumb_url($v['thumbnail']);

    ?>
    <article class="video-card fade-in" onclick="location.href='<?= $url ?>'">
      <div class="video-thumb" style="position:relative">
        <img src="<?= $thumb ?>" alt="<?= e($v['title']) ?>" loading="lazy" width="320" height="180" class="thumb-main">
        <?php $durSec = (int)$v['duration']; ?>
        <?php if ($durSec > 0): ?>
        <span class="video-duration"><?= format_duration($durSec) ?></span>
        <?php else: ?>
        <span class="video-duration video-duration--pending" data-video-id="<?= (int)$v['id'] ?>">…</span>
        <?php endif; ?>
      </div>
      <div class="video-info">
        <div class="video-title"><?= e($v['title']) ?></div>
        <div class="video-card-meta-row">
          <?php if ($is_owner): ?>
            <span class="badge badge-<?= $v['status']==='published'?'green':($v['status']==='pending'?'yellow':'gray') ?>"><?= $v['status'] ?></span>
            <?php if ($v['visibility']!=='public'): ?>
              <span class="badge badge-gray"><?= $v['visibility'] ?></span>
            <?php endif; ?>
          <?php endif; ?>
          <span class="meta-item-views" style="display:inline-flex;align-items:center;gap:3px">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            <?= format_number((int)$v['views']) ?>
          </span>
          <span>·</span>
          <span><?= time_ago($v['published_at']??$v['created_at']) ?></span>
          <?php if ($is_owner && is_creator()): ?>
            <span>·</span>
            <span class="video-earnings-inline" title="Watch-time earnings on this video">
              <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:2px"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
              <?= e(fh_format_money($earningsMap[(int)$v['id']] ?? 0.0, fh_user_currency())) ?>
            </span>
          <?php endif; ?>
        </div>
      </div>
    </article>
    <?php endforeach; ?>
  </div>

  <?php if ($pg['pages']>1): ?>
  <div class="flex gap-2" style="margin-top:24px;justify-content:center">
    <?php if($pg['has_prev']): ?><a href="?id=<?= $channel_id ?>&tab=videos&sort=<?= $sort ?>&page=<?= $page-1 ?>" class="btn btn-outline btn-sm">&laquo;</a><?php endif; ?>
    <span class="text-muted text-sm" style="align-self:center"><?= $page ?>/<?= $pg['pages'] ?></span>
    <?php if($pg['has_next']): ?><a href="?id=<?= $channel_id ?>&tab=videos&sort=<?= $sort ?>&page=<?= $page+1 ?>" class="btn btn-outline btn-sm">&raquo;</a><?php endif; ?>
  </div>
  <?php endif; ?>

  <?php else: ?>
  <div style="text-align:center;padding:60px;color:var(--text2)">
    <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 12px;opacity:.4"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
    <p>No videos yet</p>
  </div>
  <?php endif; ?>
  <?php endif; ?>

  <!-- About tab -->
  <?php if ($tab==='about'): ?>
  <div class="card" style="max-width:600px">
    <h3 style="font-weight:700;margin-bottom:12px">About <?= e($channel['channel_name']??$channel['username']) ?></h3>
    <p style="line-height:1.7;color:var(--text2)"><?= nl2br(e($channel['bio'] ?? 'No description provided.')) ?></p>
    <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border)">
      <div class="flex gap-3 text-sm text-muted">
        <span>&#128337; Joined <?= date('F Y', strtotime($channel['created_at'])) ?></span>
        <span>·</span>
        <span>&#128250; <?= $total ?> videos</span>
        <span>·</span>
        <span>&#128065; <?= format_number((int)$channel['total_views']) ?> total views</span>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
</main>
</div>

<script>
document.querySelectorAll('.sub-trigger-btn').forEach(btn => {
  btn.addEventListener('click', async function() {
    const res = await fetch('<?= BASE_URL ?>/api/videos.php?action=subscribe', {
      method: 'POST', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({channel_id: <?= $channel_id ?>})
    });
    const d = await res.json();
    document.querySelectorAll('.sub-trigger-btn').forEach(b => {
      b.textContent = d.subscribed ? 'Subscribed ✓' : 'Subscribe';
    });
  });
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
