<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['creator','admin']);

$site_theme = setting('active_theme', 'dark-minimal');
$primary    = setting('primary_color', '#6366f1');

$uid     = auth_user()['id'];
$error   = '';
$success = '';
$new_vid_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) { 
        $error = 'Invalid request.'; 
    } else {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $tags        = trim($_POST['tags'] ?? '');
        $category_ids = array_map('intval', $_POST['category_ids'] ?? []);
        $category_id  = !empty($category_ids) ? $category_ids[0] : 0;
        $visibility  = in_array($_POST['visibility']??'', ['public','unlisted','private']) ? $_POST['visibility'] : 'public';
        $upload_type = trim($_POST['upload_type'] ?? 'file');

        if (strlen($title) < 3) { 
            $error = 'Title must be at least 3 characters.'; 
        } else {
            $vfilename = '';
            $file_size = 0;
            $duration = 0;
            $thumbnail = null;

            if ($upload_type === 'file') {
                if (empty($_FILES['video']['name'])) { 
                    $error = 'Please select a video file.'; 
                } else {
                    $vfile = $_FILES['video'];
                    $mime  = mime_content_type($vfile['tmp_name']);
                    if (!allowed_video($mime)) { 
                        $error = 'Invalid video format. Use MP4, WebM, or MOV.'; 
                    } elseif ($vfile['size'] > 2 * 1024 * 1024 * 1024) { 
                        $error = 'Video max size is 2GB.'; 
                    } else {
                        if (!is_dir(VIDEO_PATH)) mkdir(VIDEO_PATH, 0755, true);
                        if (!is_dir(THUMB_PATH)) mkdir(THUMB_PATH, 0755, true);

                        $ext       = strtolower(pathinfo($vfile['name'], PATHINFO_EXTENSION));
                        $vfilename = unique_filename($ext);
                        move_uploaded_file($vfile['tmp_name'], VIDEO_PATH . $vfilename);

                        $file_size = $vfile['size'];
                        $postedDur = (int)($_POST['duration_seconds'] ?? 0);
                        $probedDur = fh_probe_video_duration($vfilename);
                        $duration  = max($postedDur, $probedDur);
                    }
                }
            } else {
                // Embed URL
                $embed_url = trim($_POST['embed_url'] ?? '');
                if (empty($embed_url)) {
                    $error = 'Please enter a video URL.';
                } elseif (!filter_var($embed_url, FILTER_VALIDATE_URL)) {
                    $error = 'Please enter a valid URL.';
                } else {
                    $vfilename = $embed_url;
                    $file_size = 0;

                    $yt_id = fh_youtube_id($vfilename);
                    if ($yt_id) {
                        $thumb_filename = unique_filename('jpg');
                        $thumb_local_path = __DIR__ . '/../uploads/thumbnails/' . $thumb_filename;
                        $img_data = @file_get_contents("https://img.youtube.com/vi/{$yt_id}/maxresdefault.jpg");
                        if (!$img_data) {
                            $img_data = @file_get_contents("https://img.youtube.com/vi/{$yt_id}/hqdefault.jpg");
                        }
                        if ($img_data) {
                            if (!is_dir(dirname($thumb_local_path))) mkdir(dirname($thumb_local_path), 0755, true);
                            file_put_contents($thumb_local_path, $img_data);
                            $thumbnail = $thumb_filename;
                        }
                    }
                    $duration = (int)($_POST['duration_seconds_embed'] ?? 0);
                }
            }

            if (!$error) {
                $slug = slugify($title);
                $base = $slug; $i = 1;
                while (db_fetch("SELECT id FROM videos WHERE slug=?", [$slug])) {
                    $slug = $base . '-' . $i++;
                }

                $approvalMode  = setting('video_approval_mode', 'manual');
                $initialStatus = ($approvalMode === 'auto') ? 'published' : 'pending';
                $publishedAt   = ($approvalMode === 'auto') ? date('Y-m-d H:i:s') : null;

                $new_vid_id = db_insert('videos', [
                    'user_id'      => $uid,
                    'category_id'  => $category_id ?: null,
                    'title'        => $title,
                    'slug'         => $slug,
                    'description'  => $description,
                    'tags'         => $tags,
                    'video_url'    => $vfilename,
                    'thumbnail'    => $thumbnail,
                    'file_size'    => $file_size,
                    'duration'     => $duration > 0 ? $duration : 0,
                    'visibility'   => $visibility,
                    'status'       => $initialStatus,
                    'published_at' => $publishedAt,
                ]);

                if ($new_vid_id && $duration < 1 && $upload_type === 'file') {
                    fh_ensure_video_duration((int)$new_vid_id);
                }

                if ($new_vid_id && !empty($category_ids)) {
                    foreach ($category_ids as $cid) {
                        db_insert('video_categories', [
                            'video_id'    => $new_vid_id,
                            'category_id' => $cid
                        ]);
                    }
                }

                $success = $initialStatus === 'published'
                    ? 'Video saved and published! Now select a thumbnail below.'
                    : 'Video saved successfully! Now select a thumbnail below.';
            }
        }
    }
}
$categories = db_fetchAll("SELECT * FROM categories WHERE is_active=1 ORDER BY sort_order");
$meta_title = 'Upload Video';
require_once __DIR__ . '/../includes/header.php';
?>
<script>document.body.classList.add('upload-single-screen');</script>


