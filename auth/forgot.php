<?php
// ============================================================
// FreeHub.Live — Forgot Password
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

if (is_logged_in()) redirect(BASE_URL . '/');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } elseif (!rate_limit('forgot_' . get_ip(), 5, 600)) {
        $error = 'Too many requests. Please wait 10 minutes before trying again.';
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $user = db_fetch("SELECT id, username, email FROM users WHERE LOWER(email)=? AND status != 'suspended'", [$email]);
            
            if ($user) {
                // Delete any existing unused tokens for this email
                db_query("DELETE FROM password_resets WHERE email=? AND used_at IS NULL", [$email]);
                
                // Generate secure token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
                
                db_insert('password_resets', [
                    'email'      => $user['email'],
                    'token'      => $token,
                    'expires_at' => $expires,
                ]);
                
                // Send email
                $sent = fh_send_password_reset($user['email'], $user['username'], $token);
                
                if ($sent) {
                    $success = 'A password reset link has been sent to your email address. Please check your inbox (and spam folder).';
                } else {
                    // Still show success for security (don't reveal if email exists)
                    // but log error for admin
                    error_log("FreeHub: Failed to send password reset email to {$user['email']}");
                    $success = 'If an account with that email exists, a reset link has been sent. Please check your inbox.';
                }
            } else {
                // Don't reveal whether email exists
                $success = 'If an account with that email exists, a reset link has been sent. Please check your inbox.';
            }
        }
    }
}

$meta_title = 'Forgot Password — ' . setting('site_name', 'FreeHub');
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
.auth-box{width:100%;max-width:420px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:36px}
.auth-title{font-size:1.4rem;font-weight:800;text-align:center;margin-bottom:4px;font-family:var(--font2)}
.auth-sub{text-align:center;color:var(--text2);font-size:.875rem;margin-bottom:24px}
.success-wrap{text-align:center;padding:20px 0}
.success-icon{font-size:3rem;margin-bottom:12px}
</style>
</head><body>
<div class="auth-wrap">
  <div class="auth-box fade-in">
    <div style="text-align:center;margin-bottom:20px">
      <?= render_site_logo('auth') ?>
    </div>
    <h1 class="auth-title">Forgot Password</h1>
    <p class="auth-sub">Enter your email to receive a password reset link</p>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="success-wrap">
        <div class="success-icon">📧</div>
        <div class="alert alert-success"><?= e($success) ?></div>
        <p style="color:var(--text2);font-size:.875rem;margin-top:12px">
          Didn't receive it? Check your spam folder or try again in a few minutes.
        </p>
      </div>
    <?php else: ?>
    <form method="POST">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <input class="form-input" type="email" id="email" name="email" required
               placeholder="you@email.com" value="<?= e($_POST['email'] ?? '') ?>"
               autocomplete="email">
      </div>
      <button type="submit" class="btn btn-primary w-full" style="justify-content:center;padding:12px">
        Send Reset Link
      </button>
    </form>
    <?php endif; ?>

    <div style="text-align:center;margin-top:20px;padding-top:16px;border-top:1px solid var(--border)">
      <a href="<?= BASE_URL ?>/auth/login.php" style="color:var(--accent);font-size:.875rem;font-weight:600">← Back to Sign In</a>
    </div>
  </div>
</div>
</body></html>
