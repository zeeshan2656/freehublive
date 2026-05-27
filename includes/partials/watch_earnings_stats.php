<?php
/**
 * Ad impressions & clicks earnings stat cards (include on any portal dashboard).
 * Optional: $watch_stats_user_id — defaults to logged-in user.
 */
$ws_uid = (int)($watch_stats_user_id ?? auth_user()['id'] ?? 0);
if ($ws_uid < 1) return;

$user = db_fetch(
    "SELECT balance, total_ad_impressions, total_ad_clicks, lifetime_ad_earnings, preferred_currency 
     FROM users WHERE id=?", 
    [$ws_uid]
);
if (!$user) return;

$currency = $user['preferred_currency'] ?? 'USD';
$ws_compact = !empty($watch_stats_compact);
?>
<div class="fh-watch-earnings-stats" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(<?= $ws_compact ? '200px' : '240px' ?>,1fr));gap:16px;margin-bottom:<?= $ws_compact ? '20px' : '32px' ?>">
  
  <!-- Balance Card -->
  <div class="stat-card" style="background:linear-gradient(135deg,rgba(34,197,94,.1),transparent);border-color:rgba(34,197,94,.3);position:relative;overflow:hidden">
    <div style="position:absolute;top:-10px;right:-10px;opacity:.05;font-size:5rem;transform:rotate(15deg)">💰</div>
    <div class="stat-label" style="display:flex;align-items:center;gap:6px;font-size:.8rem;color:var(--text2);margin-bottom:6px">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
      Available Balance
    </div>
    <div class="stat-value" style="color:var(--green);font-size:1.6rem;font-weight:800;letter-spacing:-.02em" data-fh-balance><?= e(fh_format_money((float)$user['balance'], $currency)) ?></div>
  </div>

  <!-- Ad Impressions & Clicks Card -->
  <div class="stat-card" style="position:relative;overflow:hidden">
    <div style="position:absolute;top:-10px;right:-10px;opacity:.03;font-size:5rem;transform:rotate(-10deg)">📺</div>
    <div class="stat-label" style="display:flex;align-items:center;gap:6px;font-size:.8rem;color:var(--text2);margin-bottom:6px">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="2"/><path d="M6 18h12"/></svg>
      Ad Interactions
    </div>
    <div class="stat-value" style="font-size:1.6rem;font-weight:800;letter-spacing:-.02em" data-fh-watch-time><?= format_number((int)$user['total_ad_impressions']) ?> Imps</div>
    <div class="text-xs text-muted" style="margin-top:4px;font-weight:600" data-fh-watch-hours><?= format_number((int)$user['total_ad_clicks']) ?> Clicks</div>
  </div>

  <!-- Lifetime Earnings Card -->
  <div class="stat-card" style="background:linear-gradient(135deg,rgba(99,102,241,.08),transparent);border-color:rgba(99,102,241,.25);position:relative;overflow:hidden">
    <div style="position:absolute;top:-10px;right:-10px;opacity:.04;font-size:5rem;transform:rotate(20deg)">📈</div>
    <div class="stat-label" style="display:flex;align-items:center;gap:6px;font-size:.8rem;color:var(--text2);margin-bottom:6px">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
      Lifetime Ad Earnings
    </div>
    <div class="stat-value" style="color:var(--accent);font-size:1.6rem;font-weight:800;letter-spacing:-.02em" data-fh-lifetime-earnings title="Auto-updated from ad activities">
      <?= e(fh_format_money((float)$user['lifetime_ad_earnings'], $currency)) ?>
    </div>
    <div class="text-xs" style="margin-top:4px;color:var(--accent);opacity:.8;font-weight:600">
      <?php if (has_role('creator') && (str_contains($_SERVER['REQUEST_URI'], '/creator/') || !empty($creator_context))): ?>
        CPM: <?= fh_format_money(setting('creator_cpm', '1.00'), 'USD') ?> | CPC: <?= fh_format_money(setting('creator_cpc', '50.00'), 'USD') ?> (Creator Rates)
      <?php else: ?>
        CPM: <?= fh_format_money(setting('viewer_cpm', '0.50'), 'USD') ?> | CPC: <?= fh_format_money(setting('viewer_cpc', '20.00'), 'USD') ?> (Viewer Rates)
      <?php endif; ?>
    </div>
  </div>
</div>
