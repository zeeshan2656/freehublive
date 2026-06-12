<?php
// ============================================================
// FreeHub.Live — Creator Edit Video
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin', 'creator']);

$site_theme = setting('active_theme', 'dark-minimal');
$primary    = setting('primary_color', '#6366f1');

$uid = auth_user()['id'];
$vid = (int)($_GET['id'] ?? 0);

// Fetch video (owner check)
$video = db_fetch("SELECT * FROM videos WHERE id=? AND (user_id=? OR ? IN (SELECT id FROM users WHERE role='admin' AND id=?))",
    [$vid, $uid, $uid, $uid]);

// For admins, allow editing any video
if (!$video && auth_user()['role'] === 'admin') {
    $video = db_fetch("SELECT * FROM videos WHERE id=?", [$vid]);
}

if (!$video) { http_response_code(403); die('Not found or access denied.'); }

$selected_categories = array_column(
    db_fetchAll("SELECT category_id FROM video_categories WHERE video_id=?", [$vid]),
    'category_id'
);
if (empty($selected_categories) && $video['category_id']) {
    $selected_categories = [(int)$video['category_id']];
}

$video_owner_id = (int)$video['user_id'];

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) { $error = 'Invalid request.'; }
    else {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $tags        = trim($_POST['tags'] ?? '');
        $category_ids = array_map('intval', $_POST['category_ids'] ?? []);
        $category_id  = !empty($category_ids) ? $category_ids[0] : 0;
        $visibility  = in_array($_POST['visibility'] ?? '', ['public','unlisted','private']) ? $_POST['visibility'] : 'public';
        $allow_comments = isset($_POST['allow_comments']) ? 1 : 0;


        if (strlen($title) < 3) {
            $error = 'Title must be at least 3 characters.';
        } else {
            $updateData = [
                'title'          => $title,
                'description'    => $description,
                'tags'           => $tags,
                'category_id'    => $category_id ?: null,
                'visibility'     => $visibility,
                'allow_comments' => $allow_comments,
            ];

            // Thumbnail management is now handled immediately via AJAX.
            // No file upload or base64 decoding is needed on final form submission.

            if (!$error) {
                db_update('videos', $updateData, 'id=?', [$vid]);
                
                // Sync categories
                db_query("DELETE FROM video_categories WHERE video_id=?", [$vid]);
                if (!empty($category_ids)) {
                    foreach ($category_ids as $cid) {
                        db_insert('video_categories', [
                            'video_id'    => $vid,
                            'category_id' => $cid
                        ]);
                    }
                }


                
                // Refresh video data and selected categories/playlists
                $video = db_fetch("SELECT * FROM videos WHERE id=?", [$vid]);
                $selected_categories = array_column(
                    db_fetchAll("SELECT category_id FROM video_categories WHERE video_id=?", [$vid]),
                    'category_id'
                );
                if (empty($selected_categories) && $video['category_id']) {
                    $selected_categories = [(int)$video['category_id']];
                }


                
                $success = 'Video updated successfully!';
            }
        }
    }
}
$categories = db_fetchAll("SELECT * FROM categories WHERE is_active=1 ORDER BY sort_order");
$meta_title = 'Edit Video';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <!-- Breadcrumb -->
    <div class="flex gap-2 text-sm text-muted" style="margin-bottom:20px">
      <a href="<?= BASE_URL ?>/creator/videos.php" style="color:var(--accent)">&#8592; My Videos</a>
      <span>/</span>
      <span>Edit: <?= e(truncate($video['title'], 40)) ?></span>
    </div>

    <div class="flex" style="justify-content:space-between;align-items:center;margin-bottom:24px">
      <h1 style="font-size:1.3rem;font-weight:800">Edit Video</h1>
      <div class="flex gap-2">
        <span class="status-badge status-<?= $video['status'] ?>"><?= ucfirst($video['status']) ?></span>
        <a href="<?= BASE_URL ?>/watch.php?v=<?= $vid ?>" target="_blank" class="btn btn-outline btn-sm">&#128065; Preview</a>
      </div>
    </div>

    <?php if ($error):   ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success">&#10003; <?= e($success) ?></div><?php endif; ?>

    <form id="video-edit-form" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

      <div class="video-edit-grid">

        <!-- Left: Main Details -->
        <div>
          <div class="card" style="margin-bottom:20px">
            <h2 style="font-size:1rem;font-weight:700;margin-bottom:16px">Video Details</h2>

            <div class="form-group">
              <label class="form-label">Title *</label>
              <input class="form-input" type="text" name="title" required maxlength="200"
                     value="<?= e($video['title']) ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Description</label>
              <textarea class="form-input" name="description" rows="6"
                        style="resize:vertical"><?= e($video['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
              <label class="form-label">Tags <span class="text-muted">(comma separated)</span></label>
              <input class="form-input" type="text" name="tags" maxlength="500"
                     value="<?= e($video['tags'] ?? '') ?>" placeholder="gaming, tutorial, funny">
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px;margin-top:12px">
              <div class="form-group">
                <label class="form-label">Categories (Select one or more)</label>
                <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(130px, 1fr));gap:10px;background:var(--bg3);padding:12px;border-radius:var(--radius);border:1px solid var(--border);max-height:150px;overflow-y:auto">
                  <?php foreach ($categories as $c): ?>
                  <label class="flex gap-2" style="font-size:.85rem;cursor:pointer;user-select:none;align-items:center">
                    <input type="checkbox" name="category_ids[]" value="<?= $c['id'] ?>" <?= in_array($c['id'], $selected_categories) ? 'checked' : '' ?>>
                    <span><?= e($c['name']) ?></span>
                  </label>
                  <?php endforeach; ?>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Visibility</label>
                <select class="form-input form-select" name="visibility">
                  <?php foreach (['public','unlisted','private'] as $vis): ?>
                  <option value="<?= $vis ?>" <?= $video['visibility']===$vis?'selected':'' ?>>
                    <?= ucfirst($vis) ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="flex gap-3" style="align-items:center;padding:12px;background:var(--bg3);border-radius:var(--radius)">
              <input type="checkbox" id="allow_comments" name="allow_comments" value="1"
                     <?= $video['allow_comments'] ? 'checked' : '' ?> style="width:16px;height:16px;accent-color:var(--accent)">
              <label for="allow_comments" style="font-size:.875rem;cursor:pointer">Allow Comments</label>
            </div>
          </div>

          <!-- Video Stats (read-only) -->
          <div class="card">
            <h2 style="font-size:1rem;font-weight:700;margin-bottom:16px">Video Stats</h2>
            <div class="stat-grid-4" style="gap:12px">
              <?php
              $stats = [
                ['&#128065;', 'Views',    format_number((int)$video['views'])],
                ['&#128077;', 'Likes',    format_number((int)$video['likes'])],
                ['&#128172;', 'Comments', format_number((int)$video['comments_count'])],
                ['&#128190;', 'Size',     format_filesize((int)$video['file_size'])],
              ];
              foreach ($stats as [$icon, $label, $val]):
              ?>
              <div style="background:var(--bg3);border-radius:var(--radius);padding:14px;text-align:center">
                <div style="font-size:1.2rem;margin-bottom:4px"><?= $icon ?></div>
                <div style="font-size:1.1rem;font-weight:800"><?= $val ?></div>
                <div class="text-xs text-muted"><?= $label ?></div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Right: Thumbnail + Actions -->
        <div>
          <?php if (!(int)$video['is_reel']): ?>
          <style>
          @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
          }
          .thumb-item {
            transition: transform 0.15s ease, border-color 0.15s ease;
          }
          .thumb-item:hover {
            transform: scale(1.02);
            border-color: var(--text3) !important;
          }
          .thumb-item.selected {
            border-color: var(--accent) !important;
            box-shadow: 0 0 10px rgba(99, 102, 241, 0.4);
          }
          </style>
          <div class="card" style="margin-bottom:16px">
            <h3 style="font-weight:700;margin-bottom:4px;font-size:.95rem">&#128444; Thumbnail Management</h3>
            <p class="text-xs text-muted" style="margin-bottom:16px;">Select a frame from the video or upload a custom image. Only one thumbnail remains associated with the video.</p>
            
            <input type="hidden" name="selected_generated_thumbnail" id="selected-generated-thumbnail-input" value="">
            
            <div class="thumb-management-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(110px, 1fr)); gap:10px; margin-bottom:16px;">
              <!-- 1 Currently Saved Thumbnail -->
              <div class="thumb-item active-original selected" id="thumb-item-original" onclick="selectOriginalThumb()" style="position:relative; aspect-ratio:16/9; border-radius:8px; overflow:hidden; border:3px solid var(--accent); box-shadow: 0 0 12px rgba(99, 102, 241, 0.4); cursor:pointer; background:#000;">
                <img src="<?= thumb_url($video['thumbnail'], $video['video_url']) ?>" id="thumb-preview-img" style="width:100%; height:100%; object-fit:cover;">
                <div class="thumb-badge" style="position:absolute; top:4px; left:4px; background:var(--green); color:#fff; font-size:9px; padding:2px 5px; border-radius:4px; font-weight:700; box-shadow: 0 1px 3px rgba(0,0,0,0.5);">Saved Thumbnail</div>
                <div class="thumb-select-check" style="position:absolute; top:4px; right:4px; width:16px; height:16px; background:var(--accent); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-size:9px; font-weight:bold;">✓</div>
              </div>

              <!-- 9 Generated Thumbnails -->
              <?php for ($i = 1; $i <= 9; $i++): ?>
              <div class="thumb-item generated-thumb-item loading" id="thumb-item-gen-<?= $i ?>" onclick="selectGeneratedThumb(<?= $i ?>)" style="position:relative; aspect-ratio:16/9; border-radius:8px; overflow:hidden; border:2.5px solid transparent; cursor:pointer; background:var(--bg3); display:flex; align-items:center; justify-content:center;">
                <div class="thumb-spinner" style="font-size:1.2rem; animation: spin 1s linear infinite; color:var(--text2);">&#8635;</div>
                <img style="width:100%; height:100%; object-fit:cover; display:none;">
                <div class="thumb-badge" style="position:absolute; top:4px; left:4px; background:rgba(0,0,0,0.65); border:1px solid rgba(255,255,255,0.15); color:#fff; font-size:9px; padding:2px 5px; border-radius:4px; font-weight:700; display:none;">Temporary Frame <?= $i ?></div>
                <div class="thumb-select-check" style="position:absolute; top:4px; right:4px; width:16px; height:16px; background:var(--accent); border-radius:50%; display:none; align-items:center; justify-content:center; color:#fff; font-size:9px; font-weight:bold;">✓</div>
              </div>
              <?php endfor; ?>
            </div>

            <div class="flex gap-2" style="align-items:center;">
              <label class="btn btn-outline btn-sm w-full" style="justify-content:center; cursor:pointer;" for="thumb-input">
                &#128247; Upload Custom Thumbnail
              </label>
              <input type="file" id="thumb-input" name="thumbnail" accept="image/jpeg,image/png,image/webp" style="display:none">
            </div>
            <p class="text-xs text-muted" style="margin-top:8px; text-align:center;">JPG, PNG, WebP — 1280×720 recommended</p>
          </div>
          <?php endif; ?>

          <div class="card" style="margin-bottom:16px">
            <h3 style="font-weight:700;margin-bottom:12px;font-size:.95rem">&#128279; Video Info</h3>
            <div style="font-size:.8rem;color:var(--text2);line-height:1.8">
              <div><strong>Duration:</strong> <?= format_duration((int)$video['duration']) ?></div>
              <div><strong>Uploaded:</strong> <?= date('M j, Y', strtotime($video['created_at'])) ?></div>
              <div><strong>Video ID:</strong> #<?= $vid ?></div>
              <?php if ($video['status'] === 'published' && $video['published_at']): ?>
              <div><strong>Published:</strong> <?= date('M j, Y', strtotime($video['published_at'])) ?></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="flex gap-2" style="flex-direction:column">
            <button type="submit" class="btn btn-primary w-full" style="justify-content:center;padding:12px">
              &#10003; Save Changes
            </button>
            <a href="<?= BASE_URL ?>/creator/videos.php" class="btn btn-outline w-full" style="justify-content:center">
              Cancel
            </a>
          </div>
        </div>

      </div>
    </form>
