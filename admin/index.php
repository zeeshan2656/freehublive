<?php
// ============================================================
// FreeHub.Live — Admin Dashboard
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

// Stats
$stats = [
    'users'       => db_count('users'),
    'viewers'     => db_count('users', "role IN ('viewer','affiliate')"),
    'creators'    => db_count('users', "role='creator'"),
    'videos'      => db_count('videos'),
    'views'       => db_fetch("SELECT SUM(views) as t FROM videos")['t'] ?? 0,
    'pending_vid' => db_count('videos', "status='pending'"),
    'pending_wd'  => fh_table_exists('withdrawal_requests') ? db_count('withdrawal_requests', "status='pending'") : 0,
    'earnings_distributed' => (float)(db_fetch("SELECT COALESCE(SUM(amount),0) AS t FROM earnings WHERE type IN ('ad_impression', 'ad_click') AND status='approved'")['t'] ?? 0),
    'earnings_paid'        => (float)(db_fetch("SELECT COALESCE(SUM(amount),0) AS t FROM withdrawal_requests WHERE status='paid'")['t'] ?? 0),
];

$recent_videos = db_fetchAll(
    "SELECT v.*,u.username,u.channel_name FROM videos v JOIN users u ON u.id=v.user_id
     ORDER BY v.created_at DESC LIMIT 8"
);
$recent_users = db_fetchAll(
    "SELECT * FROM users ORDER BY created_at DESC LIMIT 6"
);

$approvalMode = setting('video_approval_mode', 'manual');

