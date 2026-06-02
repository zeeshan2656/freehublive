<?php
// ============================================================
// FreeHub.Live — Modern Background Video Upload System (SPA)
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin']);

$site_theme = setting('active_theme', 'dark-minimal');
$primary    = setting('primary_color', '#6366f1');

$uid     = auth_user()['id'];
$error   = '';
$success = '';

$categories = db_fetchAll("SELECT * FROM categories WHERE is_active=1 ORDER BY sort_order");
$user_playlists = db_fetchAll("SELECT id, title FROM playlists WHERE user_id = ? ORDER BY title ASC", [$uid]);
$meta_title = 'Upload Video';
require_once __DIR__ . '/../includes/header.php';
?>
<script>document.body.classList.add('upload-single-screen');</script>

<style>
/* ── YouTube Style Background Upload Redesign ── */
.upload-wizard {
  max-width: 1100px;
  margin: 0 auto;
  padding: 16px 8px 48px;
}

/* Steps progress indicator */
.step-progress-bar {
  display: flex;
  justify-content: space-between;
  margin-bottom: 24px;
  background: var(--bg2);
  padding: 16px 24px;
  border-radius: 12px;
  border: 1px solid var(--border);
}
.wizard-step {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
}
.wizard-step:last-child { flex: none; }
.step-icon {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--bg3);
  border: 2px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  font-weight: 800;
  color: var(--text2);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.wizard-step.active .step-icon {
  background: var(--accent);
  border-color: var(--accent);
  color: #fff;
  box-shadow: 0 0 14px rgba(99, 102, 241, 0.45);
}
.wizard-step.done .step-icon {
  background: var(--green);
  border-color: var(--green);
  color: #fff;
}
.step-title { font-size: 0.85rem; font-weight: 700; color: var(--text2); }
.wizard-step.active .step-title { color: var(--text); }
.step-connector { flex: 1; height: 2px; background: var(--border); margin: 0 16px; }
.wizard-step.done .step-connector { background: var(--green); }

/* Main Dropzone */
.upload-dropzone {
  border: 2.5px dashed var(--border);
  border-radius: 20px;
  padding: 80px 24px;
  text-align: center;
  cursor: pointer;
  background: var(--bg2);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 8px 30px rgba(0,0,0,0.12);
  margin-bottom: 24px;
}
.upload-dropzone:hover, .upload-dropzone.drag-over {
  border-color: var(--accent);
  background: rgba(99, 102, 241, 0.04);
  box-shadow: 0 12px 40px rgba(99, 102, 241, 0.08);
}
.upload-dropzone svg {
  width: 64px;
  height: 64px;
  color: var(--text3);
  margin-bottom: 20px;
  transition: transform 0.3s, color 0.3s;
}
.upload-dropzone:hover svg {
  transform: translateY(-6px);
  color: var(--accent);
}
.upload-title {
  font-family: var(--font2);
  font-size: 1.25rem;
  font-weight: 800;
  margin-bottom: 8px;
}
.upload-sub {
  font-size: 0.85rem;
  color: var(--text2);
}

/* Two Column Layout */
.wizard-layout-cols {
  display: grid;
  grid-template-columns: 1.6fr 1fr;
  gap: 24px;
  align-items: start;
}
.wizard-main-panel {
  background: var(--bg2);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}
.wizard-preview-panel {
  display: flex;
  flex-direction: column;
  gap: 20px;
  position: sticky;
  top: 80px;
}

/* Details Section labels */
.wizard-section-lbl {
  font-family: var(--font2);
  font-size: 0.85rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--text2);
  margin: 24px 0 16px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.wizard-section-lbl::after {
  content: '';
  flex: 1;
  height: 1px;
  background: var(--border);
}

/* Preview Card */
.preview-card {
  background: var(--bg2);
  border: 1px solid var(--border);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 8px 30px rgba(0,0,0,0.18);
}
.preview-aspect-ratio {
  position: relative;
  aspect-ratio: 16/9;
  background: #000;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text3);
}
.preview-details { padding: 16px; }
.preview-title {
  font-size: 0.95rem;
  font-weight: 700;
  line-height: 1.4;
  margin-bottom: 6px;
  word-break: break-word;
  color: #fff;
}
.preview-meta {
  font-size: 0.8rem;
  color: var(--text2);
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Thumbnails Grid */
.thumbnail-box {
  background: var(--bg3);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 24px;
}
.thumbnail-header {
  font-weight: 700;
  font-size: 0.92rem;
  margin-bottom: 4px;
  color: #fff;
}
.thumbnail-sub {
  font-size: 0.8rem;
  color: var(--text2);
  margin-bottom: 16px;
  line-height: 1.4;
}
.thumb-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
}
.thumb-option {
  position: relative;
  aspect-ratio: 16/9;
  border-radius: 8px;
  overflow: hidden;
  border: 2px solid transparent;
  cursor: pointer;
  background: #000;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.thumb-option:hover {
  transform: scale(1.03);
  border-color: var(--text3);
}
.thumb-option.selected {
  border-color: var(--accent);
  box-shadow: 0 0 14px rgba(99, 102, 241, 0.45);
}
.thumb-option img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.thumb-option .check-badge {
  position: absolute;
  top: 6px;
  right: 6px;
  width: 18px;
  height: 18px;
  background: var(--accent);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transform: scale(0.7);
  transition: all 0.2s ease;
}
.thumb-option.selected .check-badge {
  opacity: 1;
  transform: scale(1);
}
.custom-thumb-trigger {
  aspect-ratio: 16/9;
  background: rgba(255,255,255,0.02);
  border: 1.5px dashed var(--border);
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--text2);
  cursor: pointer;
  transition: all 0.2s;
  gap: 6px;
}
.custom-thumb-trigger:hover {
  border-color: var(--accent);
  background: rgba(99, 102, 241, 0.05);
  color: #fff;
}
.thumb-loading-placeholder {
  aspect-ratio: 16/9;
  background: rgba(0,0,0,0.3);
  border-radius: 8px;
  border: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.7rem;
  color: var(--text3);
}

