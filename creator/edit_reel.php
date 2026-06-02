<?php
// ============================================================
// FreeHub.Live — Creator Edit Reel
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

$uid = auth_user()['id'];
$vid = (int)($_GET['id'] ?? 0);

// Fetch video (owner/admin check)
$video = db_fetch("SELECT * FROM videos WHERE id=? AND (user_id=? OR ? IN (SELECT id FROM users WHERE role='admin' AND id=?))",
    [$vid, $uid, $uid, $uid]);

if (!$video) {
    http_response_code(403);
    die('Not found or access denied.');
}

// Redirect to normal edit page if it's not a Reel
if (!$video['is_reel']) {
    redirect(BASE_URL . '/creator/edit.php?id=' . $vid);
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $title = trim($_POST['title'] ?? '');
        if (strlen($title) < 3) {
            $error = 'Reel Title must be at least 3 characters.';
        } else {
            $slug = slugify($title);
            $base = $slug; $i = 1;
            while (db_fetch("SELECT id FROM videos WHERE id != ? AND slug=?", [$vid, $slug])) {
                $slug = $base . '-' . $i++;
            }

            db_update('videos', [
                'title' => $title,
                'slug'  => $slug
            ], 'id=?', [$vid]);

            // Refresh video data
            $video = db_fetch("SELECT * FROM videos WHERE id=?", [$vid]);
            $success = 'Reel updated successfully!';
        }
    }
}

$meta_title = 'Edit Reel';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<main class="main-content">
<div class="container" style="max-width: 800px; margin: 0 auto; padding: 24px 16px;">

    <!-- Breadcrumbs -->
    <div class="flex gap-2 text-sm text-muted" style="margin-bottom: 20px;">
      <a href="<?= BASE_URL ?>/creator/videos.php" style="color:var(--accent)">&#8592; My Videos</a>
      <span>/</span>
      <span>Edit Reel</span>
    </div>

    <div class="flex" style="justify-content: space-between; align-items: center; margin-bottom: 24px;">
      <h1 style="font-size: 1.5rem; font-weight: 800;">🎬 Edit Reel</h1>
      <span class="status-badge status-<?= $video['status'] ?>" style="text-transform: capitalize; padding: 4px 10px; font-weight: 600; font-size: 0.8rem; border-radius: 20px; background: rgba(255,255,255,0.08); border: 1px solid var(--border);">
        <?= $video['status'] ?>
      </span>
    </div>

    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success">&#10003; <?= e($success) ?></div><?php endif; ?>

    <form method="POST">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

      <div style="display: grid; grid-template-columns: 240px 1fr; gap: 32px; align-items: start;">
        
        <!-- Left: Reel Video Preview Player -->
        <div class="card" style="padding: 12px; border-radius: 16px; background: var(--bg2); border: 1px solid var(--border); display: flex; flex-direction: column; align-items: center; gap: 12px; box-shadow: var(--shadow);">
          <div style="width: 100%; aspect-ratio: 9/16; border-radius: 12px; overflow: hidden; background: #000; border: 1px solid rgba(255,255,255,0.05); position: relative;">
            <video src="<?= video_url($video['video_url']) ?>" poster="<?= thumb_url($video['thumbnail']) ?>" controls playsinline style="width: 100%; height: 100%; object-fit: cover; display: block;"></video>
          </div>
          <div style="font-size: 0.75rem; color: var(--text2); text-align: center; line-height: 1.5;">
            <div><strong>Duration:</strong> <?= format_duration((int)$video['duration']) ?></div>
            <div><strong>Size:</strong> <?= format_filesize((int)$video['file_size']) ?></div>
          </div>
        </div>

        <!-- Right: Reel Title Form -->
        <div class="card" style="padding: 24px; border-radius: 16px; background: var(--bg2); border: 1px solid var(--border); box-shadow: var(--shadow);">
          <div class="form-group" style="margin-bottom: 24px;">
            <label class="form-label" style="font-weight: 700; font-size: 0.95rem; margin-bottom: 8px;">Reel Title <span style="color:var(--red)">*</span></label>
            <input type="text" name="title" class="form-input" value="<?= e($video['title']) ?>" placeholder="Give your reel a catchy title..." minlength="3" required style="border-radius: 8px; font-size: 1rem; padding: 12px 16px;">
          </div>

          <div class="flex gap-2" style="justify-content: flex-end; align-items: center; border-top: 1px solid var(--border); padding-top: 20px; margin-top: 12px;">
            <a href="<?= BASE_URL ?>/creator/videos.php" class="btn btn-outline" style="border-radius: 8px; padding: 10px 22px;">Cancel</a>
            <button type="submit" class="btn btn-primary" style="border-radius: 8px; padding: 10px 24px; font-weight: 600;">Save Changes</button>
          </div>
        </div>

      </div>
    </form>
</div>
</main>
</div>

<style>
@media (max-width: 768px) {
  form > div {
    grid-template-columns: 1fr !important;
    gap: 20px !important;
  }
  form > div > div:first-child {
    max-width: 280px;
    margin: 0 auto;
  }
}
</style>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
