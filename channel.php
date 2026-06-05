<?php
// Channel / Creator Profile Page
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$channel_id = (int)($_GET['id'] ?? 0);
if (!$channel_id) { redirect(BASE_URL . '/'); }

// Handle POST actions (like deleting a video or reel)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_logged_in() && verify_csrf($_POST['csrf'] ?? '')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'delete_video') {
        $vid = (int)($_POST['video_id'] ?? 0);
        $video = db_fetch("SELECT id, user_id, video_url, thumbnail FROM videos WHERE id=?", [$vid]);
        if ($video && ($video['user_id'] == auth_user()['id'] || auth_user()['role'] === 'admin')) {
            if ($video['video_url'] && !str_starts_with($video['video_url'], 'http')) {
                @unlink(VIDEO_PATH . $video['video_url']);
            }
            if ($video['thumbnail'] && !str_starts_with($video['thumbnail'], 'http')) {
                @unlink(THUMB_PATH . $video['thumbnail']);
            }
            db_query("DELETE FROM videos WHERE id=?", [$vid]);
            redirect(BASE_URL . '/channel.php?id=' . $channel_id . '&tab=videos');
        } else {
            // Try deleting from reels
            $reel = db_fetch("SELECT id, user_id, video_url FROM reels WHERE id=?", [$vid]);
            if ($reel && ($reel['user_id'] == auth_user()['id'] || auth_user()['role'] === 'admin')) {
                if ($reel['video_url'] && !str_starts_with($reel['video_url'], 'http')) {
                    @unlink(REEL_PATH . $reel['video_url']);
                }
                db_query("DELETE FROM reels WHERE id=?", [$vid]);
                redirect(BASE_URL . '/channel.php?id=' . $channel_id . '&tab=reels');
            }
        }
    }
}

$channel = db_fetch("SELECT * FROM users WHERE id=? AND status='active'", [$channel_id]);
$is_self = is_logged_in() && auth_user()['id'] == $channel_id;

$total_videos = db_count('videos', "user_id=? AND is_reel=0" . ($is_self ? "" : " AND status='published' AND visibility='public'"), [$channel_id]);
$total_reels = db_count('reels', "user_id=?" . ($is_self ? "" : " AND status='published'"), [$channel_id]);
$has_videos = ($total_videos + $total_reels) > 0;

if (!$channel || $channel['role'] === 'viewer' || (!$is_self && !$has_videos && !in_array($channel['role'],['creator','admin']))) {
    if ($channel && $is_self && $channel['role'] === 'viewer') {
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }
    http_response_code(404); die('Channel not found');
}

$tab  = $_GET['tab'] ?? 'videos';
$sort = $_GET['sort'] ?? 'latest';
$page = max(1,(int)($_GET['page']??1));

$order_col = match($sort) { 'views'=>'views', 'oldest'=>'created_at', default=>'created_at' };
$order_dir = ($sort === 'oldest') ? 'ASC' : 'DESC';

$is_owner = is_logged_in() && auth_user()['id'] == $channel_id;
$is_owner_or_admin = is_logged_in() && ($is_owner || auth_user()['role'] === 'admin');
$is_reel = ($tab === 'reels') ? 1 : 0;

if ($is_reel) {
    if ($is_owner) {
        $total  = db_count('reels', "user_id=?", [$channel_id]);
        $videos = db_fetchAll(
            "SELECT r.*, u.username, u.channel_name, u.avatar, 1 AS is_reel
             FROM reels r
             JOIN users u ON u.id = r.user_id
             WHERE r.user_id = ?
             ORDER BY r.$order_col $order_dir LIMIT 52", [$channel_id]
        );
    } else {
        $total  = db_count('reels', "user_id=? AND status='published'", [$channel_id]);
        $videos = db_fetchAll(
            "SELECT r.*, u.username, u.channel_name, u.avatar, 1 AS is_reel
             FROM reels r JOIN users u ON u.id = r.user_id
             WHERE r.user_id=? AND r.status='published'
             ORDER BY r.$order_col $order_dir LIMIT 52", [$channel_id]
        );
    }
} else {
    if ($is_owner) {
        $total  = db_count('videos', "user_id=? AND is_reel=0", [$channel_id]);
        $videos = db_fetchAll(
            "SELECT v.*, u.username, u.channel_name, u.avatar
             FROM videos v
             JOIN users u ON u.id = v.user_id
             WHERE v.user_id = ? AND v.is_reel = 0
             ORDER BY v.$order_col $order_dir LIMIT 52", [$channel_id]
        );
    } else {
        $total  = db_count('videos', "user_id=? AND status='published' AND visibility='public' AND is_reel=0", [$channel_id]);
        $videos = db_fetchAll(
            "SELECT v.*, u.username, u.channel_name, u.avatar
             FROM videos v JOIN users u ON u.id = v.user_id
             WHERE v.user_id=? AND v.status='published' AND v.visibility='public' AND v.is_reel = 0
             ORDER BY v.$order_col $order_dir LIMIT 52", [$channel_id]
        );
    }
}

