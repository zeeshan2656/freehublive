<?php
// ============================================================
// FreeHub.Live — Earnings & Watch Time Dashboard (all users)
// ============================================================
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$preview_uid = (int)($_GET['uid'] ?? 0);
$auth_uid = (int)auth_user()['id'];
$display_uid = $auth_uid;
$display_role = auth_user()['role'] ?? 'viewer';

if ($preview_uid > 0 && is_admin()) {
    $preview_user = db_fetch("SELECT id, role, preferred_currency FROM users WHERE id=?", [$preview_uid]);
    if ($preview_user) {
        $display_uid = (int)$preview_user['id'];
        $display_role = $preview_user['role'] ?? 'viewer';
    }
}

$sidebar_role = $display_role;
$uid  = $display_uid;
$user = db_fetch("SELECT * FROM users WHERE id=?", [$uid]);
$stats = fh_user_watch_stats($uid);
$currency = $user['preferred_currency'] ?? fh_user_currency();
$minUsd = fh_min_withdrawal_usd($uid);
$canWithdraw = (float)$user['balance'] >= $minUsd && !fh_pending_withdrawal($uid);

$error = '';
$success = '';

$earnings = db_fetchAll(
    "SELECT * FROM earnings WHERE user_id=? ORDER BY created_at DESC LIMIT 40",
    [$uid]
);

// --- Date Filters & Analytics Queries ---
$period  = $_GET['period'] ?? '30';
$from    = $_GET['from'] ?? '';
$to      = $_GET['to'] ?? '';

if ($from && $to) {
    $dateWhere  = "DATE(created_at) BETWEEN ? AND ?";
    $dateParams = [$from, $to];
    $label      = "($from to $to)";
    $days       = max(1, (int)((strtotime($to) - strtotime($from)) / 86400) + 1);
} else {
    $days       = in_array((int)$period, [7, 14, 30, 60, 90]) ? (int)$period : 30;
    $dateWhere  = "created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
    $dateParams = [$days];
    $label      = "Last $days days";
}

$qParams = array_merge([$uid], $dateParams);

$total_videos_watched = db_count('video_views', "user_id=? AND $dateWhere", $qParams);
$total_watch_time = db_fetch("SELECT SUM(watch_seconds) as t FROM video_views WHERE user_id=? AND $dateWhere", $qParams)['t'] ?? 0;
$total_viewing_earnings = db_fetch("SELECT SUM(amount) as t FROM earnings WHERE user_id=? AND type='watch_time' AND $dateWhere", $qParams)['t'] ?? 0;

$total_clicks = fh_table_exists('affiliate_clicks') ? db_count('affiliate_clicks', "affiliate_id=? AND $dateWhere", $qParams) : 0;
$total_referrals = fh_table_exists('referral_conversions') ? db_count('referral_conversions', "referrer_id=? AND $dateWhere", $qParams) : 0;

$ref_views_data = db_fetch("SELECT COUNT(id) as views, SUM(watch_seconds) as wt FROM video_views WHERE affiliate_id=? AND $dateWhere", $qParams);
$total_ref_views = $ref_views_data['views'] ?? 0;
$total_ref_watch = $ref_views_data['wt'] ?? 0;

$referral_earnings = db_fetch("SELECT SUM(amount) as t FROM earnings WHERE user_id=? AND type='referral' AND status='approved' AND $dateWhere", $qParams)['t'] ?? 0;

$chart = [];
for ($i = min($days, 30) - 1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $vw = db_fetch("SELECT SUM(watch_seconds) as wt FROM video_views WHERE user_id=? AND DATE(created_at)=?", [$uid, $d])['wt'] ?? 0;
    $chart[] = ['date' => date('M j', strtotime("-$i days")), 'wt' => $vw];
}
$maxWt = max(1, max(array_column($chart, 'wt')));