<style>
/* ── YouTube Style Upload Redesign ── */
.upload-wizard {
  max-width: 1040px;
  margin: 0 auto;
  padding: 16px 8px 48px;
}

/* Steps Progress bar */
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
  transition: all 0.3s;
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

/* Upload Type Tabs */
.upload-tab-header {
  display: flex;
  gap: 8px;
  margin-bottom: 20px;
  border-bottom: 1px solid var(--border);
  padding-bottom: 8px;
}
.upload-tab-btn {
  background: none;
  border: none;
  color: var(--text2);
  font-size: 0.88rem;
  font-weight: 700;
  padding: 10px 18px;
  cursor: pointer;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all var(--trans);
}
.upload-tab-btn:hover {
  color: var(--text);
  background: var(--bg3);
}
.upload-tab-btn.active {
  color: var(--accent);
  background: rgba(99, 102, 241, 0.12);
}

/* Two column layout */
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
  box-shadow: 0 8px 30px rgba(0,0,0,0.18);
}
.wizard-preview-panel {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* Video Preview Card */
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
  background: var(--bg3);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text3);
}
.preview-details {
  padding: 16px;
}
.preview-title {
  font-size: 0.92rem;
  font-weight: 700;
  line-height: 1.4;
  margin-bottom: 6px;
  word-break: break-word;
}
.preview-meta {
  font-size: 0.78rem;
  color: var(--text2);
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Dropzone style */
.upload-dropzone {
  border: 2px dashed var(--border);
  border-radius: 12px;
  padding: 44px 20px;
  text-align: center;
  cursor: pointer;
  background: var(--bg3);
  transition: all 0.25s ease;
}
.upload-dropzone:hover, .upload-dropzone.drag-over {
  border-color: var(--accent);
  background: rgba(99, 102, 241, 0.05);
}
.upload-dropzone svg {
  width: 48px;
  height: 48px;
  color: var(--text3);
  margin-bottom: 12px;
  transition: transform 0.25s;
}
.upload-dropzone:hover svg {
  transform: translateY(-3px);
  color: var(--accent);
}

/* Details Section label */
.wizard-section-lbl {
  font-size: .72rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: .06em;
  color: var(--text3);
  margin-bottom: 12px;
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

/* Thumbnails Grid styling */
.thumb-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 12px;
  margin-top: 14px;
}
.thumb-option {
  position: relative;
  aspect-ratio: 16/9;
  border-radius: 8px;
  overflow: hidden;
  border: 2px solid transparent;
  cursor: pointer;
  background: var(--bg3);
  transition: all 0.2s ease;
}
.thumb-option:hover {
  transform: scale(1.02);
  border-color: var(--border);
}
.thumb-option.selected {
  border-color: var(--accent);
  box-shadow: 0 0 12px rgba(99, 102, 241, 0.45);
}
.thumb-option img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.thumb-option .check {
  position: absolute;
  top: 6px;
  right: 6px;
  width: 16px;
  height: 16px;
  background: var(--accent);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transform: scale(0.7);
  transition: all 0.2s ease;
}
.thumb-option.selected .check {
  opacity: 1;
  transform: scale(1);
}

.thumb-loading {
  aspect-ratio: 16/9;
  background: var(--bg3);
  border-radius: 8px;
  border: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.72rem;
  color: var(--text3);
}

/* Custom scrollbar for scrollable inner areas */
.wizard-main-scrollable::-webkit-scrollbar {
  width: 6px;
}
.wizard-main-scrollable::-webkit-scrollbar-track {
  background: transparent;
}
.wizard-main-scrollable::-webkit-scrollbar-thumb {
  background: var(--border);
  border-radius: 3px;
}
.wizard-main-scrollable::-webkit-scrollbar-thumb:hover {
  background: var(--text3);
}

@media(max-width: 860px) {
  body.upload-single-screen .dashboard-main-viewport,
  body.upload-single-screen .dashboard-content-scroll {
    height: auto !important;
    overflow: visible !important;
  }
  .upload-wizard {
    width: 100% !important;
    max-width: 100% !important;
    padding: 8px 4px 32px !important;
  }
  .wizard-layout-cols {
    grid-template-columns: 1fr;
    gap: 16px;
  }
  .wizard-main-panel {
    padding: 16px 12px !important;
    border-radius: 12px !important;
  }
  .wizard-main-scrollable {
    overflow: visible !important;
    height: auto !important;
    flex: none !important;
  }
  .thumb-grid {
    grid-template-columns: repeat(2, 1fr) !important;
    gap: 8px !important;
  }
}

@media(max-width: 600px) {
  .step-progress-bar {
    padding: 12px 16px;
  }
  .wizard-step:not(.active) .step-title {
    display: none;
  }
  .wizard-step:not(.active) {
    flex: none;
  }
  .wizard-step.active {
    flex: 1;
  }
  .step-connector {
    margin: 0 8px;
  }
}

@media (min-width: 861px) {
  body.upload-single-screen .dashboard-main-viewport {
    overflow: hidden !important;
  }
  body.upload-single-screen .dashboard-content-scroll {
    height: calc(100vh - 50px) !important;
    overflow: hidden !important;
    display: flex !important;
    flex-direction: column !important;
    padding: 16px 20px !important;
  }
  .upload-wizard {
    height: 100% !important;
    max-width: 1200px !important;
    padding: 0 !important;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    gap: 16px;
  }
  body.upload-single-screen .step-progress-bar {
    margin-bottom: 0 !important;
    padding: 12px 20px !important;
    flex-shrink: 0;
  }
  .wizard-layout-cols {
    flex: 1;
    min-height: 0;
    height: calc(100% - 10px);
    grid-template-columns: 1.6fr 1fr;
    align-items: stretch;
    gap: 20px;
  }
  .wizard-main-panel {
    height: 100% !important;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    padding: 20px !important;
  }
  .wizard-main-scrollable {
    flex: 1;
    overflow-y: auto;
    padding-right: 8px;
  }
  .wizard-preview-panel {
    height: 100% !important;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    overflow: hidden;
  }
  
  /* Make sure step 2 panel handles flex correctly */
  #thumb-step {
    display: flex;
    flex-direction: column;
    height: 100% !important;
    overflow: hidden;
  }
}

