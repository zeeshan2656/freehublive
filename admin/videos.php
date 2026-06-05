<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$approvalMode = setting('video_approval_mode', 'manual');

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? '')) {
    $bulk_action = $_POST['bulk_action'] ?? '';
    $video_ids = $_POST['video_ids'] ?? [];

    if (!empty($bulk_action) && !empty($video_ids) && is_array($video_ids)) {
        $ids = array_map('intval', $video_ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        if ($bulk_action === 'approve') {
            db_query("UPDATE videos SET status='published', published_at=? WHERE id IN ($placeholders)", array_merge([date('Y-m-d H:i:s')], $ids));
            flash('success', count($ids) . ' video(s) approved.');
        } elseif ($bulk_action === 'reject') {
            db_query("UPDATE videos SET status='rejected' WHERE id IN ($placeholders)", $ids);
            flash('success', count($ids) . ' video(s) rejected.');
        } elseif ($bulk_action === 'delete') {
            // Also delete video files and thumbnails
            $files = db_fetchAll("SELECT video_url, thumbnail FROM videos WHERE id IN ($placeholders)", $ids);
            foreach ($files as $f) {
                if ($f['video_url'] && !str_starts_with($f['video_url'], 'http')) @unlink(VIDEO_PATH . $f['video_url']);
                if ($f['thumbnail'] && !str_starts_with($f['thumbnail'], 'http')) @unlink(THUMB_PATH . $f['thumbnail']);
            }
            db_query("DELETE FROM videos WHERE id IN ($placeholders)", $ids);
            flash('success', count($ids) . ' video(s) deleted.');
        }
        redirect(BASE_URL . '/admin/videos.php?' . http_build_query($_GET));
    }

    $action = $_POST['action'] ?? '';
    $vid    = (int)($_POST['video_id'] ?? 0);
    $note   = trim($_POST['approval_note'] ?? '');
    if ($action === 'approve') {
        db_update('videos', ['status' => 'published', 'published_at' => date('Y-m-d H:i:s'), 'approval_note' => $note ?: null], 'id=?', [$vid]);
        flash('success', 'Video approved and published.');
    } elseif ($action === 'reject') {
        db_update('videos', ['status' => 'rejected', 'approval_note' => $note ?: null], 'id=?', [$vid]);
        flash('success', 'Video rejected.');
    } elseif ($action === 'delete') {
        // Also delete video file
        $v = db_fetch("SELECT video_url, thumbnail FROM videos WHERE id=?", [$vid]);
        if ($v) {
            if ($v['video_url'] && !str_starts_with($v['video_url'], 'http')) @unlink(VIDEO_PATH . $v['video_url']);
            if ($v['thumbnail'] && !str_starts_with($v['thumbnail'], 'http')) @unlink(THUMB_PATH . $v['thumbnail']);
        }
        db_query("DELETE FROM videos WHERE id=?", [$vid]);
        flash('success', 'Video deleted.');
    } elseif ($action === 'feature') {
        db_update('videos', ['featured' => 1], 'id=?', [$vid]);
        db_update('videos', ['featured' => 0], 'id!=?', [$vid]);
        flash('success', 'Video featured on homepage.');
    } elseif ($action === 'toggle_approval_mode') {
        $newMode = $approvalMode === 'auto' ? 'manual' : 'auto';
        db_query("INSERT INTO settings (`key`,`value`,`group`) VALUES ('video_approval_mode',?,'content') ON DUPLICATE KEY UPDATE `value`=?", [$newMode, $newMode]);
        fh_cache_delete('fh_settings_cache');
        flash('success', 'Approval mode switched to: ' . strtoupper($newMode));
    }
    // Redirect back preserving GET parameters
    redirect(BASE_URL . '/admin/videos.php?' . http_build_query($_GET));
}

// ── Statistics Section ──
$stats = [
    'total' => (int)db_fetch("SELECT COUNT(*) AS c FROM videos")['c'],
    'total_views' => (int)db_fetch("SELECT COALESCE(SUM(views),0) AS c FROM videos")['c'],
    'published' => (int)db_fetch("SELECT COUNT(*) AS c FROM videos WHERE status='published'")['c'],
    'pending' => (int)db_fetch("SELECT COUNT(*) AS c FROM videos WHERE status='pending'")['c'],
    'rejected' => (int)db_fetch("SELECT COUNT(*) AS c FROM videos WHERE status='rejected'")['c'],
    'draft' => (int)db_fetch("SELECT COUNT(*) AS c FROM videos WHERE status='draft'")['c'],
];

// ── Advanced Filters ──
$filter = $_GET['filter'] ?? 'all';
$creator = trim($_GET['creator'] ?? '');
$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');
$type = $_GET['type'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));

