<?php
// Admin — Site Settings (Enhanced)
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? '')) {
    // AJAX Save handler for Ad Code settings
    if (!empty($_POST['ajax_save_adcode'])) {
        $ad_fields = [
            'ad_code_header', 'ad_code_header_enabled',
            'ad_code_body', 'ad_code_body_enabled', 'ad_code_body_placement',
            'ad_code_footer', 'ad_code_footer_enabled'
        ];
        foreach ($ad_fields as $key) {
            $val = $_POST[$key] ?? '';
            if (str_ends_with($key, '_enabled') && empty($val)) $val = '0';
            db_query("INSERT INTO settings (`key`,`value`,`group`) VALUES (?,?,'content') ON DUPLICATE KEY UPDATE `value`=?", [$key, $val, $val]);
        }
        fh_cache_delete('fh_settings_cache');
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Ad Code settings saved instantly!']);
        exit;
    }

    $fields = [
        'site_name','site_tagline','active_theme','primary_color',
        'reels_enabled',
        'creator_cpm','creator_cpc',
        'viewer_cpm','viewer_cpc',
        'min_withdrawal_creator','min_withdrawal_viewer',
        'min_withdrawal','min_payout',
        'withdrawal_days','withdrawal_approval_mode',
        'referral_bonus_usd',
        'ad_revenue_per_click','currency_rates_json',
        'allow_register','maintenance','maintenance_message',
        'video_approval_mode',
        'user_approval_mode',
        'creator_approval_mode',
        'smtp_host','smtp_port','smtp_user','smtp_from_email','smtp_from_name','smtp_encryption',
        'site_logo',
        'adult_mode',
        'ad_code_header', 'ad_code_header_enabled',
        'ad_code_body', 'ad_code_body_enabled', 'ad_code_body_placement',
        'ad_code_footer', 'ad_code_footer_enabled',
        'viewer_eligible_placements',
        'creator_eligible_placements'
    ];
    $_POST['min_withdrawal'] = $_POST['min_withdrawal_viewer'] ?? '25.00';
    $_POST['min_payout'] = $_POST['min_withdrawal_viewer'] ?? '25.00';

    // Handle SMTP password separately (only update if not blank)
    foreach ($fields as $key) {
        $val = $_POST[$key] ?? '';
        if (is_array($val)) {
            $val = implode(',', $val);
        } else {
            $val = str_contains($key, 'ad_code') ? $val : trim($val);
        }
        $group = 'general';
        if (str_contains($key, 'rate') || str_contains($key, 'withdrawal') || str_contains($key, 'payout') || str_contains($key, 'revenue') || str_contains($key, 'bonus') || str_contains($key, 'earn') || str_contains($key, 'eligible')) $group = 'earnings';
        if (str_contains($key, 'smtp')) $group = 'email';
        if ($key === 'video_approval_mode' || $key === 'user_approval_mode' || $key === 'creator_approval_mode' || str_contains($key, 'ad_code')) $group = 'content';
        if ($key === 'adult_mode') $group = 'popup';
        db_query("INSERT INTO settings (`key`,`value`,`group`) VALUES (?,?,?) ON DUPLICATE KEY UPDATE `value`=?", [$key, $val, $group, $val]);
    }
    // SMTP password — only update if provided
    if (!empty($_POST['smtp_pass'])) {
        $val = trim($_POST['smtp_pass']);
        db_query("INSERT INTO settings (`key`,`value`,`group`) VALUES ('smtp_pass',?,'email') ON DUPLICATE KEY UPDATE `value`=?", [$val, $val]);
    }

    // Bust setting cache if accessible
    fh_cache_delete('fh_settings_cache');
    flash('success', 'Settings saved!');
    redirect(BASE_URL . '/admin/settings.php');
}

