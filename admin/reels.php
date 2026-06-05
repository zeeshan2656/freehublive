<?php
// Admin — Dedicated Reels Management Dashboard
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? '')) {
    $bulk_action = $_POST['bulk_action'] ?? '';
    $reel_ids = $_POST['reel_ids'] ?? [];

    if (!empty($bulk_action) && !empty($reel_ids) && is_array($reel_ids)) {
        $ids = array_map('intval', $reel_ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        if ($bulk_action === 'approve') {
            db_query("UPDATE reels SET status='published' WHERE id IN ($placeholders)", $ids);
            flash('success', count($ids) . ' reel(s) approved.');
        } elseif ($bulk_action === 'reject') {
            db_query("UPDATE reels SET status='rejected' WHERE id IN ($placeholders)", $ids);
            flash('success', count($ids) . ' reel(s) rejected.');
        } elseif ($bulk_action === 'delete') {
            // Also delete reel video files
            $files = db_fetchAll("SELECT video_url FROM reels WHERE id IN ($placeholders)", $ids);
            foreach ($files as $f) {
                if ($f['video_url'] && !str_starts_with($f['video_url'], 'http')) {
                    @unlink(__DIR__ . '/../uploads/reels/' . $f['video_url']);
                }
            }
            db_query("DELETE FROM reels WHERE id IN ($placeholders)", $ids);
            flash('success', count($ids) . ' reel(s) deleted.');
        }
        redirect(BASE_URL . '/admin/reels.php?' . http_build_query($_GET));
    }

    $action = $_POST['action'] ?? '';
    $rid    = (int)($_POST['reel_id'] ?? 0);
    if ($action === 'approve') {
        db_update('reels', ['status' => 'published'], 'id=?', [$rid]);
        flash('success', 'Reel approved and published.');
    } elseif ($action === 'reject') {
        db_update('reels', ['status' => 'rejected'], 'id=?', [$rid]);
        flash('success', 'Reel rejected.');
    } elseif ($action === 'delete') {
        $r = db_fetch("SELECT video_url FROM reels WHERE id=?", [$rid]);
        if ($r && $r['video_url'] && !str_starts_with($r['video_url'], 'http')) {
            @unlink(__DIR__ . '/../uploads/reels/' . $r['video_url']);
        }
        db_query("DELETE FROM reels WHERE id=?", [$rid]);
        flash('success', 'Reel deleted.');
    }
    redirect(BASE_URL . '/admin/reels.php?' . http_build_query($_GET));
}

// ── Statistics Section ──
$stats = [
    'total' => (int)db_fetch("SELECT COUNT(*) AS c FROM reels")['c'],
    'total_views' => (int)db_fetch("SELECT COALESCE(SUM(views),0) AS c FROM reels")['c'],
    'published' => (int)db_fetch("SELECT COUNT(*) AS c FROM reels WHERE status='published'")['c'],
    'pending' => (int)db_fetch("SELECT COUNT(*) AS c FROM reels WHERE status='pending'")['c'],
    'rejected' => (int)db_fetch("SELECT COUNT(*) AS c FROM reels WHERE status='rejected'")['c'],
    'draft' => (int)db_fetch("SELECT COUNT(*) AS c FROM reels WHERE status='draft'")['c'],
];

// ── Advanced Filters ──
$filter = $_GET['filter'] ?? 'all';
$creator = trim($_GET['creator'] ?? '');
$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, (int)($_GET['page'] ?? 1));

$where = "1";
$params = [];

if ($filter !== 'all' && in_array($filter, ['published', 'pending', 'rejected', 'draft'])) {
    $where .= " AND r.status = ?";
    $params[] = $filter;
}
if ($creator !== '') {
    $where .= " AND (u.username LIKE ? OR u.channel_name LIKE ?)";
    $params[] = "%$creator%";
    $params[] = "%$creator%";
}
if ($from !== '' && $to !== '') {
    $where .= " AND DATE(r.created_at) BETWEEN ? AND ?";
    $params[] = $from;
    $params[] = $to;
} elseif ($from !== '') {
    $where .= " AND DATE(r.created_at) >= ?";
    $params[] = $from;
} elseif ($to !== '') {
    $where .= " AND DATE(r.created_at) <= ?";
    $params[] = $to;
}