</style>

<div class="upload-wizard">

  <!-- Step progress bar -->
  <div class="step-progress-bar">
    <div class="wizard-step <?= !$success ? 'active' : 'done' ?>" id="step1-el">
      <div class="step-icon">1</div>
      <div class="step-title">Video Source &amp; Details</div>
      <div class="step-connector"></div>
    </div>
    <div class="wizard-step <?= $success ? 'active' : '' ?>" id="step2-el">
      <div class="step-icon">2</div>
      <div class="step-title">Select Thumbnail</div>
      <div class="step-connector"></div>
    </div>
    <div class="wizard-step" id="step3-el">
      <div class="step-icon">3</div>
      <div class="step-title">Complete</div>
    </div>
  </div>

  <?php if ($error): ?>
  <div class="alert alert-error" style="border-radius: 12px; margin-bottom: 20px">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="display:inline-block; vertical-align:middle; margin-right:8px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <?= e($error) ?>
  </div>
  <?php endif; ?>

  <!-- STEP 1: Upload Source & Details -->
  <?php if (!$success): ?>
  <form method="POST" enctype="multipart/form-data" id="upload-form">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="upload_type" id="upload-type-input" value="file">
    <input type="hidden" name="duration_seconds" id="duration-seconds" value="0">
    <input type="hidden" name="duration_seconds_embed" id="duration-seconds-embed" value="0">

    <div class="wizard-layout-cols">
      
      <!-- Main Details Form -->
      <div class="wizard-main-panel">
        
        <!-- Tab selector -->
        <div class="upload-tab-header">
          <button type="button" class="upload-tab-btn active" id="tab-file-btn" onclick="switchUploadType('file')">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Upload File
          </button>
          <button type="button" class="upload-tab-btn" id="tab-embed-btn" onclick="switchUploadType('embed')">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
            Embed Link (YouTube / URL)
          </button>
        </div>

        <div class="wizard-main-scrollable">
          <!-- File Upload Dragzone -->
          <div id="upload-file-container">
            <div class="upload-dropzone" id="video-zone" onclick="document.getElementById('video-file').click()">
              <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
              <div class="upload-title" id="video-zone-text" style="font-size: 0.95rem">Drag &amp; drop your video here</div>
              <div class="upload-sub" style="font-size: 0.76rem; opacity: 0.8">MP4, WebM, MOV — up to 2GB</div>
            </div>
            <input type="file" id="video-file" name="video" accept="video/mp4,video/webm,video/quicktime" style="display:none">
          </div>

          <!-- Embed Link input -->
          <div id="upload-embed-container" style="display:none">
            <div class="form-group" style="margin-bottom: 20px">
              <label class="form-label" style="font-weight: 700">Video Embed URL</label>
              <div style="display:flex; gap:10px">
                <input class="form-input" type="url" id="embed-url-input" name="embed_url" placeholder="Paste YouTube link (e.g., https://www.youtube.com/watch?v=...) or direct MP4 link" style="border-radius: 8px">
                <button type="button" class="btn btn-outline" onclick="probeEmbedLink()" style="border-radius: 8px; font-weight:600">Verify</button>
              </div>
              <p class="text-xs text-muted" style="margin-top: 6px; line-height: 1.4">Supports YouTube standard URLs, short URLs, and direct URLs ending in .mp4 or .webm.</p>
            </div>
          </div>

          <div style="margin-top:24px">
            <div class="wizard-section-lbl">Video Details</div>
            
            <div class="form-group">
              <label class="form-label">Title *</label>
              <input class="form-input" type="text" name="title" id="title-field" required maxlength="200" placeholder="Give your video a catchy title" value="<?= e($_POST['title'] ?? '') ?>" style="border-radius: 8px">
            </div>

            <div class="form-group">
              <label class="form-label">Description</label>
              <textarea class="form-input" name="description" id="desc-field" rows="4" placeholder="Tell viewers what your video is about…" style="resize:vertical; border-radius: 8px"><?= e($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
              <label class="form-label">Tags <span class="text-muted">(comma separated)</span></label>
              <input class="form-input" type="text" name="tags" id="tags-field" maxlength="500" placeholder="gaming, music, vlog" value="<?= e($_POST['tags'] ?? '') ?>" style="border-radius: 8px">
            </div>

            <div class="stat-grid-2" style="margin-top:20px">
              <div class="form-group">
                <label class="form-label">Categories (Select one or more)</label>
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(130px, 1fr)); gap:8px; background:var(--bg3); padding:10px; border-radius:8px; border:1px solid var(--border); max-height:120px; overflow-y:auto">
                  <?php foreach ($categories as $c): ?>
                  <label class="flex gap-2" style="font-size:.8rem; cursor:pointer; user-select:none; align-items:center">
                    <input type="checkbox" name="category_ids[]" value="<?= $c['id'] ?>" <?= in_array($c['id'], $_POST['category_ids'] ?? []) ? 'checked' : '' ?>>
                    <span><?= e($c['name']) ?></span>
                  </label>
                  <?php endforeach; ?>
                </div>
              </div>
              
              <div class="form-group">
                <label class="form-label">Visibility</label>
                <select class="form-input form-select" name="visibility" style="border-radius: 8px">
                  <option value="public">Public</option>
                  <option value="unlisted">Unlisted</option>
                  <option value="private">Private</option>
                </select>
              </div>
            </div>
          </div>
        </div>


      </div>

      <!-- Right Sidebar (Preview / Publish mode) -->
      <div class="wizard-preview-panel">
        
        <!-- Video Preview Card -->
        <div class="preview-card">
          <div class="preview-aspect-ratio" id="preview-video-container">
            <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            <div id="preview-yt-iframe" style="display:none; width:100%; height:100%"></div>
            <video id="preview-direct-player" style="display:none; width:100%; height:100%; object-fit:cover" controls></video>
          </div>
          <div class="preview-details">
            <div class="preview-title" id="preview-video-title">Video Title Preview</div>
            <div class="preview-meta">
              <span id="preview-channel-name"><?= e(auth_user()['channel_name'] ?? auth_user()['username']) ?></span>
              <span>·</span>
              <span id="preview-video-length">0:00</span>
            </div>
          </div>
        </div>

        <!-- Video Approval Info -->
        <div style="background:var(--bg2); border:1px solid var(--border); border-radius:16px; padding:20px; box-shadow:0 6px 20px rgba(0,0,0,0.12)">
          <?php if (setting('video_approval_mode','manual') === 'auto'): ?>
          <div style="font-size:0.85rem; font-weight:700; color:var(--green); margin-bottom:6px; display:flex; align-items:center; gap:8px">
            <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--green)"></span>
            Auto-Publish Enabled
          </div>
          <p class="text-xs text-muted" style="line-height:1.45; margin:0">Your video will go live immediately and count towards your creator earnings stats once the upload processes successfully.</p>
          <?php else: ?>
          <div style="font-size:0.85rem; font-weight:700; color:var(--yellow); margin-bottom:6px; display:flex; align-items:center; gap:8px">
            <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--yellow)"></span>
            Pending Approval Review
          </div>
          <p class="text-xs text-muted" style="line-height:1.45; margin:0">Your video will be queued for manual review. Our moderation team reviews uploads within 24 hours.</p>
          <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary w-full" style="justify-content:center; padding:12px 24px; border-radius:12px; font-weight:700; font-size:0.92rem; box-shadow:0 4px 14px rgba(99,102,241,0.3)">
          Upload &amp; Continue
        </button>

      </div>

    </div>
  </form>
  <?php endif; ?>

  <!-- STEP 2: Thumbnail Selection (loaded after successful upload) -->
  <?php if ($success && $new_vid_id): ?>
  <?php 
    $video_record = db_fetch("SELECT video_url, thumbnail FROM videos WHERE id=?", [$new_vid_id]);
    $video_uri = $video_record['video_url'] ?? '';
    $has_initial_thumb = !empty($video_record['thumbnail']);
    $initial_thumb_url = $has_initial_thumb ? thumb_url($video_record['thumbnail']) : '';
  ?>
  <div id="thumb-step" class="wizard-main-panel" style="box-shadow: 0 10px 40px rgba(0,0,0,0.22)">
    <div class="alert alert-success" style="border-radius: 12px; margin-bottom: 20px; font-weight:500">
      &#10003; <?= e($success) ?>
    </div>

    <!-- Hidden video for frame extraction (for direct videos) -->
    <video id="thumb-src" crossorigin="anonymous" preload="auto"
           src="<?= video_url($video_uri) ?>"
           style="display:none"></video>
    <canvas id="thumb-canvas" style="display:none"></canvas>

    <div class="wizard-main-scrollable">
      <div style="margin-bottom: 20px">
        <h2 style="font-size:1.15rem; font-weight:800; margin-bottom:6px">&#127775; Select or Upload Thumbnail</h2>
        <p class="text-muted text-sm" style="line-height:1.5; margin:0">
          Choose a display thumbnail for your video. You can select one of our auto-extracted frames, keep the YouTube fetched thumbnail, or upload a custom image.
        </p>
      </div>

      <!-- Thumbnails option grid -->
      <div class="thumb-grid" id="thumb-grid">
        <?php for($i=0; $i<10; $i++): ?>
        <div class="thumb-loading" id="tl-<?= $i ?>">
          <span>Loading frame…</span>
        </div>
        <?php endfor; ?>
      </div>

      <!-- Selected thumbnail preview box -->
      <div id="thumb-select-info" style="display:none; margin-top:20px; padding:16px; background:rgba(99,102,241,0.06); border-radius:12px; border:1px solid rgba(99,102,241,0.25)">
        <div style="display:flex; align-items:center; gap:16px">
          <img id="selected-thumb-preview" src="" style="width:144px; aspect-ratio:16/9; border-radius:8px; object-fit:cover; border:1px solid var(--border)">
          <div>
            <div style="font-weight:800; font-size:0.95rem; margin-bottom:2px">Selected Thumbnail</div>
            <p class="text-sm text-muted" style="margin:0">Click the button below to confirm your selection and save.</p>
          </div>
        </div>
      </div>
    </div>


    <!-- Actions area -->
    <div style="margin-top:24px; padding-top:20px; border-top:1px solid var(--border); display:flex; gap:12px; align-items:center; flex-wrap:wrap">
      <input type="file" id="custom-thumb-input" accept="image/jpeg,image/png,image/webp" style="display:none">
      <button type="button" class="btn btn-outline" onclick="document.getElementById('custom-thumb-input').click()" style="border-radius:8px; font-weight:600; padding:10px 18px">
        📂 Upload Custom Thumbnail
      </button>

      <button id="save-thumb-btn" class="btn btn-primary" disabled onclick="saveThumbnail()" style="border-radius:8px; font-weight:700; padding:10px 22px">
        Set as Thumbnail
      </button>
      
      <a href="<?= BASE_URL ?>/creator/videos.php" class="btn btn-outline" style="margin-left:auto; border-radius:8px">
        Finish upload &rarr;
      </a>
    </div>
  </div>

  <script data-page-script="true">
  (function() {
    const VID_ID = <?= $new_vid_id ?>;
    const video  = document.getElementById('thumb-src');
    const canvas = document.getElementById('thumb-canvas');
    const ctx    = canvas ? canvas.getContext('2d') : null;
    if (canvas) {
      canvas.width  = 1280;
      canvas.height = 720;
    }

    const hasInitialThumb = <?= $has_initial_thumb ? 'true' : 'false' ?>;
    const initialThumbUrl = "<?= $initial_thumb_url ?>";
    const videoUrlStr = <?= json_encode($video_uri) ?>;

    let selectedDataUrl = null;
    let capturedFrames  = [];
    let videoDuration   = 0;

    function getYoutubeId(url) {
      if (!url) return null;
      const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
      const match = url.match(regExp);
      return (match && match[2].length === 11) ? match[2] : null;
    }

    const ytId = getYoutubeId(videoUrlStr);

    if (ytId) {
      // YouTube embed thumbnail handling
      const grid = document.getElementById('thumb-grid');
      grid.innerHTML = ''; // Clear loading items

      // Prepend initial pre-fetched YouTube thumbnail if it exists
      if (hasInitialThumb) {
        addThumbOption(initialThumbUrl, 'YouTube MaxRes (Prefetched)', true);
      }

      // Add other quality options from YouTube
      const ytThumbs = [
        { label: 'YouTube High Quality', url: `https://img.youtube.com/vi/${ytId}/hqdefault.jpg` },
        { label: 'YouTube Med Quality', url: `https://img.youtube.com/vi/${ytId}/mqdefault.jpg` },
        { label: 'YouTube Standard', url: `https://img.youtube.com/vi/${ytId}/sddefault.jpg` }
      ];

      ytThumbs.forEach(t => {
        // Skip duplicate of pre-fetched if it's identical
        addThumbOption(t.url, t.label, !hasInitialThumb && t.label === 'YouTube High Quality');
      });

    } else {
      // Standard local/direct video thumbnail capture
      if (video) {
        video.addEventListener('loadedmetadata', () => {
          videoDuration = Math.max(1, Math.floor(video.duration || 0));
          saveVideoDuration(videoDuration);
          captureFrames();
        });

        video.addEventListener('error', () => {
          document.querySelectorAll('.thumb-loading').forEach(el => {
            el.textContent = 'Preview N/A';
          });
        });
      }
    }

    function addThumbOption(url, label, autoSelect = false) {
      const grid = document.getElementById('thumb-grid');
      const div = document.createElement('div');
      div.className = 'thumb-option' + (autoSelect ? ' selected' : '');
      div.innerHTML = `
        <img src="${url}" crossorigin="anonymous" loading="lazy">
        <div class="check"><svg width="12" height="12" fill="#fff" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
        <div style="position:absolute;bottom:4px;left:6px;font-size:.65rem;color:rgba(255,255,255,.9);text-shadow:0 1px 2px #000">${label}</div>
      `;
      div.addEventListener('click', () => {
        document.querySelectorAll('.thumb-option').forEach(o => o.classList.remove('selected'));
        div.classList.add('selected');
        
        // If it's the prefetched/initial one, we don't need to re-encode it
        if (url === initialThumbUrl) {
          selectedDataUrl = "initial";
        } else {
          // Attempt to convert external YouTube link to canvas data URL (re-encoding handles CORS)
          convertImageToDataUri(url);
        }
        document.getElementById('selected-thumb-preview').src = url;
        document.getElementById('thumb-select-info').style.display = 'block';
        document.getElementById('save-thumb-btn').disabled = false;
      });
      grid.appendChild(div);

      if (autoSelect) {
        selectedDataUrl = url === initialThumbUrl ? "initial" : null;
        if (url !== initialThumbUrl) convertImageToDataUri(url);
        document.getElementById('selected-thumb-preview').src = url;
        document.getElementById('thumb-select-info').style.display = 'block';
        document.getElementById('save-thumb-btn').disabled = false;
      }
    }

    function convertImageToDataUri(url) {
      const img = new Image();
      img.crossOrigin = "anonymous";
      img.src = url;
      img.onload = () => {
        const tempCanvas = document.createElement('canvas');
        tempCanvas.width = 1280;
        tempCanvas.height = 720;
        const tempCtx = tempCanvas.getContext('2d');
        tempCtx.drawImage(img, 0, 0, 1280, 720);
        selectedDataUrl = tempCanvas.toDataURL('image/jpeg', 0.85);
      };
    }

    async function saveVideoDuration(seconds) {
      if (!seconds || seconds < 1) return;
      try {
        await fetch('<?= BASE_URL ?>/api/thumbnails.php?action=save_duration', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({video_id: VID_ID, duration: seconds})
        });
      } catch (e) { /* non-blocking */ }
    }

    async function captureFrames() {
      const count = 10;
      const step  = videoDuration / (count + 1);
      const times = Array.from({length: count}, (_, i) => step * (i + 1));

      const grid = document.getElementById('thumb-grid');
      grid.innerHTML = ''; // Clear loading items

      if (hasInitialThumb) {
        // Prepend pre-fetched initial thumbnail option
        addThumbOption(initialThumbUrl, 'Prefetched Thumbnail', true);
      }

      for (let i = 0; i < count; i++) {
        const t = times[i];
        const dataUrl = await seekAndCapture(t);
        capturedFrames.push(dataUrl);

        const div = document.createElement('div');
        div.className = 'thumb-option';
        div.innerHTML = `
          <img src="${dataUrl}" loading="lazy">
          <div class="check"><svg width="12" height="12" fill="#fff" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
          <div style="position:absolute;bottom:4px;left:6px;font-size:.65rem;color:rgba(255,255,255,.9);text-shadow:0 1px 2px #000">${formatTime(t)}</div>
        `;
        div.addEventListener('click', () => {
          document.querySelectorAll('.thumb-option').forEach(o => o.classList.remove('selected'));
          div.classList.add('selected');
          selectedDataUrl = dataUrl;
          document.getElementById('selected-thumb-preview').src = dataUrl;
          document.getElementById('thumb-select-info').style.display = 'block';
          document.getElementById('save-thumb-btn').disabled = false;
        });
        grid.appendChild(div);
      }
    }

    function seekAndCapture(time) {
      return new Promise(resolve => {
        video.currentTime = time;
        video.addEventListener('seeked', function onSeeked() {
          video.removeEventListener('seeked', onSeeked);
          ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
          resolve(canvas.toDataURL('image/jpeg', 0.85));
        }, {once: true});
      });
    }



    // Custom thumbnail upload handler
    const customThumbInput = document.getElementById('custom-thumb-input');
    customThumbInput?.addEventListener('change', function() {
      const file = this.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = e => {
        const img = new Image();
        img.src = e.target.result;
        img.onload = () => {
          const tempCanvas = document.createElement('canvas');
          tempCanvas.width = 1280;
          tempCanvas.height = 720;
          const tempCtx = tempCanvas.getContext('2d');
          tempCtx.drawImage(img, 0, 0, 1280, 720);
          const dataUrl = tempCanvas.toDataURL('image/jpeg', 0.85);

          selectedDataUrl = dataUrl;
          document.getElementById('selected-thumb-preview').src = dataUrl;
          document.getElementById('thumb-select-info').style.display = 'block';
          document.getElementById('save-thumb-btn').disabled = false;
          
          document.querySelectorAll('.thumb-option').forEach(o => o.classList.remove('selected'));
        };
      };
      reader.readAsDataURL(file);
    });

    window.saveThumbnail = async function() {
      if (!selectedDataUrl) return;
      const btn = document.getElementById('save-thumb-btn');
      btn.disabled = true;
      btn.textContent = 'Saving…';

      if (selectedDataUrl === "initial") {
        btn.textContent = '✓ Saved!';
        btn.style.background = 'var(--green)';
        setTimeout(() => {
          window.location.href = '<?= BASE_URL ?>/creator/videos.php';
        }, 1000);
        return;
      }

      try {
        const res = await fetch('<?= BASE_URL ?>/api/thumbnails.php?action=save_thumbnail', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({video_id: VID_ID, data_url: selectedDataUrl})
        });
        const d = await res.json();
        if (d.success) {
          btn.textContent = '✓ Saved!';
          btn.style.background = 'var(--green)';
          setTimeout(() => {
            window.location.href = '<?= BASE_URL ?>/creator/videos.php';
          }, 1200);
        } else {
          btn.textContent = 'Error — retry';
          btn.disabled = false;
        }
      } catch(e) {
        btn.textContent = 'Error — retry';
        btn.disabled = false;
      }
    };

    function formatTime(s) {
      s = Math.floor(s);
      return Math.floor(s/60) + ':' + String(s%60).padStart(2,'0');
    }
  })();
  </script>
  <?php endif; ?>

