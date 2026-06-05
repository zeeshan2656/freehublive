<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$vid = (int)($_GET['v'] ?? 0);
if (!$vid) { redirect(BASE_URL . '/'); }

// Redirect to reels page if the ID belongs to a reel
$reel = db_fetch("SELECT id FROM reels WHERE id = ?", [$vid]);
if ($reel) {
    redirect(BASE_URL . '/reels.php?id=' . $vid);
}

$video = db_fetch(
    "SELECT v.*,u.username,u.channel_name,u.avatar,u.subscribers,u.bio
     FROM videos v JOIN users u ON u.id=v.user_id
     WHERE v.id=? AND v.status='published' AND v.visibility='public'", [$vid]
);
if (!$video) { http_response_code(404); die('Video not found'); }
$is_xhr_view = isset($_GET['xhr_view']);


$playlist_id = (int)($_GET['list'] ?? 0);
$playlist = null;
$playlist_videos = [];
$next_video_id = null;
$prev_video_id = null;
$current_video_index = -1;

if ($playlist_id > 0) {
    $playlist = db_fetch("SELECT p.*, u.username, u.channel_name FROM playlists p JOIN users u ON u.id = p.user_id WHERE p.id = ?", [$playlist_id]);
    if ($playlist) {
        $is_playlist_owner = is_logged_in() && (auth_user()['id'] == $playlist['user_id'] || auth_user()['role'] === 'admin');
        $uid = is_logged_in() ? auth_user()['id'] : 0;
        
        if ($playlist['visibility'] !== 'private' || $is_playlist_owner) {
            $playlist_videos = db_fetchAll(
                "SELECT pv.video_id, v.title, v.thumbnail, v.duration, v.is_reel, u.username, u.channel_name
                 FROM playlist_videos pv
                 JOIN videos v ON v.id = pv.video_id
                 JOIN users u ON u.id = v.user_id
                 WHERE pv.playlist_id = ? AND (v.visibility = 'public' OR v.user_id = ? OR ? = 1)
                 ORDER BY pv.sort_order ASC",
                [$playlist_id, $uid, is_logged_in() && auth_user()['role'] === 'admin' ? 1 : 0]
            );
            
            foreach ($playlist_videos as $idx => $pv) {
                if ((int)$pv['video_id'] === $vid) {
                    $current_video_index = $idx;
                    break;
                }
            }
            
            if ($current_video_index !== -1) {
                if (isset($playlist_videos[$current_video_index + 1])) {
                    $next_video_id = (int)$playlist_videos[$current_video_index + 1]['video_id'];
                }
                if (isset($playlist_videos[$current_video_index - 1])) {
                    $prev_video_id = (int)$playlist_videos[$current_video_index - 1]['video_id'];
                }
            }
        } else {
            $playlist = null;
        }
    }
}


// Track view
$ip   = hash_ip(get_ip());
$aff  = get_ref_code();
$affId = null;
if ($aff) {
    $affUser = db_fetch("SELECT id FROM users WHERE ref_code=?", [$aff]);
    $affId = $affUser['id'] ?? null;
}
$viewRow = db_fetch(
    "SELECT id FROM video_views WHERE video_id=? AND ip_hash=? ORDER BY id DESC LIMIT 1",
    [$vid, $ip]
);
if (!$viewRow) {
    $view_session_id = db_insert('video_views', [
        'video_id'     => $vid,
        'user_id'      => auth_user()['id'] ?? null,
        'affiliate_id' => $affId,
        'ip_hash'      => $ip,
        'ref_code'     => $aff,
        'device'       => detect_device(),
        'is_unique'    => 1,
    ]);
    db_update('videos', ['views' => $video['views']+1], 'id=?', [$vid]);
} elseif ((int)($video['is_reel'] ?? 0) === 1) {
    $view_session_id = db_insert('video_views', [
        'video_id'     => $vid,
        'user_id'      => auth_user()['id'] ?? null,
        'affiliate_id' => $affId,
        'ip_hash'      => $ip,
        'ref_code'     => $aff,
        'device'       => detect_device(),
        'is_unique'    => 0,
    ]);
    db_update('videos', ['views' => $video['views']+1], 'id=?', [$vid]);
}

if ($is_xhr_view) {
    header('Content-Type: application/json');
    $current_views = db_fetch("SELECT views FROM videos WHERE id=?", [$vid])['views'];
    echo json_encode([
        'success'   => true,
        'views'     => (int)$current_views,
        'formatted' => format_number((int)$current_views)
    ]);
    exit;
}

// Related (optimized indexed UNION query)
$related = db_fetchAll_cached(
    "(SELECT v.*,u.username,u.channel_name,u.avatar FROM videos v
      JOIN users u ON u.id=v.user_id
      WHERE v.id!=? AND v.status='published' AND v.visibility='public' AND v.category_id=?
      ORDER BY v.views DESC LIMIT 12)
     UNION
     (SELECT v.*,u.username,u.channel_name,u.avatar FROM videos v
      JOIN users u ON u.id=v.user_id
      WHERE v.id!=? AND v.status='published' AND v.visibility='public'
      AND EXISTS (SELECT 1 FROM video_categories vc WHERE vc.video_id = v.id AND vc.category_id = ?)
      ORDER BY v.views DESC LIMIT 12)
     UNION
     (SELECT v.*,u.username,u.channel_name,u.avatar FROM videos v
      JOIN users u ON u.id=v.user_id
      WHERE v.id!=? AND v.status='published' AND v.visibility='public'
      ORDER BY v.views DESC LIMIT 12)
     LIMIT 12",
    [$vid, $video['category_id'], $vid, $video['category_id'], $vid],
    60
);


