<?php
// Admin — Withdrawal Requests (Enhanced with Notes, Receipts & Proof Popup Modal)
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
        $proof_filename = $row['payment_proof'] ?? null;
        
        // Handle Payment Proof File Upload (JPEG, PNG, WEBP, PDF)
        if (!empty($_FILES['payment_proof']['name'])) {
            $file = $_FILES['payment_proof'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'pdf'], true)) {
                if ($file['size'] <= 10 * 1024 * 1024) { // 10MB Max Limit
                    $proofDir = __DIR__ . '/../uploads/proofs/';
                    if (!is_dir($proofDir)) {
                        mkdir($proofDir, 0755, true);
                    }
                    $new_name = 'proof_' . unique_filename($ext);
                    if (move_uploaded_file($file['tmp_name'], $proofDir . $new_name)) {
                        // Delete previous proof if exists
                        if ($proof_filename && file_exists($proofDir . $proof_filename)) {
                            @unlink($proofDir . $proof_filename);
                        }
                        $proof_filename = $new_name;
                    }
                } else {
                    flash('error', 'Payment proof file size exceeds 10MB.');
                    redirect(BASE_URL . '/admin/withdrawals.php');
                }
            } else {
                flash('error', 'Allowed proof formats: JPG, PNG, WEBP, PDF.');
                redirect(BASE_URL . '/admin/withdrawals.php');
            }
        }

        if ($action === 'processing') {
            db_update('withdrawal_requests', [
                'status' => 'processing', 
                'admin_note' => $note ?: null,
                'payment_proof' => $proof_filename
            ], 'id=?', [$wid]);
        }
        
        if ($action === 'paid') {
            db_update('withdrawal_requests', [
                'status'       => 'paid',
                'processed_at' => date('Y-m-d H:i:s'),
                'admin_note'   => $note ?: null,
                'payment_proof' => $proof_filename
            ], 'id=?', [$wid]);
            
            db_update('earnings', ['status' => 'paid'],
                "user_id=? AND type='payout' AND status='pending' AND amount=?",
                [$row['user_id'], $row['amount']]
            );
        }
        
        if ($action === 'reject') {
            db_update('withdrawal_requests', [
                'status' => 'rejected', 
                'admin_note' => $note ?: null,
                'payment_proof' => $proof_filename
            ], 'id=?', [$wid]);
            
            db_query("UPDATE users SET balance = balance + ? WHERE id=?", [$row['amount'], $row['user_id']]);
            db_update('earnings', ['status' => 'rejected'],
                "user_id=? AND type='payout' AND status='pending' AND amount=?",
                [$row['user_id'], $row['amount']]
            );
        }
    }
    flash('success', 'Withdrawal request updated successfully.');
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
    $where .= " AND (u.username LIKE ? OR u.email LIKE ? OR u.channel_name LIKE ? OR w.id = ?)";
    $params[] = "%$filterUser%"; $params[] = "%$filterUser%"; $params[] = "%$filterUser%"; $params[] = (int)$filterUser;
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

$meta_title = 'Withdrawals Management';
require_once __DIR__ . '/partials/admin_head.php';
?>

<style>
/* Modal Popup Styling */
.custom-modal-backdrop {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(6px);
  z-index: 1100;
  align-items: center;
  justify-content: center;
  padding: 16px;
}
.custom-modal-backdrop.open { display: flex; }
.custom-modal-content {
  background: var(--bg2);
  border: 1px solid var(--border);
  border-radius: 16px;
  width: 100%;
  max-width: 560px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.5);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}
