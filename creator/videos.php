<?php
// Creator — My Videos
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['creator','admin']);
$site_theme = setting('active_theme', 'dark-minimal');
$primary    = setting('primary_color', '#6366f1');
$uid = auth_user()['id'];

if ($_SERVER['REQUEST_METHOD']==='POST' && verify_csrf($_POST['csrf']??'')) {
    $vid    = (int)($_POST['video_id']??0);
    $action = $_POST['action']??'';
    $own    = db_fetch("SELECT id FROM videos WHERE id=? AND user_id=?", [$vid,$uid]);
    if ($own) {
        if ($action==='delete') db_query("DELETE FROM videos WHERE id=?", [$vid]);
        if ($action==='toggle_comments') db_query("UPDATE videos SET allow_comments=1-allow_comments WHERE id=?", [$vid]);
        if ($action==='visibility' && in_array($_POST['visibility']??'',['public','unlisted','private']))
            db_update('videos', ['visibility'=>$_POST['visibility']], 'id=?', [$vid]);
    }
    redirect(BASE_URL . '/creator/videos.php');
}

$page   = max(1,(int)($_GET['page']??1));
$filter = $_GET['status']??'all';
$where  = "user_id=$uid";
if ($filter!=='all') $where .= " AND status='$filter'";
$total  = db_count('videos', $where);
$pg     = paginate($total, 20, $page);
$videos = db_fetchAll("SELECT * FROM videos WHERE $where ORDER BY created_at DESC LIMIT 20 OFFSET {$pg['offset']}");
// Duration sync removed for performance — synced via watch.php instead
$meta_title = 'My Videos';
$header_actions = '
<style>
@media (max-width: 576px) {
  .header-upload-btn span { display: none !important; }
  .header-upload-btn { padding: 8px !important; width: 34px; height: 34px; justify-content: center; border-radius: 50% !important; }
}
</style>
<a href="' . BASE_URL . '/creator/upload.php" class="btn btn-primary btn-sm flex gap-1 header-upload-btn" style="border-radius: 18px; padding: 6px 12px;" title="Upload Video">
  <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="flex-shrink:0;">
    <polyline points="17 8 12 3 7 8"></polyline>
    <line x1="12" y1="3" x2="12" y2="15"></line>
    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
  </svg>
  <span>Upload Video</span>
</a>';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">

    <div class="flex gap-2" style="margin-bottom:16px">
      <?php foreach(['all','published','pending','rejected','draft'] as $s): ?>
      <a href="?status=<?= $s ?>" class="btn btn-sm <?= $filter===$s?'btn-primary':'btn-outline' ?>"><?= ucfirst($s) ?></a>
      <?php endforeach; ?>
    </div>

    <div class="table-wrap">
      <table>
        <thead><tr><th>Video</th><th>Status</th><th>Views</th><th>Likes</th><th>Duration</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($videos as $v): ?>
        <tr>
          <td>
            <div class="flex gap-3">
              <img src="<?= thumb_url($v['thumbnail']) ?>" style="width:80px;aspect-ratio:16/9;object-fit:cover;border-radius:4px;flex-shrink:0" loading="lazy">
              <div style="min-width:0"><div style="font-weight:600;font-size:.85rem;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;max-width:180px"><?= e($v['title']) ?></div>
              <div class="text-xs text-muted" style="margin-top:3px"><?= e($v['visibility']) ?></div></div>
            </div>
          </td>
          <td><span class="badge badge-<?= $v['status']==='published'?'green':($v['status']==='pending'?'yellow':'gray') ?>"><?= $v['status'] ?></span></td>
          <td class="text-sm"><?= format_number((int)$v['views']) ?></td>
          <td class="text-sm"><?= format_number((int)$v['likes']) ?></td>
          <td class="text-sm"><?= format_duration((int)$v['duration']) ?></td>
          <td class="text-xs text-muted"><?= date('M j, Y',strtotime($v['created_at'])) ?></td>
          <td>
            <form method="POST" class="flex gap-1">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="video_id" value="<?= $v['id'] ?>">
              <a href="<?= BASE_URL ?>/creator/edit.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-outline">&#9998; Edit</a>
              <a href="<?= BASE_URL ?>/watch.php?v=<?= $v['id'] ?>" class="btn btn-sm btn-outline" target="_blank">&#128065;</a>
              <button name="action" value="delete" class="btn btn-sm btn-outline" style="color:var(--red)" onclick="return confirm('Delete this video?')">&#128465;</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(!$videos): ?>
        <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text2)">No videos yet. <a href="<?= BASE_URL ?>/creator/upload.php" style="color:var(--accent)">Upload your first video</a></td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
