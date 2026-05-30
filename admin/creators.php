<?php
// Admin — Creators Management
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD']==='POST' && verify_csrf($_POST['csrf']??'')) {
    $uid    = (int)($_POST['user_id']??0);
    $action = $_POST['action']??'';
    if ($action==='approve') {
        db_update('users',['status'=>'active','role'=>'creator'],'id=?',[$uid]);
        $pu = db_fetch("SELECT username FROM users WHERE id=?", [$uid]);
        ensure_user_channel($uid, $pu['username'] ?? null);
    }
    if ($action==='reject')  db_update('users',['status'=>'suspended'],'id=?',[$uid]);
    flash('success','Creator updated.');
    redirect(BASE_URL.'/admin/creators.php');
}

$filter = $_GET['filter']??'pending';
$where  = $filter==='all' ? "role='creator'" : "role='creator' AND status='$filter'";
$creators = db_fetchAll(
    "SELECT u.*,(SELECT COUNT(*) FROM videos WHERE user_id=u.id) as vcount,
     (SELECT SUM(views) FROM videos WHERE user_id=u.id) as total_views
     FROM users u WHERE $where ORDER BY u.created_at DESC"
);
$meta_title = 'Creators';
require_once __DIR__.'/partials/admin_head.php';
?>
<div class="admin-content">
  <?php foreach(get_flash() as $f): ?><div class="alert alert-<?= $f['type'] ?>"><?= e($f['msg']) ?></div><?php endforeach; ?>

  <div class="flex gap-2" style="margin-bottom:16px">
    <?php foreach(['pending'=>'Pending','active'=>'Approved','suspended'=>'Disabled','all'=>'All'] as $f=>$l): ?>
    <a href="?filter=<?= $f ?>" class="btn btn-sm <?= $filter===$f?'btn-primary':'btn-outline' ?>"><?= $l ?></a>
    <?php endforeach; ?>
  </div>

  <div class="table-wrap">
    <table>
      <thead><tr><th>Creator</th><th>Email</th><th>Videos</th><th>Total Views</th><th>Balance</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach($creators as $p): ?>
      <tr>
        <td>
          <div class="flex gap-2">
            <img src="<?= avatar_url($p['avatar']) ?>" class="avatar avatar-sm" width="32" height="32">
            <div>
              <div style="font-weight:600;font-size:.85rem"><?= e($p['channel_name']??$p['username']) ?></div>
              <div class="text-xs text-muted">@<?= e($p['username']) ?></div>
            </div>
          </div>
        </td>
        <td class="text-sm text-muted"><?= e($p['email']) ?></td>
        <td class="text-sm"><?= $p['vcount'] ?></td>
        <td class="text-sm"><?= format_number((int)($p['total_views']??0)) ?></td>
        <td class="text-sm">$<?= number_format((float)$p['balance'],2) ?></td>
        <td>
          <?php
          $c_labels = ['active' => 'Approved', 'pending' => 'Pending', 'suspended' => 'Disabled', 'rejected' => 'Rejected'];
          $c_badges = ['active' => 'green', 'pending' => 'yellow', 'suspended' => 'red', 'rejected' => 'red'];
          ?>
          <span class="badge badge-<?= $c_badges[$p['status']] ?? 'gray' ?>"><?= e($c_labels[$p['status']] ?? $p['status']) ?></span>
        </td>
        <td class="text-xs text-muted"><?= date('M j, Y',strtotime($p['created_at'])) ?></td>
        <td>
          <form method="POST" class="flex gap-1">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="user_id" value="<?= $p['id'] ?>">
            <?php if($p['status']==='pending'): ?>
            <button name="action" value="approve" class="btn btn-sm" style="background:var(--green);color:#fff">Approve</button>
            <button name="action" value="reject"  class="btn btn-sm btn-outline" style="color:var(--red)">Reject</button>
            <?php elseif($p['status']==='active'): ?>
            <button name="action" value="reject" class="btn btn-sm btn-outline" style="color:var(--red)">Suspend</button>
            <?php else: ?>
            <button name="action" value="approve" class="btn btn-sm" style="background:var(--green);color:#fff">Restore</button>
            <?php endif; ?>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(!$creators): ?><tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text2)">No creators in this category</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__.'/partials/admin_foot.php'; ?>
