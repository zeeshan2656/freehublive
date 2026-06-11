<?php
// ============================================================
// FreeHub.Live — Reels Feed Page
// ============================================================
$is_reels = true;
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$meta_title = 'Reels — FreeHub';
$meta_desc  = 'Watch short vertical videos on FreeHub.';

// ── SSR: Fetch first reel for instant display (no JS round-trip needed) ──
$_ssr_start_id = (int)($_GET['id'] ?? 0);
$_ssr_reels = [];

try {
    // Use same fields as api/videos.php to ensure compatibility
    $ssr_fields = "v.id, v.video_url, v.user_id, u.username, u.channel_name, u.avatar, v.title, v.views, v.likes, COALESCE(v.comments_count, 0) AS comments_count, v.created_at";

    if ($_ssr_start_id > 0) {
        $start_reel = db_fetch(
            "SELECT $ssr_fields
             FROM reels v JOIN users u ON u.id=v.user_id
             WHERE v.id=? AND v.status='published'",
            [$_ssr_start_id]
        );
        if ($start_reel) {
            $_ssr_reels[] = $start_reel;
        }
        // Load a few more reels after the start one
        if ($_ssr_reels) {
            $more = db_fetchAll(
                "SELECT $ssr_fields
                 FROM reels v JOIN users u ON u.id=v.user_id
                 WHERE v.status='published' AND v.id != ?
                 ORDER BY v.created_at DESC, v.id DESC LIMIT 2",
                [$_ssr_start_id]
            );
            $_ssr_reels = array_merge($_ssr_reels, $more);
        }
    } else {
        // Load latest 3 reels for SSR bootstrap
        $_ssr_reels = db_fetchAll(
            "SELECT $ssr_fields
             FROM reels v JOIN users u ON u.id=v.user_id
             WHERE v.status='published'
             ORDER BY v.created_at DESC, v.id DESC LIMIT 3"
        );
    }
} catch (\Throwable $e) {
    // Silently fall back — JS will load reels from IndexedDB or API
    $_ssr_reels = [];
}

// Format SSR reels for JS consumption
$_ssr_feed = array_map(function($v) {
    return [
        'id'          => (int)$v['id'],
        'user_id'     => (int)$v['user_id'],
        'video_src'   => reel_url($v['video_url']),
        'channel'     => $v['channel_name'] ?? $v['username'] ?? '',
        'avatar'      => avatar_url($v['avatar'] ?? null),
        'description' => $v['title'] ?? '',
        'title'       => $v['title'] ?? '',
        'views'       => format_number((int)($v['views'] ?? 0)),
        'views_raw'   => (int)($v['views'] ?? 0),
        'likes'       => format_number((int)($v['likes'] ?? 0)),
        'comments'    => format_number((int)($v['comments_count'] ?? 0)),
        'ago'         => time_ago($v['created_at'] ?? date('Y-m-d H:i:s')),
        'is_reel'     => 1,
        'url'         => BASE_URL . '/reels/' . $v['id'],
    ];
}, $_ssr_reels);

require_once __DIR__ . '/includes/header.php';
?>

