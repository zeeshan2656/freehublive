<?php
// ============================================================
// FreeHub.Live — Idempotent schema migrations
// ============================================================

function fh_column_exists(string $table, string $column): bool {
    $row = db_fetch(
        "SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
        [$table, $column]
    );
    return (int)($row['c'] ?? 0) > 0;
}

function fh_table_exists(string $table): bool {
    $row = db_fetch(
        "SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
        [$table]
    );
    return (int)($row['c'] ?? 0) > 0;
}

function fh_index_exists(string $table, string $index): bool {
    $row = db_fetch(
        "SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?",
        [$table, $index]
    );
    return (int)($row['c'] ?? 0) > 0;
}

function fh_run_migrations(): void {
    static $done = false;
    if ($done) return;
    $done = true;

    // Schema not imported yet — skip (import install/schema.sql in phpMyAdmin)
    if (!fh_table_exists('users')) {
        return;
    }

    // ── Users table additions ────────────────────────────────
    if (!fh_column_exists('users', 'preferred_currency')) {
        db_query("ALTER TABLE users ADD COLUMN preferred_currency VARCHAR(3) NOT NULL DEFAULT 'USD' AFTER balance");
    }
    if (!fh_column_exists('users', 'total_watch_seconds')) {
        db_query("ALTER TABLE users ADD COLUMN total_watch_seconds BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER total_views");
    }
    if (!fh_column_exists('users', 'lifetime_watch_earnings')) {
        db_query("ALTER TABLE users ADD COLUMN lifetime_watch_earnings DECIMAL(12,4) NOT NULL DEFAULT 0.0000 AFTER total_watch_seconds");
        db_query(
            "UPDATE users u SET lifetime_watch_earnings = (
                SELECT COALESCE(SUM(e.amount), 0) FROM earnings e
                WHERE e.user_id = u.id AND e.type = 'watch_time'
                AND e.status IN ('approved', 'paid')
            )"
        );
    }
    if (!fh_column_exists('users', 'is_active')) {
        db_query("ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER status");
    }
    if (!fh_column_exists('users', 'cover_image')) {
        db_query("ALTER TABLE users ADD COLUMN cover_image VARCHAR(255) DEFAULT NULL AFTER avatar");
    }

    // ── Migrate role from partner to creator ─────────────────
    try {
        db_query("ALTER TABLE users MODIFY COLUMN role ENUM('admin','affiliate','partner','creator','viewer') NOT NULL DEFAULT 'viewer'");
        db_query("UPDATE users SET role = 'creator' WHERE role = 'partner'");
        db_query("ALTER TABLE users MODIFY COLUMN role ENUM('admin','affiliate','creator','viewer') NOT NULL DEFAULT 'viewer'");
    } catch (Throwable $e) {
        // Ignore if already run or database/driver mismatch
    }

    // ── Withdrawal requests table ───────────────────────────
    if (!fh_table_exists('withdrawal_requests')) {
        db_query("CREATE TABLE withdrawal_requests (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            amount DECIMAL(12,4) NOT NULL,
            currency VARCHAR(3) NOT NULL DEFAULT 'USD',
            payment_method VARCHAR(80) NOT NULL,
            payment_details TEXT NOT NULL,
            country VARCHAR(80) DEFAULT NULL,
            status ENUM('pending','processing','paid','rejected') NOT NULL DEFAULT 'pending',
            admin_note TEXT DEFAULT NULL,
            due_by DATE DEFAULT NULL,
            processed_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_status (status),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");
    }

    // ── Password resets table ───────────────────────────────
    if (!fh_table_exists('password_resets')) {
        db_query("CREATE TABLE password_resets (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(100) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            used_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_token (token),
            INDEX idx_email (email)
        ) ENGINE=InnoDB");
    }

    // ── Referral conversions table ──────────────────────────
    if (!fh_table_exists('referral_conversions')) {
        db_query("CREATE TABLE referral_conversions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            referrer_id INT UNSIGNED NOT NULL,
            referred_user_id INT UNSIGNED NOT NULL,
            ref_code VARCHAR(20) NOT NULL,
            bonus_paid TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_referrer (referrer_id),
            INDEX idx_referred (referred_user_id)
        ) ENGINE=InnoDB");
    }

    // ── Videos table: approval_status column ────────────────
    if (!fh_column_exists('videos', 'approval_note')) {
        db_query("ALTER TABLE videos ADD COLUMN approval_note TEXT DEFAULT NULL AFTER status");
    }

    // ── Extend earnings.type enum ──────────────────────────
    try {
        db_query("ALTER TABLE earnings MODIFY COLUMN type ENUM(
            'video_view','affiliate_click','affiliate_view','payout','bonus','watch_time','ad_revenue','referral'
        ) NOT NULL");
    } catch (Throwable $e) {
        // Already migrated or MySQL version difference
    }

    // ── Default settings ────────────────────────────────────
    $defaults = [
        'watch_time_rate_usd'    => '0.50',
        'viewer_rate_usd'        => '0.50',
        'creator_rate_usd'       => '0.50',
        'min_withdrawal'         => '25.00',
        'min_payout'             => '25.00',
        'ad_revenue_per_click'   => '0.05',
        'currency_rates_json'    => '{"USD":1,"INR":83.5,"PKR":278,"EUR":0.92,"GBP":0.79,"CAD":1.36,"AUD":1.52,"BDT":110,"AED":3.67,"SAR":3.75}',
        'schema_version'         => '3',
        'video_approval_mode'    => 'manual',  // 'auto' or 'manual'
        'smtp_host'              => '',
        'smtp_port'              => '587',
        'smtp_user'              => '',
        'smtp_pass'              => '',
        'smtp_from_email'        => '',
        'smtp_from_name'         => 'FreeHub',
        'smtp_encryption'        => 'tls',     // 'tls' or 'ssl'
        'referral_bonus_usd'     => '0.00',    // bonus for referrer when user signs up
    ];
    foreach ($defaults as $key => $val) {
        $group = 'general';
        if (str_contains($key, 'rate') || str_contains($key, 'withdrawal') || str_contains($key, 'payout') || str_contains($key, 'revenue') || str_contains($key, 'bonus')) $group = 'earnings';
        if (str_contains($key, 'smtp')) $group = 'email';
        if ($key === 'video_approval_mode') $group = 'content';
        db_query(
            "INSERT INTO settings (`key`,`value`,`group`) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE `key`=`key`",
            [$key, $val, $group]
        );
    }

    // ── Ensure admin has a channel name ─────────────────────
    $admin = db_fetch("SELECT id, channel_name FROM users WHERE role='admin' ORDER BY id ASC LIMIT 1");
    if ($admin && empty($admin['channel_name'])) {
        $uname = db_fetch("SELECT username FROM users WHERE id=?", [$admin['id']]);
        db_update('users', ['channel_name' => $uname['username'] ?? 'Admin Channel'], 'id=?', [$admin['id']]);
    }

    // ── Track existing referrals if table is new ─────────────
    // Populate referral_conversions from existing referred_by data
    if (fh_table_exists('referral_conversions') && fh_column_exists('users', 'referred_by')) {
        $untracked = db_fetchAll(
            "SELECT u.id AS referred_user_id, u.referred_by AS referrer_id, u2.ref_code
             FROM users u
             JOIN users u2 ON u2.id = u.referred_by
             LEFT JOIN referral_conversions rc ON rc.referred_user_id = u.id
             WHERE u.referred_by IS NOT NULL AND rc.id IS NULL
             LIMIT 100"
        );
        foreach ($untracked as $row) {
            try {
                db_insert('referral_conversions', [
                    'referrer_id'      => $row['referrer_id'],
                    'referred_user_id' => $row['referred_user_id'],
                    'ref_code'         => $row['ref_code'] ?? '',
                    'bonus_paid'       => 0,
                ]);
            } catch (Throwable $e) {
                // Ignore duplicate
            }
        }
    }

    // ── Ad Placements table ──────────────────────────────────
    if (!fh_table_exists('ad_placements')) {
        db_query("CREATE TABLE ad_placements (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            key_name VARCHAR(50) NOT NULL,
            device_target ENUM('all', 'desktop', 'mobile') NOT NULL DEFAULT 'all',
            name VARCHAR(100) NOT NULL,
            assigned_ad_id INT UNSIGNED DEFAULT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $placements = [
            ['landing_trending', 'Landing Page - Trending Now Grid (Last Card)'],
            ['landing_latest', 'Landing Page - Latest Uploads Grid (Last Card)'],
            ['search_grid', 'Search Results Grid (Last Card)'],
            ['category_grid', 'Category Videos Grid (Last Card)']
        ];
        foreach ($placements as $p) {
            db_query("INSERT INTO ad_placements (key_name, name) VALUES (?, ?)", [$p[0], $p[1]]);
        }
    }

    if (fh_table_exists('ad_placements') && !fh_column_exists('ad_placements', 'device_target')) {
        try {
            db_query("ALTER TABLE ad_placements DROP INDEX key_name");
        } catch (Throwable $e) {}
        db_query("ALTER TABLE ad_placements ADD COLUMN device_target ENUM('all', 'desktop', 'mobile') NOT NULL DEFAULT 'all' AFTER key_name");
    }
}
