<?php
// Admin — Edit Video
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$vid = (int)($_GET['id'] ?? 0);
$video = db_fetch(
    "SELECT v.*, u.username, u.channel_name FROM videos v
     JOIN users u ON u.id = v.user_id WHERE v.id=?",
    [$vid]
);
if (!$video) {
    flash('error', 'Video not found.');
    redirect(BASE_URL . '/admin/videos.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? '')) {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status      = in_array($_POST['status'] ?? '', ['published','pending','draft','rejected']) ? $_POST['status'] : $video['status'];
    $cat_id      = (int)($_POST['category_id'] ?? $video['category_id'] ?? 0);
    $tags        = trim($_POST['tags'] ?? '');
    $featured    = (int)($_POST['featured'] ?? 0);
    $note        = trim($_POST['approval_note'] ?? '');
    $visibility  = in_array($_POST['visibility'] ?? '', ['public', 'unlisted', 'private']) ? $_POST['visibility'] : $video['visibility'];

    if (empty($title)) {
        $error = 'Title is required.';
    } else {
        $updateData = [
            'title'         => $title,
            'description'   => $description,
            'status'        => $status,
            'category_id'   => $cat_id ?: null,
            'tags'          => $tags,
            'featured'      => $featured ? 1 : 0,
            'approval_note' => $note ?: null,
            'visibility'    => $visibility,
        ];

        // Handle published_at
        if ($status === 'published' && empty($video['published_at'])) {
            $updateData['published_at'] = date('Y-m-d H:i:s');
        }

        // Thumbnail management is now handled immediately via AJAX.
        // No file upload is needed on final form submission.

        if (!$error) {
            db_update('videos', $updateData, 'id=?', [$vid]);
            // If featured, unfeature others
            if ($featured) db_update('videos', ['featured' => 0], 'id!=?', [$vid]);
            flash('success', 'Video updated successfully.');
            redirect(BASE_URL . '/admin/video_edit.php?id=' . $vid);
        }
    }

    $video = db_fetch("SELECT v.*, u.username, u.channel_name FROM videos v JOIN users u ON u.id=v.user_id WHERE v.id=?", [$vid]);
}

$categories = db_fetchAll("SELECT id, name FROM categories WHERE is_active=1 ORDER BY name");
$meta_title = 'Edit Video';
require_once __DIR__ . '/partials/admin_head.php';
?>
<div class="admin-content">
  <div class="admin-page-header">
    <div>
      <a href="<?= BASE_URL ?>/admin/videos.php" style="color:var(--text2);font-size:.8rem">← All Videos</a>
    </div>
    <div class="flex gap-2">
      <a href="<?= BASE_URL ?>/watch.php?v=<?= $vid ?>" target="_blank" class="btn btn-outline btn-sm">👁️ Preview</a>
      <a href="<?= BASE_URL ?>/admin/users.php?view=<?= $video['user_id'] ?>" class="btn btn-outline btn-sm">👤 Creator</a>
    </div>
  </div>

  <?php foreach (get_flash() as $f): ?><div class="alert alert-<?= $f['type'] ?>"><?= e($f['msg']) ?></div><?php endforeach; ?>
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

  <div class="video-edit-grid">
    <!-- Main Form -->
    <div>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="card" style="margin-bottom:16px">
          <h3 style="font-weight:700;margin-bottom:16px">Video Details</h3>
          <div class="form-group">
            <label class="form-label">Title *</label>
            <input class="form-input" type="text" name="title" value="<?= e($video['title'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Description</label>
            <textarea class="form-input" name="description" rows="5"><?= e($video['description'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Tags (comma-separated)</label>
            <input class="form-input" type="text" name="tags" value="<?= e($video['tags'] ?? '') ?>" placeholder="tag1, tag2, tag3">
          </div>
          <div class="stat-grid-2">
            <div class="form-group">
              <label class="form-label">Category</label>
              <select class="form-input form-select" name="category_id">
                <option value="">None</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= (int)($video['category_id']??0)===$cat['id']?'selected':'' ?>><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Status</label>
              <select class="form-input form-select" name="status">
                <?php foreach (['published','pending','draft','rejected'] as $s): ?>
                <option value="<?= $s ?>" <?= $video['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Visibility</label>
            <select class="form-input form-select" name="visibility">
              <?php foreach (['public','unlisted','private'] as $vis): ?>
              <option value="<?= $vis ?>" <?= $video['visibility']===$vis?'selected':'' ?>><?= ucfirst($vis) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Admin Approval Note</label>
            <input class="form-input" type="text" name="approval_note" value="<?= e($video['approval_note'] ?? '') ?>" placeholder="Reason for rejection or note to creator">
          </div>
        </div>

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

        <div class="card" style="margin-bottom:16px">
          <h3 style="font-weight:700;margin-bottom:12px">Options</h3>
          <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
            <input type="checkbox" name="featured" value="1" <?= !empty($video['featured'])&&$video['featured']?'checked':'' ?>>
            <span>⭐ Feature this video on homepage</span>
          </label>
        </div>

        <div class="flex gap-3">
          <button type="submit" class="btn btn-primary">Save Changes</button>
          <a href="<?= BASE_URL ?>/admin/videos.php" class="btn btn-outline">Cancel</a>
        </div>
      </form>
    </div>

    <!-- Right Sidebar Info -->
    <div>
      <div class="card" style="margin-bottom:16px; padding:0; overflow:hidden; border:1px solid var(--border); border-radius:12px;">
        <div style="position:relative; aspect-ratio:16/9; background:#000; display:flex; align-items:center; justify-content:center;">
          <?php
          $video_url = $video['video_url'] ?? '';
          $yt_id = fh_youtube_id($video_url);
          if ($yt_id):
          ?>
            <iframe width="100%" height="100%" src="https://www.youtube.com/embed/<?= e($yt_id) ?>?rel=0" frameborder="0" allowfullscreen style="border:none; position:absolute; top:0; left:0; width:100%; height:100%;"></iframe>
          <?php elseif (str_starts_with($video_url, 'http')): ?>
            <iframe width="100%" height="100%" src="<?= e($video_url) ?>" frameborder="0" allowfullscreen style="border:none; position:absolute; top:0; left:0; width:100%; height:100%;"></iframe>
          <?php else: ?>
            <video controls poster="<?= thumb_url($video['thumbnail'], $video['video_url']) ?>" style="width:100%; height:100%; object-fit:contain;">
              <source src="<?= e(video_url($video_url)) ?>" type="video/mp4">
            </video>
          <?php endif; ?>
        </div>
      </div>

      <div class="card" style="margin-bottom:16px">
        <h3 style="font-weight:700;margin-bottom:12px;font-size:.9rem">Video Info</h3>
        <div style="font-size:.82rem;display:flex;flex-direction:column;gap:8px">
          <div><span class="text-muted">ID:</span> #<?= $vid ?></div>
          <div><span class="text-muted">Creator:</span> <a href="<?= BASE_URL ?>/admin/users.php?view=<?= $video['user_id'] ?>" style="color:var(--accent)"><?= e($video['channel_name'] ?? $video['username']) ?></a></div>
          <div><span class="text-muted">Views:</span> <?= format_number((int)$video['views']) ?></div>
          <div><span class="text-muted">Watch Time:</span> <?= format_duration((int)($video['watch_time'] ?? 0)) ?></div>
          <div><span class="text-muted">Duration:</span> <?= format_duration((int)($video['duration'] ?? 0)) ?></div>
          <div><span class="text-muted">Uploaded:</span> <?= date('M j, Y', strtotime($video['created_at'])) ?></div>
          <?php if ($video['published_at']): ?>
          <div><span class="text-muted">Published:</span> <?= date('M j, Y', strtotime($video['published_at'])) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card">
        <h3 style="font-weight:700;margin-bottom:12px;font-size:.9rem">Quick Actions</h3>
        <form method="POST">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <div class="flex flex-col gap-2">
            <?php if ($video['status'] === 'pending'): ?>
            <button name="action" value="approve" class="btn btn-sm" style="background:var(--green);color:#fff;justify-content:center">✅ Approve Video</button>
            <button name="action" value="reject" class="btn btn-sm btn-outline" style="color:var(--red);justify-content:center">❌ Reject Video</button>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/watch.php?v=<?= $vid ?>" target="_blank" class="btn btn-outline btn-sm" style="justify-content:center;text-align:center">👁️ Watch Video</a>
          </div>
        </form>
      </div>
    </div>
  </div>
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
<?php require_once __DIR__ . '/partials/admin_foot.php'; ?>