// User reaction
$user_reaction = null;
if (is_logged_in()) {
    $r = db_fetch("SELECT type FROM video_reactions WHERE video_id=? AND user_id=?", [$vid, auth_user()['id']]);
    $user_reaction = $r['type'] ?? null;
}

$meta_title = $video['title'] . ' — ' . setting('site_name','FreeHub');
$meta_desc  = truncate(strip_tags($video['description'] ?? ''), 160);
$meta_image = thumb_url($video['thumbnail']);
$is_watch = true;

// Build preload hint for local video files (speeds up first-frame time)
$_fh_video_preload_url = '';
$_fh_is_local_video = false;
if (!empty($video['video_url']) && !str_starts_with($video['video_url'], 'http')) {
    // Use the streaming endpoint for proper byte-range support
    $_fh_video_preload_url = BASE_URL . '/api/stream.php?v=' . (int)$vid;
    $_fh_is_local_video = true;
}

require_once __DIR__ . '/includes/header.php';

// Emit <link rel="preload"> AFTER header (so it goes into the <head> via output buffer)
if ($_fh_is_local_video && $_fh_video_preload_url) {
    echo '<link rel="preload" href="' . e($_fh_video_preload_url) . '" as="video" fetchpriority="high">' . "\n";
}
?>

<div class="layout">
  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
  <main class="main-content watch-page">
    <div class="container">
      <div class="watch-layout">

    <!-- ── Player Column ── -->
    <div class="player-column">
      <!-- Player -->
      <div class="player-wrapper" id="player-wrapper">
        <?php
        $video_url = $video['video_url'] ?? '';
        $is_external = str_starts_with($video_url, 'http://') || str_starts_with($video_url, 'https://');
        $yt_id = fh_youtube_id($video_url);

        $is_direct_video = false;
        if ($is_external) {
            $path = parse_url($video_url, PHP_URL_PATH);
            if ($path) {
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                if (in_array($ext, ['mp4', 'webm', 'mov', 'ogg', 'm3u8', 'mp3'])) {
                    $is_direct_video = true;
                }
            }
        }

        if ($yt_id):
        ?>
          <iframe id="fh-youtube-player" width="100%" height="100%"
                  src="https://www.youtube.com/embed/<?= e($yt_id) ?>?autoplay=1&enablejsapi=1"
                  frameborder="0"
                  allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                  allowfullscreen style="width:100%;height:100%;border:none;display:block"></iframe>
        <?php elseif ($is_external && !$is_direct_video): ?>
          <iframe id="fh-embed-player" width="100%" height="100%"
                  src="<?= BASE_URL ?>/embed_proxy.php?url=<?= urlencode($video_url) ?>"
                  frameborder="0"
                  allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                  allowfullscreen style="width:100%;height:100%;border:none;display:block"></iframe>
        <?php else: ?>
          <video id="fh-player" playsinline preload="auto"
                 poster="<?= thumb_url($video['thumbnail']) ?>"
                 style="width:100%;height:100%;display:block"
                 fetchpriority="high">
            <?php if ($video['hls_url']): ?>
              <source src="<?= e($video['hls_url']) ?>" type="application/x-mpegURL">
            <?php endif; ?>
            <?php if ($_fh_is_local_video): ?>
              <!-- Use streaming endpoint for byte-range support (seek without re-download) -->
              <source src="<?= e($_fh_video_preload_url) ?>" type="video/mp4">
            <?php else: ?>
              <source src="<?= video_url($video['video_url']) ?>" type="video/mp4">
            <?php endif; ?>
            Your browser does not support video playback.
          </video>
          
          <!-- Big Centered Controls Overlay -->
          <div class="player-overlay-center" id="overlay-center">
            <button class="overlay-center-btn" id="overlay-skip-back" aria-label="Skip back 10s">
              <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M1 4v6h6"/><path d="M3.51 15a9 9 0 1 0 .49-4.5"/></svg>
            </button>
            <button class="overlay-center-btn play-pause" id="overlay-play-btn" aria-label="Play/Pause">
              <svg id="overlay-play-icon" width="32" height="32" fill="currentColor" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
            </button>
            <button class="overlay-center-btn" id="overlay-skip-fwd" aria-label="Skip forward 10s">
              <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M23 4v6h-6"/><path d="M20.49 15a9 9 0 1 1-.49-4.5"/></svg>
            </button>
          </div>

          <!-- Custom Controls Bottom Bar -->
          <div class="player-controls" id="ctrl">
            <div class="progress-bar-container" id="progress-container">
              <div class="progress-bar" id="progress" role="slider" aria-label="Video progress">
                <div class="progress-fill" id="progress-fill" style="width:0%"></div>
                <div class="progress-thumb" id="progress-thumb" style="left:0%"></div>
              </div>
            </div>
            
            <div class="ctrl-row">
              <div class="ctrl-left">
                <button class="ctrl-btn" id="play-btn" aria-label="Play/Pause">
                  <svg id="play-icon" width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                </button>
                <button class="ctrl-btn desktop-only" id="skip-back" aria-label="Skip back 10s">
                  <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 4v6h6"/><path d="M3.51 15a9 9 0 1 0 .49-4.5"/></svg>
                </button>
                <button class="ctrl-btn desktop-only" id="skip-fwd" aria-label="Skip forward 10s">
                  <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M23 4v6h-6"/><path d="M20.49 15a9 9 0 1 1-.49-4.5"/></svg>
                </button>
                
                <div class="volume-container desktop-only">
                  <button class="ctrl-btn" id="mute-btn" aria-label="Mute/Unmute">
                    <svg id="volume-icon" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 5L6 9H2v6h4l5 4V5z"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
                  </button>
                  <input type="range" class="volume-slider" id="volume" min="0" max="1" step="0.05" value="1" aria-label="Volume">
                </div>
                
                <span class="time-display" id="time-display">0:00 / <?= format_duration((int)$video['duration']) ?></span>
              </div>
              
              <div class="ctrl-right">
                <select id="speed-select" class="speed-selector" aria-label="Playback speed">
                  <option value="0.5">0.5x</option>
                  <option value="1" selected>1x</option>
                  <option value="1.25">1.25x</option>
                  <option value="1.5">1.5x</option>
                  <option value="2">2x</option>
                </select>
                <button class="ctrl-btn desktop-only" id="mini-btn" aria-label="Mini player">
                  <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="2"/><rect x="12" y="12" width="8" height="8" rx="1"/></svg>
                </button>
                <button class="ctrl-btn" id="fullscreen-btn" aria-label="Fullscreen">
                  <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
                </button>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- Ad Placeholder: Below Player -->
      <?php
      $below_ad_html = render_ad_placeholder('watch_below_player');
      if (!empty($below_ad_html)):
      ?>
      <div class="watch-below-player-wrapper">
        <?= $below_ad_html ?>
      </div>
      <?php endif; ?>

      <!-- Video Info -->
      <div class="watch-info">
        <h1 style="font-size:1.25rem;font-weight:700;margin-bottom:8px;line-height:1.3"><?= e($video['title']) ?></h1>
        <div class="watch-actions-row">
          <div class="flex gap-2 text-muted text-sm">
            <span><?= format_number((int)$video['views']) ?> views</span>
            <span>·</span>
            <span><?= time_ago($video['published_at'] ?? $video['created_at']) ?></span>
          </div>
          <div class="watch-action-btns">
            <!-- Like -->
            <button class="btn btn-outline btn-sm" id="like-btn" data-id="<?= $vid ?>"
                    style="<?= $user_reaction==='like'?'background:rgba(99,102,241,.15);color:var(--accent)':'' ?>">
              <svg width="16" height="16" fill="<?= $user_reaction==='like'?'var(--accent)':'none' ?>" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3z"/><path d="M7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>
              <span id="like-count"><?= format_number((int)$video['likes']) ?></span>
            </button>
            <!-- Dislike -->
            <button class="btn btn-outline btn-sm" id="dislike-btn" data-id="<?= $vid ?>"
                    style="<?= $user_reaction==='dislike'?'background:rgba(99,102,241,.15);color:var(--accent)':'' ?>">
              <svg width="16" height="16" fill="<?= $user_reaction==='dislike'?'var(--accent)':'none' ?>" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3z"/><path d="M17 2h2.67A2.31 2.31 0 0 1 22 4v7a2.31 2.31 0 0 1-2.33 2H17"/></svg>
            </button>
            <!-- Share -->
            <button class="btn btn-outline btn-sm" id="share-btn">
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
              Share
            </button>
            <!-- Watch Later -->
            <?php $is_saved = is_logged_in() ? (bool)db_fetch("SELECT id FROM watch_later WHERE user_id=? AND video_id=?", [auth_user()['id'], $vid]) : false; ?>
            <button class="btn btn-outline btn-sm" id="wl-btn" data-id="<?= $vid ?>"
                    style="<?= $is_saved?'background:rgba(99,102,241,.15);color:var(--accent)':'' ?>">
              <svg width="16" height="16" fill="<?= $is_saved?'var(--accent)':'none' ?>" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
              <span><?= $is_saved ? 'Saved ✓' : 'Save' ?></span>
            </button>
            <?php if (is_logged_in() && (auth_user()['id'] == $video['user_id'] || auth_user()['role'] === 'admin')): ?>
            <a href="<?= BASE_URL ?>/creator/<?= $video['is_reel'] ? 'edit_reel.php' : 'edit.php' ?>?id=<?= $vid ?>" class="btn btn-outline btn-sm" style="color:var(--accent);border-color:var(--accent)">
              &#9998; <?= $video['is_reel'] ? 'Edit Reel' : 'Edit Video' ?>
            </a>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Channel Info -->
      <div class="watch-channel-row">
        <a href="<?= BASE_URL ?>/channel.php?id=<?= $video['user_id'] ?>&tab=videos" class="flex gap-3">
          <img src="<?= avatar_url($video['avatar']) ?>" alt="<?= e($video['channel_name']??$video['username']) ?>"
               class="avatar avatar-lg" width="64" height="64" loading="lazy">
          <div>
            <div style="font-weight:700;font-size:1rem"><?= e($video['channel_name']??$video['username']) ?></div>
            <div class="text-muted text-sm"><?= format_number((int)$video['subscribers']) ?> subscribers</div>
          </div>
        </a>
        <?php if (!is_logged_in() || auth_user()['id'] != $video['user_id']): ?>
        <?php $is_subbed = is_logged_in() ? (bool)db_fetch("SELECT id FROM subscriptions WHERE subscriber_id=? AND channel_id=?", [auth_user()['id'], $video['user_id']]) : false; ?>
        <button class="btn <?= $is_subbed ? 'btn-subscribed' : 'btn-primary' ?>" id="sub-btn" data-channel="<?= $video['user_id'] ?>"><?= $is_subbed ? 'Subscribed ✓' : 'Subscribe' ?></button>
        <?php endif; ?>
      </div>

      <!-- Description -->
      <?php if ($video['description']): ?>
      <div class="watch-desc" style="margin:16px 0;padding:16px;background:var(--bg2);border-radius:var(--radius);font-size:.9rem;line-height:1.7;color:var(--text2)" id="desc-box">
        <div id="desc-text" style="display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden">
          <?= nl2br(e($video['description'])) ?>
        </div>
        <button style="color:var(--accent);font-size:.82rem;font-weight:600;margin-top:8px" onclick="
          const d=document.getElementById('desc-text');
          d.style.webkitLineClamp=d.style.webkitLineClamp?'':'3';
          this.textContent=d.style.webkitLineClamp?'Show more':'Show less'
        ">Show more</button>
      </div>
      <?php endif; ?>

      <!-- Comments -->
      <div class="watch-comments" style="margin-top:24px">
        <h2 style="font-size:1rem;font-weight:700;margin-bottom:16px"><?= format_number((int)$video['comments_count']) ?> Comments</h2>
        <form id="comment-form" class="comment-form-row">
          <img src="<?= is_logged_in() ? avatar_url(auth_user()['avatar']) : avatar_url(null) ?>" class="avatar avatar-sm" width="32" height="32">
          <div style="flex:1">
            <input type="text" class="form-input" placeholder="Add a comment…" id="comment-input" style="border-radius:20px" maxlength="500">
          </div>
          <button type="submit" class="btn btn-primary" style="border-radius:20px; padding: 10px 24px;">Post</button>
          <input type="hidden" name="video_id" value="<?= $vid ?>">
        </form>
        <div id="comments-list"></div>
        <button class="btn btn-outline" id="load-comments" style="margin-top:12px">Load Comments</button>
        <button class="btn btn-outline" id="close-comments" style="margin-top:12px; display:none;">Close Comments</button>
      </div>
    </div>

    <!-- ── Related Column ── -->
    <aside class="player-sidebar" aria-label="Related videos">
      <!-- Ad Placeholder: Watch Page Sidebar -->
      <div style="margin-bottom: 20px; padding: 0 16px;">
        <?= render_ad_placeholder('watch_sidebar') ?>
      </div>
      <?php if ($playlist): ?>
      <div class="playlist-watch-panel" style="margin: 0 16px 20px; border: 1px solid var(--border); border-radius: 12px; overflow: hidden; background: var(--bg2); box-shadow: 0 4px 15px rgba(0,0,0,0.15)">
        <div style="padding: 16px; background: linear-gradient(135deg, rgba(99,102,241,0.12), rgba(139,92,246,0.06)); border-bottom: 1px solid var(--border)">
          <div style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: var(--accent); letter-spacing: 0.05em; margin-bottom: 4px">Playing from Playlist</div>
          <a href="<?= BASE_URL ?>/playlists.php?id=<?= $playlist['id'] ?>" style="display: block; font-weight: 700; font-size: 0.95rem; color: var(--text); line-height: 1.3; margin-bottom: 6px; transition: color 0.2s">
            <?= e($playlist['title']) ?>
          </a>
          <div style="font-size: 0.8rem; color: var(--text2)">
            <span><?= e($playlist['channel_name'] ?? $playlist['username'] ?? 'Creator') ?> · <?= ($current_video_index + 1) ?> / <?= count($playlist_videos) ?></span>
          </div>
        </div>
        <div class="playlist-watch-items" style="max-height: 280px; overflow-y: auto; display: flex; flex-direction: column">
          <?php foreach ($playlist_videos as $idx => $pv):
            $is_current = ((int)$pv['video_id'] === $vid);
            $pv_thumb = thumb_url($pv['thumbnail']);
          ?>
            <a href="<?= BASE_URL ?>/watch.php?v=<?= $pv['video_id'] ?>&list=<?= $playlist['id'] ?>" 
               class="playlist-watch-item"
               style="display: flex; gap: 10px; padding: 10px 16px; align-items: center; text-decoration: none; border-bottom: 1px solid rgba(255,255,255,0.03); transition: background 0.15s; background: <?= $is_current ? 'rgba(99,102,241,0.1)' : 'transparent' ?>">
              
              <div style="font-size: 0.75rem; font-weight: 700; color: <?= $is_current ? 'var(--accent)' : 'var(--text3)' ?>; width: 18px; text-align: center; flex-shrink: 0">
                <?php if ($is_current): ?>
                  ▶
                <?php else: ?>
                  <?= $idx + 1 ?>
                <?php endif; ?>
              </div>

              <?php $is_pv_portrait = (int)($pv['is_reel'] ?? 0) === 1; ?>
              <div class="playlist-video-thumb-wrapper<?= $is_pv_portrait ? ' is-portrait' : '' ?>" style="width: 80px; aspect-ratio: <?= $is_pv_portrait ? '9/16' : '16/9' ?>; border-radius: 4px; overflow: hidden; position: relative; flex-shrink: 0; background: var(--bg3)">
                <img src="<?= $pv_thumb ?>" alt="<?= e($pv['title']) ?>" style="width:100%; height:100%; object-fit: <?= $is_pv_portrait ? 'contain' : 'cover' ?>">
                <span style="position: absolute; bottom: 2px; right: 2px; background: rgba(0,0,0,0.8); color: #fff; font-size: 0.6rem; font-weight: 600; padding: 0.5px 3px; border-radius: 2px">
                  <?= format_duration((int)$pv['duration']) ?>
                </span>
              </div>

              <div style="min-width: 0; flex: 1">
                <div style="font-size: 0.8rem; font-weight: 600; color: <?= $is_current ? 'var(--accent)' : 'var(--text)' ?>; line-height: 1.25; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 2px">
                  <?= e($pv['title']) ?>
                </div>
                <div style="font-size: 0.72rem; color: var(--text2)">
                  <?= e($pv['channel_name'] ?? $pv['username']) ?>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      <style>
        .playlist-watch-items::-webkit-scrollbar { width: 4px; }
        .playlist-watch-items::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }
        .playlist-watch-item:hover { background: rgba(255, 255, 255, 0.03) !important; }
        .playlist-watch-item.active:hover { background: rgba(99, 102, 241, 0.12) !important; }
      </style>
      <?php endif; ?>

      <h2 style="font-size:.95rem;font-weight:700;margin-bottom:12px;padding:0 16px">Up Next</h2>
      <!-- Ad Placeholder: Watch Page Up Next -->
      <div style="margin-bottom: 20px; padding: 0 16px;">
        <?= render_ad_placeholder('watch_up_next') ?>
      </div>
      <?php foreach ($related as $r):
        $is_r_portrait = (int)($r['is_reel'] ?? 0) === 1;
      ?>
      <a href="<?= BASE_URL ?>/watch.php?v=<?= $r['id'] ?>" class="related-video-item">
        <div class="related-thumb<?= $is_r_portrait ? ' is-portrait' : '' ?>" style="position:relative">
          <img src="<?= thumb_url($r['thumbnail']) ?>" alt="<?= e($r['title']) ?>"
               loading="lazy" width="168" height="94">
          <span style="position:absolute;bottom:4px;right:4px;background:rgba(0,0,0,.8);color:#fff;font-size:.68rem;font-weight:600;padding:1px 5px;border-radius:3px">
            <?= format_duration((int)$r['duration']) ?>
          </span>
        </div>
        <div style="min-width:0">
          <div style="font-size:.82rem;font-weight:600;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:4px"><?= e($r['title']) ?></div>
          <div class="text-muted" style="font-size:.75rem"><?= e($r['channel_name']??$r['username']) ?></div>
          <div class="text-muted" style="font-size:.75rem"><?= format_number((int)$r['views']) ?> views</div>
        </div>
      </a>
      <?php endforeach; ?>
    </aside>
      </div>
    </div>
  </main>
