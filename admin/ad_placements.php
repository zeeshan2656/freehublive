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
        $ad_id = $_POST['ad_id'] === '' ? null : (int)$_POST['ad_id'];
        
        if ($id && $name) {
            db_update('ad_placements', [
                'name' => $name,
                'device_target' => $device_target,
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
            <th style="padding: 12px; font-weight: 700; font-size: 0.85rem; color: var(--text2);">ID</th>
            <th style="padding: 12px; font-weight: 700; font-size: 0.85rem; color: var(--text2);">Name</th>
            <th style="padding: 12px; font-weight: 700; font-size: 0.85rem; color: var(--text2);">Key Name</th>
            <th style="padding: 12px; font-weight: 700; font-size: 0.85rem; color: var(--text2);">Device Target</th>
            <th style="padding: 12px; font-weight: 700; font-size: 0.85rem; color: var(--text2);">Assigned Ad</th>
            <th style="padding: 12px; font-weight: 700; font-size: 0.85rem; color: var(--text2); text-align: right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($placements as $p): ?>
            <tr style="border-bottom: 1px solid var(--border); transition: background-color 0.2s;">
              <td style="padding: 14px 12px; font-size: 0.85rem; font-weight: bold; color: var(--text2);"><?= (int)$p['id'] ?></td>
              <td style="padding: 14px 12px;">
                <div style="font-weight: 700; font-size: 0.9rem; color: var(--text);"><?= e($p['name']) ?></div>
              </td>
              <td style="padding: 14px 12px;">
                <code style="background: var(--bg3); padding: 3px 6px; border-radius: 4px; font-family: monospace; color: var(--accent); font-weight: bold; font-size: 0.78rem;"><?= e($p['key_name']) ?></code>
              </td>
              <td style="padding: 14px 12px;">
                <span class="badge badge-<?= $p['device_target'] === 'all' ? 'blue' : ($p['device_target'] === 'desktop' ? 'cyan' : 'purple') ?>" style="font-size: 0.68rem; font-weight: 600; text-transform: uppercase;">
                  <?= $p['device_target'] === 'all' ? 'All Devices' : e($p['device_target']) ?>
                </span>
              </td>
              <td style="padding: 14px 12px;">
                <?php if ($p['assigned_ad_id']): ?>
                  <div class="flex gap-2" style="align-items: center; flex-wrap: wrap;">
                    <span style="font-weight: 700; font-size: 0.88rem; color: var(--text);"><?= e($p['ad_title']) ?></span>
                    <span class="badge badge-blue" style="font-size: 0.65rem; font-weight: 600; text-transform: uppercase;"><?= e($p['ad_type']) ?></span>
                    <span class="badge badge-<?= $p['ad_active'] ? 'green' : 'gray' ?>" style="font-size: 0.65rem; font-weight: 600; text-transform: uppercase;"><?= $p['ad_active'] ? 'Active' : 'Inactive' ?></span>
                  </div>
                <?php else: ?>
                  <span class="text-muted text-xs" style="font-style: italic; color: var(--text3);">Unassigned</span>
                <?php endif; ?>
              </td>
              <td style="padding: 14px 12px; text-align: right;">
                <div style="display: inline-flex; gap: 6px; align-items: center;">
                  <!-- Edit/Assign Button -->
                  <button type="button" class="btn btn-sm btn-outline" onclick="openPlacementModal(<?= (int)$p['id'] ?>, '<?= e(addslashes($p['name'])) ?>', '<?= e(addslashes($p['key_name'])) ?>', '<?= e($p['device_target']) ?>', '<?= $p['assigned_ad_id'] ?: '' ?>')" style="font-weight: 600;">
                    ✏️ Edit
                  </button>

                  <!-- Duplicate Action Form -->
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="placement_id" value="<?= $p['id'] ?>">
                    <button name="action" value="duplicate" class="btn btn-sm btn-outline" style="border-color: var(--blue); color: var(--blue);" title="Duplicate this placement">
                      👯 Duplicate
                    </button>
                  </form>

                  <!-- Delete Action Form (only for duplicated items) -->
                  <?php if ($p['id'] > 4): ?>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this duplicated placement?');">
                      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                      <input type="hidden" name="placement_id" value="<?= $p['id'] ?>">
                      <button name="action" value="delete" class="btn btn-sm btn-outline" style="border-color: var(--red); color: var(--red);" title="Delete duplicated placement">
                        🗑️ Delete
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
function openPlacementModal(id, name, key, deviceTarget, assignedAdId) {
  document.getElementById('modal_placement_id').value = id;
  document.getElementById('modal_placement_name').value = name;
  document.getElementById('modal_placement_key').value = key;
  document.getElementById('modal_device_target').value = deviceTarget;
  document.getElementById('modal_ad_id').value = assignedAdId || '';
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