$meta_title = 'Site Settings';
require_once __DIR__ . '/partials/admin_head.php';
$themes = [
  'light-white'  => 'Light White',
  'dark-minimal' => 'Minima'
];
?>
<div class="admin-content">
  <?php foreach (get_flash() as $f): ?><div class="alert alert-<?= $f['type'] ?>"><?= e($f['msg']) ?></div><?php endforeach; ?>
  
  <div class="settings-layout-grid">
    <!-- Secondary Sidebar -->
    <div class="card" style="padding:16px;display:flex;flex-direction:column;gap:8px" id="settings-sidebar">
      <button class="btn btn-outline" style="justify-content:flex-start;border:none;background:rgba(99,102,241,.1);color:var(--accent)" onclick="showSlice('slice-general', this)">⚙️ General</button>
      <button class="btn btn-outline" style="justify-content:flex-start;border:none" onclick="showSlice('slice-appearance', this)">🎨 Appearance</button>
      <button class="btn btn-outline" style="justify-content:flex-start;border:none" onclick="showSlice('slice-approval', this)">🛡️ Approval Settings</button>
      <button class="btn btn-outline" style="justify-content:flex-start;border:none" onclick="showSlice('slice-adcode', this)">📢 Ad Code</button>
      <button class="btn btn-outline" style="justify-content:flex-start;border:none" onclick="showSlice('slice-popup', this)">🔞 First Popup</button>
      <button class="btn btn-outline" style="justify-content:flex-start;border:none" onclick="showSlice('slice-email', this)">✉️ Email / SMTP</button>
    </div>

    <!-- Main Content -->
    <form method="POST" enctype="multipart/form-data" novalidate>
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
          <label class="form-label">Maintenance Mode</label>
          <select class="form-input form-select" name="maintenance" id="maintenance-toggle">
            <option value="0" <?= setting('maintenance','0')==='0'?'selected':'' ?>>Off</option>
            <option value="1" <?= setting('maintenance','0')==='1'?'selected':'' ?>>On</option>
          </select>
        </div>
        <div class="form-group" id="maintenance-msg-group" style="<?= setting('maintenance','0')==='0'?'display:none':'' ?>">
          <label class="form-label">Maintenance Message <small class="text-muted">(shown to visitors when maintenance is on)</small></label>
          <textarea class="form-input" name="maintenance_message" rows="4" placeholder="Leave blank to use the default message..." style="resize:vertical;line-height:1.6"><?= e(setting('maintenance_message','')) ?></textarea>
          <small class="text-muted text-xs" style="display:block;margin-top:4px">If left empty, a default message will be displayed. You can write any custom message you want visitors to see.</small>
        </div>
        <script>document.getElementById('maintenance-toggle').addEventListener('change',function(){document.getElementById('maintenance-msg-group').style.display=this.value==='1'?'':'none';});</script>
        <div class="form-group">
          <label class="form-label">Enable Reels Feature</label>
          <select class="form-input form-select" name="reels_enabled">
            <option value="1" <?= setting('reels_enabled','1')==='1'?'selected':'' ?>>Yes (Enabled)</option>
            <option value="0" <?= setting('reels_enabled','1')==='0'?'selected':'' ?>>No (Disabled)</option>
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

      <!-- Monetization slice removed -->

      <!-- Approval Settings Slice -->
      <div id="slice-approval" class="slice-section" style="display:none">
      <div class="card">
        <h3 style="font-weight:700;margin-bottom:20px">Approval Settings</h3>
        
        <!-- User Approval Mode -->
        <div class="form-group">
          <label class="form-label">User / Viewer Approval Mode</label>
          <select class="form-input form-select" name="user_approval_mode">
            <option value="auto"   <?= setting('user_approval_mode','auto')==='auto'?'selected':'' ?>>⚡ Auto Approval — New viewers gain access instantly</option>
            <option value="manual" <?= setting('user_approval_mode','auto')==='manual'?'selected':'' ?>>🔍 Manual Approval — Admin must approve new viewers</option>
          </select>
          <small class="text-muted text-xs" style="display:block;margin-top:4px">In manual mode, viewers remain pending and cannot access dashboard pages or earn rewards until approved.</small>
        </div>
        
        <!-- Creator Approval Mode -->
        <div class="form-group" style="margin-top:20px">
          <label class="form-label">Creator Approval Mode</label>
          <select class="form-input form-select" name="creator_approval_mode">
            <option value="manual" <?= setting('creator_approval_mode','manual')==='manual'?'selected':'' ?>>🔍 Manual Approval — Admin must approve new creators</option>
            <option value="auto"   <?= setting('creator_approval_mode','manual')==='auto'?'selected':'' ?>>⚡ Auto Approval — New creators gain access instantly</option>
          </select>
          <small class="text-muted text-xs" style="display:block;margin-top:4px">In manual mode, creators remain pending and cannot access creator tools or upload videos until approved.</small>
        </div>
        
        <!-- Video Approval Mode -->
        <div class="form-group" style="margin-top:20px">
          <label class="form-label">Video Approval Mode</label>
          <select class="form-input form-select" name="video_approval_mode">
            <option value="manual" <?= setting('video_approval_mode','manual')==='manual'?'selected':'' ?>>🔍 Manual Approval — Admin reviews each video</option>
            <option value="auto"   <?= setting('video_approval_mode','manual')==='auto'?'selected':'' ?>>⚡ Auto Approval — Videos publish immediately</option>
          </select>
          <small class="text-muted text-xs" style="display:block;margin-top:4px">In manual mode, uploaded videos are queued for admin approval before going live.</small>
        </div>
      </div>
      </div>

      <!-- First Popup Slice -->
      <div id="slice-popup" class="slice-section" style="display:none">
      <div class="card">
        <h3 style="font-weight:700;margin-bottom:20px">First Popup Settings</h3>
        <p class="text-sm text-muted" style="margin-bottom:20px;line-height:1.6">
          Configure popups that appear before visitors can access the website.
          When enabled, the selected popup will block all site content until the user interacts with it.
        </p>

        <!-- Adult Mode Toggle -->
        <div class="form-group">
          <label class="form-label">🔞 Adult Site Mode (18+ Age Verification)</label>
          <select class="form-input form-select" name="adult_mode">
            <option value="0" <?= setting('adult_mode','0')==='0'?'selected':'' ?>>🚫 Disabled — No age verification popup</option>
            <option value="1" <?= setting('adult_mode','0')==='1'?'selected':'' ?>>✅ Enabled — Age verification popup is mandatory</option>
          </select>
          <small class="text-muted text-xs" style="display:block;margin-top:6px;line-height:1.5">
            When enabled, all visitors must confirm they are 18+ before accessing any page on the website.
            The verification is stored in the browser session and does not reappear until the session expires.
            Admin users are exempt from the popup.
          </small>
        </div>

        <div style="margin-top:20px;padding:16px;border-radius:8px;background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.15)">
          <div style="font-weight:700;font-size:.88rem;margin-bottom:6px;color:var(--red)">⚠️ Important</div>
          <p class="text-sm text-muted" style="line-height:1.6">
            Enabling this mode confirms your platform hosts age-restricted content.
            The popup blocks ALL site content (including public pages) until the visitor verifies their age.
            Users who decline are shown a blocked-access screen and cannot browse the site.
          </p>
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

      <!-- Ad Code Slice -->
      <div id="slice-adcode" class="slice-section" style="display:none">
      <div class="card">
        <h3 style="font-weight:700;margin-bottom:6px">📢 Ad Code Management</h3>
        <p class="text-sm text-muted" style="margin-bottom:20px;line-height:1.6">
          Insert custom HTML, CSS, JavaScript, tracking codes, or advertisement pixels into different areas of the website.
        </p>

        <!-- Header Code Area -->
        <div style="margin-top:20px; padding:16px; border-radius:8px; background:var(--bg3); border:1px solid var(--border);">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
            <div style="font-weight:700;font-size:.9rem;color:var(--text)">1. Header Code Area (&lt;head&gt;)</div>
            <select class="form-input form-select" name="ad_code_header_enabled" style="width:140px; height:34px; padding:4px 8px; font-size:.8rem;">
              <option value="1" <?= setting('ad_code_header_enabled','0')==='1'?'selected':'' ?>>✅ Enabled</option>
              <option value="0" <?= setting('ad_code_header_enabled','0')==='0'?'selected':'' ?>>🚫 Disabled</option>
            </select>
          </div>
          <p class="text-xs text-muted" style="margin-bottom:10px; line-height:1.4">Loads directly inside the HTML <code>&lt;head&gt;</code> section. Ideal for Google Analytics, verification meta tags, and pixel trackers.</p>
          <div class="form-group" style="margin-bottom:0">
            <textarea class="form-input adcode-editor-textarea" name="ad_code_header" id="ad-code-header-ta" rows="8" style="font-family:monospace; font-size:.8rem; resize:vertical; background:#121212; color:#32ff32; border-color:#2a2a2a; line-height:1.5; width:100%;" placeholder="<!-- Paste your head scripts here -->"><?= e(setting('ad_code_header','')) ?></textarea>
            <div id="ad-code-header-ace" class="adcode-ace-editor" style="display:none; height:200px; border:1px solid var(--border); border-radius:4px; font-size:12px;"></div>
          </div>
          <div style="display:flex; justify-content:flex-end; margin-top:8px">
            <button type="button" class="btn btn-sm btn-outline" style="font-size:0.75rem; padding:4px 12px; height:30px;" onclick="instantSaveAdcode('header', this)">⚡ Save Header Code</button>
          </div>
        </div>

        <!-- Body Code Area -->
        <div style="margin-top:20px; padding:16px; border-radius:8px; background:var(--bg3); border:1px solid var(--border);">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
            <div style="font-weight:700;font-size:.9rem;color:var(--text)">2. Body Code Area</div>
            <div class="flex gap-2" style="flex-wrap:wrap;">
              <select class="form-input form-select" name="ad_code_body_placement" style="width:140px; height:34px; padding:4px 8px; font-size:.8rem;" title="Placement location inside body">
                <option value="top" <?= setting('ad_code_body_placement','bottom')==='top'?'selected':'' ?>>Top (Start of Body)</option>
                <option value="bottom" <?= setting('ad_code_body_placement','bottom')==='bottom'?'selected':'' ?>>Bottom (End of Body)</option>
              </select>
              <select class="form-input form-select" name="ad_code_body_enabled" style="width:140px; height:34px; padding:4px 8px; font-size:.8rem;">
                <option value="1" <?= setting('ad_code_body_enabled','0')==='1'?'selected':'' ?>>✅ Enabled</option>
                <option value="0" <?= setting('ad_code_body_enabled','0')==='0'?'selected':'' ?>>🚫 Disabled</option>
              </select>
            </div>
          </div>
          <p class="text-xs text-muted" style="margin-bottom:10px; line-height:1.4">Loads inside the main body container, either immediately after the opening <code>&lt;body&gt;</code> tag or right before the closing <code>&lt;/body&gt;</code> tag.</p>
          <div class="form-group" style="margin-bottom:0">
            <textarea class="form-input adcode-editor-textarea" name="ad_code_body" id="ad-code-body-ta" rows="8" style="font-family:monospace; font-size:.8rem; resize:vertical; background:#121212; color:#32ff32; border-color:#2a2a2a; line-height:1.5; width:100%;" placeholder="<!-- Paste your body scripts here -->"><?= e(setting('ad_code_body','')) ?></textarea>
            <div id="ad-code-body-ace" class="adcode-ace-editor" style="display:none; height:200px; border:1px solid var(--border); border-radius:4px; font-size:12px;"></div>
          </div>
          <div style="display:flex; justify-content:flex-end; margin-top:8px">
            <button type="button" class="btn btn-sm btn-outline" style="font-size:0.75rem; padding:4px 12px; height:30px;" onclick="instantSaveAdcode('body', this)">⚡ Save Body Code</button>
          </div>
        </div>

        <!-- Footer Code Area -->
        <div style="margin-top:20px; padding:16px; border-radius:8px; background:var(--bg3); border:1px solid var(--border);">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
            <div style="font-weight:700;font-size:.9rem;color:var(--text)">3. Footer Code Area</div>
            <select class="form-input form-select" name="ad_code_footer_enabled" style="width:140px; height:34px; padding:4px 8px; font-size:.8rem;">
              <option value="1" <?= setting('ad_code_footer_enabled','0')==='1'?'selected':'' ?>>✅ Enabled</option>
              <option value="0" <?= setting('ad_code_footer_enabled','0')==='0'?'selected':'' ?>>🚫 Disabled</option>
            </select>
          </div>
          <p class="text-xs text-muted" style="margin-bottom:10px; line-height:1.4">Loads inside the website footer wrapper, perfect for embedding copyright scripts, bottom widgets, or ad unit codes.</p>
          <div class="form-group" style="margin-bottom:0">
            <textarea class="form-input adcode-editor-textarea" name="ad_code_footer" id="ad-code-footer-ta" rows="8" style="font-family:monospace; font-size:.8rem; resize:vertical; background:#121212; color:#32ff32; border-color:#2a2a2a; line-height:1.5; width:100%;" placeholder="<!-- Paste your footer scripts here -->"><?= e(setting('ad_code_footer','')) ?></textarea>
            <div id="ad-code-footer-ace" class="adcode-ace-editor" style="display:none; height:200px; border:1px solid var(--border); border-radius:4px; font-size:12px;"></div>
          </div>
          <div style="display:flex; justify-content:flex-end; margin-top:8px">
            <button type="button" class="btn btn-sm btn-outline" style="font-size:0.75rem; padding:4px 12px; height:30px;" onclick="instantSaveAdcode('footer', this)">⚡ Save Footer Code</button>
          </div>
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.7/ace.js"></script>
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
  
  // Resize Ace editors on tab switch
  if (id === 'slice-adcode' && typeof ace !== 'undefined') {
    Object.values(aceInstances).forEach(editor => editor.resize());
  }
}

