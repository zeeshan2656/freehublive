<?php
/**
 * Watch-time earnings stat cards (include on any portal dashboard).
 * Optional: $watch_stats_user_id — defaults to logged-in user.
 */
if (!function_exists('fh_user_watch_stats')) {
    return;
}
$ws_uid = (int)($watch_stats_user_id ?? auth_user()['id'] ?? 0);
if ($ws_uid < 1) return;

$watch_stats = fh_user_watch_stats($ws_uid);
$watch_currency = $watch_stats['currency'];
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
    <div class="stat-value" style="color:var(--green);font-size:1.6rem;font-weight:800;letter-spacing:-.02em" data-fh-balance><?= e($watch_stats['balance_formatted']) ?></div>
  </div>

  <!-- Watch Time Card -->
  <div class="stat-card" style="position:relative;overflow:hidden">
    <div style="position:absolute;top:-10px;right:-10px;opacity:.03;font-size:5rem;transform:rotate(-10deg)">⏱️</div>
    <div class="stat-label" style="display:flex;align-items:center;gap:6px;font-size:.8rem;color:var(--text2);margin-bottom:6px">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      Total Watch Time
    </div>
    <div class="stat-value" style="font-size:1.6rem;font-weight:800;letter-spacing:-.02em" data-fh-watch-time><?= format_duration($watch_stats['total_watch_seconds']) ?></div>
    <div class="text-xs text-muted" style="margin-top:4px;font-weight:600" data-fh-watch-hours><?= number_format($watch_stats['watch_hours'], 2) ?> Hours</div>
  </div>

  <!-- Lifetime Earnings Card -->
  <div class="stat-card" style="background:linear-gradient(135deg,rgba(99,102,241,.08),transparent);border-color:rgba(99,102,241,.25);position:relative;overflow:hidden">
    <div style="position:absolute;top:-10px;right:-10px;opacity:.04;font-size:5rem;transform:rotate(20deg)">📈</div>
    <div class="stat-label" style="display:flex;align-items:center;gap:6px;font-size:.8rem;color:var(--text2);margin-bottom:6px">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
      Lifetime Watch Earnings
    </div>
    <div class="stat-value" style="color:var(--accent);font-size:1.6rem;font-weight:800;letter-spacing:-.02em" data-fh-lifetime-earnings title="Auto-updated from watch time">
      <?= e($watch_stats['lifetime_watch_formatted']) ?>
    </div>
    <div class="text-xs" style="margin-top:4px;color:var(--accent);opacity:.8;font-weight:600">Rate: <?= e($watch_stats['rate_formatted']) ?></div>
  </div>
</div>
