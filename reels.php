<?php
// ============================================================
// FreeHub.Live — Dedicated Reels Feed Page
// ============================================================
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Check if feature flag is active
if (setting('reels_enabled', '1') !== '1') {
    redirect(BASE_URL . '/');
}

$starting_reel_id = (int)($_GET['v'] ?? 0);

// Fetch all published public reels
// Prioritize starting reel if provided in query string
$reels = db_fetchAll(
    "SELECT v.*, u.username, u.channel_name, u.avatar, u.subscribers
     FROM videos v
     JOIN users u ON u.id = v.user_id
     WHERE v.is_reel = 1 AND v.status = 'published' AND v.visibility = 'public'
     ORDER BY (v.id = ?) DESC, v.published_at DESC",
    [$starting_reel_id]
);

$meta_title = 'Reels — ' . setting('site_name', 'FreeHub');
$meta_desc  = 'Watch trending short vertical videos.';

$left_placement = db_fetch("SELECT ad_width FROM ad_placements WHERE key_name = 'reels_left'");
$right_placement = db_fetch("SELECT ad_width FROM ad_placements WHERE key_name = 'reels_right'");
$mobile_top_placement = db_fetch("SELECT ad_width FROM ad_placements WHERE key_name = 'reels_mobile_top'");
$left_w = (int)($left_placement['ad_width'] ?? 160);
$right_w = (int)($right_placement['ad_width'] ?? 160);
$mobile_top_w = (int)($mobile_top_placement['ad_width'] ?? 320);

$is_reels = true; // Marks active state in header
require_once __DIR__ . '/includes/header.php';
?>

<style>
/* CSS Reset and layout overrides for Reels Page */
body {
  overflow: hidden !important;
  background-color: #000 !important;
}

@media (max-width: 768px) {
  #main-navbar, .sidebar, footer, .footer {
    display: none !important;
  }
  body {
    padding-top: 0 !important;
    padding-bottom: 0 !important;
  }
}

.reels-container {
  display: flex;
  flex-direction: column;
  height: calc(100vh - 56px); /* Height minus navbar on desktop */
  position: relative;
  overflow: hidden;
  background-color: #0a0a0c;
  color: #fff;
}

@media (max-width: 768px) {
  .reels-container {
    height: 100dvh;
    margin-top: 0 !important;
  }
}

.no-reels {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  text-align: center;
  color: var(--text2);
}
.no-reels svg {
  color: var(--accent);
  margin-bottom: 20px;
  opacity: 0.6;
}

/* Mobile overlay header - Removed home/reel buttons and replaced with ad and marquee title */
.reel-mobile-top-overlay {
  position: absolute;
  top: 8px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 95;
  width: calc(100% - 32px);
  max-width: <?= $mobile_top_w ?>px;
  display: none;
  flex-direction: column;
  gap: 0px;
  pointer-events: none;
}

@media (max-width: 768px) {
  .reel-mobile-top-overlay {
    display: flex;
  }
}

.reel-mobile-top-title-wrapper {
  width: 100%;
  background: transparent;
  backdrop-filter: none;
  -webkit-backdrop-filter: none;
  border: none;
  box-shadow: none;
  padding: 4px 0;
  overflow: hidden;
  pointer-events: auto;
  box-sizing: border-box;
  text-align: center;
}

.reel-mobile-top-title {
  font-size: 0.88rem;
  font-weight: 600;
  color: #fff;
  white-space: nowrap;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.6);
  display: inline-block;
  max-width: 100%;
  transition: transform 0.1s linear;
}

.reel-mobile-top-title.scroll-active {
  max-width: none;
  animation: marquee-scroll 4s ease-in-out infinite alternate;
}

@keyframes marquee-scroll {
  0%, 15% { transform: translate3d(0, 0, 0); }
  85%, 100% { transform: translate3d(var(--scroll-dist, 0px), 0, 0); }
}

.creator-badge-container {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s ease;
  border-radius: 30px;
}

@media (max-width: 768px) {
  .reel-creator-row.is-subbed-state .creator-badge-container {
    background: linear-gradient(90deg, var(--accent) 0%, #ff007f 50%, #7000ff 100%);
    padding: 6px 14px 6px 6px;
    border-radius: 30px;
    box-shadow: 0 4px 15px rgba(255, 0, 127, 0.35);
    border: 1px solid rgba(255, 255, 255, 0.25);
  }
  .reel-creator-row.is-subbed-state .creator-badge-container .creator-name {
    color: #fff !important;
    text-shadow: none !important;
    font-weight: 700;
  }
  .reel-creator-row.is-subbed-state .creator-badge-container .creator-avatar {
    border: 1.5px solid #fff !important;
  }
}

.panel-header.is-subbed-state .sub-btn-reel,
.reel-creator-row.is-subbed-state .sub-btn-reel {
  display: none !important;
}

/* Reels-Specific Layout & Ad Placement Styling */
.reel-middle-column {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex: 1;
  min-width: 0;
  height: 100%;
}
@media (max-width: 768px) {
  .reel-middle-column {
    max-width: 100%;
    height: 100%;
    display: block;
  }
}

.reel-player-and-panel-row {
  display: flex;
  flex-direction: row;
  gap: 20px;
  justify-content: center;
  align-items: stretch;
  width: 100%;
  flex: 1;
  min-height: 0;
}
@media (max-width: 768px) {
  .reel-player-and-panel-row {
    display: block;
    width: 100%;
    height: 100%;
  }
}

.reel-left-ad-wrapper {
  width: <?= $left_w ?>px;
  flex-shrink: 0;
  display: flex;
  justify-content: flex-end;
  align-items: stretch;
  height: 100%;
}
@media (max-width: 1024px) {
  .reel-left-ad-wrapper {
    display: none !important;
  }
}

.reel-right-ad-wrapper {
  width: <?= $right_w ?>px;
  flex-shrink: 0;
  display: flex;
  justify-content: flex-start;
  align-items: stretch;
  height: 100%;
}
@media (max-width: 1024px) {
  .reel-right-ad-wrapper {
    display: none !important;
  }
}

.reel-left-ad-wrapper .ad-sponsored-container,
.reel-right-ad-wrapper .ad-sponsored-container {
  height: 100% !important;
  margin: 0 !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  flex-direction: column;
  width: 100% !important;
  background: rgba(0, 0, 0, 0.4) !important;
  border: 1px solid rgba(255, 255, 255, 0.08) !important;
  border-radius: 12px !important;
  box-sizing: border-box;
}

.reel-bottom-ad-wrapper {
  width: 100%;
  max-width: 820px;
  margin-top: 12px;
  display: flex;
  justify-content: center;
  align-items: center;
}
@media (max-width: 768px) {
  .reel-bottom-ad-wrapper {
    display: none !important;
  }
}

.reel-mobile-top-overlay .ad-sponsored-container {
  margin: 0 !important;
  padding: 0.3rem !important;
  width: 100% !important;
  max-width: 100% !important;
  background: rgba(0, 0, 0, 0.45) !important;
  backdrop-filter: blur(12px) !important;
  -webkit-backdrop-filter: blur(12px) !important;
  border: 1px solid rgba(255, 255, 255, 0.15) !important;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5) !important;
  border-radius: 8px !important;
  pointer-events: auto;
}

