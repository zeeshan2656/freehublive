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
        "SELECT v.*, u.username, u.channel_name, u.avatar
         FROM videos v
         JOIN users u ON u.id = v.user_id
         WHERE v.user_id = ?
         ORDER BY $order LIMIT 12 OFFSET {$pg['offset']}", [$channel_id]
    );
} else {
    // Show only public published videos
    $total  = db_count('videos', "user_id=? AND status='published' AND visibility='public'", [$channel_id]);
    $pg     = paginate($total, 12, $page);
    $videos = db_fetchAll(
        "SELECT v.*, u.username, u.channel_name, u.avatar
         FROM videos v JOIN users u ON u.id = v.user_id
         WHERE v.user_id=? AND v.status='published' AND v.visibility='public'
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

$ref = auth_user()['ref_code'] ?? '';

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
      <?php if (!is_logged_in() || auth_user()['id'] != $channel_id): ?>
      <button class="btn <?= $is_subscribed ? 'btn-subscribed' : 'btn-primary' ?> sub-trigger-btn mobile-only-sub-btn" data-channel="<?= $channel_id ?>">
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
      <?php if (!is_logged_in() || auth_user()['id'] != $channel_id): ?>
      <button class="btn <?= $is_subscribed ? 'btn-subscribed' : 'btn-primary' ?> sub-trigger-btn" id="sub-btn" data-channel="<?= $channel_id ?>">
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
      echo render_video_card($v, fh_video_card_opts($v, $earningsMap, $ref));
    endforeach; ?>
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

  <!-- Playlists tab -->
  <?php if ($tab==='playlists'): ?>
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
    <h3 style="font-weight:800;font-size:1.1rem;display:flex;align-items:center;gap:8px">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="color:var(--accent)"><rect x="4" y="4" width="16" height="16" rx="2"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="12" y1="4" x2="12" y2="20"/></svg>
      Playlists
    </h3>
    <?php if ($is_owner): ?>
    <button class="btn btn-primary btn-sm" id="create-playlist-btn">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="vertical-align:middle;margin-right:4px"><path d="M12 5v14M5 12h14"/></svg>
      New Playlist
    </button>
    <?php endif; ?>
  </div>
  
  <?php
    $playlists = db_fetchAll(
        "SELECT p.*, COUNT(pi.id) as item_count
         FROM playlists p
         LEFT JOIN playlist_items pi ON pi.playlist_id = p.id
         WHERE p.user_id = ?
         GROUP BY p.id
         ORDER BY p.updated_at DESC",
        [$channel_id]
    );
  ?>
  
  <?php if ($playlists): ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px">
    <?php foreach ($playlists as $pl): ?>
    <div class="card" style="padding:0;overflow:hidden;display:flex;flex-direction:column;cursor:pointer;transition:all 0.2s" onclick="location.href='<?= BASE_URL ?>/playlists.php?id=<?= $pl['id'] ?>'">
      <div style="width:100%;padding-top:56.25%;position:relative;background:linear-gradient(135deg,rgba(99,102,241,.1),rgba(236,72,153,.1))">
        <?php if ($pl['thumbnail']): ?>
        <img src="<?= thumb_url($pl['thumbnail']) ?>" alt="<?= e($pl['name']) ?>" style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover">
        <?php else: ?>
        <div style="position:absolute;top:0;left:0;width:100%;height:100%;display:flex;align-items:center;justify-content:center">
          <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="opacity:.3"><rect x="4" y="4" width="16" height="16" rx="2"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="12" y1="4" x2="12" y2="20"/></svg>
        </div>
        <?php endif; ?>
        <span style="position:absolute;bottom:8px;right:8px;background:rgba(0,0,0,0.7);color:#fff;font-size:.75rem;font-weight:600;padding:4px 8px;border-radius:4px"><?= (int)$pl['item_count'] ?> videos</span>
      </div>
      <div style="flex:1;display:flex;flex-direction:column;padding:12px">
        <div style="font-weight:700;font-size:.9rem;margin-bottom:4px;color:var(--text);word-break:break-word"><?= e($pl['name']) ?></div>
        <?php if ($pl['description']): ?>
        <div style="font-size:.8rem;color:var(--text2);margin-bottom:8px;line-height:1.3;word-break:break-word"><?= e(truncate($pl['description'], 80)) ?></div>
        <?php endif; ?>
        <div style="margin-top:auto;display:flex;align-items:center;justify-content:space-between;gap:8px">
          <span style="font-size:.75rem;color:var(--text2)"><?= date('M j, Y', strtotime($pl['created_at'])) ?></span>
          <?php if ($is_owner): ?>
          <button class="playlist-delete-btn" data-playlist-id="<?= $pl['id'] ?>" style="background:none;border:none;color:var(--text2);cursor:pointer;font-size:1rem;padding:0;display:flex;align-items:center" title="Delete playlist" onclick="event.stopPropagation()">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
          </button>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div style="text-align:center;padding:60px 24px;color:var(--text2)">
    <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 12px;opacity:.4"><rect x="4" y="4" width="16" height="16" rx="2"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="12" y1="4" x2="12" y2="20"/></svg>
    <p style="font-size:.95rem;margin-bottom:4px">No playlists yet</p>
    <?php if ($is_owner): ?>
    <p class="text-muted text-sm">Create your first playlist to organize videos.</p>
    <?php else: ?>
    <p class="text-muted text-sm">This creator hasn't made any playlists.</p>
    <?php endif; ?>
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
const IS_LOGGED_IN = <?= is_logged_in() ? 'true' : 'false' ?>;
function requireLogin() {
  if (!IS_LOGGED_IN) {
    if (confirm("You need to login to perform this action. Do you want to login now?")) {
      window.location.href = '<?= BASE_URL ?>/auth/login.php';
    }
    return false;
  }
  return true;
}

document.querySelectorAll('.sub-trigger-btn').forEach(btn => {
  btn.addEventListener('click', async function(e) {
    if (e) { e.preventDefault(); e.stopPropagation(); }
    if (!requireLogin()) return;
    const res = await fetch('<?= BASE_URL ?>/api/videos.php?action=subscribe', {
      method: 'POST', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({channel_id: <?= $channel_id ?>})
    });
    const d = await res.json();
    document.querySelectorAll('.sub-trigger-btn').forEach(b => {
      b.textContent = d.subscribed ? 'Subscribed ✓' : 'Subscribe';
      if (d.subscribed) {
        b.classList.remove('btn-primary');
        b.classList.add('btn-subscribed');
      } else {
        b.classList.remove('btn-subscribed');
        b.classList.add('btn-primary');
      }
    });
  });
});

// Playlist management
const createPlaylistBtn = document.getElementById('create-playlist-btn');
if (createPlaylistBtn) {
  createPlaylistBtn.addEventListener('click', async function(e) {
    e.preventDefault();
    const name = prompt('Playlist name:');
    if (!name) return;
    const res = await fetch('<?= BASE_URL ?>/api/videos.php?action=create_playlist', {
      method: 'POST', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({name: name, description: ''})
    });
    const d = await res.json();
    if (d.success) {
      location.reload();
    } else {
      alert('Error: ' + (d.message || 'Failed to create playlist'));
    }
  });
}

document.querySelectorAll('.playlist-delete-btn').forEach(btn => {
  btn.addEventListener('click', async function(e) {
    e.preventDefault();
    e.stopPropagation();
    if (!confirm('Delete this playlist?')) return;
    const playlistId = this.getAttribute('data-playlist-id');
    const res = await fetch('<?= BASE_URL ?>/api/videos.php?action=delete_playlist', {
      method: 'POST', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({playlist_id: playlistId})
    });
    const d = await res.json();
    if (d.success) {
      location.reload();
    } else {
      alert('Error: ' + (d.message || 'Failed to delete playlist'));
    }
  });
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
