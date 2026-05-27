<?php
// Admin — Affiliates Management
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$affiliates = db_fetchAll(
    "SELECT u.*,
     (SELECT COUNT(*) FROM affiliate_clicks WHERE affiliate_id=u.id) as clicks,
     (SELECT COUNT(*) FROM video_views WHERE affiliate_id=u.id) as aff_views,
     (SELECT SUM(amount) FROM earnings WHERE user_id=u.id AND status='paid') as paid
     FROM users u WHERE u.role='affiliate' ORDER BY clicks DESC"
);
$meta_title = 'Affiliates';
require_once __DIR__.'/partials/admin_head.php';
?>
<div class="admin-content">
  <!-- Summary -->
  <div class="stat-grid-4" style="margin-bottom:24px">
    <div class="stat-card"><div class="stat-value"><?= count($affiliates) ?></div><div class="stat-label">Total Affiliates</div></div>
    <div class="stat-card"><div class="stat-value"><?= format_number(array_sum(array_column($affiliates,'clicks'))) ?></div><div class="stat-label">Total Clicks</div></div>
    <div class="stat-card"><div class="stat-value"><?= format_number(array_sum(array_column($affiliates,'aff_views'))) ?></div><div class="stat-label">Views Generated</div></div>
    <div class="stat-card"><div class="stat-value">$<?= number_format(array_sum(array_column($affiliates,'paid')),2) ?></div><div class="stat-label">Total Paid</div></div>
  </div>

  <div class="table-wrap">
    <table>
      <thead><tr><th>Affiliate</th><th>Ref Code</th><th>Clicks</th><th>Views</th><th>Balance</th><th>Paid Out</th><th>Status</th><th>Joined</th></tr></thead>
      <tbody>
      <?php foreach($affiliates as $a): ?>
      <tr>
        <td>
          <div class="flex gap-2">
            <img src="<?= avatar_url($a['avatar']) ?>" class="avatar avatar-sm" width="32" height="32">
            <div>
              <div style="font-weight:600;font-size:.85rem"><?= e($a['username']) ?></div>
              <div class="text-xs text-muted"><?= e($a['email']) ?></div>
            </div>
          </div>
        </td>
        <td><code style="background:var(--bg3);padding:3px 8px;border-radius:4px;font-size:.78rem"><?= e($a['ref_code']) ?></code></td>
        <td class="text-sm font-bold"><?= format_number((int)$a['clicks']) ?></td>
        <td class="text-sm"><?= format_number((int)$a['aff_views']) ?></td>
        <td class="text-sm" style="color:var(--green)">$<?= number_format((float)$a['balance'],2) ?></td>
        <td class="text-sm">$<?= number_format((float)($a['paid']??0),2) ?></td>
        <td><span class="badge badge-<?= $a['status']==='active'?'green':'red' ?>"><?= $a['status'] ?></span></td>
        <td class="text-xs text-muted"><?= date('M j, Y',strtotime($a['created_at'])) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if(!$affiliates): ?><tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text2)">No affiliates yet</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__.'/partials/admin_foot.php'; ?>
