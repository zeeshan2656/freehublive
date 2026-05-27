<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
if (is_logged_in()) redirect(BASE_URL . '/');
$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) { $error = 'Invalid request.'; }
    elseif (!rate_limit('register_' . get_ip(), 5, 300)) { $error = 'Too many registrations. Try later.'; }
    else {
        $username     = trim($_POST['username'] ?? '');
        $email        = trim($_POST['email'] ?? '');
        $first_name   = trim($_POST['first_name'] ?? '');
        $last_name    = trim($_POST['last_name'] ?? '');
        $pass         = $_POST['password'] ?? '';
        $country_code = trim($_POST['country_code'] ?? '');
        $phone_raw    = trim($_POST['phone'] ?? '');
        $phone        = $country_code . $phone_raw;
        $role     = in_array($_POST['role'] ?? '', ['viewer','creator']) ? $_POST['role'] : 'viewer';
        if (strlen($username) < 3 || strlen($username) > 30) $error = 'Username must be 3-30 chars.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Invalid email.';
        elseif (empty($phone_raw) || !preg_match('/^[0-9]{6,15}$/', $phone_raw)) $error = 'Enter a valid phone number (digits only, 6-15 digits).';
        elseif (empty($country_code)) $error = 'Select your country code.';
        elseif (strlen($pass) < 8) $error = 'Password min 8 chars.';
        elseif ($_POST['pass2'] !== $pass) $error = 'Passwords do not match.';
        elseif (db_fetch("SELECT id FROM users WHERE username=?", [$username])) $error = 'Username taken.';
        elseif (db_fetch("SELECT id FROM users WHERE email=?", [$email]))    $error = 'Email already registered.';
        else {
            $ref_code = generate_ref_code();
                while (db_fetch("SELECT id FROM users WHERE ref_code=?", [$ref_code])) $ref_code = generate_ref_code();
                $ref_by = null;
                if (!empty($_COOKIE['fh_ref'])) {
                    $refUser = db_fetch("SELECT id FROM users WHERE ref_code=?", [$_COOKIE['fh_ref']]);
                    $ref_by = $refUser['id'] ?? null;
                }
                $id = db_insert('users', [
                    'username'           => $username,
                    'email'              => $email,
                    'first_name'         => $first_name,
                    'last_name'          => $last_name,
                    'phone'              => $phone,
                    'password'           => hash_password($pass),
                    'role'               => $role,
                    'status'             => $role === 'creator' ? 'pending' : 'active',
                    'ref_code'           => $ref_code,
                    'referred_by'        => $ref_by,
                    'channel_name'       => $username,
                    'preferred_currency' => 'USD',
                ]);
                ensure_user_channel((int)$id, $username);
                // Track referral conversion
                if ($ref_by && fh_table_exists('referral_conversions')) {
                    try {
                        $refUser2 = db_fetch("SELECT ref_code FROM users WHERE id=?", [$ref_by]);
                        db_insert('referral_conversions', [
                            'referrer_id'      => $ref_by,
                            'referred_user_id' => $id,
                            'ref_code'         => $refUser2['ref_code'] ?? '',
                            'bonus_paid'       => 0,
                        ]);
                        // Pay referral bonus if configured
                        $bonus = (float)setting('referral_bonus_usd', '0.00');
                        if ($bonus > 0) {
                            require_once __DIR__ . '/../includes/earnings.php';
                            fh_credit_user($bonus, $ref_by, "Referral bonus: new user #{$id}");
                            db_update('referral_conversions', ['bonus_paid' => 1], 'referrer_id=? AND referred_user_id=?', [$ref_by, $id]);
                        }
                    } catch (Throwable $e) { /* ignore duplicate */ }
                }
                flash('success', $role === 'creator' ? 'Account created! Admin will review your creator application. Please sign in.' : 'Account created successfully! Please sign in.');
                redirect(BASE_URL . '/auth/login.php');
        }
    }
}
$meta_title = 'Join Free — ' . setting('site_name','FreeHub');
$site_theme = setting('active_theme', 'dark-minimal');
$primary    = setting('primary_color', '#6366f1');
?><!DOCTYPE html>
<html lang="en" data-theme="<?= e($site_theme) ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($meta_title) ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
<style>:root{--accent:<?= e($primary) ?>;--accent2:<?= e($primary) ?>cc}</style>
<script>const _st=localStorage.getItem('fh_theme');if(_st)document.documentElement.setAttribute('data-theme',_st);</script>
<style>
.auth-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;
  background:radial-gradient(ellipse at top right,rgba(99,102,241,.15) 0%,transparent 60%),var(--bg)}