<div class="reels-feed-container">
  <!-- Reels Ads Placements (Desktop Sides) -->
  <div class="ad-sponsored-container ad-desktop-only reels-desktop-left-ad" data-placement="reels_left" data-device-target="desktop"></div>
  <div class="ad-sponsored-container ad-desktop-only reels-desktop-right-ad" data-placement="reels_right" data-device-target="desktop"></div>

  <!-- Sleek Floating Back Button -->
  <a href="<?= BASE_URL ?>/" class="reels-back-button" title="Back to Home">
    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
      <path d="M19 12H5M12 19l-7-7 7-7"/>
    </svg>
  </a>
    
  <!-- Reels feed slider -->
  <div class="reels-container" id="reels-slider">
    <!-- Dynamic slides injected here from SSR bootstrap or IndexedDB -->
  </div>

  <!-- SSR Bootstrap Data (inlined for zero-latency first render) -->
  <script id="ssr-reels-data" type="application/json"><?= json_encode($_ssr_feed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>

  <!-- Dynamic Empty State (hidden by default) -->
  <div id="reels-empty-state" style="display:none; flex-direction:column; align-items:center; justify-content:center; height:100%; color:var(--text2); text-align:center; padding: 20px;">
    <svg width="64" height="64" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="opacity:.4; margin-bottom:16px;">
      <rect x="6" y="2" width="12" height="20" rx="2" ry="2"/>
      <line x1="12" y1="18" x2="12.01" y2="18"/>
    </svg>
    <h2 style="font-size:1.3rem; margin-bottom:8px; color:#fff;">No Reels Yet</h2>
    <p style="font-size:0.9rem; margin-bottom:20px; max-width: 320px;">Be the first to upload a short vertical video (under 5 mins) to kickstart the Reels feed!</p>
    <a href="<?= BASE_URL ?>/creator/upload.php?mode=reel" class="btn btn-primary" style="font-weight:700;">Create a Reel</a>
  </div>

  <!-- Comments Sliding Overlay Panel -->
  <div id="reels-comments-panel">
    <div class="comments-panel-header">
      <h3>Comments</h3>
      <button type="button" class="close-panel-btn" onclick="closeComments()">&times;</button>
    </div>
    <div class="comments-panel-list" id="comments-list-container">
      <!-- Injected dynamically -->
    </div>
    <div class="comments-panel-footer">
      <?php if (is_logged_in()): ?>
        <form onsubmit="postReelComment(event)">
          <input type="text" id="new-comment-field" placeholder="Add a comment..." required autocomplete="off">
          <button type="submit" class="post-comment-btn">Post</button>
        </form>
      <?php else: ?>
        <div style="text-align:center; padding: 10px; font-size: 0.85rem; color: var(--text2);">
          Please <a href="<?= BASE_URL ?>/auth/login.php" style="color:var(--accent); font-weight:700;">login</a> to comment.
        </div>
      <?php endif; ?>
    </div>
  </div>
</div> <!-- /reels-feed-container -->

<style>
/* Reels Ads Placement Overlay Styles */
.reels-mobile-overlay-ad {
  position: absolute;
  top: 16px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 120;
  margin: 0 !important;
  padding: 0 !important;
  background: transparent !important;
  border: none !important;
  box-shadow: none !important;
  width: 100%;
  max-width: 340px;
  display: flex !important;
  justify-content: center;
  pointer-events: auto;
}
.reels-desktop-left-ad {
  position: absolute;
  top: 50%;
  left: calc(50% - 230px - 180px);
  transform: translateY(-50%);
  z-index: 90;
  width: 160px;
  height: 600px;
  margin: 0 !important;
  padding: 0 !important;
  background: transparent !important;
  border: none !important;
  box-shadow: none !important;
}
.reels-desktop-right-ad {
  position: absolute;
  top: 50%;
  right: calc(50% - 230px - 180px);
  transform: translateY(-50%);
  z-index: 90;
  width: 160px;
  height: 600px;
  margin: 0 !important;
  padding: 0 !important;
  background: transparent !important;
  border: none !important;
  box-shadow: none !important;
}

html, body {
  margin: 0;
  padding: 0;
  height: 100%;
  overflow: hidden;
  background: #000;
}
.reels-feed-container {
  height: 100vh;
  height: 100dvh;
  width: 100vw;
  position: relative;
  overflow: hidden;
  background: #000;
}
.reels-back-button {
  position: absolute;
  top: 16px;
  left: 16px;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.42);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 150;
  cursor: pointer;
  transition: background 0.2s, transform 0.2s;
}
.reels-back-button:hover {
  background: rgba(0, 0, 0, 0.6);
  transform: scale(1.05);
}
.reels-container {
  height: 100%;
  width: 100%;
  max-width: 460px;
  margin: 0 auto;
  overflow-y: scroll;
  scroll-snap-type: y mandatory;
  scrollbar-width: none;
  background: #000;
  box-shadow: 0 0 30px rgba(0,0,0,0.8);
  position: relative;
}
.reels-container::-webkit-scrollbar {
  display: none;
}
.reel-slide {
  height: 100%;
  width: 100%;
  scroll-snap-align: start;
  scroll-snap-stop: always;
  position: relative;
  overflow: hidden;
}
.double-tap-zone {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: calc(100% - 100px);
  z-index: 5;
}
.reel-play-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 4;
  cursor: pointer;
}
.play-icon-shape {
  background: rgba(0,0,0,0.5);
  border-radius: 50%;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  opacity: 0;
  transform: scale(1.5);
  transition: all 0.3s ease;
}
.reel-play-overlay.paused .play-icon-shape {
  opacity: 1;
  transform: scale(1);
}
.reel-info-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  width: calc(100% - 70px);
  padding: 24px 16px;
  background: linear-gradient(transparent, rgba(0,0,0,0.85));
  z-index: 10;
  color: #fff;
  pointer-events: none;
}
.reel-creator-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 1px solid #fff;
  object-fit: cover;
}
.reel-title {
  font-size: 0.95rem;
  font-weight: 700;
  margin: 10px 0 4px;
  text-shadow: 0 1px 3px rgba(0,0,0,0.6);
  pointer-events: auto;
}
.reel-sidebar-actions {
  position: absolute;
  right: 12px;
  bottom: 30px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  z-index: 10;
}
.reel-side-btn {
  background: transparent;
  border: none;
  color: #fff;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  font-size: 0.72rem;
  padding: 0;
  outline: none;
}
.creator-profile-btn img {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 2px solid var(--accent);
  object-fit: cover;
  box-shadow: 0 4px 12px rgba(0,0,0,0.25);
  transition: transform 0.2s;
}
.creator-profile-btn img:hover {
  transform: scale(1.08);
}
.action-icon-wrap {
  width: 42px;
  height: 42px;
  background: rgba(30, 30, 30, 0.4);
  backdrop-filter: blur(8px);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}
