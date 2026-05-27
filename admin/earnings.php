<?php
// Admin — Earnings Management
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD']==='POST' && verify_csrf($_POST['csrf']??'')) {
    $eid    = (int)($_POST['earning_id']??0);
    $action = $_POST['action']??'';
    if ($action === 'approve') {
        $row = db_fetch("SELECT * FROM earnings WHERE id=?", [$eid]);
        if ($row) {
            db_update('earnings', ['status'=>'paid'], 'id=?', [$eid]);
            db_query("UPDATE users SET balance=GREATEST(0,balance-?) WHERE id=?", [$row['amount'],$row['user_id']]);
        }
    }
    if ($action === 'reject') db_update('earnings', ['status'=>'rejected'], 'id=?', [$eid]);
    flash('success','Updated.');
    redirect(BASE_URL . '/admin/earnings.php');
}

$filter = $_GET['status'] ?? 'pending';
$where  = $filter !== 'all' ? "e.status='$filter'" : '1';
$rows   = db_fetchAll(
    "SELECT e.*,u.username,u.email FROM earnings e JOIN users u ON u.id=e.user_id
     WHERE $where ORDER BY e.created_at DESC LIMIT 50"
);
$totals = db_fetch("SELECT SUM(CASE WHEN status='pending' THEN amount ELSE 0 END) as pending,
                           SUM(CASE WHEN status='paid' THEN amount ELSE 0 END) as paid
                    FROM earnings");

$meta_title = 'Earnings Management';
require_once __DIR__ . '/partials/admin_head.php';
?>
<div class="admin-content">
  <?php foreach(get_flash() as $f): ?><div class="alert alert-<?= $f['type'] ?>"><?= e($f['msg']) ?></div><?php endforeach; ?>

  <div class="stat-grid-3" style="margin-bottom:24px">
    <div class="stat-card"><div class="stat-value">$<?= number_format((float)$totals['pending'],2) ?></div><div class="stat-label">Pending Payouts</div></div>
    <div class="stat-card"><div class="stat-value">$<?= number_format((float)$totals['paid'],2) ?></div><div class="stat-label">Total Paid Out</div></div>
    <div class="stat-card"><div class="stat-value"><?= db_count('earnings',"status='pending'") ?></div><div class="stat-label">Pending Requests</div></div>
  </div>

  <div class="flex gap-2" style="margin-bottom:16px">
    <?php foreach(['all','pending','approved','paid','rejected'] as $s): ?>
    <a href="?status=<?= $s ?>" class="btn btn-sm <?= $filter===$s?'btn-primary':'btn-outline' ?>"><?= ucfirst($s) ?></a>
    <?php endforeach; ?>
  </div>

  <div class="table-wrap">
    <table>
      <thead><tr><th>User</th><th>Type</th><th>Amount</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><div style="font-weight:600;font-size:.85rem"><?= e($r['username']) ?></div><div class="text-xs text-muted"><?= e($r['email']) ?></div></td>
        <td><span class="badge badge-blue"><?= e($r['type']) ?></span></td>
        <td style="font-weight:700;color:var(--green)">$<?= number_format((float)$r['amount'],4) ?></td>
        <td><span class="badge badge-<?= $r['status']==='paid'?'green':($r['status']==='pending'?'yellow':'gray') ?>"><?= $r['status'] ?></span></td>
        <td class="text-xs text-muted"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
        <td>
          <?php if ($r['status']==='pending'): ?>
          <form method="POST" class="flex gap-1">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="earning_id" value="<?= $r['id'] ?>">
            <button name="action" value="approve" class="btn btn-sm" style="background:var(--green);color:#fff">Pay</button>
            <button name="action" value="reject"  class="btn btn-sm btn-outline" style="color:var(--red)">Reject</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/partials/admin_foot.php'; ?>
