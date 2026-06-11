<?php
// Creator — My Reels
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin', 'creator']);
$site_theme = setting('active_theme', 'dark-minimal');
$primary    = setting('primary_color', '#6366f1');
$uid = auth_user()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? '')) {
    $bulk_action = $_POST['bulk_action'] ?? '';
    $reel_ids = $_POST['reel_ids'] ?? [];

    if (!empty($bulk_action) && !empty($reel_ids) && is_array($reel_ids)) {
        $ids = array_map('intval', $reel_ids);
        if ($bulk_action === 'delete') {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $params = array_merge([$uid], $ids);
            $own_reels = db_fetchAll("SELECT id, video_url FROM reels WHERE user_id=? AND id IN ($placeholders)", $params);
            if ($own_reels) {
                $own_ids = array_column($own_reels, 'id');
                $own_placeholders = implode(',', array_fill(0, count($own_ids), '?'));
                foreach ($own_reels as $f) {
                    if ($f['video_url'] && !str_starts_with($f['video_url'], 'http')) {
                        @unlink(REEL_PATH . $f['video_url']);
                    }
                }
                db_query("DELETE FROM reels WHERE id IN ($own_placeholders)", $own_ids);
                flash('success', count($own_ids) . ' reel(s) deleted.');
            }
        }
        redirect(BASE_URL . '/creator/reels.php?' . http_build_query($_GET));
    }

    $rid    = (int)($_POST['reel_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $own    = db_fetch("SELECT id FROM reels WHERE id=? AND user_id=?", [$rid, $uid]);
    if ($own) {
        if ($action === 'delete') {
            $v = db_fetch("SELECT video_url FROM reels WHERE id=?", [$rid]);
            if ($v) {
                if ($v['video_url'] && !str_starts_with($v['video_url'], 'http')) {
                    @unlink(REEL_PATH . $v['video_url']);
                }
            }
            db_query("DELETE FROM reels WHERE id=?", [$rid]);
            flash('success', 'Reel deleted.');
        }
        if ($action === 'edit_title') {
            $new_title = trim($_POST['title'] ?? '');
            db_update('reels', ['title' => empty($new_title) ? null : $new_title], 'id=?', [$rid]);
            flash('success', 'Reel title updated.');
        }
    }
    redirect(BASE_URL . '/creator/reels.php?' . http_build_query($_GET));
}

$page   = max(1, (int)($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$from   = trim($_GET['from'] ?? '');
$to     = trim($_GET['to'] ?? '');

$where  = "user_id = $uid";
$params = [];

if ($search !== '') {
    $where .= " AND title LIKE ?";
    $params[] = "%$search%";
}
if ($from !== '' && $to !== '') {
    $where .= " AND DATE(created_at) BETWEEN ? AND ?";
    $params[] = $from;
    $params[] = $to;
} elseif ($from !== '') {
    $where .= " AND DATE(created_at) >= ?";
    $params[] = $from;
} elseif ($to !== '') {
    $where .= " AND DATE(created_at) <= ?";
    $params[] = $to;
}

$total  = db_count('reels', $where, $params);
$pg     = paginate($total, 20, $page);
$reels  = db_fetchAll("SELECT * FROM reels WHERE $where ORDER BY created_at DESC LIMIT 20 OFFSET {$pg['offset']}", $params);

$meta_title = 'My Reels';
$header_actions = '
<div class="flex gap-2">
<a href="' . BASE_URL . '/creator/upload.php?mode=reel" class="btn btn-primary btn-sm flex gap-1 header-upload-btn" style="border-radius: 18px; padding: 6px 12px;" title="Upload Reel">
  <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="flex-shrink:0;">
    <polyline points="17 8 12 3 7 8"></polyline>
    <line x1="12" y1="3" x2="12" y2="15"></line>
    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
  </svg>
  <span>Upload Reel</span>
</a>
</div>';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container studio-container" style="max-width:1200px; margin:0 auto; padding:24px 16px 80px">
  
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
    <h1 style="font-size:1.6rem; font-weight:800; color:#fff; margin:0">My Reels</h1>
    <?= $header_actions ?>
  </div>

  <!-- Search & Filters -->
  <div class="card" style="padding:16px; margin-bottom:24px; background:var(--bg2); border:1px solid var(--border); border-radius:12px">
    <form method="GET" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center">
      <input type="text" name="search" class="form-input" placeholder="Search by title..." value="<?= e($search) ?>" style="flex:1; min-width:200px; border-radius:6px">
      
      <div style="display:flex; gap:8px; align-items:center">
        <span class="text-muted text-xs">From:</span>
        <input type="date" name="from" class="form-input" value="<?= e($from) ?>" style="width:130px; border-radius:6px">
        <span class="text-muted text-xs">To:</span>
        <input type="date" name="to" class="form-input" value="<?= e($to) ?>" style="width:130px; border-radius:6px">
      </div>

      <button type="submit" class="btn btn-outline" style="border-radius:6px">Filter</button>
      <?php if ($search || $from || $to): ?>
        <a href="<?= BASE_URL ?>/creator/reels.php" class="btn btn-gray" style="border-radius:6px">Reset</a>
      <?php endif; ?>
    </form>
  </div>

  <form method="POST" id="bulk-actions-form">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

    <!-- Bulk Actions Bar -->
    <div class="bulk-actions-bar card card-sm flex gap-2" style="margin-bottom:16px; padding:12px 18px; background:var(--bg2); border:1px solid var(--border); display:none; align-items:center; border-radius:8px">
      <span style="font-weight:600; font-size:0.85rem; color:var(--text2);">With Selected (<span id="selected-count">0</span>):</span>
      <button type="submit" name="bulk_action" value="delete" class="btn btn-sm btn-outline" style="color:var(--red); border-color:var(--red); font-size:0.8rem; padding:4px 12px; border-radius:4px" onclick="return confirm('Are you sure you want to delete all selected reels?')">Delete</button>
    </div>

    <div class="table-wrap" style="background:var(--bg2); border:1px solid var(--border); border-radius:12px; overflow:hidden">
      <table class="compact-table" style="width:100%; border-collapse:collapse; text-align:left">
        <thead>
          <tr style="border-bottom:1px solid var(--border); background:var(--bg3)">
            <th style="width:40px; text-align:center; padding:12px"><input type="checkbox" id="select-all-reels" style="cursor:pointer"></th>
            <th style="padding:12px">Preview</th>
            <th style="padding:12px">Title</th>
            <th style="padding:12px">Views & Likes</th>
            <th style="padding:12px">Upload Date</th>
            <th style="width:120px; padding:12px">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!empty($reels)): ?>
          <?php foreach ($reels as $r): ?>
          <tr style="border-bottom:1px solid var(--border)">
            <td style="text-align:center; padding:12px">
              <input type="checkbox" name="reel_ids[]" value="<?= $r['id'] ?>" class="reel-select-checkbox" style="cursor:pointer">
            </td>
            <td style="padding:12px">
              <video src="<?= reel_url($r['video_url']) ?>#t=0.1" muted playsinline style="width:50px; aspect-ratio:9/16; object-fit:cover; border-radius:4px; background:#000"></video>
            </td>
            <td style="max-width:300px; font-weight:600; padding:12px">
              <a href="<?= BASE_URL ?>/reels/<?= $r['id'] ?>" target="_blank" style="color:var(--accent); text-decoration:none">
                <?= $r['title'] ? e(truncate($r['title'], 50)) : '<em class="text-muted" style="font-size:0.8rem">Untitled Reel</em>' ?>
              </a>
              <div class="text-xs text-muted" style="margin-top:2px"><?= e($r['status']) ?></div>
            </td>
            <td style="padding:12px">
              <div style="font-size:0.85rem; font-weight:600">👀 <?= format_number($r['views']) ?></div>
              <div class="text-xs text-muted">👍 <?= format_number($r['likes']) ?> likes</div>
            </td>
            <td style="font-size:0.82rem; color:var(--text2); padding:12px">
              <?= date('M d, Y', strtotime($r['created_at'])) ?>
            </td>
            <td style="padding:12px">
              <div style="display:flex; gap:6px">
                <button type="button" class="btn btn-sm btn-outline" style="border-radius:4px; font-size:0.75rem; padding:4px 8px" onclick="editReelTitle(<?= $r['id'] ?>, '<?= e($r['title'] ?? '') ?>')">Edit</button>
                <button type="button" class="btn btn-sm btn-outline" style="color:var(--red); border-color:var(--red); border-radius:4px; font-size:0.75rem; padding:4px 8px" onclick="deleteReel(<?= $r['id'] ?>)">Delete</button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" style="text-align:center; padding:40px; color:var(--text2)">
              No reels yet. <a href="<?= BASE_URL ?>/creator/upload.php?mode=reel" style="color:var(--accent)">Upload your first reel</a>
            </td>
          </tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </form>

  <?php if ($pg['pages'] > 1): ?>
    <div style="display:flex; justify-content:center; gap:8px; margin-top:24px">
      <?php for ($i=1; $i<=$pg['pages']; $i++): ?>
        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>" class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-outline' ?>" style="width:32px; justify-content:center; border-radius:4px"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>

</div>

<!-- Hidden action forms -->
<form id="single-action-form" method="POST" style="display:none">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <input type="hidden" name="reel_id" id="action-reel-id">
  <input type="hidden" name="action" id="action-type">
  <input type="hidden" name="title" id="action-title">
</form>

<script>
function editReelTitle(id, currentTitle) {
  const newTitle = prompt("Edit Reel Title:", currentTitle);
  if (newTitle !== null) {
    document.getElementById('action-reel-id').value = id;
    document.getElementById('action-type').value = 'edit_title';
    document.getElementById('action-title').value = newTitle;
    document.getElementById('single-action-form').submit();
  }
}

function deleteReel(id) {
  if (confirm("Are you sure you want to delete this reel?")) {
    document.getElementById('action-reel-id').value = id;
    document.getElementById('action-type').value = 'delete';
    document.getElementById('single-action-form').submit();
  }
}

// Bulk action handling
document.addEventListener('DOMContentLoaded', () => {
  const selectAll = document.getElementById('select-all-reels');
  const checkboxes = document.querySelectorAll('.reel-select-checkbox');
  const bulkBar = document.querySelector('.bulk-actions-bar');
  const selectedCount = document.getElementById('selected-count');

  if (selectAll) {
    selectAll.addEventListener('change', function() {
      checkboxes.forEach(cb => cb.checked = this.checked);
      updateBulkBar();
    });
  }

  checkboxes.forEach(cb => {
    cb.addEventListener('change', updateBulkBar);
  });

  function updateBulkBar() {
    const checked = document.querySelectorAll('.reel-select-checkbox:checked');
    if (checked.length > 0) {
      selectedCount.textContent = checked.length;
      bulkBar.style.display = 'flex';
    } else {
      bulkBar.style.display = 'none';
      if (selectAll) selectAll.checked = false;
    }
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
