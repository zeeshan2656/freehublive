<?php
// ============================================================
// FreeHub.Live — Authentication & Session Management
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400 * 30,
        'path'     => '/',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function auth_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool {
    return isset($_SESSION['user']['id']);
}

function has_role(string|array $roles): bool {
    $user = auth_user();
    if (!$user) return false;
    return in_array($user['role'], (array)$roles, true);
}

/**
 * Platform roles:
 *  admin    — site manager (cannot earn)
 *  viewer   — Watch & Earn user (watches videos, earns, has referral link)
 *  creator  — Creator (uploads videos, earns, has referral link)
 * 
 * Legacy 'affiliate' role is treated as 'viewer' for backward-compat.
 */
function is_admin(): bool    { return has_role('admin'); }
function is_creator(): bool  { return has_role(['creator', 'admin']); }
// All logged-in non-admin users are considered affiliates (they all get referral links)
function is_affiliate(): bool{ return has_role(['viewer', 'creator', 'affiliate', 'admin']); }
// Strict viewer-only check
function is_viewer(): bool   { return has_role(['viewer', 'affiliate']); }
// Check if user can earn (non-admin)
function can_earn(): bool    { return is_logged_in() && !is_admin(); }

function require_login(string $redirect = '/auth/login.php'): void {
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . $redirect . '?next=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function require_role(string|array $roles, string $redirect = '/'): void {
    require_login();
    if (!has_role($roles)) {
        header('Location: ' . BASE_URL . $redirect);
        exit;
    }
}

function login_user(array $user): void {
    $_SESSION['user'] = [
        'id'                 => $user['id'],
        'username'           => $user['username'],
        'email'              => $user['email'],
        'role'               => $user['role'],
        'avatar'             => $user['avatar'] ?? null,
        'channel_name'       => $user['channel_name'] ?? $user['username'],
        'balance'            => $user['balance'] ?? 0,
        'ref_code'           => $user['ref_code'] ?? null,
        'preferred_currency' => $user['preferred_currency'] ?? 'USD',
    ];
    // Update last_login
    db_update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
}

function logout_user(): void {
    $_SESSION = [];
    session_destroy();
}

function hash_password(string $pass): string {
    return password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verify_password(string $pass, string $hash): bool {
    return password_verify($pass, $hash);
}

function generate_ref_code(): string {
    return strtoupper(substr(bin2hex(random_bytes(5)), 0, 8));
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verify_csrf(string $token): bool {
    return hash_equals($_SESSION['csrf'] ?? '', $token);
}

// Rate limiting (file-based, fast)
function rate_limit(string $key, int $max, int $window = 60): bool {
    $file = sys_get_temp_dir() . '/fh_rl_' . md5($key) . '.json';
    $now  = time();
    $data = file_exists($file) ? (array)json_decode(file_get_contents($file), true) : ['hits' => [], 'count' => 0];
    // Clean old hits
    $data['hits'] = array_filter($data['hits'], fn($t) => $t > $now - $window);
    if (count($data['hits']) >= $max) return false;
    $data['hits'][] = $now;
    file_put_contents($file, json_encode($data), LOCK_EX);
    return true;
}
