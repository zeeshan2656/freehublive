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
        ];

        // Handle published_at
        if ($status === 'published' && empty($video['published_at'])) {
            $updateData['published_at'] = date('Y-m-d H:i:s');
        }

        // Handle thumbnail upload
        if (!empty($_FILES['thumbnail']['tmp_name'])) {
            $mime = mime_content_type($_FILES['thumbnail']['tmp_name']);
            if (allowed_image($mime)) {
                $ext  = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
                $fname = unique_filename($ext);
                if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], THUMB_PATH . $fname)) {
                    // Delete old thumbnail
                    if (!empty($video['thumbnail']) && !str_starts_with($video['thumbnail'], 'http')) {
                        @unlink(THUMB_PATH . $video['thumbnail']);
                    }
                    $updateData['thumbnail'] = $fname;
                }
            } else {
                $error = 'Invalid thumbnail format. Use JPG, PNG, or WebP.';
            }
        }

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
            <label class="form-label">Admin Approval Note</label>
            <input class="form-input" type="text" name="approval_note" value="<?= e($video['approval_note'] ?? '') ?>" placeholder="Reason for rejection or note to creator">
          </div>
        </div>

        <div class="card" style="margin-bottom:16px">
          <h3 style="font-weight:700;margin-bottom:12px">Thumbnail</h3>
          <?php if ($video['thumbnail']): ?>
          <img src="<?= thumb_url($video['thumbnail']) ?>" style="width:100%;max-width:320px;aspect-ratio:16/9;object-fit:cover;border-radius:8px;margin-bottom:12px">
          <?php endif; ?>
          <div class="form-group">
            <label class="form-label">Replace Thumbnail</label>
            <input class="form-input" type="file" name="thumbnail" accept="image/*">
          </div>
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
      <div class="card" style="margin-bottom:16px">
        <h3 style="font-weight:700;margin-bottom:12px;font-size:.9rem">Video Info</h3>
        <div style="font-size:.82rem;display:flex;flex-direction:column;gap:8px">
          <div><span class="text-muted">ID:</span> #<?= $vid ?></div>
          <div><span class="text-muted">Creator:</span> <a href="<?= BASE_URL ?>/admin/users.php?view=<?= $video['user_id'] ?>" style="color:var(--accent)"><?= e($video['channel_name'] ?? $video['username']) ?></a></div>
          <div><span class="text-muted">Views:</span> <?= format_number((int)$video['views']) ?></div>
          <div><span class="text-muted">Watch Time:</span> <?= format_duration((int)($video['watch_time'] ?? 0)) ?></div>
          <div><span class="text-muted">Duration:</span> <?= format_duration((int)($video['duration'] ?? 0)) ?></div>
          <div><span class="text-muted">Revenue:</span> $<?= number_format((float)($video['revenue']??0),4) ?></div>
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
<?php require_once __DIR__ . '/partials/admin_foot.php'; ?>