</div>

<!-- Share Modal -->
<div class="modal-backdrop" id="share-modal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Share Video</span>
      <button class="btn-icon" onclick="document.getElementById('share-modal').classList.remove('open')">&#x2715;</button>
    </div>
    <div class="modal-body">
      <?php if (auth_user()): ?>
      <p class="text-sm text-muted" style="margin-bottom:12px">Your affiliate link (earns you money when shared):</p>
      <div class="flex gap-2">
        <input class="form-input" id="share-url" value="<?= BASE_URL ?>/watch.php?v=<?= $vid ?>&ref=<?= auth_user()['ref_code'] ?? '' ?>" readonly>
        <button class="btn btn-primary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('share-url').value);this.textContent='Copied!'">Copy</button>
      </div>
      <?php else: ?>
      <div class="flex gap-2">
        <input class="form-input" id="share-url" value="<?= BASE_URL ?>/watch.php?v=<?= $vid ?>" readonly>
        <button class="btn btn-primary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('share-url').value);this.textContent='Copied!'">Copy</button>
      </div>
      <p class="text-sm text-muted" style="margin-top:12px"><a href="<?= BASE_URL ?>/auth/register.php" style="color:var(--accent)">Join affiliate program</a> to earn from shares.</p>
      <?php endif; ?>
    </div>
  </div>
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

