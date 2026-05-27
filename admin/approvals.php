<?php
// ============================================================
// FreeHub.Live — Admin Centralized Approvals Panel
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$tab = $_GET['tab'] ?? 'viewers';
if (!in_array($tab, ['viewers', 'creators', 'videos'], true)) {
    $tab = 'viewers';
}

// ── POST Actions ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? '')) {
    $action = $_POST['action'] ?? '';
    
    // User / Creator actions
    if (in_array($action, ['approve_user', 'reject_user', 'suspend_user', 'activate_user'], true)) {
        $uid = (int)($_POST['user_id'] ?? 0);
        $u = db_fetch("SELECT * FROM users WHERE id = ?", [$uid]);
        if ($u && $u['role'] !== 'admin') {
            if ($action === 'approve_user') {
                db_update('users', ['status' => 'active'], 'id = ?', [$uid]);
                ensure_user_channel($uid, $u['username']);
                flash('success', 'Account approved successfully.');
            } elseif ($action === 'reject_user') {
                db_update('users', ['status' => 'rejected'], 'id = ?', [$uid]);
                flash('success', 'Account application rejected.');
            } elseif ($action === 'suspend_user') {
                db_update('users', ['status' => 'suspended'], 'id = ?', [$uid]);
                flash('success', 'Account suspended.');
            } elseif ($action === 'activate_user') {
                db_update('users', ['status' => 'active'], 'id = ?', [$uid]);
                flash('success', 'Account re-enabled.');
            }
        } else {
            flash('error', 'Invalid operation.');
        }
        redirect(BASE_URL . '/admin/approvals.php?tab=' . $tab);
    }
    
    // Video actions
    if (in_array($action, ['approve_video', 'reject_video'], true)) {
        $vid = (int)($_POST['video_id'] ?? 0);
        $note = trim($_POST['approval_note'] ?? '');
        $v = db_fetch("SELECT id FROM videos WHERE id = ?", [$vid]);
        if ($v) {
            if ($action === 'approve_video') {
                db_update('videos', [
                    'status' => 'published',
                    'published_at' => date('Y-m-d H:i:s'),
                    'approval_note' => $note ?: null
                ], 'id = ?', [$vid]);
                flash('success', 'Video approved and published.');
            } elseif ($action === 'reject_video') {
                db_update('videos', [
                    'status' => 'rejected',
                    'approval_note' => $note ?: null
                ], 'id = ?', [$vid]);
                flash('success', 'Video application rejected.');
            }
        } else {
            flash('error', 'Video not found.');
        }
        redirect(BASE_URL . '/admin/approvals.php?tab=' . $tab);
    }
}

// Fetch counts for badges
$count_viewers  = db_count('users', "status = 'pending' AND role != 'creator' AND role != 'admin'");
$count_creators = db_count('users', "status = 'pending' AND role = 'creator'");
$count_videos   = db_count('videos', "status = 'pending'");

// Fetch list items based on active tab
$items = [];
if ($tab === 'viewers') {
    $items = db_fetchAll("SELECT * FROM users WHERE status = 'pending' AND role != 'creator' AND role != 'admin' ORDER BY created_at DESC");
} elseif ($tab === 'creators') {
    $items = db_fetchAll("SELECT * FROM users WHERE status = 'pending' AND role = 'creator' ORDER BY created_at DESC");
} elseif ($tab === 'videos') {
    $items = db_fetchAll("SELECT v.*, u.username, u.channel_name FROM videos v JOIN users u ON u.id = v.user_id WHERE v.status = 'pending' ORDER BY v.created_at DESC");
}

