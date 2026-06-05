<?php
// ============================================================
// FreeHub.Live — Parallel Background Video Upload System (SPA Studio)
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin', 'creator']);

$site_theme = setting('active_theme', 'dark-minimal');
$primary    = setting('primary_color', '#6366f1');

$uid     = auth_user()['id'];
$categories = db_fetchAll("SELECT * FROM categories WHERE is_active=1 ORDER BY sort_order");
$user_playlists = db_fetchAll("SELECT id, title FROM playlists WHERE user_id = ? ORDER BY title ASC", [$uid]);

$mode = ($_GET['mode'] ?? '') === 'reel' ? 'reel' : 'video';
$page_title = $mode === 'reel' ? 'Upload Reel' : 'Upload Video';
$meta_title = $page_title . ' — Studio';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container upload-studio-container" style="max-width:1300px; margin:0 auto; padding: 24px 16px 80px;">
  
  <!-- Page Header -->
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
    <div>
      <h1 style="font-size:1.8rem; font-weight:800; background: linear-gradient(135deg, #fff 0%, #a5b4fc 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin:0;"><?= htmlspecialchars($page_title) ?></h1>
      <?php if ($mode === 'reel'): ?>
        <p style="color:var(--text2); font-size:0.9rem; margin-top:4px;">Upload and publish short vertical Reels (under 60s) in the background.</p>
      <?php else: ?>
        <p style="color:var(--text2); font-size:0.9rem; margin-top:4px;">Upload and publish landscape videos in the background.</p>
      <?php endif; ?>
    </div>
    
    <!-- Small Top Dropzone (always visible once uploads start) -->
    <div id="top-dropzone" style="display:none;">
      <div class="top-upload-bar" onclick="document.getElementById('video-file-input').click()">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="color:var(--accent)"><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/></svg>
        <span>Upload More Videos</span>
      </div>
    </div>
  </div>

  <!-- ── WELCOME VIEW: Large Init Dropzone ── -->
  <div id="welcome-dropzone" class="fade-in">
    <!-- Tab selector -->
    <div class="upload-tab-header" style="margin-bottom:24px; <?= $mode === 'reel' ? 'display:none;' : 'display:flex;' ?> gap:12px;">
      <button type="button" class="upload-tab-btn active" id="tab-file-btn" onclick="switchSourceType('file')">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        Upload Video Files
      </button>
      <button type="button" class="upload-tab-btn" id="tab-embed-btn" onclick="switchSourceType('embed')">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
        Import Links (YouTube / MP4)
      </button>
    </div>

    <!-- Dropzone Block -->
    <div id="file-drop-container">
      <div class="upload-dropzone" id="drop-zone" onclick="document.getElementById('video-file-input').click()">
        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" style="width:64px; height:64px; color:var(--text3); margin-bottom:20px;"><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/></svg>
        <h2 style="font-size:1.4rem; font-weight:700; margin-bottom:8px;">Drag and drop video files to upload</h2>
        <p style="color:var(--text2); font-size:0.9rem; margin-bottom:20px;">Upload multiple files in parallel. Max chunk size 5MB. Resumable.</p>
        <button class="btn btn-primary" style="font-weight:700; padding:10px 24px;">Select Files</button>
      </div>
    </div>

    <!-- Embed input block -->
    <div id="embed-url-container" style="display:none">
      <div class="upload-dropzone" style="padding:50px 24px; cursor:default;">
        <div style="max-width:580px; margin:0 auto">
          <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color:var(--text3); margin-bottom:16px"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
          <h2 style="font-size:1.3rem; font-weight:700; margin-bottom:8px">Import Video from External Source</h2>
          <p style="color:var(--text2); font-size:0.88rem; margin-bottom:24px">Paste a YouTube video URL or a direct link ending in .mp4 / .webm</p>
          <div style="display:flex; gap:10px">
            <input class="form-input" type="url" id="embed-link-field" placeholder="https://www.youtube.com/watch?v=..." style="border-radius:8px">
            <button type="button" class="btn btn-primary" onclick="verifyAndLoadEmbed()" style="border-radius:8px; font-weight:700; white-space:nowrap">Import Video</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Input file element supporting multiple selections -->
  <input type="file" id="video-file-input" accept="video/mp4,video/webm,video/quicktime" multiple style="display:none">

  <!-- ── DASHBOARD VIEW: Dual Column Studio ── -->
  <div id="studio-dashboard" style="display:none;" class="fade-in">
    <div class="upload-dashboard-grid">
      
      <!-- Left Column: Upload List Queue -->
      <div class="queue-panel">
        <h3 style="font-size:1.05rem; font-weight:800; color:#fff; margin-bottom:16px; display:flex; align-items:center; gap:8px;">
          <span>📦 Upload Queue</span>
          <span id="queue-count-badge" class="badge badge-gray" style="font-size:0.75rem;">0</span>
        </h3>
        
        <!-- List container -->
        <div id="uploads-queue" style="display:flex; flex-direction:column; gap:12px; max-height:680px; overflow-y:auto; padding-right:4px;">
          <!-- Upload item templates injected here -->
        </div>
      </div>
      
      <!-- Right Column: Metadata Form Details Editor -->
      <div class="editor-panel">
        <!-- Placeholder when no upload is selected -->
        <div id="editor-placeholder" style="text-align:center; padding:120px 24px; color:var(--text2); border: 1px dashed var(--border); border-radius:16px; background:var(--bg2);">
          <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 16px; opacity:.4"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="3" x2="9" y2="21"/></svg>
          <h4 style="font-size:1.05rem; color:#fff; margin-bottom:6px;">Select a Video to Edit Details</h4>
          <p style="font-size:0.85rem;">Click any uploading or completed video on the left queue list to configure its title, description, and categories.</p>
        </div>

        <!-- The Details Editor Form (hidden when nothing selected) -->
        <div id="details-editor-form" style="display:none;" class="fade-in">
          <form id="metadata-editor-form" onsubmit="saveActiveMetadata(event)">
            <div class="studio-form-layout">
              
              <!-- Form inputs -->
              <div class="studio-form-fields card" style="padding:24px; background:var(--bg2); border:1px solid var(--border); border-radius:16px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid var(--border); padding-bottom:12px;">
                  <h3 style="font-size:1.1rem; font-weight:800; color:#fff; margin:0;" id="editor-selected-title">Edit Video Metadata</h3>
                  <span id="editor-selected-status" class="badge" style="font-size:0.7rem; font-weight:700;">Uploading</span>
                </div>

                <div class="form-group">
                  <label class="form-label">Title *</label>
                  <input class="form-input" type="text" id="details-title" name="title" required placeholder="Add a descriptive title" style="border-radius:8px">
                </div>

                <div class="form-group">
                  <label class="form-label">Description</label>
                  <textarea class="form-input" id="details-desc" name="description" rows="4" placeholder="Tell viewers what your video is about…" style="resize:vertical; border-radius:8px"></textarea>
                </div>

                <div class="form-group">
                  <label class="form-label">Tags <span class="text-muted">(comma separated)</span></label>
                  <input class="form-input" type="text" id="details-tags" name="tags" placeholder="gaming, music, vlog" style="border-radius:8px">
                </div>

                <!-- Thumbnail Selection Grid -->
                <div class="thumbnail-box" id="thumbnail-selector-panel" style="background:var(--bg3); border:1px solid var(--border); border-radius:12px; padding:20px; margin-bottom:24px;">
                  <div style="font-weight:700; font-size:0.92rem; margin-bottom:4px; color:#fff;">Thumbnail Selector</div>
                  <div style="font-size:0.8rem; color:var(--text2); margin-bottom:16px; line-height:1.4;">Select a frame from the video or upload a custom image.</div>
                  
                  <div class="thumb-grid" id="details-thumb-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(110px, 1fr)); gap:12px;">
                    <!-- Custom upload trigger -->
                    <div class="custom-thumb-trigger" onclick="document.getElementById('spa-custom-thumb').click()" style="aspect-ratio:16/9; background:var(--bg2); border:2.2px dashed var(--border); border-radius:8px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:6px; cursor:pointer; font-size:0.7rem; color:var(--text2); font-weight:600; text-align:center; transition:all 0.2s;">
                      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                      <span>Custom Image</span>
                    </div>
                    <input type="file" id="spa-custom-thumb" accept="image/jpeg,image/png,image/webp" style="display:none">
                    
                    <!-- Dynamically extracted frames render here -->
                  </div>
                </div>

                <!-- Categories and Playlist lists -->
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:24px;">
                  <div class="form-group">
                    <label class="form-label">Categories</label>
                    <div style="display:flex; flex-direction:column; gap:8px; background:var(--bg3); padding:12px; border-radius:8px; border:1px solid var(--border); max-height:140px; overflow-y:auto" id="details-categories-box">
                      <?php foreach ($categories as $c): ?>
                      <label class="flex gap-2" style="font-size:.8rem; cursor:pointer; user-select:none; align-items:center">
                        <input type="checkbox" name="category_ids[]" value="<?= $c['id'] ?>" class="category-checkbox">
                        <span><?= e($c['name']) ?></span>
                      </label>
                      <?php endforeach; ?>
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="form-label">Playlists</label>
                    <div style="display:flex; flex-direction:column; gap:8px; background:var(--bg3); padding:12px; border-radius:8px; border:1px solid var(--border); max-height:140px; overflow-y:auto" id="details-playlists-box">
                      <?php if (!empty($user_playlists)): ?>
                        <?php foreach ($user_playlists as $pl): ?>
                        <label class="flex gap-2" style="font-size:.8rem; cursor:pointer; user-select:none; align-items:center">
                          <input type="checkbox" name="playlist_ids[]" value="<?= $pl['id'] ?>" class="playlist-checkbox">
                          <span><?= e($pl['title']) ?></span>
                        </label>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <span class="text-muted text-xs" style="padding:4px">No playlists created.</span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label">Visibility</label>
                  <select class="form-input form-select" id="details-visibility" name="visibility" style="border-radius: 8px">
                    <option value="public" selected>Public (Instantly published)</option>
                    <option value="unlisted">Unlisted (Accessible via link)</option>
                    <option value="private">Private (Only visible to you)</option>
                  </select>
                </div>

                <div class="form-group" id="reels-checkbox-group" style="display:none; margin-top:16px;">
                  <label style="display:flex; align-items:center; gap:8px; cursor:pointer; user-select:none;">
                    <input type="checkbox" id="details-is-reel" name="is_reel" value="1">
                    <span style="font-weight:600; font-size:0.9rem;">📱 Publish as Reel (Short Vertical Video)</span>
                  </label>
                  <small class="text-muted text-xs" style="display:block; margin-top:4px;">Only videos 60 seconds or less can be published as Reels.</small>
                </div>

                <div style="display:flex; gap:12px; margin-top:28px;">
                  <button type="submit" class="btn btn-primary" id="save-metadata-btn" style="flex:1; justify-content:center; padding:12px; font-weight:800; border-radius:8px;">
                    💾 Save Video Details
                  </button>
                </div>
              </div>
              
              <!-- Sticky Preview Panel -->
              <div class="studio-preview-sticky">
                <div class="preview-card" style="background:var(--bg2); border:1px solid var(--border); border-radius:16px; overflow:hidden; box-shadow:0 8px 30px rgba(0,0,0,0.18);">
                  <div class="preview-aspect-ratio" style="position:relative; aspect-ratio:16/9; background:#000; display:flex; align-items:center; justify-content:center; color:var(--text3);">
                    <video id="spa-player" style="width:100%; height:100%; object-fit:contain" controls></video>
                    <iframe id="spa-iframe-player" style="display:none; width:100%; height:100%; border:none" allowfullscreen></iframe>
                  </div>
                  <div class="preview-details" style="padding:16px;">
                    <div class="preview-title" id="details-preview-title" style="font-size:0.95rem; font-weight:700; color:#fff; word-break:break-word;">Video Title Preview</div>
                    <div style="font-size:0.8rem; color:var(--text2); display:flex; align-items:center; gap:8px;">
                      <span><?= e(auth_user()['channel_name'] ?? auth_user()['username']) ?></span>
                      <span>·</span>
                      <span id="details-preview-duration">0:00</span>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </form>
        </div>

      </div>

    </div>
  </div>

