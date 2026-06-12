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
    if (!isset($_SESSION['user']['id'])) {
        return null;
    }
    
    static $fresh_user = null;
    if ($fresh_user === null) {
        $fresh_user = db_fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user']['id']]);
        if ($fresh_user) {
            // Update session cache with latest database values
            $_SESSION['user']['username']           = $fresh_user['username'];
            $_SESSION['user']['email']              = $fresh_user['email'];
            $_SESSION['user']['role']               = $fresh_user['role'];
            $_SESSION['user']['status']             = $fresh_user['status'];
            $_SESSION['user']['avatar']             = $fresh_user['avatar'];
            $_SESSION['user']['cover_image']        = $fresh_user['cover_image'] ?? null;
            $_SESSION['user']['channel_name']       = $fresh_user['channel_name'] ?? $fresh_user['username'];
            $_SESSION['user']['balance']            = $fresh_user['balance'];
            $_SESSION['user']['preferred_currency'] = $fresh_user['preferred_currency'];
            $_SESSION['user']['ref_code']           = $fresh_user['ref_code'];
        } else {
            // User deleted from DB, clear session
            $_SESSION = [];
            return null;
        }
    }
    return $_SESSION['user'];
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
// Check if user can earn (non-admin and active)
function can_earn(): bool    { 
    $user = auth_user();
    return $user && !is_admin() && ($user['status'] ?? 'pending') === 'active'; 
}

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

function get_guest_user_id(): int {
    static $guest_id = null;
    if ($guest_id !== null) {
        return $guest_id;
    }
    $guest = db_fetch("SELECT id FROM users WHERE username = 'guest'");
    if ($guest) {
        $guest_id = (int)$guest['id'];
        return $guest_id;
    }
    // Create guest user
    try {
        $guest_id = db_insert('users', [
            'username' => 'guest',
            'email' => 'guest@freehub.live',
            'password' => password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT),
            'role' => 'viewer',
            'status' => 'active',
            'channel_name' => 'Guest User',
            'ref_code' => 'GUEST'
        ]);
        return (int)$guest_id;
    } catch (Throwable $e) {
        // Fallback in case of collision or other issues
        $guest = db_fetch("SELECT id FROM users WHERE username = 'guest'");
        if ($guest) {
            $guest_id = (int)$guest['id'];
            return $guest_id;
        }
    }
    return 0;
}