const player = document.getElementById('fh-player');
const progressFill = document.getElementById('progress-fill');
const progressThumb = document.getElementById('progress-thumb');
const timeDisplay = document.getElementById('time-display');
const playIcon = document.getElementById('play-icon');
const vidDuration = <?= (int)$video['duration'] ?>;
const FH_VIDEO_ID = <?= (int)$vid ?>;

function fmtTime(s){
  s=Math.floor(s);
  const m=Math.floor(s/60),sec=s%60;
  return m+':'+(sec<10?'0':'')+sec;
}

if (player) {
  const wrapper = document.getElementById('player-wrapper');
  const overlayPlayIcon = document.getElementById('overlay-play-icon');
  const muteBtn = document.getElementById('mute-btn');
  const volumeIcon = document.getElementById('volume-icon');
  const volumeSlider = document.getElementById('volume');
  const progressContainer = document.getElementById('progress-container');
  const progressEl = document.getElementById('progress');
  
  let controlsTimeout = null;
  let isDraggingProgress = false;
  let lastVolume = 1;

  function showControls() {
    wrapper.classList.remove('controls-hidden');
    resetControlsTimeout();
  }

  function hideControls() {
    if (!player.paused && !isDraggingProgress) {
      wrapper.classList.add('controls-hidden');
    }
  }

  function resetControlsTimeout() {
    clearTimeout(controlsTimeout);
    if (!player.paused && !isDraggingProgress) {
      controlsTimeout = setTimeout(hideControls, 3000);
    }
  }

  // Mouse / Touch events for controls visibility
  wrapper.addEventListener('mousemove', showControls);
  wrapper.addEventListener('touchstart', showControls, {passive: true});
  
  wrapper.addEventListener('click', (e) => {
    // Prevent toggle play/pause if clicked controls or overlay buttons
    if (e.target.closest('#ctrl') || e.target.closest('.overlay-center-btn')) {
      resetControlsTimeout();
      return;
    }
    
    if (wrapper.classList.contains('controls-hidden')) {
      showControls();
    } else {
      player.paused ? player.play() : player.pause();
      resetControlsTimeout();
    }
  });

  // Auto-save duration from actual video length when missing in database
  player.addEventListener('loadedmetadata', () => {
    const d = Math.floor(player.duration || 0);
    if (d < 1) return;
    if (vidDuration > 0) return;
    fetch('<?= BASE_URL ?>/api/thumbnails.php?action=save_duration', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      credentials: 'same-origin',
      body: JSON.stringify({video_id: FH_VIDEO_ID, duration: d})
    }).then(r => r.json()).then(data => {
      if (data.success && data.formatted) {
        timeDisplay.textContent = fmtTime(player.currentTime) + ' / ' + data.formatted;
        document.querySelectorAll('.video-duration--pending[data-video-id="' + FH_VIDEO_ID + '"]')
          .forEach(el => { el.textContent = data.formatted; el.classList.remove('video-duration--pending'); });
      }
    }).catch(() => {});
  });

  // Play/Pause Bottom Bar
  document.getElementById('play-btn').addEventListener('click', (e) => {
    e.stopPropagation();
    player.paused ? player.play() : player.pause();
  });
  
  // Play/Pause Overlay Centered
  document.getElementById('overlay-play-btn').addEventListener('click', (e) => {
    e.stopPropagation();
    player.paused ? player.play() : player.pause();
  });

  player.addEventListener('play', () => {
    const pauseSvg = '<rect x="5" y="4" width="4" height="16" rx="1"/><rect x="15" y="4" width="4" height="16" rx="1"/>';
    playIcon.innerHTML = pauseSvg;
    overlayPlayIcon.innerHTML = pauseSvg;
    resetControlsTimeout();
  });

  player.addEventListener('pause', () => {
    const playSvg = '<polygon points="5 3 19 12 5 21 5 3"/>';
    playIcon.innerHTML = playSvg;
    overlayPlayIcon.innerHTML = playSvg;
    showControls();
  });

  // Progress update
  player.addEventListener('timeupdate', () => {
    if (!isDraggingProgress) {
      const pct = player.duration ? (player.currentTime / player.duration) * 100 : 0;
      progressFill.style.width = pct + '%';
      progressThumb.style.left = pct + '%';
      timeDisplay.textContent = fmtTime(player.currentTime) + ' / ' + fmtTime(player.duration || vidDuration);
    }
  });

  // Seek functions
  function seek(e) {
    const rect = progressEl.getBoundingClientRect();
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const pct = Math.max(0, Math.min(1, (clientX - rect.left) / rect.width));
    progressFill.style.width = (pct * 100) + '%';
    progressThumb.style.left = (pct * 100) + '%';
    
    if (player.duration) {
      player.currentTime = pct * player.duration;
    }
  }

  // Progress click / drag listeners
  progressContainer.addEventListener('mousedown', (e) => {
    isDraggingProgress = true;
    seek(e);
    showControls();
  });

  document.addEventListener('mousemove', (e) => {
    if (isDraggingProgress) {
      seek(e);
      showControls();
    }
  });

  document.addEventListener('mouseup', () => {
    if (isDraggingProgress) {
      isDraggingProgress = false;
      resetControlsTimeout();
    }
  });

  // Mobile Touch Drag seek
  progressContainer.addEventListener('touchstart', (e) => {
    isDraggingProgress = true;
    seek(e);
    showControls();
  }, {passive: true});

  document.addEventListener('touchmove', (e) => {
    if (isDraggingProgress) {
      seek(e);
      showControls();
    }
  }, {passive: true});

  document.addEventListener('touchend', () => {
    if (isDraggingProgress) {
      isDraggingProgress = false;
      resetControlsTimeout();
    }
  });

  // Mute / Volume dynamic icon updating
  function updateVolumeIcon(vol, isMuted) {
    if (isMuted || vol == 0) {
      volumeIcon.innerHTML = '<path d="M11 5L6 9H2v6h4l5 4V5z"/><line x1="22" y1="9" x2="16" y2="15" stroke="currentColor" stroke-width="2"/><line x1="16" y1="9" x2="22" y2="15" stroke="currentColor" stroke-width="2"/>';
    } else if (vol < 0.5) {
      volumeIcon.innerHTML = '<path d="M11 5L6 9H2v6h4l5 4V5z"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07" stroke="currentColor" stroke-width="2"/>';
    } else {
      volumeIcon.innerHTML = '<path d="M11 5L6 9H2v6h4l5 4V5z"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07" stroke="currentColor" stroke-width="2"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14" stroke="currentColor" stroke-width="2"/>';
    }
  }

  muteBtn?.addEventListener('click', (e) => {
    e.stopPropagation();
    if (player.muted) {
      player.muted = false;
      player.volume = lastVolume;
      volumeSlider.value = lastVolume;
      updateVolumeIcon(lastVolume, false);
    } else {
      lastVolume = player.volume || 1;
      player.muted = true;
      player.volume = 0;
      volumeSlider.value = 0;
      updateVolumeIcon(0, true);
    }
    resetControlsTimeout();
  });

  volumeSlider?.addEventListener('input', function(e) {
    e.stopPropagation();
    player.volume = this.value;
    player.muted = (this.value == 0);
    lastVolume = this.value > 0 ? this.value : lastVolume;
    updateVolumeIcon(this.value, player.muted);
    resetControlsTimeout();
  });

  // Speed selector
  document.getElementById('speed-select').addEventListener('change', function(e) {
    e.stopPropagation();
    player.playbackRate = this.value;
    resetControlsTimeout();
  });

  // Skip 10s Center Controls
  document.getElementById('overlay-skip-back').addEventListener('click', (e) => {
    e.stopPropagation();
    player.currentTime = Math.max(0, player.currentTime - 10);
    resetControlsTimeout();
  });

  document.getElementById('overlay-skip-fwd').addEventListener('click', (e) => {
    e.stopPropagation();
    player.currentTime = Math.min(player.duration || vidDuration, player.currentTime + 10);
    resetControlsTimeout();
  });

  // Skip 10s Bottom Controls (Desktop-only fallback)
  document.getElementById('skip-back')?.addEventListener('click', (e) => {
    e.stopPropagation();
    player.currentTime = Math.max(0, player.currentTime - 10);
    resetControlsTimeout();
  });
  
  document.getElementById('skip-fwd')?.addEventListener('click', (e) => {
    e.stopPropagation();
    player.currentTime = Math.min(player.duration || vidDuration, player.currentTime + 10);
    resetControlsTimeout();
  });

  // Fullscreen
  document.getElementById('fullscreen-btn').addEventListener('click', (e) => {
    e.stopPropagation();
    if (document.fullscreenElement) {
      document.exitFullscreen();
    } else {
      wrapper.requestFullscreen().catch(() => {
        // Fallback for Safari/iOS
        player.webkitEnterFullscreen?.();
      });
    }
    resetControlsTimeout();
  });

  // Mini player
  document.getElementById('mini-btn')?.addEventListener('click', (e) => {
    e.stopPropagation();
    if (document.pictureInPictureElement) {
      document.exitPictureInPicture();
    } else {
      player.requestPictureInPicture?.();
    }
    resetControlsTimeout();
  });

  // Keyboard shortcuts
  document.addEventListener('keydown', e => {
    if (['INPUT', 'TEXTAREA'].includes(e.target.tagName)) return;
    if (e.code === 'Space') {
      e.preventDefault();
      player.paused ? player.play() : player.pause();
    }
    if (e.code === 'ArrowRight') player.currentTime = Math.min(player.duration || vidDuration, player.currentTime + 10);
    if (e.code === 'ArrowLeft') player.currentTime = Math.max(0, player.currentTime - 10);
    if (e.code === 'ArrowUp') {
      e.preventDefault();
      player.volume = Math.min(1, player.volume + 0.1);
      volumeSlider.value = player.volume;
      updateVolumeIcon(player.volume, false);
    }
    if (e.code === 'ArrowDown') {
      e.preventDefault();
      player.volume = Math.max(0, player.volume - 0.1);
      volumeSlider.value = player.volume;
      updateVolumeIcon(player.volume, player.volume === 0);
    }
    if (e.code === 'KeyF') {
      document.getElementById('fullscreen-btn').click();
    }
  });
}

