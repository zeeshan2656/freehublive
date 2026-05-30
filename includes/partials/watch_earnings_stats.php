<?php
/**
 * Ad impressions & clicks earnings stat cards (include on any portal dashboard).
 * Optional: $watch_stats_user_id — defaults to logged-in user.
 */
$ws_uid = (int)($watch_stats_user_id ?? auth_user()['id'] ?? 0);
if ($ws_uid < 1) return;

$ws_user = db_fetch(
    "SELECT preferred_currency FROM users WHERE id=?", 
    [$ws_uid]
);
if (!$ws_user) return;

$ws_currency = $ws_user['preferred_currency'] ?? 'USD';
$ws_compact = !empty($watch_stats_compact);

$is_creator = (has_role('creator') && (str_contains($_SERVER['REQUEST_URI'], '/creator/') || !empty($creator_context)));

if ($is_creator) {
    $placements = array_filter(array_map('trim', explode(',', setting('creator_eligible_placements', ''))), 'strlen');
} else {
    $placements = array_filter(array_map('trim', explode(',', setting('viewer_eligible_placements', ''))), 'strlen');
}

$impressions = 0;
$clicks = 0;
$cpm_earnings = 0.0;
$cpc_earnings = 0.0;
$total_earnings = 0.0;

if (!empty($placements)) {
    $placeholders = implode(',', array_fill(0, count($placements), '?'));
    
    if ($is_creator) {
        $stats = db_fetch(
            "SELECT 
                SUM(CASE WHEN type = 'impression' THEN 1 ELSE 0 END) AS imps,
                SUM(CASE WHEN type = 'click' THEN 1 ELSE 0 END) AS clks,
                SUM(CASE WHEN type = 'impression' THEN earnings_creator ELSE 0 END) AS cpm_earn,
                SUM(CASE WHEN type = 'click' THEN earnings_creator ELSE 0 END) AS cpc_earn,
                SUM(earnings_creator) AS total_earn
             FROM ad_logs
             WHERE creator_id = ? AND placement IN ($placeholders)",
            array_merge([$ws_uid], $placements)
        );
    } else {
        $stats = db_fetch(
            "SELECT 
                SUM(CASE WHEN type = 'impression' THEN 1 ELSE 0 END) AS imps,
                SUM(CASE WHEN type = 'click' THEN 1 ELSE 0 END) AS clks,
                SUM(CASE WHEN type = 'impression' THEN earnings_viewer ELSE 0 END) AS cpm_earn,
                SUM(CASE WHEN type = 'click' THEN earnings_viewer ELSE 0 END) AS cpc_earn,
                SUM(earnings_viewer) AS total_earn
             FROM ad_logs
             WHERE viewer_id = ? AND placement IN ($placeholders)",
            array_merge([$ws_uid], $placements)
        );
    }
    
    if ($stats) {
        $impressions = (int)($stats['imps'] ?? 0);
        $clicks = (int)($stats['clks'] ?? 0);
        $cpm_earnings = (float)($stats['cpm_earn'] ?? 0.0);
        $cpc_earnings = (float)($stats['cpc_earn'] ?? 0.0);
        $total_earnings = (float)($stats['total_earn'] ?? 0.0);
    }
}
?>
<div class="fh-watch-earnings-stats" style="display: grid !important; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) !important; gap: 12px !important; width: 100% !important;">
  
  <!-- Card 1: Assigned placement impressions -->
  <div class="stat-card" style="position:relative;overflow:hidden">
    <div style="position:absolute;top:-5px;right:-5px;opacity:.03;font-size:3rem;transform:rotate(-10deg)">👁️</div>
    <div class="stat-label" style="display:flex;align-items:center;gap:6px;font-size:0.74rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--text2);margin-bottom:6px">
      <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
      Ad Impressions
    </div>
    <div class="stat-value" style="font-size:1.35rem;font-weight:800;letter-spacing:-.02em"><?= format_number($impressions) ?></div>
    <div class="text-xs text-muted" style="margin-top:4px">Assigned Placements</div>
  </div>

  <!-- Card 2: Assigned placement clicks -->
  <div class="stat-card" style="position:relative;overflow:hidden">
    <div style="position:absolute;top:-5px;right:-5px;opacity:.03;font-size:3rem;transform:rotate(15deg)">🖱️</div>
    <div class="stat-label" style="display:flex;align-items:center;gap:6px;font-size:0.74rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--text2);margin-bottom:6px">
      <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M15 3h6v6M10 14L21 3M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
      Ad Clicks
    </div>
    <div class="stat-value" style="font-size:1.35rem;font-weight:800;letter-spacing:-.02em"><?= format_number($clicks) ?></div>
    <div class="text-xs text-muted" style="margin-top:4px">Assigned Placements</div>
  </div>

  <!-- Card 3: CPM Earnings -->
  <div class="stat-card" style="background:linear-gradient(135deg,rgba(99,102,241,.04),transparent);position:relative;overflow:hidden">
    <div style="position:absolute;top:-5px;right:-5px;opacity:.03;font-size:3rem;transform:rotate(20deg)">📊</div>
    <div class="stat-label" style="display:flex;align-items:center;gap:6px;font-size:0.74rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--text2);margin-bottom:6px">
      <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
      CPM Earnings
    </div>
    <div class="stat-value" style="color:var(--accent);font-size:1.35rem;font-weight:800;letter-spacing:-.02em"><?= e(fh_format_money($cpm_earnings, $ws_currency)) ?></div>
    <div class="text-xs text-muted" style="margin-top:4px">
      Rate: <?= $is_creator ? fh_format_money(setting('creator_cpm', '1.00'), 'USD') : fh_format_money(setting('viewer_cpm', '0.50'), 'USD') ?> / 1K Imps
    </div>
  </div>

  <!-- Card 4: CPC Earnings -->
  <div class="stat-card" style="background:linear-gradient(135deg,rgba(99,102,241,.04),transparent);position:relative;overflow:hidden">
    <div style="position:absolute;top:-5px;right:-5px;opacity:.03;font-size:3rem;transform:rotate(-15deg)">📈</div>
    <div class="stat-label" style="display:flex;align-items:center;gap:6px;font-size:0.74rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--text2);margin-bottom:6px">
      <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M23 6l-9.5 9.5-5-5L1 18M17 6h6v6"/></svg>
      CPC Earnings
    </div>
    <div class="stat-value" style="color:var(--accent);font-size:1.35rem;font-weight:800;letter-spacing:-.02em"><?= e(fh_format_money($cpc_earnings, $ws_currency)) ?></div>
    <div class="text-xs text-muted" style="margin-top:4px">
      Rate: <?= $is_creator ? fh_format_money(setting('creator_cpc', '5.00'), 'USD') : fh_format_money(setting('viewer_cpc', '2.00'), 'USD') ?> / 1K Clicks
    </div>
  </div>

  <!-- Card 5: Total Earnings -->
  <div class="stat-card" style="background:linear-gradient(135deg,rgba(34,197,94,.06),transparent);border-color:rgba(34,197,94,.22);position:relative;overflow:hidden">
    <div style="position:absolute;top:-5px;right:-5px;opacity:.05;font-size:3rem;transform:rotate(15deg)">💰</div>
    <div class="stat-label" style="display:flex;align-items:center;gap:6px;font-size:0.74rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--text2);margin-bottom:6px">
      <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M9 12h6"/></svg>
      Total Ad Earnings
    </div>
    <div class="stat-value" style="color:var(--green);font-size:1.35rem;font-weight:800;letter-spacing:-.02em"><?= e(fh_format_money($total_earnings, $ws_currency)) ?></div>
    <div class="text-xs text-muted" style="margin-top:4px">Impressions + Clicks</div>
  </div>

</div>
