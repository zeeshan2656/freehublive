<?php
// Creator — My Videos
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin']);
$site_theme = setting('active_theme', 'dark-minimal');
$primary    = setting('primary_color', '#6366f1');
$uid = auth_user()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? '')) {
    $bulk_action = $_POST['bulk_action'] ?? '';
    $video_ids = $_POST['video_ids'] ?? [];

    if (!empty($bulk_action) && !empty($video_ids) && is_array($video_ids)) {
        $ids = array_map('intval', $video_ids);
        if ($bulk_action === 'delete') {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $params = array_merge([$uid], $ids);
            $own_videos = db_fetchAll("SELECT id, video_url, thumbnail FROM videos WHERE user_id=? AND id IN ($placeholders)", $params);
            if ($own_videos) {
                $own_ids = array_column($own_videos, 'id');
                $own_placeholders = implode(',', array_fill(0, count($own_ids), '?'));
                foreach ($own_videos as $f) {
                    if ($f['video_url'] && !str_starts_with($f['video_url'], 'http')) @unlink(VIDEO_PATH . $f['video_url']);
                    if ($f['thumbnail'] && !str_starts_with($f['thumbnail'], 'http')) @unlink(THUMB_PATH . $f['thumbnail']);
                }
                db_query("DELETE FROM videos WHERE id IN ($own_placeholders)", $own_ids);
                flash('success', count($own_ids) . ' video(s) deleted.');
            }
        }
        redirect(BASE_URL . '/creator/videos.php?' . http_build_query($_GET));
    }

    $vid    = (int)($_POST['video_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $own    = db_fetch("SELECT id FROM videos WHERE id=? AND user_id=?", [$vid, $uid]);
    if ($own) {
        if ($action === 'delete') {
            $v = db_fetch("SELECT video_url, thumbnail FROM videos WHERE id=?", [$vid]);
            if ($v) {
                if ($v['video_url'] && !str_starts_with($v['video_url'], 'http')) @unlink(VIDEO_PATH . $v['video_url']);
                if ($v['thumbnail'] && !str_starts_with($v['thumbnail'], 'http')) @unlink(THUMB_PATH . $v['thumbnail']);
            }
            db_query("DELETE FROM videos WHERE id=?", [$vid]);
            flash('success', 'Video deleted.');
        }
        if ($action === 'toggle_comments') {
            db_query("UPDATE videos SET allow_comments=1-allow_comments WHERE id=?", [$vid]);
            flash('success', 'Comments toggled.');
        }
        if ($action === 'visibility' && in_array($_POST['visibility'] ?? '', ['public', 'unlisted', 'private'])) {
            db_update('videos', ['visibility' => $_POST['visibility']], 'id=?', [$vid]);
            flash('success', 'Visibility updated.');
        }
    }
    redirect(BASE_URL . '/creator/videos.php?' . http_build_query($_GET));
}

$page   = max(1, (int)($_GET['page'] ?? 1));
$filter = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$from   = trim($_GET['from'] ?? '');
$to     = trim($_GET['to'] ?? '');
$min_earn  = trim($_GET['min_earn'] ?? '');
$max_earn  = trim($_GET['max_earn'] ?? '');

$where  = "user_id = $uid";
$params = [];

if ($filter !== 'all') {
    $where .= " AND status = ?";
    $params[] = $filter;
}
if ($search !== '') {
    $where .= " AND (title LIKE ? OR tags LIKE ?)";
    $params[] = "%$search%";
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

if ($min_earn !== '' || $max_earn !== '') {
    $c_placements = array_filter(array_map('trim', explode(',', setting('creator_eligible_placements', ''))), 'strlen');
    if (!empty($c_placements)) {
        $place_placeholders = implode(',', array_fill(0, count($c_placements), '?'));
        $subquery = "(SELECT COALESCE(SUM(al.earnings_creator), 0) FROM ad_logs al WHERE al.video_id = id AND al.creator_id = user_id AND al.placement IN ($place_placeholders))";
        
        if ($min_earn !== '') {
            $where .= " AND $subquery >= ?";
            $params = array_merge($params, $c_placements, [(float)$min_earn]);
        }
        if ($max_earn !== '') {
            $where .= " AND $subquery <= ?";
            $params = array_merge($params, $c_placements, [(float)$max_earn]);
        }
    } else {
        if ($min_earn !== '' && (float)$min_earn > 0) {
            $where .= " AND 1=0";
        }
    }
}

$total  = db_count('videos', $where, $params);
$pg     = paginate($total, 20, $page);
$videos = db_fetchAll("SELECT * FROM videos WHERE $where ORDER BY created_at DESC LIMIT 20 OFFSET {$pg['offset']}", $params);
$video_ids = array_column($videos, 'id');
$earnings_map = fh_creator_video_earnings_map($uid, $video_ids);

// Duration sync removed for performance — synced via watch.php instead
$meta_title = 'My Videos';
$header_actions = '
<style>
@media (max-width: 576px) {
  .header-upload-btn span { display: none !important; }
  .header-upload-btn { padding: 8px !important; width: 34px; height: 34px; justify-content: center; border-radius: 50% !important; }
}
</style>
<div class="flex gap-2">
<a href="' . BASE_URL . '/creator/upload.php" class="btn btn-primary btn-sm flex gap-1 header-upload-btn" style="border-radius: 18px; padding: 6px 12px;" title="Upload Video">
  <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="flex-shrink:0;">
    <polyline points="17 8 12 3 7 8"></polyline>
    <line x1="12" y1="3" x2="12" y2="15"></line>
    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
  </svg>
  <span>Upload Video</span>
</a>';



$header_actions .= '</div>';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">

    <?php foreach (get_flash() as $fl): ?>
      <div class="alert alert-<?= $fl['type'] === 'error' ? 'error' : $fl['type'] ?>" style="margin-bottom:16px"><?= e($fl['msg']) ?></div>
    <?php endforeach; ?>

    <div class="flex gap-2" style="margin-bottom:16px">
      <?php 
      foreach(['all','published','pending','rejected','draft'] as $s): 
        $tab_query = $_GET;
        $tab_query['status'] = $s;
        $tab_query['page'] = 1;
      ?>
      <a href="?<?= e(http_build_query($tab_query)) ?>" class="btn btn-sm <?= $filter===$s?'btn-primary':'btn-outline' ?>"><?= ucfirst($s) ?></a>
      <?php endforeach; ?>
    </div>

    <!-- Advanced Filters Form -->
    <style>
      .creator-filter-flex {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        column-gap: 24px;
        align-items: flex-end;
      }
      .creator-filter-flex > .form-group {
        margin-bottom: 0;
      }
      .filter-search {
        flex: 1 1 180px;
      }
      .filter-type {
        flex: 1 1 150px;
      }
      .filter-date-range {
        flex: 2 1 300px;
      }
      .filter-earnings {
        flex: 1.5 1 220px;
      }
      .filter-actions {
        flex: 1 1 180px;
        margin-bottom: 0;
      }
    </style>
    <form method="GET" class="card" style="margin-bottom:24px; padding:18px; background:var(--bg2); border:1px solid var(--border)">
      <input type="hidden" name="page" value="1">
      <input type="hidden" name="status" value="<?= e($filter) ?>">
      <div class="creator-filter-flex">
        
        <!-- Search Keyword -->
        <div class="form-group filter-search">
          <label class="form-label" style="font-size:0.8rem; font-weight:600; margin-bottom:4px; display:block;">Search Title</label>
          <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search title or tag..." class="form-input" style="height:38px; font-size:0.85rem;">
        </div>



        <!-- Date Range Filter -->
        <div class="form-group filter-date-range">
          <label class="form-label" style="font-size:0.8rem; font-weight:600; margin-bottom:4px; display:block;">Upload Date Range</label>
          <div class="flex gap-2">
            <input type="date" name="from" value="<?= e($from) ?>" class="form-input" style="flex:1; height:38px; font-size:0.85rem; padding: 4px 8px;">
            <span class="text-muted" style="align-self:center; font-size:0.85rem;">to</span>
            <input type="date" name="to" value="<?= e($to) ?>" class="form-input" style="flex:1; height:38px; font-size:0.85rem; padding: 4px 8px;">
          </div>
        </div>

        <!-- Earnings Filter -->
        <div class="form-group filter-earnings">
          <label class="form-label" style="font-size:0.8rem; font-weight:600; margin-bottom:4px; display:block;">Earnings (USD)</label>
          <div class="flex gap-2">
            <input type="number" step="0.01" name="min_earn" value="<?= e($min_earn) ?>" placeholder="Min" class="form-input" style="flex:1; height:38px; font-size:0.85rem;">
            <span class="text-muted" style="align-self:center; font-size:0.85rem;">-</span>
            <input type="number" step="0.01" name="max_earn" value="<?= e($max_earn) ?>" placeholder="Max" class="form-input" style="flex:1; height:38px; font-size:0.85rem;">
          </div>
        </div>

        <!-- Filter Actions -->
        <div class="flex gap-2 filter-actions">
          <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center; height:38px; font-size:0.85rem; padding: 0;">Filter</button>
          <a href="?status=<?= e($filter) ?>" class="btn btn-outline" style="flex:1; justify-content:center; height:38px; font-size:0.85rem; display:flex; align-items:center;">Reset</a>
        </div>

      </div>
    </form>

    <form method="POST" id="bulk-actions-form">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

      <!-- Bulk Actions Bar -->
      <div class="bulk-actions-bar card card-sm flex gap-2" style="margin-bottom: 16px; padding: 12px 18px; background: var(--bg2); border: 1px solid var(--border); display: none; align-items: center;">
        <span style="font-weight: 600; font-size: 0.85rem; color: var(--text2);">With Selected (<span id="selected-count">0</span>):</span>
        <button type="submit" name="bulk_action" value="delete" class="btn btn-sm btn-outline" style="color: var(--red); border-color: var(--red); font-size: 0.8rem; padding: 4px 12px; border-radius: 4px;" onclick="return confirm('Are you sure you want to delete all selected videos?')">Delete</button>
      </div>

      <div class="table-wrap">
        <table class="compact-table">
          <thead><tr>
            <th style="width: 40px; text-align: center;"><input type="checkbox" id="select-all-videos" style="cursor: pointer;"></th>
            <th>Thumbnail</th>
            <th>Title</th>
            <th>Status</th>
            <th>Views &amp; Likes</th>
            <th>Duration</th>
            <th>Ad Stats</th>
            <th>Earnings</th>
            <th>Upload Date</th>
            <th style="width:120px">Actions</th>
          </tr></thead>
          <tbody>
          <?php foreach ($videos as $v): ?>
          <tr>
            <td style="text-align: center;">
              <input type="checkbox" name="video_ids[]" value="<?= $v['id'] ?>" class="video-select-checkbox" style="cursor: pointer;">
            </td>
            <td><img src="<?= thumb_url($v['thumbnail']) ?>" style="width:72px;aspect-ratio:16/9;object-fit:cover;border-radius:4px" loading="lazy"></td>
            <td style="max-width:200px; font-weight:600;">
              <a href="<?= BASE_URL ?>/watch.php?v=<?= $v['id'] ?>" target="_blank" style="color:var(--accent); display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= e($v['title']) ?>">
                <?= e(truncate($v['title'], 50)) ?>
              </a>

              <div class="text-xs text-muted" style="margin-top:2px"><?= e($v['visibility']) ?></div>
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
            <td class="text-sm">
              <div>👁️ <?= number_format((int)$v['views']) ?> views</div>
              <div style="margin-top:2px">👍 <?= number_format((int)$v['likes']) ?> likes</div>
            </td>
            <td class="text-sm"><?= format_duration((int)$v['duration']) ?></td>
            <td class="text-sm">
              <div style="white-space:nowrap">📺 <?= number_format((int)$v['ad_impressions']) ?> imps</div>
              <div style="white-space:nowrap; margin-top:2px">🖱️ <?= number_format((int)$v['ad_clicks']) ?> clicks</div>
            </td>
            <td class="text-sm font-bold" style="color:var(--green)">
              <?= e(fh_format_money($earnings_map[$v['id']] ?? 0.0, fh_user_currency())) ?>
            </td>
            <td class="text-xs text-muted"><?= date('M j, Y H:i', strtotime($v['created_at'])) ?></td>
            <td>
              <div class="flex gap-2" style="align-items:center">
                <a href="<?= BASE_URL ?>/watch.php?v=<?= $v['id'] ?>" target="_blank" class="btn btn-sm btn-outline" title="View Video" style="padding: 4px 8px;">View</a>
                <a href="<?= BASE_URL ?>/creator/edit.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-outline" title="Edit Video" style="padding: 4px 8px;">Edit</a>
                <form method="POST" action="?<?= e(http_build_query($_GET)) ?>" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this video?')">
                  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                  <input type="hidden" name="video_id" value="<?= $v['id'] ?>">
                  <button name="action" value="delete" class="btn btn-sm btn-outline" style="color:var(--red); border-color:rgba(239, 68, 68, 0.4); padding: 4px 8px;" title="Delete Video">Delete</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if(!$videos): ?>
          <tr><td colspan="10" style="text-align:center;padding:40px;color:var(--text2)">No videos yet. <a href="<?= BASE_URL ?>/creator/upload.php" style="color:var(--accent)">Upload your first video</a></td></tr>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