// Reactions (Like/Dislike)
async function handleReact(e, type) {
  if (e) { e.preventDefault(); e.stopPropagation(); }
  if (!requireLogin()) return;
  const res=await fetch('<?= BASE_URL ?>/api/videos.php?action=react',{
    method:'POST',headers:{'Content-Type':'application/json'},
    body:JSON.stringify({video_id:<?= $vid ?>,type:type})
  });
  const d=await res.json();
  if(d.success) {
    document.getElementById('like-count').textContent=d.data.likes;
    const lBtn = document.getElementById('like-btn');
    const dBtn = document.getElementById('dislike-btn');
    if (lBtn) {
      lBtn.style.cssText = d.data.reaction === 'like' ? 'background:rgba(99,102,241,.15);color:var(--accent)' : '';
      lBtn.querySelector('svg').setAttribute('fill', d.data.reaction === 'like' ? 'var(--accent)' : 'none');
    }
    if (dBtn) {
      dBtn.style.cssText = d.data.reaction === 'dislike' ? 'background:rgba(99,102,241,.15);color:var(--accent)' : '';
      dBtn.querySelector('svg').setAttribute('fill', d.data.reaction === 'dislike' ? 'var(--accent)' : 'none');
    }
  }
}
document.getElementById('like-btn')?.addEventListener('click', (e) => handleReact(e, 'like'));
document.getElementById('dislike-btn')?.addEventListener('click', (e) => handleReact(e, 'dislike'));