/* Diagnostics Upload Progress Widget */
.upload-diagnostics-card {
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(139, 92, 246, 0.04) 100%);
  border: 1.5px solid var(--accent);
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 8px 32px rgba(99, 102, 241, 0.1);
}
.progress-bar-wrapper {
  height: 8px;
  background: var(--bg3);
  border-radius: 6px;
  overflow: hidden;
  margin: 10px 0;
}
.progress-bar-fill {
  height: 100%;
  width: 0%;
  background: linear-gradient(90deg, var(--accent), #a855f7);
  transition: width 0.3s ease;
  border-radius: 6px;
}
.diagnostics-meta {
  display: flex;
  justify-content: space-between;
  font-size: 0.78rem;
  color: var(--text2);
  margin-top: 4px;
}

/* Success Card */
.success-screen-card {
  max-width: 620px;
  margin: 40px auto;
  background: var(--bg2);
  border: 1px solid var(--border);
  border-radius: 20px;
  padding: 40px;
  text-align: center;
  box-shadow: 0 12px 50px rgba(0,0,0,0.22);
}
.success-icon {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: rgba(34, 197, 94, 0.1);
  color: var(--green);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.2rem;
  margin: 0 auto 24px;
  border: 2px solid rgba(34, 197, 94, 0.2);
}
.success-title {
  font-family: var(--font2);
  font-size: 1.6rem;
  font-weight: 900;
  color: #fff;
  margin-bottom: 8px;
}
.success-subtitle {
  font-size: 0.95rem;
  color: var(--text2);
  line-height: 1.5;
  margin-bottom: 24px;
}

/* Layout switches */
#details-view, #success-view { display: none; }

/* Segmented Tab Selector styling */
.upload-tab-header {
  display: flex;
  background: var(--bg3);
  padding: 4px;
  border-radius: 10px;
  border: 1px solid var(--border);
  gap: 4px;
}
.upload-tab-btn {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 10px 16px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.85rem;
  color: var(--text2);
  background: transparent;
  transition: all 0.2s ease;
  cursor: pointer;
  border: none;
}
.upload-tab-btn:hover {
  color: var(--text);
  background: rgba(255, 255, 255, 0.02);
}
.upload-tab-btn.active {
  background: var(--bg2);
  color: var(--accent);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

/* Responsive design */
@media (max-width: 900px) {
  .wizard-layout-cols {
    grid-template-columns: 1fr;
    gap: 20px;
  }
  .wizard-preview-panel {
    position: static;
  }
  .wizard-main-panel {
    padding: 16px;
  }
}

@media (max-width: 768px) {
  .upload-wizard {
    padding: 8px 0px 32px;
  }
  .upload-tab-header {
    flex-direction: column;
    background: transparent;
    border: none;
    padding: 0;
    gap: 8px;
  }
  .upload-tab-btn {
    width: 100%;
    background: var(--bg2);
    border: 1px solid var(--border);
    border-radius: 8px;
  }
  .upload-tab-btn.active {
    background: var(--accent);
    color: #fff;
    border-color: var(--accent);
  }
  .upload-dropzone {
    padding: 48px 16px;
  }
  .upload-title {
    font-size: 1.1rem;
  }
}

@media (max-width: 600px) {
  .step-progress-bar {
    flex-direction: column;
    gap: 12px;
    padding: 12px;
  }
  .step-connector {
    display: none;
  }
  .wizard-step {
    width: 100%;
  }
  .thumb-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
  }
  .success-screen-card {
    padding: 24px 16px;
    margin: 20px auto;
  }
  .success-title {
    font-size: 1.3rem;
  }
}
</style>

<div class="upload-wizard">

  <!-- Step progress indicator -->
  <div class="step-progress-bar">
    <div class="wizard-step active" id="step1-indicator">
      <div class="step-icon">1</div>
      <div class="step-title">Select &amp; Import</div>
      <div class="step-connector"></div>
    </div>
    <div class="wizard-step" id="step2-indicator">
      <div class="step-icon">2</div>
      <div class="step-title">Details &amp; Metadata</div>
      <div class="step-connector"></div>
    </div>
    <div class="wizard-step" id="step3-indicator">
      <div class="step-icon">3</div>
      <div class="step-title">Complete</div>
    </div>
  </div>

  <!-- ── VIEW 1: Dropzone ── -->
  <div id="dropzone-view" class="fade-in">
    <!-- Tab selector -->
    <div class="upload-tab-header" style="margin-bottom:24px">
      <button type="button" class="upload-tab-btn active" id="tab-file-btn" onclick="switchSourceType('file')">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        Upload Video File
      </button>
      <button type="button" class="upload-tab-btn" id="tab-embed-btn" onclick="switchSourceType('embed')">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
        Embed Link (YouTube / Direct URL)
      </button>
      <?php if (setting('reels_enabled', '1') === '1'): ?>
      <a href="<?= BASE_URL ?>/creator/upload_reel.php" class="upload-tab-btn" style="text-decoration:none">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
        Upload Reel
      </a>
      <?php endif; ?>
    </div>

    <!-- Dropzone File block -->
    <div id="file-drop-container">
      <div class="upload-dropzone" id="drop-zone" onclick="document.getElementById('video-file-input').click()">
        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        <h2 class="upload-title">Drag and drop video files to upload</h2>
        <p class="upload-sub">Your videos will be members-only private until published.</p>
        <button class="btn btn-primary" style="margin-top:20px; font-weight:700">Select File</button>
      </div>
      <input type="file" id="video-file-input" accept="video/mp4,video/webm,video/quicktime" style="display:none">
    </div>

    <!-- Embed input block -->
    <div id="embed-url-container" style="display:none">
      <div class="wizard-main-panel" style="padding:40px; text-align:center">
        <div style="max-width:580px; margin:0 auto">
          <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color:var(--text3); margin-bottom:16px"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
          <h2 class="upload-title" style="margin-bottom:8px">Import Video from External Source</h2>
          <p class="upload-sub" style="margin-bottom:24px">Paste a YouTube video URL or a direct link ending in .mp4 / .webm</p>
          <div style="display:flex; gap:10px">
            <input class="form-input" type="url" id="embed-link-field" placeholder="https://www.youtube.com/watch?v=..." style="border-radius:8px">
            <button type="button" class="btn btn-primary" onclick="verifyAndLoadEmbed()" style="border-radius:8px; font-weight:700">Verify &amp; Continue</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── VIEW 2: SPA Details Wizard ── -->
  <div id="details-view" class="fade-in">
    <form id="spa-upload-form" onsubmit="submitDetailsForm(event)">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      
      <div class="wizard-layout-cols">
        
        <!-- Left details panel -->
        <div class="wizard-main-panel">
          <div class="wizard-section-lbl" style="margin-top:0">Video Details</div>
          
          <div class="form-group">
            <label class="form-label">Title *</label>
            <input class="form-input" type="text" id="details-title" name="title" required placeholder="Catchy video title" style="border-radius:8px">
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
          <div class="thumbnail-box">
            <div class="thumbnail-header">Thumbnail Selector</div>
            <div class="thumbnail-sub">Select one of our instantly extracted frames or upload a custom image.</div>
            
            <div class="thumb-grid" id="details-thumb-grid">
              <!-- Custom upload card -->
              <div class="custom-thumb-trigger" onclick="document.getElementById('spa-custom-thumb').click()">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                <span>Upload Custom</span>
              </div>
              <input type="file" id="spa-custom-thumb" accept="image/jpeg,image/png,image/webp" style="display:none">
              
              <!-- Frame placeholders -->
              <?php for($i=0; $i<7; $i++): ?>
              <div class="thumb-loading-placeholder" id="placeholder-<?= $i ?>">
                <span>Frame..</span>
              </div>
              <?php endfor; ?>
            </div>
          </div>

          <!-- Metas row -->
          <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px">
            <div class="form-group">
              <label class="form-label">Categories (Select one or more)</label>
              <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(130px, 1fr)); gap:8px; background:var(--bg3); padding:10px; border-radius:8px; border:1px solid var(--border); max-height:120px; overflow-y:auto">
                <?php foreach ($categories as $c): ?>
                <label class="flex gap-2" style="font-size:.8rem; cursor:pointer; user-select:none; align-items:center">
                  <input type="checkbox" name="category_ids[]" value="<?= $c['id'] ?>">
                  <span><?= e($c['name']) ?></span>
                </label>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Playlists</label>
              <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(130px, 1fr)); gap:8px; background:var(--bg3); padding:10px; border-radius:8px; border:1px solid var(--border); max-height:120px; overflow-y:auto">
                <?php if (!empty($user_playlists)): ?>
                  <?php foreach ($user_playlists as $pl): ?>
                  <label class="flex gap-2" style="font-size:.8rem; cursor:pointer; user-select:none; align-items:center">
                    <input type="checkbox" name="playlist_ids[]" value="<?= $pl['id'] ?>">
                    <span><?= e($pl['title']) ?></span>
                  </label>
                  <?php endforeach; ?>
                <?php else: ?>
                  <span class="text-muted text-xs" style="padding:4px">No playlists available.</span>
                <?php endif; ?>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Visibility</label>
              <select class="form-input form-select" id="details-visibility" name="visibility" style="border-radius: 8px">
                <option value="public">Public</option>
                <option value="unlisted" selected>Unlisted</option>
                <option value="private">Private</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Right sticky preview & diagnostics panel -->
        <div class="wizard-preview-panel">
          <!-- Video Card -->
          <div class="preview-card">
            <div class="preview-aspect-ratio" id="details-video-preview">
              <video id="spa-player" style="width:100%; height:100%; object-fit:cover" controls></video>
              <iframe id="spa-iframe-player" style="display:none; width:100%; height:100%; border:none" allowfullscreen></iframe>
            </div>
            <div class="preview-details">
              <div class="preview-title" id="details-preview-title">Video Title Preview</div>
              <div class="preview-meta">
                <span><?= e(auth_user()['channel_name'] ?? auth_user()['username']) ?></span>
                <span>·</span>
                <span id="details-preview-duration">0:00</span>
              </div>
            </div>
          </div>

          <!-- Diagnostics Upload Progress widget -->
          <div class="upload-diagnostics-card" id="spa-diagnostics-widget">
            <div style="display:flex; justify-content:space-between; align-items:center">
              <div style="font-size:0.88rem; font-weight:800; color:var(--accent); display:flex; align-items:center; gap:8px" id="spa-status-text">
                <span style="animation: spin 1.5s linear infinite; display:inline-block" id="spa-status-spinner">🔄</span>
                Initializing upload...
              </div>
              <div style="font-size:0.85rem; font-weight:800; color:#fff" id="spa-percentage-text">0%</div>
            </div>
            
            <div class="progress-bar-wrapper">
              <div class="progress-bar-fill" id="spa-progress-fill"></div>
            </div>
            
            <div class="diagnostics-meta">
              <div>Speed: <span id="spa-speed-text">—</span></div>
              <div>ETA: <span id="spa-eta-text">—</span></div>
            </div>

            <!-- Retry button (hidden by default) -->
            <button type="button" class="btn btn-outline btn-sm w-full" id="spa-retry-btn" style="margin-top:12px; display:none; justify-content:center; gap:6px; color:var(--yellow); border-color:rgba(245,158,11,0.3)">
              🔁 Resume Interrupted Upload
            </button>
          </div>

          <button type="submit" class="btn btn-primary w-full" id="spa-submit-btn" style="justify-content:center; padding:14px; border-radius:12px; font-weight:800; box-shadow:0 4px 14px rgba(99,102,241,0.25)">
            🚀 Publish &amp; Finish
          </button>
          <div style="text-align:center; font-size:0.75rem; color:var(--text3); font-weight:500">
            Upload continues in background — details will update instantly
          </div>
        </div>

      </div>
    </form>
  </div>

  <!-- ── VIEW 3: Success Completion Page ── -->
  <div id="success-view" class="fade-in">
    <div class="success-screen-card">
      <div class="success-icon">&#10003;</div>
      <h2 class="success-title">Video Published Successfully!</h2>
      <p class="success-subtitle" id="success-subtitle-text">
        Your video metadata and custom thumbnail have been securely saved. The background uploader is finalizing details.
      </p>

      <div class="preview-card" style="max-width:320px; margin:0 auto 30px">
        <div class="preview-aspect-ratio">
          <img id="success-thumb-preview" src="" style="width:100%; height:100%; object-fit:cover">
        </div>
        <div class="preview-details" style="text-align:left">
          <div class="preview-title" id="success-video-title" style="font-size:0.88rem; margin:0">Video Title</div>
        </div>
      </div>

      <!-- Keep uploader progress card in the success screen if it hasn't finalized! -->
      <div class="upload-diagnostics-card" id="success-diagnostics-card" style="text-align:left; margin-bottom:30px">
        <div style="display:flex; justify-content:space-between; align-items:center">
          <div style="font-size:0.82rem; font-weight:800; color:var(--accent)" id="success-status-lbl">Background Finalizing...</div>
          <div style="font-size:0.82rem; font-weight:800; color:#fff" id="success-percent-lbl">100%</div>
        </div>
        <div class="progress-bar-wrapper">
          <div class="progress-bar-fill" id="success-progress-fill" style="width:100%"></div>
        </div>
      </div>

      <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap">
        <a href="<?= BASE_URL ?>/creator/videos.php" class="btn btn-primary" style="font-weight:700; border-radius:8px">
          📂 Go to Videos
        </a>
        <a href="<?= BASE_URL ?>/channel.php?id=<?= $uid ?>" target="_blank" class="btn btn-outline" style="font-weight:600; border-radius:8px">
          📺 View Channel
        </a>
        <button type="button" class="btn btn-outline" onclick="resetUploadWizard()" style="font-weight:600; border-radius:8px">
          ➕ Upload Another
        </button>
      </div>
    </div>
  </div>

