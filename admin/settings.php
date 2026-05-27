<?php
// Admin — Site Settings (Enhanced)
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? '')) {
    $fields = [
        'site_name','site_tagline','active_theme','primary_color',
        'viewer_rate_usd','creator_rate_usd','watch_time_rate_usd',
        'min_withdrawal','min_payout',
        'ad_revenue_per_click','currency_rates_json',
        'allow_register','maintenance',
        'video_approval_mode',
        'referral_bonus_usd',
        'smtp_host','smtp_port','smtp_user','smtp_from_email','smtp_from_name','smtp_encryption',
        'site_logo',
    ];
    // Sync rates so old code still works
    $_POST['watch_time_rate_usd'] = $_POST['viewer_rate_usd'] ?? $_POST['watch_time_rate_usd'] ?? '0.50';
    $_POST['min_payout'] = $_POST['min_withdrawal'] ?? $_POST['min_payout'] ?? '25.00';

    // Handle SMTP password separately (only update if not blank)
    foreach ($fields as $key) {
        $val = trim($_POST[$key] ?? '');
        $group = 'general';
        if (str_contains($key, 'rate') || str_contains($key, 'withdrawal') || str_contains($key, 'payout') || str_contains($key, 'revenue') || str_contains($key, 'bonus')) $group = 'earnings';
        if (str_contains($key, 'smtp')) $group = 'email';
        if ($key === 'video_approval_mode') $group = 'content';
        db_query("INSERT INTO settings (`key`,`value`,`group`) VALUES (?,?,?) ON DUPLICATE KEY UPDATE `value`=?", [$key, $val, $group, $val]);
    }
    // SMTP password — only update if provided
    if (!empty($_POST['smtp_pass'])) {
        $val = trim($_POST['smtp_pass']);
        db_query("INSERT INTO settings (`key`,`value`,`group`) VALUES ('smtp_pass',?,'email') ON DUPLICATE KEY UPDATE `value`=?", [$val, $val]);
    }

    // Bust setting cache if accessible
    // cache_delete('settings');
    flash('success', 'Settings saved!');
    redirect(BASE_URL . '/admin/settings.php');
}

