<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$approvalMode = setting('video_approval_mode', 'manual');

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? '')) {
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
        cache_delete('settings');
        flash('success', 'Approval mode switched to: ' . strtoupper($newMode));
    }
    // Redirect back preserving GET parameters
    redirect(BASE_URL . '/admin/videos.php?' . http_build_query($_GET));
}

// ── Statistics Section ──
$stats = [
    'total' => (int)db_fetch("SELECT COUNT(*) AS c FROM videos")['c'],
    'watch_time' => (int)db_fetch("SELECT COALESCE(SUM(watch_time),0) AS c FROM videos")['c'],
    'earnings' => (float)db_fetch("SELECT COALESCE(SUM(revenue),0) AS c FROM videos")['c'],
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
$min_watch = trim($_GET['min_watch'] ?? '');
$max_watch = trim($_GET['max_watch'] ?? '');
$min_earn = trim($_GET['min_earn'] ?? '');
$max_earn = trim($_GET['max_earn'] ?? '');
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
if ($min_watch !== '') {
    $where .= " AND v.watch_time >= ?";
    $params[] = (int)$min_watch;
}
if ($max_watch !== '') {
    $where .= " AND v.watch_time <= ?";
    $params[] = (int)$max_watch;
}
if ($min_earn !== '') {
    $where .= " AND v.revenue >= ?";
    $params[] = (float)$min_earn;
}
if ($max_earn !== '') {
    $where .= " AND v.revenue <= ?";
    $params[] = (float)$max_earn;
}

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
      <div class="stat-value" style="font-size:1.3rem;"><?= format_duration($stats['watch_time']) ?></div>
      <div class="stat-label">Total Watch Time</div>
    </div>
    <div class="stat-card" style="padding:14px 18px;">
      <div class="stat-value" style="font-size:1.3rem; color:var(--green);">$<?= number_format($stats['earnings'], 2) ?></div>
      <div class="stat-label">Creator Earnings</div>
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

      <!-- Watch Time Range -->
      <div class="form-group" style="margin-bottom:0">
        <label class="form-label">Watch Time (sec)</label>
        <div class="flex gap-2">
          <input type="number" name="min_watch" value="<?= e($min_watch) ?>" placeholder="Min" class="form-input" style="flex:1">
          <span class="text-muted" style="align-self:center">-</span>
          <input type="number" name="max_watch" value="<?= e($max_watch) ?>" placeholder="Max" class="form-input" style="flex:1">
        </div>
      </div>

      <!-- Earning Range -->
      <div class="form-group" style="margin-bottom:0">
        <label class="form-label">Earnings (USD)</label>
        <div class="flex gap-2">
          <input type="number" step="0.01" name="min_earn" value="<?= e($min_earn) ?>" placeholder="Min" class="form-input" style="flex:1">
          <span class="text-muted" style="align-self:center">-</span>
          <input type="number" step="0.01" name="max_earn" value="<?= e($max_earn) ?>" placeholder="Max" class="form-input" style="flex:1">
        </div>
      </div>

      <!-- Actions -->
      <div class="flex gap-2" style="margin-bottom:0">
        <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center; height:42px;">Filter</button>
        <a href="?" class="btn btn-outline" style="flex:1; justify-content:center; height:42px;">Reset</a>
      </div>

    </div>
  </form>

  <div class="table-wrap">
    <table>
      <thead><tr>
        <th>Thumbnail</th>
        <th>Title</th>
        <th>Creator</th>
        <th>Status</th>
        <th>Views &amp; Stats</th>
        <th>Watch Time</th>
        <th>Earnings</th>
        <th>Upload Date</th>
        <th style="min-width:160px">Actions</th>
      </tr></thead>
      <tbody>
      <?php foreach ($videos as $v): ?>
      <tr>
        <td><img src="<?= thumb_url($v['thumbnail']) ?>" style="width:88px;aspect-ratio:16/9;object-fit:cover;border-radius:4px"></td>
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
          <span class="badge badge-<?= $v['status']==='published'?'green':($v['status']==='pending'?'yellow':($v['status']==='rejected'?'red':'gray')) ?>">
            <?= ucfirst($v['status']) ?>
          </span>
        </td>
        <td class="text-sm">👁️ <?= number_format((int)$v['views']) ?> views</td>
        <td class="text-sm"><?= format_duration((int)($v['watch_time'] ?? 0)) ?></td>
        <td class="text-sm font-bold" style="color:var(--green)">$<?= number_format((float)($v['revenue'] ?? 0), 2) ?></td>
        <td class="text-xs text-muted"><?= date('M j, Y H:i', strtotime($v['created_at'])) ?></td>
        <td>
          <form method="POST" action="?<?= e(http_build_query($_GET)) ?>" style="display:flex; flex-direction:column; gap:4px; min-width:160px;">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="video_id" value="<?= $v['id'] ?>">
            
            <div class="flex gap-1" style="flex-wrap:wrap">
              <!-- View -->
              <a href="<?= BASE_URL ?>/watch.php?v=<?= $v['id'] ?>" target="_blank" class="btn btn-sm btn-outline" title="View Video" style="flex:1; justify-content:center;">👁️</a>
              
              <!-- Edit -->
              <a href="<?= BASE_URL ?>/admin/video_edit.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-outline" title="Edit Video" style="flex:1; justify-content:center;">✏️</a>
              
              <!-- Delete -->
              <button name="action" value="delete" class="btn btn-sm btn-outline" title="Delete Video" style="color:var(--red); flex:1; justify-content:center;" onclick="return confirm('Delete this video?')">🗑️</button>
            </div>

            <?php if ($v['status'] === 'pending'): ?>
              <input type="text" name="approval_note" placeholder="Admin note (optional)" class="form-input" style="font-size:.72rem; padding:4px 8px; margin-top:2px; height:26px;">
            <?php endif; ?>

            <div class="flex gap-1" style="margin-top:2px;">
              <!-- Approve -->
              <?php if ($v['status'] !== 'published'): ?>
                <button name="action" value="approve" class="btn btn-sm" style="background:var(--green); color:#fff; flex:1; justify-content:center; font-size:.75rem; padding:4px;">Approve</button>
              <?php endif; ?>
              <!-- Reject -->
              <?php if ($v['status'] !== 'rejected'): ?>
                <button name="action" value="reject" class="btn btn-sm btn-outline" style="color:var(--red); flex:1; justify-content:center; font-size:.75rem; padding:4px;">Reject</button>
              <?php endif; ?>
            </div>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$videos): ?>
      <tr><td colspan="9" style="text-align:center;padding:32px;color:var(--text2)">No videos found</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

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
<?php require_once __DIR__ . '/partials/admin_foot.php'; ?>