.custom-modal-header {
  padding: 18px 24px;
  border-bottom: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.custom-modal-header h3 { font-family: var(--font2); font-size: 1.15rem; font-weight: 800; margin: 0; color: #fff; }
.custom-modal-close { font-size: 1.5rem; color: var(--text2); cursor: pointer; border: none; background: none; }
.custom-modal-close:hover { color: #fff; }
.custom-modal-body {
  padding: 24px;
  overflow-y: auto;
  max-height: 80vh;
}
.modal-detail-row {
  display: flex;
  justify-content: space-between;
  padding: 10px 0;
  border-bottom: 1px solid rgba(255,255,255,0.03);
}
.modal-detail-row:last-child { border-bottom: none; }
.modal-detail-lbl { font-size: 0.85rem; color: var(--text2); font-weight: 500; }
.modal-detail-val { font-size: 0.88rem; color: #fff; font-weight: 600; text-align: right; word-break: break-word; max-width: 60%; }
.modal-proof-container {
  margin-top: 18px;
  background: var(--bg3);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 14px;
  text-align: center;
}
.modal-proof-img {
  max-width: 100%;
  max-height: 260px;
  border-radius: 6px;
  margin-top: 8px;
  object-fit: contain;
  border: 1px solid var(--border);
}
</style>

<div class="admin-content">
  <div class="admin-page-header">
    <p class="text-sm text-muted">Process member payments, attach custom transactions notes, and upload transfer slips.</p>
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
        <label class="form-label" style="font-size:.75rem">ID, User or Email</label>
        <input type="text" name="user" value="<?= e($filterUser) ?>" placeholder="ID or name" class="form-input" style="font-size:.8rem">
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
          <th>ID</th><th>User</th><th>Amount</th><th>Method</th>
          <th>Due By</th><th>Status</th><th>Date</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): 
        $modalData = htmlspecialchars(json_encode([
          'id' => $r['id'],
          'uid' => $r['uid'],
          'username' => $r['username'],
          'email' => $r['email'],
          'channel_name' => $r['channel_name'] ?? $r['username'],
          'role' => $r['role'],
          'amount' => '$' . number_format((float)$r['amount'], 2) . ' ' . $r['currency'],
          'payment_method' => ucfirst(str_replace('_', ' ', $r['payment_method'])),
          'payment_details' => $r['payment_details'],
          'country' => $r['country'] ?? '—',
          'status' => ucfirst($r['status']),
          'due_by' => $r['due_by'] ?? '—',
          'admin_note' => $r['admin_note'] ?? '',
          'payment_proof' => $r['payment_proof'] ? BASE_URL . '/uploads/proofs/' . $r['payment_proof'] : null,
          'created_at' => date('M j, Y H:i', strtotime($r['created_at'])),
        ]), ENT_QUOTES, 'UTF-8');
      ?>
      <tr style="cursor:pointer" onclick="if(!event.target.closest('form') && !event.target.closest('a')) { showWithdrawalDetails(<?= $modalData ?>); }">
        <td><strong style="color:var(--accent)">#<?= $r['id'] ?></strong></td>
        <td>
          <a href="<?= BASE_URL ?>/admin/users.php?view=<?= $r['uid'] ?>" style="color:var(--accent);font-weight:600;font-size:.85rem"><?= e($r['channel_name'] ?? $r['username']) ?></a>
          <span class="badge badge-<?= $r['role'] === 'creator' ? 'green' : 'gray' ?>" style="font-size: .65rem; padding: 1px 4px; margin-left: 4px;"><?= $r['role'] === 'creator' ? 'Creator' : 'Viewer' ?></span>
          <div class="text-xs text-muted"><?= e($r['email']) ?></div>
        </td>
        <td style="font-weight:700;color:var(--green)">$<?= number_format((float)$r['amount'], 2) ?> <span class="text-xs text-muted"><?= e($r['currency']) ?></span></td>
        <td class="text-sm"><?= e($r['payment_method']) ?></td>
        <td class="text-xs <?= $r['status'] === 'pending' && $r['due_by'] && $r['due_by'] < date('Y-m-d') ? 'text-red' : 'text-muted' ?>"><?= e($r['due_by'] ?? '—') ?></td>
        <td><span class="badge badge-<?= $r['status'] === 'paid' ? 'green' : ($r['status'] === 'pending' ? 'yellow' : ($r['status'] === 'processing' ? 'blue' : 'gray')) ?>"><?= e($r['status']) ?></span></td>
        <td class="text-xs text-muted"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
        <td>
          <?php if (in_array($r['status'], ['pending', 'processing'], true)): ?>
          <form method="POST" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:6px;min-width:190px" onclick="event.stopPropagation()">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="withdrawal_id" value="<?= $r['id'] ?>">
            
            <input class="form-input" type="text" name="admin_note" placeholder="Admin note (optional)" style="font-size:.72rem;padding:5px 8px" value="<?= e($r['admin_note']??'') ?>">
            
            <div style="text-align:left">
              <label style="font-size:.65rem;color:var(--text2);display:block;margin-bottom:2px">Upload Proof slip (Image/PDF)</label>
              <input type="file" name="payment_proof" accept="image/*,application/pdf" style="font-size:.72rem;background:transparent;border:none;padding:0;color:var(--text2)">
            </div>

            <div style="display:flex;gap:4px;margin-top:2px">
              <?php if ($r['status'] === 'pending'): ?>
              <button name="action" value="processing" class="btn btn-xs btn-outline" style="flex:1">🔄 Processing</button>
              <?php endif; ?>
              <button name="action" value="paid" class="btn btn-xs btn-success" style="flex:1;background:var(--green);color:#fff">✅ Paid</button>
              <button name="action" value="reject" class="btn btn-xs btn-outline" style="color:var(--red)" onclick="return confirm('Reject and refund this withdrawal?')">❌ Reject</button>
            </div>
          </form>
          <?php else: ?>
            <div class="flex flex-col gap-1 align-start">
              <button type="button" class="btn btn-xs btn-outline" onclick="showWithdrawalDetails(<?= $modalData ?>); event.stopPropagation()">🔍 View Details</button>
              <?php if ($r['payment_proof']): ?>
                <span class="text-xs" style="color:var(--green)">✓ Receipt Linked</span>
              <?php endif; ?>
            </div>
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

<!-- ── Withdrawal Details Modal ── -->
<div id="withdrawal-detail-modal" class="custom-modal-backdrop" onclick="closeModalOnBackdrop(event)">
  <div class="custom-modal-content fade-in">
    <div class="custom-modal-header">
      <h3>Withdrawal Details</h3>
      <button type="button" class="custom-modal-close" onclick="closeWithdrawalModal()">&times;</button>
    </div>
    <div class="custom-modal-body" id="modal-body-content">
      <!-- Dynamic content inserted by Javascript -->
    </div>
  </div>
</div>

<script>
function showWithdrawalDetails(data) {
  const body = document.getElementById('modal-body-content');
  
  const statusLower = (data.status || '').toLowerCase();
  const badgeClass = statusLower === 'paid' ? 'green' : (statusLower === 'pending' ? 'yellow' : (statusLower === 'processing' ? 'blue' : 'gray'));
  
  let proofHtml = '';
  if (data.payment_proof) {
    const isPdf = data.payment_proof.toLowerCase().endsWith('.pdf');
    proofHtml = isPdf
      ? `<div class="modal-proof-container">
          <div style="font-weight:700;font-size:0.8rem;color:var(--text2);margin-bottom:6px;">📎 Payout Receipt (PDF Document)</div>
          <a href="${data.payment_proof}" target="_blank" class="btn btn-sm btn-outline" style="margin-top:8px;display:inline-flex;align-items:center;gap:6px">📥 Download PDF Receipt</a>
         </div>`
      : `<div class="modal-proof-container">
          <div style="font-weight:700;font-size:0.8rem;color:var(--text2);margin-bottom:8px">📸 Payout Receipt Proof</div>
          <a href="${data.payment_proof}" target="_blank"><img src="${data.payment_proof}" class="modal-proof-img" alt="Payment Proof"></a>
          <div style="font-size:0.7rem;color:var(--text3);margin-top:6px">Click image to inspect or download</div>
         </div>`;
  } else if (statusLower === 'paid') {
    proofHtml = `<div class="modal-proof-container" style="border-style:dashed;background:transparent"><div style="font-size:0.8rem;color:var(--text3)">No receipt uploaded by Administrator.</div></div>`;
  }

  // Format details beautifully as a structured grid/table
  const detailLines = (data.payment_details || '').split('\n').filter(l => l.trim());
  let detailHtml = '';
  if (detailLines.length > 0) {
    detailHtml = '<div style="display:flex; flex-direction:column; gap:8px; width:100%;">';
    detailLines.forEach(l => {
      const idx = l.indexOf(':');
      if (idx !== -1) {
        const lbl = l.substring(0, idx).trim();
        const val = l.substring(idx + 1).trim();
        detailHtml += `
          <div style="display:flex; justify-content:space-between; font-size:0.8rem; border-bottom:1px solid rgba(255,255,255,0.03); padding-bottom:6px;">
            <span style="color:var(--text2); font-weight:600;">${lbl}</span>
            <span style="color:#fff; font-weight:700; font-family:monospace; word-break:break-all; max-width:65%; text-align:right;">${val}</span>
          </div>`;
      } else {
        detailHtml += `
          <div style="font-size:0.8rem; font-family:monospace; color:#fff; word-break:break-all; border-bottom:1px solid rgba(255,255,255,0.03); padding-bottom:6px;">
            ${l}
          </div>`;
      }
    });
    detailHtml += '</div>';
  } else {
    detailHtml = `<span style="font-family:monospace; font-size:0.8rem; color:var(--text3)">No details provided</span>`;
  }

  body.innerHTML = `
    <div class="modal-detail-row"><span class="modal-detail-lbl">Request ID</span><span class="modal-detail-val">#${data.id}</span></div>
    <div class="modal-detail-row">
      <span class="modal-detail-lbl">Member</span>
      <span class="modal-detail-val">${data.channel_name} (@${data.username})<br><small style="color:var(--text3); font-size:0.75rem;">${data.email}</small></span>
    </div>
    <div class="modal-detail-row"><span class="modal-detail-lbl">Account Role</span><span class="modal-detail-val">${data.role.toUpperCase()}</span></div>
    <div class="modal-detail-row"><span class="modal-detail-lbl">Payout Amount</span><span class="modal-detail-val" style="color:var(--green)">${data.amount}</span></div>
    <div class="modal-detail-row"><span class="modal-detail-lbl">Payment Method</span><span class="modal-detail-val">${data.payment_method}</span></div>
    <div class="modal-detail-row"><span class="modal-detail-lbl">Country</span><span class="modal-detail-val">${data.country}</span></div>
    <div class="modal-detail-row" style="flex-direction:column;gap:6px;align-items:stretch">
      <span class="modal-detail-lbl" style="margin-bottom:2px">Account Details</span>
      <div style="background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:12px 14px">${detailHtml}</div>
    </div>
    <div class="modal-detail-row"><span class="modal-detail-lbl">Status</span><span class="modal-detail-val">
      <span class="badge badge-${badgeClass}">${data.status}</span>
    </span></div>
    <div class="modal-detail-row"><span class="modal-detail-lbl">Requested Date</span><span class="modal-detail-val">${data.created_at}</span></div>
    <div class="modal-detail-row"><span class="modal-detail-lbl">Due Timeline</span><span class="modal-detail-val">${data.due_by}</span></div>
    
    ${data.admin_note ? `
    <div class="modal-detail-row" style="flex-direction:column; gap:6px; align-items:stretch">
      <span class="modal-detail-lbl">Administrator Note</span>
      <div style="text-align:left; background:rgba(16,185,129,0.06); padding:12px 14px; border-radius:10px; border:1px solid rgba(16,185,129,0.15); width:100%; white-space:pre-wrap; font-size:0.83rem; line-height:1.5; color:#fff">${data.admin_note}</div>
    </div>` : ''}
    
    ${proofHtml}
  `;

  document.getElementById('withdrawal-detail-modal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeWithdrawalModal() {
  document.getElementById('withdrawal-detail-modal').classList.remove('open');
  document.body.style.overflow = '';
}

function closeModalOnBackdrop(e) {
  if (e.target.id === 'withdrawal-detail-modal') {
    closeWithdrawalModal();
  }
}
</script>

<?php require_once __DIR__ . '/partials/admin_foot.php'; ?>