</div>

<!-- Hidden Canvas for client-side frame extraction -->
<canvas id="offscreen-canvas" style="display:none"></canvas>

<!-- Toast notifications layout container -->
<div id="upload-toasts-container"></div>

<style>
/* ── Redesigned Upload Studio Styling ── */
.top-upload-bar {
  background: var(--bg2);
  border: 1px dashed var(--accent);
  padding: 8px 16px;
  border-radius: 99px;
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--accent);
  transition: all 0.2s;
}
.top-upload-bar:hover {
  background: rgba(99,102,241,0.06);
  transform: translateY(-1px);
}
.upload-tab-header {
  background: var(--bg2);
  border: 1px solid var(--border);
  padding: 6px;
  border-radius: 12px;
  display: inline-flex;
}
.upload-tab-btn {
  background: transparent;
  border: none;
  padding: 10px 18px;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--text2);
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s;
}
.upload-tab-btn.active {
  background: var(--bg3);
  color: #fff;
}
.upload-dropzone {
  background: var(--bg2);
  border: 2px dashed var(--border);
  border-radius: 20px;
  padding: 80px 24px;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s;
}
.upload-dropzone:hover {
  border-color: var(--accent);
  background: rgba(99,102,241,0.02);
}
.upload-dashboard-grid {
  display: grid;
  grid-template-columns: 360px 1fr;
  gap: 28px;
  align-items: start;
}
@media (max-width: 992px) {
  .upload-dashboard-grid {
    grid-template-columns: 1fr;
  }
}
.queue-panel {
  background: var(--bg2);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 20px;
}
.upload-item-card {
  background: var(--bg3);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 14px;
  cursor: pointer;
  position: relative;
  transition: all 0.2s;
  user-select: none;
}
.upload-item-card:hover {
  border-color: var(--text3);
}
.upload-item-card.selected {
  border-color: var(--accent);
  box-shadow: 0 0 12px rgba(99, 102, 241, 0.2);
  background: rgba(99, 102, 241, 0.02);
}
.progress-mini-bar {
  background: var(--border);
  height: 4px;
  border-radius: 2px;
  overflow: hidden;
  margin-top: 8px;
}
.progress-mini-fill {
  background: var(--accent);
  height: 100%;
  width: 0%;
  transition: width 0.3s;
}
.progress-mini-fill.processing {
  background: var(--yellow);
}
.progress-mini-fill.published {
  background: var(--green);
}
.progress-mini-fill.failed {
  background: var(--red);
}
.studio-form-layout {
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 24px;
  align-items: start;
}
@media (max-width: 1200px) {
  .studio-form-layout {
    grid-template-columns: 1fr;
  }
}
.studio-preview-sticky {
  position: sticky;
  top: 90px;
}
#upload-toasts-container {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 10000;
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-width: 380px;
  width: 100%;
}
.toast-card {
  background: rgba(15, 23, 42, 0.95);
  border: 1.5px solid var(--border);
  color: #fff;
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.5);
  display: flex;
  gap: 12px;
  align-items: start;
  transform: translateY(20px);
  opacity: 0;
  animation: slideInToast 0.35s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
  transition: all 0.3s;
}
@keyframes slideInToast {
  to { transform: translateY(0); opacity: 1; }
}
.custom-thumb-trigger:hover {
  border-color: var(--accent) !important;
  color: #fff !important;
  transform: scale(1.02);
}
.thumb-option {
  position: relative;
  aspect-ratio: 16/9;
  border-radius: 8px;
  overflow: hidden;
  border: 2px solid transparent;
  cursor: pointer;
  background: #000;
  transition: all 0.2s;
}
.thumb-option:hover {
  transform: scale(1.02);
  border-color: var(--text3);
}
.thumb-option.selected {
  border-color: var(--accent);
  box-shadow: 0 0 12px rgba(99,102,241,0.3);
}
.thumb-option img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.thumb-option .check-badge {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 16px;
  height: 16px;
  background: var(--accent);
  border-radius: 50%;
  display: none;
  align-items: center;
  justify-content: center;
}
.thumb-option.selected .check-badge {
  display: flex;
}
.cancel-upload-btn {
  background: none;
  border: none;
  color: var(--text3);
  cursor: pointer;
  padding: 4px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}
.cancel-upload-btn:hover {
  background: rgba(239, 68, 68, 0.15);
  color: var(--red);
}
</style>

