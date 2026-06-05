<?php
// ============================================================
// FreeHub.Live — Reels Feed Page
// ============================================================
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$meta_title = 'Reels — FreeHub';
$meta_desc  = 'Watch short vertical videos on FreeHub.';
require_once __DIR__ . '/includes/header.php';

// Fetch initial reels randomly
$reels = db_fetchAll(
    "SELECT v.*, u.username, u.channel_name, u.avatar,
            (SELECT COUNT(*) FROM comments WHERE video_id = v.id AND status = 'visible') AS comments_count,
            (SELECT type FROM video_reactions WHERE video_id = v.id AND user_id = ?) AS user_reaction
     FROM videos v
     JOIN users u ON u.id = v.user_id
     WHERE v.status = 'published' AND v.visibility = 'public' AND v.is_reel = 1
     ORDER BY RAND() LIMIT 15",
    [is_logged_in() ? (int)auth_user()['id'] : 0]
);
?>

<div class="layout">
  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

  <main class="main-content" style="padding: 0; background: #000; overflow: hidden; height: calc(100vh - 56px); position: relative;">
    
    <?php if (empty($reels)): ?>
      <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; color:var(--text2); text-align:center; padding: 20px;">
        <svg width="64" height="64" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="opacity:.4; margin-bottom:16px;">
          <rect x="6" y="2" width="12" height="20" rx="2" ry="2"/>
          <line x1="12" y1="18" x2="12.01" y2="18"/>
        </svg>
        <h2 style="font-size:1.3rem; margin-bottom:8px; color:#fff;">No Reels Yet</h2>
        <p style="font-size:0.9rem; margin-bottom:20px; max-width: 320px;">Be the first to upload a short vertical video (under 60s) to kickstart the Reels feed!</p>
        <a href="<?= BASE_URL ?>/creator/upload.php?mode=reel" class="btn btn-primary" style="font-weight:700;">Create a Reel</a>
      </div>
    <?php else: ?>
      
      <!-- Reels feed slider -->
      <div class="reels-container" id="reels-slider">
        <?php foreach ($reels as $r): 
          $is_liked = ($r['user_reaction'] ?? '') === 'like';
          $video_src = video_url($r['video_url']);
        ?>
          <div class="reel-slide" data-id="<?= $r['id'] ?>" data-title="<?= e($r['title']) ?>" data-views="<?= $r['views'] ?>" data-likes="<?= $r['likes'] ?>" data-comments="<?= $r['comments_count'] ?>">
            
            <!-- Video tag -->
            <video class="reel-video" src="<?= e($video_src) ?>" loop playsinline preload="auto" style="width:100%; height:100%; object-fit:cover; background:#000;"></video>
            
            <!-- Top Ad Banner Overlay -->
            <?php if (function_exists('render_ad_placeholder') && !empty($ad_html = render_ad_placeholder('reels_top_overlay'))): ?>
              <div class="reel-ad-overlay">
                <?= $ad_html ?>
              </div>
            <?php endif; ?>
            
            <!-- Tap to play/pause indicator overlay -->
            <div class="reel-play-overlay" onclick="togglePlay(this)">
              <div class="play-icon-shape">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
              </div>
            </div>

            <!-- Double-tap like animation anchor -->
            <div class="double-tap-zone" ondblclick="handleDoubleTap(this, <?= $r['id'] ?>)"></div>

            <!-- Bottom Left Info Overlay -->
            <div class="reel-info-overlay">
              <div class="reel-creator-row">
                <a href="<?= BASE_URL ?>/channel.php?id=<?= $r['user_id'] ?>&tab=videos" style="display:flex; align-items:center; gap:10px; color:#fff; font-weight:700; text-decoration:none;">
                  <img class="reel-creator-avatar" src="<?= avatar_url($r['avatar']) ?>" alt="<?= e($r['channel_name']??$r['username']) ?>">
                  <span><?= e($r['channel_name'] ?: $r['username']) ?></span>
                </a>
              </div>
              <h2 class="reel-title"><?= e($r['title']) ?></h2>
              <?php if (!empty($r['description'])): ?>
                <p class="reel-desc"><?= e(truncate($r['description'], 120)) ?></p>
              <?php endif; ?>
            </div>

            <!-- Right Sidebar Action Controls -->
            <div class="reel-sidebar-actions">
              <!-- Creator profile -->
              <a href="<?= BASE_URL ?>/channel.php?id=<?= $r['user_id'] ?>&tab=videos" class="reel-side-btn creator-profile-btn" title="Visit Channel">
                <img src="<?= avatar_url($r['avatar']) ?>" alt="Creator">
              </a>

              <!-- Like button -->
              <button type="button" class="reel-side-btn like-btn <?= $is_liked ? 'liked' : '' ?>" onclick="toggleLike(this, <?= $r['id'] ?>)" title="Like">
                <div class="action-icon-wrap">
                  <svg class="heart-icon" viewBox="0 0 24 24" fill="<?= $is_liked ? 'var(--red)' : 'none' ?>" stroke="<?= $is_liked ? 'var(--red)' : 'currentColor' ?>" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                </div>
                <span class="like-count"><?= format_number((int)$r['likes']) ?></span>
              </button>

              <!-- Comments button -->
              <button type="button" class="reel-side-btn comment-trigger-btn" onclick="openComments(<?= $r['id'] ?>)" title="Comments">
                <div class="action-icon-wrap">
                  <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                </div>
                <span class="comment-count"><?= format_number((int)$r['comments_count']) ?></span>
              </button>

              <!-- Share button -->
              <button type="button" class="reel-side-btn share-btn" onclick="shareReel(<?= $r['id'] ?>, this)" title="Copy Link">
                <div class="action-icon-wrap">
                  <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                </div>
                <span>Share</span>
              </button>

              <!-- Mute/Unmute state controller -->
              <button type="button" class="reel-side-btn mute-btn" onclick="toggleMuteState()" title="Mute/Unmute">
                <div class="action-icon-wrap">
                  <svg class="mute-icon-svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/>
                    <path class="volume-waves" d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/>
                  </svg>
                </div>
              </button>
            </div>

          </div>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>

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

  </main>
