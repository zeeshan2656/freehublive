<?php
// ============================================================
// FreeHub.Live — Modern Background Reel Upload System (SPA)
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin']);

if (setting('reels_enabled', '1') !== '1') {
    redirect(BASE_URL . '/');
}

$site_theme = setting('active_theme', 'dark-minimal');
$primary    = setting('primary_color', '#6366f1');

$uid     = auth_user()['id'];
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) { 
        $error = 'Invalid request.'; 
    } else {
        $title       = trim($_POST['title'] ?? '');
        $visibility  = in_array($_POST['visibility']??'', ['public','unlisted','private']) ? $_POST['visibility'] : 'public';

        if ($title === '') {
            $title = 'Reel #' . rand(10000, 99999);
        }

        if (empty($_FILES['video']['name'])) {
            $error = 'Please select a video file.';
        } else {
            $vfile = $_FILES['video'];
            $mime  = mime_content_type($vfile['tmp_name']);
            
            if (!allowed_video($mime)) { 
                $error = 'Invalid video format. Use MP4, WebM, or MOV.'; 
            } elseif ($vfile['size'] > 500 * 1024 * 1024) { // Reels max 500MB
                $error = 'Reel video max size is 500MB.'; 
            } else {
                if (!is_dir(VIDEO_PATH)) mkdir(VIDEO_PATH, 0755, true);
                if (!is_dir(THUMB_PATH)) mkdir(THUMB_PATH, 0755, true);

                $ext       = strtolower(pathinfo($vfile['name'], PATHINFO_EXTENSION));
                $vfilename = unique_filename($ext);
                move_uploaded_file($vfile['tmp_name'], VIDEO_PATH . $vfilename);

                $file_size = $vfile['size'];
                $duration  = fh_probe_video_duration($vfilename);

                // Handle extracted first-frame thumbnail upload (sent via AJAX or standard form thumbnail blob)
                $thumbnail = null;
                if (!empty($_FILES['extracted_thumb']['name'])) {
                    $tfile = $_FILES['extracted_thumb'];
                    $text  = strtolower(pathinfo($tfile['name'], PATHINFO_EXTENSION));
                    if (in_array($text, ['jpg', 'jpeg', 'png'])) {
                        $tfilename = unique_filename($text);
                        move_uploaded_file($tfile['tmp_name'], THUMB_PATH . $tfilename);
                        $thumbnail = $tfilename;
                    }
                }

                $slug = slugify($title);
                $base = $slug; $i = 1;
                while (db_fetch("SELECT id FROM videos WHERE slug=?", [$slug])) {
                    $slug = $base . '-' . $i++;
                }

                $approvalMode  = setting('video_approval_mode', 'manual');
                $initialStatus = ($approvalMode === 'auto') ? 'published' : 'pending';
                $publishedAt   = ($approvalMode === 'auto') ? date('Y-m-d H:i:s') : null;

                $new_reel_id = db_insert('videos', [
                    'user_id'      => $uid,
                    'category_id'  => null,
                    'title'        => $title,
                    'slug'         => $slug,
                    'description'  => 'Short vertical reel video.',
                    'tags'         => 'reel,short',
                    'video_url'    => $vfilename,
                    'thumbnail'    => $thumbnail,
                    'file_size'    => $file_size,
                    'duration'     => $duration > 0 ? $duration : 0,
                    'visibility'   => $visibility,
                    'status'       => $initialStatus,
                    'published_at' => $publishedAt,
                    'is_reel'      => 1,
                ]);

                if ($new_reel_id && $duration < 1) {
                    fh_ensure_video_duration((int)$new_reel_id);
                }

                if ($initialStatus === 'published') {
                    flash('success', 'Your Reel has been uploaded and published successfully!');
                } else {
                    flash('success', 'Your Reel has been submitted and is currently pending admin moderation approval.');
                }
                
                redirect(BASE_URL . '/channel.php?id=' . $uid . '&tab=reels');
            }
        }
    }
}

$meta_title = 'Upload Reel';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<main class="main-content">