$meta_title = 'Dashboard';
require_once __DIR__ . '/partials/admin_head.php';
?>
<div class="admin-content">

    <!-- Admin Welcome Banner (no earnings shown — admin cannot earn) -->
    <div class="card" style="margin-bottom:24px;background:linear-gradient(135deg,rgba(99,102,241,.12),rgba(139,92,246,.06));border-color:rgba(99,102,241,.3)">
      <div class="flex gap-4" style="align-items:center;flex-wrap:wrap">
        <div>
          <div style="font-size:1.1rem;font-weight:800;margin-bottom:4px">Welcome back, Admin 👋</div>
          <div class="text-sm text-muted">You are managing the FreeHub platform. Admins do not earn from the platform.</div>
        </div>
        <div class="flex gap-2" style="margin-left:auto;flex-wrap:wrap">
          <a href="<?= BASE_URL ?>/admin/settings.php" class="btn btn-outline btn-sm">⚙️ Settings</a>
          <a href="<?= BASE_URL ?>/admin/analytics.php" class="btn btn-primary btn-sm">📊 Analytics</a>
        </div>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="stat-grid-4" style="margin-bottom:24px">
      <div class="stat-card">
        <div class="stat-value"><?= format_number($stats['users']) ?></div>
        <div class="stat-label">Total Users</div>
        <div class="text-xs text-muted" style="margin-top:4px">👁️ <?= $stats['viewers'] ?> viewers · 🎬 <?= $stats['creators'] ?> creators</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?= format_number($stats['videos']) ?></div>
        <div class="stat-label">Total Videos</div>
        <div class="text-xs" style="margin-top:4px;color:var(--yellow)"><?= $stats['pending_vid'] ?> pending review</div>
      </div>
      <div class="stat-card">
        <div class="stat-value" style="color:var(--green)">$<?= number_format($stats['earnings_distributed'],2) ?></div>
        <div class="stat-label">Earnings Distributed</div>
        <div class="text-xs text-muted" style="margin-top:4px">$<?= number_format($stats['earnings_paid'],2) ?> paid out</div>
      </div>
      <div class="stat-card">
        <div class="stat-value" style="color:var(--yellow)"><?= $stats['pending_wd'] ?></div>
        <div class="stat-label">Pending Withdrawals</div>
        <div class="text-xs text-muted" style="margin-top:4px">
          <a href="<?= BASE_URL ?>/admin/withdrawals.php" style="color:var(--accent)">View all →</a>
        </div>
      </div>
    </div>

    <!-- Video Approval Mode Banner -->
    <?php if ($stats['pending_vid'] > 0 || $approvalMode === 'manual'): ?>
    <div class="card" style="margin-bottom:24px;border-color:var(--yellow);background:rgba(255,179,0,.05)">
      <div class="flex gap-3" style="align-items:center;flex-wrap:wrap">
        <div>
          <div style="font-weight:700">📹 Video Approval: <?= strtoupper($approvalMode) ?> mode</div>
          <?php if ($stats['pending_vid'] > 0): ?>
          <div class="text-sm text-muted"><?= $stats['pending_vid'] ?> videos waiting for your review</div>
          <?php endif; ?>
        </div>
        <div class="flex gap-2" style="margin-left:auto">
          <a href="<?= BASE_URL ?>/admin/videos.php?filter=pending" class="btn btn-sm" style="background:var(--yellow);color:#1a1000">
            Review Now (<?= $stats['pending_vid'] ?>)
          </a>
          <a href="<?= BASE_URL ?>/admin/settings.php" class="btn btn-outline btn-sm">Change Mode</a>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <h2 style="font-size:.9rem;font-weight:700;margin-bottom:12px;color:var(--text2);text-transform:uppercase;letter-spacing:.05em">Quick Actions</h2>
    <div class="quick-actions" style="margin-bottom:28px">
      <a href="<?= BASE_URL ?>/admin/videos.php?filter=pending" class="qa-btn">
        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
        <span>Approve Videos</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/users.php?role=creator&status=pending" class="qa-btn">
        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
        <span>New Creators</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/withdrawals.php?status=pending" class="qa-btn">
        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        <span>Withdrawals</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/analytics.php" class="qa-btn">
        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        <span>Analytics</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/users.php" class="qa-btn">
        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <span>All Users</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/settings.php" class="qa-btn">
        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/></svg>
        <span>Settings</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/settings.php?tab=adcode" class="qa-btn">
        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/><path d="M10 8l-2 2 2 2M14 8l2 2-2 2"/></svg>
        <span>Ad Code</span>
      </a>
    </div>

    <div class="stat-grid-2">
      <!-- Recent Videos -->
      <div class="card card-sm">
        <div class="section-header" style="margin-bottom:16px">
          <h3 style="font-weight:700;font-size:.95rem">Recent Videos</h3>
          <a href="<?= BASE_URL ?>/admin/videos.php" class="see-all">All &rarr;</a>
        </div>
        <?php foreach ($recent_videos as $v): ?>
        <div class="flex gap-3" style="padding:8px 0;border-bottom:1px solid var(--border)">
          <img src="<?= thumb_url($v['thumbnail']) ?>" style="width:64px;aspect-ratio:16/9;border-radius:4px;object-fit:cover;flex-shrink:0" loading="lazy">
          <div style="min-width:0;flex:1">
            <div style="font-size:.83rem;font-weight:600;overflow:hidden;white-space:nowrap;text-overflow:ellipsis"><?= e($v['title']) ?></div>
            <div class="text-muted text-xs"><?= e($v['channel_name']??$v['username']) ?> · <?= format_number((int)$v['views']) ?> views</div>
          </div>
          <span class="badge badge-<?= $v['status']==='published'?'green':(($v['status']==='pending' || $v['status']==='processing')?'yellow':($v['status']==='rejected'?'red':'gray')) ?>"><?= e($v['status']==='processing'?'Pending':ucfirst($v['status'])) ?></span>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Recent Users -->
      <div class="card card-sm">
        <div class="section-header" style="margin-bottom:16px">
          <h3 style="font-weight:700;font-size:.95rem">Recent Users</h3>
          <a href="<?= BASE_URL ?>/admin/users.php" class="see-all">All &rarr;</a>
        </div>
        <?php foreach ($recent_users as $u): ?>
        <div class="flex gap-3" style="padding:8px 0;border-bottom:1px solid var(--border)">
          <img src="<?= avatar_url($u['avatar']) ?>" class="avatar avatar-sm" width="32" height="32" loading="lazy">
          <div style="min-width:0;flex:1">
            <div style="font-size:.83rem;font-weight:600"><?= e($u['username']) ?></div>
            <div class="text-muted text-xs"><?= e($u['email']) ?></div>
          </div>
          <span class="badge badge-<?= $u['role']==='admin'?'blue':($u['role']==='creator'?'green':($u['role']==='affiliate'?'yellow':'gray')) ?>">
            <?= $u['role'] === 'creator' ? 'Creator' : ($u['role'] === 'viewer' ? 'Viewer' : ucfirst($u['role'])) ?>
          </span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/partials/admin_foot.php'; ?>
