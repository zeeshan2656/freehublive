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

    // ── Migration cache: skip INFORMATION_SCHEMA queries if already done ──
    // Bump this version whenever you add new migrations to force re-check
    $migration_version = '2026.06.13.1';
    $cache_dir = __DIR__ . '/../cache/';
    $flag_file = $cache_dir . '.migrations_done';
    
    if (is_file($flag_file)) {
        $cached_ver = @file_get_contents($flag_file);
        if (trim($cached_ver) === $migration_version) {
            return; // All migrations already applied for this version
        }
    }

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
    
    // ── Migrate legacy 'processing' video status to 'pending'
    db_query("UPDATE videos SET status = 'pending' WHERE status = 'processing'");


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
        'user_approval_mode'     => 'auto',    // 'auto' or 'manual'
        'creator_approval_mode'  => 'manual',  // 'auto' or 'manual'
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
        if ($key === 'video_approval_mode' || $key === 'user_approval_mode' || $key === 'creator_approval_mode') $group = 'content';
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

    if (fh_table_exists('ad_placements') && !fh_column_exists('ad_placements', 'ad_width')) {
        db_query("ALTER TABLE ad_placements ADD COLUMN ad_width SMALLINT UNSIGNED DEFAULT NULL AFTER device_target");
    }
    if (fh_table_exists('ad_placements') && !fh_column_exists('ad_placements', 'ad_height')) {
        db_query("ALTER TABLE ad_placements ADD COLUMN ad_height SMALLINT UNSIGNED DEFAULT NULL AFTER ad_width");
    }
    if (fh_table_exists('ad_placements') && !fh_column_exists('ad_placements', 'reload_interval')) {
        db_query("ALTER TABLE ad_placements ADD COLUMN reload_interval INT UNSIGNED DEFAULT NULL AFTER ad_height");
    }

    if (fh_table_exists('ad_placements')) {
        $check = db_fetch("SELECT COUNT(*) AS c FROM ad_placements WHERE key_name = 'home_mobile_top'");
        if ((int)$check['c'] === 0) {
            db_query("INSERT INTO ad_placements (key_name, name, device_target) VALUES ('home_mobile_top', 'Home Page Mobile Top Banner', 'mobile')");
        }
    }


    // ── Update users status ENUM to include 'rejected' ─────────
    if (fh_table_exists('users')) {
        try {
            db_query("ALTER TABLE users MODIFY COLUMN status ENUM('active', 'suspended', 'pending', 'rejected') NOT NULL DEFAULT 'pending'");
        } catch (Throwable $e) {
            // Ignore if already run
        }
    }

    // ── Ad Impressions & Clicks Earnings Migration ──────────────────
    // 1. Extend earnings.type enum
    try {
        db_query("ALTER TABLE earnings MODIFY COLUMN type ENUM(
            'video_view','affiliate_click','affiliate_view','payout','bonus','watch_time','ad_revenue','referral','ad_impression','ad_click'
        ) NOT NULL");
    } catch (Throwable $e) {}

    // 2. Create ad_logs table
    if (!fh_table_exists('ad_logs')) {
        db_query("CREATE TABLE ad_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ad_id INT UNSIGNED NOT NULL,
            video_id INT UNSIGNED DEFAULT NULL,
            viewer_id INT UNSIGNED DEFAULT NULL,
            creator_id INT UNSIGNED DEFAULT NULL,
            type ENUM('impression', 'click') NOT NULL,
            ip_hash VARCHAR(64) NOT NULL,
            user_agent VARCHAR(255) DEFAULT NULL,
            earnings_viewer DECIMAL(12, 6) DEFAULT 0.000000,
            earnings_creator DECIMAL(12, 6) DEFAULT 0.000000,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ad (ad_id),
            INDEX idx_video (video_id),
            INDEX idx_viewer (viewer_id),
            INDEX idx_creator (creator_id),
            INDEX idx_type (type),
            INDEX idx_ip_date (ip_hash, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    // 3. Add default settings for CPM / CPC
    $ad_defaults = [
        'creator_cpm' => '1.00',
        'creator_cpc' => '50.00',
        'viewer_cpm' => '0.50',
        'viewer_cpc' => '20.00',
    ];
    foreach ($ad_defaults as $key => $val) {
        db_query(
            "INSERT INTO settings (`key`,`value`,`group`) VALUES (?,?,'earnings')
             ON DUPLICATE KEY UPDATE `key`=`key`",
            [$key, $val]
        );
    }

    // 4. Add columns to videos
    if (fh_table_exists('videos')) {
        if (!fh_column_exists('videos', 'ad_impressions')) {
            db_query("ALTER TABLE videos ADD COLUMN ad_impressions INT UNSIGNED NOT NULL DEFAULT 0 AFTER watch_time");
        }
        if (!fh_column_exists('videos', 'ad_clicks')) {
            db_query("ALTER TABLE videos ADD COLUMN ad_clicks INT UNSIGNED NOT NULL DEFAULT 0 AFTER ad_impressions");
        }
    }

    // 5. Add columns to users
    if (fh_table_exists('users')) {
        if (!fh_column_exists('users', 'total_ad_impressions')) {
            db_query("ALTER TABLE users ADD COLUMN total_ad_impressions BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER total_watch_seconds");
        }
        if (!fh_column_exists('users', 'total_ad_clicks')) {
            db_query("ALTER TABLE users ADD COLUMN total_ad_clicks BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER total_ad_impressions");
        }
        if (!fh_column_exists('users', 'lifetime_ad_earnings')) {
            db_query("ALTER TABLE users ADD COLUMN lifetime_ad_earnings DECIMAL(12,4) NOT NULL DEFAULT 0.0000 AFTER lifetime_watch_earnings");
        }
    }

    // 6. Add default placements for watch page and footer
    if (fh_table_exists('ad_placements')) {
        $watch_placements = [
            ['watch_sidebar', 'Watch Page Sidebar Banner', 'all'],
            ['watch_below_player', 'Watch Page Below Player Banner', 'all'],
            ['watch_up_next', 'Watch Page Up Next Banner', 'all'],
            ['above_footer', 'Above Footer Banner', 'all']
        ];
        foreach ($watch_placements as $wp) {
            $check = db_fetch("SELECT COUNT(*) AS c FROM ad_placements WHERE key_name = ?", [$wp[0]]);
            if ((int)$check['c'] === 0) {
                db_query("INSERT INTO ad_placements (key_name, name, device_target) VALUES (?, ?, ?)", [$wp[0], $wp[1], $wp[2]]);
            }
        }
    }

    // 7. Video Player Overlay Ad Placement System
    if (fh_table_exists('ad_placements')) {
        if (!fh_column_exists('ad_placements', 'ad_display_duration')) {
            db_query("ALTER TABLE ad_placements ADD COLUMN ad_display_duration SMALLINT UNSIGNED DEFAULT NULL AFTER reload_interval");
        }
        if (!fh_column_exists('ad_placements', 'ad_trigger_count')) {
            db_query("ALTER TABLE ad_placements ADD COLUMN ad_trigger_count TINYINT UNSIGNED DEFAULT NULL AFTER ad_display_duration");
        }
        
        $check = db_fetch("SELECT COUNT(*) AS c FROM ad_placements WHERE key_name = 'video_player_overlay'");
        if ((int)$check['c'] === 0) {
            db_query("INSERT INTO ad_placements (key_name, name, device_target, ad_display_duration, ad_trigger_count) VALUES ('video_player_overlay', 'Video Player Overlay Ad', 'all', 5, 3)");
        }
    }

    // 8. Performance Tuning Compound Indexes
    if (fh_table_exists('videos')) {
        if (!fh_index_exists('videos', 'idx_vid_status_visibility_pub')) {
            db_query("ALTER TABLE videos ADD INDEX idx_vid_status_visibility_pub (status, visibility, published_at DESC)");
        }
        if (!fh_index_exists('videos', 'idx_vid_status_visibility_created')) {
            db_query("ALTER TABLE videos ADD INDEX idx_vid_status_visibility_created (status, visibility, created_at DESC)");
        }
        if (!fh_index_exists('videos', 'idx_vid_status_visibility_views')) {
            db_query("ALTER TABLE videos ADD INDEX idx_vid_status_visibility_views (status, visibility, views DESC)");
        }
        if (!fh_index_exists('videos', 'idx_vid_feat_status_vis_pub')) {
            db_query("ALTER TABLE videos ADD INDEX idx_vid_feat_status_vis_pub (featured, status, visibility, published_at DESC)");
        }
        if (!fh_index_exists('videos', 'idx_vid_cat_status_vis_views')) {
            db_query("ALTER TABLE videos ADD INDEX idx_vid_cat_status_vis_views (category_id, status, visibility, views DESC)");
        }
    }
    if (fh_table_exists('video_categories')) {
        if (!fh_index_exists('video_categories', 'idx_vc_cat_vid')) {
            db_query("ALTER TABLE video_categories ADD INDEX idx_vc_cat_vid (category_id, video_id)");
        }
    }
    if (fh_table_exists('comments')) {
        if (!fh_index_exists('comments', 'idx_comm_vid_status_created')) {
            db_query("ALTER TABLE comments ADD INDEX idx_comm_vid_status_created (video_id, status, created_at DESC)");
        }
    }





    // ── CPM and Placement Assignment Refactor (2026.05.30.1) ────
    if (fh_table_exists('ad_logs')) {
        if (!fh_column_exists('ad_logs', 'placement')) {
            db_query("ALTER TABLE ad_logs ADD COLUMN placement VARCHAR(80) DEFAULT NULL AFTER type");
        }
        if (!fh_index_exists('ad_logs', 'idx_ad_logs_placement')) {
            db_query("ALTER TABLE ad_logs ADD INDEX idx_ad_logs_placement (placement)");
        }
    }

    if (fh_table_exists('earnings')) {
        if (!fh_column_exists('earnings', 'placement')) {
            db_query("ALTER TABLE earnings ADD COLUMN placement VARCHAR(80) DEFAULT NULL AFTER reference_id");
        }
        if (!fh_index_exists('earnings', 'idx_earnings_placement')) {
            db_query("ALTER TABLE earnings ADD INDEX idx_earnings_placement (placement)");
        }
    }

    // Set correct defaults for thousand-based CPM/CPC settings
    $new_defaults = [
        'viewer_cpm'   => '0.50',
        'viewer_cpc'   => '2.00',
        'creator_cpm'  => '1.00',
        'creator_cpc'  => '5.00',
    ];
    foreach ($new_defaults as $k => $v) {
        db_query(
            "INSERT INTO settings (`key`, `value`, `group`) VALUES (?, ?, 'earnings')
             ON DUPLICATE KEY UPDATE `value` = ?",
            [$k, $v, $v]
        );
    }

    // ── Reels Short-Video System (2026.05.30.2) ────
    if (fh_table_exists('videos')) {
        if (!fh_column_exists('videos', 'is_reel')) {
            db_query("ALTER TABLE videos ADD COLUMN is_reel TINYINT(1) NOT NULL DEFAULT 0 AFTER category_id");
        }
        if (!fh_index_exists('videos', 'idx_videos_is_reel')) {
            db_query("ALTER TABLE videos ADD INDEX idx_videos_is_reel (is_reel)");
        }
    }

    $reels_defaults = [
        'reels_enabled' => '1',
    ];
    foreach ($reels_defaults as $k => $v) {
        db_query(
            "INSERT INTO settings (`key`, `value`, `group`) VALUES (?, ?, 'general')
             ON DUPLICATE KEY UPDATE `key`=`key`",
            [$k, $v]
        );
    }

    if (fh_table_exists('ad_placements')) {
        $check = db_fetch("SELECT COUNT(*) AS c FROM ad_placements WHERE key_name = 'reels_top_overlay'");
        if ((int)$check['c'] === 0) {
            db_query("INSERT INTO ad_placements (key_name, name, device_target) VALUES ('reels_top_overlay', 'Reels Top Overlay Ad', 'all')");
        }
    }

    // ── Reels-Specific Ad Placement System (2026.05.30.3) ────
    if (fh_table_exists('ad_placements')) {
        $reels_placements = [
            ['reels_mobile_top', 'Reels Mobile Top Ad', 'mobile'],
            ['reels_left', 'Reels Left Ad', 'desktop'],
            ['reels_right', 'Reels Right Ad', 'desktop'],
            ['reels_bottom', 'Reels Bottom Ad', 'desktop']
        ];
        foreach ($reels_placements as $rp) {
            $check = db_fetch("SELECT COUNT(*) AS c FROM ad_placements WHERE key_name = ?", [$rp[0]]);
            if ((int)$check['c'] === 0) {
                db_query("INSERT INTO ad_placements (key_name, name, device_target) VALUES (?, ?, ?)", [$rp[0], $rp[1], $rp[2]]);
            }
        }
    }

    // ── Grid Interstitial Ad Placements (2026.05.30.4) ────
    if (fh_table_exists('ad_placements')) {
        $grid_placements = [
            // Home / Landing Page
            ['landing_latest_10', 'Landing Page - After 10th Video', 'all'],
            ['landing_latest_20', 'Landing Page - After 20th Video', 'all'],
            ['landing_latest_30', 'Landing Page - After 30th Video', 'all'],
            ['landing_latest_40', 'Landing Page - After 40th Video', 'all'],
            ['landing_latest_50', 'Landing Page - After 50th Video', 'all'],
            // Search Page
            ['search_grid_10', 'Search Results Grid - After 10th Video', 'all'],
            ['search_grid_20', 'Search Results Grid - After 20th Video', 'all'],
            ['search_grid_30', 'Search Results Grid - After 30th Video', 'all'],
            ['search_grid_40', 'Search Results Grid - After 40th Video', 'all'],
            ['search_grid_50', 'Search Results Grid - After 50th Video', 'all'],
            // Category Page
            ['category_grid_10', 'Category Grid - After 10th Video', 'all'],
            ['category_grid_20', 'Category Grid - After 20th Video', 'all'],
            ['category_grid_30', 'Category Grid - After 30th Video', 'all'],
            ['category_grid_40', 'Category Grid - After 40th Video', 'all'],
            ['category_grid_50', 'Category Grid - After 50th Video', 'all']
        ];
        foreach ($grid_placements as $gp) {
            $check = db_fetch("SELECT COUNT(*) AS c FROM ad_placements WHERE key_name = ?", [$gp[0]]);
            if ((int)$check['c'] === 0) {
                db_query("INSERT INTO ad_placements (key_name, name, device_target) VALUES (?, ?, ?)", [$gp[0], $gp[1], $gp[2]]);
            }
        }
    }

    // ── Remove legacy ad placements (2026.05.30.5) ────
    if (fh_table_exists('ad_placements')) {
        db_query("DELETE FROM ad_placements WHERE key_name IN ('landing_trending', 'landing_latest')");
    }

    // ── Database Relational Cleanup & Orphan Record Eviction (2026.05.30.7) ────
    try {
        if (fh_table_exists('comments')) {
            db_query("DELETE FROM comments WHERE video_id NOT IN (SELECT id FROM videos)");
            db_query("DELETE FROM comments WHERE user_id IS NOT NULL AND user_id NOT IN (SELECT id FROM users)");
        }

        if (fh_table_exists('ad_logs')) {
            db_query("DELETE FROM ad_logs WHERE video_id IS NOT NULL AND video_id NOT IN (SELECT id FROM videos)");
            db_query("DELETE FROM ad_logs WHERE viewer_id IS NOT NULL AND viewer_id NOT IN (SELECT id FROM users)");
        }
    } catch (Throwable $e) {
        // Suppress database engine or key index lock errors silently
    }

    // ── Video upload status enum tuning (2026.05.30.9) ──
    if (fh_table_exists('videos')) {
        try {
            db_query("ALTER TABLE videos MODIFY COLUMN status ENUM('draft','pending','published','rejected','processing','uploading','failed') NOT NULL DEFAULT 'uploading'");
        } catch (Throwable $e) {}
    }

    // ── Expand upload_sessions for deferred-publish workflow (2026.06.03.1) ──
    if (fh_table_exists('upload_sessions')) {
        if (!fh_column_exists('upload_sessions', 'meta_json')) {
            try { db_query("ALTER TABLE upload_sessions ADD COLUMN meta_json TEXT DEFAULT NULL AFTER token"); } catch (Throwable $e) {}
        }
        if (!fh_column_exists('upload_sessions', 'temp_thumb')) {
            try { db_query("ALTER TABLE upload_sessions ADD COLUMN temp_thumb VARCHAR(255) DEFAULT NULL AFTER meta_json"); } catch (Throwable $e) {}
        }
        if (!fh_column_exists('upload_sessions', 'status')) {
            try { db_query("ALTER TABLE upload_sessions ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active' AFTER temp_thumb"); } catch (Throwable $e) {}
        }
        if (!fh_column_exists('upload_sessions', 'uploaded_bytes')) {
            try { db_query("ALTER TABLE upload_sessions ADD COLUMN uploaded_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER status"); } catch (Throwable $e) {}
        }
        if (!fh_column_exists('upload_sessions', 'total_size')) {
            try { db_query("ALTER TABLE upload_sessions ADD COLUMN total_size BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER uploaded_bytes"); } catch (Throwable $e) {}
        }
        if (!fh_column_exists('upload_sessions', 'created_at')) {
            try { db_query("ALTER TABLE upload_sessions ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP"); } catch (Throwable $e) {}
        }
        // Make video_id nullable (session is created before video record exists)
        try { db_query("ALTER TABLE upload_sessions MODIFY COLUMN video_id INT UNSIGNED DEFAULT NULL"); } catch (Throwable $e) {}
    } else {
        // Create expanded upload_sessions table from scratch
        try {
            db_query("CREATE TABLE IF NOT EXISTS upload_sessions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                video_id INT UNSIGNED DEFAULT NULL,
                user_id INT UNSIGNED NOT NULL,
                token VARCHAR(64) NOT NULL,
                meta_json TEXT DEFAULT NULL,
                temp_thumb VARCHAR(255) DEFAULT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                uploaded_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
                total_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_token (token),
                INDEX idx_user (user_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB;");
        } catch (Throwable $e) {}
    }

    // ── Drop monetization/earnings tables and settings (2026.06.05.1) ──
    try {
        db_query("DROP TABLE IF EXISTS earnings");
    } catch (Throwable $e) {}
    try {
        db_query("DROP TABLE IF EXISTS withdrawal_requests");
    } catch (Throwable $e) {}
    try {
        db_query("DROP TABLE IF EXISTS affiliate_clicks");
    } catch (Throwable $e) {}
    try {
        db_query("DROP TABLE IF EXISTS referral_conversions");
    } catch (Throwable $e) {}
    try {
        db_query("DELETE FROM settings WHERE `group` = 'earnings'");
    } catch (Throwable $e) {}

    // ── Separate Reels System migration (2026.06.05.2) ──
    if (!fh_table_exists('reels')) {
        db_query("CREATE TABLE reels (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            video_url VARCHAR(500) NOT NULL,
            title VARCHAR(200) DEFAULT NULL,
            views BIGINT UNSIGNED NOT NULL DEFAULT 0,
            likes INT UNSIGNED NOT NULL DEFAULT 0,
            comments_count INT UNSIGNED NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'published',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_created (created_at DESC),
            INDEX idx_status (status),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    if (!fh_table_exists('reel_comments')) {
        db_query("CREATE TABLE reel_comments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            reel_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            parent_id INT UNSIGNED DEFAULT NULL,
            content TEXT NOT NULL,
            status ENUM('visible','hidden','spam') NOT NULL DEFAULT 'visible',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_reel (reel_id),
            INDEX idx_user (user_id),
            FOREIGN KEY (reel_id) REFERENCES reels(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    if (!fh_table_exists('reel_reactions')) {
        db_query("CREATE TABLE reel_reactions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            reel_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            type ENUM('like','dislike') NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_reel_reaction (reel_id, user_id),
            FOREIGN KEY (reel_id) REFERENCES reels(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    // Migrate existing reels from videos table
    if (fh_column_exists('videos', 'is_reel')) {
        $existing_reels = db_fetchAll("SELECT * FROM videos WHERE is_reel = 1");
        
        $uploads_dir = __DIR__ . '/../uploads/';
        $videos_dir = $uploads_dir . 'videos/';
        $reels_dir = $uploads_dir . 'reels/';
        $thumbs_dir = $uploads_dir . 'thumbnails/';
        
        if (!is_dir($reels_dir)) {
            @mkdir($reels_dir, 0755, true);
        }

        foreach ($existing_reels as $r) {
            // Check if already in reels table
            $exists = db_fetch("SELECT id FROM reels WHERE id = ?", [$r['id']]);
            if (!$exists) {
                db_insert('reels', [
                    'id'             => $r['id'],
                    'user_id'        => $r['user_id'],
                    'video_url'      => $r['video_url'],
                    'title'          => $r['title'],
                    'views'          => $r['views'],
                    'likes'          => $r['likes'],
                    'comments_count' => $r['comments_count'],
                    'status'         => ($r['status'] === 'published') ? 'published' : 'draft',
                    'created_at'     => $r['created_at'] ?: date('Y-m-d H:i:s')
                ]);
                
                // Copy reactions
                db_query("INSERT IGNORE INTO reel_reactions (reel_id, user_id, type, created_at)
                          SELECT ?, user_id, type, created_at FROM video_reactions WHERE video_id = ?", [$r['id'], $r['id']]);
                          
                // Copy comments
                db_query("INSERT IGNORE INTO reel_comments (reel_id, user_id, parent_id, content, status, created_at)
                          SELECT ?, user_id, parent_id, content, status, created_at FROM comments WHERE video_id = ?", [$r['id'], $r['id']]);
            }
            
            // Move file in file system
            $filename = $r['video_url'];
            if ($filename && !str_starts_with($filename, 'http')) {
                $old_file = $videos_dir . $filename;
                $new_file = $reels_dir . $filename;
                if (is_file($old_file)) {
                    @rename($old_file, $new_file);
                }
            }
            
            // Delete old thumbnail
            $thumb = $r['thumbnail'];
            if ($thumb && !str_starts_with($thumb, 'http') && $thumb !== 'default-thumb.jpg') {
                $thumb_file = $thumbs_dir . $thumb;
                if (is_file($thumb_file)) {
                    @unlink($thumb_file);
                }
            }
        }
        
        // Delete records from videos
        db_query("DELETE FROM videos WHERE is_reel = 1");
    }

    // ── Analytics System migration (2026.06.06.1) ──
    if (!fh_table_exists('analytics_pageviews')) {
        db_query("CREATE TABLE analytics_pageviews (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(64) NOT NULL,
            user_id INT UNSIGNED DEFAULT NULL,
            ip_hash VARCHAR(64) NOT NULL,
            url VARCHAR(255) NOT NULL,
            referer VARCHAR(255) DEFAULT NULL,
            traffic_source VARCHAR(50) NOT NULL DEFAULT 'direct',
            device_type ENUM('desktop', 'mobile', 'tablet') NOT NULL DEFAULT 'desktop',
            os VARCHAR(50) DEFAULT NULL,
            browser VARCHAR(50) DEFAULT NULL,
            country VARCHAR(3) NOT NULL DEFAULT 'US',
            city VARCHAR(100) NOT NULL DEFAULT 'Unknown',
            duration INT UNSIGNED NOT NULL DEFAULT 0,
            is_reel TINYINT(1) NOT NULL DEFAULT 0,
            is_video TINYINT(1) NOT NULL DEFAULT 0,
            content_id INT UNSIGNED DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_created_at (created_at DESC),
            INDEX idx_session (session_id),
            INDEX idx_user (user_id),
            INDEX idx_traffic (traffic_source),
            INDEX idx_device (device_type),
            INDEX idx_country (country)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    if (!fh_table_exists('analytics_daily_stats')) {
        db_query("CREATE TABLE analytics_daily_stats (
            `date` DATE PRIMARY KEY,
            visits INT UNSIGNED NOT NULL DEFAULT 0,
            visitors INT UNSIGNED NOT NULL DEFAULT 0,
            pageviews INT UNSIGNED NOT NULL DEFAULT 0,
            video_views INT UNSIGNED NOT NULL DEFAULT 0,
            reel_views INT UNSIGNED NOT NULL DEFAULT 0,
            avg_duration DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            bounce_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    if (!fh_table_exists('analytics_device_stats')) {
        db_query("CREATE TABLE analytics_device_stats (
            `date` DATE NOT NULL,
            device_type VARCHAR(20) NOT NULL,
            os VARCHAR(50) NOT NULL,
            browser VARCHAR(50) NOT NULL,
            `count` INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (`date`, device_type, os, browser),
            INDEX idx_date (`date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    if (!fh_table_exists('analytics_geo_stats')) {
        db_query("CREATE TABLE analytics_geo_stats (
            `date` DATE NOT NULL,
            country VARCHAR(3) NOT NULL,
            city VARCHAR(100) NOT NULL,
            `count` INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (`date`, country, city),
            INDEX idx_date (`date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    if (!fh_table_exists('analytics_source_stats')) {
        db_query("CREATE TABLE analytics_source_stats (
            `date` DATE NOT NULL,
            source VARCHAR(50) NOT NULL,
            `count` INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (`date`, source),
            INDEX idx_date (`date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    // Add HLS and thumbnail support for reels table
    if (fh_table_exists('reels')) {
        if (!fh_column_exists('reels', 'hls_url')) {
            db_query("ALTER TABLE reels ADD COLUMN hls_url VARCHAR(500) DEFAULT NULL AFTER video_url");
        }
        if (!fh_column_exists('reels', 'thumbnail')) {
            db_query("ALTER TABLE reels ADD COLUMN thumbnail VARCHAR(255) DEFAULT NULL AFTER hls_url");
        }
    }

    // ── All migrations passed — write flag to skip on next request ──
    if (!is_dir($cache_dir)) {
        @mkdir($cache_dir, 0755, true);
    }
    @file_put_contents($flag_file, $migration_version);
}

function fh_aggregate_date(string $date_str): void {
    // 1. Basic metrics (visits, visitors, pageviews, video_views, reel_views, avg_duration)
    $basic = db_fetch("
        SELECT 
            COUNT(*) AS total_pageviews,
            COUNT(DISTINCT session_id) AS total_visits,
            COUNT(DISTINCT ip_hash) AS total_visitors,
            SUM(CASE WHEN is_video = 1 THEN 1 ELSE 0 END) AS total_video_views,
            SUM(CASE WHEN is_reel = 1 THEN 1 ELSE 0 END) AS total_reel_views,
            COALESCE(AVG(duration), 0) AS avg_duration
        FROM analytics_pageviews
        WHERE DATE(created_at) = ?
    ", [$date_str]);

    // 2. Bounce Rate metric
    $bounce = db_fetch("
        SELECT 
            COUNT(DISTINCT session_id) as total_sessions,
            SUM(CASE WHEN pv_count = 1 AND max_dur <= 10 THEN 1 ELSE 0 END) as bounce_sessions
        FROM (
            SELECT session_id, COUNT(*) as pv_count, MAX(duration) as max_dur
            FROM analytics_pageviews
            WHERE DATE(created_at) = ?
            GROUP BY session_id
        ) as session_summaries
    ", [$date_str]);

    $visits = (int)($basic['total_visits'] ?? 0);
    $visitors = (int)($basic['total_visitors'] ?? 0);
    $pageviews = (int)($basic['total_pageviews'] ?? 0);
    $video_views = (int)($basic['total_video_views'] ?? 0);
    $reel_views = (int)($basic['total_reel_views'] ?? 0);
    $avg_duration = (float)($basic['avg_duration'] ?? 0.00);
    
    $total_sessions = (int)($bounce['total_sessions'] ?? 0);
    $bounce_sessions = (int)($bounce['bounce_sessions'] ?? 0);
    $bounce_rate = $total_sessions > 0 ? ($bounce_sessions / $total_sessions) * 100 : 0.00;

    db_query("
        INSERT INTO analytics_daily_stats (date, visits, visitors, pageviews, video_views, reel_views, avg_duration, bounce_rate)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            visits = VALUES(visits),
            visitors = VALUES(visitors),
            pageviews = VALUES(pageviews),
            video_views = VALUES(video_views),
            reel_views = VALUES(reel_views),
            avg_duration = VALUES(avg_duration),
            bounce_rate = VALUES(bounce_rate)
    ", [$date_str, $visits, $visitors, $pageviews, $video_views, $reel_views, $avg_duration, $bounce_rate]);

    // 3. Device breakdown
    db_query("DELETE FROM analytics_device_stats WHERE date = ?", [$date_str]);
    $devices = db_fetchAll("
        SELECT device_type, os, browser, COUNT(*) as count
        FROM analytics_pageviews
        WHERE DATE(created_at) = ?
        GROUP BY device_type, os, browser
    ", [$date_str]);
    foreach ($devices as $row) {
        db_query("
            INSERT INTO analytics_device_stats (date, device_type, os, browser, count)
            VALUES (?, ?, ?, ?, ?)
        ", [$date_str, $row['device_type'], $row['os'] ?: 'Other', $row['browser'] ?: 'Other', $row['count']]);
    }

    // 4. Geo breakdown
    db_query("DELETE FROM analytics_geo_stats WHERE date = ?", [$date_str]);
    $geo = db_fetchAll("
        SELECT country, city, COUNT(*) as count
        FROM analytics_pageviews
        WHERE DATE(created_at) = ?
        GROUP BY country, city
    ", [$date_str]);
    foreach ($geo as $row) {
        db_query("
            INSERT INTO analytics_geo_stats (date, country, city, count)
            VALUES (?, ?, ?, ?)
        ", [$date_str, $row['country'], $row['city'], $row['count']]);
    }

    // 5. Source breakdown
    db_query("DELETE FROM analytics_source_stats WHERE date = ?", [$date_str]);
    $sources = db_fetchAll("
        SELECT traffic_source, COUNT(*) as count
        FROM analytics_pageviews
        WHERE DATE(created_at) = ?
        GROUP BY traffic_source
    ", [$date_str]);
    foreach ($sources as $row) {
        db_query("
            INSERT INTO analytics_source_stats (date, source, count)
            VALUES (?, ?, ?)
        ", [$date_str, $row['traffic_source'], $row['count']]);
    }
}

function fh_run_analytics_aggregation(): void {
    $min_date_row = db_fetch("SELECT MIN(DATE(created_at)) as min_date FROM analytics_pageviews");
    if (!$min_date_row || empty($min_date_row['min_date'])) {
        return;
    }
    
    $start_date = $min_date_row['min_date'];
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    if (strtotime($start_date) > strtotime($yesterday)) {
        return;
    }
    
    $current = new DateTime($start_date);
    $end = new DateTime($yesterday);
    $run_count = 0;
    
    while ($current <= $end && $run_count < 30) {
        $date_str = $current->format('Y-m-d');
        
        $is_yesterday = ($date_str === $yesterday);
        $exists = db_fetch("SELECT 1 FROM analytics_daily_stats WHERE date = ?", [$date_str]);
        
        if (!$exists || $is_yesterday) {
            fh_aggregate_date($date_str);
            $run_count++;
        }
        $current->modify('+1 day');
    }

    // Prune raw logs older than 60 days to keep the database lightweight
    $prune_date = date('Y-m-d H:i:s', strtotime('-60 days'));
    db_query("DELETE FROM analytics_pageviews WHERE created_at < ?", [$prune_date]);
}