</div>

<style>
/* Reels Feed Style Customizations */
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
.reel-ad-overlay {
  position: absolute;
  top: 12px;
  left: 50%;
  transform: translateX(-50%);
  width: calc(100% - 24px);
  max-width: 340px;
  z-index: 10;
  display: flex;
  justify-content: center;
  pointer-events: auto;
  text-align: center;
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
.reel-desc {
  font-size: 0.82rem;
  color: #e2e8f0;
  line-height: 1.4;
  margin: 0;
  text-shadow: 0 1px 2px rgba(0,0,0,0.5);
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

/* Floating double-tap heart animation style */
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

/* Comments slide-in panel */
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

// Initialize IntersectionObserver to handle vertical swipe autoplay
document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('reels-slider');
  if (!container) return;

  const slides = container.querySelectorAll('.reel-slide');
  
  // Set initial volume/mute settings
  updateMuteIcons();

  const observerOptions = {
    root: container,
    rootMargin: '0px',
    threshold: 0.6 // Autoplay when 60% of the slide is visible
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      const video = entry.target.querySelector('.reel-video');
      if (!video) return;

      if (entry.isIntersecting) {
        // Play video
        video.muted = isMutedGlobal;
        const playPromise = video.play();
        if (playPromise !== undefined) {
          playPromise.catch(error => {
            // Auto-play was prevented (browser restrictions), show paused icon
            entry.target.querySelector('.reel-play-overlay').classList.add('paused');
          });
        }
      } else {
        // Pause video & reset
        video.pause();
        video.currentTime = 0;
        entry.target.querySelector('.reel-play-overlay').classList.remove('paused');
      }
    });
  }, observerOptions);

  slides.forEach(slide => observer.observe(slide));

  // Keyboard navigation support
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
});

// Toggle play/pause state of active video
function togglePlay(overlay) {
  const slide = overlay.closest('.reel-slide');
  const video = slide.querySelector('.reel-video');
  if (!video) return;

  if (video.paused) {
    video.play();
    overlay.classList.remove('paused');
  } else {
    video.pause();
    overlay.classList.add('paused');
  }
}

