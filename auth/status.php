<?php
// ============================================================
// FreeHub.Live — Account Status Screen
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$user = auth_user();
$status = $user['status'] ?? 'pending';

// If they are active, they have no reason to be here. Redirect to dashboard.
if ($status === 'active') {
    redirect(BASE_URL . '/dashboard.php');
}

$meta_title = 'Account Status — ' . setting('site_name', 'FreeHub');
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container" style="max-width: 600px; padding: 80px 20px; margin: 0 auto;">
    <div class="card fade-in" style="padding: 40px; border-radius: 12px; background: var(--bg2); border: 1px solid var(--border); box-shadow: var(--shadow); text-align: center;">
        
        <?php if ($status === 'pending'): ?>
            <div style="font-size: 4rem; margin-bottom: 20px; animation: pulse 2s infinite;">⏳</div>
            <h1 style="font-family: var(--font2); font-size: 1.8rem; font-weight: 800; margin-bottom: 12px; color: var(--text);">Account Under Review</h1>
            <p style="color: var(--text2); font-size: 0.95rem; line-height: 1.6; margin-bottom: 30px;">
                Thank you for joining <strong><?= e(setting('site_name', 'FreeHub')) ?></strong>! 
                Your application is currently under review by our administration team.
                Please check back shortly. You will automatically receive full dashboard access once approved.
            </p>
        <?php elseif ($status === 'rejected'): ?>
            <div style="font-size: 4rem; margin-bottom: 20px;">❌</div>
            <h1 style="font-family: var(--font2); font-size: 1.8rem; font-weight: 800; margin-bottom: 12px; color: var(--red);">Application Rejected</h1>
            <p style="color: var(--text2); font-size: 0.95rem; line-height: 1.6; margin-bottom: 30px;">
                We regret to inform you that your application for a viewer or creator account was not approved.
                If you believe this is an error or would like to appeal, please contact support at 
                <a href="mailto:support@freehub.live" style="color: var(--accent); text-decoration: underline;">support@freehub.live</a>.
            </p>
        <?php elseif ($status === 'suspended'): ?>
            <div style="font-size: 4rem; margin-bottom: 20px;">🚫</div>
            <h1 style="font-family: var(--font2); font-size: 1.8rem; font-weight: 800; margin-bottom: 12px; color: var(--red);">Account Suspended</h1>
            <p style="color: var(--text2); font-size: 0.95rem; line-height: 1.6; margin-bottom: 30px;">
                Your account has been suspended due to violations of our terms of service or community guidelines.
                If you wish to dispute this suspension, please reach out to our moderation department.
            </p>
        <?php endif; ?>

        <div style="display: flex; flex-direction: column; gap: 12px; align-items: center;">
            <a href="<?= BASE_URL ?>/" class="btn btn-primary" style="width: 100%; justify-content: center; height: 42px; border-radius: 21px; display: inline-flex; align-items: center; text-decoration: none;">
                🏠 Back to Home Page
            </a>
            
            <a href="<?= BASE_URL ?>/auth/logout.php" class="btn btn-outline" style="width: 100%; justify-content: center; height: 42px; border-radius: 21px; display: inline-flex; align-items: center; text-decoration: none; border-color: var(--border);">
                ↵ Log Out of Account
            </a>
        </div>
    </div>
</div>

<style>
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}
</style>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>
