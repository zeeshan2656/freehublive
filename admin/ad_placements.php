<?php
// Admin — Ad Placements Management
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? '')) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update') {
        $id = (int)($_POST['placement_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $device_target = $_POST['device_target'] ?? 'all';
        $ad_width = $_POST['ad_width'] === '' ? null : (int)$_POST['ad_width'];
        $ad_height = $_POST['ad_height'] === '' ? null : (int)$_POST['ad_height'];
        $reload_interval = $_POST['reload_interval'] === '' ? null : (int)$_POST['reload_interval'];
        $ad_id = $_POST['ad_id'] === '' ? null : (int)$_POST['ad_id'];
        
        if ($id && $name) {
            db_update('ad_placements', [
                'name' => $name,
                'device_target' => $device_target,
                'ad_width' => $ad_width,
                'ad_height' => $ad_height,
                'reload_interval' => $reload_interval,
                'assigned_ad_id' => $ad_id
            ], 'id=?', [$id]);
            flash('success', 'Ad placement updated successfully.');
        }
    }
    
    if ($action === 'duplicate') {
        $id = (int)($_POST['placement_id'] ?? 0);
        $orig = db_fetch("SELECT * FROM ad_placements WHERE id = ?", [$id]);
        if ($orig) {
            db_insert('ad_placements', [
                'key_name'       => $orig['key_name'],
                'device_target'  => $orig['device_target'],
                'ad_width'       => $orig['ad_width'] ?: null,
                'ad_height'      => $orig['ad_height'] ?: null,
                'reload_interval'=> $orig['reload_interval'] ?: null,
                'name'           => 'Copy of ' . $orig['name'],
                'assigned_ad_id' => $orig['assigned_ad_id'] ?: null
            ]);
            flash('success', 'Placement duplicated successfully.');
        }
    }
    
    if ($action === 'delete') {
        $id = (int)($_POST['placement_id'] ?? 0);
        $protected_ids = [1, 2, 3, 4, 6, 7, 8, 9];
        if (!in_array($id, $protected_ids)) {
            db_query("DELETE FROM ad_placements WHERE id = ?", [$id]);
            flash('success', 'Placement deleted.');
        } else {
            flash('error', 'Cannot delete system default placements.');
        }
    }
    
    redirect(BASE_URL . '/admin/ad_placements.php?' . http_build_query($_GET));
}

// ── Search & Filter Logic ──
$search = trim($_GET['search'] ?? '');
$device_filter = $_GET['device'] ?? 'any';
$status_filter = $_GET['status'] ?? 'any';

$where = "1";
$params = [];

if ($search !== '') {
    $where .= " AND (ap.name LIKE ? OR ap.key_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($device_filter !== 'any') {
    $where .= " AND ap.device_target = ?";
    $params[] = $device_filter;
}
if ($status_filter === 'assigned') {
    $where .= " AND ap.assigned_ad_id IS NOT NULL";
} elseif ($status_filter === 'unassigned') {
    $where .= " AND ap.assigned_ad_id IS NULL";
}

$placements = db_fetchAll("
    SELECT ap.*, a.title AS ad_title, a.content_type AS ad_type, a.is_active AS ad_active
    FROM ad_placements ap
    LEFT JOIN ads a ON ap.assigned_ad_id = a.id
    WHERE $where
    ORDER BY ap.id ASC
", $params);

// Fetch active ads for assignment dropdown
$active_ads = db_fetchAll("SELECT id, title, content_type FROM ads WHERE is_active = 1 ORDER BY title ASC");

$meta_title = 'Ad Placements';
require_once __DIR__ . '/partials/admin_head.php';
?>
<style>
/* Table Row Height & Vertical Centering */
.admin-categories-table th,
.admin-categories-table td {
  vertical-align: middle !important;
  padding: 6px 12px !important; /* Extremely neat and compact padding */
}

/* Row hover highlight */
.admin-categories-table tbody tr {
  transition: background-color 0.2s ease;
}
.admin-categories-table tbody tr:hover {
  background-color: rgba(255, 255, 255, 0.015) !important;
}

/* Premium Action Buttons */
.btn-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  height: 28px;
  padding: 0 10px;
  font-size: 0.75rem;
  font-weight: 600;
  border-radius: 6px;
  background: var(--bg2);
  border: 1px solid var(--border);
  color: var(--text2);
  transition: all 0.15s ease-in-out;
  cursor: pointer;
  line-height: 1;
}