$meta_title = 'Site Settings';
require_once __DIR__ . '/partials/admin_head.php';
$themes = [
  'light-white'  => 'Light White',
  'dark-minimal' => 'Minima',
  'gray'         => 'Gray',
  'light-blue'   => 'Light Blue',
  'pink'         => 'Pink',
  'red-black'    => 'Red Black',
  'green'        => 'Green',
  'light-green'  => 'Light Green'
];
?>
<div class="admin-content">
  <?php foreach (get_flash() as $f): ?><div class="alert alert-<?= $f['type'] ?>"><?= e($f['msg']) ?></div><?php endforeach; ?>
  
  <div class="settings-layout-grid">
    <!-- Secondary Sidebar -->
    <div class="card" style="padding:16px;display:flex;flex-direction:column;gap:8px" id="settings-sidebar">
      <button class="btn btn-outline" style="justify-content:flex-start;border:none;background:rgba(99,102,241,.1);color:var(--accent)" onclick="showSlice('slice-general', this)">⚙️ General</button>
      <button class="btn btn-outline" style="justify-content:flex-start;border:none" onclick="showSlice('slice-appearance', this)">🎨 Appearance</button>
      <button class="btn btn-outline" style="justify-content:flex-start;border:none" onclick="showSlice('slice-monetization', this)">💰 Monetization</button>
      <button class="btn btn-outline" style="justify-content:flex-start;border:none" onclick="showSlice('slice-approval', this)">🛡️ Video Approval</button>
      <button class="btn btn-outline" style="justify-content:flex-start;border:none" onclick="showSlice('slice-email', this)">✉️ Email / SMTP</button>
    </div>

    <!-- Main Content -->
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      
      <!-- General Slice -->
      <div id="slice-general" class="slice-section">

      <!-- General -->
      <div class="card">
        <h3 style="font-weight:700;margin-bottom:16px">General</h3>
        <div class="form-group">
          <label class="form-label">Site Name</label>
          <input class="form-input" type="text" name="site_name" value="<?= e(setting('site_name','FreeHub')) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Tagline</label>
          <input class="form-input" type="text" name="site_tagline" value="<?= e(setting('site_tagline','Watch. Share. Earn.')) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Allow New Registrations</label>
          <select class="form-input form-select" name="allow_register">
            <option value="1" <?= setting('allow_register','1')==='1'?'selected':'' ?>>Yes</option>
            <option value="0" <?= setting('allow_register','1')==='0'?'selected':'' ?>>No</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Maintenance Mode</label>
          <select class="form-input form-select" name="maintenance">
            <option value="0" <?= setting('maintenance','0')==='0'?'selected':'' ?>>Off</option>
            <option value="1" <?= setting('maintenance','0')==='1'?'selected':'' ?>>On</option>
          </select>
        </div>
      </div>
      </div>

      <!-- Appearance Slice -->
      <div id="slice-appearance" class="slice-section" style="display:none">
      <div class="card">
        <h3 style="font-weight:700;margin-bottom:16px">Appearance</h3>
        <div class="form-group">
          <label class="form-label">Default Theme</label>
          <select class="form-input form-select" name="active_theme">
            <?php foreach ($themes as $val => $label): ?>
            <option value="<?= $val ?>" <?= setting('active_theme','dark-minimal')===$val?'selected':'' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Accent Color</label>
          <input type="color" name="primary_color" value="<?= e(setting('primary_color','#6366f1')) ?>" style="width:100%;height:40px;border:none;cursor:pointer">
        </div>
      </div>
      </div>

      <!-- Monetization Slice -->
      <div id="slice-monetization" class="slice-section" style="display:none">
      <div class="card">
        <h3 style="font-weight:700;margin-bottom:6px">Earnings & Payouts</h3>
        <p class="text-sm text-muted" style="margin-bottom:16px">
          Configure separate earning rates for viewers (Watch &amp; Earn users) and creators.
          <strong>Admin cannot earn</strong> — earnings are only for viewers and creators.
        </p>
        <div class="stat-grid-2">
          <div class="form-group">
            <label class="form-label">👁️ Viewer Rate (USD/hour watched)</label>
            <input class="form-input" type="number" name="viewer_rate_usd" step="0.001" min="0"
                   value="<?= e(setting('viewer_rate_usd', setting('watch_time_rate_usd','0.50'))) ?>">
            <small class="text-muted text-xs">Amount Watch &amp; Earn users earn per hour of watch time</small>
          </div>
          <div class="form-group">
            <label class="form-label">🎬 Creator Rate (USD/hour of their videos watched)</label>
            <input class="form-input" type="number" name="creator_rate_usd" step="0.001" min="0"
                   value="<?= e(setting('creator_rate_usd', setting('watch_time_rate_usd','0.50'))) ?>">
            <small class="text-muted text-xs">Amount creators earn per hour their videos are watched</small>
          </div>
          <div class="form-group">
            <label class="form-label">Minimum Withdrawal for Creators (USD)</label>
            <input class="form-input" type="number" name="min_withdrawal_creator" step="0.01" min="0"
                   value="<?= e(setting('min_withdrawal_creator', setting('min_withdrawal','25.00'))) ?>">
            <small class="text-muted text-xs">Set 0 for instant/no minimum</small>
          </div>
          <div class="form-group">
            <label class="form-label">Minimum Withdrawal for Viewers (USD)</label>
            <input class="form-input" type="number" name="min_withdrawal_viewer" step="0.01" min="0"
                   value="<?= e(setting('min_withdrawal_viewer', setting('min_withdrawal','25.00'))) ?>">
            <small class="text-muted text-xs">Set 0 for instant/no minimum</small>
          </div>
          <div class="form-group">
            <label class="form-label">Withdrawal Processing Days</label>
            <input class="form-input" type="number" name="withdrawal_days" step="1" min="0"
                   value="<?= e(setting('withdrawal_days','7')) ?>">
            <small class="text-muted text-xs">Number of days users must wait for withdrawal processing (0 for instant)</small>
          </div>
          <div class="form-group">
            <label class="form-label">Withdrawal Approval Mode</label>
            <select class="form-input form-select" name="withdrawal_approval_mode">
              <option value="manual" <?= setting('withdrawal_approval_mode','manual')==='manual'?'selected':'' ?>>🔍 Manual — Admin approves each request</option>
              <option value="auto"   <?= setting('withdrawal_approval_mode','manual')==='auto'?'selected':'' ?>>⚡ Auto — Requests process automatically</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Referral Bonus per Signup (USD, 0 = disabled)</label>
            <input class="form-input" type="number" name="referral_bonus_usd" step="0.01" min="0"
                   value="<?= e(setting('referral_bonus_usd','0.00')) ?>">
            <small class="text-muted text-xs">Bonus paid to referrer when a new user signs up via their link</small>
          </div>
          <div class="form-group">
            <label class="form-label">Referral Video Watch Rate (USD/hour)</label>
            <input class="form-input" type="number" name="referral_watch_rate_usd" step="0.001" min="0"
                   value="<?= e(setting('referral_watch_rate_usd','0.10')) ?>">
            <small class="text-muted text-xs">Bonus paid to referrer when their referred users watch videos</small>
          </div>
          <div class="form-group">
            <label class="form-label">Admin Ad Revenue per Click (USD)</label>
            <input class="form-input" type="number" name="ad_revenue_per_click" step="0.001" min="0"
                   value="<?= e(setting('ad_revenue_per_click','0.05')) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Currency Rates JSON (vs USD)</label>
            <textarea class="form-input" name="currency_rates_json" rows="3" style="font-family:monospace;font-size:.8rem"><?= e(setting('currency_rates_json','')) ?></textarea>
          </div>
        </div>
      </div>
      </div>

      <!-- Video Approval Slice -->
      <div id="slice-approval" class="slice-section" style="display:none">
      <div class="card">
        <h3 style="font-weight:700;margin-bottom:16px">Video Approval</h3>
        <div class="form-group">
          <label class="form-label">Approval Mode</label>
          <select class="form-input form-select" name="video_approval_mode">
            <option value="manual" <?= setting('video_approval_mode','manual')==='manual'?'selected':'' ?>>🔍 Manual — Admin reviews each video</option>
            <option value="auto"   <?= setting('video_approval_mode','manual')==='auto'?'selected':'' ?>>⚡ Auto — Videos publish immediately</option>
          </select>
          <small class="text-muted text-xs">In manual mode, uploaded videos are queued for admin approval before going live.</small>
        </div>
      </div>
      </div>

      <!-- Email Slice -->
      <div id="slice-email" class="slice-section" style="display:none">
      <div class="card">
        <h3 style="font-weight:700;margin-bottom:16px">Email / SMTP Settings</h3>
        <p class="text-sm text-muted" style="margin-bottom:12px">Used for password reset emails. Leave empty to use PHP's built-in mail().</p>
        <div class="form-group">
          <label class="form-label">SMTP Host</label>
          <input class="form-input" type="text" name="smtp_host" placeholder="smtp.gmail.com" value="<?= e(setting('smtp_host','')) ?>">
        </div>
        <div class="stat-grid-2">
          <div class="form-group">
            <label class="form-label">SMTP Port</label>
            <input class="form-input" type="number" name="smtp_port" value="<?= e(setting('smtp_port','587')) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Encryption</label>
            <select class="form-input form-select" name="smtp_encryption">
              <option value="tls" <?= setting('smtp_encryption','tls')==='tls'?'selected':'' ?>>TLS (STARTTLS)</option>
              <option value="ssl" <?= setting('smtp_encryption','tls')==='ssl'?'selected':'' ?>>SSL</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">SMTP Username (Email)</label>
          <input class="form-input" type="email" name="smtp_user" placeholder="noreply@yourdomain.com" value="<?= e(setting('smtp_user','')) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">SMTP Password <small class="text-muted">(leave blank to keep existing)</small></label>
          <input class="form-input" type="password" name="smtp_pass" placeholder="Leave blank to keep current password">
        </div>
        <div class="form-group">
          <label class="form-label">From Email</label>
          <input class="form-input" type="email" name="smtp_from_email" placeholder="noreply@yourdomain.com" value="<?= e(setting('smtp_from_email','')) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">From Name</label>
          <input class="form-input" type="text" name="smtp_from_name" placeholder="FreeHub" value="<?= e(setting('smtp_from_name', setting('site_name','FreeHub'))) ?>">
        </div>
      </div>

      </div>

      <!-- Global Save Button -->
      <div class="card" style="margin-top:24px;display:flex;justify-content:flex-end;gap:12px;background:var(--bg2)">
        <a href="<?= BASE_URL ?>/admin/" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary" style="padding:12px 32px">Save All Settings</button>
      </div>

    </form>
  </div>
</div>

<script>
function showSlice(id, btn) {
  // Hide all slices
  document.querySelectorAll('.slice-section').forEach(el => el.style.display = 'none');
  // Reset all buttons
  document.querySelectorAll('#settings-sidebar button').forEach(b => {
    b.style.background = 'transparent';
    b.style.color = 'var(--text)';
  });
  // Show target slice
  document.getElementById(id).style.display = 'block';
  // Highlight active button
  btn.style.background = 'rgba(99,102,241,.1)';
  btn.style.color = 'var(--accent)';
}
</script>
<?php require_once __DIR__ . '/partials/admin_foot.php'; ?>