$meta_title = 'My Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
      <div class="flex" style="justify-content:flex-end;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px">
        
        <!-- Date Filters -->
        <form method="GET" action="" style="display:flex;gap:8px;align-items:center" class="text-sm">
          <select name="period" class="form-input form-select" style="padding:4px 8px;height:32px;font-size:.8rem" onchange="this.form.submit()">
            <option value="7" <?= $period==='7'?'selected':'' ?>>Last 7 Days</option>
            <option value="14" <?= $period==='14'?'selected':'' ?>>Last 14 Days</option>
            <option value="30" <?= $period==='30'?'selected':'' ?>>Last 30 Days</option>
            <option value="60" <?= $period==='60'?'selected':'' ?>>Last 60 Days</option>
            <option value="90" <?= $period==='90'?'selected':'' ?>>Last 90 Days</option>
            <option value="custom" <?= $period==='custom'?'selected':'' ?>>Custom Range...</option>
          </select>
          <?php if ($period === 'custom'): ?>
          <input type="date" name="from" value="<?= e($from) ?>" class="form-input" style="padding:4px 8px;height:32px;font-size:.8rem" required>
          <span>to</span>
          <input type="date" name="to" value="<?= e($to) ?>" class="form-input" style="padding:4px 8px;height:32px;font-size:.8rem" required>
          <button type="submit" class="btn btn-primary btn-sm" style="height:32px">Apply</button>
          <?php endif; ?>
        </form>
      </div>

      <?php foreach (get_flash() as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
      <?php endforeach; ?>
      <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

      <?php $watch_stats_user_id = $uid; require __DIR__ . '/includes/partials/watch_earnings_stats.php'; ?>

      <div style="display:flex;align-items:center;justify-content:space-between;margin-top:32px;margin-bottom:16px">
        <h3 style="font-weight:800;font-size:1.2rem;display:flex;align-items:center;gap:8px">
          <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="color:var(--accent)"><path d="M21.21 15.89A10 10 0 1 1 8 2.83M22 12A10 10 0 0 0 12 2v10z"/></svg>
          Viewing Performance <span style="font-size:.8rem;font-weight:600;color:var(--text2);background:var(--bg3);padding:2px 8px;border-radius:12px;margin-left:8px"><?= e($label) ?></span>
        </h3>
      </div>
      
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;margin-bottom:32px">
        <div class="stat-card" style="padding:20px;display:flex;flex-direction:column;justify-content:center">
          <div class="stat-label" style="font-size:.8rem;color:var(--text2);margin-bottom:6px;text-transform:uppercase;letter-spacing:.05em">Videos Watched</div>
          <div class="stat-value" style="font-size:1.8rem;font-weight:800"><?= format_number($total_videos_watched) ?></div>
        </div>
        <div class="stat-card" style="padding:20px;display:flex;flex-direction:column;justify-content:center">
          <div class="stat-label" style="font-size:.8rem;color:var(--text2);margin-bottom:6px;text-transform:uppercase;letter-spacing:.05em">Watch Time</div>
          <div class="stat-value" style="font-size:1.8rem;font-weight:800"><?= format_duration((int)$total_watch_time) ?></div>
        </div>
        <div class="stat-card" style="padding:20px;display:flex;flex-direction:column;justify-content:center;background:linear-gradient(135deg,rgba(99,102,241,.05),transparent);border-color:rgba(99,102,241,.2)">
          <div class="stat-label" style="font-size:.8rem;color:var(--accent);margin-bottom:6px;text-transform:uppercase;letter-spacing:.05em">Viewing Earnings</div>
          <div class="stat-value" style="color:var(--accent);font-size:1.8rem;font-weight:800">$<?= number_format((float)$total_viewing_earnings, 4) ?></div>
        </div>
      </div>

      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
        <h3 style="font-weight:800;font-size:1.2rem;display:flex;align-items:center;gap:8px">
          <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="color:var(--green)"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          Referral Performance <span style="font-size:.8rem;font-weight:600;color:var(--text2);background:var(--bg3);padding:2px 8px;border-radius:12px;margin-left:8px"><?= e($label) ?></span>
        </h3>
      </div>
      
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:32px">
        <div class="stat-card" style="padding:16px">
          <div class="stat-label" style="font-size:.75rem;color:var(--text2);margin-bottom:4px">Shared Links Clicks</div>
          <div class="stat-value" style="font-size:1.4rem;font-weight:800"><?= format_number($total_clicks) ?></div>
        </div>
        <div class="stat-card" style="padding:16px">
          <div class="stat-label" style="font-size:.75rem;color:var(--text2);margin-bottom:4px">Referral Signups</div>
          <div class="stat-value" style="font-size:1.4rem;font-weight:800"><?= format_number($total_referrals) ?></div>
        </div>
        <div class="stat-card" style="padding:16px">
          <div class="stat-label" style="font-size:.75rem;color:var(--text2);margin-bottom:4px">Referral Views</div>
          <div class="stat-value" style="font-size:1.4rem;font-weight:800"><?= format_number($total_ref_views) ?></div>
        </div>
        <div class="stat-card" style="padding:16px">
          <div class="stat-label" style="font-size:.75rem;color:var(--text2);margin-bottom:4px">Referral Watch Time</div>
          <div class="stat-value" style="font-size:1.4rem;font-weight:800"><?= format_duration((int)$total_ref_watch) ?></div>
        </div>
        <div class="stat-card" style="padding:16px;background:linear-gradient(135deg,rgba(34,197,94,.05),transparent);border-color:rgba(34,197,94,.2)">
          <div class="stat-label" style="font-size:.75rem;color:var(--green);margin-bottom:4px">Referral Earnings</div>
          <div class="stat-value" style="color:var(--green);font-size:1.4rem;font-weight:800">$<?= number_format((float)$referral_earnings, 4) ?></div>
        </div>
      </div>

      <!-- Watch Time Chart -->
      <div class="card" style="margin-bottom:28px;padding:24px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
          <h3 style="font-weight:800;font-size:1.15rem;display:flex;align-items:center;gap:8px">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="color:var(--accent)"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            Daily Watch Time Trend
            <span style="font-size:.8rem;font-weight:600;color:var(--text2);background:var(--bg3);padding:2px 8px;border-radius:12px"><?= e($label) ?></span>
          </h3>
        </div>
        <?php
          $hasData = max(array_column($chart, 'wt')) > 0;
        ?>
        <?php if (!$hasData): ?>
        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:140px;color:var(--text2);gap:8px">
          <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="opacity:.4"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          <span style="font-size:.9rem">No watch time data for this period</span>
        </div>
        <?php else: ?>
        <div style="display:flex;align-items:flex-end;gap:4px;height:160px;overflow-x:auto;padding-bottom:4px">
          <?php foreach ($chart as $d):
            $h = max(4, round(($d['wt']/$maxWt)*130));
            $opacity = $d['wt'] > 0 ? '1' : '0.2';
          ?>
          <div style="flex:1;min-width:28px;max-width:44px;display:flex;flex-direction:column;align-items:center;gap:4px;height:160px;justify-content:flex-end">
            <?php if($d['wt'] > 0): ?>
            <span style="font-size:.6rem;color:var(--text2);white-space:nowrap;writing-mode:horizontal-tb"><?= format_duration((int)$d['wt']) ?></span>
            <?php endif; ?>
            <div style="width:100%;max-width:32px;height:<?= $h ?>px;background:linear-gradient(to top,var(--accent),rgba(139,92,246,.5));border-radius:5px 5px 0 0;opacity:<?= $opacity ?>;transition:opacity .2s;cursor:default" title="<?= $d['date'] ?>: <?= format_duration((int)$d['wt']) ?> watch time"></div>
            <span style="font-size:.6rem;color:var(--text2);white-space:nowrap"><?= $d['date'] ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>


      <?php if (!is_admin() && (is_creator() || $user['role'] === 'creator')): ?>
      <div class="card" style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;background:linear-gradient(135deg,rgba(99,102,241,.06),transparent);border-color:rgba(99,102,241,.2)">
        <div style="display:flex;align-items:center;gap:14px">
          <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,var(--accent),rgba(139,92,246,.6));display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg width="22" height="22" fill="none" stroke="#fff" stroke-width="2.2" viewBox="0 0 24 24"><path d="M15 10l4.553-2.069A1 1 0 0 1 21 8.82v6.36a1 1 0 0 1-1.447.89L15 14M3 8a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8z"/></svg>
          </div>
          <div>
            <div style="font-weight:700;font-size:.95rem">Creator Channel</div>
            <div class="text-sm text-muted">Your channel: <a href="<?= BASE_URL ?>/channel.php?id=<?= $uid ?>" style="color:var(--accent)"><?= e($user['channel_name'] ?? $user['username']) ?></a></div>
          </div>
        </div>
        <a href="<?= BASE_URL ?>/creator/" class="btn btn-primary btn-sm">Open Creator Studio →</a>
      </div>
      <?php endif; ?>

      <?php if (is_admin()): ?>
      <div class="card" style="margin-bottom:20px;background:linear-gradient(135deg,rgba(99,102,241,.08),transparent);border-color:rgba(99,102,241,.3);display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap">
        <div style="display:flex;align-items:center;gap:14px">
          <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,var(--accent),rgba(139,92,246,.7));display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg width="22" height="22" fill="none" stroke="#fff" stroke-width="2.2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
          </div>
          <div>
            <div style="font-weight:700;font-size:.95rem">Admin Channel &amp; Ads</div>
            <div class="text-sm text-muted">Manage ads and your official admin channel</div>
          </div>
        </div>
        <div class="flex gap-2" style="flex-wrap:wrap">
          <a href="<?= BASE_URL ?>/admin/ads.php" class="btn btn-primary btn-sm">Manage Ads</a>
          <a href="<?= BASE_URL ?>/channel.php?id=<?= $uid ?>" class="btn btn-outline btn-sm">View Channel</a>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!is_admin()): ?>
      <!-- Referral Link Card -->
      <div class="card" style="margin-bottom:28px;background:linear-gradient(135deg,rgba(99,102,241,.1),rgba(139,92,246,.06));border-color:rgba(99,102,241,.3)">
        <div class="flex gap-3" style="align-items:center;flex-wrap:wrap">
          <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,rgba(99,102,241,.3),rgba(139,92,246,.2));display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" style="color:var(--accent)"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
          </div>
          <div style="flex:1">
            <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--accent);margin-bottom:3px">Your Referral Link</div>
            <code style="font-size:.82rem;color:var(--text);word-break:break-all"><?= e(BASE_URL . '/?ref=' . ($user['ref_code'] ?? '')) ?></code>
          </div>
          <a href="<?= BASE_URL ?>/referral.php" class="btn btn-outline btn-sm">Full Dashboard →</a>
        </div>
      </div>



      <div class="card" style="padding:24px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:8px">
          <h3 style="font-weight:800;font-size:1.1rem;display:flex;align-items:center;gap:8px">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="color:var(--green)"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            Earnings History
          </h3>
        </div>
        <?php if ($earnings): ?>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Type</th><th>Amount (USD)</th><th>Status</th><th>Description</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($earnings as $e): ?>
            <tr>
              <td><span class="badge badge-blue"><?= e($e['type']) ?></span></td>
              <td style="font-weight:600;color:var(--green)"><?= fh_format_money((float)$e['amount'], $currency) ?></td>
              <td><span class="badge badge-<?= $e['status']==='paid'||$e['status']==='approved'?'green':($e['status']==='pending'?'yellow':'gray') ?>"><?= e($e['status']) ?></span></td>
              <td class="text-sm text-muted"><?= e($e['description'] ?? '') ?></td>
              <td class="text-xs text-muted"><?= date('M j, Y', strtotime($e['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <p class="text-muted text-sm">No earnings yet. Watch videos to start earning based on your watch time.</p>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