// Global synced volume/mute controller
function toggleMuteState() {
  isMutedGlobal = !isMutedGlobal;
  localStorage.setItem('reels_muted', isMutedGlobal ? 'true' : 'false');
  
  const videos = document.querySelectorAll('.reel-video');
  videos.forEach(v => {
    v.muted = isMutedGlobal;
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

// Toggle Like logic via API
async function toggleLike(btn, id) {
  const liked = btn.classList.contains('liked');
  const actionType = liked ? 'like' : 'like'; // Reacting toggles
  
  btn.classList.toggle('liked');
  const heart = btn.querySelector('.heart-icon');
  const countSpan = btn.querySelector('.like-count');
  let currentCount = parseInt(countSpan.textContent) || 0;

  if (liked) {
    // Unlike
    heart.setAttribute('fill', 'none');
    heart.setAttribute('stroke', 'currentColor');
    countSpan.textContent = Math.max(0, currentCount - 1);
  } else {
    // Like
    heart.setAttribute('fill', 'var(--red)');
    heart.setAttribute('stroke', 'var(--red)');
    countSpan.textContent = currentCount + 1;
  }

  try {
    const res = await fetch('<?= BASE_URL ?>/api/videos.php?action=react', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ video_id: id, type: 'like' })
    });
    const d = await res.json();
    // Sync actual counts if backend returns it
    if (d.success && d.data) {
      countSpan.textContent = d.data.likes;
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

// Double tap zone handler
function handleDoubleTap(zone, id) {
  // Create animated floating heart at tap coordinate
  const slide = zone.closest('.reel-slide');
  const rect = zone.getBoundingClientRect();
  const e = window.event;
  
  // Coordinates relative to zone
  const x = e.clientX - rect.left;
  const y = e.clientY - rect.top;

  const heart = document.createElement('div');
  heart.className = 'floating-heart';
  heart.style.left = `${x}px`;
  heart.style.top = `${y}px`;
  heart.innerHTML = `
    <svg width="60" height="60" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
  `;
  zone.appendChild(heart);

  // Trigger like button toggle
  const likeBtn = slide.querySelector('.like-btn');
  if (likeBtn && !likeBtn.classList.contains('liked')) {
    toggleLike(likeBtn, id);
  }

  // Remove heart element after animation completes
  setTimeout(() => heart.remove(), 800);
}

// Copy Share link
function shareReel(id, btn) {
  const url = `${window.location.origin}${window.location.pathname.replace('reels.php', 'watch.php')}?v=${id}`;
  navigator.clipboard.writeText(url).then(() => {
    const textSpan = btn.querySelector('span');
    const originalText = textSpan.textContent;
    textSpan.textContent = 'Copied!';
    setTimeout(() => {
      textSpan.textContent = originalText;
    }, 2000);
  });
}

// Open Comments Sliding Panel
async function openComments(id) {
  activeCommentVideoId = id;
  const panel = document.getElementById('reels-comments-panel');
  panel.classList.add('open');

  const container = document.getElementById('comments-list-container');
  container.innerHTML = '<div style="text-align:center; padding: 20px; color: var(--text2);">Loading comments...</div>';

  try {
    const res = await fetch(`<?= BASE_URL ?>/api/videos.php?action=comments&video_id=${id}`);
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
          <img class="comment-avatar" src="${c.avatar || '<?= BASE_URL ?>/assets/img/default-avatar.jpg'}" alt="${c.username}">
          <div class="comment-body">
            <div class="comment-username">${c.username}</div>
            <div class="comment-content">${c.content}</div>
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

// Close Comments Sliding Panel
function closeComments() {
  const panel = document.getElementById('reels-comments-panel');
  panel.classList.remove('open');
  activeCommentVideoId = null;
}

// Post Comment
async function postReelComment(e) {
  e.preventDefault();
  const input = document.getElementById('new-comment-field');
  const content = input.value.trim();
  if (!content || !activeCommentVideoId) return;

  const btn = e.target.querySelector('button[type="submit"]');
  btn.disabled = true;
  btn.textContent = '...';

  try {
    const res = await fetch('<?= BASE_URL ?>/api/videos.php?action=comment', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ video_id: activeCommentVideoId, content: content })
    });
    const d = await res.json();

    if (d.success) {
      input.value = '';
      
      // Update comment count on slide
      const slide = document.querySelector(`.reel-slide[data-id="${activeCommentVideoId}"]`);
      if (slide) {
        const countSpan = slide.querySelector('.comment-count');
        if (countSpan) {
          const currentCount = parseInt(countSpan.textContent) || 0;
          countSpan.textContent = currentCount + 1;
        }
      }

      // Reload comments list
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
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