const aceInstances = {};

function initAceEditor(textareaId, editorDivId) {
  const ta = document.getElementById(textareaId);
  const div = document.getElementById(editorDivId);
  if (!ta || !div) return;

  // Hide textarea, show div
  ta.style.display = 'none';
  div.style.display = 'block';

  // Initialize Ace
  const editor = ace.edit(editorDivId);
  editor.setTheme("ace/theme/tomorrow_night");
  editor.session.setMode("ace/mode/html");
  editor.setValue(ta.value, -1);

  // Sync Ace changes to textarea
  editor.session.on('change', function() {
    ta.value = editor.getValue();
  });

  // Save reference
  aceInstances[textareaId] = editor;
}

function instantSaveAdcode(area, btn) {
  if (!btn) {
    btn = window.event ? (window.event.currentTarget || window.event.srcElement) : null;
  }
  const csrf = document.querySelector('input[name="csrf"]').value;
  const formData = new FormData();
  formData.append('csrf', csrf);
  formData.append('ajax_save_adcode', '1');

  // Append values
  formData.append('ad_code_header', document.getElementById('ad-code-header-ta').value);
  formData.append('ad_code_header_enabled', document.querySelector('select[name="ad_code_header_enabled"]').value);
  formData.append('ad_code_body', document.getElementById('ad-code-body-ta').value);
  formData.append('ad_code_body_enabled', document.querySelector('select[name="ad_code_body_enabled"]').value);
  formData.append('ad_code_body_placement', document.querySelector('select[name="ad_code_body_placement"]').value);
  formData.append('ad_code_footer', document.getElementById('ad-code-footer-ta').value);
  formData.append('ad_code_footer_enabled', document.querySelector('select[name="ad_code_footer_enabled"]').value);

  // Show status inside button
  const originalText = btn ? btn.textContent : '';
  if (btn) {
    btn.disabled = true;
    btn.textContent = '⏳ Saving...';
  }

  fetch('', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (btn) {
      btn.textContent = '✅ Saved!';
      setTimeout(() => {
        btn.disabled = false;
        btn.textContent = originalText;
      }, 2000);
    }
  })
  .catch(err => {
    console.error(err);
    if (btn) {
      btn.textContent = '✗ Error';
      setTimeout(() => {
        btn.disabled = false;
        btn.textContent = originalText;
      }, 2000);
    }
  });
}

document.addEventListener("DOMContentLoaded", function() {
  // Check if Ace Editor loaded successfully
  if (typeof ace !== 'undefined') {
    initAceEditor('ad-code-header-ta', 'ad-code-header-ace');
    initAceEditor('ad-code-body-ta', 'ad-code-body-ace');
    initAceEditor('ad-code-footer-ta', 'ad-code-footer-ace');
  }

  // Router check for tab=adcode
  const urlParams = new URLSearchParams(window.location.search);
  const tab = urlParams.get('tab');
  if (tab === 'adcode') {
    const btn = document.querySelector('[onclick*="slice-adcode"]');
    if (btn) btn.click();
  }
});
</script>
<?php require_once __DIR__ . '/partials/admin_foot.php'; ?>