$where = "1";
$params = [];



if ($filter !== 'all' && in_array($filter, ['published', 'pending', 'rejected', 'draft'])) {
    $where .= " AND v.status = ?";
    $params[] = $filter;
}
if ($creator !== '') {
    $where .= " AND (u.username LIKE ? OR u.channel_name LIKE ?)";
    $params[] = "%$creator%";
    $params[] = "%$creator%";
}
if ($from !== '' && $to !== '') {
    $where .= " AND DATE(v.created_at) BETWEEN ? AND ?";
    $params[] = $from;
    $params[] = $to;
} elseif ($from !== '') {
    $where .= " AND DATE(v.created_at) >= ?";
    $params[] = $from;
} elseif ($to !== '') {
    $where .= " AND DATE(v.created_at) <= ?";
    $params[] = $to;
}
// End date check

$total = db_count('videos v JOIN users u ON u.id=v.user_id', $where, $params);
$pg    = paginate($total, 20, $page);

$videos = db_fetchAll(
    "SELECT v.*,u.username,u.channel_name,u.id AS uid
     FROM videos v JOIN users u ON u.id=v.user_id
     WHERE $where ORDER BY v.created_at DESC LIMIT 20 OFFSET {$pg['offset']}",
    $params
);

$pendingCount = db_count('videos', "status='pending'");

