<?php
// Admin — Enhanced User Management
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$status_labels = [
    'active'    => 'Approved',
    'pending'   => 'Pending',
    'rejected'  => 'Rejected',
    'suspended' => 'Disabled'
];
$status_badges = [
    'active'    => 'green',
    'pending'   => 'yellow',
    'rejected'  => 'red',
    'suspended' => 'red'
];

// ── POST Actions ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? '')) {
    $uid    = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'suspend')   db_update('users', ['status' => 'suspended'], 'id=?', [$uid]);
    if ($action === 'activate')  db_update('users', ['status' => 'active'],    'id=?', [$uid]);
    if ($action === 'reject')    db_update('users', ['status' => 'rejected'],  'id=?', [$uid]);
    if ($action === 'approve_creator') {
        db_update('users', ['status' => 'active', 'role' => 'creator'], 'id=?', [$uid]);
        $pu = db_fetch("SELECT username FROM users WHERE id=?", [$uid]);
        ensure_user_channel($uid, $pu['username'] ?? null);
    }
    if ($action === 'make_viewer') {
        db_update('users', ['role' => 'viewer'], 'id=?', [$uid]);
    }
    if ($action === 'make_creator') {
        db_update('users', ['role' => 'creator', 'status' => 'active'], 'id=?', [$uid]);
        $pu = db_fetch("SELECT username FROM users WHERE id=?", [$uid]);
        ensure_user_channel($uid, $pu['username'] ?? null);
    }
    if ($action === 'change_password') {
        $newPass = trim($_POST['new_password'] ?? '');
        if (strlen($newPass) >= 8) {
            db_update('users', ['password' => hash_password($newPass)], 'id=?', [$uid]);
            flash('success', 'Password changed successfully.');
        } else {
            flash('error', 'Password must be at least 8 characters.');
        }
        redirect(BASE_URL . '/admin/users.php?view=' . $uid);
    }
    if ($action === 'delete_user') {
        // Prevent deleting own account
        if ($uid !== (int)auth_user()['id']) {
            db_query("DELETE FROM users WHERE id=?", [$uid]);
            flash('success', 'User deleted.');
            redirect(BASE_URL . '/admin/users.php');
        } else {
            flash('error', 'Cannot delete your own admin account.');
            redirect(BASE_URL . '/admin/users.php?view=' . $uid);
        }
    }
    flash('success', 'User updated.');
    redirect(BASE_URL . '/admin/users.php' . ($uid ? '?view=' . $uid : ''));
}