</div>

<canvas id="offscreen-canvas" style="display:none"></canvas>

<script data-page-script="true">
(function() {
  const hiddenInput = document.getElementById('selected-generated-thumbnail-input');
  const thumbInput = document.getElementById('thumb-input');

  // ── Selection Functions ──
  window.selectOriginalThumb = function() {
    document.querySelectorAll('.thumb-management-grid .thumb-item').forEach(el => {
      el.classList.remove('selected');
      el.style.borderColor = 'transparent';
      el.style.boxShadow = 'none';
      const check = el.querySelector('.thumb-select-check');
      if (check) {
        check.style.display = 'none';
        check.style.animation = '';
        check.innerHTML = '✓';
      }
    });
    
    const originalItem = document.getElementById('thumb-item-original');
    if (originalItem) {
      originalItem.classList.add('selected');
      originalItem.style.borderColor = 'var(--accent)';
      originalItem.style.boxShadow = '0 0 12px rgba(99, 102, 241, 0.4)';
      const check = originalItem.querySelector('.thumb-select-check');
      if (check) {
        check.style.display = 'flex';
        check.innerHTML = '✓';
        check.style.animation = '';
      }
      
      const badge = originalItem.querySelector('.thumb-badge');
      if (badge) {
        badge.textContent = "Saved Thumbnail";
        badge.style.background = "var(--green)";
      }
    }
    if (hiddenInput) hiddenInput.value = '';
  };

  window.selectGeneratedThumb = async function(index) {
    const item = document.getElementById('thumb-item-gen-' + index);
    if (!item || item.classList.contains('loading')) return;

    document.querySelectorAll('.thumb-management-grid .thumb-item').forEach(el => {
      el.classList.remove('selected');
      el.style.borderColor = 'transparent';
      el.style.boxShadow = 'none';
      const check = el.querySelector('.thumb-select-check');
      if (check) {
        check.style.display = 'none';
        check.style.animation = '';
        check.innerHTML = '✓';
      }
    });

    item.classList.add('selected');
    item.style.borderColor = 'var(--accent)';
    item.style.boxShadow = '0 0 12px rgba(99, 102, 241, 0.4)';
    const check = item.querySelector('.thumb-select-check');
    if (check) {
      check.style.display = 'flex';
      check.innerHTML = '✓';
    }

    // Show loading spinner on the active original thumbnail check
    const origCheck = document.querySelector('#thumb-item-original .thumb-select-check');
    if (origCheck) {
      origCheck.style.display = 'flex';
      origCheck.innerHTML = '&#8635;';
      origCheck.style.animation = 'spin 1s linear infinite';
    }

    const dataUrl = item.dataset.thumbData;
    if (!dataUrl) return;

    try {
      const response = await fetch('<?= BASE_URL ?>/api/thumbnails.php?action=save_thumbnail', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          video_id: <?= $vid ?>,
          data_url: dataUrl
        })
      });
      const result = await response.json();
      if (result.success && result.thumbnail_url) {
        // Replace original thumbnail preview
        const previewImg = document.getElementById('thumb-preview-img');
        if (previewImg) previewImg.src = result.thumbnail_url;
        
        // Success notification banner
        showToast('Thumbnail replaced and database updated successfully!');
      } else {
        alert(result.error || 'Failed to save thumbnail.');
      }
    } catch (err) {
      console.error(err);
      alert('Error saving thumbnail.');
    } finally {
      selectOriginalThumb();
    }
  };

  // ── Custom File Upload Handler (AJAX implementation) ──
  if (thumbInput) {
    thumbInput.addEventListener('change', function() {
      if (!this.files[0]) return;

      const reader = new FileReader();
      reader.onload = async e => {
        const dataUrl = e.target.result;

        // Show loading spinner on the active original thumbnail check
        const origCheck = document.querySelector('#thumb-item-original .thumb-select-check');
        if (origCheck) {
          origCheck.style.display = 'flex';
          origCheck.innerHTML = '&#8635;';
          origCheck.style.animation = 'spin 1s linear infinite';
        }

        try {
          const response = await fetch('<?= BASE_URL ?>/api/thumbnails.php?action=save_thumbnail', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              video_id: <?= $vid ?>,
              data_url: dataUrl
            })
          });
          const result = await response.json();
          if (result.success && result.thumbnail_url) {
            const previewImg = document.getElementById('thumb-preview-img');
            if (previewImg) previewImg.src = result.thumbnail_url;
            showToast('Custom thumbnail uploaded and saved successfully!');
          } else {
            alert(result.error || 'Failed to upload custom thumbnail.');
          }
        } catch (err) {
          console.error(err);
          alert('Error uploading custom thumbnail.');
        } finally {
          selectOriginalThumb();
        }
      };
      reader.readAsDataURL(this.files[0]);
    });
  }

  function showToast(msg) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success fade-in';
    alertDiv.style.position = 'fixed';
    alertDiv.style.bottom = '20px';
    alertDiv.style.right = '20px';
    alertDiv.style.zIndex = '9999';
    alertDiv.style.boxShadow = '0 4px 12px rgba(0,0,0,0.5)';
    alertDiv.innerHTML = '&#10003; ' + msg;
    document.body.appendChild(alertDiv);
    setTimeout(() => {
      alertDiv.style.opacity = '0';
      alertDiv.style.transition = 'opacity 0.5s';
      setTimeout(() => alertDiv.remove(), 500);
    }, 3000);
  }

  // ── Client-side Frame Extraction ──
  <?php if (!(int)$video['is_reel']): ?>
  const videoSrc = '<?= BASE_URL ?>/api/stream.php?v=<?= $vid ?>';
  const tempVideo = document.createElement('video');
  tempVideo.src = videoSrc;
  tempVideo.crossOrigin = 'anonymous';
  tempVideo.muted = true;
  tempVideo.playsInline = true;

  tempVideo.onloadedmetadata = async () => {
    const duration = tempVideo.duration || 0;
    if (duration <= 0) return;

    const step = duration / 10;
    const canvas = document.getElementById('offscreen-canvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');

    const vWidth = tempVideo.videoWidth || 640;
    const vHeight = tempVideo.videoHeight || 360;
    canvas.width = vWidth;
    canvas.height = vHeight;

    for (let i = 1; i <= 9; i++) {
      try {
        const time = step * i;
        const dataUrl = await seekAndCapture(tempVideo, canvas, ctx, time);
        renderGeneratedThumb(i, dataUrl);
      } catch (e) {
        console.error("Frame capture error at step " + i + ":", e);
      }
    }
  };

  function seekAndCapture(video, canvas, ctx, time) {
    return new Promise((resolve, reject) => {
      const onSeeked = () => {
        video.removeEventListener('seeked', onSeeked);
        video.removeEventListener('error', onError);
        try {
          ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
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

  function renderGeneratedThumb(index, dataUrl) {
    const item = document.getElementById('thumb-item-gen-' + index);
    if (!item) return;
    item.classList.remove('loading');
    
    const spinner = item.querySelector('.thumb-spinner');
    if (spinner) spinner.style.display = 'none';

    const img = item.querySelector('img');
    if (img) {
      img.src = dataUrl;
      img.style.display = 'block';
    }

    const badge = item.querySelector('.thumb-badge');
    if (badge) badge.style.display = 'block';

    item.dataset.thumbData = dataUrl;
  }
  <?php endif; ?>

})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