// Share modal
document.getElementById('share-btn')?.addEventListener('click',(e)=>{ if (e) { e.preventDefault(); e.stopPropagation(); } document.getElementById('share-modal').classList.add('open')});
document.getElementById('share-modal')?.addEventListener('click',function(e){if(e.target===this)this.classList.remove('open');});

// Watch Later
document.getElementById('wl-btn')?.addEventListener('click',async function(e){
  if (e) { e.preventDefault(); e.stopPropagation(); }
  if (!requireLogin()) return;
  const res=await fetch('<?= BASE_URL ?>/api/videos.php?action=watch_later',{
    method:'POST',headers:{'Content-Type':'application/json'},
    body:JSON.stringify({video_id:<?= $vid ?>})
  });
  const d=await res.json();
  if (d.success) {
    this.style.cssText = d.data.saved ? 'background:rgba(99,102,241,.15);color:var(--accent)' : '';
    this.querySelector('svg').setAttribute('fill', d.data.saved ? 'var(--accent)' : 'none');
    this.querySelector('span').textContent = d.data.saved ? 'Saved ✓' : 'Save';
  }
});

// Subscribe
document.getElementById('sub-btn')?.addEventListener('click',async function(e){
  if (e) { e.preventDefault(); e.stopPropagation(); }
  if (!requireLogin()) return;
  const res=await fetch('<?= BASE_URL ?>/api/videos.php?action=subscribe',{
    method:'POST',headers:{'Content-Type':'application/json'},
    body:JSON.stringify({channel_id:<?= $video['user_id'] ?>})
  });
  const d=await res.json();
  if (d.success) {
    this.textContent = d.data.subscribed ? 'Subscribed ✓' : 'Subscribe';
    if (d.data.subscribed) {
      this.classList.remove('btn-primary');
      this.classList.add('btn-subscribed');
    } else {
      this.classList.remove('btn-subscribed');
      this.classList.add('btn-primary');
    }
  }
});

