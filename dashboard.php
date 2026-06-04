<?php
// ============================================================
// FreeHub.Live — Saved Videos & Subscriptions Dashboard
// ============================================================
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$preview_uid = (int)($_GET['uid'] ?? 0);
$current_auth = auth_user();
if (!$current_auth) {
    logout_user();
    redirect(BASE_URL . '/auth/login.php');
}
$auth_uid = (int)$current_auth['id'];
$display_uid = $auth_uid;
$display_role = $current_auth['role'] ?? 'viewer';

if ($preview_uid > 0 && is_admin()) {
    $preview_user = db_fetch("SELECT id, role FROM users WHERE id=?", [$preview_uid]);
    if ($preview_user) {
        $display_uid = (int)$preview_user['id'];
        $display_role = $preview_user['role'] ?? 'viewer';
    }
}

$sidebar_role = $display_role;
$uid  = $display_uid;
$user = db_fetch("SELECT * FROM users WHERE id=?", [$uid]);
if (!$user) {
    http_response_code(404);
    die('User not found');
}

$tab = $_GET['tab'] ?? 'saved';
if (!in_array($tab, ['saved', 'subscriptions'], true)) {
    $tab = 'saved';
}

$meta_title = $tab === 'subscriptions' ? 'Subscribed Channels' : 'Saved Videos';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 16px;">
      <?php foreach (get_flash() as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
      <?php endforeach; ?>

      <?php if ($tab === 'subscriptions'): ?>
      <div class="card" style="padding:24px">
        <h3 style="font-weight:800;font-size:1.2rem;display:flex;align-items:center;gap:8px;margin-bottom:20px">
          <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="color:var(--accent)"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          Subscribed Channels
        </h3>
        
        <?php
        $subs = db_fetchAll(
            "SELECT u.id, u.username, u.channel_name, u.avatar, u.subscribers
             FROM subscriptions s
             JOIN users u ON s.channel_id = u.id
             WHERE s.subscriber_id = ?
             ORDER BY s.created_at DESC",
            [$auth_uid]
        );
        ?>
        
        <?php if ($subs): ?>
          <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:16px;">
            <?php foreach ($subs as $sub): ?>
              <div class="stat-card" style="padding:16px; display:flex; align-items:center; justify-content:space-between; gap:16px; border-radius:var(--radius-lg); border:1px solid var(--border); background:var(--bg2);">
                <div style="display:flex; align-items:center; gap:12px; min-width:0">
                  <img src="<?= avatar_url($sub['avatar']) ?>" class="avatar" width="48" height="48" style="flex-shrink:0; border-radius:50%; object-fit:cover">
                  <div style="min-width:0">
                    <div style="font-weight:700; font-size:.9rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:var(--text)">
                      <?= e($sub['channel_name'] ?: $sub['username']) ?>
                    </div>
                    <div class="text-muted text-xs" style="margin-top:2px">
                      <?= format_number((int)$sub['subscribers']) ?> subscribers
                    </div>
                  </div>
                </div>
                <a href="<?= BASE_URL ?>/channel.php?id=<?= $sub['id'] ?>" class="btn btn-outline btn-sm" style="padding: 6px 12px; font-size: 0.78rem; font-weight: 600; display:inline-flex; align-items:center; border-radius:14px">
                  Visit Channel
                </a>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div style="text-align:center; padding:48px 24px; color:var(--text2)">
            <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 12px; opacity:.4"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            <p style="font-size:.9rem">You haven't subscribed to any channels yet.</p>
            <a href="<?= BASE_URL ?>/" class="btn btn-primary btn-sm" style="margin-top:16px">Explore Videos</a>
          </div>
        <?php endif; ?>
      </div>

      <?php elseif ($tab === 'saved'): ?>
      <?php
        $savedVideos = db_fetchAll(
            "SELECT v.*, u.username, u.channel_name, u.avatar
             FROM watch_later w
             JOIN videos v ON v.id = w.video_id
             JOIN users u ON u.id = v.user_id
             WHERE w.user_id = ?
             ORDER BY w.added_at DESC",
            [$uid]
        );
        $savedRef = auth_user()['ref_code'] ?? '';
      ?>
      <div class="card" style="padding:24px">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;border-bottom:1px solid var(--border);padding-bottom:16px">
          <h3 style="font-weight:700;font-size:1.1rem;margin:0">🎥 Saved Videos</h3>
          <span class="text-muted" style="font-size:.9rem">Showing <?= format_number(count($savedVideos)) ?> item<?= count($savedVideos) === 1 ? '' : 's' ?></span>
        </div>

        <?php if ($savedVideos): ?>
          <div class="grid grid-6">
            <?php foreach ($savedVideos as $video): ?>
              <?= render_video_card($video, fh_video_card_opts($video, [], $savedRef)) ?>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div style="text-align:center; padding:48px 24px; color:var(--text2)">
            <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 12px; opacity:.4"><path d="M5 13l4 4 10-10"/><path d="M12 20a8 8 0 1 0 0-16 8 8 0 0 0 0 16z"/></svg>
            <p style="font-size:.95rem; margin-top:8px;">No saved videos yet.</p>
            <p class="text-muted" style="margin-top:6px; font-size:.9rem">Save videos from the watch page to see them here.</p>
            <a href="<?= BASE_URL ?>/" class="btn btn-primary btn-sm" style="margin-top:14px">Browse Videos</a>
          </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
