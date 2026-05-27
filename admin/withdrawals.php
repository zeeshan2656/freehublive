<?php
// Admin — Withdrawal Requests (Enhanced with filters)
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? '')) {
    $wid    = (int)($_POST['withdrawal_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $note   = trim($_POST['admin_note'] ?? '');
    $row    = db_fetch("SELECT * FROM withdrawal_requests WHERE id=?", [$wid]);
    if ($row) {
        if ($action === 'processing') {
            db_update('withdrawal_requests', ['status' => 'processing', 'admin_note' => $note ?: null], 'id=?', [$wid]);
        }
        if ($action === 'paid') {
            db_update('withdrawal_requests', [
                'status'       => 'paid',
                'processed_at' => date('Y-m-d H:i:s'),
                'admin_note'   => $note ?: null,
            ], 'id=?', [$wid]);
            db_update('earnings', ['status' => 'paid'],
                "user_id=? AND type='payout' AND status='pending' AND amount=?",
                [$row['user_id'], $row['amount']]
            );
        }
        if ($action === 'reject') {
            db_update('withdrawal_requests', ['status' => 'rejected', 'admin_note' => $note ?: null], 'id=?', [$wid]);
            db_query("UPDATE users SET balance = balance + ? WHERE id=?", [$row['amount'], $row['user_id']]);
            db_update('earnings', ['status' => 'rejected'],
                "user_id=? AND type='payout' AND status='pending' AND amount=?",
                [$row['user_id'], $row['amount']]
            );
        }
    }
    flash('success', 'Withdrawal updated.');
    redirect(BASE_URL . '/admin/withdrawals.php?' . http_build_query(array_filter([
        'status' => $_GET['status'] ?? '',
        'from'   => $_GET['from'] ?? '',
        'to'     => $_GET['to'] ?? '',
        'user'   => $_GET['user'] ?? '',
    ])));
}

// ── Filters ──────────────────────────────────────────────────
$filterStatus = preg_replace('/[^a-z]/', '', $_GET['status'] ?? 'pending');
$filterFrom   = $_GET['from'] ?? '';
$filterTo     = $_GET['to'] ?? '';
$filterUser   = trim($_GET['user'] ?? '');
$filterMin    = $_GET['min_amount'] ?? '';
$filterMax    = $_GET['max_amount'] ?? '';

$where  = '1';
$params = [];
if ($filterStatus !== 'all' && $filterStatus !== '') {
    $where .= " AND w.status=?"; $params[] = $filterStatus;
}
if ($filterFrom) {
    $where .= " AND DATE(w.created_at) >= ?"; $params[] = $filterFrom;
}
if ($filterTo) {
    $where .= " AND DATE(w.created_at) <= ?"; $params[] = $filterTo;
}
if ($filterUser) {
    $where .= " AND (u.username LIKE ? OR u.email LIKE ? OR u.channel_name LIKE ?)";
    $params[] = "%$filterUser%"; $params[] = "%$filterUser%"; $params[] = "%$filterUser%";
}
if ($filterMin !== '') {
    $where .= " AND w.amount >= ?"; $params[] = (float)$filterMin;
}
if ($filterMax !== '') {
    $where .= " AND w.amount <= ?"; $params[] = (float)$filterMax;
}

$rows = db_fetchAll(
    "SELECT w.*, u.username, u.email, u.channel_name, u.role, u.id AS uid
     FROM withdrawal_requests w
     JOIN users u ON u.id = w.user_id
     WHERE $where
     ORDER BY w.created_at DESC
     LIMIT 100",
    $params
);

$totals = [
    'pending'    => db_count('withdrawal_requests', "status='pending'"),
    'processing' => db_count('withdrawal_requests', "status='processing'"),
    'paid'       => (float)(db_fetch("SELECT COALESCE(SUM(amount),0) AS t FROM withdrawal_requests WHERE status='paid'")['t'] ?? 0),
    'rejected'   => db_count('withdrawal_requests', "status='rejected'"),
];

$meta_title = 'Withdrawals';
require_once __DIR__ . '/partials/admin_head.php';
?>
<div class="admin-content">
  <div class="admin-page-header">
    <p class="text-sm text-muted">Process payments within 7 days of each request.</p>
  </div>

  <!-- Summary Cards -->
  <div class="stat-grid-4" style="margin-bottom:24px">
    <div class="stat-card"><div class="stat-value" style="color:var(--yellow)"><?= $totals['pending'] ?></div><div class="stat-label">Pending</div></div>
    <div class="stat-card"><div class="stat-value" style="color:var(--accent)"><?= $totals['processing'] ?></div><div class="stat-label">Processing</div></div>
    <div class="stat-card"><div class="stat-value" style="color:var(--green)">$<?= number_format($totals['paid'],2) ?></div><div class="stat-label">Total Paid Out</div></div>
    <div class="stat-card"><div class="stat-value" style="color:var(--red)"><?= $totals['rejected'] ?></div><div class="stat-label">Rejected</div></div>
  </div>

  <?php foreach (get_flash() as $f): ?><div class="alert alert-<?= $f['type'] ?>"><?= e($f['msg']) ?></div><?php endforeach; ?>

  <!-- Status Tabs -->
  <div class="flex gap-2" style="margin-bottom:12px;flex-wrap:wrap">
    <?php foreach (['pending', 'processing', 'paid', 'rejected', 'all'] as $s): ?>
    <a href="?status=<?= $s ?>" class="btn btn-sm <?= $filterStatus === $s ? 'btn-primary' : 'btn-outline' ?>">
      <?= ucfirst($s) ?>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Advanced Filters -->
  <form method="GET" class="card" style="margin-bottom:16px;padding:14px">
    <input type="hidden" name="status" value="<?= e($filterStatus) ?>">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;align-items:end">
      <div class="form-group" style="margin:0">
        <label class="form-label" style="font-size:.75rem">User / Email</label>
        <input type="text" name="user" value="<?= e($filterUser) ?>" placeholder="Username or email" class="form-input" style="font-size:.8rem">
      </div>
      <div class="form-group" style="margin:0; min-width: 180px;">
        <label class="form-label" style="font-size:.75rem">Date Range</label>
        <div class="smart-date-filter" data-preset="<?= e($_GET['date_preset'] ?? '') ?>">
          <button type="button" class="btn btn-outline smart-date-btn w-full" style="justify-content:space-between; height:36px; font-size:.8rem; width:100%; padding: 0 10px; border-radius: 4px;">
            <span>📅 <?= !empty($filterFrom) && !empty($filterTo) ? e($filterFrom) . ' - ' . e($filterTo) : 'Select Range' ?></span>
            <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
          </button>
          <input type="hidden" name="from" class="smart-from-val" value="<?= e($filterFrom) ?>">
          <input type="hidden" name="to" class="smart-to-val" value="<?= e($filterTo) ?>">
          <input type="hidden" name="date_preset" value="<?= e($_GET['date_preset'] ?? '') ?>">
        </div>
      </div>
      <div class="form-group" style="margin:0">
        <label class="form-label" style="font-size:.75rem">Min Amount ($)</label>
        <input type="number" name="min_amount" value="<?= e($filterMin) ?>" placeholder="0" class="form-input" step="0.01" style="font-size:.8rem">
      </div>
      <div class="form-group" style="margin:0">
        <label class="form-label" style="font-size:.75rem">Max Amount ($)</label>
        <input type="number" name="max_amount" value="<?= e($filterMax) ?>" placeholder="9999" class="form-input" step="0.01" style="font-size:.8rem">
      </div>
      <div class="flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <a href="?status=<?= e($filterStatus) ?>" class="btn btn-outline btn-sm">Reset</a>
      </div>
    </div>
  </form>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>User</th><th>Amount</th><th>Method</th>
          <th>Payment Details</th><th>Due By</th><th>Status</th><th>Date</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td>
          <a href="<?= BASE_URL ?>/admin/users.php?view=<?= $r['uid'] ?>" style="color:var(--accent);font-weight:600;font-size:.85rem"><?= e($r['channel_name'] ?? $r['username']) ?></a>
          <span class="badge badge-<?= $r['role'] === 'creator' ? 'green' : 'gray' ?>" style="font-size: .65rem; padding: 1px 4px; margin-left: 4px;"><?= $r['role'] === 'creator' ? 'Creator' : 'Viewer' ?></span>
          <div class="text-xs text-muted"><?= e($r['email']) ?></div>
        </td>
        <td style="font-weight:700;color:var(--green)">$<?= number_format((float)$r['amount'], 2) ?> <span class="text-xs text-muted"><?= e($r['currency']) ?></span></td>
        <td class="text-sm"><?= e($r['payment_method']) ?></td>
        <td class="text-xs text-muted" style="max-width:180px;white-space:pre-wrap"><?= e($r['payment_details']) ?></td>
        <td class="text-xs <?= $r['status'] === 'pending' && $r['due_by'] && $r['due_by'] < date('Y-m-d') ? 'text-red' : 'text-muted' ?>"><?= e($r['due_by'] ?? '—') ?></td>
        <td><span class="badge badge-<?= $r['status'] === 'paid' ? 'green' : ($r['status'] === 'pending' ? 'yellow' : ($r['status'] === 'processing' ? 'blue' : 'gray')) ?>"><?= e($r['status']) ?></span></td>
        <td class="text-xs text-muted"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
        <td>
          <?php if (in_array($r['status'], ['pending', 'processing'], true)): ?>
          <form method="POST" style="display:flex;flex-direction:column;gap:6px;min-width:180px">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="withdrawal_id" value="<?= $r['id'] ?>">
            <input class="form-input" type="text" name="admin_note" placeholder="Admin note (optional)" style="font-size:.72rem;padding:5px 8px" value="<?= e($r['admin_note']??'') ?>">
            <?php if ($r['status'] === 'pending'): ?>
            <button name="action" value="processing" class="btn btn-sm btn-outline">🔄 Mark Processing</button>
            <?php endif; ?>
            <button name="action" value="paid" class="btn btn-sm" style="background:var(--green);color:#fff">✅ Mark Paid</button>
            <button name="action" value="reject" class="btn btn-sm btn-outline" style="color:var(--red)" onclick="return confirm('Reject and refund this withdrawal?')">❌ Reject &amp; Refund</button>
          </form>
          <?php elseif ($r['admin_note']): ?>
          <div class="text-xs text-muted" style="max-width:160px">Note: <?= e($r['admin_note']) ?></div>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
      <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--text2)">No withdrawal requests found</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/partials/admin_foot.php'; ?>
