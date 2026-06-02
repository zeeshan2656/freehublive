<?php
// Auth - Login
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
if (is_logged_in()) redirect(BASE_URL . '/');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) { $error = 'Invalid request.'; }
    elseif (!rate_limit('login_' . get_ip(), 10, 300)) { $error = 'Too many attempts. Wait 5 minutes.'; }
    else {
        $user = db_fetch("SELECT * FROM users WHERE email=? OR username=?", [trim($_POST['login']), trim($_POST['login'])]);
        if ($user && verify_password($_POST['password'] ?? '', $user['password'])) {
            if ($user['status'] === 'suspended') { $error = 'Account suspended.'; }
            else {
                login_user($user);
                // Smart redirect: admin -> admin dashboard, creator -> studio, else -> homepage or ?next=
                if (!empty($_GET['next'])) {
                    redirect(BASE_URL . $_GET['next']);
                } else {
                    redirect(BASE_URL . '/');
                }
            }
        } else { $error = 'Invalid credentials.'; }
    }
}
$meta_title = 'Sign In — ' . setting('site_name','FreeHub');
$site_theme = setting('active_theme', 'dark-minimal');
$primary    = setting('primary_color', '#6366f1');
require_once __DIR__ . '/../includes/db.php';
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
  background:radial-gradient(ellipse at top left,rgba(99,102,241,.15) 0%,transparent 60%),var(--bg)}
.auth-box{width:100%;max-width:420px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:36px}
.auth-logo{display:flex;justify-content:center;margin-bottom:24px}
.auth-title{font-size:1.4rem;font-weight:800;text-align:center;margin-bottom:4px;font-family:var(--font2)}
.auth-sub{text-align:center;color:var(--text2);font-size:.875rem;margin-bottom:24px}
.divider{display:flex;align-items:center;gap:12px;margin:16px 0;color:var(--text2);font-size:.8rem}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:var(--border)}
</style>
</head><body>
<div class="auth-wrap">
  <div class="auth-box fade-in">
    <div class="auth-logo">
      <?= render_site_logo('auth') ?>
    </div>
    <h1 class="auth-title">Welcome Back</h1>
    <p class="auth-sub">Sign in to continue watching</p>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="POST" autocomplete="on">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <div class="form-group">
        <label class="form-label" for="login">Email or Username</label>
        <input class="form-input" type="text" id="login" name="login" required autocomplete="username"
               placeholder="you@email.com" value="<?= e($_POST['login'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="password" style="display:flex;justify-content:space-between">
          Password
          <a href="<?= BASE_URL ?>/auth/forgot.php" style="color:var(--accent);font-weight:400;font-size:.8rem">Forgot?</a>
        </label>
        <input class="form-input" type="password" id="password" name="password" required autocomplete="current-password" placeholder="••••••••">
      </div>
      <button type="submit" class="btn btn-primary w-full" style="justify-content:center;padding:12px">Sign In</button>
    </form>
    <div class="divider">or</div>
    <p style="text-align:center;font-size:.875rem;color:var(--text2)">
      Don't have an account? <a href="<?= BASE_URL ?>/auth/register.php" style="color:var(--accent);font-weight:600">Join Free</a>
    </p>
    <div style="text-align:center;margin-top:20px;padding-top:16px;border-top:1px solid var(--border)">
      <a href="<?= BASE_URL ?>/" style="color:var(--text2);font-size:.875rem;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:color .15s" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text2)'">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        Back to Home
      </a>
    </div>
  </div>
</div>
</body></html>