.ad-sponsored-container {
  pointer-events: auto !important;
}

/* Subscribe button sizing & styling */
.sub-btn-reel {
  border-radius: 20px;
  font-weight: 700;
  padding: 8px 16px !important;
  font-size: 0.85rem !important;
  transition: all 0.2s;
  border: none;
  cursor: pointer;
  height: 36px !important;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.sub-btn-reel.subbed {
  background: linear-gradient(90deg, var(--accent) 0%, #ff007f 50%, #7000ff 100%) !important;
  color: #fff !important;
  box-shadow: 0 2px 10px rgba(255, 0, 127, 0.25) !important;
}

/* Like icon pop micro-animation */
@keyframes heartPop {
  0% { transform: scale(1); }
  50% { transform: scale(1.3); }
  100% { transform: scale(1); }
}
.like-btn.liked svg, .action-btn.liked .icon-wrap svg {
  animation: heartPop 0.3s ease-in-out;
}

/* Desktop Liked color support */
.like-btn.liked {
  color: #ef4444 !important;
  border-color: #ef4444 !important;
  background: rgba(239, 68, 68, 0.08) !important;
}

/* Feed Container */
.reels-feed {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
  height: 100%;
  overflow-y: scroll;
  scroll-behavior: smooth;
  scrollbar-width: none; /* Hide scrollbars */
}
.reels-feed::-webkit-scrollbar {
  display: none;
}

@media (max-width: 768px) {
  .reels-feed {
    scroll-snap-type: y mandatory;
    width: 100%;
  }
}

/* Slide item */
.reel-slide {
  display: flex;
  width: 100%;
  max-width: <?= (450 + 350 + $left_w + $right_w + 120) ?>px;
  height: 100%;
  flex-shrink: 0;
  padding: 20px 0;
  gap: 20px;
  scroll-snap-align: start;
}

@media (max-width: 768px) {
  .reel-slide {
    max-width: 100%;
    height: 100%;
    padding: 0;
    gap: 0;
  }
}

/* Player container */
.reel-player-container {
  flex: 1;
  background-color: #000;
  border-radius: 16px;
  overflow: hidden;
  position: relative;
  box-shadow: 0 8px 32px rgba(0,0,0,0.6);
  aspect-ratio: 9/16;
  max-width: 450px;
  margin: 0 auto;
}

@media (max-width: 768px) {
  .reel-player-container {
    max-width: 100%;
    height: 100%;
    border-radius: 0;
    box-shadow: none;
  }
}

.reel-video {
  width: 100%;
  height: 100%;
  object-fit: cover;
  cursor: pointer;
}

/* Play/Volume feedback overlays */
.video-overlay-play-btn, .video-overlay-volume-btn {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%) scale(0.8);
  background: rgba(0,0,0,0.6);
  border-radius: 50%;
  width: 70px;
  height: 70px;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  pointer-events: none;
  transition: all 0.3s ease;
  z-index: 10;
}

.video-overlay-play-btn.show, .video-overlay-volume-btn.show {
  opacity: 1;
  transform: translate(-50%, -50%) scale(1);
}

/* Mobile info/actions overlays (Only on mobile view) */
.reel-mobile-info {
  display: none;
  position: absolute;
  bottom: 20px;
  left: 16px;
  right: 80px;
  z-index: 5;
  color: #fff;
  pointer-events: auto;
}

.reel-mobile-actions {
  display: none;
  position: absolute;
  bottom: 30px;
  right: 16px;
  z-index: 5;
  flex-direction: column;
  align-items: center;
  gap: 20px;
}

@media (max-width: 768px) {
  .reel-mobile-info {
    display: flex !important;
    flex-direction: column !important;
    align-items: flex-start !important;
    gap: 8px !important;
    left: 16px !important;
    right: 80px !important;
  }
  .reel-creator-row {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    flex-wrap: nowrap !important;
    gap: 10px !important;
    width: 100% !important;
  }
  .reel-title-row {
    display: block !important;
    width: 100% !important;
    clear: both !important;
    margin-top: 6px !important;
  }
  .reel-mobile-actions {
    display: flex;
  }
}

.reel-creator-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
}
.reel-creator-row .creator-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 1.5px solid #fff;
  object-fit: cover;
}
.reel-creator-row .creator-name {
  font-weight: 700;
  color: #fff;
  text-shadow: 0 1px 3px rgba(0,0,0,0.8);
  font-size: 0.95rem;
  text-decoration: none;
}
.reel-title-row {
  font-size: 0.9rem;
  line-height: 1.4;
  text-shadow: 0 1px 3px rgba(0,0,0,0.8);
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

/* Merged sub-btn-reel styling with primary style block */

/* Action button items */
.action-btn {
  background: none;
  border: none;
  color: #fff;
  display: flex;
  flex-direction: column;
  align-items: center;
  cursor: pointer;
  padding: 0;
}
.action-btn .icon-wrap {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: rgba(0,0,0,0.4);
  backdrop-filter: blur(10px);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 5px;
  transition: transform 0.2s;
  box-shadow: 0 4px 10px rgba(0,0,0,0.3);
  border: 1px solid rgba(255,255,255,0.08);
}
.action-btn:hover .icon-wrap {
  transform: scale(1.1);
}
.action-btn.liked .icon-wrap {
  color: #ef4444;
  background: rgba(239, 68, 68, 0.15);
  border-color: rgba(239, 68, 68, 0.3);
}
.action-btn .count-label {
  font-size: 0.7rem;
  font-weight: 700;
  text-shadow: 0 1px 3px rgba(0,0,0,0.8);
}

/* Desktop right details panel */
.reel-desktop-panel {
  width: 350px;
  background: var(--bg2, #18181b);
  border-radius: 16px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 8px 32px rgba(0,0,0,0.4);
  border: 1px solid var(--border, rgba(255,255,255,0.08));
}

@media (max-width: 768px) {
  .reel-desktop-panel {
    display: none;
  }
}

.reel-desktop-panel .panel-header {
  padding: 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  border-bottom: 1px solid var(--border, rgba(255,255,255,0.08));
}
.reel-desktop-panel .panel-header .creator-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  object-fit: cover;
  border: 1px solid var(--border);
}
.reel-desktop-panel .panel-header .creator-details {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.reel-desktop-panel .panel-header .creator-name {
  font-weight: 700;
  color: var(--text, #fff);
  text-decoration: none;
  font-size: 0.95rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.reel-desktop-panel .panel-header .sub-count {
  font-size: 0.75rem;
  color: var(--text2, rgba(255,255,255,0.6));
}

.reel-desktop-panel .panel-body {
  padding: 16px;
  border-bottom: 1px solid var(--border, rgba(255,255,255,0.08));
}
.reel-desktop-panel .reel-title {
  font-size: 1rem;
  font-weight: 700;
  margin: 0 0 10px 0;
  line-height: 1.4;
  color: var(--text);
}
.reel-desktop-panel .reel-stats {
  display: flex;
  gap: 12px;
  font-size: 0.8rem;
  color: var(--text2);
  margin-bottom: 16px;
}
.reel-desktop-panel .panel-actions {
  display: flex;
  gap: 10px;
}

/* Comments section inside panel */
.panel-comments-section {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  padding: 16px;
}
.panel-comments-section h3 {
  font-size: 0.95rem;
  font-weight: 800;
  margin: 0 0 12px 0;
  color: var(--text);
}
.panel-comments-list, .panel-comments-list-mobile {
  flex: 1;
  overflow-y: auto;
  margin-bottom: 12px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding-right: 4px;
}
.panel-comments-list::-webkit-scrollbar, .panel-comments-list-mobile::-webkit-scrollbar {
  width: 5px;
}
.panel-comments-list::-webkit-scrollbar-thumb, .panel-comments-list-mobile::-webkit-scrollbar-thumb {
  background: var(--border, rgba(255,255,255,0.15));
  border-radius: 4px;
}

.comment-item {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  font-size: 0.82rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.03);
  padding-bottom: 8px;
}
.comment-item .comment-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
}
.comment-item .comment-user {
  font-weight: 700;
  color: var(--accent);
  margin-right: 6px;
}
.comment-item .comment-text {
  color: var(--text);
  line-height: 1.3;
}
.comment-item .comment-time {
  font-size: 0.7rem;
  color: var(--text2);
  margin-top: 3px;
}

/* Form input */
.reel-comment-form, .reel-comment-form-mobile {
  display: flex;
  gap: 8px;
  border-top: 1px solid var(--border, rgba(255,255,255,0.08));
  padding-top: 12px;
}
.comment-input {
  flex: 1;
  background: var(--bg3, #27272a);
  border: 1px solid var(--border, rgba(255,255,255,0.08));
  color: #fff;
  border-radius: 20px;
  padding: 8px 16px;
  font-size: 0.85rem;
  outline: none;
}
.comment-input:focus {
  border-color: var(--accent);
}
.comment-submit-btn {
  background: var(--accent);
  color: #fff;
  border: none;
  border-radius: 20px;
  padding: 8px 16px;
  font-size: 0.85rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}
.comment-submit-btn:hover {
  filter: brightness(1.15);
}

/* Navigation controls desktop */
.reel-nav-arrow {
  position: absolute;
  left: calc(50% - 310px);
  background: rgba(0,0,0,0.5);
  border: 1px solid rgba(255,255,255,0.1);
  color: #fff;
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 10;
  transition: all 0.2s;
  box-shadow: 0 4px 15px rgba(0,0,0,0.5);
}
.reel-nav-arrow:hover {
  background: var(--accent);
  border-color: var(--accent);
  transform: scale(1.05);
}
.prev-arrow {
  top: calc(50% - 40px);
}
.next-arrow {
  top: calc(50% + 40px);
}

@media (max-width: 950px) {
  .reel-nav-arrow {
    left: 20px;
  }
}
@media (max-width: 768px) {
  .reel-nav-arrow {
    display: none;
  }
}

/* Mobile Comments Panel Slide-up */
.mobile-comments-panel {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 200;
  display: none;
  pointer-events: none;
}
.mobile-comments-panel.active {
  display: block;
  pointer-events: auto;
}
.mobile-comments-panel .panel-backdrop {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  opacity: 0;
  transition: opacity 0.3s ease;
}
.mobile-comments-panel.active .panel-backdrop {
  opacity: 1;
}
.mobile-comments-panel .panel-content {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  height: 70%;
  background: #18181b;
  border-top-left-radius: 20px;
  border-top-right-radius: 20px;
  display: flex;
  flex-direction: column;
  padding: 16px;
  transform: translateY(100%);
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  box-shadow: 0 -8px 30px rgba(0,0,0,0.5);
}
.mobile-comments-panel.active .panel-content {
  transform: translateY(0);
}
.panel-drag-handle {
  width: 40px;
  height: 4px;
  background: rgba(255,255,255,0.2);
  border-radius: 2px;
  margin: 0 auto 12px auto;
}
.mobile-comments-panel .panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
  border-bottom: 1px solid rgba(255,255,255,0.08);
  padding-bottom: 10px;
}
.mobile-comments-panel .panel-header h3 {
  margin: 0;
  font-size: 1.1rem;
}
.mobile-comments-panel .close-panel-btn {
  background: none;
  border: none;
  color: #fff;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0;
}
.comments-loading, .comments-empty {
  text-align: center;
  padding: 40px 10px;
  color: var(--text2);
  font-size: 0.85rem;
}

/* Dynamic search overlay styling */
.reels-search-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  background: rgba(10, 10, 12, 0.85);
  backdrop-filter: blur(15px);
  -webkit-backdrop-filter: blur(15px);
  z-index: 150;
  padding: 12px 16px;
  display: none;
  align-items: center;
  gap: 12px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  transform: translateY(-100%);
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.reels-search-overlay.active {
  display: flex;
  transform: translateY(0);
}
.search-input-wrapper {
  position: relative;
  flex: 1;
  display: flex;
  align-items: center;
}
.search-input-wrapper .search-icon {
  position: absolute;
  left: 14px;
  color: rgba(255, 255, 255, 0.5);
  pointer-events: none;
}
#reels-search-input {
  width: 100%;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.12);
  color: #fff;
  border-radius: 24px;
  padding: 10px 40px 10px 42px;
  font-size: 0.9rem;
  outline: none;
  transition: all 0.2s;
}
#reels-search-input:focus {
  border-color: var(--accent);
  background: rgba(255, 255, 255, 0.12);
  box-shadow: 0 0 10px rgba(99, 102, 241, 0.2);
}
.clear-search-btn {
  position: absolute;
  right: 14px;
  background: none;
  border: none;
  color: rgba(255, 255, 255, 0.6);
  font-size: 1.2rem;
  cursor: pointer;
  display: none;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  padding: 0;
}
.clear-search-btn:hover {
  color: #fff;
}
.close-search-btn {
  background: none;
  border: none;
  color: #fff;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  padding: 8px 4px;
  white-space: nowrap;
}
.close-search-btn:hover {
  opacity: 0.8;
}