.btn-action svg {
  width: 12px;
  height: 12px;
  stroke: currentColor;
  stroke-width: 2.5;
  fill: none;
  flex-shrink: 0;
}

/* Edit button styles */
.btn-edit {
  border-color: rgba(99, 102, 241, 0.25);
  color: var(--accent);
}
.btn-edit:hover {
  background: rgba(99, 102, 241, 0.1);
  border-color: var(--accent);
  color: var(--accent);
  transform: translateY(-1px);
}

/* Duplicate button styles */
.btn-duplicate {
  border-color: rgba(59, 130, 246, 0.25);
  color: #3b82f6; /* Modern Blue */
}
.btn-duplicate:hover {
  background: rgba(59, 130, 246, 0.1);
  border-color: #3b82f6;
  color: #3b82f6;
  transform: translateY(-1px);
}

/* Delete button styles */
.btn-delete {
  border-color: rgba(239, 68, 68, 0.25);
  color: var(--red);
}
.btn-delete:hover {
  background: rgba(239, 68, 68, 0.1);
  border-color: var(--red);
  color: var(--red);
  transform: translateY(-1px);
}

/* Badge Tweaks for Premium Alignment */
.admin-categories-table .badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 3px 8px !important;
  font-size: 0.65rem !important;
  font-weight: 700 !important;
  letter-spacing: 0.03em;
  text-transform: uppercase;
  border-radius: 4px !important;
  line-height: 1;
  vertical-align: middle;
}

.badge-cyan {
  background: rgba(6, 182, 212, 0.12) !important;
  color: #06b6d4 !important;
}
.badge-purple {
  background: rgba(168, 85, 247, 0.12) !important;
  color: #a855f7 !important;
}