$is_subscribed = false;
if (is_logged_in()) {
    $is_subscribed = (bool)db_fetch("SELECT id FROM subscriptions WHERE subscriber_id=? AND channel_id=?", [auth_user()['id'],$channel_id]);
}

$earningsMap = [];

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
      <?php elseif ($is_owner && (is_creator() || is_admin())): ?>
      <div class="mobile-only" style="flex-direction:column; gap:6px; width:100%;">
        <a href="<?= BASE_URL ?>/creator/upload.php" class="btn btn-primary" style="display: inline-flex; align-items: center; justify-content: center; text-decoration: none">
          Upload Video
        </a>
        <a href="<?= BASE_URL ?>/creator/upload.php?mode=reel" class="btn btn-outline" style="display: inline-flex; align-items: center; justify-content: center; text-decoration: none">
          Upload Reel
        </a>
      </div>
      <?php endif; ?>
    </div>
    
    <div class="channel-header-info">
      <div class="channel-title-row">
        <h1 class="channel-name-title"><?= e($channel['channel_name'] ?: $channel['username']) ?></h1>
        <div class="channel-meta-stats text-muted text-sm" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
          <span class="stat-subscribers">Subscriber <?= format_number((int)$channel['subscribers']) ?>.</span>
          <span class="stat-videos">Videos <?= $total_videos ?>.</span>
          <span class="stat-reels">Reels <?= $total_reels ?>.</span>
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
      <?php elseif ($is_owner && (is_creator() || is_admin())): ?>
      <div style="display:flex; gap:8px;">
        <a href="<?= BASE_URL ?>/creator/upload.php" class="btn btn-primary" style="display: inline-flex; align-items: center; justify-content: center; text-decoration: none">
          Upload Video
        </a>
        <a href="<?= BASE_URL ?>/creator/upload.php?mode=reel" class="btn btn-outline" style="display: inline-flex; align-items: center; justify-content: center; text-decoration: none">
          Upload Reel
        </a>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Tabs -->
  <div class="channel-tabs">
    <a href="?id=<?= $channel_id ?>&tab=videos" class="channel-tab <?= $tab==='videos'?'active':'' ?>">Videos</a>
    <a href="?id=<?= $channel_id ?>&tab=reels" class="channel-tab <?= $tab==='reels'?'active':'' ?>">Reels</a>
    <a href="?id=<?= $channel_id ?>&tab=playlists" class="channel-tab <?= $tab==='playlists'?'active':'' ?>">Playlists</a>
    <a href="?id=<?= $channel_id ?>&tab=about" class="channel-tab <?= $tab==='about'?'active':'' ?>">About</a>
    
    <!-- Desktop sorting (hidden on mobile) -->
    <div class="channel-sort channel-sort-desktop">
      <?php foreach(['latest'=>'Latest','views'=>'Popular','oldest'=>'Oldest'] as $s=>$l): ?>
      <a href="?id=<?= $channel_id ?>&tab=<?= e($tab) ?>&sort=<?= $s ?>" class="btn btn-sm <?= $sort===$s?'btn-primary':'btn-outline' ?>"><?= $l ?></a>
      <?php endforeach; ?>
    </div>
    
    <!-- Mobile sorting (dropdown, hidden on desktop) -->
    <div class="channel-sort-mobile">
      <select onchange="location = this.value;" class="sort-dropdown-select" aria-label="Sort videos">
        <?php foreach(['latest'=>'Latest','views'=>'Popular','oldest'=>'Oldest'] as $s=>$l): ?>
        <option value="?id=<?= $channel_id ?>&tab=<?= e($tab) ?>&sort=<?= $s ?>" <?= $sort===$s?'selected':'' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <!-- Videos tab -->
  <?php if ($tab==='videos'): ?>
  <?php if ($videos): ?>
  <div class="grid grid-4" id="video-grid">
    <?php foreach ($videos as $v):
      $card_opts = fh_video_card_opts($v, $earningsMap, $ref);
      if ($is_owner_or_admin) {
          $card_opts['show_delete'] = true;
      }
      echo render_video_card($v, $card_opts);
    endforeach; ?>
  </div>

  <?php if ($total > 52): ?>
  <div style="text-align:center;margin-top:24px">
    <button class="btn btn-outline" id="load-more" data-page="2" data-channel-id="<?= $channel_id ?>" data-sort="<?= e($sort) ?>">Load More</button>
  </div>
  <?php endif; ?>

  <?php else: ?>
  <div style="text-align:center;padding:60px;color:var(--text2)">
    <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 12px;opacity:.4"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
    <p>No videos yet</p>
  </div>
  <?php endif; ?>
  <?php endif; ?>

  <!-- Reels tab -->
  <?php if ($tab==='reels'): ?>
  <?php if ($videos): ?>
  <div class="grid grid-4" id="video-grid" style="grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));">
    <?php foreach ($videos as $v):
      $card_opts = fh_video_card_opts($v, [], $ref);
      if ($is_owner_or_admin) {
          $card_opts['show_delete'] = true;
      }
      echo render_video_card($v, $card_opts);
    endforeach; ?>
  </div>

  <?php if ($total > 52): ?>
  <div style="text-align:center;margin-top:24px">
    <button class="btn btn-outline" id="load-more" data-page="2" data-channel-id="<?= $channel_id ?>" data-sort="<?= e($sort) ?>" data-is-reel="1">Load More</button>
  </div>
  <?php endif; ?>

  <?php else: ?>
  <div style="text-align:center;padding:60px;color:var(--text2)">
    <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 12px;opacity:.4"><rect x="6" y="2" width="12" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
    <p>No Reels yet</p>
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
        "SELECT p.*, 
                (SELECT COUNT(*) FROM playlist_videos pv WHERE pv.playlist_id = p.id) as item_count,
                (SELECT v.thumbnail FROM playlist_videos pv JOIN videos v ON v.id = pv.video_id WHERE pv.playlist_id = p.id ORDER BY pv.sort_order LIMIT 1) as thumbnail
         FROM playlists p
         WHERE p.user_id = ?
         ORDER BY p.created_at DESC",
        [$channel_id]
    );
  ?>
  
  <?php if ($playlists): ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px">
    <?php foreach ($playlists as $pl): ?>
    <div class="card" style="padding:0;overflow:hidden;display:flex;flex-direction:column;cursor:pointer;transition:all 0.2s" onclick="location.href='<?= BASE_URL ?>/playlists.php?id=<?= $pl['id'] ?>'">
      <div style="width:100%;padding-top:56.25%;position:relative;background:linear-gradient(135deg,rgba(99,102,241,.1),rgba(236,72,153,.1))">
        <?php if ($pl['thumbnail']): ?>
        <img src="<?= thumb_url($pl['thumbnail']) ?>" alt="<?= e($pl['title']) ?>" style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover">
        <?php else: ?>
        <div style="position:absolute;top:0;left:0;width:100%;height:100%;display:flex;align-items:center;justify-content:center">
          <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="opacity:.3"><rect x="4" y="4" width="16" height="16" rx="2"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="12" y1="4" x2="12" y2="20"/></svg>
        </div>
        <?php endif; ?>
        <span style="position:absolute;bottom:8px;right:8px;background:rgba(0,0,0,0.7);color:#fff;font-size:.75rem;font-weight:600;padding:4px 8px;border-radius:4px"><?= (int)$pl['item_count'] ?> videos</span>
      </div>
      <div style="flex:1;display:flex;flex-direction:column;padding:12px">
        <div style="font-weight:700;font-size:.9rem;margin-bottom:4px;color:var(--text);word-break:break-word"><?= e($pl['title']) ?></div>
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
        <span>&#128250; <?= $total_videos ?> videos</span>
        <span>·</span>
        <span>&#128241; <?= $total_reels ?> reels</span>
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