.auth-box{width:100%;max-width:460px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:36px}
.auth-title{font-size:1.4rem;font-weight:800;text-align:center;margin-bottom:4px;font-family:var(--font2)}
.auth-sub{text-align:center;color:var(--text2);font-size:.875rem;margin-bottom:24px}
.role-cards{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:20px}
.role-card{border:2px solid var(--border);border-radius:var(--radius);padding:12px 8px;text-align:center;cursor:pointer;transition:all .15s}
.role-card:hover{border-color:var(--accent)}
.role-card input{display:none}
.role-card.selected{border-color:var(--accent);background:rgba(99,102,241,.1)}
.role-card .role-icon{font-size:1.4rem;margin-bottom:4px}
.role-card .role-name{font-size:.8rem;font-weight:600}
.role-card .role-desc{font-size:.7rem;color:var(--text2);margin-top:2px}
</style>
</head><body>
<div class="auth-wrap">
  <div class="auth-box fade-in">
    <div style="text-align:center;margin-bottom:20px">
      <?= render_site_logo('auth') ?>
    </div>
    <h1 class="auth-title">Create Your Account</h1>
    <p class="auth-sub">Earn from watch time — viewers and creators alike</p>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php else: ?>
    <form method="POST" id="reg-form">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="role" id="role-field" value="viewer">
      <p style="font-size:.85rem;font-weight:600;margin-bottom:8px">I want to…</p>
      <div class="role-cards" style="grid-template-columns:repeat(2,1fr)">
        <label class="role-card selected" id="role-viewer" onclick="selectRole('viewer','role-viewer')">
          <div class="role-icon">&#128250;</div>
          <div class="role-name">Watch &amp; Earn</div>
          <div class="role-desc">Watch videos &amp; earn</div>
        </label>
        <label class="role-card" id="role-creator" onclick="selectRole('creator','role-creator')">
          <div class="role-icon">&#127916;</div>
          <div class="role-name">Creator</div>
          <div class="role-desc">Upload videos</div>
        </label>
      </div>
      <div style="display:flex;gap:12px">
        <div class="form-group" style="flex:1">
          <label class="form-label" for="first_name">First Name</label>
          <input class="form-input" type="text" id="first_name" name="first_name" required
                 placeholder="John" value="<?= e($_POST['first_name'] ?? '') ?>">
        </div>
        <div class="form-group" style="flex:1">
          <label class="form-label" for="last_name">Last Name</label>
          <input class="form-input" type="text" id="last_name" name="last_name" required
                 placeholder="Doe" value="<?= e($_POST['last_name'] ?? '') ?>">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" for="username">Username</label>
        <input class="form-input" type="text" id="username" name="username" required minlength="3" maxlength="30"
               placeholder="coolcreator" value="<?= e($_POST['username'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="email">Email</label>
        <input class="form-input" type="email" id="email" name="email" required
               placeholder="you@email.com" value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="phone">Phone Number</label>
        <div style="display:flex;gap:8px">
          <select name="country_code" id="country_code" class="form-input form-select" required
                  style="width:130px;flex-shrink:0;font-size:.85rem">
            <option value="">Code</option>
            <option value="+1" <?= ($_POST['country_code'] ?? '')=='+1'?'selected':'' ?>>🇺🇸 +1</option>
            <option value="+44" <?= ($_POST['country_code'] ?? '')=='+44'?'selected':'' ?>>🇬🇧 +44</option>
            <option value="+91" <?= ($_POST['country_code'] ?? '')=='+91'?'selected':'' ?>>🇮🇳 +91</option>
            <option value="+92" <?= ($_POST['country_code'] ?? '')=='+92'?'selected':'' ?>>🇵🇰 +92</option>
            <option value="+86" <?= ($_POST['country_code'] ?? '')=='+86'?'selected':'' ?>>🇨🇳 +86</option>
            <option value="+81" <?= ($_POST['country_code'] ?? '')=='+81'?'selected':'' ?>>🇯🇵 +81</option>
            <option value="+49" <?= ($_POST['country_code'] ?? '')=='+49'?'selected':'' ?>>🇩🇪 +49</option>
            <option value="+33" <?= ($_POST['country_code'] ?? '')=='+33'?'selected':'' ?>>🇫🇷 +33</option>
            <option value="+61" <?= ($_POST['country_code'] ?? '')=='+61'?'selected':'' ?>>🇦🇺 +61</option>
            <option value="+55" <?= ($_POST['country_code'] ?? '')=='+55'?'selected':'' ?>>🇧🇷 +55</option>
            <option value="+7" <?= ($_POST['country_code'] ?? '')=='+7'?'selected':'' ?>>🇷🇺 +7</option>
            <option value="+966" <?= ($_POST['country_code'] ?? '')=='+966'?'selected':'' ?>>🇸🇦 +966</option>
            <option value="+971" <?= ($_POST['country_code'] ?? '')=='+971'?'selected':'' ?>>🇦🇪 +971</option>
            <option value="+234" <?= ($_POST['country_code'] ?? '')=='+234'?'selected':'' ?>>🇳🇬 +234</option>
            <option value="+27" <?= ($_POST['country_code'] ?? '')=='+27'?'selected':'' ?>>🇿🇦 +27</option>
            <option value="+62" <?= ($_POST['country_code'] ?? '')=='+62'?'selected':'' ?>>🇮🇩 +62</option>
            <option value="+90" <?= ($_POST['country_code'] ?? '')=='+90'?'selected':'' ?>>🇹🇷 +90</option>
            <option value="+82" <?= ($_POST['country_code'] ?? '')=='+82'?'selected':'' ?>>🇰🇷 +82</option>
            <option value="+39" <?= ($_POST['country_code'] ?? '')=='+39'?'selected':'' ?>>🇮🇹 +39</option>
            <option value="+34" <?= ($_POST['country_code'] ?? '')=='+34'?'selected':'' ?>>🇪🇸 +34</option>
            <option value="+52" <?= ($_POST['country_code'] ?? '')=='+52'?'selected':'' ?>>🇲🇽 +52</option>
            <option value="+20" <?= ($_POST['country_code'] ?? '')=='+20'?'selected':'' ?>>🇪🇬 +20</option>
            <option value="+60" <?= ($_POST['country_code'] ?? '')=='+60'?'selected':'' ?>>🇲🇾 +60</option>
            <option value="+63" <?= ($_POST['country_code'] ?? '')=='+63'?'selected':'' ?>>🇵🇭 +63</option>
            <option value="+66" <?= ($_POST['country_code'] ?? '')=='+66'?'selected':'' ?>>🇹🇭 +66</option>
            <option value="+84" <?= ($_POST['country_code'] ?? '')=='+84'?'selected':'' ?>>🇻🇳 +84</option>
            <option value="+880" <?= ($_POST['country_code'] ?? '')=='+880'?'selected':'' ?>>🇧🇩 +880</option>
            <option value="+94" <?= ($_POST['country_code'] ?? '')=='+94'?'selected':'' ?>>🇱🇰 +94</option>
            <option value="+977" <?= ($_POST['country_code'] ?? '')=='+977'?'selected':'' ?>>🇳🇵 +977</option>
            <option value="+93" <?= ($_POST['country_code'] ?? '')=='+93'?'selected':'' ?>>🇦🇫 +93</option>
            <option value="+98" <?= ($_POST['country_code'] ?? '')=='+98'?'selected':'' ?>>🇮🇷 +98</option>
            <option value="+964" <?= ($_POST['country_code'] ?? '')=='+964'?'selected':'' ?>>🇮🇶 +964</option>
          </select>
          <input class="form-input" type="tel" id="phone" name="phone" required
                 placeholder="3001234567" value="<?= e($_POST['phone'] ?? '') ?>"
                 pattern="[0-9]{6,15}" style="flex:1">
        </div>
        <small style="color:var(--text2);font-size:.75rem;margin-top:4px;display:block">Select country code, then enter your number without leading zero</small>
      </div>
      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <input class="form-input" type="password" id="password" name="password" required minlength="8" placeholder="Min. 8 characters">
      </div>
      <div class="form-group">
        <label class="form-label" for="pass2">Confirm Password</label>
        <input class="form-input" type="password" id="pass2" name="pass2" required placeholder="Repeat password">
      </div>
      <button type="submit" class="btn btn-primary w-full" style="justify-content:center;padding:12px">Create Account</button>
      <p style="text-align:center;font-size:.78rem;color:var(--text2);margin-top:12px">
        By joining you agree to our <a href="<?= BASE_URL ?>/terms.php" style="color:var(--accent)">Terms</a> &amp; <a href="<?= BASE_URL ?>/privacy.php" style="color:var(--accent)">Privacy Policy</a>
      </p>
    </form>
    <p style="text-align:center;font-size:.875rem;color:var(--text2);margin-top:16px">
      Already have an account? <a href="<?= BASE_URL ?>/auth/login.php" style="color:var(--accent);font-weight:600">Sign In</a>
    </p>
    <div style="text-align:center;margin-top:20px;padding-top:16px;border-top:1px solid var(--border)">
      <a href="<?= BASE_URL ?>/" style="color:var(--text2);font-size:.875rem;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:color .15s" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text2)'">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        Back to Home
      </a>
    </div>
    <?php endif; ?>
  </div>
</div>
<script>
function selectRole(r,id){
  document.getElementById('role-field').value=r;
  document.querySelectorAll('.role-card').forEach(c=>c.classList.remove('selected'));
  document.getElementById(id).classList.add('selected');
}</script>
</body></html>