<style>
/* ── Reels Background Upload Redesign ── */
.upload-wizard {
  max-width: 1000px;
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
  grid-template-columns: 1.5fr 1fr;
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
  margin: 0 0 16px;
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

/* Preview Card - Vertical 9:16 for Reels */
.preview-card {
  background: var(--bg2);
  border: 1px solid var(--border);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 8px 30px rgba(0,0,0,0.18);
  max-width: 260px;
  margin: 0 auto;
  width: 100%;
}
.preview-aspect-ratio {
  position: relative;
  aspect-ratio: 9/16;
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

/* Layout switches */
#details-view, #success-view { display: none; }

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
      <div class="step-title">Select Reel File</div>
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
      <a href="<?= BASE_URL ?>/creator/upload.php" class="upload-tab-btn" style="text-decoration:none">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        Upload Video File
      </a>
      <a href="<?= BASE_URL ?>/creator/upload.php?tab=embed" class="upload-tab-btn" style="text-decoration:none">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
        Embed Link (YouTube / Direct URL)
      </a>
      <a href="<?= BASE_URL ?>/creator/upload_reel.php" class="upload-tab-btn active" style="text-decoration:none">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
        Upload Reel
      </a>
    </div>

    <!-- Dropzone File block -->
    <div id="file-drop-container">
      <?php if ($error): ?><div class="alert alert-error" style="margin-bottom:20px"><?= e($error) ?></div><?php endif; ?>
      <div class="upload-dropzone" id="drop-zone" onclick="document.getElementById('video-file-input').click()">
        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        <h2 class="upload-title">Drag and drop vertical video file to upload Reel</h2>
        <p class="upload-sub">Supports MP4, WebM, MOV. Reels upload progressively in the background.</p>
        <button type="button" class="btn btn-primary" style="margin-top:20px; font-weight:700">Select Reel File</button>
      </div>
      <input type="file" id="video-file-input" accept="video/mp4,video/webm,video/quicktime" style="display:none">
    </div>
  </div>

  <!-- ── VIEW 2: SPA Details Wizard ── -->
  <div id="details-view" class="fade-in">
    <form id="spa-upload-form" onsubmit="submitDetailsForm(event)">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      
      <div class="wizard-layout-cols">
        
        <!-- Left details panel -->
        <div class="wizard-main-panel">
          <div class="wizard-section-lbl">Reel Details</div>
          
          <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" style="font-weight: 700;">Reel Title <span class="text-muted">(Optional)</span></label>
            <input class="form-input" type="text" id="details-title" name="title" placeholder="Give your reel a catchy title..." style="border-radius:8px">
          </div>

          <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" style="font-weight: 700;">Visibility</label>
            <select class="form-input form-select" id="details-visibility" name="visibility" style="border-radius: 8px">
              <option value="public" selected>🌐 Public (everyone can watch)</option>
              <option value="unlisted">🔗 Unlisted (anyone with link can watch)</option>
              <option value="private">🔒 Private (only you can watch)</option>
            </select>
          </div>
        </div>

        <!-- Right sticky preview & diagnostics panel -->
        <div class="wizard-preview-panel">
          <!-- Video Card (Reels 9:16) -->
          <div class="preview-card">
            <div class="preview-aspect-ratio" id="details-video-preview">
              <video id="spa-player" style="width:100%; height:100%; object-fit:cover" controls></video>
            </div>
            <div class="preview-details">
              <div class="preview-title" id="details-preview-title">Reel Title Preview</div>
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
            🚀 Publish Reel
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
      <h2 class="success-title">Reel Published Successfully!</h2>
      <p class="success-subtitle" id="success-subtitle-text">
        Your Reel metadata and thumbnail have been saved. The background uploader is finalizing details.
      </p>

      <div class="preview-card" style="max-width:220px; margin:0 auto 30px">
        <div class="preview-aspect-ratio">
          <img id="success-thumb-preview" src="" style="width:100%; height:100%; object-fit:cover">
        </div>
        <div class="preview-details" style="text-align:left">
          <div class="preview-title" id="success-video-title" style="font-size:0.88rem; margin:0">Reel Title</div>
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
        <a href="<?= BASE_URL ?>/channel.php?id=<?= $uid ?>&tab=reels" class="btn btn-primary" style="font-weight:700; border-radius:8px">
          📂 Go to Reels
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
      e.returnValue = 'Your Reel upload is currently in progress. Leaving this page will cancel the upload. Are you sure you want to exit?';
      return e.returnValue;
    }
  });

  // Real-time preview title sync
  detailsTitle.addEventListener('input', () => {
    previewTitle.textContent = detailsTitle.value.trim() || 'Reel Title Preview';
  });

  // Drag & drop handlers
  if (dropZone && fileInput) {
    ['dragover','dragenter'].forEach(e => dropZone.addEventListener(e, ev => { ev.preventDefault(); dropZone.classList.add('drag-over'); }));
    ['dragleave','drop'].forEach(e => dropZone.addEventListener(e, ev => { ev.preventDefault(); dropZone.classList.remove('drag-over'); }));
    dropZone.addEventListener('drop', e => { if(e.dataTransfer.files[0]) handleSelectedFile(e.dataTransfer.files[0]); });
    fileInput.addEventListener('change', () => { if(fileInput.files[0]) handleSelectedFile(fileInput.files[0]); });
  }

  // Main Handler once local file is selected
  function handleSelectedFile(file) {
    activeFile = file;
    isUploading = true;
    
    // Fill Title automatically with name without extension
    const nameWithoutExt = file.name.substring(0, file.name.lastIndexOf('.')) || file.name;
    detailsTitle.value = nameWithoutExt.substring(0, 100);
    previewTitle.textContent = detailsTitle.value;
    
    // Transition wizard panels instantly
    dropzoneView.style.display = 'none';
    detailsView.style.display = 'block';
    
    step1Indicator.classList.add('done');
    step1Indicator.classList.remove('active');
    step2Indicator.classList.add('active');
    
    // Render local video playing in player
    const localBlobUrl = URL.createObjectURL(file);
    spaPlayer.src = localBlobUrl;
    spaPlayer.style.display = 'block';
    
    // Listen for duration
    spaPlayer.addEventListener('loadedmetadata', () => {
      const d = Math.max(1, Math.floor(spaPlayer.duration || 0));
      previewDuration.textContent = formatTime(d);
      
      // Auto-extract client-side first frame thumbnail instantly!
      generateClientThumbnail(file);
    }, {once: true});

    // Start background progressive chunk upload loop
    startProgressiveChunkUpload(file);
  }

  // Auto-generate client-side vertical thumbnail instantly
  async function generateClientThumbnail(file) {
    const canvas = document.getElementById('offscreen-canvas');
    const ctx = canvas.getContext('2d');
    
    const helperVideo = document.createElement('video');
    helperVideo.preload = 'auto';
    helperVideo.src = URL.createObjectURL(file);
    helperVideo.muted = true;
    helperVideo.playsInline = true;
    
    helperVideo.addEventListener('loadedmetadata', async () => {
      const videoWidth = helperVideo.videoWidth || 720;
      const videoHeight = helperVideo.videoHeight || 1280;
      // maintain vertical 9:16 aspect ratio bounds
      canvas.width = 360;
      canvas.height = Math.round(360 * (videoHeight / videoWidth));
      
      // seek to 0.1s to ensure a non-black frame
      helperVideo.currentTime = 0.1;
      helperVideo.addEventListener('seeked', () => {
        ctx.drawImage(helperVideo, 0, 0, canvas.width, canvas.height);
        selectedThumbDataUrl = canvas.toDataURL('image/jpeg', 0.85);
        URL.revokeObjectURL(helperVideo.src);
      }, {once: true});
    }, {once: true});
  }

  // Start Background Resumable Progressive Chunk Upload
  async function startProgressiveChunkUpload(file) {
    try {
      submitBtn.disabled = true;
      submitBtn.style.opacity = '0.65';
      statusText.innerHTML = `<span style="animation: spin 1.5s linear infinite; display:inline-block">🔄</span> Initializing background upload...`;
      
      const meta = {
        title: detailsTitle.value || '',
        is_reel: 1,
        visibility: document.getElementById('details-visibility').value || 'public'
      };

      // 1. Initialize Upload Placeholder Record with is_reel enabled
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
      
      // Enable Submit Button Immediately - User can complete details form anytime!
      submitBtn.disabled = false;
      submitBtn.style.opacity = '1';
      
      // 2. Perform Resumable progressive Chunk Upload Loop
      const CHUNK_SIZE = 5 * 1024 * 1024; // 5MB chunks
      const totalSize = file.size;
      let uploadedBytes = 0;
      
      // status check for resumability
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
      
      for (let start = uploadedBytes; start < totalSize; start += CHUNK_SIZE) {
        if (!isUploading) break;
        
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

        // Calculate Speed & ETA
        const now = Date.now();
        const delta = (now - lastTime) / 1000;
        const deltaBytes = uploadedBytes - lastBytes;
        const speed = delta > 0 ? Math.round(deltaBytes / delta) : 0;
        lastTime = now;
        lastBytes = uploadedBytes;

        const pct = Math.floor((uploadedBytes / totalSize) * 100);
        uploadProgress = pct;
        
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

  // Submit Details Form & Complete SPA Transition
  window.submitDetailsForm = async function(e) {
    e.preventDefault();
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving details...';

    if (!uploadVideoId) {
      alert('Error initializing Reel upload. Please select a valid file.');
      submitBtn.disabled = false;
      submitBtn.textContent = 'Publish Reel';
      return;
    }

    const meta = {
      video_id: uploadVideoId,
      title: detailsTitle.value,
      visibility: document.getElementById('details-visibility').value
    };

    try {
      // 1. Submit metadata update via AJAX
      const saveRes = await fetch('<?= BASE_URL ?>/api/videos.php?action=save_metadata', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({meta})
      });
      const saveData = await saveRes.json();
      
      // Get the finalized or generated title to show on success screen
      let finalTitle = detailsTitle.value.trim();
      if (!finalTitle && saveData.success) {
        // Fetch generated title or keep placeholder
        finalTitle = 'Reel Upload';
      }

      // 2. Save selected base64 client-side thumbnail via AJAX
      if (selectedThumbDataUrl) {
        await fetch('<?= BASE_URL ?>/api/thumbnails.php?action=save_thumbnail', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({video_id: uploadVideoId, data_url: selectedThumbDataUrl})
        });
      }

      // Transition wizard instantly to Success View
      detailsView.style.display = 'none';
      successView.style.display = 'block';
      
      step2Indicator.classList.add('done');
      step2Indicator.classList.remove('active');
      step3Indicator.classList.add('active');

      // Populate success view details
      document.getElementById('success-video-title').textContent = finalTitle;
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
      alert('Error updating Reel details. Please try again.');
      submitBtn.disabled = false;
      submitBtn.textContent = 'Publish Reel';
    }
  };

  // Reset entire SPA uploader state
  window.resetUploadWizard = function() {
    isUploading = false;
    activeFile = null;
    uploadVideoId = null;
    uploadToken = null;
    selectedThumbDataUrl = null;
    uploadProgress = 0;
    
    document.getElementById('spa-upload-form').reset();
    detailsTitle.value = '';
    previewTitle.textContent = 'Reel Title Preview';
    previewDuration.textContent = '0:00';
    
    spaPlayer.pause();
    spaPlayer.src = '';
    
    successView.style.display = 'none';
    detailsView.style.display = 'none';
    dropzoneView.style.display = 'block';
    
    step1Indicator.className = 'wizard-step active';
    step2Indicator.className = 'wizard-step';
    step3Indicator.className = 'wizard-step';
    
    percentageText.textContent = '0%';
    progressFill.style.width = '0%';
    speedText.textContent = '—';
    etaText.textContent = '—';
  };

  // Utilities
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

</main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
