<?php
// ============================================================
// FreeHub.Live — Reset Password
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) redirect(BASE_URL . '/');

$token   = trim($_GET['token'] ?? '');
$error   = '';
$success = '';
$reset   = null;

if (empty($token)) {
    redirect(BASE_URL . '/auth/forgot.php');
}

// Validate token
$reset = db_fetch(
    "SELECT * FROM password_resets 
     WHERE token=? AND used_at IS NULL AND expires_at > NOW()
     LIMIT 1",
    [$token]
);

if (!$reset) {
    $error = 'This reset link is invalid or has expired. Please request a new one.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reset) {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $error = 'Invalid request.';
    } elseif (!rate_limit('reset_' . get_ip(), 10, 600)) {
        $error = 'Too many attempts. Please wait before trying again.';
    } else {
        $pass  = $_POST['password'] ?? '';
        $pass2 = $_POST['pass2'] ?? '';
        
        if (strlen($pass) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($pass !== $pass2) {
            $error = 'Passwords do not match.';
        } else {
            // Get user by email
            $user = db_fetch("SELECT id, username FROM users WHERE email=? LIMIT 1", [$reset['email']]);
            if (!$user) {
                $error = 'User account not found.';
            } else {
                // Update password
                db_update('users', ['password' => hash_password($pass)], 'id=?', [$user['id']]);
                // Mark token as used
                db_update('password_resets', ['used_at' => date('Y-m-d H:i:s')], 'token=?', [$token]);
                // Invalidate all other tokens for this email
                db_query("DELETE FROM password_resets WHERE email=? AND token != ?", [$reset['email'], $token]);
                $success = 'Your password has been successfully reset. You can now sign in with your new password.';
                $reset = null; // Hide the form
            }
        }
    }
}

$meta_title = 'Reset Password — ' . setting('site_name', 'FreeHub');
$site_theme = setting('active_theme', 'dark-minimal');
$primary    = setting('primary_color', '#6366f1');
?><!DOCTYPE html>
<html lang="en" data-theme="<?= e($site_theme) ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($meta_title) ?></title>
<link rel="stylesheet" href="<?= fh_asset_url('assets/css/main.css') ?>">
<style>:root{--accent:<?= e($primary) ?>;--accent2:<?= e($primary) ?>cc}</style>
<script>const _st=localStorage.getItem('fh_theme');if(_st)document.documentElement.setAttribute('data-theme',_st);</script>
<style>
.auth-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;
  background:radial-gradient(ellipse at bottom left,rgba(99,102,241,.15) 0%,transparent 60%),var(--bg)}
.auth-box{width:100%;max-width:440px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:36px}
.auth-title{font-size:1.4rem;font-weight:800;text-align:center;margin-bottom:4px;font-family:var(--font2)}
.auth-sub{text-align:center;color:var(--text2);font-size:.875rem;margin-bottom:24px}
.pw-strength{height:4px;background:var(--border);border-radius:2px;margin-top:6px;overflow:hidden}
.pw-strength-bar{height:100%;width:0%;border-radius:2px;transition:width .3s,background .3s}
</style>
</head><body>
<div class="auth-wrap">
  <div class="auth-box fade-in">
    <div style="text-align:center;margin-bottom:20px">
      <?= render_site_logo('auth') ?>
    </div>
    <h1 class="auth-title">Reset Password</h1>
    <p class="auth-sub">Choose a strong new password for your account</p>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= e($error) ?></div>
      <?php if (!$reset): ?>
      <div style="text-align:center;margin-top:16px">
        <a href="<?= BASE_URL ?>/auth/forgot.php" class="btn btn-primary btn-sm">Request New Reset Link</a>
      </div>
      <?php endif; ?>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert alert-success"><?= e($success) ?></div>
      <div style="text-align:center;margin-top:20px">
        <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-primary">Sign In Now</a>
      </div>
    <?php elseif ($reset): ?>
    <form method="POST" id="reset-form">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <div class="form-group">
        <label class="form-label" for="password">New Password</label>
        <input class="form-input" type="password" id="password" name="password"
               required minlength="8" placeholder="Min. 8 characters"
               autocomplete="new-password" oninput="checkStrength(this.value)">
        <div class="pw-strength"><div class="pw-strength-bar" id="pw-bar"></div></div>
        <small id="pw-hint" style="color:var(--text3);font-size:.75rem;margin-top:3px;display:block">Enter a password</small>
      </div>
      <div class="form-group">
        <label class="form-label" for="pass2">Confirm New Password</label>
        <input class="form-input" type="password" id="pass2" name="pass2"
               required minlength="8" placeholder="Repeat password"
               autocomplete="new-password">
      </div>
      <button type="submit" class="btn btn-primary w-full" style="justify-content:center;padding:12px">
        Set New Password
      </button>
    </form>
    <?php endif; ?>

    <div style="text-align:center;margin-top:20px;padding-top:16px;border-top:1px solid var(--border)">
      <a href="<?= BASE_URL ?>/auth/login.php" style="color:var(--text2);font-size:.875rem">← Back to Sign In</a>
    </div>
  </div>
</div>
<script>
function checkStrength(pw) {
  const bar  = document.getElementById('pw-bar');
  const hint = document.getElementById('pw-hint');
  let score  = 0;
  if (pw.length >= 8)  score++;
  if (pw.length >= 12) score++;
  if (/[A-Z]/.test(pw)) score++;
  if (/[0-9]/.test(pw)) score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;
  const levels = [
    {w:'0%',  c:'transparent', t:''},
    {w:'25%', c:'#ef4444',     t:'Weak'},
    {w:'50%', c:'#f97316',     t:'Fair'},
    {w:'75%', c:'#eab308',     t:'Good'},
    {w:'90%', c:'#22c55e',     t:'Strong'},
    {w:'100%',c:'#6366f1',     t:'Very Strong'},
  ];
  const lvl = levels[Math.min(score, 5)];
  bar.style.width = lvl.w;
  bar.style.background = lvl.c;
  hint.textContent = lvl.t;
  hint.style.color = lvl.c;
}
document.getElementById('reset-form')?.addEventListener('submit', function(e) {
  const p1 = document.getElementById('password')?.value;
  const p2 = document.getElementById('pass2')?.value;
  if (p1 !== p2) {
    e.preventDefault();
    alert('Passwords do not match!');
  }
});
</script>
</body></html>