// ── Single user detail view ────────────────────────────────────
$view_uid = (int)($_GET['view'] ?? 0);
if ($view_uid) {
    $view_user = db_fetch("SELECT * FROM users WHERE id=?", [$view_uid]);
    if (!$view_user) {
        flash('error', 'User not found.');
        redirect(BASE_URL . '/admin/users.php');
    }
    
    $user_earnings = [];
    $user_withdrawals = [];
    $user_videos = db_fetchAll(
        "SELECT id, title, status, views, created_at FROM videos WHERE user_id=? ORDER BY created_at DESC LIMIT 20",
        [$view_uid]
    );
    $user_referrals = fh_table_exists('referral_conversions')
        ? db_fetchAll(
            "SELECT u.username, u.role, rc.created_at FROM referral_conversions rc
             JOIN users u ON u.id = rc.referred_user_id
             WHERE rc.referrer_id=? ORDER BY rc.created_at DESC LIMIT 20",
            [$view_uid]
          )
        : [];
    $total_earnings = 0.0;
    $total_withdrawn = 0.0;

    $meta_title = 'User: ' . $view_user['username'];
    require_once __DIR__ . '/partials/admin_head.php';
    ?>
    <div class="admin-content">
      <div class="admin-page-header">
        <div>
          <a href="<?= BASE_URL ?>/admin/users.php" style="color:var(--text2);font-size:.8rem;text-decoration:none">&larr; All Users</a>
        </div>
      </div>

      <?php foreach (get_flash() as $f): ?><div class="alert alert-<?= $f['type'] ?>"><?= e($f['msg']) ?></div><?php endforeach; ?>

      <div class="user-details-grid">
        <!-- Left: Details -->
        <div>
          <!-- Profile Card -->
          <div class="card" style="margin-bottom:20px">
            <div class="flex gap-4" style="align-items:center;margin-bottom:16px">
              <img src="<?= avatar_url($view_user['avatar']) ?>" class="avatar" width="60" height="60" style="border-radius:50%;border:2px solid var(--accent)">
              <div>
                <div style="font-size:1.1rem;font-weight:800"><?= e($view_user['username']) ?></div>
                <div class="text-muted text-sm"><?= e($view_user['email']) ?></div>
                <div style="margin-top:4px;display:flex;gap:6px;flex-wrap:wrap">
                  <span class="badge badge-<?= ['admin'=>'blue','creator'=>'green','viewer'=>'gray','affiliate'=>'yellow'][$view_user['role']]??'gray' ?>"><?= e($view_user['role'] === 'creator' ? 'Creator' : ($view_user['role'] === 'viewer' ? 'Watch & Earn' : ucfirst($view_user['role']))) ?></span>
                  <span class="badge badge-<?= $status_badges[$view_user['status']] ?? 'gray' ?>"><?= e($status_labels[$view_user['status']] ?? $view_user['status']) ?></span>
                </div>
              </div>
            </div>
            <div class="stat-grid-2" style="font-size:.85rem">
              <div><span class="text-muted">Phone:</span> <?= e($view_user['phone'] ?? '—') ?></div>
              <div><span class="text-muted">Channel:</span> <?= e($view_user['channel_name'] ?? '—') ?></div>
              <div><span class="text-muted">Ref Code:</span> <code style="color:var(--accent)"><?= e($view_user['ref_code'] ?? '—') ?></code></div>
              <div><span class="text-muted">Joined:</span> <?= date('M j, Y', strtotime($view_user['created_at'])) ?></div>
              <div><span class="text-muted">Last Login:</span> <?= $view_user['last_login'] ? date('M j, Y H:i', strtotime($view_user['last_login'])) : 'Never' ?></div>
              <div><span class="text-muted">Watch Time:</span> <?= round((int)($view_user['total_watch_seconds']??0)/3600, 1) ?>h</div>
            </div>
          </div>





          <!-- Videos (Creators only) -->
          <?php if ($view_user['role'] === 'creator' && $user_videos): ?>
          <div class="card" style="margin-bottom:20px">
            <h3 style="font-weight:700;margin-bottom:12px;font-size:.95rem">Videos (<?= count($user_videos) ?>)</h3>
            <div class="table-wrap">
              <table>
                <thead><tr><th>Title</th><th>Status</th><th>Views</th><th>Date</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($user_videos as $v): ?>
                <tr>
                  <td style="max-width:160px;font-size:.83rem;font-weight:500"><?= e(truncate($v['title'],40)) ?></td>
                  <td><span class="badge badge-<?= $v['status']==='published'?'green':(($v['status']==='pending' || $v['status']==='processing')?'yellow':($v['status']==='rejected'?'red':'gray')) ?>"><?= e($v['status']==='processing'?'Pending':ucfirst($v['status'])) ?></span></td>
                  <td class="text-sm"><?= format_number((int)$v['views']) ?></td>
                  <td class="text-xs text-muted"><?= date('M j, Y', strtotime($v['created_at'])) ?></td>
                  <td>
                    <div class="flex gap-1">
                      <a href="<?= BASE_URL ?>/watch.php?v=<?= $v['id'] ?>" target="_blank" class="btn btn-sm btn-outline">View</a>
                      <a href="<?= BASE_URL ?>/admin/video_edit.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
          <?php endif; ?>

          <!-- Referrals -->
          <?php if ($user_referrals): ?>
          <div class="card" style="margin-bottom:20px">
            <h3 style="font-weight:700;margin-bottom:12px;font-size:.95rem">Referred Users (<?= count($user_referrals) ?>)</h3>
            <div class="table-wrap">
              <table>
                <thead><tr><th>Username</th><th>Role</th><th>Joined</th></tr></thead>
                <tbody>
                <?php foreach ($user_referrals as $ru): ?>
                <tr>
                  <td class="text-sm font-semibold"><?= e($ru['username']) ?></td>
                  <td><span class="badge badge-gray"><?= e($ru['role']) ?></span></td>
                  <td class="text-xs text-muted"><?= date('M j, Y', strtotime($ru['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
          <?php endif; ?>


        </div>

        <!-- Right: Actions -->
        <div>
          <!-- Status Actions -->
          <div class="card" style="margin-bottom:16px">
            <h3 style="font-weight:700;margin-bottom:12px;font-size:.9rem">Account Actions</h3>
            <form method="POST" style="display:flex;flex-direction:column;gap:8px">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="user_id" value="<?= $view_uid ?>">
              <?php if ((int)$view_user['id'] !== (int)auth_user()['id']): ?>
                <?php if ($view_user['status'] === 'pending' && $view_user['role'] !== 'creator'): ?>
                <button name="action" value="activate" class="btn btn-sm" style="background:var(--green);color:#fff">✅ Approve Application</button>
                <?php endif; ?>
                <?php if ($view_user['status'] === 'active' || $view_user['status'] === 'pending'): ?>
                <button name="action" value="suspend" class="btn btn-outline btn-sm" style="color:var(--red)" onclick="return confirm('Suspend this user?')">🚫 Suspend Account</button>
                <?php endif; ?>
                <?php if ($view_user['status'] === 'pending'): ?>
                <button name="action" value="reject" class="btn btn-outline btn-sm" style="color:var(--red)" onclick="return confirm('Reject this application?')">❌ Reject Application</button>
                <?php endif; ?>
                <?php if ($view_user['status'] === 'suspended' || $view_user['status'] === 'rejected'): ?>
                <button name="action" value="activate" class="btn btn-sm" style="background:var(--green);color:#fff">✅ Activate Account</button>
                <?php endif; ?>
                <?php if ($view_user['status'] === 'pending' && $view_user['role'] === 'creator'): ?>
                <button name="action" value="approve_creator" class="btn btn-sm" style="background:var(--green);color:#fff">✅ Approve as Creator</button>
                <?php endif; ?>
                <?php if ($view_user['role'] === 'viewer' || $view_user['role'] === 'affiliate'): ?>
                <button name="action" value="make_creator" class="btn btn-outline btn-sm" style="color:var(--accent)">🎬 Promote to Creator</button>
                <?php endif; ?>
                <?php if ($view_user['role'] === 'creator'): ?>
                <button name="action" value="make_viewer" class="btn btn-outline btn-sm">👁️ Demote to Viewer</button>
                <?php endif; ?>
                <button name="action" value="delete_user" class="btn btn-outline btn-sm" style="color:var(--red);margin-top:8px;border-color:rgba(239,68,68,.3)" onclick="return confirm('PERMANENTLY DELETE this user and all their data? This cannot be undone!')">🗑️ Delete User</button>
              <?php else: ?>
                <p class="text-muted text-sm">Your own admin account — cannot be modified here.</p>
              <?php endif; ?>
            </form>
          </div>

          <!-- Change Password -->
          <?php if ((int)$view_user['id'] !== (int)auth_user()['id']): ?>
          <div class="card" style="margin-bottom:16px">
            <h3 style="font-weight:700;margin-bottom:12px;font-size:.9rem">Change Password</h3>
            <form method="POST">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="user_id" value="<?= $view_uid ?>">
              <input type="hidden" name="action" value="change_password">
              <div class="form-group" style="margin-bottom:10px">
                <input class="form-input" type="text" name="new_password" placeholder="New password (min. 8 chars)" required minlength="8" style="font-size:.85rem">
              </div>
              <button type="submit" class="btn btn-outline btn-sm w-full">Set New Password</button>
            </form>
          </div>
          <?php endif; ?>

          <!-- Quick Info -->
          <div class="card">
            <h3 style="font-weight:700;margin-bottom:12px;font-size:.9rem">Quick Info</h3>
            <div style="font-size:.82rem;display:flex;flex-direction:column;gap:8px">
              <div class="flex" style="justify-content:space-between"><span class="text-muted">ID</span><span>#{$view_uid}</span></div>
              <div class="flex" style="justify-content:space-between"><span class="text-muted">Role</span><span><?= e($view_user['role']) ?></span></div>
              <div class="flex" style="justify-content:space-between"><span class="text-muted">Watch Time</span><span><?= round((int)($view_user['total_watch_seconds']??0)/3600, 2) ?>h</span></div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php require_once __DIR__ . '/partials/admin_foot.php';
    exit;
}

// ── Users List ─────────────────────────────────────────────────
$role   = $_GET['role']   ?? 'all';
$status = $_GET['status'] ?? 'all';
$search = trim($_GET['s'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));

$where  = '1';
$params = [];
if ($role   !== 'all') {
    if ($role === 'viewer') {
        $where .= " AND role IN ('viewer','affiliate')";
    } else {
        $where .= " AND role=?"; $params[] = $role;
    }
}
if ($status !== 'all') { $where .= " AND status=?"; $params[] = $status; }
if ($search) {
    $where .= " AND (username LIKE ? OR email LIKE ? OR channel_name LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}

$total = db_count('users', $where, $params);
$pg    = paginate($total, 25, $page);
$users = db_fetchAll("SELECT * FROM users WHERE $where ORDER BY created_at DESC LIMIT 25 OFFSET {$pg['offset']}", $params);

$meta_title = 'User Management';
require_once __DIR__ . '/partials/admin_head.php';
?>
<div class="admin-content">
  <div class="admin-page-header">
    <div class="text-sm text-muted"><?= number_format($total) ?> total users</div>
  </div>

  <?php foreach (get_flash() as $f): ?><div class="alert alert-<?= $f['type'] ?>"><?= e($f['msg']) ?></div><?php endforeach; ?>

  <!-- Filters -->
  <form method="GET" class="flex gap-2" style="flex-wrap:wrap;margin-bottom:16px">
    <input type="text" name="s" value="<?= e($search) ?>" placeholder="Search by name, email, channel…" class="form-input" style="width:240px">
    <select name="role" class="form-input form-select" style="width:auto" onchange="this.form.submit()">
      <option value="all">All Roles</option>
      <option value="viewer" <?= $role==='viewer'?'selected':'' ?>>Watch & Earn (Viewers)</option>
      <option value="creator" <?= $role==='creator'?'selected':'' ?>>Creators</option>
      <option value="admin" <?= $role==='admin'?'selected':'' ?>>Admin</option>
    </select>
    <select name="status" class="form-input form-select" style="width:auto" onchange="this.form.submit()">
      <option value="all">All Status</option>
      <?php foreach (['active','suspended','pending','rejected'] as $s): ?>
      <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= e($status_labels[$s] ?? ucfirst($s)) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <a href="?" class="btn btn-outline btn-sm">Reset</a>
  </form>

  <div class="table-wrap">
    <table>
      <thead><tr>
        <th>User</th><th>Email</th><th>Role</th><th>Status</th>
        <th>Watch Time</th><th>Joined</th><th>Actions</th>
      </tr></thead>
      <tbody>
      <?php foreach ($users as $u): ?>
      <tr>
        <td>
          <div class="flex gap-2">
            <img src="<?= avatar_url($u['avatar']) ?>" class="avatar avatar-sm" width="32" height="32">
            <div>
              <div style="font-weight:600;font-size:.85rem"><?= e($u['username']) ?></div>
              <div class="text-xs text-muted"><?= e($u['channel_name'] ?? '') ?></div>
            </div>
          </div>
        </td>
        <td class="text-sm text-muted"><?= e($u['email']) ?></td>
        <td>
          <span class="badge badge-<?= ['admin'=>'blue','creator'=>'green','viewer'=>'gray','affiliate'=>'yellow'][$u['role']]??'gray' ?>">
            <?= $u['role'] === 'creator' ? 'Creator' : ($u['role'] === 'viewer' ? 'Viewer' : ucfirst($u['role'])) ?>
          </span>
        </td>
        <td>
          <span class="badge badge-<?= $status_badges[$u['status']] ?? 'gray' ?>">
            <?= e($status_labels[$u['status']] ?? $u['status']) ?>
          </span>
        </td>
        <td class="text-sm text-muted"><?= round((int)($u['total_watch_seconds']??0)/3600, 1) ?>h</td>
        <td class="text-xs text-muted"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
        <td>
          <div class="flex gap-1" style="flex-wrap:wrap">
            <a href="?view=<?= $u['id'] ?>" class="btn btn-sm btn-outline" title="View full details">Details</a>
            <?php if ($u['status'] === 'pending' && $u['role'] === 'creator'): ?>
            <form method="POST" style="display:inline">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <button name="action" value="approve_creator" class="btn btn-sm" style="background:var(--green);color:#fff" title="Approve Creator">✅</button>
            </form>
            <?php elseif ($u['status'] === 'pending' && $u['role'] !== 'creator'): ?>
            <form method="POST" style="display:inline">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <button name="action" value="activate" class="btn btn-sm" style="background:var(--green);color:#fff" title="Approve Viewer">✅</button>
            </form>
            <?php endif; ?>
            <form method="POST" style="display:inline">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <?php if ($u['status'] === 'active' && $u['role'] !== 'admin'): ?>
              <button name="action" value="suspend" class="btn btn-sm btn-outline" style="color:var(--red)" onclick="return confirm('Suspend?')" title="Suspend">🚫</button>
              <?php elseif ($u['status'] === 'suspended' || $u['status'] === 'rejected'): ?>
              <button name="action" value="activate" class="btn btn-sm" style="background:var(--green);color:#fff" title="Activate/Unblock">✅</button>
              <?php endif; ?>
            </form>
            <?php if ($u['status'] === 'pending'): ?>
            <form method="POST" style="display:inline">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <button name="action" value="reject" class="btn btn-sm btn-outline" style="color:var(--red)" onclick="return confirm('Reject application?')" title="Reject">❌</button>
            </form>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pg['pages'] > 1): ?>
  <div class="flex gap-2" style="margin-top:16px;justify-content:center">
    <?php if($pg['has_prev']): ?><a href="?page=<?= $page-1 ?>&role=<?= $role ?>&status=<?= $status ?>&s=<?= urlencode($search) ?>" class="btn btn-outline btn-sm">&laquo;</a><?php endif; ?>
    <span class="text-muted text-sm" style="align-self:center">Page <?= $page ?>/<?= $pg['pages'] ?></span>
    <?php if($pg['has_next']): ?><a href="?page=<?= $page+1 ?>&role=<?= $role ?>&status=<?= $status ?>&s=<?= urlencode($search) ?>" class="btn btn-outline btn-sm">&raquo;</a><?php endif; ?>
  </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/partials/admin_foot.php'; ?>