// Load Comments
document.getElementById('load-comments')?.addEventListener('click',async function(e){
  if (e) { e.preventDefault(); e.stopPropagation(); }
  this.style.display='none';
  const res=await fetch('<?= BASE_URL ?>/api/videos.php?action=comments&video_id=<?= $vid ?>');
  const d=await res.json();
  const list=document.getElementById('comments-list');
  list.innerHTML = '';
  (d.data||[]).forEach(c=>{
    const div=document.createElement('div');
    div.style.cssText='display:flex;gap:12px;margin-bottom:16px';
    div.innerHTML=`<img src="${c.avatar}" class="avatar avatar-sm" width="32" height="32" loading="lazy">
      <div><div style="font-weight:600;font-size:.85rem">${c.username}</div>
      <div style="font-size:.88rem;margin-top:4px;color:var(--text2)">${c.content}</div>
      <div style="font-size:.75rem;color:var(--text3);margin-top:4px">${c.ago}</div></div>`;
    list.appendChild(div);
  });
  document.getElementById('close-comments').style.display='inline-flex';
});

// Close Comments
document.getElementById('close-comments')?.addEventListener('click',function(e){
  if (e) { e.preventDefault(); e.stopPropagation(); }
  this.style.display='none';
  document.getElementById('comments-list').innerHTML = '';
  document.getElementById('load-comments').style.display='inline-flex';
});