</div>

<!-- STEP 1 Helper Scripts -->
<?php if (!$success): ?>
<script data-page-script="true">
(function() {
  const zone  = document.getElementById('video-zone');
  const input = document.getElementById('video-file');
  const uploadTypeInput = document.getElementById('upload-type-input');
  
  const tabFileBtn = document.getElementById('tab-file-btn');
  const tabEmbedBtn = document.getElementById('tab-embed-btn');
  const fileContainer = document.getElementById('upload-file-container');
  const embedContainer = document.getElementById('upload-embed-container');
  
  const titleField = document.getElementById('title-field');
  const previewTitle = document.getElementById('preview-video-title');
  const previewLength = document.getElementById('preview-video-length');
  const previewContainer = document.getElementById('preview-video-container');

  // Real-time title update
  titleField?.addEventListener('input', () => {
    if (previewTitle) {
      previewTitle.textContent = titleField.value.trim() || 'Video Title Preview';
    }
  });

  // Switch between Upload file and Embed url
  window.switchUploadType = function(type) {
    uploadTypeInput.value = type;
    if (type === 'file') {
      tabFileBtn.classList.add('active');
      tabEmbedBtn.classList.remove('active');
      fileContainer.style.display = 'block';
      embedContainer.style.display = 'none';
      input.required = true;
      document.getElementById('embed-url-input').required = false;
    } else {
      tabFileBtn.classList.remove('active');
      tabEmbedBtn.classList.add('active');
      fileContainer.style.display = 'none';
      embedContainer.style.display = 'block';
      input.required = false;
      document.getElementById('embed-url-input').required = true;
    }
    resetPreview();
  };

  function resetPreview() {
    previewContainer.innerHTML = `<svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>`;
    previewLength.textContent = '0:00';
  }

  // File drag & drop handlers
  if (zone && input) {
    ['dragover','dragenter'].forEach(e => zone.addEventListener(e, ev => { ev.preventDefault(); zone.classList.add('drag-over'); }));
    ['dragleave','drop'].forEach(e => zone.addEventListener(e, ev => { ev.preventDefault(); zone.classList.remove('drag-over'); }));
    zone.addEventListener('drop', e => { if(e.dataTransfer.files[0]) { input.files = e.dataTransfer.files; updateVideoFile(e.dataTransfer.files[0]); } });
    input.addEventListener('change', () => { if(input.files[0]) updateVideoFile(input.files[0]); });
  }

  function updateVideoFile(f) {
    const zt = document.getElementById('video-zone-text');
    if (zt) { zt.textContent = '✓ ' + f.name + ' (' + (f.size/1048576).toFixed(1) + ' MB)'; }
    if (zone) zone.style.borderColor = 'var(--green)';
    
    // Auto-fill title with filename without extension if title is empty
    if (titleField && !titleField.value.trim()) {
      const nameWithoutExt = f.name.substring(0, f.name.lastIndexOf('.')) || f.name;
      titleField.value = nameWithoutExt.substring(0, 100);
      titleField.dispatchEvent(new Event('input'));
    }

    const durInput = document.getElementById('duration-seconds');
    if (!durInput || !f) return;
    durInput.value = '0';

    const blobUrl = URL.createObjectURL(f);
    
    // Render preview player for local file
    previewContainer.innerHTML = `<video id="preview-direct-player" style="width:100%; height:100%; object-fit:cover" controls></video>`;
    const playerEl = document.getElementById('preview-direct-player');
    playerEl.src = blobUrl;
    playerEl.style.display = 'block';

    playerEl.addEventListener('loadedmetadata', () => {
      const d = Math.max(1, Math.floor(playerEl.duration || 0));
      durInput.value = String(d);
      
      const m = Math.floor(d / 60), s = d % 60;
      const formattedTime = m + ':' + String(s).padStart(2, '0');
      
      previewLength.textContent = formattedTime;
      if (zt) {
        zt.textContent += ' — length ' + formattedTime;
      }
    }, {once: true});
  }

  function getYoutubeId(url) {
    if (!url) return null;
    const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
    const match = url.match(regExp);
    return (match && match[2].length === 11) ? match[2] : null;
  }

  // Probe & preview embedded URLs
  window.probeEmbedLink = function() {
    const urlVal = document.getElementById('embed-url-input').value.trim();
    if (!urlVal) {
      alert('Please enter a link to verify.');
      return;
    }

    const ytId = getYoutubeId(urlVal);
    const durInputEmbed = document.getElementById('duration-seconds-embed');

    if (ytId) {
      // Render YouTube iframe preview
      previewContainer.innerHTML = `<iframe src="https://www.youtube.com/embed/${ytId}" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen style="width:100%; height:100%; border:none"></iframe>`;
      previewLength.textContent = 'YouTube Embed';
      
      // Auto-fetch YouTube title via JSONP/no-key oEmbed (extremely premium feature!)
      fetch(`https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=${ytId}&format=json`)
        .then(r => r.json())
        .then(data => {
          if (data && data.title && titleField && !titleField.value.trim()) {
            titleField.value = data.title;
            titleField.dispatchEvent(new Event('input'));
          }
        }).catch(() => {});
        
      if (durInputEmbed) durInputEmbed.value = '0'; // default for YouTube embeds
    } else if (urlVal.match(/\.(mp4|webm|mov|ogg)(\?.*)?$/i) || urlVal.includes('stream') || urlVal.startsWith('http')) {
      // Render direct video element preview
      previewContainer.innerHTML = `<video id="preview-direct-player" style="width:100%; height:100%; object-fit:cover" controls></video>`;
      const playerEl = document.getElementById('preview-direct-player');
      playerEl.src = urlVal;
      playerEl.style.display = 'block';
      
      playerEl.addEventListener('loadedmetadata', () => {
        const d = Math.max(1, Math.floor(playerEl.duration || 0));
        if (durInputEmbed) durInputEmbed.value = String(d);
        const m = Math.floor(d / 60), s = d % 60;
        previewLength.textContent = m + ':' + String(s).padStart(2, '0');
      }, {once: true});

      playerEl.addEventListener('error', () => {
        previewLength.textContent = 'Direct Video Link';
      });
    } else {
      alert('Link format is verified but preview is not supported. You can still upload.');
      previewLength.textContent = 'External Embed';
    }
  };
})();
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