.reel-side-btn:hover .action-icon-wrap {
  background: rgba(255,255,255,0.15);
  transform: scale(1.08);
}
.like-btn.liked .heart-icon {
  animation: heartPop 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes heartPop {
  0% { transform: scale(1); }
  50% { transform: scale(1.3); }
  100% { transform: scale(1); }
}
.floating-heart {
  position: absolute;
  z-index: 100;
  color: var(--red);
  transform: translate(-50%, -50%) scale(0);
  animation: floatingHeartPop 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
  pointer-events: none;
}
@keyframes floatingHeartPop {
  0% { transform: translate(-50%, -50%) scale(0); opacity: 0; }
  15% { transform: translate(-50%, -50%) scale(1.2); opacity: 0.9; }
  50% { transform: translate(-50%, -50%) scale(1); opacity: 0.9; }
  100% { transform: translate(-50%, -60px) scale(0.6); opacity: 0; }
}

#reels-comments-panel {
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translate(-50%, 100%);
  width: 100%;
  max-width: 460px;
  height: 65%;
  background: #141416;
  border-radius: 16px 16px 0 0;
  z-index: 120;
  display: flex;
  flex-direction: column;
  transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
  box-shadow: 0 -8px 30px rgba(0,0,0,0.5);
  border-top: 1px solid var(--border);
}
#reels-comments-panel.open {
  transform: translate(-50%, 0);
}
.comments-panel-header {
  padding: 16px;
  border-bottom: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.comments-panel-header h3 {
  font-size: 1rem;
  font-weight: 700;
  margin: 0;
  color: #fff;
}
.close-panel-btn {
  background: transparent;
  border: none;
  color: var(--text2);
  font-size: 1.6rem;
  cursor: pointer;
  line-height: 1;
  padding: 0;
}
.close-panel-btn:hover { color: #fff; }
.comments-panel-list {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
}
.comments-panel-footer {
  padding: 16px;
  border-top: 1px solid var(--border);
  background: #18181b;
}
.comments-panel-footer form {
  display: flex;
  gap: 8px;
}
#new-comment-field {
  flex: 1;
  background: #27272a;
  border: 1px solid #3f3f46;
  color: #fff;
  border-radius: 20px;
  padding: 8px 16px;
  font-size: 0.85rem;
  outline: none;
}
.post-comment-btn {
  background: var(--accent);
  color: #fff;
  border: none;
  border-radius: 20px;
  padding: 8px 16px;
  font-weight: 600;
  font-size: 0.85rem;
  cursor: pointer;
}
.comment-item {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
}
.comment-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  object-fit: cover;
}
.comment-body {
  flex: 1;
  min-width: 0;
}
.comment-username {
  font-size: 0.78rem;
  font-weight: 700;
  color: #fff;
  margin-bottom: 2px;
}
.comment-content {
  font-size: 0.82rem;
  color: var(--text2);
  line-height: 1.4;
  word-break: break-word;
}
</style>

<script>
// Reels Logic Controller
let activeCommentVideoId = null;
let isMutedGlobal = localStorage.getItem('reels_muted') === 'true';
let currentActiveIndex = 0;
let isReelsLoading = false;
let reelsPage = 1;
let hasNextPage = true;
let reelsList = [];

// Track created Blob URLs to prevent memory leaks
const activeBlobUrls = {};

const cache = new ReelsCache();