// ── Infinite scroll / Load More for Channel Videos ───────────────────
const FH_CREATOR_ID = <?= (is_logged_in() && is_creator()) ? (int)auth_user()['id'] : 0 ?>;
const IS_OWNER_OR_ADMIN = <?= $is_owner_or_admin ? 'true' : 'false' ?>;

function bindLoadMore() {
  const loadMoreBtn = document.getElementById('load-more');
  if (!loadMoreBtn) return;
  
  const newBtn = loadMoreBtn.cloneNode(true);
  loadMoreBtn.parentNode.replaceChild(newBtn, loadMoreBtn);
  
  newBtn.addEventListener('click', async function(){
    const btn = this;
    const page = parseInt(btn.dataset.page);
    const channelId = parseInt(btn.dataset.channelId || 0);
    const sort = encodeURIComponent(btn.dataset.sort || 'latest');
    
    btn.textContent = 'Loading…';
    btn.disabled = true;
    try {
      const isReel = btn.dataset.isReel ? parseInt(btn.dataset.isReel) : 0;
      const res = await fetch(`<?= BASE_URL ?>/api/videos.php?page=${page}&per_page=48&channel_id=${channelId}&sort=${sort}&is_reel=${isReel}`);
      const data = await res.json();
      const grid = document.getElementById('video-grid');
      if (data.videos && data.videos.length) {
        data.videos.forEach(v => {
          const el = document.createElement('article');
          el.className = 'video-card fade-in';
          const watchUrl = v.url || '<?= BASE_URL ?>/watch.php?v=' + encodeURIComponent(v.id);
          const channelUrl = '<?= BASE_URL ?>/channel.php?id=' + v.user_id + '&tab=videos';
          el.onclick = () => location.href = watchUrl;
          const durBadge = v.duration_fmt
            ? `<span class="video-duration">${v.duration_fmt}</span>`
            : `<span class="video-duration video-duration--pending">…</span>`;
          
          let deleteBtnHtml = '';
          if (IS_OWNER_OR_ADMIN) {
            const csrfToken = '<?= csrf_token() ?>';
            const deleteActionUrl = '<?= BASE_URL ?>/channel.php?id=' + encodeURIComponent(v.user_id) + '&tab=videos';
            const editUrl = '<?= BASE_URL ?>/creator/edit.php?id=' + encodeURIComponent(v.id);
            deleteBtnHtml = `
<div class="video-owner-actions" style="position:absolute; top:8px; right:8px; z-index:15; display:flex; gap:6px;" onclick="event.stopPropagation();">
  <a href="${editUrl}" class="btn btn-sm btn-icon" style="background:rgba(0,0,0,0.65); color:#fff; border:none; width:28px; height:28px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; padding:0; cursor:pointer;" title="Edit Video">
    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
  </a>
  <form method="POST" action="${deleteActionUrl}" style="margin:0;" onsubmit="return confirm('Delete this video?');">
    <input type="hidden" name="csrf" value="${csrfToken}">
    <input type="hidden" name="video_id" value="${v.id}">
    <button type="submit" name="action" value="delete_video" class="btn btn-sm btn-icon" style="background:rgba(239,68,68,0.85); color:#fff; border:none; width:28px; height:28px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; padding:0; cursor:pointer;" title="Delete Video">
      <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
    </button>
  </form>
</div>`;
          }

          el.innerHTML = `
            <div class="video-thumb \${v.is_reel ? 'is-portrait' : ''}" style="position:relative">
              \${v.is_reel 
                ? `<video src="\${v.video_src}#t=0.1" muted playsinline preload="metadata" class="thumb-main" style="width:100%; height:100%; object-fit:cover; aspect-ratio:9/16;"></video>`
                : `<img src="\${v.thumbnail}" alt="\${v.title}" loading="lazy" width="320" height="180" class="thumb-main">`
              }
              \${durBadge}
              \${deleteBtnHtml}
            </div>
            <div class="video-card-body">
              <div class="video-card-info-wrap">
                <a href="${channelUrl}" onclick="event.stopPropagation();" style="display:inline-block;flex-shrink:0;border-radius:50%;overflow:hidden;width:44px;height:44px;">
                  <img src="${v.avatar}" alt="${v.channel}" class="video-card-avatar" loading="lazy" width="44" height="44" style="width:100%;height:100%;object-fit:cover">
                </a>
                <div style="min-width:0;">
                  <div class="video-title">${v.title}</div>
                  <div class="video-card-subtitle">
                    <a href="${channelUrl}" onclick="event.stopPropagation();" class="video-card-channel-link">${v.channel}</a>
                  </div>
                </div>
              </div>
              <div class="video-card-stats-row">
                <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>${v.views}</span>
                <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>${v.likes || 0}</span>
                <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M8 9h8M8 13h4"/></svg>${v.comments || 0}</span>
                <span>·</span>
                <span>${v.ago}</span>
              </div>
            </div>`;
          grid.appendChild(el);
        });
        btn.dataset.page = page + 1;
        btn.textContent = 'Load More';
        btn.disabled = false;
        if (!data.has_next) btn.style.display = 'none';
      } else {
        btn.style.display = 'none';
      }
    } catch(e) {
      btn.textContent = 'Load More';
      btn.disabled = false;
    }
  });
}
bindLoadMore();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
