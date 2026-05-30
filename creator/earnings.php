<?php
// Creator — Earnings Dashboard
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['creator','admin']);

$site_theme = setting('active_theme', 'dark-minimal');
$primary    = setting('primary_color', '#6366f1');
$uid = auth_user()['id'];
$user = db_fetch("SELECT * FROM users WHERE id=?", [$uid]);

$currency = $user['preferred_currency'] ?? fh_user_currency();
$minUsd = fh_min_withdrawal_usd($uid);
$canWithdraw = (float)$user['balance'] >= $minUsd && !fh_pending_withdrawal($uid);

$meta_title = 'Earnings';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
    
    <!-- Centralized ad impressions & earnings stat cards -->
    <?php $watch_stats_user_id = (int)$uid; $creator_context = true; require __DIR__ . '/../includes/partials/watch_earnings_stats.php'; ?>

    <div class="card" style="margin-top:24px; padding:24px; border-radius: var(--radius-lg); border: 1px solid var(--border); background: var(--bg2); border-top: 4px solid var(--accent);">
      <h3 style="font-weight:800; font-size:1.20rem; display:flex; align-items:center; gap:8px; margin-bottom:20px; color: var(--text);">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="color:var(--accent)"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Monetization Summary
      </h3>
      
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:20px; margin-bottom:24px;">
        <!-- Account Balance Card -->
        <div class="stat-card" style="padding:20px; display:flex; flex-direction:column; justify-content:space-between; border-radius:var(--radius-lg); border:1px solid var(--border); background:var(--bg3);">
          <div>
            <div class="stat-label" style="font-size:0.75rem; color:var(--text2); text-transform:uppercase; margin-bottom:6px; font-weight:600;">Account Balance</div>
            <div class="stat-value" style="font-size:2rem; font-weight:800; color:var(--green);"><?= e(fh_format_money((float)$user['balance'], $currency)) ?></div>
          </div>
          <div class="text-xs text-muted" style="margin-top:8px;">Available for immediate withdrawal request</div>
        </div>

        <!-- Minimum Withdrawal Card -->
        <div class="stat-card" style="padding:20px; display:flex; flex-direction:column; justify-content:space-between; border-radius:var(--radius-lg); border:1px solid var(--border); background:var(--bg3);">
          <div>
            <div class="stat-label" style="font-size:0.75rem; color:var(--text2); text-transform:uppercase; margin-bottom:6px; font-weight:600;">Minimum Withdrawal Threshold</div>
            <div class="stat-value" style="font-size:1.6rem; font-weight:800; color:var(--text);"><?= e(fh_format_money($minUsd, $currency)) ?></div>
          </div>
          <div class="text-xs text-muted" style="margin-top:8px;">Dynamically configured from admin settings</div>
        </div>
      </div>

      <div style="display:flex; justify-content:flex-end; gap:12px; flex-wrap: wrap;">
        <a href="<?= BASE_URL ?>/withdrawal.php" class="btn btn-primary" style="border-radius:18px; padding:8px 20px; font-weight:600; display: inline-flex; align-items: center; gap: 6px;">
          <span>💳</span> Request Withdrawal &rarr;
        </a>
      </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