// Sorting logic
$order_by = "r.created_at DESC";
if ($sort === 'most_viewed') {
    $order_by = "r.views DESC, r.created_at DESC";
} elseif ($sort === 'oldest') {
    $order_by = "r.created_at ASC";
}

$total = db_count('reels r JOIN users u ON u.id=r.user_id', $where, $params);
$pg    = paginate($total, 20, $page);

$reels = db_fetchAll(
    "SELECT r.*, u.username, u.channel_name, u.id AS uid
     FROM reels r JOIN users u ON u.id=r.user_id
     WHERE $where ORDER BY $order_by LIMIT 20 OFFSET {$pg['offset']}",
    $params
);

$pendingCount = db_count('reels', "status='pending'");

$meta_title = 'Reels Management';
require_once __DIR__ . '/partials/admin_head.php';
?>
<div class="admin-content">
  <div class="admin-page-header" style="justify-content:flex-end">
    <div class="flex gap-2" style="align-items:center">
      <?php if ($pendingCount > 0): ?>
      <span class="badge" style="background:var(--yellow);color:#1a1000"><?= $pendingCount ?> pending</span>
      <?php endif; ?>
    </div>
  </div>

  <?php foreach (get_flash() as $fl): ?><div class="alert alert-<?= $fl['type'] ?>"><?= e($fl['msg']) ?></div><?php endforeach; ?>

  <!-- Statistics Panel -->
  <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap:12px; margin-bottom:24px;">
    <div class="stat-card" style="padding:14px 18px;">
      <div class="stat-value" style="font-size:1.3rem;"><?= number_format($stats['total']) ?></div>
      <div class="stat-label">Total Reels</div>
    </div>
    <div class="stat-card" style="padding:14px 18px;">
      <div class="stat-value" style="font-size:1.3rem;"><?= number_format($stats['total_views']) ?></div>
      <div class="stat-label">Total Reel Views</div>
    </div>
    <div class="stat-card" style="padding:14px 18px; border-left: 3px solid var(--green);">
      <div class="stat-value" style="font-size:1.3rem; color:var(--green);"><?= number_format($stats['published']) ?></div>
      <div class="stat-label">Published</div>
    </div>
    <div class="stat-card" style="padding:14px 18px; border-left: 3px solid var(--yellow);">
      <div class="stat-value" style="font-size:1.3rem; color:var(--yellow);"><?= number_format($stats['pending']) ?></div>
      <div class="stat-label">Pending</div>
    </div>
    <div class="stat-card" style="padding:14px 18px; border-left: 3px solid var(--red);">
      <div class="stat-value" style="font-size:1.3rem; color:var(--red);"><?= number_format($stats['rejected']) ?></div>
      <div class="stat-label">Rejected</div>
    </div>
    <div class="stat-card" style="padding:14px 18px; border-left: 3px solid var(--text2);">
      <div class="stat-value" style="font-size:1.3rem; color:var(--text2);"><?= number_format($stats['draft']) ?></div>
      <div class="stat-label">Drafts</div>
    </div>
  </div>

  <!-- Advanced Filters Form -->
  <form method="GET" class="card" style="margin-bottom:24px; padding:18px; background:var(--bg2); border:1px solid var(--border)">
    <input type="hidden" name="page" value="1">
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:12px; align-items:flex-end;">
      
      <!-- Creator Filter -->
      <div class="form-group" style="margin-bottom:0">
        <label class="form-label">Creator Name</label>
        <input type="text" name="creator" value="<?= e($creator) ?>" placeholder="Search creator..." class="form-input">
      </div>

      <!-- Status Filter -->
      <div class="form-group" style="margin-bottom:0">
        <label class="form-label">Status</label>
        <select class="form-input form-select" name="filter">
          <option value="all" <?= $filter==='all'?'selected':'' ?>>All Statuses</option>
          <option value="published" <?= $filter==='published'?'selected':'' ?>>Published</option>
          <option value="pending" <?= $filter==='pending'?'selected':'' ?>>Pending</option>
          <option value="rejected" <?= $filter==='rejected'?'selected':'' ?>>Rejected</option>
          <option value="draft" <?= $filter==='draft'?'selected':'' ?>>Draft</option>
        </select>
      </div>

      <!-- Sorting Filter -->
      <div class="form-group" style="margin-bottom:0">
        <label class="form-label">Sort By</label>
        <select class="form-input form-select" name="sort">
          <option value="newest" <?= $sort==='newest'?'selected':'' ?>>Newest</option>
          <option value="most_viewed" <?= $sort==='most_viewed'?'selected':'' ?>>Most Viewed</option>
          <option value="oldest" <?= $sort==='oldest'?'selected':'' ?>>Oldest</option>
        </select>
      </div>

      <!-- Date Range Filter -->
      <div class="form-group" style="margin-bottom:0">
        <label class="form-label">Upload Date</label>
        <div class="smart-date-filter" data-preset="<?= e($_GET['date_preset'] ?? '') ?>">
          <button type="button" class="btn btn-outline smart-date-btn w-full" style="justify-content:space-between; height:42px; font-size:.82rem; width:100%;">
            <span>📅 <?= !empty($from) && !empty($to) ? e($from) . ' to ' . e($to) : 'Select Range' ?></span>
            <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
          </button>
          <input type="hidden" name="from" class="smart-from-val" value="<?= e($from) ?>">
          <input type="hidden" name="to" class="smart-to-val" value="<?= e($to) ?>">
          <input type="hidden" name="date_preset" value="<?= e($_GET['date_preset'] ?? '') ?>">
        </div>
      </div>

      <!-- Actions -->
      <div class="flex gap-2" style="margin-bottom:0">
        <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center; height:42px;">Filter</button>
        <a href="?" class="btn btn-outline" style="flex:1; justify-content:center; height:42px;">Reset</a>
      </div>

    </div>
  </form>

  <form method="POST" id="bulk-actions-form">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

    <!-- Bulk Actions Bar -->
    <div class="bulk-actions-bar card card-sm flex gap-2" style="margin-bottom: 16px; padding: 12px 18px; background: var(--bg2); border: 1px solid var(--border); display: none; align-items: center;">
      <span style="font-weight: 600; font-size: 0.85rem; color: var(--text2);">With Selected (<span id="selected-count">0</span>):</span>
      <button type="submit" name="bulk_action" value="approve" class="btn btn-sm btn-primary" style="background: var(--green); color: #fff; border: none; font-size: 0.8rem; padding: 5px 12px; border-radius: 4px;">Approve</button>
      <button type="submit" name="bulk_action" value="reject" class="btn btn-sm btn-outline" style="color: var(--yellow); border-color: var(--yellow); font-size: 0.8rem; padding: 4px 12px; border-radius: 4px;">Reject</button>
      <button type="submit" name="bulk_action" value="delete" class="btn btn-sm btn-outline" style="color: var(--red); border-color: var(--red); font-size: 0.8rem; padding: 4px 12px; border-radius: 4px;" onclick="return confirm('Are you sure you want to delete all selected reels?')">Delete</button>
    </div>

    <div class="table-wrap">
      <table class="compact-table">
        <thead><tr>
          <th style="width: 40px; text-align: center;"><input type="checkbox" id="select-all-reels" style="cursor: pointer;"></th>
          <th style="width: 70px;">Preview</th>
          <th>Title</th>
          <th>Creator</th>
          <th>Status</th>
          <th>Views &amp; Stats</th>
          <th>Upload Date</th>
          <th style="width:120px">Actions</th>
        </tr></thead>
        <tbody>
        <?php foreach ($reels as $r): ?>
        <tr>
          <td style="text-align: center;">
            <input type="checkbox" name="reel_ids[]" value="<?= $r['id'] ?>" class="reel-select-checkbox" style="cursor: pointer;">
          </td>
          <td>
            <video src="<?= reel_url($r['video_url']) ?>#t=0.1" muted playsinline style="width:40px; aspect-ratio:9/16; object-fit:cover; border-radius:4px; background:#000; display:block;"></video>
          </td>
          <td style="max-width:220px; font-weight:600;">
            <a href="<?= BASE_URL ?>/reels.php?id=<?= $r['id'] ?>" target="_blank" style="color:var(--accent); display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= e($r['title']) ?>">
              <?= e(truncate($r['title'], 50)) ?>
            </a>
          </td>
          <td>
            <a href="<?= BASE_URL ?>/admin/users.php?view=<?= $r['uid'] ?>" style="font-weight:500;">
              <?= e($r['channel_name'] ?? $r['username']) ?>
            </a>
          </td>
          <td>
            <span class="badge badge-<?= 
              $r['status'] === 'published' ? 'green' : (
              in_array($r['status'], ['pending', 'processing', 'uploading']) ? 'yellow' : (
              in_array($r['status'], ['rejected', 'failed']) ? 'red' : 'gray'
              )) ?>">
              <?= ucfirst($r['status']) ?>
            </span>
          </td>
          <td class="text-sm" style="font-weight:700;">👁️ <?= number_format((int)$r['views']) ?> views</td>
          <td class="text-xs text-muted"><?= date('M j, Y H:i', strtotime($r['created_at'])) ?></td>
          <td>
            <div class="flex gap-2" style="align-items:center">
              <a href="<?= BASE_URL ?>/reels.php?id=<?= $r['id'] ?>" target="_blank" class="btn btn-sm btn-outline" title="View Reel" style="padding: 4px 8px;">View</a>
              <?php if ($r['status'] === 'pending'): ?>
                <form method="POST" action="?<?= e(http_build_query($_GET)) ?>" style="display:inline">
                  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                  <input type="hidden" name="reel_id" value="<?= $r['id'] ?>">
                  <button name="action" value="approve" class="btn btn-sm btn-primary" style="background:var(--green); border:none; padding: 4px 8px;" title="Approve">Approve</button>
                </form>
              <?php endif; ?>
              <form method="POST" action="?<?= e(http_build_query($_GET)) ?>" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this reel?')">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="reel_id" value="<?= $r['id'] ?>">
                <button name="action" value="delete" class="btn btn-sm btn-outline" style="color:var(--red); border-color:rgba(239, 68, 68, 0.4); padding: 4px 8px;" title="Delete Reel">Delete</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$reels): ?>
        <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--text2)">No reels found</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </form>

  <!-- Pagination -->
  <?php if ($pg['pages'] > 1): ?>
  <div class="flex gap-2" style="margin-top:16px;justify-content:center">
    <?php if ($pg['has_prev']): ?>
      <a href="?<?= e(http_build_query(array_merge($_GET, ['page' => $page-1]))) ?>" class="btn btn-outline btn-sm">&laquo; Prev</a>
    <?php endif; ?>
    <span class="text-muted text-sm" style="align-self:center">Page <?= $page ?> of <?= $pg['pages'] ?></span>
    <?php if ($pg['has_next']): ?>
      <a href="?<?= e(http_build_query(array_merge($_GET, ['page' => $page+1]))) ?>" class="btn btn-outline btn-sm">Next &raquo;</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<style>
.compact-table tbody td {
  padding: 6px 12px !important;
  vertical-align: middle;
}
.compact-table thead th {
  padding: 8px 12px !important;
}
</style>

<script>
(function() {
    const selectAll = document.getElementById('select-all-reels');
    const checkboxes = document.querySelectorAll('.reel-select-checkbox');
    const bulkBar = document.querySelector('.bulk-actions-bar');
    const selectedCountSpan = document.getElementById('selected-count');

    function updateBulkBar() {
        const checkedCount = document.querySelectorAll('.reel-select-checkbox:checked').length;
        if (checkedCount > 0) {
            bulkBar.style.display = 'flex';
            selectedCountSpan.textContent = checkedCount;
        } else {
            bulkBar.style.display = 'none';
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => {
                cb.checked = selectAll.checked;
            });
            updateBulkBar();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            if (!cb.checked && selectAll) {
                selectAll.checked = false;
            }
            const allChecked = Array.from(checkboxes).every(c => c.checked);
            if (allChecked && selectAll) {
                selectAll.checked = true;
            }
            updateBulkBar();
        });
    });
})();
</script>

<?php require_once __DIR__ . '/partials/admin_foot.php'; ?>