$meta_title = 'Approval Management';
require_once __DIR__ . '/partials/admin_head.php';
?>
<div class="admin-content">
    
    <!-- Tab Navigation -->
    <div style="display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 1px solid var(--border); padding-bottom: 8px; flex-wrap: wrap;">
        <a href="?tab=viewers" class="btn <?= $tab === 'viewers' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 0.88rem; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; height: 38px; border-radius: 19px; text-decoration: none;">
            👁️ Pending Viewers
            <?php if ($count_viewers > 0): ?>
                <span style="background: rgba(255,255,255,0.25); color: #fff; font-size: 0.72rem; font-weight: 700; padding: 2px 7px; border-radius: 10px;"><?= $count_viewers ?></span>
            <?php endif; ?>
        </a>
        <a href="?tab=creators" class="btn <?= $tab === 'creators' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 0.88rem; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; height: 38px; border-radius: 19px; text-decoration: none;">
            🎬 Pending Creators
            <?php if ($count_creators > 0): ?>
                <span style="background: rgba(255,255,255,0.25); color: #fff; font-size: 0.72rem; font-weight: 700; padding: 2px 7px; border-radius: 10px;"><?= $count_creators ?></span>
            <?php endif; ?>
        </a>
        <a href="?tab=videos" class="btn <?= $tab === 'videos' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 0.88rem; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; height: 38px; border-radius: 19px; text-decoration: none;">
            📹 Pending Videos
            <?php if ($count_videos > 0): ?>
                <span style="background: rgba(255,255,255,0.25); color: #fff; font-size: 0.72rem; font-weight: 700; padding: 2px 7px; border-radius: 10px;"><?= $count_videos ?></span>
            <?php endif; ?>
        </a>
    </div>

    <?php foreach (get_flash() as $f): ?>
        <div class="alert alert-<?= $f['type'] ?>"><?= e($f['msg']) ?></div>
    <?php endforeach; ?>

    <div class="table-wrap">
        <table>
            <?php if ($tab === 'viewers' || $tab === 'creators'): ?>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Joined</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="5" style="padding: 30px; text-align: center; color: var(--text2);">No pending <?= $tab ?> in approval queue.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $u): ?>
                            <tr>
                                <td>
                                    <div class="flex gap-2">
                                        <img src="<?= avatar_url($u['avatar']) ?>" class="avatar avatar-sm" width="32" height="32">
                                        <div>
                                            <div style="font-weight: 600; font-size: 0.85rem;"><?= e($u['username']) ?></div>
                                            <div class="text-xs text-muted"><?= e($u['first_name'] . ' ' . $u['last_name']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-muted" style="font-size: 0.85rem;"><?= e($u['email']) ?></td>
                                <td class="text-muted" style="font-size: 0.85rem;"><?= e($u['phone'] ?: '—') ?></td>
                                <td class="text-muted" style="font-size: 0.85rem;"><?= date('M d, Y H:i', strtotime($u['created_at'])) ?></td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 8px;">
                                        <form method="POST" action="" style="margin: 0; display: inline;">
                                            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <button type="submit" name="action" value="approve_user" class="btn btn-xs" style="background: var(--green); color: #fff; border: 1px solid var(--green);">
                                                Approve
                                            </button>
                                        </form>
                                        <form method="POST" action="" style="margin: 0; display: inline;" onsubmit="return confirm('Reject this account registration application?');">
                                            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <button type="submit" name="action" value="reject_user" class="btn btn-xs btn-outline" style="color: var(--red); border-color: rgba(239,68,68,0.4);">
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            <?php elseif ($tab === 'videos'): ?>
                <thead>
                    <tr>
                        <th>Thumbnail</th>
                        <th>Video Title</th>
                        <th>Creator</th>
                        <th>Upload Date</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="5" style="padding: 30px; text-align: center; color: var(--text2);">No pending videos in approval queue.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $v): ?>
                            <tr>
                                <td>
                                    <img src="<?= thumb_url($v['thumbnail']) ?>" style="width: 80px; aspect-ratio: 16/9; object-fit: cover; border-radius: 4px;">
                                </td>
                                <td style="font-weight: 600; font-size: 0.88rem; max-width: 250px;">
                                    <a href="<?= BASE_URL ?>/watch.php?v=<?= $v['id'] ?>" target="_blank" style="color: var(--accent); text-decoration: none;">
                                        <?= e(truncate($v['title'], 60)) ?>
                                    </a>
                                </td>
                                <td style="font-size: 0.85rem;">
                                    <a href="<?= BASE_URL ?>/channel.php?id=<?= $v['user_id'] ?>" target="_blank" style="font-weight: 500;">
                                        <?= e($v['channel_name'] ?: $v['username']) ?>
                                    </a>
                                </td>
                                <td class="text-muted" style="font-size: 0.82rem;"><?= date('M d, Y H:i', strtotime($v['created_at'])) ?></td>
                                <td style="text-align: right;">
                                    <form method="POST" action="" style="display: inline-flex; flex-direction: column; gap: 4px; align-items: flex-end;">
                                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="video_id" value="<?= $v['id'] ?>">
                                        
                                        <div style="display: flex; gap: 6px;">
                                            <input type="text" name="approval_note" placeholder="Admin note (optional)" class="form-input" style="font-size: 0.72rem; padding: 4px 8px; height: 26px; width: 140px; margin-bottom: 2px;">
                                            <button type="submit" name="action" value="approve_video" class="btn btn-xs" style="background: var(--green); color: #fff; border: 1px solid var(--green);">
                                                Approve
                                            </button>
                                            <button type="submit" name="action" value="reject_video" class="btn btn-xs btn-outline" style="color: var(--red); border-color: rgba(239,68,68,0.4);" onclick="return confirm('Reject this video?');">
                                                Reject
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            <?php endif; ?>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/partials/admin_foot.php'; ?>