// Post Comment
document.getElementById('comment-form')?.addEventListener('submit',async function(e){
  e.preventDefault();
  if (!requireLogin()) return;
  const input=document.getElementById('comment-input');
  if(!input.value.trim()) return;
  const res=await fetch('<?= BASE_URL ?>/api/videos.php?action=comment',{
    method:'POST',headers:{'Content-Type':'application/json'},
    body:JSON.stringify({video_id:<?= $vid ?>,content:input.value.trim()})
  });
  const d=await res.json();
  if(d.success){ 
    input.value='';
    document.getElementById('close-comments').style.display = 'none';
    document.getElementById('load-comments').style.display = 'inline-flex';
    document.getElementById('load-comments').click();
  }
});

// HLS support
if(player && typeof Hls!=='undefined'&&Hls.isSupported()&&player.dataset.hls){
  const hls=new Hls({maxBufferLength:30});
  hls.loadSource(player.dataset.hls);
  hls.attachMedia(player);
}

// ── Next-Video Prefetch (fires at 70% progress) ─────────────────────────────
// Silently preloads the next recommended video so navigation is near-instant
<?php
// Safely get the next most recent video to prefetch
$next_prefetch_id = null;
try {
    $next_vid = db_fetch(
        "SELECT id FROM videos WHERE status='published' AND visibility='public' AND id != ? ORDER BY created_at DESC LIMIT 1",
        [$vid]
    );
    if ($next_vid) {
        $next_prefetch_id = (int)$next_vid['id'];
    }
} catch (\Throwable $e) {
    // Silently ignore — prefetch is optional
}
?>
<?php if ($next_prefetch_id): ?>
(function() {
  const nextVideoId = <?= (int)$next_prefetch_id ?>;
  const nextVideoStreamUrl = '<?= BASE_URL ?>/api/stream.php?v=' + nextVideoId;
  let prefetchTriggered = false;

  function triggerPrefetch() {
    if (prefetchTriggered) return;
    prefetchTriggered = true;
    // Prefetch first 256KB of the next video (enough for instant start)
    fetch(nextVideoStreamUrl, {
      headers: { Range: 'bytes=0-262143' },
      credentials: 'same-origin'
    }).catch(() => {});
    // Also warm the metadata
    const preloadLink = document.createElement('link');
    preloadLink.rel = 'prefetch';
    preloadLink.href = nextVideoStreamUrl;
    preloadLink.as = 'video';
    document.head.appendChild(preloadLink);
  }

  if (player) {
    player.addEventListener('timeupdate', function() {
      if (!prefetchTriggered && player.duration > 0) {
        const progress = player.currentTime / player.duration;
        if (progress >= 0.70) triggerPrefetch();
      }
    });
    // Also trigger when video ends
    player.addEventListener('ended', triggerPrefetch);
  }
})();
<?php endif; ?>
</script>
<script>
window.FH_WATCH = {
  viewId: <?= (int)$view_session_id ?>,
  videoId: <?= (int)$vid ?>,
  endpoint: <?= json_encode(BASE_URL . '/api/watchtime.php') ?>
};
window.FH_VIDEO_DURATION = <?= (int)$video['duration'] ?>;
</script>
<script src="<?= fh_asset_url('assets/js/video-player-ads.js') ?>" defer></script>
<script>
window.addEventListener('load', () => {
  if (document.getElementById('player-wrapper')) {
    setTimeout(() => {
      window.fhAdManager = new FHVideoAdManager({
        playerId: 'fh-player',
        ytPlayerId: 'fh-youtube-player',
        videoId: <?= (int)$vid ?>,
        baseUrl: '<?= BASE_URL ?>',
        device: '<?= detect_device() ?>'
      });
    }, 50);
  }
});

// Playlist Autoplay Support
<?php if ($playlist && $next_video_id): ?>
window.addEventListener('DOMContentLoaded', () => {
  const player = document.getElementById('fh-player');
  const nextVideoUrl = '<?= BASE_URL ?>/watch.php?v=<?= $next_video_id ?>&list=<?= $playlist['id'] ?>';
  
  // HTML5 Video ended event
  if (player) {
    player.addEventListener('ended', () => {
      window.location.href = nextVideoUrl;
    });
  }
  
  // YouTube API ended polling
  let ytEndedTriggered = false;
  let checkYTEnd = setInterval(() => {
    if (window.fhAdManager && window.fhAdManager.ytPlayerObj && typeof window.fhAdManager.ytPlayerObj.getPlayerState === 'function') {
      try {
        const state = window.fhAdManager.ytPlayerObj.getPlayerState();
        if (state === 0 && !ytEndedTriggered) { // 0 is YT.PlayerState.ENDED
          ytEndedTriggered = true;
          clearInterval(checkYTEnd);
          window.location.href = nextVideoUrl;
        }
      } catch (e) {
        // Ignore iframe api errors
      }
    }
  }, 500);
});
<?php endif; ?>
</script>
<script src="<?= fh_asset_url('assets/js/watchtime.js') ?>" defer></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
