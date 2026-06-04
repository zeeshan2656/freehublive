<?php
// ============================================================
// FreeHub.Live — Creator Edit Video
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin']);

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
$user_playlists = db_fetchAll("SELECT id, title FROM playlists WHERE user_id = ? ORDER BY title ASC", [$video_owner_id]);
$selected_playlists = array_column(
    db_fetchAll("SELECT playlist_id FROM playlist_videos WHERE video_id = ?", [$vid]),
    'playlist_id'
);

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
        $playlist_ids = array_map('intval', $_POST['playlist_ids'] ?? []);

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

            // Handle new thumbnail upload
            if (!empty($_FILES['thumbnail']['name'])) {
                $tfile = $_FILES['thumbnail'];
                $tmime = mime_content_type($tfile['tmp_name']);
                if (allowed_image($tmime)) {
                    if (!is_dir(THUMB_PATH)) mkdir(THUMB_PATH, 0755, true);
                    $ext = strtolower(pathinfo($tfile['name'], PATHINFO_EXTENSION));
                    $tfn = unique_filename($ext ?: 'jpg');
                    move_uploaded_file($tfile['tmp_name'], THUMB_PATH . $tfn);
                    // Delete old thumb
                    $old = $video['thumbnail'];
                    if ($old && !str_starts_with($old, 'http') && file_exists(THUMB_PATH . $old)) {
                        @unlink(THUMB_PATH . $old);
                    }
                    $updateData['thumbnail'] = $tfn;
                } else {
                    $error = 'Invalid image format.';
                }
            }

            if (empty($video['thumbnail']) && empty($updateData['thumbnail'])) {
                $updateData['thumbnail'] = 'default-thumb.jpg';
            }

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

                // Sync playlists
                $current_pids = array_column(
                    db_fetchAll("SELECT playlist_id FROM playlist_videos WHERE video_id = ?", [$vid]),
                    'playlist_id'
                );

                $to_remove = array_diff($current_pids, $playlist_ids);
                foreach ($to_remove as $pid) {
                    db_query("DELETE FROM playlist_videos WHERE playlist_id = ? AND video_id = ?", [$pid, $vid]);
                    db_query("DELETE FROM playlist_items WHERE playlist_id = ? AND video_id = ?", [$pid, $vid]);
                    db_query("UPDATE playlists SET video_count = GREATEST(0, CAST(video_count AS SIGNED) - 1) WHERE id = ?", [$pid]);
                }

                $to_add = array_diff($playlist_ids, $current_pids);
                foreach ($to_add as $pid) {
                    $playlist = db_fetch("SELECT id FROM playlists WHERE id = ? AND (user_id = ? OR ? IN (SELECT id FROM users WHERE role='admin' AND id=?))", [$pid, $video_owner_id, $uid, $uid]);
                    if ($playlist) {
                        $max_sort = (int)db_fetch("SELECT MAX(sort_order) as m FROM playlist_videos WHERE playlist_id = ?", [$pid])['m'];
                        db_insert('playlist_videos', [
                            'playlist_id' => $pid,
                            'video_id'    => $vid,
                            'sort_order'  => $max_sort + 1
                        ]);
                        $max_pos = (int)db_fetch("SELECT MAX(position) as m FROM playlist_items WHERE playlist_id = ?", [$pid])['m'];
                        db_insert('playlist_items', [
                            'playlist_id' => $pid,
                            'video_id'    => $vid,
                            'position'    => $max_pos + 1
                        ]);
                        db_query("UPDATE playlists SET video_count = video_count + 1 WHERE id = ?", [$pid]);
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

                $selected_playlists = array_column(
                    db_fetchAll("SELECT playlist_id FROM playlist_videos WHERE video_id = ?", [$vid]),
                    'playlist_id'
                );
                
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
                <label class="form-label">Playlists (Assign to one or more)</label>
                <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(130px, 1fr));gap:10px;background:var(--bg3);padding:12px;border-radius:var(--radius);border:1px solid var(--border);max-height:150px;overflow-y:auto">
                  <?php if (!empty($user_playlists)): ?>
                    <?php foreach ($user_playlists as $pl): ?>
                    <label class="flex gap-2" style="font-size:.85rem;cursor:pointer;user-select:none;align-items:center">
                      <input type="checkbox" name="playlist_ids[]" value="<?= $pl['id'] ?>" <?= in_array($pl['id'], $selected_playlists) ? 'checked' : '' ?>>
                      <span><?= e($pl['title']) ?></span>
                    </label>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <span class="text-muted text-xs" style="padding:4px">No playlists created yet.</span>
                  <?php endif; ?>
                </div>
              </div>
              
              <div class="form-group" style="grid-column: 1 / -1">
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
          <div class="card" style="margin-bottom:16px">
            <h3 style="font-weight:700;margin-bottom:12px;font-size:.95rem">&#128444; Thumbnail</h3>
            <div class="thumb-preview-box" id="thumb-preview-box">
              <img src="<?= thumb_url($video['thumbnail']) ?>" id="thumb-preview-img" alt="Thumbnail">
            </div>
            <label class="btn btn-outline w-full btn-sm" style="justify-content:center;cursor:pointer" for="thumb-input">
              &#128247; Change Thumbnail
            </label>
            <input type="file" id="thumb-input" name="thumbnail" accept="image/jpeg,image/png,image/webp" style="display:none">
            <p class="text-xs text-muted" style="margin-top:8px;text-align:center">JPG, PNG, WebP — 1280×720 recommended</p>
          </div>

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

<script data-page-script="true">
// Thumbnail preview
document.getElementById('thumb-input').addEventListener('change', function() {
  if (!this.files[0]) return;
  const reader = new FileReader();
  reader.onload = e => { document.getElementById('thumb-preview-img').src = e.target.result; };
  reader.readAsDataURL(this.files[0]);
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