$meta_title = 'Video Management';
require_once __DIR__ . '/partials/admin_head.php';
?>
<div class="admin-content">
  <div class="admin-page-header" style="justify-content:flex-end">
    <div class="flex gap-2" style="align-items:center">
      <!-- Approval Mode Toggle -->
      <form method="POST" action="?<?= e(http_build_query($_GET)) ?>" style="display:inline">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <button name="action" value="toggle_approval_mode" class="btn btn-sm <?= $approvalMode === 'auto' ? 'btn-primary' : 'btn-outline' ?>"
                title="Click to toggle approval mode" style="<?= $approvalMode === 'auto' ? 'background:var(--green)' : 'border-color:var(--yellow);color:var(--yellow)' ?>">
          <?= $approvalMode === 'auto' ? '⚡ Auto Approval ON' : '🔍 Manual Approval' ?>
        </button>
      </form>
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
      <div class="stat-label">Total Videos</div>
    </div>
    <div class="stat-card" style="padding:14px 18px;">
      <div class="stat-value" style="font-size:1.3rem;"><?= number_format($stats['total_views']) ?></div>
      <div class="stat-label">Total Video Views</div>
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
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:12px; align-items:flex-end;">
      
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



      <!-- Date Range with Global Smart Filter -->
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

      <!-- Filter Button Actions -->

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
      <button type="submit" name="bulk_action" value="delete" class="btn btn-sm btn-outline" style="color: var(--red); border-color: var(--red); font-size: 0.8rem; padding: 4px 12px; border-radius: 4px;" onclick="return confirm('Are you sure you want to delete all selected videos?')">Delete</button>
    </div>

    <div class="table-wrap">
      <table class="compact-table">
        <thead><tr>
          <th style="width: 40px; text-align: center;"><input type="checkbox" id="select-all-videos" style="cursor: pointer;"></th>
          <th>Thumbnail</th>
          <th>Title</th>
          <th>Creator</th>
          <th>Status</th>
          <th>Views &amp; Stats</th>
          <th>Ad Stats</th>
          <th>Upload Date</th>
          <th style="width:120px">Actions</th>
        </tr></thead>
        <tbody>
        <?php foreach ($videos as $v): ?>
        <tr>
          <td style="text-align: center;">
            <input type="checkbox" name="video_ids[]" value="<?= $v['id'] ?>" class="video-select-checkbox" style="cursor: pointer;">
          </td>
          <td>
            <?php if ((int)$v['is_reel'] === 1): ?>
              <video src="<?= video_url($v['video_url']) ?>#t=0.1" muted playsinline style="width:50px; aspect-ratio:9/16; object-fit:cover; border-radius:4px; background:#000;"></video>
            <?php else: ?>
              <img src="<?= thumb_url($v['thumbnail']) ?>" style="width:72px;aspect-ratio:16/9;object-fit:cover;border-radius:4px">
            <?php endif; ?>
          </td>
          <td style="max-width:200px; font-weight:600;">
            <a href="<?= BASE_URL ?>/watch.php?v=<?= $v['id'] ?>" target="_blank" style="color:var(--accent); display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= e($v['title']) ?>">
              <?= e(truncate($v['title'], 50)) ?>
            </a>

            <?php if (!empty($v['approval_note'])): ?>
            <div style="font-size:.72rem;color:var(--yellow);margin-top:2px">Note: <?= e($v['approval_note']) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <a href="<?= BASE_URL ?>/admin/users.php?view=<?= $v['uid'] ?>" style="font-weight:500;">
              <?= e($v['channel_name'] ?? $v['username']) ?>
            </a>
          </td>
          <td>
            <span class="badge badge-<?= 
              $v['status'] === 'published' ? 'green' : (
              in_array($v['status'], ['pending', 'processing', 'uploading']) ? 'yellow' : (
              in_array($v['status'], ['rejected', 'failed']) ? 'red' : 'gray'
              )) ?>">
              <?= ucfirst($v['status']) ?>
            </span>
          </td>
          <td class="text-sm">👁️ <?= number_format((int)$v['views']) ?> views</td>
          <td class="text-sm">
            <div style="white-space:nowrap">📺 <?= number_format((int)$v['ad_impressions']) ?> imps</div>
            <div style="white-space:nowrap">🖱️ <?= number_format((int)$v['ad_clicks']) ?> clicks</div>
          </td>
          <td class="text-xs text-muted"><?= date('M j, Y H:i', strtotime($v['created_at'])) ?></td>
          <td>
            <div class="flex gap-2" style="align-items:center">
              <a href="<?= BASE_URL ?>/watch.php?v=<?= $v['id'] ?>" target="_blank" class="btn btn-sm btn-outline" title="View Video" style="padding: 4px 8px;">View</a>
              <a href="<?= BASE_URL ?>/admin/video_edit.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-outline" title="Edit Video" style="padding: 4px 8px;">Edit</a>
              <form method="POST" action="?<?= e(http_build_query($_GET)) ?>" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this video?')">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="video_id" value="<?= $v['id'] ?>">
                <button name="action" value="delete" class="btn btn-sm btn-outline" style="color:var(--red); border-color:rgba(239, 68, 68, 0.4); padding: 4px 8px;" title="Delete Video">Delete</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$videos): ?>
        <tr><td colspan="10" style="text-align:center;padding:32px;color:var(--text2)">No videos found</td></tr>
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
    const selectAll = document.getElementById('select-all-videos');
    const checkboxes = document.querySelectorAll('.video-select-checkbox');
    const bulkBar = document.querySelector('.bulk-actions-bar');
    const selectedCountSpan = document.getElementById('selected-count');

    function updateBulkBar() {
        const checkedCount = document.querySelectorAll('.video-select-checkbox:checked').length;
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