/* Code Tag styling for key_name */
.admin-categories-table code {
  background: rgba(99, 102, 241, 0.06) !important;
  border: 1px solid rgba(99, 102, 241, 0.15) !important;
  color: var(--accent) !important;
  padding: 3px 6px !important;
  border-radius: 4px;
  font-family: monospace;
  font-weight: 700;
  font-size: 0.75rem !important;
  vertical-align: middle;
  display: inline-block;
  line-height: 1;
}
</style>
<div class="admin-content">
  <div class="admin-page-header">
    <h1 style="display: flex; align-items: center; gap: 8px;">📺 Ad Placement Areas</h1>
  </div>
  
  <?php foreach(get_flash() as $f): ?>
    <div class="alert alert-<?= $f['type'] === 'error' ? 'danger' : $f['type'] ?>"><?= e($f['msg']) ?></div>
  <?php endforeach; ?>

  <!-- Advanced Filters Form -->
  <form method="GET" class="card" style="margin-bottom:24px; padding:18px; background:var(--bg2); border:1px solid var(--border)">
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px; align-items:flex-end;">
      
      <!-- Search Input -->
      <div class="form-group" style="margin-bottom:0">
        <label class="form-label" style="font-size:0.75rem; font-weight:700;">Search Placements</label>
        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Name or key..." class="form-input" style="height:38px; border-radius:6px;">
      </div>

      <!-- Device Targeting Filter -->
      <div class="form-group" style="margin-bottom:0">
        <label class="form-label" style="font-size:0.75rem; font-weight:700;">Device Target</label>
        <select class="form-input form-select" name="device" style="height:38px; border-radius:6px; background:var(--bg); color:var(--text); border:1px solid var(--border);">
          <option value="any" <?= $device_filter === 'any' ? 'selected' : '' ?>>Any Device</option>
          <option value="all" <?= $device_filter === 'all' ? 'selected' : '' ?>>All Devices</option>
          <option value="desktop" <?= $device_filter === 'desktop' ? 'selected' : '' ?>>Desktop Only</option>
          <option value="mobile" <?= $device_filter === 'mobile' ? 'selected' : '' ?>>Mobile Only</option>
        </select>
      </div>

      <!-- Assigned Ad Filter -->
      <div class="form-group" style="margin-bottom:0">
        <label class="form-label" style="font-size:0.75rem; font-weight:700;">Assignment Status</label>
        <select class="form-input form-select" name="status" style="height:38px; border-radius:6px; background:var(--bg); color:var(--text); border:1px solid var(--border);">
          <option value="any" <?= $status_filter === 'any' ? 'selected' : '' ?>>Any Status</option>
          <option value="assigned" <?= $status_filter === 'assigned' ? 'selected' : '' ?>>Assigned Only</option>
          <option value="unassigned" <?= $status_filter === 'unassigned' ? 'selected' : '' ?>>Unassigned Only</option>
        </select>
      </div>

      <!-- Action Buttons -->
      <div class="flex gap-2" style="margin-bottom:0">
        <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center; height:38px; border-radius:6px;">Filter</button>
        <a href="?" class="btn btn-outline" style="flex:1; justify-content:center; height:38px; border-radius:6px; display:inline-flex; align-items:center;">Reset</a>
      </div>

    </div>
  </form>

  <!-- Placements List Form (Table) -->
  <div class="card card-sm">
    <div class="table-wrap admin-table-scroll">
      <table class="admin-categories-table" style="width: 100%; border-collapse: collapse;">
        <thead>
          <tr style="border-bottom: 1px solid var(--border); text-align: left;">
            <th>ID</th>
            <th>Name</th>
            <th>Key Name</th>
            <th>Device Target</th>
            <th>Placement Size</th>
            <th>Assigned Ad</th>
            <th style="text-align: right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($placements as $p): ?>
            <tr style="border-bottom: 1px solid var(--border);">
              <td style="font-size: 0.85rem; font-weight: bold; color: var(--text2);"><?= (int)$p['id'] ?></td>
              <td>
                <div style="font-weight: 700; font-size: 0.9rem; color: var(--text);"><?= e($p['name']) ?></div>
              </td>
              <td>
                <code><?= e($p['key_name']) ?></code>
              </td>
              <td>
                <span class="badge badge-<?= $p['device_target'] === 'all' ? 'blue' : ($p['device_target'] === 'desktop' ? 'cyan' : 'purple') ?>">
                   <?= $p['device_target'] === 'all' ? 'All Devices' : e($p['device_target']) ?>
                 </span>
              </td>
              <td>
                <div style="display: flex; flex-direction: column; gap: 4px;">
                  <?php if ($p['ad_width'] || $p['ad_height']): ?>
                    <code style="background: rgba(255,255,255,0.03); border: 1px solid var(--border); padding: 2px 6px; border-radius: 4px; font-weight: bold; color: var(--text2); display: inline-block;">
                      <?= $p['ad_width'] ? (int)$p['ad_width'] . 'px' : 'Auto' ?> × <?= $p['ad_height'] ? (int)$p['ad_height'] . 'px' : 'Auto' ?>
                    </code>
                  <?php else: ?>
                    <span class="text-muted text-xs" style="font-style: italic; color: var(--text3);">Auto / Responsive</span>
                  <?php endif; ?>
                  
                  <?php if ($p['reload_interval']): ?>
                    <div style="font-size: 0.72rem; color: var(--accent); font-weight: bold; display: flex; align-items: center; gap: 3px;">
                      <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="stroke: var(--accent);"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l.73-.73"/></svg>
                      <?= (int)$p['reload_interval'] ?>s reload
                    </div>
                  <?php else: ?>
                    <div style="font-size: 0.72rem; color: var(--text3); font-style: italic;">No reload</div>
                  <?php endif; ?>
                </div>
              </td>
              <td>
                <?php if ($p['assigned_ad_id']): ?>
                  <div class="flex gap-2" style="align-items: center; flex-wrap: wrap;">
                    <span style="font-weight: 700; font-size: 0.88rem; color: var(--text);"><?= e($p['ad_title']) ?></span>
                    <span class="badge badge-blue"><?= e($p['ad_type']) ?></span>
                    <span class="badge badge-<?= $p['ad_active'] ? 'green' : 'gray' ?>"><?= $p['ad_active'] ? 'Active' : 'Inactive' ?></span>
                  </div>
                <?php else: ?>
                  <span class="text-muted text-xs" style="font-style: italic; color: var(--text3);">Unassigned</span>
                <?php endif; ?>
              </td>
              <td style="text-align: right;">
                <div style="display: inline-flex; gap: 6px; align-items: center;">
                  <!-- Edit/Assign Button -->
                  <button type="button" class="btn-action btn-edit" onclick="openPlacementModal(<?= (int)$p['id'] ?>, '<?= e(addslashes($p['name'])) ?>', '<?= e(addslashes($p['key_name'])) ?>', '<?= e($p['device_target']) ?>', '<?= $p['assigned_ad_id'] ?: '' ?>', '<?= $p['ad_width'] ?: '' ?>', '<?= $p['ad_height'] ?: '' ?>', '<?= $p['reload_interval'] ?: '' ?>')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    Edit
                  </button>

                  <!-- Duplicate Action Form -->
                  <form method="POST" style="display:inline-block; margin: 0;">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="placement_id" value="<?= $p['id'] ?>">
                    <button name="action" value="duplicate" class="btn-action btn-duplicate" title="Duplicate this placement">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                      Duplicate
                    </button>
                  </form>

                  <!-- Delete Action Form (only for duplicated items) -->
                  <?php if ($p['id'] > 4): ?>
                    <form method="POST" style="display:inline-block; margin: 0;" onsubmit="return confirm('Are you sure you want to delete this duplicated placement?');">
                      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                      <input type="hidden" name="placement_id" value="<?= $p['id'] ?>">
                      <button name="action" value="delete" class="btn-action btn-delete" title="Delete duplicated placement">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                        Delete
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$placements): ?>
            <tr>
              <td colspan="6" style="text-align: center; padding: 24px; color: var(--text3); font-style: italic;">
                No placement areas found matching the filter criteria.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Popup Assignment Modal Form -->
  <div class="modal-backdrop" id="placement-modal">
    <div class="modal" style="max-width: 500px; width: 95%;">
      <div class="modal-header" style="padding: 16px 20px; border-bottom: 1px solid var(--border);">
        <span class="modal-title" style="font-weight:700; font-size:1.05rem;">Edit Ad Placement</span>
        <button type="button" class="btn-icon" onclick="closePlacementModal()" style="background:none; border:none; color:var(--text2); cursor:pointer; font-size:1.25rem;">&times;</button>
      </div>
      <div class="modal-body" style="padding: 20px;">
        <form method="POST">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="placement_id" id="modal_placement_id">
          
          <div class="form-group" style="margin-bottom: 14px;">
            <label class="form-label" style="font-size: 0.8rem; font-weight: bold; color: var(--text2);">Placement Name *</label>
            <input class="form-input" type="text" name="name" id="modal_placement_name" required style="font-size: 0.9rem;">
          </div>
          
          <div class="form-group" style="margin-bottom: 14px;">
            <label class="form-label" style="font-size: 0.8rem; font-weight: bold; color: var(--text2);">Placement Key Name (Read-Only)</label>
            <input class="form-input" type="text" id="modal_placement_key" readonly style="background: var(--bg3); opacity: 0.8; font-family: monospace; font-size: 0.85rem; color: var(--accent);">
          </div>

          <div class="form-group" style="margin-bottom: 14px;">
            <label class="form-label" style="font-size: 0.8rem; font-weight: bold; color: var(--text2);">Target Device</label>
            <select class="form-input form-select" name="device_target" id="modal_device_target" style="width: 100%; height: 38px; border-radius: 6px; padding: 0 10px; background: var(--bg); color: var(--text); border: 1px solid var(--border);">
              <option value="all">All Devices</option>
              <option value="desktop">Desktop Only</option>
              <option value="mobile">Mobile Only</option>
            </select>
          </div>

          <div class="form-row-grid" style="margin-bottom: 14px;">
            <div class="form-group" style="margin-bottom: 0;">
              <label class="form-label" style="font-size: 0.8rem; font-weight: bold; color: var(--text2);">Max Width (px)</label>
              <input class="form-input" type="number" name="ad_width" id="modal_ad_width" placeholder="e.g. 728 (Optional)" min="1" style="font-size: 0.9rem;">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
              <label class="form-label" style="font-size: 0.8rem; font-weight: bold; color: var(--text2);">Max Height (px)</label>
              <input class="form-input" type="number" name="ad_height" id="modal_ad_height" placeholder="e.g. 90 (Optional)" min="1" style="font-size: 0.9rem;">
            </div>
          </div>
          
          <div class="form-group" style="margin-bottom: 14px;">
            <label class="form-label" style="font-size: 0.8rem; font-weight: bold; color: var(--text2);">Reload Ad (seconds)</label>
            <input class="form-input" type="number" name="reload_interval" id="modal_reload_interval" placeholder="e.g. 30 (Optional)" min="5" style="font-size: 0.9rem;">
            <div style="font-size: 0.72rem; color: var(--text3); margin-top: 4px; line-height: 1.3;">
              Auto-reloads the ad placement without refreshing the page. Enter seconds (e.g. 30, 45, 60). Leave empty to disable.
            </div>
          </div>
          
          <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" style="font-size: 0.82rem; font-weight: 700; color: var(--text2); margin-bottom: 6px;">Select Ad to Assign</label>
            <select class="form-input form-select" name="ad_id" id="modal_ad_id" style="width: 100%; height: 38px; border-radius: 6px; padding: 0 10px; background: var(--bg); color: var(--text); border: 1px solid var(--border);">
              <option value="">-- None / Unassigned --</option>
              <?php foreach ($active_ads as $ad): ?>
                <option value="<?= $ad['id'] ?>">
                  <?= e($ad['title']) ?> (<?= e($ad['content_type']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="flex gap-2" style="justify-content: flex-end; border-top: 1px solid var(--border); padding-top: 15px; margin-top: 10px;">
            <button type="button" class="btn btn-outline" onclick="closePlacementModal()">Cancel</button>
            <button type="submit" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 4px;">
              💾 Save Placement
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function openPlacementModal(id, name, key, deviceTarget, assignedAdId, adWidth, adHeight, reloadInterval) {
  document.getElementById('modal_placement_id').value = id;
  document.getElementById('modal_placement_name').value = name;
  document.getElementById('modal_placement_key').value = key;
  document.getElementById('modal_device_target').value = deviceTarget;
  document.getElementById('modal_ad_id').value = assignedAdId || '';
  document.getElementById('modal_ad_width').value = adWidth || '';
  document.getElementById('modal_ad_height').value = adHeight || '';
  document.getElementById('modal_reload_interval').value = reloadInterval || '';
  document.getElementById('placement-modal').classList.add('open');
}

function closePlacementModal() {
  document.getElementById('placement-modal').classList.remove('open');
}

document.getElementById('placement-modal').addEventListener('click', function(e) {
  if (e.target === this) {
    closePlacementModal();
  }
});
</script>
<?php require_once __DIR__ . '/partials/admin_foot.php'; ?>