function format_number(num) {
  num = parseInt(num, 10) || 0;
  if (num >= 1000000) return (num / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
  if (num >= 1000) return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
  return num;
}

function getYoutubeId(url) {
  if (!url) return null;
  if (url.match(/^[a-zA-Z0-9_-]{11}$/)) return url;
  const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=|shorts\/)([^#\&\?]*).*/;
  const match = url.match(regExp);
  return (match && match[2].length === 11) ? match[2] : null;
}

function appendReelSlide(v) {
  const container = document.getElementById('reels-slider');
  const slide = renderReelSlide(v);
  container.appendChild(slide);
  if (observer) observer.observe(slide);
}

function renderReelSlide(v) {
  const slide = document.createElement('div');
  slide.className = 'reel-slide';
  slide.setAttribute('data-id', v.id);
  slide.setAttribute('data-title', v.description || v.title || '');
  slide.setAttribute('data-views', v.views || 0);

  const avatarUrl = v.avatar || '<?= BASE_URL ?>/assets/img/default-avatar.jpg';
  const ytId = getYoutubeId(v.video_src);

  let mediaHtml = '';
  if (ytId) {
    mediaHtml = `<div class="reel-video" data-src="${v.video_src}" data-yt-id="${ytId}" style="width:100%; height:100%; background:#000;"></div>`;
  } else {
    mediaHtml = `<video class="reel-video" data-src="${v.video_src}" loop playsinline style="width:100%; height:100%; object-fit:contain; background:#000;" onerror="handleVideoError(this)"></video>`;
  }

  slide.innerHTML = `
    ${mediaHtml}
    
    <div class="reel-play-overlay" onclick="togglePlay(this)">
      <div class="play-icon-shape">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
      </div>
    </div>

    <div class="double-tap-zone" onclick="handleTapOrDoubleTap(this, ${v.id})"></div>

    <!-- Dynamic Mobile Overlay Ad inside Slide -->
    <div class="ad-sponsored-container ad-mobile-only reels-mobile-overlay-ad" data-placement="reels_mobile_top" data-device-target="mobile" data-lazy="true" style="position: absolute; top: 16px; left: 50%; transform: translateX(-50%); z-index: 120; margin: 0; padding: 0; background: transparent; width: 100%; max-width: 340px; display: flex; justify-content: center; pointer-events: auto;"></div>

    <div class="reel-info-overlay">
      <div class="reel-creator-row" style="display:flex; align-items:center; gap:10px; color:#fff; font-weight:700;">
        <img class="reel-creator-avatar" src="${avatarUrl}" alt="${escapeHtml(v.channel)}">
        <span>@${escapeHtml(v.channel)}</span>
      </div>
      <h2 class="reel-title">${escapeHtml(v.description || v.title || '')}</h2>
    </div>

    <div class="reel-sidebar-actions">
      <div class="reel-side-btn creator-profile-btn" style="cursor: default;">
        <img src="${avatarUrl}" alt="Creator">
      </div>

      <button type="button" class="reel-side-btn like-btn" onclick="toggleLike(this, ${v.id})" title="Like">
        <div class="action-icon-wrap">
          <svg class="heart-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        </div>
        <span class="like-count"></span>
      </button>

      <button type="button" class="reel-side-btn comment-trigger-btn" onclick="openComments(${v.id})" title="Comments">
        <div class="action-icon-wrap">
          <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        </div>
        <span class="comment-count"></span>
      </button>

      <button type="button" class="reel-side-btn share-btn" onclick="shareReel(${v.id}, this)" title="Copy Link">
        <div class="action-icon-wrap">
          <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
        </div>
        <span>Share</span>
      </button>

      <button type="button" class="reel-side-btn views-display-btn" title="Views" style="cursor: default;">
        <div class="action-icon-wrap">
          <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
        </div>
        <span>${v.views_raw !== undefined ? Number(v.views_raw).toLocaleString() : (v.views || 0)}</span>
      </button>

      <button type="button" class="reel-side-btn mute-btn" onclick="toggleMuteState()" title="Mute/Unmute">
        <div class="action-icon-wrap">
          <svg class="mute-icon-svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/>
            <path class="volume-waves" d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/>
          </svg>
        </div>
      </button>
    </div>
  `;
  return slide;
}

function playActiveVideo(video, slide) {
  if (!video || !video.src || video.src === '') return;

  const playOverlay = slide ? slide.querySelector('.reel-play-overlay') : null;
  if (playOverlay) {
    playOverlay.classList.remove('paused');
  }

  video.muted = isMutedGlobal;
  const playPromise = video.play();
  if (playPromise !== undefined) {
    playPromise.catch(error => {
      console.log("Autoplay failed, trying muted fallback...", error);
      video.muted = true;
      const mutedPlayPromise = video.play();
      if (mutedPlayPromise !== undefined) {
        mutedPlayPromise.catch(err => {
          console.warn("Muted autoplay also blocked", err);
          if (playOverlay) {
            playOverlay.classList.add('paused');
          }
        });
      }
    });
  }
}

async function updateMediaSources(activeIndex) {
  const slides = document.querySelectorAll('.reel-slide');
  const keepIds = [];

  for (let idx = 0; idx < slides.length; idx++) {
    const slide = slides[idx];
    const video = slide.querySelector('.reel-video');
    if (!video) continue;

    const reelId = parseInt(slide.getAttribute('data-id'), 10);
    const realSrc = video.getAttribute('data-src');

    // Rolling cache window range: [activeIndex - 2, activeIndex + 10]
    if (idx >= activeIndex - 2 && idx <= activeIndex + 10) {
      keepIds.push(reelId);
    }

    // Active DOM range: [activeIndex - 2, activeIndex + 5]
    if (idx >= activeIndex - 2 && idx <= activeIndex + 5) {
      if (video.tagName === 'DIV' && video.hasAttribute('data-yt-id')) {
        const ytId = video.getAttribute('data-yt-id');
        if (idx === activeIndex) {
          let iframe = video.querySelector('iframe');
          if (!iframe) {
            video.innerHTML = `<iframe class="youtube-player-iframe" src="https://www.youtube.com/embed/${ytId}?enablejsapi=1&autoplay=1&controls=0&loop=1&playlist=${ytId}&mute=${isMutedGlobal ? 1 : 0}&playsinline=1&rel=0" style="width:100%; height:100%; border:none; display:block;" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
          } else {
            if (iframe.contentWindow) {
              iframe.contentWindow.postMessage('{"event":"command","func":"playVideo","args":""}', '*');
            }
          }
          const playOverlay = slide.querySelector('.reel-play-overlay');
          if (playOverlay) playOverlay.classList.remove('paused');
        } else {
          video.innerHTML = '';
        }
      } else {
        // Standard video file
        let srcJustSet = false;
        if (!video.src || video.src === '') {
          const localBlob = await cache.getVideoBlob(reelId);
          if (localBlob) {
            const blobUrl = URL.createObjectURL(localBlob);
            activeBlobUrls[reelId] = blobUrl;
            video.src = blobUrl;
          } else {
            video.src = realSrc;
          }
          srcJustSet = true;
        }

        if (idx === activeIndex) {
          video.setAttribute('preload', 'auto');
          if (srcJustSet || video.paused) {
            playActiveVideo(video, slide);
          }
        } else if (idx > activeIndex) {
          video.setAttribute('preload', 'auto');
          if (video.paused && video.readyState < 2) {
            video.load();
          }
        } else {
          video.setAttribute('preload', 'metadata');
        }
      }
    } else {
      if (video.tagName === 'DIV') {
        video.innerHTML = '';
      } else {
        video.removeAttribute('preload');
        video.removeAttribute('src');
        video.load();

        if (activeBlobUrls[reelId]) {
          URL.revokeObjectURL(activeBlobUrls[reelId]);
          delete activeBlobUrls[reelId];
        }
      }
    }
  }

  // Preload upcoming reels binary video files
  precacheRange(activeIndex + 1, activeIndex + 10);

  // Evict old unused video blobs
  cache.cleanOldBlobs(keepIds);
}

async function precacheRange(startIdx, endIdx) {
  const slides = document.querySelectorAll('.reel-slide');
  for (let idx = startIdx; idx <= endIdx; idx++) {
    if (idx >= slides.length) break;
    const slide = slides[idx];
    const reelId = parseInt(slide.getAttribute('data-id'), 10);
    const video = slide.querySelector('.reel-video');
    if (!video || video.tagName === 'DIV') continue;

    const realSrc = video.getAttribute('data-src');

    try {
      const existing = await cache.getVideoBlob(reelId);
      if (!existing) {
        const res = await fetch(realSrc);
        const blob = await res.blob();
        await cache.saveVideoBlob(reelId, blob);
        
        // Dynamic swap to blob if active in viewport buffer range
        const activeSlides = document.querySelectorAll('.reel-slide');
        const currentActive = parseInt(activeSlides[currentActiveIndex].getAttribute('data-id'), 10);
        const selfIdx = Array.from(activeSlides).indexOf(slide);
        if (selfIdx >= currentActiveIndex - 2 && selfIdx <= currentActiveIndex + 5) {
          if (video.src === realSrc || !video.src) {
            const blobUrl = URL.createObjectURL(blob);
            activeBlobUrls[reelId] = blobUrl;
            video.src = blobUrl;
          }
        }
      }
    } catch (e) {
      console.warn(`Failed to preload video blob for reel ${reelId}:`, e);
    }
  }
}

let observer;

document.addEventListener('DOMContentLoaded', async () => {
  const container = document.getElementById('reels-slider');
  if (!container) return;

  updateMuteIcons();

  const observerOptions = {
    root: container,
    rootMargin: '0px',
    threshold: 0.6
  };

  observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      const video = entry.target.querySelector('.reel-video');
      if (!video) return;

      if (entry.isIntersecting) {
        const slides = Array.from(container.querySelectorAll('.reel-slide'));
        currentActiveIndex = slides.indexOf(entry.target);
        
        updateMediaSources(currentActiveIndex);

        // Track active Reel view
        const reelId = entry.target.getAttribute('data-id');
        if (reelId && typeof FHTracker !== 'undefined') {
          FHTracker.trackReel(reelId);
        }

        // Refresh mobile overlay ad inside active slide
        const mobileAd = entry.target.querySelector('.reels-mobile-overlay-ad');
        if (mobileAd && typeof window.reloadAd === 'function') {
          window.reloadAd(mobileAd);
        }
        
        // Refresh desktop side ads
        const desktopLeftAd = document.querySelector('.reels-desktop-left-ad');
        const desktopRightAd = document.querySelector('.reels-desktop-right-ad');
        if (desktopLeftAd && typeof window.reloadAd === 'function') {
          window.reloadAd(desktopLeftAd);
        }
        if (desktopRightAd && typeof window.reloadAd === 'function') {
          window.reloadAd(desktopRightAd);
        }

        // Pause all other videos in the document
        document.querySelectorAll('.reel-video').forEach(v => {
          if (v !== video) {
            v.pause();
            v.currentTime = 0;
            const slide = v.closest('.reel-slide');
            if (slide) {
              const playOverlay = slide.querySelector('.reel-play-overlay');
              if (playOverlay) playOverlay.classList.remove('paused');
            }
          }
        });

        if (video.src && video.src !== '') {
          playActiveVideo(video, entry.target);
        }
      } else {
        video.pause();
        video.currentTime = 0;
        entry.target.querySelector('.reel-play-overlay').classList.remove('paused');
      }
    });
  }, observerOptions);

  const urlParams = new URLSearchParams(window.location.search);
  const startId = urlParams.get('id') || '';

  // ── Read SSR-injected reel data (zero network latency bootstrap) ──────────────
  let ssrReels = [];
  try {
    const ssrEl = document.getElementById('ssr-reels-data');
    if (ssrEl && ssrEl.textContent) {
      ssrReels = JSON.parse(ssrEl.textContent) || [];
    }
  } catch (e) {
    console.warn('SSR reels data parse error:', e);
  }

  // Background sync helper to refresh feed if cache is stale or empty
  async function refreshFeedFromAPI() {
    const url = `${FH_BASE}/api/videos.php?is_reel=1&page=1&per_page=15`;
    try {
      const res = await fetch(url);
      const data = await res.json();
      if (data && data.videos) {
        const freshVideos = data.videos;
        const currentIds = reelsList.map(v => v.id).join(',');
        const freshIds = freshVideos.map(v => v.id).join(',');
        
        if (currentIds !== freshIds) {
          cache.saveFeed(freshVideos);
          
          const isStillAtStart = (currentActiveIndex === 0);
          const activeVideo = container.querySelector('.reel-slide .reel-video');
          if (activeVideo) activeVideo.pause();
          
          container.innerHTML = '';
          reelsList = [];
          
          freshVideos.forEach(v => {
            appendReelSlide(v);
            reelsList.push(v);
          });
          
          reelsPage = 2;
          hasNextPage = data.has_next;
          
          if (reelsList.length === 0) {
            document.getElementById('reels-empty-state').style.display = 'flex';
          } else {
            document.getElementById('reels-empty-state').style.display = 'none';
          }
          
          if (isStillAtStart) {
            updateMediaSources(0);
          } else {
            updateMediaSources(currentActiveIndex);
          }
        }
      }
    } catch (err) {
      console.warn("Failed to background refresh reels feed:", err);
    }
  }

  if (startId) {
    // Specific reel requested — SSR may have it, check first
    if (ssrReels.length && ssrReels[0].id == startId) {
      ssrReels.forEach(v => {
        appendReelSlide(v);
        reelsList.push(v);
      });
      reelsPage = 2;
      hasNextPage = true;
      updateMediaSources(0);
      // Load more in background
      loadMoreReels();
    } else {
      await loadMoreReels();
    }
  } else {
    // ── Priority 1: SSR data (already in page, zero latency) ──
    if (ssrReels.length) {
      ssrReels.forEach(v => {
        appendReelSlide(v);
        reelsList.push(v);
      });
      reelsPage = 1; // Will be overwritten when cache/API loads
      hasNextPage = true;
      updateMediaSources(0);
    }

    // ── Priority 2: IndexedDB cache (if fresh within 30 min) ──
    let hasCache = false;
    try {
      const isFresh = await cache.isFeedFresh();
      const cachedFeed = await cache.getFeed();
      if (cachedFeed && cachedFeed.length) {
        // Ensure cache actually contains the latest SSR-rendered reel (prevents hiding fresh uploads)
        const hasLatest = !ssrReels.length || cachedFeed.some(v => v.id === ssrReels[0].id);
        if (hasLatest) {
          // Replace SSR data with fuller cached feed
          container.innerHTML = '';
          reelsList = [];
          cachedFeed.forEach(v => {
            appendReelSlide(v);
            reelsList.push(v);
          });
          reelsPage = 2;
          hasNextPage = true;
          hasCache = true;
          updateMediaSources(0);

          if (!isFresh) {
            // Cache is stale — refresh silently in background
            refreshFeedFromAPI();
          }
        }
      }
    } catch (e) {
      console.warn("IndexedDB load error, fallback to API", e);
    }

    // ── Priority 3: Live API (if no cache or SSR had nothing) ──
    if (!hasCache && !ssrReels.length) {
      await loadMoreReels();
    } else if (!hasCache) {
      // Had SSR but no cache — load full feed from API
      loadMoreReels();
    }
  }

  if (!reelsList.length) {
    document.getElementById('reels-empty-state').style.display = 'flex';
  }

  // Increase preload threshold to 2 full reel heights (prevents empty buffer)
  container.addEventListener('scroll', () => {
    const threshold = container.scrollHeight - container.clientHeight - 2000;
    if (container.scrollTop >= threshold) {
      loadMoreReels();
    }
  });

  window.addEventListener('keydown', (e) => {
    if (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA') {
      return;
    }
    if (e.key === 'ArrowUp') {
      e.preventDefault();
      container.scrollBy({ top: -container.clientHeight, behavior: 'smooth' });
    } else if (e.key === 'ArrowDown') {
      e.preventDefault();
      container.scrollBy({ top: container.clientHeight, behavior: 'smooth' });
    }
  });

  // Unmute active video on first user interaction if the browser forced a muted autoplay fallback
  const unmuteOnInteraction = () => {
    const slides = document.querySelectorAll('.reel-slide');
    if (slides[currentActiveIndex]) {
      const activeVideo = slides[currentActiveIndex].querySelector('.reel-video');
      if (activeVideo && !isMutedGlobal) {
        if (activeVideo.tagName === 'VIDEO') {
          activeVideo.muted = false;
        } else {
          const iframe = activeVideo.querySelector('iframe');
          if (iframe && iframe.contentWindow) {
            iframe.contentWindow.postMessage(JSON.stringify({
              event: 'command',
              func: 'unmute',
              args: ''
            }), '*');
          }
        }
        updateMuteIcons();
      }
    }
    document.removeEventListener('click', unmuteOnInteraction);
    document.removeEventListener('touchstart', unmuteOnInteraction);
  };
  document.addEventListener('click', unmuteOnInteraction);
  document.addEventListener('touchstart', unmuteOnInteraction);
});

async function loadMoreReels() {
  if (isReelsLoading || !hasNextPage) return;
  isReelsLoading = true;

  const urlParams = new URLSearchParams(window.location.search);
  const startId = urlParams.get('id') || '';
  const startIdParam = (startId && reelsPage === 1) ? `&start_id=${startId}` : '';
  const url = `${FH_BASE}/api/videos.php?is_reel=1&page=${reelsPage}&per_page=10${startIdParam}`;
  try {
    const res = await fetch(url);
    const data = await res.json();
    if (data && data.videos && data.videos.length) {
      const container = document.getElementById('reels-slider');
      data.videos.forEach(v => {
        if (container.querySelector(`.reel-slide[data-id="${v.id}"]`)) return;
        appendReelSlide(v);
        reelsList.push(v);
      });

      reelsPage++;
      hasNextPage = data.has_next;
      
      cache.saveFeed(reelsList);

      updateMediaSources(currentActiveIndex);
    } else {
      hasNextPage = false;
    }
  } catch (err) {
    console.warn("API fetch error for loadMoreReels", err);
    hasNextPage = false;
  }

  isReelsLoading = false;
}

function togglePlay(overlay) {
  const slide = overlay.closest('.reel-slide');
  const video = slide.querySelector('.reel-video');
  if (!video) return;

  if (video.tagName === 'VIDEO') {
    if (video.paused) {
      video.play();
      overlay.classList.remove('paused');
    } else {
      video.pause();
      overlay.classList.add('paused');
    }
  } else {
    const iframe = video.querySelector('iframe');
    if (iframe && iframe.contentWindow) {
      if (overlay.classList.contains('paused')) {
        iframe.contentWindow.postMessage('{"event":"command","func":"playVideo","args":""}', '*');
        overlay.classList.remove('paused');
      } else {
        iframe.contentWindow.postMessage('{"event":"command","func":"pauseVideo","args":""}', '*');
        overlay.classList.add('paused');
      }
    }
  }
}

function toggleMuteState() {
  isMutedGlobal = !isMutedGlobal;
  localStorage.setItem('reels_muted', isMutedGlobal ? 'true' : 'false');
  
  const videos = document.querySelectorAll('.reel-video');
  videos.forEach(v => {
    if (v.tagName === 'VIDEO') {
      v.muted = isMutedGlobal;
    } else {
      const iframe = v.querySelector('iframe');
      if (iframe && iframe.contentWindow) {
        iframe.contentWindow.postMessage(JSON.stringify({
          event: 'command',
          func: isMutedGlobal ? 'mute' : 'unmute',
          args: ''
        }), '*');
      }
    }
  });

  updateMuteIcons();
}

function updateMuteIcons() {
  const muteBtns = document.querySelectorAll('.mute-btn');
  muteBtns.forEach(btn => {
    const waves = btn.querySelector('.volume-waves');
    if (isMutedGlobal) {
      if (waves) waves.style.display = 'none';
      btn.title = "Unmute";
    } else {
      if (waves) waves.style.display = 'block';
      btn.title = "Mute";
    }
  });
}

async function toggleLike(btn, id) {
  const liked = btn.classList.contains('liked');
  btn.classList.toggle('liked');
  const heart = btn.querySelector('.heart-icon');
  const countSpan = btn.querySelector('.like-count');
  let currentCount = parseInt(countSpan.textContent) || 0;

  if (liked) {
    heart.setAttribute('fill', 'none');
    heart.setAttribute('stroke', 'currentColor');
    countSpan.textContent = currentCount > 1 ? format_number(currentCount - 1) : '';
  } else {
    heart.setAttribute('fill', 'var(--red)');
    heart.setAttribute('stroke', 'var(--red)');
    countSpan.textContent = format_number(currentCount + 1);
  }

  try {
    const res = await fetch(`${FH_BASE}/api/videos.php?action=reel_react`, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ video_id: id, type: 'like' })
    });
    const d = await res.json();
    if (d.success && d.data) {
      countSpan.textContent = format_number(d.data.likes);
      if (d.data.user_reaction === 'like') {
        btn.classList.add('liked');
        heart.setAttribute('fill', 'var(--red)');
        heart.setAttribute('stroke', 'var(--red)');
      } else {
        btn.classList.remove('liked');
        heart.setAttribute('fill', 'none');
        heart.setAttribute('stroke', 'currentColor');
      }
    }
  } catch (e) {}
}

let lastTapTime = 0;
function handleTapOrDoubleTap(zone, id) {
  const now = Date.now();
  if (now - lastTapTime < 300) {
    handleDoubleTap(zone, id);
  } else {
    const overlay = zone.closest('.reel-slide').querySelector('.reel-play-overlay');
    if (overlay) togglePlay(overlay);
  }
  lastTapTime = now;
}

function handleDoubleTap(zone, id) {
  const slide = zone.closest('.reel-slide');
  const rect = zone.getBoundingClientRect();
  const e = window.event;
  
  let x = rect.width / 2;
  let y = rect.height / 2;
  if (e && e.clientX) {
    x = e.clientX - rect.left;
    y = e.clientY - rect.top;
  }

  const heart = document.createElement('div');
  heart.className = 'floating-heart';
  heart.style.left = `${x}px`;
  heart.style.top = `${y}px`;
  heart.innerHTML = `
    <svg width="60" height="60" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
  `;
  zone.appendChild(heart);

  const likeBtn = slide.querySelector('.like-btn');
  if (likeBtn && !likeBtn.classList.contains('liked')) {
    toggleLike(likeBtn, id);
  }

  setTimeout(() => heart.remove(), 800);
}

function shareReel(id, btn) {
  const baseUrlMeta = document.querySelector('meta[name="base-url"]');
  const baseUrl = baseUrlMeta ? baseUrlMeta.content : (window.location.origin + '/vid/freehub');
  const url = `${baseUrl}/reels/${id}`;
  navigator.clipboard.writeText(url).then(() => {
    const textSpan = btn.querySelector('span');
    const originalText = textSpan.textContent;
    textSpan.textContent = 'Copied!';
    setTimeout(() => {
      textSpan.textContent = originalText;
    }, 2000);
  });
}

async function openComments(id) {
  activeCommentVideoId = id;
  const panel = document.getElementById('reels-comments-panel');
  panel.classList.add('open');

  const container = document.getElementById('comments-list-container');
  container.innerHTML = '<div style="text-align:center; padding: 20px; color: var(--text2);">Loading comments...</div>';

  try {
    const res = await fetch(`${FH_BASE}/api/videos.php?action=reel_comments&video_id=${id}`);
    const d = await res.json();
    
    if (d.success && d.data) {
      container.innerHTML = '';
      if (d.data.length === 0) {
        container.innerHTML = '<div style="text-align:center; padding: 40px; color: var(--text2); font-size: 0.85rem;">No comments yet. Be the first to share your thoughts!</div>';
        return;
      }
      d.data.forEach(c => {
        const item = document.createElement('div');
        item.className = 'comment-item';
        item.innerHTML = `
          <img class="comment-avatar" src="${c.avatar || '<?= BASE_URL ?>/assets/img/default-avatar.jpg'}" alt="${escapeHtml(c.username)}">
          <div class="comment-body">
            <div class="comment-username">${escapeHtml(c.username)}</div>
            <div class="comment-content">${escapeHtml(c.content)}</div>
          </div>
        `;
        container.appendChild(item);
      });
    } else {
      container.innerHTML = '<div style="text-align:center; padding: 20px; color: var(--red);">Could not load comments.</div>';
    }
  } catch (e) {
    container.innerHTML = '<div style="text-align:center; padding: 20px; color: var(--red);">Error loading comments.</div>';
  }
}

function closeComments() {
  const panel = document.getElementById('reels-comments-panel');
  panel.classList.remove('open');
  activeCommentVideoId = null;
}

async function postReelComment(e) {
  e.preventDefault();
  const input = document.getElementById('new-comment-field');
  const content = input.value.trim();
  if (!content || !activeCommentVideoId) return;

  const btn = e.target.querySelector('button[type="submit"]');
  btn.disabled = true;
  btn.textContent = '...';

  try {
    const res = await fetch(`${FH_BASE}/api/videos.php?action=reel_comment`, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ video_id: activeCommentVideoId, content: content })
    });
    const d = await res.json();

    if (d.success) {
      input.value = '';
      
      const slide = document.querySelector(`.reel-slide[data-id="${activeCommentVideoId}"]`);
      if (slide) {
        const countSpan = slide.querySelector('.comment-count');
        if (countSpan) {
          const currentCount = parseInt(countSpan.textContent) || 0;
          countSpan.textContent = format_number(currentCount + 1);
        }
      }

      await openComments(activeCommentVideoId);
    } else {
      alert('Failed to post comment: ' + (d.message || 'Server error'));
    }
  } catch(err) {
    alert('Network error posting comment.');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Post';
  }
}

function handleVideoError(video) {
  const rawSrc = video.getAttribute('data-src');
  if (rawSrc && video.src !== rawSrc) {
    console.warn("Blob URL playback failed, falling back to direct stream:", rawSrc);
    video.src = rawSrc;
    video.load();
    const slide = video.closest('.reel-slide');
    playActiveVideo(video, slide);
  }
}

function escapeHtml(str) {
  if (!str) return '';
  return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