</div>

<!-- Hidden Canvas for local frame extraction -->
<canvas id="offscreen-canvas" style="display:none"></canvas>

<script data-page-script="true">
(function() {
  // Global Upload States
  let activeFile = null;
  let uploadVideoId = null;
  let uploadToken = null;
  let selectedThumbDataUrl = null;
  let isUploading = false;
  let uploadProgress = 0;
  
  // DOM Elements
  const dropZone = document.getElementById('drop-zone');
  const fileInput = document.getElementById('video-file-input');
  
  const step1Indicator = document.getElementById('step1-indicator');
  const step2Indicator = document.getElementById('step2-indicator');
  const step3Indicator = document.getElementById('step3-indicator');
  
  const dropzoneView = document.getElementById('dropzone-view');
  const detailsView = document.getElementById('details-view');
  const successView = document.getElementById('success-view');
  
  const detailsTitle = document.getElementById('details-title');
  const previewTitle = document.getElementById('details-preview-title');
  const previewDuration = document.getElementById('details-preview-duration');
  
  const spaPlayer = document.getElementById('spa-player');
  const spaIframePlayer = document.getElementById('spa-iframe-player');
  const detailsThumbGrid = document.getElementById('details-thumb-grid');
  
  const statusText = document.getElementById('spa-status-text');
  const statusSpinner = document.getElementById('spa-status-spinner');
  const percentageText = document.getElementById('spa-percentage-text');
  const progressFill = document.getElementById('spa-progress-fill');
  const speedText = document.getElementById('spa-speed-text');
  const etaText = document.getElementById('spa-eta-text');
  const retryBtn = document.getElementById('spa-retry-btn');
  const submitBtn = document.getElementById('spa-submit-btn');

  // Prevent Navigation Warning
  window.addEventListener('beforeunload', function(e) {
    if (isUploading) {
      e.preventDefault();
      e.returnValue = 'Your video upload is currently in progress. Leaving this page will cancel the upload. Are you sure you want to exit?';
      return e.returnValue;
    }
  });

  // real-time preview title sync
  detailsTitle.addEventListener('input', () => {
    previewTitle.textContent = detailsTitle.value.trim() || 'Video Title Preview';
  });

  // Drag & drop handlers
  if (dropZone && fileInput) {
    ['dragover','dragenter'].forEach(e => dropZone.addEventListener(e, ev => { ev.preventDefault(); dropZone.classList.add('drag-over'); }));
    ['dragleave','drop'].forEach(e => dropZone.addEventListener(e, ev => { ev.preventDefault(); dropZone.classList.remove('drag-over'); }));
    dropZone.addEventListener('drop', e => { if(e.dataTransfer.files[0]) handleSelectedFile(e.dataTransfer.files[0]); });
    fileInput.addEventListener('change', () => { if(fileInput.files[0]) handleSelectedFile(fileInput.files[0]); });
  }

  // Switch tabs
  window.switchSourceType = function(type) {
    document.querySelectorAll('.upload-tab-btn').forEach(btn => btn.classList.remove('active'));
    if (type === 'file') {
      document.getElementById('tab-file-btn').classList.add('active');
      document.getElementById('file-drop-container').style.display = 'block';
      document.getElementById('embed-url-container').style.display = 'none';
    } else {
      document.getElementById('tab-embed-btn').classList.add('active');
      document.getElementById('file-drop-container').style.display = 'none';
      document.getElementById('embed-url-container').style.display = 'block';
    }
  };

  // Main Handler once local file is selected
  function handleSelectedFile(file) {
    activeFile = file;
    isUploading = true;
    
    // Auto-fill Title immediately
    const nameWithoutExt = file.name.substring(0, file.name.lastIndexOf('.')) || file.name;
    detailsTitle.value = nameWithoutExt.substring(0, 100);
    previewTitle.textContent = detailsTitle.value;
    
    // Transition wizard panels instantly (SPA)
    dropzoneView.style.display = 'none';
    detailsView.style.display = 'block';
    
    step1Indicator.classList.add('done');
    step1Indicator.classList.remove('active');
    step2Indicator.classList.add('active');
    
    // Render local video playing in player
    const localBlobUrl = URL.createObjectURL(file);
    spaPlayer.src = localBlobUrl;
    spaPlayer.style.display = 'block';
    spaIframePlayer.style.display = 'none';
    
    // Listen for duration
    spaPlayer.addEventListener('loadedmetadata', () => {
      const d = Math.max(1, Math.floor(spaPlayer.duration || 0));
      previewDuration.textContent = formatTime(d);
      
      // Auto-extract client-side thumbnails instantly!
      generateClientThumbnails(file, d);
    }, {once: true});

    // Start background progressive chunk upload loop (non-blocking)
    startProgressiveChunkUpload(file);
  }

  // Auto-generate client-side thumbnails instantly
  async function generateClientThumbnails(file, duration) {
    const canvas = document.getElementById('offscreen-canvas');
    canvas.width = 640;
    canvas.height = 360;
    const ctx = canvas.getContext('2d');
    
    const count = 7;
    const step = duration / (count + 1);
    const times = Array.from({length: count}, (_, i) => step * (i + 1));
    
    // Clear frames container and show dynamic frames loading
    const grid = document.getElementById('details-thumb-grid');
    // keep custom upload element
    const customTrigger = grid.querySelector('.custom-thumb-trigger');
    grid.innerHTML = '';
    grid.appendChild(customTrigger);
    
    // Load a helper video element
    const helperVideo = document.createElement('video');
    helperVideo.preload = 'auto';
    helperVideo.src = URL.createObjectURL(file);
    helperVideo.muted = true;
    helperVideo.playsInline = true;
    
    helperVideo.addEventListener('loadedmetadata', async () => {
      for (let i = 0; i < count; i++) {
        // create temporary placeholder item
        const placeholder = document.createElement('div');
        placeholder.className = 'thumb-loading-placeholder';
        placeholder.textContent = 'Frame..';
        grid.appendChild(placeholder);
        
        const time = times[i];
        const dataUrl = await seekAndCapture(helperVideo, canvas, ctx, time);
        
        // replace placeholder with clickable thumbnail option
        placeholder.className = 'thumb-option';
        placeholder.textContent = '';
        placeholder.innerHTML = `
          <img src="${dataUrl}">
          <div class="check-badge"><svg width="10" height="10" fill="#fff" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
          <div style="position:absolute;bottom:3px;left:6px;font-size:0.6rem;color:#fff;text-shadow:0 1px 2px #000">${formatTime(time)}</div>
        `;
        
        placeholder.addEventListener('click', () => {
          grid.querySelectorAll('.thumb-option').forEach(o => o.classList.remove('selected'));
          placeholder.classList.add('selected');
          selectedThumbDataUrl = dataUrl;
        });
        
        // Auto-select the first generated frame as default thumbnail!
        if (i === 0) {
          placeholder.click();
        }
      }
      
      // Clean up helper Blob
      URL.revokeObjectURL(helperVideo.src);
    }, {once: true});
  }

  function seekAndCapture(video, canvas, ctx, time) {
    return new Promise(resolve => {
      video.currentTime = time;
      video.addEventListener('seeked', function onSeeked() {
        video.removeEventListener('seeked', onSeeked);
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        resolve(canvas.toDataURL('image/jpeg', 0.8));
      }, {once: true});
    });
  }

  // Handle Custom Thumbnail Select File and Draw
  const customThumbInput = document.getElementById('spa-custom-thumb');
  customThumbInput.addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
      const img = new Image();
      img.src = e.target.result;
      img.onload = () => {
        const canvas = document.createElement('canvas');
        canvas.width = 1280;
        canvas.height = 720;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0, 1280, 720);
        const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
        
        selectedThumbDataUrl = dataUrl;
        
        // Create custom thumbnail card and append to grid
        const grid = document.getElementById('details-thumb-grid');
        const customOpt = document.createElement('div');
        customOpt.className = 'thumb-option selected';
        customOpt.innerHTML = `
          <img src="${dataUrl}">
          <div class="check-badge"><svg width="10" height="10" fill="#fff" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
          <div style="position:absolute;bottom:3px;left:6px;font-size:0.6rem;color:#fff;text-shadow:0 1px 2px #000">Uploaded</div>
        `;
        grid.querySelectorAll('.thumb-option').forEach(o => o.classList.remove('selected'));
        customOpt.addEventListener('click', () => {
          grid.querySelectorAll('.thumb-option').forEach(o => o.classList.remove('selected'));
          customOpt.classList.add('selected');
          selectedThumbDataUrl = dataUrl;
        });
        grid.appendChild(customOpt);
      };
    };
    reader.readAsDataURL(file);
  });

  // Start Background Resumable Progressive Chunk Upload (Fetch & AJAX)
  async function startProgressiveChunkUpload(file) {
    try {
      submitBtn.disabled = true;
      submitBtn.style.opacity = '0.65';
      statusText.innerHTML = `<span style="animation: spin 1.5s linear infinite; display:inline-block">🔄</span> Initializing background upload...`;
      
      const meta = {
        title: detailsTitle.value || 'Untitled Upload',
        description: document.getElementById('details-desc').value || '',
        tags: document.getElementById('details-tags').value || '',
        category_ids: Array.from(document.querySelectorAll('input[name="category_ids[]"]:checked')).map(i=>i.value),
        visibility: document.getElementById('details-visibility').value || 'unlisted'
      };

      // 1. Initialize Upload Placeholder Record
      const initRes = await fetch('<?= BASE_URL ?>/api/videos.php?action=init_upload', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({meta})
      });
      const initData = await initRes.json();
      if (!initData.success) {
        statusText.innerHTML = `✗ Initialization failed.`;
        isUploading = false;
        return;
      }

      uploadVideoId = initData.data.video_id;
      uploadToken = initData.data.upload_token;
      
      // Enable Submit Button Immediately - User can complete metadata save and continue working!
      submitBtn.disabled = false;
      submitBtn.style.opacity = '1';
      
      // 2. Perform Resumable progressive Chunk Upload Loop
      const CHUNK_SIZE = 5 * 1024 * 1024; // 5MB chunks
      const totalSize = file.size;
      let uploadedBytes = 0;
      
      // status check (resumability)
      try {
        const checkRes = await fetch(`<?= BASE_URL ?>/api/upload.php?action=status&video_id=${uploadVideoId}&token=${uploadToken}`);
        const checkData = await checkRes.json();
        if (checkData.success && checkData.data.uploaded) {
          uploadedBytes = checkData.data.uploaded;
        }
      } catch (e) {}

      let lastTime = Date.now();
      let lastBytes = uploadedBytes;

      statusText.innerHTML = `<span style="animation: spin 1.5s linear infinite; display:inline-block">📤</span> Uploading chunks...`;
      
      // Define resilient chunk loop
      for (let start = uploadedBytes; start < totalSize; start += CHUNK_SIZE) {
        if (!isUploading) break; // cancelled
        
        const end = Math.min(start + CHUNK_SIZE, totalSize);
        const chunkBlob = file.slice(start, end);
        const formData = new FormData();
        formData.append('chunk', chunkBlob, file.name);

        let attempt = 0;
        let success = false;
        
        while (attempt < 5 && !success) {
          try {
            const uploadRes = await fetch(`<?= BASE_URL ?>/api/upload.php?video_id=${uploadVideoId}&token=${uploadToken}`, {
              method: 'POST',
              body: formData
            });
            const uploadData = await uploadRes.json();
            if (uploadData.success) {
              success = true;
              uploadedBytes = uploadData.data.uploaded || end;
            } else {
              attempt++;
              await new Promise(r => setTimeout(r, 1500));
            }
          } catch (e) {
            attempt++;
            statusText.innerHTML = `⚠️ Connection flicker — retrying chunk (${attempt}/5)...`;
            await new Promise(r => setTimeout(r, 2000));
          }
        }

        if (!success) {
          isUploading = false;
          statusText.innerHTML = `❌ Upload failed.`;
          retryBtn.style.display = 'flex';
          retryBtn.onclick = () => {
            retryBtn.style.display = 'none';
            isUploading = true;
            startProgressiveChunkUpload(file);
          };
          return;
        }

        // Calculate Speeds & ETAs
        const now = Date.now();
        const delta = (now - lastTime) / 1000;
        const deltaBytes = uploadedBytes - lastBytes;
        const speed = delta > 0 ? Math.round(deltaBytes / delta) : 0;
        lastTime = now;
        lastBytes = uploadedBytes;

        const pct = Math.floor((uploadedBytes / totalSize) * 100);
        uploadProgress = pct;
        
        // Update diagnostics text
        percentageText.textContent = pct + '%';
        progressFill.style.width = pct + '%';
        
        // Success screen diagnostic update
        const successPercentLbl = document.getElementById('success-percent-lbl');
        const successProgressFill = document.getElementById('success-progress-fill');
        if (successPercentLbl) successPercentLbl.textContent = pct + '%';
        if (successProgressFill) successProgressFill.style.width = pct + '%';

        speedText.textContent = speed > 0 ? formatBytes(speed) + '/s' : '—';
        
        const remainSeconds = speed > 0 ? Math.max(0, Math.round((totalSize - uploadedBytes) / speed)) : null;
        etaText.textContent = remainSeconds !== null ? formatETA(remainSeconds) : '—';
      }

      if (uploadedBytes >= totalSize) {
        statusText.innerHTML = `⏳ Finalizing upload...`;
        
        const successStatusLbl = document.getElementById('success-status-lbl');
        if (successStatusLbl) successStatusLbl.textContent = 'Finalizing background file...';

        // Finalize chunk merger on the server
        const finalizeRes = await fetch(`<?= BASE_URL ?>/api/upload.php?video_id=${uploadVideoId}&token=${uploadToken}&finalize=1&filename=${encodeURIComponent(file.name)}`, {
          method: 'POST'
        });
        const finalizeData = await finalizeRes.json();
        
        if (finalizeData.success) {
          isUploading = false;
          statusText.innerHTML = `✅ Complete`;
          statusSpinner.textContent = '✓';
          percentageText.textContent = '100%';
          progressFill.style.width = '100%';
          
          if (successStatusLbl) successStatusLbl.textContent = 'Upload Completed & Processing Finished!';
          
          // Save duration
          const d = Math.max(1, Math.floor(spaPlayer.duration || 0));
          if (d > 0) {
            await fetch('<?= BASE_URL ?>/api/thumbnails.php?action=save_duration', {
              method: 'POST',
              headers: {'Content-Type': 'application/json'},
              body: JSON.stringify({video_id: uploadVideoId, duration: d})
            });
          }
          
          // Hide progress card on success screen once complete
          const successDiagCard = document.getElementById('success-diagnostics-card');
          if (successDiagCard) successDiagCard.style.display = 'none';
        } else {
          statusText.textContent = `✗ Finalization failed.`;
          isUploading = false;
        }
      }

    } catch (e) {
      console.error(e);
      isUploading = false;
      statusText.innerHTML = `❌ Server connection failed.`;
    }
  }

  // Verify and load External embeds (YouTube / direct links)
  window.verifyAndLoadEmbed = async function() {
    let urlVal = document.getElementById('embed-link-field').value.trim();
    if (!urlVal) {
      alert('Please enter a video URL.');
      return;
    }

    if (urlVal.includes('<iframe')) {
      const match = urlVal.match(/src=["']([^"']+)["']/i);
      if (match && match[1]) {
        urlVal = match[1];
        document.getElementById('embed-link-field').value = urlVal;
      }
    }

    const ytId = getYoutubeId(urlVal);
    
    // Set wizard elements
    dropzoneView.style.display = 'none';
    detailsView.style.display = 'block';
    
    step1Indicator.classList.add('done');
    step1Indicator.classList.remove('active');
    step2Indicator.classList.add('active');

    // Display appropriate preview player
    if (ytId) {
      spaPlayer.style.display = 'none';
      spaIframePlayer.src = `https://www.youtube.com/embed/${ytId}`;
      spaIframePlayer.style.display = 'block';
      previewDuration.textContent = 'YouTube Embed';
      
      // Auto-fetch YouTube title
      fetch(`https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=${ytId}&format=json`)
        .then(r => r.json())
        .then(data => {
          if (data && data.title) {
            detailsTitle.value = data.title;
            previewTitle.textContent = data.title;
          }
        }).catch(()=>{});

      // Load YouTube Thumbnails in selector grid
      const grid = document.getElementById('details-thumb-grid');
      const customTrigger = grid.querySelector('.custom-thumb-trigger');
      grid.innerHTML = '';
      grid.appendChild(customTrigger);

      const ytThumbs = [
        { label: 'Max Resolution', url: `https://img.youtube.com/vi/${ytId}/maxresdefault.jpg` },
        { label: 'High Quality', url: `https://img.youtube.com/vi/${ytId}/hqdefault.jpg` },
        { label: 'Medium Quality', url: `https://img.youtube.com/vi/${ytId}/mqdefault.jpg` }
      ];

      ytThumbs.forEach((t, i) => {
        const option = document.createElement('div');
        option.className = 'thumb-option';
        option.innerHTML = `
          <img src="${t.url}">
          <div class="check-badge"><svg width="10" height="10" fill="#fff" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
          <div style="position:absolute;bottom:3px;left:6px;font-size:0.6rem;color:#fff;text-shadow:0 1px 2px #000">${t.label}</div>
        `;
        option.addEventListener('click', () => {
          grid.querySelectorAll('.thumb-option').forEach(o => o.classList.remove('selected'));
          option.classList.add('selected');
          convertYtImageToBase64(t.url);
        });
        grid.appendChild(option);
        
        if (i === 0) option.click();
      });

    } else {
      // Direct file link
      spaPlayer.src = urlVal;
      spaPlayer.style.display = 'block';
      spaIframePlayer.style.display = 'none';
      
      spaPlayer.addEventListener('loadedmetadata', () => {
        previewDuration.textContent = formatTime(Math.max(1, Math.floor(spaPlayer.duration || 0)));
      }, {once: true});

      previewDuration.textContent = 'Direct URL';
    }

    // Hide Diagnostics progress bar for embeds (since no file chunks are uploaded!)
    document.getElementById('spa-diagnostics-widget').style.display = 'none';
  };

  // Convert external YouTube image to base64 via canvas to allow server saving
  function convertYtImageToBase64(url) {
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.src = url;
    img.onload = () => {
      const canvas = document.createElement('canvas');
      canvas.width = 1280;
      canvas.height = 720;
      const ctx = canvas.getContext('2d');
      ctx.drawImage(img, 0, 0, 1280, 720);
      selectedThumbDataUrl = canvas.toDataURL('image/jpeg', 0.85);
    };
  }

  // Submit Details Form & Complete SPA Transition (Non-blocking)
  window.submitDetailsForm = async function(e) {
    e.preventDefault();
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving details...';

    // If it's an embed URL, initialize upload session and placeholder first
    let urlVal = document.getElementById('embed-link-field').value.trim();
    if (urlVal && !uploadVideoId) {
      const meta = {
        title: detailsTitle.value || 'Untitled Embed',
        description: document.getElementById('details-desc').value || '',
        tags: document.getElementById('details-tags').value || '',
        category_ids: Array.from(document.querySelectorAll('input[name="category_ids[]"]:checked')).map(i=>i.value),
        visibility: document.getElementById('details-visibility').value || 'public'
      };
      
      const initRes = await fetch('<?= BASE_URL ?>/api/videos.php?action=init_upload', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({meta})
      });
      const initData = await initRes.json();
      if (initData.success) {
        uploadVideoId = initData.data.video_id;
        uploadToken = initData.data.upload_token;
        
        // Update direct video URL on backend
        await fetch(`<?= BASE_URL ?>/api/upload.php?video_id=${uploadVideoId}&token=${uploadToken}&finalize=1&filename=${encodeURIComponent(urlVal)}`, {
          method: 'POST'
        });
      }
    }

    if (!uploadVideoId) {
      alert('Error initializing video upload. Please select a valid file.');
      submitBtn.disabled = false;
      submitBtn.textContent = 'Publish & Finish';
      return;
    }

    // Capture metadata details
    const meta = {
      video_id: uploadVideoId,
      title: detailsTitle.value,
      description: document.getElementById('details-desc').value,
      tags: document.getElementById('details-tags').value,
      visibility: document.getElementById('details-visibility').value,
      category_ids: Array.from(document.querySelectorAll('input[name="category_ids[]"]:checked')).map(i=>i.value)
    };

    try {
      // 1. Submit metadata update via AJAX
      await fetch('<?= BASE_URL ?>/api/videos.php?action=save_metadata', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({meta})
      });

      // 2. Save selected base64 client-side thumbnail via AJAX
      if (selectedThumbDataUrl) {
        await fetch('<?= BASE_URL ?>/api/thumbnails.php?action=save_thumbnail', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({video_id: uploadVideoId, data_url: selectedThumbDataUrl})
        });
      }

      // Transition wizard instantly to Success View (SPA)
      detailsView.style.display = 'none';
      successView.style.display = 'block';
      
      step2Indicator.classList.add('done');
      step2Indicator.classList.remove('active');
      step3Indicator.classList.add('active');

      // Populate success view details
      document.getElementById('success-video-title').textContent = detailsTitle.value;
      if (selectedThumbDataUrl) {
        document.getElementById('success-thumb-preview').src = selectedThumbDataUrl;
      } else {
        document.getElementById('success-thumb-preview').src = '<?= thumb_url(null) ?>';
      }

      // Hide active progress indicator on success view if upload has already completed!
      const successDiagCard = document.getElementById('success-diagnostics-card');
      if (!isUploading) {
        if (successDiagCard) successDiagCard.style.display = 'none';
      } else {
        if (successDiagCard) {
          successDiagCard.style.display = 'block';
          document.getElementById('success-status-lbl').textContent = 'Uploading in background...';
          document.getElementById('success-percent-lbl').textContent = uploadProgress + '%';
          document.getElementById('success-progress-fill').style.width = uploadProgress + '%';
        }
      }

    } catch (e) {
      console.error(e);
      alert('Error updating details. Please try again.');
      submitBtn.disabled = false;
      submitBtn.textContent = 'Publish & Finish';
    }
  };

  // Reset entire SPA uploader state to upload another video
  window.resetUploadWizard = function() {
    isUploading = false;
    activeFile = null;
    uploadVideoId = null;
    uploadToken = null;
    selectedThumbDataUrl = null;
    uploadProgress = 0;
    
    // Reset forms
    document.getElementById('spa-upload-form').reset();
    detailsTitle.value = '';
    previewTitle.textContent = 'Video Title Preview';
    previewDuration.textContent = '0:00';
    
    // Stop players
    spaPlayer.pause();
    spaPlayer.src = '';
    spaIframePlayer.src = '';
    
    // Transition views
    successView.style.display = 'none';
    detailsView.style.display = 'none';
    dropzoneView.style.display = 'block';
    
    step1Indicator.className = 'wizard-step active';
    step2Indicator.className = 'wizard-step';
    step3Indicator.className = 'wizard-step';
    
    // Reset dropzone text
    document.getElementById('embed-link-field').value = '';
    document.getElementById('spa-diagnostics-widget').style.display = 'block';
    percentageText.textContent = '0%';
    progressFill.style.width = '0%';
    speedText.textContent = '—';
    etaText.textContent = '—';
  };

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

})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
