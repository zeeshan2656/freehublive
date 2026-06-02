<?php
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

        if (strlen($title) < 3) { 
            $error = 'Reel Title must be at least 3 characters.'; 
        } elseif (empty($_FILES['video']['name'])) {
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
<div class="container" style="max-width: 680px; margin: 0 auto; padding: 24px 16px;">
  
  <div class="card" style="padding: 32px; border-radius: 16px; border: 1px solid var(--border); background: var(--bg2); position: relative; overflow: hidden; box-shadow: var(--shadow);">
    <div style="position:absolute; top:-10px; right:-10px; opacity:.03; font-size:6rem; transform:rotate(15deg)">🎬</div>
    <h2 style="font-weight: 800; font-size: 1.6rem; color: var(--text); margin-bottom: 6px;">🎬 Upload short vertical Reel</h2>
    <p class="text-muted text-sm" style="margin-bottom: 28px;">Share engaging vertical short videos. Keep it under 60 seconds for the best user experience.</p>
    
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    
    <form id="reel-upload-form" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      
      <!-- Video Drag & Drop Zone -->
      <div class="form-group" style="margin-bottom: 24px;">
        <label class="form-label" style="font-weight: 700;">Reel Video File <span style="color:var(--red)">*</span></label>
        <div id="drop-zone" class="upload-dropzone" style="border: 2px dashed var(--border); border-radius: 12px; padding: 48px 16px; text-align: center; cursor: pointer; background: var(--bg3); transition: all 0.2s;">
          <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin: 0 auto 12px; color: var(--accent); transition: transform 0.2s;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          <span style="font-weight: 600; display: block; font-size: 0.9rem; color: var(--text);" id="file-label">Drag & drop video file or click to browse</span>
          <span class="text-xs text-muted" style="margin-top: 4px; display: block;">Supports MP4, WebM, MOV up to 500MB</span>
          <input type="file" name="video" id="video-input" accept="video/mp4,video/webm,video/quicktime" required style="display: none;">
        </div>
        <div id="video-preview-wrapper" style="display: none; margin-top: 14px; padding: 10px; border-radius: 8px; background: rgba(99, 102, 241, 0.05); border: 1px solid rgba(99, 102, 241, 0.15); align-items: center; justify-content: space-between;">
          <div class="flex gap-2" style="align-items: center; min-width: 0;">
            <div id="canvas-preview-container" style="width: 44px; aspect-ratio: 9/16; border-radius: 4px; overflow: hidden; background: #000; flex-shrink: 0; border: 1px solid var(--border)">
              <canvas id="thumb-canvas" style="width: 100%; height: 100%; object-fit: cover; display: block;"></canvas>
            </div>
            <div style="min-width: 0;">
              <div id="video-filename" style="font-size: 0.8rem; font-weight: 700; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 320px;">Filename.mp4</div>
              <div id="video-duration" style="font-size: 0.72rem; color: var(--text2); margin-top: 2px;">Duration: Probing...</div>
            </div>
          </div>
          <button type="button" id="remove-file-btn" class="btn btn-outline btn-sm" style="border: none; padding: 4px 8px; font-weight: bold; color: var(--red); background: rgba(239, 68, 68, 0.08); border-radius: 6px;">Remove</button>
        </div>
      </div>
      
      <!-- Hidden File input for extracted thumbnail blob -->
      <input type="file" name="extracted_thumb" id="extracted-thumb-input" style="display: none;">
      
      <!-- Reel Title -->
      <div class="form-group" style="margin-bottom: 20px;">
        <label class="form-label" style="font-weight: 700;">Reel Title <span style="color:var(--red)">*</span></label>
        <input type="text" name="title" class="form-input" placeholder="Give your reel a catchy title..." minlength="3" required style="border-radius: 8px;">
      </div>
      
      <!-- Visibility -->
      <div class="form-group" style="margin-bottom: 28px;">
        <label class="form-label" style="font-weight: 700;">Visibility</label>
        <select class="form-input form-select" name="visibility" style="border-radius: 8px;">
          <option value="public">🌐 Public (everyone can watch)</option>
          <option value="unlisted">🔗 Unlisted (anyone with link can watch)</option>
          <option value="private">🔒 Private (only you can watch)</option>
        </select>
      </div>
      
      <!-- Form CTA buttons -->
      <div class="flex gap-2" style="justify-content: flex-end; align-items: center; border-top: 1px solid var(--border); padding-top: 20px;">
        <a href="<?= BASE_URL ?>/creator/" class="btn btn-outline" style="border-radius: 8px; padding: 10px 22px;">Cancel</a>
        <button type="submit" id="submit-btn" class="btn btn-primary" style="border-radius: 8px; padding: 10px 24px; font-weight: 600;">Publish Reel</button>
      </div>
      
    </form>
  </div>
  
</div>
</main>
</div>

<script>
const dropZone = document.getElementById('drop-zone');
const videoInput = document.getElementById('video-input');
const fileLabel = document.getElementById('file-label');
const previewWrapper = document.getElementById('video-preview-wrapper');
const videoFilename = document.getElementById('video-filename');
const videoDuration = document.getElementById('video-duration');
const removeBtn = document.getElementById('remove-file-btn');
const thumbCanvas = document.getElementById('thumb-canvas');
const extractedThumbInput = document.getElementById('extracted-thumb-input');
const submitBtn = document.getElementById('submit-btn');

// Drag & Drop
dropZone.addEventListener('click', () => videoInput.click());
dropZone.addEventListener('dragover', (e) => {
  e.preventDefault();
  dropZone.classList.add('drag-over');
});
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
dropZone.addEventListener('drop', (e) => {
  e.preventDefault();
  dropZone.classList.remove('drag-over');
  if (e.dataTransfer.files.length) {
    videoInput.files = e.dataTransfer.files;
    handleVideoSelect();
  }
});

videoInput.addEventListener('change', handleVideoSelect);

removeBtn.addEventListener('click', () => {
  videoInput.value = '';
  extractedThumbInput.value = '';
  previewWrapper.style.display = 'none';
  dropZone.style.display = 'block';
});

function handleVideoSelect() {
  const file = videoInput.files[0];
  if (!file) return;
  
  videoFilename.textContent = file.name;
  dropZone.style.display = 'none';
  previewWrapper.style.display = 'flex';
  
  // Extract first frame
  const videoEl = document.createElement('video');
  videoEl.preload = 'metadata';
  videoEl.muted = true;
  videoEl.playsInline = true;
  
  const objectUrl = URL.createObjectURL(file);
  videoEl.src = objectUrl;
  
  videoEl.addEventListener('loadedmetadata', () => {
    // Show duration
    const durSecs = Math.round(videoEl.duration);
    videoDuration.textContent = `Duration: ${durSecs}s`;
    
    // Seek to 0.1s to load frame
    videoEl.currentTime = 0.1;
  });
  
  videoEl.addEventListener('seeked', () => {
    const ctx = thumbCanvas.getContext('2d');
    
    // Scale canvas to video frame aspect ratio
    const videoWidth = videoEl.videoWidth || 720;
    const videoHeight = videoEl.videoHeight || 1280;
    
    thumbCanvas.width = 180;
    thumbCanvas.height = Math.round(180 * (videoHeight / videoWidth));
    
    // Draw frame onto canvas
    ctx.drawImage(videoEl, 0, 0, thumbCanvas.width, thumbCanvas.height);
    
    // Convert to file blob
    thumbCanvas.toBlob((blob) => {
      if (blob) {
        const extFile = new File([blob], 'reel_extracted_thumb.jpg', { type: 'image/jpeg' });
        
        // Append to extracted thumb input using DataTransfer
        const dt = new DataTransfer();
        dt.items.add(extFile);
        extractedThumbInput.files = dt.files;
        
        console.log("Client-side first frame thumbnail extracted successfully!");
      }
    }, 'image/jpeg', 0.85);
    
    // Clean memory URL
    URL.revokeObjectURL(objectUrl);
  });
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