/* No search results overlay block */
.no-search-results {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  width: 100%;
  text-align: center;
  color: var(--text2);
  position: absolute;
  top: 0;
  left: 0;
  z-index: 10;
  background: #0a0a0c;
}
.no-search-results svg {
  color: var(--accent);
  margin-bottom: 16px;
  opacity: 0.6;
}
</style>

<div class="reels-container">
  <?php if (!$reels): ?>
    <div class="no-reels">
      <svg width="64" height="64" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
      <h2>No Reels Found</h2>
      <p style="margin-bottom:20px">Be the first to upload a Reel!</p>
      <a href="<?= BASE_URL ?>/creator/upload_reel.php" class="btn btn-primary">Upload Reel</a>
    </div>
  <?php else: ?>
    <!-- Mobile top floating header removed -->

    <!-- Global Reels Search Overlay (Dynamic Search) -->
    <div class="reels-search-overlay" id="reels-search-overlay">
      <div class="search-input-wrapper">
        <svg class="search-icon" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" id="reels-search-input" placeholder="Search by title or creator name..." autocomplete="off">
        <button class="clear-search-btn" id="clear-search-btn" aria-label="Clear search">&times;</button>
      </div>
      <button class="close-search-btn" id="close-search-btn">Cancel</button>
    </div>

    <!-- No Search Results Block -->
    <div class="no-search-results" id="no-search-results" style="display: none;">
      <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><path stroke-linecap="round" d="M8 15h6"/></svg>
      <h3>No matching Reels found</h3>
      <p>Try searching for a different title or creator.</p>
    </div>

    <div class="reels-feed" id="reels-feed">
      <?php foreach ($reels as $index => $r):
        $thumb = thumb_url($r['thumbnail']);
        $video_src = video_url($r['video_url']);
        $title = e($r['title']);
        $creator = e($r['channel_name'] ?: $r['username']);
        $avatar = avatar_url($r['avatar']);
        $likes = (int)$r['likes'];
        $views = (int)$r['views'];
        $comments_count = (int)($r['comments_count'] ?? 0);
        $ch_id = (int)$r['user_id'];
        
        // Track ad impression if ad placement loads and has views
        $is_subbed = false;
        if (is_logged_in()) {
            $is_subbed = (bool)db_fetch("SELECT id FROM subscriptions WHERE subscriber_id=? AND channel_id=?", [auth_user()['id'], $ch_id]);
        }
        
        $is_liked = false;
        if (is_logged_in()) {
            $react = db_fetch("SELECT type FROM video_reactions WHERE video_id=? AND user_id=? AND type='like'", [$r['id'], auth_user()['id']]);
            if ($react) $is_liked = true;
        }
      ?>
      <div class="reel-slide" data-index="<?= $index ?>" data-id="<?= $r['id'] ?>" data-creator-id="<?= $ch_id ?>">
        <!-- Left ad wrapper (Desktop Only) -->
        <div class="reel-left-ad-wrapper">
          <div class="ad-sponsored-container ad-reels-left" data-placement="reels_left" data-lazy="true" style="display:none;"></div>
        </div>

        <!-- Middle Column containing Player, Panel, and Bottom Ad -->
        <div class="reel-middle-column">
          <div class="reel-player-and-panel-row">
            <!-- Video element container -->
            <div class="reel-player-container">
              <video class="reel-video" src="<?= $video_src ?>" loop playsinline webkit-playsinline preload="<?= ($index === 0) ? 'auto' : 'none' ?>" poster="<?= $thumb ?>"></video>
              
              <!-- Tap/Play/Volume overlays -->
              <div class="video-overlay-play-btn">
                <svg width="40" height="40" fill="currentColor" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
              </div>
              <div class="video-overlay-volume-btn">
                <svg width="40" height="40" fill="currentColor" viewBox="0 0 24 24"><path d="M11 5L6 9H2v6h4l5 4V5z"/></svg>
              </div>

              <!-- Mobile top overlay (Ad and Title) -->
              <div class="reel-mobile-top-overlay">
                <div class="ad-sponsored-container ad-reels-mobile-top" data-placement="reels_mobile_top" data-lazy="true" style="display:none;"></div>
                <div class="reel-mobile-top-title-wrapper" onclick="event.stopPropagation();">
                  <div class="reel-mobile-top-title"><?= $title ?></div>
                </div>
              </div>

              <!-- Mobile info overlay -->
              <div class="reel-mobile-info">
                <div class="reel-creator-row <?= $is_subbed ? 'is-subbed-state' : '' ?>">
                  <div class="creator-badge-container" onclick="event.stopPropagation();">
                    <a href="<?= BASE_URL ?>/channel.php?id=<?= $ch_id ?>&tab=reels" class="creator-avatar-link">
                      <img src="<?= $avatar ?>" alt="<?= $creator ?>" class="creator-avatar">
                    </a>
                    <a href="<?= BASE_URL ?>/channel.php?id=<?= $ch_id ?>&tab=reels" class="creator-name"><?= $creator ?></a>
                  </div>
                  <?php if (!is_logged_in() || auth_user()['id'] != $ch_id): ?>
                    <button class="btn btn-primary btn-sm sub-btn-reel <?= $is_subbed ? 'subbed' : '' ?>" data-channel="<?= $ch_id ?>" onclick="event.stopPropagation();">
                      <?= $is_subbed ? 'Subscribed ✓' : 'Subscribe' ?>
                    </button>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Mobile actions overlay -->
              <div class="reel-mobile-actions">
                <!-- Search Button -->
                <button class="action-btn search-trigger-btn" onclick="event.stopPropagation();" title="Search Reels">
                  <div class="icon-wrap">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                  </div>
                  <span class="count-label">Search</span>
                </button>

                <!-- Sound Toggle -->
                <button class="action-btn sound-toggle-btn" onclick="event.stopPropagation();" title="Toggle sound">
                  <div class="icon-wrap">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" class="sound-mute-icon" viewBox="0 0 24 24" style="display: none;"><path d="M11 5L6 9H2v6h4l5 4V5z"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/></svg>
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" class="sound-unmute-icon" viewBox="0 0 24 24" style="display: block;"><path d="M11 5L6 9H2v6h4l5 4V5z"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
                  </div>
                  <span class="count-label sound-label">Sound</span>
                </button>

                <!-- Like -->
                <button class="action-btn like-btn <?= $is_liked ? 'liked' : '' ?>" data-id="<?= $r['id'] ?>">
                  <div class="icon-wrap">
                    <svg width="24" height="24" fill="<?= $is_liked ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                  </div>
                  <span class="count-label"><?= format_number($likes) ?></span>
                </button>

                <!-- Comments -->
                <button class="action-btn comment-trigger-btn" data-id="<?= $r['id'] ?>">
                  <div class="icon-wrap">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                  </div>
                  <span class="count-label"><?= format_number($comments_count) ?></span>
                </button>

                <!-- Share -->
                <button class="action-btn share-trigger-btn" data-id="<?= $r['id'] ?>" data-url="<?= BASE_URL ?>/reels.php?v=<?= $r['id'] ?>">
                  <div class="icon-wrap">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8M16 6l-4-4-4 4M12 2v13"/></svg>
                  </div>
                  <span class="count-label">Share</span>
                </button>

                <!-- Views -->
                <div class="action-btn" style="cursor: default;">
                  <div class="icon-wrap">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </div>
                  <span class="count-label views-count" data-id="<?= $r['id'] ?>"><?= format_number($views) ?></span>
                </div>
              </div>
            </div>

            <!-- Desktop side details panel -->
            <div class="reel-desktop-panel">
              <div class="panel-header <?= $is_subbed ? 'is-subbed-state' : '' ?>">
                <div class="creator-badge-container" onclick="event.stopPropagation();">
                  <a href="<?= BASE_URL ?>/channel.php?id=<?= $ch_id ?>&tab=reels" class="creator-avatar-link">
                    <img src="<?= $avatar ?>" alt="<?= $creator ?>" class="creator-avatar">
                  </a>
                  <div class="creator-details">
                    <a href="<?= BASE_URL ?>/channel.php?id=<?= $ch_id ?>&tab=reels" class="creator-name"><?= $creator ?></a>
                    <span class="sub-count"><?= format_number((int)$r['subscribers']) ?> subscribers</span>
                  </div>
                </div>
                <?php if (!is_logged_in() || auth_user()['id'] != $ch_id): ?>
                  <button class="btn btn-primary btn-sm sub-btn-reel <?= $is_subbed ? 'subbed' : '' ?>" data-channel="<?= $ch_id ?>" onclick="event.stopPropagation();">
                    <?= $is_subbed ? 'Subscribed ✓' : 'Subscribe' ?>
                  </button>
                <?php endif; ?>
              </div>

              <div class="panel-body">
                <h2 class="reel-title"><?= $title ?></h2>
                <div class="reel-stats">
                  <span class="stat-item views-count" data-id="<?= $r['id'] ?>"><?= format_number($views) ?> views</span>
                  <span class="stat-item"><?= time_ago($r['published_at']) ?></span>
                </div>
                
                <div class="panel-actions">
                  <button class="btn btn-outline btn-sm like-btn <?= $is_liked ? 'liked' : '' ?>" data-id="<?= $r['id'] ?>">
                    <svg width="16" height="16" fill="<?= $is_liked ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                    <span class="like-label"><?= $is_liked ? 'Liked' : 'Like' ?></span> (<span class="like-count"><?= $likes ?></span>)
                  </button>
                  
                  <button class="btn btn-outline btn-sm share-trigger-btn" data-url="<?= BASE_URL ?>/reels.php?v=<?= $r['id'] ?>">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="margin-right:6px"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8M16 6l-4-4-4 4M12 2v13"/></svg> Share
                  </button>

                  <button class="btn btn-outline btn-sm sound-toggle-btn" title="Toggle sound" onclick="event.stopPropagation();">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" class="sound-mute-icon" viewBox="0 0 24 24" style="display: none; margin-right:6px;"><path d="M11 5L6 9H2v6h4l5 4V5z"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/></svg>
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" class="sound-unmute-icon" viewBox="0 0 24 24" style="display: inline-block; margin-right:6px;"><path d="M11 5L6 9H2v6h4l5 4V5z"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
                    <span class="sound-label">Sound</span>
                  </button>
                </div>
              </div>

              <div class="panel-comments-section">
                <h3>Comments (<span class="comments-count-label" data-id="<?= $r['id'] ?>"><?= format_number($comments_count) ?></span>)</h3>
                <div class="panel-comments-list" data-id="<?= $r['id'] ?>">
                  <div class="comments-loading">Loading comments...</div>
                </div>
                
                <form class="reel-comment-form" data-id="<?= $r['id'] ?>">
                  <input type="text" placeholder="Add a comment..." class="comment-input" required autocomplete="off">
                  <button type="submit" class="comment-submit-btn">Post</button>
                </form>
              </div>
            </div>
          </div>
          
          <!-- Reels Bottom Ad (Desktop Only) -->
          <div class="reel-bottom-ad-wrapper">
            <div class="ad-sponsored-container ad-reels-bottom" data-placement="reels_bottom" data-lazy="true" style="display:none;"></div>
          </div>
        </div>

        <!-- Right ad wrapper (Desktop Only) -->
        <div class="reel-right-ad-wrapper">
          <div class="ad-sponsored-container ad-reels-right" data-placement="reels_right" data-lazy="true" style="display:none;"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Desktop Navigation controls (Prev/Next buttons floating) -->
    <button class="reel-nav-arrow prev-arrow" id="prev-reel-btn" aria-label="Previous Reel">
      <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"/></svg>
    </button>
    <button class="reel-nav-arrow next-arrow" id="next-reel-btn" aria-label="Next Reel">
      <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
    </button>

    <!-- Mobile comments slide-up panel -->
    <div class="mobile-comments-panel" id="mobile-comments-panel">
      <div class="panel-backdrop"></div>
      <div class="panel-content">
        <div class="panel-drag-handle"></div>
        <div class="panel-header">
          <h3>Comments</h3>
          <button class="close-panel-btn" aria-label="Close Comments">&times;</button>
        </div>
        <div class="panel-comments-list-mobile">
          <!-- Loaded via JS -->
        </div>
        <form class="reel-comment-form-mobile" id="mobile-comment-form">
          <input type="text" placeholder="Add a comment..." class="comment-input" required autocomplete="off">
          <button type="submit" class="comment-submit-btn">Post</button>
        </form>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const feed = document.getElementById('reels-feed');
  const slides = document.querySelectorAll('.reel-slide');
  const prevBtn = document.getElementById('prev-reel-btn');
  const nextBtn = document.getElementById('next-reel-btn');
  const mobileCommentsPanel = document.getElementById('mobile-comments-panel');
  const closePanelBtn = mobileCommentsPanel ? mobileCommentsPanel.querySelector('.close-panel-btn') : null;
  const backdrop = mobileCommentsPanel ? mobileCommentsPanel.querySelector('.panel-backdrop') : null;

  if (!feed || slides.length === 0) return;

  const searchOverlay = document.getElementById('reels-search-overlay');
  const searchInput = document.getElementById('reels-search-input');
  const clearSearchBtn = document.getElementById('clear-search-btn');
  const closeSearchBtn = document.getElementById('close-search-btn');
  const noSearchResults = document.getElementById('no-search-results');

  let activeIndex = 0;
  let mutedGlobal = false; // default to normal mode (unmuted)

  // Reload all ads associated with a specific slide
  function reloadAdsForSlide(slide) {
    const ads = slide.querySelectorAll('.ad-sponsored-container');
    ads.forEach(container => {
      if (window.reloadAd) {
        window.reloadAd(container);
      } else if (window.loadLazyAd) {
        window.loadLazyAd(container);
      }
    });
  }

  // Play a video, mute/unmute based on global state
  function playVideo(index) {
    slides.forEach((slide, idx) => {
      const video = slide.querySelector('.reel-video');
      if (!video) return;

      if (idx === index) {
        // Active video
        video.muted = mutedGlobal;
        
        // Show volume overlay indicator if muted
        const volBtn = slide.querySelector('.video-overlay-volume-btn');
        if (mutedGlobal && volBtn) {
          volBtn.innerHTML = '<svg width="40" height="40" fill="currentColor" viewBox="0 0 24 24"><path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77zM3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02z"/></svg>';
          volBtn.classList.add('show');
          setTimeout(() => volBtn.classList.remove('show'), 1500);
        }

        // Set source if not set (lazy loading)
        video.preload = "auto";
        video.play().then(() => {
          // Increment view via watches if needed
          fetch(`<?= BASE_URL ?>/watch.php?v=${slide.dataset.id}&xhr_view=1`)
            .then(res => res.json())
            .then(data => {
              if (data && data.success) {
                // Update all view counters on the page for this reel in real-time
                document.querySelectorAll(`.views-count[data-id="${slide.dataset.id}"]`).forEach(el => {
                  if (el.classList.contains('stat-item')) {
                    el.textContent = `${data.formatted} views`;
                  } else {
                    el.textContent = data.formatted;
                  }
                });
              }
            }).catch(()=>{});
        }).catch(err => {
          console.log("Autoplay blocked or failed:", err);
          // Fallback to muted playback if autoplay with sound is blocked
          if (!mutedGlobal) {
            video.muted = true;
            mutedGlobal = true;
            syncSoundButtonsState();
            video.play().then(() => {
              // Increment view for muted playback too
              fetch(`<?= BASE_URL ?>/watch.php?v=${slide.dataset.id}&xhr_view=1`)
                .then(res => res.json())
                .then(data => {
                  if (data && data.success) {
                    document.querySelectorAll(`.views-count[data-id="${slide.dataset.id}"]`).forEach(el => {
                      if (el.classList.contains('stat-item')) {
                        el.textContent = `${data.formatted} views`;
                      } else {
                        el.textContent = data.formatted;
                      }
                    });
                  }
                }).catch(()=>{});
            }).catch(muteErr => {
              console.log("Muted autoplay also blocked:", muteErr);
            });
          }
        });
        
        // Load comments for this active reel
        loadComments(slide.dataset.id);

        // Reload ad placements for this active reel slide
        reloadAdsForSlide(slide);

        // Adjust marquee scroll for title
        adjustTitleScroll(slide);
      } else {
        // Pause other videos
        video.pause();
        // Preload adjacent slides to enable instant loading when swiping/scrolling
        if (Math.abs(idx - index) === 1) {
          if (video.preload !== "auto") {
            video.preload = "auto";
            video.load(); // Force the browser to start downloading/buffering the video stream
          }
        } else {
          video.preload = "none";
        }
      }
    });
  }

  function adjustTitleScroll(slide) {
    const wrapper = slide.querySelector('.reel-mobile-top-title-wrapper');
    const title = slide.querySelector('.reel-mobile-top-title');
    if (!wrapper || !title || wrapper.clientWidth === 0) return;

    title.classList.remove('scroll-active');
    title.style.removeProperty('--scroll-dist');
    title.style.removeProperty('animation-duration');

    const overflow = title.scrollWidth - wrapper.clientWidth;
    if (overflow > 0) {
      title.style.setProperty('--scroll-dist', `-${overflow + 16}px`);
      const duration = Math.max(3, (overflow / 30) + 2);
      title.style.animationDuration = `${duration}s`;
      title.classList.add('scroll-active');
    }
  }

  window.addEventListener('resize', () => {
    const activeSlide = slides[activeIndex];
    if (activeSlide) {
      adjustTitleScroll(activeSlide);
    }
  });

  // Set up intersection observer to detect current visible reel slide
  const observerOptions = {
    root: feed,
    rootMargin: '0px',
    threshold: 0.6 // Slide must be 60% visible to count as active
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const index = parseInt(entry.target.dataset.index);
        activeIndex = index;
        playVideo(activeIndex);
        updateNavArrows();
      }
    });
  }, observerOptions);

  slides.forEach(slide => observer.observe(slide));

  // Desktop navigation arrows click handlers
  function updateNavArrows() {
    let hasPrev = false;
    let hasNext = false;
    for (let i = activeIndex - 1; i >= 0; i--) {
      if (!slides[i].classList.contains('filtered-out')) {
        hasPrev = true;
        break;
      }
    }
    for (let i = activeIndex + 1; i < slides.length; i++) {
      if (!slides[i].classList.contains('filtered-out')) {
        hasNext = true;
        break;
      }
    }
    if (prevBtn) prevBtn.style.opacity = hasPrev ? '1' : '0.3';
    if (nextBtn) nextBtn.style.opacity = hasNext ? '1' : '0.3';
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      let prevIndex = -1;
      for (let i = activeIndex - 1; i >= 0; i--) {
        if (!slides[i].classList.contains('filtered-out')) {
          prevIndex = i;
          break;
        }
      }
      if (prevIndex !== -1) {
        activeIndex = prevIndex;
        scrollToReel(activeIndex);
      }
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      let nextIndex = -1;
      for (let i = activeIndex + 1; i < slides.length; i++) {
        if (!slides[i].classList.contains('filtered-out')) {
          nextIndex = i;
          break;
        }
      }
      if (nextIndex !== -1) {
        activeIndex = nextIndex;
        scrollToReel(activeIndex);
      }
    });
  }

  function scrollToReel(index) {
    const slide = slides[index];
    if (!slide) return;
    
    // In desktop view, slides are stacked vertically inside the scroll container
    // We scroll the feed container to the top position of the target slide
    const containerTop = feed.getBoundingClientRect().top;
    const slideTop = slide.getBoundingClientRect().top;
    const offset = slideTop - containerTop + feed.scrollTop;
    
    feed.scrollTo({
      top: offset,
      behavior: 'smooth'
    });
  }

  // Keyboard navigation
  document.addEventListener('keydown', (e) => {
    // Skip if user is typing in form elements to avoid conflicts
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
      return;
    }
    if (e.key === 'ArrowUp') {
      e.preventDefault();
      let prevIndex = -1;
      for (let i = activeIndex - 1; i >= 0; i--) {
        if (!slides[i].classList.contains('filtered-out')) {
          prevIndex = i;
          break;
        }
      }
      if (prevIndex !== -1) {
        activeIndex = prevIndex;
        scrollToReel(activeIndex);
      }
    } else if (e.key === 'ArrowDown') {
      e.preventDefault();
      let nextIndex = -1;
      for (let i = activeIndex + 1; i < slides.length; i++) {
        if (!slides[i].classList.contains('filtered-out')) {
          nextIndex = i;
          break;
        }
      }
      if (nextIndex !== -1) {
        activeIndex = nextIndex;
        scrollToReel(activeIndex);
      }
    } else if (e.key === ' ') {
      e.preventDefault();
      const currentVideo = slides[activeIndex].querySelector('.reel-video');
      if (currentVideo) {
        togglePlayPause(currentVideo);
      }
    }
  });

  // Tap video to toggle play/pause or unmute
  slides.forEach(slide => {
    const video = slide.querySelector('.reel-video');
    if (!video) return;

    video.addEventListener('click', () => {
      if (video.muted) {
        // First click/tap unmutes globally
        mutedGlobal = false;
        slides.forEach(s => {
          const v = s.querySelector('.reel-video');
          if (v) v.muted = false;
        });
        syncSoundButtonsState();
        
        // Show volume feedback icon
        const volBtn = slide.querySelector('.video-overlay-volume-btn');
        if (volBtn) {
          volBtn.innerHTML = '<svg width="40" height="40" fill="currentColor" viewBox="0 0 24 24"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/></svg>';
          volBtn.classList.add('show');
          setTimeout(() => volBtn.classList.remove('show'), 1000);
        }
      } else {
        // Subsequent click toggles play/pause
        togglePlayPause(video);
      }
    });
  });

  // Sound Buttons Sync function
  function syncSoundButtonsState() {
    slides.forEach(slide => {
      const sBtns = slide.querySelectorAll('.sound-toggle-btn');
      sBtns.forEach(sBtn => {
        const muteIcon = sBtn.querySelector('.sound-mute-icon');
        const unmuteIcon = sBtn.querySelector('.sound-unmute-icon');
        const label = sBtn.querySelector('.sound-label');
        
        if (mutedGlobal) {
          if (muteIcon) muteIcon.style.display = 'inline-block';
          if (unmuteIcon) unmuteIcon.style.display = 'none';
          if (label) label.textContent = 'Muted';
        } else {
          if (muteIcon) muteIcon.style.display = 'none';
          if (unmuteIcon) unmuteIcon.style.display = 'inline-block';
          if (label) label.textContent = 'Sound';
        }
      });
    });
  }

  // Sound Toggle Click Handler
  document.querySelectorAll('.sound-toggle-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      mutedGlobal = !mutedGlobal;
      
      // Update all video mute states
      slides.forEach(slide => {
        const video = slide.querySelector('.reel-video');
        if (video) video.muted = mutedGlobal;
      });
      
      syncSoundButtonsState();
    });
  });

  function togglePlayPause(video) {
    const slide = video.closest('.reel-slide');
    const playBtn = slide.querySelector('.video-overlay-play-btn');
    
    if (video.paused) {
      video.play();
      if (playBtn) {
        playBtn.innerHTML = '<svg width="40" height="40" fill="currentColor" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>'; // play symbol fading out
        playBtn.classList.add('show');
        setTimeout(() => playBtn.classList.remove('show'), 800);
      }
    } else {
      video.pause();
      if (playBtn) {
        playBtn.innerHTML = '<svg width="40" height="40" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>'; // pause symbol
        playBtn.classList.add('show');
      }
    }
  }

  // Like interaction handler
  document.querySelectorAll('.like-btn').forEach(btn => {
    btn.addEventListener('click', async function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const videoId = this.dataset.id;
      const res = await fetch('<?= BASE_URL ?>/api/videos.php?action=react', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({video_id: videoId, type: 'like'})
      });
      const data = await res.json();
      
      if (data.success && data.data) {
        const reactionData = data.data;
        // Update both mobile and desktop like buttons if present
        document.querySelectorAll(`.like-btn[data-id="${videoId}"]`).forEach(lBtn => {
          const svg = lBtn.querySelector('svg');
          if (reactionData.reaction === 'like') {
            lBtn.classList.add('liked');
            if (svg) svg.setAttribute('fill', 'currentColor');
            const label = lBtn.querySelector('.like-label');
            if (label) label.textContent = 'Liked';
          } else {
            lBtn.classList.remove('liked');
            if (svg) svg.setAttribute('fill', 'none');
            const label = lBtn.querySelector('.like-label');
            if (label) label.textContent = 'Like';
          }
          const count = lBtn.querySelector('.like-count') || lBtn.querySelector('.count-label');
          if (count) count.textContent = reactionData.likes;
        });
      } else {
        if (confirm("Please login to like this reel. Login now?")) {
          window.location.href = '<?= BASE_URL ?>/auth/login.php';
        }
      }
    });
  });

  // Subscribe interaction handler
  document.querySelectorAll('.sub-btn-reel').forEach(btn => {
    btn.addEventListener('click', async function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const channelId = this.dataset.channel;
      const res = await fetch('<?= BASE_URL ?>/api/videos.php?action=subscribe', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({channel_id: channelId})
      });
      const data = await res.json();
      
      if (data.success && data.data) {
        const subData = data.data;
        document.querySelectorAll(`.sub-btn-reel[data-channel="${channelId}"]`).forEach(sBtn => {
          const row = sBtn.closest('.reel-creator-row');
          const header = sBtn.closest('.panel-header');
          if (subData.subscribed) {
            sBtn.classList.add('subbed');
            sBtn.textContent = 'Subscribed ✓';
            if (row) row.classList.add('is-subbed-state');
            if (header) header.classList.add('is-subbed-state');
          } else {
            sBtn.classList.remove('subbed');
            sBtn.textContent = 'Subscribe';
            if (row) row.classList.remove('is-subbed-state');
            if (header) header.classList.remove('is-subbed-state');
          }
        });
      } else {
        if (confirm("Please login to subscribe. Login now?")) {
          window.location.href = '<?= BASE_URL ?>/auth/login.php';
        }
      }
    });
  });

  // Comments loading and posting
  async function loadComments(videoId) {
    const deskList = document.querySelector(`.panel-comments-list[data-id="${videoId}"]`);
    const mobList = document.querySelector('.panel-comments-list-mobile');

    const renderCommentsHtml = (comments) => {
      if (comments.length === 0) {
        return '<div class="comments-empty">No comments yet. Start the conversation!</div>';
      }
      return comments.map(c => `
        <div class="comment-item">
          <img src="${c.avatar}" alt="${c.username}" class="comment-avatar">
          <div style="min-width:0; flex:1;">
            <div><span class="comment-user">${c.username}</span><span class="comment-text">${c.content}</span></div>
            <div class="comment-time">${c.ago}</div>
          </div>
        </div>
      `).join('');
    };

    try {
      const res = await fetch(`<?= BASE_URL ?>/api/videos.php?action=comments&video_id=${videoId}`);
      const data = await res.json();
      if (data.success && data.data) {
        const html = renderCommentsHtml(data.data);
        if (deskList) deskList.innerHTML = html;
        if (mobileCommentsPanel && mobileCommentsPanel.dataset.id === videoId && mobList) {
          mobList.innerHTML = html;
        }
      }
    } catch(err) {
      console.error(err);
    }
  }

  // Handle desktop comment forms
  document.querySelectorAll('.reel-comment-form').forEach(form => {
    form.addEventListener('submit', async function(e) {
      e.preventDefault();
      const videoId = this.dataset.id;
      const input = this.querySelector('.comment-input');
      const content = input.value.trim();
      if (!content) return;

      const res = await fetch('<?= BASE_URL ?>/api/videos.php?action=comment', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({video_id: videoId, content: content})
      });
      const data = await res.json();

      if (data.success) {
        input.value = '';
        loadComments(videoId);
        document.querySelectorAll(`.comment-trigger-btn[data-id="${videoId}"] .count-label`).forEach(lbl => {
          let count = parseInt(lbl.textContent) || 0;
          lbl.textContent = count + 1;
        });
        document.querySelectorAll(`.comments-count-label[data-id="${videoId}"]`).forEach(lbl => {
          let count = parseInt(lbl.textContent) || 0;
          lbl.textContent = count + 1;
        });
      } else {
        if (confirm("Please login to comment. Login now?")) {
          window.location.href = '<?= BASE_URL ?>/auth/login.php';
        }
      }
    });
  });

  // Mobile comments panel triggers
  document.querySelectorAll('.comment-trigger-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      const videoId = this.dataset.id;
      
      if (mobileCommentsPanel) {
        mobileCommentsPanel.dataset.id = videoId;
        const form = document.getElementById('mobile-comment-form');
        if (form) form.dataset.id = videoId;
        
        mobileCommentsPanel.classList.add('active');
        
        const mobList = document.querySelector('.panel-comments-list-mobile');
        if (mobList) mobList.innerHTML = '<div class="comments-loading">Loading comments...</div>';
        
        loadComments(videoId);
      }
    });
  });

  // Mobile comments form submit
  const mobForm = document.getElementById('mobile-comment-form');
  if (mobForm) {
    mobForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      const videoId = this.dataset.id;
      const input = this.querySelector('.comment-input');
      const content = input.value.trim();
      if (!content) return;

      const res = await fetch('<?= BASE_URL ?>/api/videos.php?action=comment', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({video_id: videoId, content: content})
      });
      const data = await res.json();

      if (data.success) {
        input.value = '';
        loadComments(videoId);
        document.querySelectorAll(`.comment-trigger-btn[data-id="${videoId}"] .count-label`).forEach(lbl => {
          let count = parseInt(lbl.textContent) || 0;
          lbl.textContent = count + 1;
        });
        document.querySelectorAll(`.comments-count-label[data-id="${videoId}"]`).forEach(lbl => {
          let count = parseInt(lbl.textContent) || 0;
          lbl.textContent = count + 1;
        });
      } else {
        if (confirm("Please login to comment. Login now?")) {
          window.location.href = '<?= BASE_URL ?>/auth/login.php';
        }
      }
    });
  }

  // Close mobile comments panel
  if (closePanelBtn) {
    closePanelBtn.addEventListener('click', () => {
      mobileCommentsPanel.classList.remove('active');
    });
  }
  if (backdrop) {
    backdrop.addEventListener('click', () => {
      mobileCommentsPanel.classList.remove('active');
    });
  }

  // Share button links
  document.querySelectorAll('.share-trigger-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      const url = this.dataset.url;
      navigator.clipboard.writeText(url).then(() => {
        alert('Reel link copied to clipboard!');
      }).catch(err => {
        console.error('Could not copy text: ', err);
      });
    });
  });

  // Dynamic Search Functionality
  function filterReels() {
    const query = searchInput.value.toLowerCase().trim();
    if (clearSearchBtn) clearSearchBtn.style.display = query ? 'flex' : 'none';

    let firstVisibleIndex = -1;
    let visibleSlidesCount = 0;

    slides.forEach((slide, idx) => {
      const titleEl = slide.querySelector('.reel-mobile-top-title');
      const creatorEl = slide.querySelector('.creator-name');
      const titleText = titleEl ? titleEl.textContent.toLowerCase() : '';
      const creatorText = creatorEl ? creatorEl.textContent.toLowerCase() : '';

      const isMatch = titleText.includes(query) || creatorText.includes(query);

      if (isMatch) {
        slide.style.display = '';
        slide.classList.remove('filtered-out');
        visibleSlidesCount++;
        if (firstVisibleIndex === -1) {
          firstVisibleIndex = idx;
        }
      } else {
        slide.style.display = 'none';
        slide.classList.add('filtered-out');
        const video = slide.querySelector('.reel-video');
        if (video) video.pause();
      }
    });

    if (noSearchResults) {
      noSearchResults.style.display = (visibleSlidesCount === 0 && query !== '') ? 'flex' : 'none';
    }

    if (firstVisibleIndex !== -1 && slides[activeIndex].classList.contains('filtered-out')) {
      activeIndex = firstVisibleIndex;
      scrollToReel(activeIndex);
      playVideo(activeIndex);
    }
    updateNavArrows();
  }

  // Search event listeners
  const searchTriggers = document.querySelectorAll('.search-trigger-btn');
  searchTriggers.forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      if (searchOverlay) {
        searchOverlay.classList.add('active');
        searchInput.focus();
      }
    });
  });

  if (closeSearchBtn) {
    closeSearchBtn.addEventListener('click', () => {
      if (searchOverlay) searchOverlay.classList.remove('active');
      searchInput.value = '';
      filterReels();
    });
  }

  if (clearSearchBtn) {
    clearSearchBtn.addEventListener('click', () => {
      searchInput.value = '';
      searchInput.focus();
      filterReels();
    });
  }

  if (searchInput) {
    searchInput.addEventListener('input', filterReels);
  }

  // Initialize first video play
  updateNavArrows();
  syncSoundButtonsState();
  setTimeout(() => {
    playVideo(activeIndex);
  }, 100);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