<script>
(function() {
  const uploads = {}; // Map of uploadId -> uploadObject
  let selectedUploadId = null;
  let sourceType = 'file'; // file or embed
  const MAX_CONCURRENT_UPLOADS = 1;

  // ── IndexedDB Helper Class for persistent queues ──
  class UploadDB {
    static dbName = 'FreeHubUploadManager';
    static storeName = 'videoQueue';

    static open() {
      return new Promise((resolve, reject) => {
        const request = indexedDB.open(this.dbName, 1);
        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);
        request.onupgradeneeded = (e) => {
          const db = e.target.result;
          if (!db.objectStoreNames.contains(this.storeName)) {
            db.createObjectStore(this.storeName, { keyPath: 'id' });
          }
        };
      });
    }

    static async save(session) {
      const db = await this.open();
      return new Promise((resolve, reject) => {
        const tx = db.transaction(this.storeName, 'readwrite');
        const store = tx.objectStore(this.storeName);
        
        const serialized = {
          id: session.id,
          isEmbed: session.isEmbed,
          embedUrl: session.embedUrl,
          file: session.file,
          title: session.title,
          description: session.description,
          tags: session.tags,
          visibility: session.visibility,
          categoryIds: session.categoryIds,
          playlistIds: session.playlistIds,
          progress: session.progress,
          speed: session.speed,
          eta: session.eta,
          status: session.status,
          sessionId: session.sessionId,
          videoId: session.videoId,
          token: session.token,
          uploadedBytes: session.uploadedBytes,
          videoUrl: session.videoUrl,
          isReel: session.isReel,
          thumbnails: session.thumbnails,
          selectedThumbDataUrl: session.selectedThumbDataUrl,
          retries: session.retries || 0,
          createdAt: session.createdAt || Date.now()
        };
        
        const req = store.put(serialized);
        req.onsuccess = () => resolve();
        req.onerror = () => reject(req.error);
      });
    }

    static async delete(id) {
      const db = await this.open();
      return new Promise((resolve, reject) => {
        const tx = db.transaction(this.storeName, 'readwrite');
        const store = tx.objectStore(this.storeName);
        const req = store.delete(id);
        req.onsuccess = () => resolve();
        req.onerror = () => reject(req.error);
      });
    }

    static async getAll() {
      const db = await this.open();
      return new Promise((resolve, reject) => {
        const tx = db.transaction(this.storeName, 'readonly');
        const store = tx.objectStore(this.storeName);
        const req = store.getAll();
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
      });
    }
  }

  // DOM Elements
  const dropZone = document.getElementById('drop-zone');
  const fileInput = document.getElementById('video-file-input');
  const uploadsQueue = document.getElementById('uploads-queue');
  const detailsForm = document.getElementById('details-editor-form');
  const editorPlaceholder = document.getElementById('editor-placeholder');
  
  // Form elements
  const detailsTitle = document.getElementById('details-title');
  const detailsDesc = document.getElementById('details-desc');
  const detailsTags = document.getElementById('details-tags');
  const detailsVisibility = document.getElementById('details-visibility');
  const reelsCheckboxGroup = document.getElementById('reels-checkbox-group');
  const detailsIsReel = document.getElementById('details-is-reel');
  const saveMetadataBtn = document.getElementById('save-metadata-btn');
  const spaPlayer = document.getElementById('spa-player');
  const spaIframePlayer = document.getElementById('spa-iframe-player');
  const detailsPreviewTitle = document.getElementById('details-preview-title');
  const detailsPreviewDuration = document.getElementById('details-preview-duration');
  
  // Custom thumb file picker
  const customThumbInput = document.getElementById('spa-custom-thumb');

  // Drag & drop handlers
  if (dropZone) {
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
    dropZone.addEventListener('drop', e => {
      e.preventDefault();
      dropZone.classList.remove('drag-over');
      const files = e.dataTransfer.files;
      handleSelectedFiles(files);
    });
  }

  if (fileInput) {
    fileInput.addEventListener('change', function() {
      handleSelectedFiles(this.files);
    });
  }

  // Switch between Drop File and Embed URL import
  window.switchSourceType = function(type) {
    sourceType = type;
    document.getElementById('tab-file-btn').classList.toggle('active', type === 'file');
    document.getElementById('tab-embed-btn').classList.toggle('active', type === 'embed');
    document.getElementById('file-drop-container').style.display = type === 'file' ? 'block' : 'none';
    document.getElementById('embed-url-container').style.display = type === 'embed' ? 'block' : 'none';
  };

  // Helper to extract duration before queueing
  function getVideoDuration(file) {
    return new Promise((resolve) => {
      const video = document.createElement('video');
      video.preload = 'metadata';
      video.src = URL.createObjectURL(file);
      video.onloadedmetadata = () => {
        const dur = video.duration || 0;
        URL.revokeObjectURL(video.src);
        resolve(dur);
      };
      video.onerror = () => {
        URL.revokeObjectURL(video.src);
        resolve(0);
      };
    });
  }

  // Main file processing entrance
  async function handleSelectedFiles(files) {
    if (files.length === 0) return;

    const urlParams = new URLSearchParams(window.location.search);
    const isReelMode = urlParams.get('mode') === 'reel';

    // Show dashboard layouts, hide default dropzones
    document.getElementById('welcome-dropzone').style.display = 'none';
    document.getElementById('top-dropzone').style.display = 'block';
    document.getElementById('studio-dashboard').style.display = 'block';

    let queuedCount = 0;
    for (let i = 0; i < files.length; i++) {
      const file = files[i];
      // Basic check
      if (!file.type.startsWith('video/') && !file.name.match(/\.(mp4|webm|mov|avi|mkv)$/i)) {
        showToast(`File "${file.name}" is not a supported video.`, 'danger');
        continue;
      }
      
      if (isReelMode) {
        const duration = await getVideoDuration(file);
        if (duration > 60) {
          showToast(`File "${file.name}" exceeds 60s limit for Reels (${Math.round(duration)}s).`, 'danger');
          continue;
        }
      }
      
      createUploadSession(file);
      queuedCount++;
    }
    fileInput.value = ''; // clear input

    // If nothing was successfully queued, and no other uploads are active, revert view
    if (Object.keys(uploads).length === 0) {
      document.getElementById('welcome-dropzone').style.display = 'block';
      document.getElementById('top-dropzone').style.display = 'none';
      document.getElementById('studio-dashboard').style.display = 'none';
    }
  }

  // Import Embed logic
  window.verifyAndLoadEmbed = async function() {
    let urlVal = document.getElementById('embed-link-field').value.trim();
    if (!urlVal) {
      alert('Please enter a video URL.');
      return;
    }
    
    // Quick iframe extraction
    if (urlVal.includes('<iframe')) {
      const match = urlVal.match(/src=["']([^"']+)["']/i);
      if (match && match[1]) {
        urlVal = match[1];
      }
    }

    // Hide welcome panel & switch to dashboard layout
    document.getElementById('welcome-dropzone').style.display = 'none';
    document.getElementById('top-dropzone').style.display = 'block';
    document.getElementById('studio-dashboard').style.display = 'block';
    document.getElementById('embed-link-field').value = '';

    createEmbedSession(urlVal);
  };

  // Create an embed upload item session
  async function createEmbedSession(url) {
    const uploadId = 'up_' + Math.random().toString(36).substr(2, 9);
    const ytId = getYoutubeId(url);
    const defaultTitle = ytId ? 'YouTube Import #' + ytId : 'External Import #' + Math.floor(Math.random() * 10000);

    const session = {
      id: uploadId,
      isEmbed: true,
      embedUrl: url,
      file: null,
      title: defaultTitle,
      description: 'Imported external video link.',
      tags: 'import, embed',
      visibility: 'public',
      categoryIds: [],
      playlistIds: [],
      progress: 0,
      speed: 0,
      eta: 0,
      status: 'queued',
      sessionId: null,
      videoId: null,
      token: null,
      thumbnails: [],
      selectedThumbDataUrl: null,
      localBlobUrl: null,
      isReel: (new URLSearchParams(window.location.search).get('mode') === 'reel') ? 1 : 0,
      createdAt: Date.now(),
      activeLoopRunning: false
    };

    uploads[uploadId] = session;
    renderQueueCard(session);
    updateQueueCount();

    await UploadDB.save(session);

    if (!selectedUploadId) {
      selectUpload(uploadId);
    }

    refreshQueueUI();
    processQueue();
  }

  // Start background upload session
  async function createUploadSession(file) {
    const uploadId = 'up_' + Math.random().toString(36).substr(2, 9);
    const cleanTitle = file.name.substring(0, file.name.lastIndexOf('.')) || file.name;

    const session = {
      id: uploadId,
      isEmbed: false,
      file: file,
      title: cleanTitle.substring(0, 100),
      description: '',
      tags: '',
      visibility: 'public',
      categoryIds: [],
      playlistIds: [],
      progress: 0,
      speed: 0,
      eta: 0,
      status: 'queued',
      sessionId: null,
      videoId: null,
      token: null,
      uploadedBytes: 0,
      thumbnails: [],
      selectedThumbDataUrl: null,
      localBlobUrl: URL.createObjectURL(file),
      isReel: (new URLSearchParams(window.location.search).get('mode') === 'reel') ? 1 : 0,
      abortController: new AbortController(),
      createdAt: Date.now(),
      activeLoopRunning: false
    };

    uploads[uploadId] = session;
    renderQueueCard(session);
    updateQueueCount();

    await UploadDB.save(session);

    if (!selectedUploadId) {
      selectUpload(uploadId);
    }

    refreshQueueUI();
    processQueue();
  }

  // Render a nice queue list item card
  function renderQueueCard(session) {
    const card = document.createElement('div');
    card.className = 'upload-item-card';
    card.id = `card_${session.id}`;
    card.addEventListener('click', () => selectUpload(session.id));

    card.innerHTML = `
      <div style="display:flex; justify-content:space-between; align-items:start; gap:8px;">
        <div style="display:flex; gap:10px; align-items:center; min-width:0; flex:1;">
          <div class="card-thumb-preview" id="thumb_${session.id}" style="width:50px; aspect-ratio:16/9; background:#000; border-radius:6px; overflow:hidden; flex-shrink:0; display:flex; align-items:center; justify-content:center; color:var(--text3);">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="3" x2="9" y2="21"/></svg>
          </div>
          <div style="min-width:0; flex:1;">
            <div class="card-title-lbl" style="font-size:0.8rem; font-weight:700; color:#fff; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; margin-bottom:2px;" id="lbl_title_${session.id}">${escapeHtml(session.title)}</div>
            <div style="font-size:0.7rem; color:var(--text2); display:flex; gap:6px; align-items:center; flex-wrap:wrap;" id="lbl_meta_${session.id}">
              <span id="lbl_status_${session.id}">Initializing...</span>
              <span id="lbl_pct_${session.id}">0%</span>
            </div>
          </div>
        </div>
        <div style="display:flex; align-items:center; gap:4px; flex-shrink:0;">
          <button type="button" class="queue-action-btn pause-resume-btn" id="btn_pause_${session.id}" onclick="pauseUploadSession(event, '${session.id}')" title="Pause Upload" style="display:none; color:var(--text2); padding:4px;">
            <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><rect x="4" y="4" width="4" height="16"/><rect x="16" y="4" width="4" height="16"/></svg>
          </button>
          <button type="button" class="queue-action-btn pause-resume-btn" id="btn_resume_${session.id}" onclick="resumeUploadSession(event, '${session.id}')" title="Resume Upload" style="display:none; color:var(--accent); padding:4px;">
            <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><polygon points="5,4 19,12 5,20"/></svg>
          </button>
          <button type="button" class="cancel-upload-btn" onclick="cancelUploadSession(event, '${session.id}')" title="Cancel/Remove Upload">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
      </div>
      <div class="progress-mini-bar">
        <div class="progress-mini-fill" id="fill_${session.id}"></div>
      </div>
    `;

    uploadsQueue.appendChild(card);
  }

  // Update badge number of queue
  function updateQueueCount() {
    const badge = document.getElementById('queue-count-badge');
    if (badge) {
      badge.textContent = Object.keys(uploads).length;
    }
  }

  // Cancel an upload session (and abort active chunk request)
  window.cancelUploadSession = async function(e, id) {
    if (e) e.stopPropagation();
    const session = uploads[id];
    if (!session) return;

    if (session.status === 'uploading' || session.status === 'processing' || session.status === 'retrying') {
      if (!confirm(`Cancel and abort upload for "${session.title}"?`)) return;
    }

    // Abort HTTP chunk uploads
    if (session.abortController) {
      session.abortController.abort();
    }

    // Clean local blob memory
    if (session.localBlobUrl) {
      URL.revokeObjectURL(session.localBlobUrl);
    }

    // No server-side video record to clean up — deferred-publish means
    // no videos row exists until finalization succeeds.
    // Temp upload chunks will be cleaned up by the server naturally.

    // Delete from IndexedDB
    await UploadDB.delete(id);

    // Delete from memory and DOM
    delete uploads[id];
    const card = document.getElementById(`card_${id}`);
    if (card) card.remove();
    updateQueueCount();

    // If it was selected, clear details panel
    if (selectedUploadId === id) {
      selectedUploadId = null;
      detailsForm.style.display = 'none';
      editorPlaceholder.style.display = 'block';
    }

    showToast(`Upload removed.`, 'yellow');

    refreshQueueUI();
    processQueue();
  };

  // Pause session uploader
  window.pauseUploadSession = async function(e, id) {
    if (e) e.stopPropagation();
    const session = uploads[id];
    if (!session) return;

    session.status = 'paused';
    session.activeLoopRunning = false;
    if (session.abortController) {
      session.abortController.abort();
      session.abortController = new AbortController(); // recreate for next resume
    }
    
    await UploadDB.save(session);
    updateCardProgress(session, 'Paused', 'paused');
    updateSelectedEditorState(session);
    
    refreshQueueUI();
    processQueue();
  };

  // Resume session uploader
  window.resumeUploadSession = async function(e, id) {
    if (e) e.stopPropagation();
    const session = uploads[id];
    if (!session) return;

    session.status = 'queued';
    session.retries = 0;
    session.activeLoopRunning = false;
    
    await UploadDB.save(session);
    updateCardProgress(session, 'Queued...', 'uploading');
    updateSelectedEditorState(session);
    
    refreshQueueUI();
    processQueue();
  };

  // Get active uploading/processing counts
  function getActiveUploadsCount() {
    return Object.values(uploads).filter(u => u.status === 'uploading' || u.status === 'processing').length;
  }

  // Pick next items and process sequential queueing
  async function processQueue() {
    const activeCount = getActiveUploadsCount();
    if (activeCount >= MAX_CONCURRENT_UPLOADS) return;

    const list = Object.values(uploads).sort((a, b) => (a.createdAt || 0) - (b.createdAt || 0));
    const next = list.find(u => (u.status === 'queued' || u.status === 'retrying') && !u.activeLoopRunning);
    if (!next) return;

    if (next.isEmbed) {
      startEmbedProcess(next);
    } else {
      startFileProgressiveUpload(next);
    }
  }

  // Refresh dynamic queue UI position counters
  function refreshQueueUI() {
    Object.values(uploads).forEach(session => {
      const fillState = session.status === 'published' ? 'published' : (session.status === 'failed' ? 'failed' : (session.status === 'processing' ? 'processing' : 'uploading'));
      let text = 'Queued...';
      if (session.status === 'paused') text = 'Paused';
      if (session.status === 'failed') text = 'Failed';
      if (session.status === 'published') text = 'Published';
      if (session.status === 'processing') text = 'Processing...';
      
      updateCardProgress(session, text, fillState);
    });
  }

  // Select an upload from the queue list to edit details
  function selectUpload(id) {
    const session = uploads[id];
    if (!session) return;

    selectedUploadId = id;

    // Highlight selected card
    document.querySelectorAll('.upload-item-card').forEach(c => c.classList.remove('selected'));
    const cardEl = document.getElementById(`card_${id}`);
    if (cardEl) cardEl.classList.add('selected');

    // Display editor, hide placeholder
    editorPlaceholder.style.display = 'none';
    detailsForm.style.display = 'block';

    // Update panel title and status
    document.getElementById('editor-selected-title').textContent = `Metadata: ${session.title}`;
    const statusBadge = document.getElementById('editor-selected-status');
    statusBadge.textContent = session.status.toUpperCase();
    statusBadge.className = 'badge badge-' + (session.status === 'published' ? 'green' : (session.status === 'failed' ? 'red' : 'yellow'));

    // Populate input fields
    detailsTitle.value = session.title;
    detailsDesc.value = session.description;
    detailsTags.value = session.tags;
    detailsVisibility.value = session.visibility;

    // Set preview player
    if (session.isEmbed) {
      spaPlayer.style.display = 'none';
      const ytId = getYoutubeId(session.embedUrl);
      if (ytId) {
        spaIframePlayer.src = `https://www.youtube.com/embed/${ytId}`;
        spaIframePlayer.style.display = 'block';
      } else {
        spaIframePlayer.style.display = 'none';
        spaPlayer.src = session.embedUrl;
        spaPlayer.style.display = 'block';
      }
      detailsPreviewDuration.textContent = 'Embed URL';
    } else {
      spaIframePlayer.style.display = 'none';
      spaIframePlayer.src = '';
      if (session.status === 'published') {
        // play final uploaded video url
        spaPlayer.src = session.videoUrl;
      } else {
        spaPlayer.src = session.localBlobUrl;
      }
      spaPlayer.style.display = 'block';
      
      // read duration
      if (spaPlayer.duration) {
        const dur = Math.max(1, Math.floor(spaPlayer.duration || 0));
        session.duration = dur;
        detailsPreviewDuration.textContent = formatTime(dur);
      } else {
        spaPlayer.onloadedmetadata = () => {
          const dur = Math.max(1, Math.floor(spaPlayer.duration || 0));
          session.duration = dur;
          detailsPreviewDuration.textContent = formatTime(dur);
          updateReelsUI(dur);
        };
      }
    }

    detailsPreviewTitle.textContent = session.title;

    // Bind listeners to title changes
    detailsTitle.oninput = function() {
      session.title = this.value;
      detailsPreviewTitle.textContent = this.value;
      const cardTitle = document.getElementById(`lbl_title_${session.id}`);
      if (cardTitle) cardTitle.textContent = this.value;
    };
    
    detailsDesc.oninput = function() { session.description = this.value; };
    detailsTags.oninput = function() { session.tags = this.value; };
    detailsVisibility.onchange = function() { session.visibility = this.value; };

    // Reels dynamic logic
    const updateReelsUI = (dur) => {
      if (reelsCheckboxGroup) reelsCheckboxGroup.style.display = 'none';
      const urlParams = new URLSearchParams(window.location.search);
      const isReelMode = urlParams.get('mode') === 'reel';
      session.isReel = isReelMode ? 1 : 0;
      if (detailsIsReel) detailsIsReel.checked = isReelMode;
      
      const thumbPanel = document.getElementById('thumbnail-selector-panel');
      if (thumbPanel) {
        thumbPanel.style.display = isReelMode ? 'none' : 'block';
      }
    };

    const initialDur = session.duration || spaPlayer.duration || 0;
    updateReelsUI(initialDur);

    // Setup Category & Playlists checkboxes
    document.querySelectorAll('.category-checkbox').forEach(cb => {
      cb.checked = session.categoryIds.includes(cb.value);
      cb.onchange = function() {
        if (this.checked) {
          if (!session.categoryIds.includes(this.value)) session.categoryIds.push(this.value);
        } else {
          session.categoryIds = session.categoryIds.filter(v => v !== this.value);
        }
      };
    });

    document.querySelectorAll('.playlist-checkbox').forEach(cb => {
      cb.checked = session.playlistIds.includes(cb.value);
      cb.onchange = function() {
        if (this.checked) {
          if (!session.playlistIds.includes(this.value)) session.playlistIds.push(this.value);
        } else {
          session.playlistIds = session.playlistIds.filter(v => v !== this.value);
        }
      };
    });

    // Populate thumbnail selector grid
    renderThumbnailsGrid(session);
  }

  // Render thumbnail options in selector grid
  function renderThumbnailsGrid(session) {
    const grid = document.getElementById('details-thumb-grid');
    const customTrigger = grid.querySelector('.custom-thumb-trigger');
    grid.innerHTML = '';
    grid.appendChild(customTrigger);

    // Render generated frames
    session.thumbnails.forEach((thumbUrl, idx) => {
      const isSelected = (session.selectedThumbDataUrl === thumbUrl);
      const opt = document.createElement('div');
      opt.className = 'thumb-option' + (isSelected ? ' selected' : '');
      opt.innerHTML = `
        <img src="${thumbUrl}">
        <div class="check-badge"><svg width="10" height="10" fill="#fff" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
        <div style="position:absolute;bottom:3px;left:6px;font-size:0.65rem;color:#fff;text-shadow:0 1px 2px #000">Frame ${idx+1}</div>
      `;
      opt.addEventListener('click', () => {
        grid.querySelectorAll('.thumb-option').forEach(o => o.classList.remove('selected'));
        opt.classList.add('selected');
        session.selectedThumbDataUrl = thumbUrl;
        
        // upload thumbnail instantly using session_id or video_id
        saveCustomThumbnail(session, thumbUrl);
      });
      grid.appendChild(opt);
    });

    // If custom thumbnail is selected and it is not in the generated frames
    if (session.selectedThumbDataUrl && !session.thumbnails.includes(session.selectedThumbDataUrl)) {
      const opt = document.createElement('div');
      opt.className = 'thumb-option selected';
      opt.innerHTML = `
        <img src="${session.selectedThumbDataUrl}">
        <div class="check-badge"><svg width="10" height="10" fill="#fff" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
        <div style="position:absolute;bottom:3px;left:6px;font-size:0.65rem;color:#fff;text-shadow:0 1px 2px #000">Uploaded</div>
      `;
      opt.addEventListener('click', () => {
        grid.querySelectorAll('.thumb-option').forEach(o => o.classList.remove('selected'));
        opt.classList.add('selected');
        session.selectedThumbDataUrl = session.selectedThumbDataUrl;
      });
      grid.appendChild(opt);
    }
  }

  // Handle custom image thumbnail file picker
  if (customThumbInput) {
    customThumbInput.addEventListener('change', function() {
      const file = this.files[0];
      if (!file) return;

      const session = uploads[selectedUploadId];
      if (!session) return;

      const reader = new FileReader();
      reader.onload = e => {
        const img = new Image();
        img.src = e.target.result;
        img.onload = () => {
          const imgPortrait = img.naturalHeight > img.naturalWidth;
          const canvas = document.createElement('canvas');
          if (imgPortrait) {
            canvas.width = 720;
            canvas.height = 1280;
          } else {
            canvas.width = 1280;
            canvas.height = 720;
          }
          const ctx = canvas.getContext('2d');
          drawImageFit(img, canvas, ctx);
          const dataUrl = canvas.toDataURL('image/jpeg', 0.85);

          session.selectedThumbDataUrl = dataUrl;
          renderThumbnailsGrid(session);

          // Upload thumbnail instantly
          saveCustomThumbnail(session, dataUrl);
        };
      };
      reader.readAsDataURL(file);
      this.value = ''; // clear input
    });
  }

  // Upload thumbnail base64 data to server (supports pre-publish via session_id)
  async function saveCustomThumbnail(session, dataUrl) {
    if (!session || !dataUrl) return;
    const payload = { data_url: dataUrl };
    if (session.videoId) {
      payload.video_id = session.videoId;
    } else if (session.sessionId) {
      payload.session_id = session.sessionId;
    } else {
      return; // Neither ID available yet
    }
    try {
      await fetch('<?= BASE_URL ?>/api/thumbnails.php?action=save_thumbnail', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
      });
    } catch (e) {}
  }

  // Start background upload loop for standard video files
  async function startFileProgressiveUpload(session) {
    const file = session.file;
    session.activeLoopRunning = true;
    session.status = 'uploading';
    await UploadDB.save(session);

    try {
      const meta = {
        title: session.title,
        description: session.description,
        tags: session.tags,
        visibility: session.visibility,
        category_ids: session.categoryIds,
        is_reel: session.isReel || 0
      };

      // 1. Init session if not already initialized
      if (!session.sessionId || !session.token) {
        updateCardProgress(session, 'Initializing...', 'uploading');
        const initRes = await fetch('<?= BASE_URL ?>/api/videos.php?action=init_upload', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({meta}),
          signal: session.abortController.signal
        });
        const initData = await initRes.json();
        if (!initData.success) {
          setUploadFailed(session, 'Initialization failed.');
          return;
        }

        session.sessionId = initData.data.session_id;
        session.token = initData.data.upload_token;
        await UploadDB.save(session);

        if (session.isReel) {
          probeReelDurationAndOrientation(session);
        } else {
          // Extract client-side thumbnails in the background
          extractThumbnailsInBackground(session);
        }
      } else {
        // Resume frame extraction if interrupted
        if (session.isReel) {
          if (!session.duration) {
            probeReelDurationAndOrientation(session);
          }
        } else {
          if (!session.thumbnails || session.thumbnails.length === 0) {
            extractThumbnailsInBackground(session);
          }
        }
      }

      // Check resumability status from the server
      let uploadedBytes = 0;
      try {
        const checkRes = await fetch(`<?= BASE_URL ?>/api/upload.php?action=status&session_id=${session.sessionId}&token=${session.token}`, {
          signal: session.abortController.signal
        });
        const checkData = await checkRes.json();
        if (checkData.success && checkData.data.uploaded) {
          uploadedBytes = checkData.data.uploaded;
        }
      } catch (e) {}

      session.uploadedBytes = uploadedBytes;
      await UploadDB.save(session);

      // 2. Progressive chunked upload loop (5MB Chunks)
      const CHUNK_SIZE = 5 * 1024 * 1024;
      const totalSize = file.size;

      let lastTime = Date.now();
      let lastBytes = uploadedBytes;

      updateCardProgress(session, 'Uploading...');

      for (let start = uploadedBytes; start < totalSize; start += CHUNK_SIZE) {
        if (session.status !== 'uploading' && session.status !== 'retrying') {
          session.activeLoopRunning = false;
          return; // Paused or Cancelled
        }

        const end = Math.min(start + CHUNK_SIZE, totalSize);
        const chunkBlob = file.slice(start, end);
        const formData = new FormData();
        formData.append('chunk', chunkBlob, file.name);

        let success = false;
        try {
          const uploadRes = await fetch(`<?= BASE_URL ?>/api/upload.php?session_id=${session.sessionId}&token=${session.token}`, {
            method: 'POST',
            body: formData,
            signal: session.abortController.signal
          });
          const uploadData = await uploadRes.json();
          if (uploadData.success) {
            success = true;
            session.uploadedBytes = uploadData.data.uploaded || end;
            session.retries = 0; // reset retries
            await UploadDB.save(session);
          } else {
            throw new Error('Upload failed');
          }
        } catch (e) {
          if (e.name === 'AbortError' || session.status === 'paused') {
            session.activeLoopRunning = false;
            return; // Interrupted
          }

          session.retries = (session.retries || 0) + 1;
          if (session.retries > 10) {
            setUploadFailed(session, 'Too many chunk failures.');
            return;
          }

          // Exponential backoff
          const delay = Math.min(30000, 1000 * Math.pow(2, session.retries)) + Math.random() * 1000;
          session.status = 'retrying';
          updateCardProgress(session, `Flicker - retrying...`);
          await UploadDB.save(session);
          
          await new Promise(r => setTimeout(r, delay));
          start -= CHUNK_SIZE; // retry chunk
          continue;
        }

        // Speed and ETA calculations
        const now = Date.now();
        const delta = (now - lastTime) / 1000;
        const deltaBytes = session.uploadedBytes - lastBytes;
        const speed = delta > 0 ? Math.round(deltaBytes / delta) : 0;
        lastTime = now;
        lastBytes = session.uploadedBytes;

        const pct = Math.floor((session.uploadedBytes / totalSize) * 100);
        session.progress = pct;
        session.speed = speed;
        
        const remainSec = speed > 0 ? Math.max(0, Math.round((totalSize - session.uploadedBytes) / speed)) : null;
        session.eta = remainSec;

        session.status = 'uploading';
        updateCardProgress(session, 'Uploading...');
        await UploadDB.save(session);
      }

      // 3. Finalize upload
      if (session.uploadedBytes >= totalSize) {
        updateCardProgress(session, 'Finalizing file...', 'processing');
        session.status = 'processing';
        updateSelectedEditorState(session);
        await UploadDB.save(session);

        const finalizeRes = await fetch(`<?= BASE_URL ?>/api/upload.php?session_id=${session.sessionId}&token=${session.token}&finalize=1&filename=${encodeURIComponent(file.name)}`, {
          method: 'POST',
          signal: session.abortController.signal
        });
        const finalizeData = await finalizeRes.json();

        if (finalizeData.success) {
          session.status = 'published';
          session.videoId = finalizeData.data.video_id;
          session.videoUrl = finalizeData.data.video_url;
          session.activeLoopRunning = false;

          // Duration is already saved in session meta_json and committed in the transaction
          // But re-save if we have a more accurate client-side reading
          await saveVideoDuration(session);

          // Thumbnail was already saved to upload_sessions.temp_thumb and committed
          // No need to re-upload

          updateCardProgress(session, 'Published', 'published');
          updateSelectedEditorState(session);

          // Clear completed upload from IndexedDB
          await UploadDB.delete(session.id);
          
          showToast(`🟢 <strong>"${escapeHtml(session.title)}"</strong> published successfully!`);
          
          // Auto-hide from queue after brief delay
          removePublishedCard(session.id);
          
          processQueue();
        } else {
          setUploadFailed(session, 'Finalization failed.');
        }
      }

    } catch (err) {
      if (err.name === 'AbortError') return;
      setUploadFailed(session, 'Connection error.');
    }
  }

  // Start background upload loop for external embeds
  async function startEmbedProcess(session) {
    session.activeLoopRunning = true;
    session.status = 'uploading';
    await UploadDB.save(session);

    try {
      const meta = {
        title: session.title,
        description: session.description,
        tags: session.tags,
        visibility: session.visibility,
        category_ids: session.categoryIds,
        is_reel: session.isReel || 0
      };

      // 1. Init session if not already initialized
      if (!session.sessionId || !session.token) {
        updateCardProgress(session, 'Initializing import...', 'uploading');
        const initRes = await fetch('<?= BASE_URL ?>/api/videos.php?action=init_upload', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({meta})
        });
        const initData = await initRes.json();
        if (!initData.success) {
          setUploadFailed(session, 'Import initialization failed.');
          return;
        }

        session.sessionId = initData.data.session_id;
        session.token = initData.data.upload_token;
        await UploadDB.save(session);

        // Import thumbnails from YouTube if applicable
        const ytId = getYoutubeId(session.embedUrl);
        if (ytId) {
          const ytMaxThumb = `https://img.youtube.com/vi/${ytId}/maxresdefault.jpg`;
          session.thumbnails = [
            ytMaxThumb,
            `https://img.youtube.com/vi/${ytId}/hqdefault.jpg`,
            `https://img.youtube.com/vi/${ytId}/mqdefault.jpg`
          ];
          session.selectedThumbDataUrl = ytMaxThumb;
          
          // Save thumb instantly using session_id
          convertAndSaveYtThumbnail(session, ytMaxThumb);
          
          if (selectedUploadId === session.id) {
            renderThumbnailsGrid(session);
          }
        }
      }

      updateCardProgress(session, 'Processing import...', 'processing');
      session.status = 'processing';
      await UploadDB.save(session);

      // 2. Finalize direct embed URL on backend
      const finalizeRes = await fetch(`<?= BASE_URL ?>/api/upload.php?session_id=${session.sessionId}&token=${session.token}&finalize=1&filename=${encodeURIComponent(session.embedUrl)}`, {
        method: 'POST'
      });
      const finalizeData = await finalizeRes.json();

      if (finalizeData.success) {
        session.status = 'published';
        session.videoId = finalizeData.data.video_id;
        session.videoUrl = finalizeData.data.video_url;
        session.activeLoopRunning = false;
        updateCardProgress(session, 'Published', 'published');
        updateSelectedEditorState(session);

        await UploadDB.delete(session.id);
        showToast(`🟢 <strong>"${escapeHtml(session.title)}"</strong> imported successfully!`);
        
        // Auto-hide from queue after brief delay
        removePublishedCard(session.id);
        
        processQueue();
      } else {
        setUploadFailed(session, 'Import finalization failed.');
      }

    } catch (e) {
      setUploadFailed(session, 'Import failed.');
    }
  }

  // Convert YouTube thumbnail to Base64 and save it
  function convertAndSaveYtThumbnail(session, url) {
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = function() {
      const imgPortrait = img.naturalHeight > img.naturalWidth;
      const canvas = document.createElement('canvas');
      if (imgPortrait) {
        canvas.width = 720;
        canvas.height = 1280;
      } else {
        canvas.width = 1280;
        canvas.height = 720;
      }
      const ctx = canvas.getContext('2d');
      drawImageFit(img, canvas, ctx);
      const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
      saveCustomThumbnail(session, dataUrl);
    };
    img.src = url;
  }

  // Extract client-side video frames in the background
  async function extractThumbnailsInBackground(session) {
    const helperVideo = document.createElement('video');
    helperVideo.preload = 'auto';
    helperVideo.src = session.localBlobUrl;
    helperVideo.muted = true;
    helperVideo.playsInline = true;

    helperVideo.onloadedmetadata = async () => {
      const duration = Math.max(1, Math.floor(helperVideo.duration || 0));
      const count = 7;
      const step = duration / (count + 1);
      const times = Array.from({length: count}, (_, i) => step * (i + 1));

      const vWidth = helperVideo.videoWidth || 640;
      const vHeight = helperVideo.videoHeight || 360;
      const isPortrait = vHeight > vWidth;
      session.duration = duration;
      session.isReel = (isPortrait && duration <= 60) ? 1 : 0;
      
      const urlParams = new URLSearchParams(window.location.search);
      session.isReel = (urlParams.get('mode') === 'reel') ? 1 : 0;
      
      await UploadDB.save(session);

      // Save video orientation and duration to backend session asynchronously
      if (session.sessionId) {
        saveVideoOrientationAndDuration(session);
      }

      const maxDim = 640;
      let cw, ch;
      if (isPortrait) {
        cw = Math.round(maxDim * (vWidth / vHeight));
        ch = maxDim;
      } else {
        cw = maxDim;
        ch = Math.round(maxDim * (vHeight / vWidth));
      }

      const canvas = document.createElement('canvas');
      canvas.width = cw || (isPortrait ? 360 : 640);
      canvas.height = ch || (isPortrait ? 640 : 360);
      const ctx = canvas.getContext('2d');

      for (let i = 0; i < count; i++) {
        try {
          const time = times[i];
          const dataUrl = await seekAndCapture(helperVideo, canvas, ctx, time);
          session.thumbnails.push(dataUrl);

          // Auto-select first frame as default
          if (i === 0 && !session.selectedThumbDataUrl) {
            session.selectedThumbDataUrl = dataUrl;
            // update mini card preview immediately
            const miniThumb = document.getElementById(`thumb_${session.id}`);
            if (miniThumb) {
              miniThumb.innerHTML = `<img src="${dataUrl}" style="width:100%;height:100%;object-fit:contain;background:#000">`;
            }
            saveCustomThumbnail(session, dataUrl);
          }

          await UploadDB.save(session);

          // Update grid dynamically if current video is selected
          if (selectedUploadId === session.id) {
            renderThumbnailsGrid(session);
          }
        } catch (e) {}
      }
      URL.revokeObjectURL(helperVideo.src);
    };
  }

  // Probe duration and orientation for Reels without extracting thumbnails
  async function probeReelDurationAndOrientation(session) {
    const helperVideo = document.createElement('video');
    helperVideo.preload = 'auto';
    helperVideo.src = session.localBlobUrl;
    helperVideo.muted = true;
    helperVideo.playsInline = true;

    helperVideo.onloadedmetadata = async () => {
      const duration = Math.max(1, Math.floor(helperVideo.duration || 0));
      session.duration = duration;
      session.isReel = 1;
      
      await UploadDB.save(session);

      // Save video orientation and duration to backend session asynchronously
      if (session.sessionId) {
        saveVideoOrientationAndDuration(session);
      }
      
      // Update UI duration
      if (selectedUploadId === session.id) {
        detailsPreviewDuration.textContent = formatTime(duration);
      }
      URL.revokeObjectURL(helperVideo.src);
    };
  }

  // Save video duration to server (supports pre-publish via session_id)
  async function saveVideoDuration(session) {
    let d = 0;
    if (selectedUploadId === session.id && spaPlayer.duration) {
      d = Math.max(1, Math.floor(spaPlayer.duration || 0));
    }
    if (d <= 0) {
      // Create quick hidden probe element
      const probe = document.createElement('video');
      probe.src = session.localBlobUrl;
      await new Promise(r => {
        probe.onloadedmetadata = () => {
          d = Math.max(1, Math.floor(probe.duration || 0));
          r();
        };
        probe.onerror = () => r();
        setTimeout(r, 3000); // safety timeout
      });
    }
    if (d > 0) {
      const payload = { duration: d };
      if (session.videoId) {
        payload.video_id = session.videoId;
      } else if (session.sessionId) {
        payload.session_id = session.sessionId;
      } else {
        return;
      }
      try {
        await fetch('<?= BASE_URL ?>/api/thumbnails.php?action=save_duration', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify(payload)
        });
      } catch (e) {}
    }
  }

  // Update card progress bar and label text
  function updateCardProgress(session, text, fillState = 'uploading') {
    const cardStatus = document.getElementById(`lbl_status_${session.id}`);
    const pctLabel = document.getElementById(`lbl_pct_${session.id}`);
    const fill = document.getElementById(`fill_${session.id}`);
    const pauseBtn = document.getElementById(`btn_pause_${session.id}`);
    const resumeBtn = document.getElementById(`btn_resume_${session.id}`);

    // Update buttons visibility based on status
    if (pauseBtn && resumeBtn) {
      if (session.status === 'uploading' || session.status === 'retrying') {
        pauseBtn.style.display = 'inline-block';
        resumeBtn.style.display = 'none';
      } else if (session.status === 'paused' || session.status === 'failed') {
        pauseBtn.style.display = 'none';
        resumeBtn.style.display = 'inline-block';
      } else {
        pauseBtn.style.display = 'none';
        resumeBtn.style.display = 'none';
      }
    }

    // Determine Queue Position if status is queued
    let statusText = text;
    if (session.status === 'queued') {
      const sortedQueue = Object.values(uploads)
        .filter(u => u.status === 'queued')
        .sort((a, b) => (a.createdAt || 0) - (b.createdAt || 0));
      const pos = sortedQueue.findIndex(u => u.id === session.id) + 1;
      statusText = `Queued (Pos ${pos})`;
    }

    if (cardStatus) {
      if (session.status === 'uploading' && session.progress > 0) {
        const speedFmt = session.speed > 0 ? formatBytes(session.speed) + '/s' : '';
        const etaFmt = session.eta !== null ? formatETA(session.eta) + ' remaining' : '';
        cardStatus.innerHTML = `<span style="color:var(--accent)">📤 ${session.progress}%</span> · ${speedFmt} · ${etaFmt}`;
      } else if (session.status === 'retrying' && session.retries > 0) {
        cardStatus.innerHTML = `<span style="color:var(--yellow)">⚠️ Retrying (${session.retries}/10)</span>`;
      } else {
        cardStatus.textContent = statusText;
      }
    }

    if (pctLabel) {
      pctLabel.textContent = (session.status === 'uploading' || session.status === 'processing') ? session.progress + '%' : '';
    }

    if (fill) {
      fill.className = `progress-mini-fill ${fillState}`;
      fill.style.width = (session.status === 'uploading' ? session.progress : 100) + '%';
    }
  }

  // Auto-remove published card from queue after a brief visual delay
  function removePublishedCard(id) {
    setTimeout(() => {
      const card = document.getElementById(`card_${id}`);
      if (card) {
        card.style.transition = 'opacity 0.5s ease, transform 0.5s ease, max-height 0.5s ease';
        card.style.opacity = '0';
        card.style.transform = 'translateX(30px)';
        card.style.maxHeight = card.offsetHeight + 'px';
        card.style.overflow = 'hidden';
        
        setTimeout(() => {
          card.style.maxHeight = '0';
          card.style.padding = '0';
          card.style.margin = '0';
          card.style.border = 'none';
          
          setTimeout(() => {
            card.remove();
            delete uploads[id];
            updateQueueCount();
            
            // If this was the selected card, clear editor
            if (selectedUploadId === id) {
              selectedUploadId = null;
              detailsForm.style.display = 'none';
              editorPlaceholder.style.display = 'block';
              
              // Auto-select next available card if any
              const remaining = Object.keys(uploads);
              if (remaining.length > 0) {
                selectUpload(remaining[0]);
              }
            }
            
            // If no uploads left, show welcome dropzone again
            if (Object.keys(uploads).length === 0) {
              document.getElementById('welcome-dropzone').style.display = 'block';
              document.getElementById('top-dropzone').style.display = 'none';
              document.getElementById('studio-dashboard').style.display = 'none';
            }
          }, 500);
        }, 50);
      }
    }, 2000); // Show "Published" status for 2 seconds before removing
  }

  // Mark session upload as failed
  function setUploadFailed(session, reason) {
    session.status = 'failed';
    session.activeLoopRunning = false;
    UploadDB.save(session);
    updateCardProgress(session, `❌ Failed: ${reason}`, 'failed');
    updateSelectedEditorState(session);
    showToast(`❌ <strong>"${escapeHtml(session.title)}"</strong> upload failed.`, 'danger');
    processQueue();
  }

  // Update selected details form header states
  function updateSelectedEditorState(session) {
    if (selectedUploadId !== session.id) return;
    const statusBadge = document.getElementById('editor-selected-status');
    statusBadge.textContent = session.status.toUpperCase();
    statusBadge.className = 'badge badge-' + (session.status === 'published' ? 'green' : (session.status === 'failed' ? 'red' : 'yellow'));
  }

  // Save active video metadata using AJAX (supports pre-publish via session_id)
  window.saveActiveMetadata = async function(e) {
    if (e) e.preventDefault();
    const session = uploads[selectedUploadId];
    if (!session) return;

    if (!session.sessionId && !session.videoId) {
      alert('Video is initializing. Please wait a moment.');
      return;
    }

    if (session.isReel === 1 && session.duration > 60) {
      alert('Reels must be 60 seconds or less. Please upload a shorter video.');
      return;
    }

    saveMetadataBtn.disabled = true;
    saveMetadataBtn.textContent = 'Saving details...';

    const meta = {
      title: session.title,
      description: session.description,
      tags: session.tags,
      visibility: session.visibility,
      category_ids: session.categoryIds,
      is_reel: session.isReel || 0
    };
    // Send the appropriate ID depending on publish state
    if (session.videoId) {
      meta.video_id = session.videoId;
    } else if (session.sessionId) {
      meta.session_id = session.sessionId;
    }

    try {
      // 1. Save metadata via POST API
      const res = await fetch('<?= BASE_URL ?>/api/videos.php?action=save_metadata', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({meta})
      });
      const d = await res.json();
      
      if (d.success) {
        // 2. Save thumbnail choice if present
        if (session.selectedThumbDataUrl) {
          await saveCustomThumbnail(session, session.selectedThumbDataUrl);
        }
        
        showToast(`💾 Details saved successfully for "${escapeHtml(session.title)}"!`);
      } else {
        alert('Error saving details: ' + (d.message || 'Server error'));
      }
    } catch (err) {
      alert('Failed to save details. Check your connection.');
    } finally {
      saveMetadataBtn.disabled = false;
      saveMetadataBtn.textContent = '💾 Save Video Details';
    }
  };

  // Show a gorgeous sliding notification card toast
  function showToast(msg, type = 'success') {
    const toast = document.createElement('div');
    toast.className = 'toast-card';
    
    let icon = '✓';
    let color = 'var(--green)';
    if (type === 'danger') { icon = '✗'; color = 'var(--red)'; }
    else if (type === 'yellow') { icon = '⚠️'; color = 'var(--yellow)'; }

    toast.innerHTML = `
      <div style="width:24px; height:24px; border-radius:50%; background:${color}; display:flex; align-items:center; justify-content:center; font-weight:800; color:#fff; font-size:0.8rem; flex-shrink:0;">${icon}</div>
      <div style="font-size:0.82rem; line-height:1.4;">${msg}</div>
    `;

    const container = document.getElementById('upload-toasts-container');
    container.appendChild(toast);

    setTimeout(() => {
      toast.style.transform = 'translateY(-20px)';
      toast.style.opacity = '0';
      setTimeout(() => toast.remove(), 400);
    }, 4500);
  }

  // Helper seeks frame and extracts image JPEG base64
  function seekAndCapture(video, canvas, ctx, time) {
    return new Promise((resolve, reject) => {
      const onSeeked = () => {
        video.removeEventListener('seeked', onSeeked);
        video.removeEventListener('error', onError);
        try {
          drawVideoFit(video, canvas, ctx);
          resolve(canvas.toDataURL('image/jpeg', 0.8));
        } catch (e) { reject(e); }
      };
      const onError = (e) => {
        video.removeEventListener('seeked', onSeeked);
        video.removeEventListener('error', onError);
        reject(e);
      };
      video.addEventListener('seeked', onSeeked);
      video.addEventListener('error', onError);
      video.currentTime = time;
    });
  }

  // Draw video using letterbox/pillarbox fitting
  function drawVideoFit(video, canvas, ctx) {
    const cw = canvas.width;
    const ch = canvas.height;
    const vw = video.videoWidth;
    const vh = video.videoHeight;
    
    // Clear / Fill black
    ctx.fillStyle = '#000000';
    ctx.fillRect(0, 0, cw, ch);
    
    const canvasRatio = cw / ch;
    const videoRatio = vw / vh;
    
    let drawWidth, drawHeight, x, y;
    if (videoRatio > canvasRatio) {
      drawWidth = cw;
      drawHeight = cw / videoRatio;
      x = 0;
      y = (ch - drawHeight) / 2;
    } else {
      drawWidth = ch * videoRatio;
      drawHeight = ch;
      x = (cw - drawWidth) / 2;
      y = 0;
    }
    ctx.drawImage(video, x, y, drawWidth, drawHeight);
  }

  // Draw image using letterbox/pillarbox fitting
  function drawImageFit(img, canvas, ctx) {
    const cw = canvas.width;
    const ch = canvas.height;
    const iw = img.naturalWidth || img.width;
    const ih = img.naturalHeight || img.height;
    
    // Clear / Fill black
    ctx.fillStyle = '#000000';
    ctx.fillRect(0, 0, cw, ch);
    
    const canvasRatio = cw / ch;
    const imageRatio = iw / ih;
    
    let drawWidth, drawHeight, x, y;
    if (imageRatio > canvasRatio) {
      drawWidth = cw;
      drawHeight = cw / imageRatio;
      x = 0;
      y = (ch - drawHeight) / 2;
    } else {
      drawWidth = ch * imageRatio;
      drawHeight = ch;
      x = (cw - drawWidth) / 2;
      y = 0;
    }
    ctx.drawImage(img, x, y, drawWidth, drawHeight);
  }

  // Save orientation and duration to session/DB asynchronously
  async function saveVideoOrientationAndDuration(session) {
    const meta = { 
      is_reel: session.isReel,
      duration: session.duration
    };
    if (session.videoId) {
      meta.video_id = session.videoId;
    } else if (session.sessionId) {
      meta.session_id = session.sessionId;
    } else {
      return;
    }
    try {
      await fetch('<?= BASE_URL ?>/api/videos.php?action=save_metadata', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ meta })
      });
    } catch (e) {}
  }

  // Queue Restoration initialization from IndexedDB
  async function initQueue() {
    try {
      const stored = await UploadDB.getAll();
      if (stored && stored.length > 0) {
        document.getElementById('welcome-dropzone').style.display = 'none';
        document.getElementById('top-dropzone').style.display = 'block';
        document.getElementById('studio-dashboard').style.display = 'block';

        for (const item of stored) {
          const session = {
            id: item.id,
            isEmbed: item.isEmbed,
            embedUrl: item.embedUrl,
            file: item.file,
            title: item.title,
            description: item.description,
            tags: item.tags,
            visibility: item.visibility,
            categoryIds: item.categoryIds,
            playlistIds: item.playlistIds,
            progress: item.progress || 0,
            speed: item.speed || 0,
            eta: item.eta || null,
            status: item.status,
            sessionId: item.sessionId,
            videoId: item.videoId,
            token: item.token,
            uploadedBytes: item.uploadedBytes || 0,
            videoUrl: item.videoUrl || null,
            isReel: item.isReel || 0,
            thumbnails: item.thumbnails || [],
            selectedThumbDataUrl: item.selectedThumbDataUrl || null,
            retries: item.retries || 0,
            createdAt: item.createdAt || Date.now(),
            abortController: new AbortController(),
            activeLoopRunning: false
          };

          if (!session.isEmbed && session.file) {
            session.localBlobUrl = URL.createObjectURL(session.file);
          }

          if (session.status === 'uploading' || session.status === 'retrying') {
            session.status = 'queued';
          }

          uploads[session.id] = session;
          renderQueueCard(session);
          
          if (session.selectedThumbDataUrl) {
            const miniThumb = document.getElementById(`thumb_${session.id}`);
            if (miniThumb) {
              miniThumb.innerHTML = `<img src="${session.selectedThumbDataUrl}" style="width:100%;height:100%;object-fit:contain;background:#000">`;
            }
          }
        }
        updateQueueCount();

        const list = Object.keys(uploads);
        if (list.length > 0) {
          selectUpload(list[0]);
        }

        refreshQueueUI();
        processQueue();
      }
    } catch (err) {
      console.error('Failed to initialize queue:', err);
    }
  }

  // Handle connection events
  window.addEventListener('online', () => {
    Object.values(uploads).forEach(session => {
      if (session.status === 'retrying' || session.status === 'failed') {
        session.status = 'queued';
        session.retries = 0;
        updateCardProgress(session, 'Reconnected. Resuming...', 'uploading');
      }
    });
    processQueue();
  });

  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
      processQueue();
    }
  });

  // Utilities
  function getYoutubeId(url) {
    if (!url) return null;
    if (url.match(/^[a-zA-Z0-9_-]{11}$/)) return url;
    const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=|shorts\/)([^#\&\?]*).*/;
    const match = url.match(regExp);
    return (match && match[2].length === 11) ? match[2] : null;
  }

  function formatTime(s) {
    s = Math.floor(s);
    return Math.floor(s/60) + ':' + String(s%60).padStart(2,'0');
  }

  function formatBytes(n) {
    if (n >= 1073741824) return (n/1073741824).toFixed(2) + ' GB';
    if (n >= 1048576) return (n/1048576).toFixed(2) + ' MB';
    if (n >= 1024) return (n/1024).toFixed(2) + ' KB';
    return n + ' B';
  }

  function formatETA(s) {
    if (s < 60) return s + 's';
    const m = Math.floor(s/60); const sec = s%60;
    return m + 'm ' + sec + 's';
  }

  function escapeHtml(str) {
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
  }

  // Run queue initialization
  initQueue();

})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
