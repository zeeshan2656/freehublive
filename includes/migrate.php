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
    $migration_version = '2026.05.28.2';
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

    // ── Pages CMS Table ─────────────────────────────────────
    if (!fh_table_exists('pages')) {
        db_query("CREATE TABLE pages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(150) NOT NULL,
            slug VARCHAR(150) NOT NULL UNIQUE,
            content MEDIUMTEXT,
            is_published TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $initial_pages = [
            [
                'title' => 'Home',
                'slug' => 'home',
                'content' => '<h1>Welcome to FreeHub</h1><p>Watch. Share. Earn.</p><p>FreeHub is the premier next-generation video sharing platform where creators and viewers connect, collaborate, and share rewards. Explore trending topics, support your favorite video creators, or broadcast your own channel to start building your audience today.</p>'
            ],
            [
                'title' => 'About Us',
                'slug' => 'about-us',
                'content' => '<h1>About Us</h1><p>Welcome to FreeHub! We are a dynamic, community-driven video sharing platform designed to empower content creators and engage audiences around the globe.</p><h2>Our Mission</h2><p>Our mission is simple: to democratize online entertainment and monetization. We believe that everyone who contributes to the platform—whether by creating compelling content or actively viewing videos—deserves to share in its success.</p><h2>How It Works</h2><ul><li><strong>Creators:</strong> Upload high-definition videos, engage with subscribers, and monetize content directly based on watch duration and advertising engagement.</li><li><strong>Viewers:</strong> Watch interesting videos, discover new channels, and earn active viewing rewards.</li></ul>'
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'content' => '<h1>Privacy Policy</h1><p>Last updated: May 2026</p><p>Your privacy is of paramount importance to us. This Privacy Policy details the types of personal data we collect, how we use it, and the strict security measures we implement to protect your information.</p><h2>1. Information We Collect</h2><p>We collect information to provide better services to all our users, including account details, viewing histories, interaction records, and preferred payment preferences.</p><h2>2. How We Use Information</h2><p>We use the collected information to manage user authentication, accurately calculate viewer rewards and creator earnings, process secure withdrawals, and prevent fraudulent activity on the platform.</p>'
            ],
            [
                'title' => 'Disclaimer',
                'slug' => 'disclaimer',
                'content' => '<h1>Disclaimer</h1><p>Please read this disclaimer carefully before using the platform.</p><h2>No Earnings Guarantees</h2><p>Any earning statistics, rate tables, or success stories displayed on the platform are illustrative examples of potential outcomes. Actual creator and viewer earnings are not guaranteed and will vary based on user engagement, geographic location, adherence to community rules, and overall platform ad revenue.</p><h2>Third-Party Ads</h2><p>We display third-party advertisements. We are not responsible for the contents, products, or claims made in these external advertisements.</p>'
            ],
            [
                'title' => 'Contact Us',
                'slug' => 'contact-us',
                'content' => '<h1>Contact Us</h1><p>Have questions, technical issues, or partnership proposals? The FreeHub support team is here to assist you.</p><h2>Get in Touch</h2><p>You can contact our support department directly by sending an email to:</p><p><strong>Email:</strong> support@freehub.live</p><p>Our business hours are Monday through Friday, 9:00 AM to 6:00 PM (EST). We aim to respond to all inquiries within 24 to 48 hours.</p>'
            ],
            [
                'title' => 'Creator Page',
                'slug' => 'creator-page',
                'content' => '<h1>Creator Program</h1><p>Welcome to the FreeHub Creator Program. Broadcast your passion, build a loyal fanbase, and generate competitive revenue from your content.</p><h2>How to Get Started</h2><ol><li><strong>Setup Channel:</strong> Register or update your account role to Creator and define your unique channel name.</li><li><strong>Upload Original Content:</strong> Upload videos in standard high-definition formats. Keep titles descriptive and thumbnails engaging.</li><li><strong>Promote:</strong> Share your videos across social channels using your custom referral link to drive initial traction.</li></ol><h2>Rules & Policies</h2><ul><li><strong>Originality:</strong> Only upload videos that you own or have full authorization to distribute. Plagiarism will lead to channel termination.</li><li><strong>Quality:</strong> Maintain clear audio and visual standards. Poor quality videos may be unlisted.</li><li><strong>Prohibited Content:</strong> Content displaying violence, harassment, hate speech, or explicit material is strictly forbidden.</li></ul>'
            ],
            [
                'title' => 'Viewer Page',
                'slug' => 'viewer-page',
                'content' => '<h1>Viewer Rewards</h1><p>FreeHub values your time and attention. That is why we pay you to watch videos!</p><h2>How to Watch & Earn</h2><ul><li><strong>Stay Active:</strong> Earn rewards for every minute you spend watching authorized videos on our platform.</li><li><strong>Refer Friends:</strong> Share your referral code. For every creator or viewer you introduce to FreeHub, you earn a percentage of their earnings for life!</li></ul><h2>Viewer Rules & Fair Play</h2><p>To keep the ecosystem fair for creators and advertisers, we enforce the following rules:</p><ul><li>No botting, scripting, automatic page reloads, or background playing tools.</li><li>Only watch one video at a time. Multi-tabbing to inflate watch time is disallowed.</li><li>Use a single account. Creating duplicate accounts to claim rewards will result in an immediate and permanent ban.</li></ul>'
            ],
            [
                'title' => 'Payment & Payout Policy',
                'slug' => 'payment-policy',
                'content' => '<h1>Payment & Payout Policy</h1><p>At FreeHub, we ensure secure, transparent, and timely payouts for all eligible creators and viewers.</p><h2>Withdrawal Guidelines</h2><table border=\"1\" style=\"border-collapse: collapse; width: 100%; border-color: var(--border);\"><thead><tr><th style=\"padding: 8px; text-align: left;\">Payment Parameter</th><th style=\"padding: 8px; text-align: left;\">Detail / Limit</th></tr></thead><tbody><tr><td style=\"padding: 8px;\">Minimum Threshold</td><td style=\"padding: 8px;\">$25.00 USD (or local currency equivalent)</td></tr><tr><td style=\"padding: 8px;\">Processing Time</td><td style=\"padding: 8px;\">Paid within 7 business days from approval date</td></tr><tr><td style=\"padding: 8px;\">Supported Channels</td><td style=\"padding: 8px;\">PayPal, Direct Bank Transfer, Cryptocurrency (USDT)</td></tr></tbody></table><p style=\"margin-top: 12px;\">All withdrawal requests undergo manual audit by the administration team to verify traffic authenticity and rule compliance.</p>'
            ],
            [
                'title' => 'Terms & Conditions',
                'slug' => 'terms-conditions',
                'content' => '<h1>Terms & Conditions</h1><p>These Terms and Conditions govern your access to and use of FreeHub. By creating an account or browsing the platform, you fully accept these terms.</p><h2>1. Account Registration</h2><p>You must provide accurate, complete, and up-to-date information during signup. You are solely responsible for maintaining account confidentiality.</p><h2>2. Intellectual Property</h2><p>All trademarks, logos, and system layouts remain the exclusive property of FreeHub. Uploaded content remains the property of the creator, who grants FreeHub a worldwide license to host and stream it.</p>'
            ],
            [
                'title' => 'Community Guidelines',
                'slug' => 'community-guidelines',
                'content' => '<h1>Community Guidelines</h1><p>Our guidelines are designed to foster a safe, positive, and constructive environment for all users on FreeHub.</p><h2>Be Respectful</h2><p>We do not tolerate harassment, bullying, hate speech, or discriminatory language based on race, gender, religion, or orientation.</p><h2>Content Safety</h2><p>Keep content safe for our diverse audience. Avoid posting graphic violence, self-harm material, or illegal activities.</p>'
            ]
        ];

        foreach ($initial_pages as $p) {
            db_insert('pages', $p);
        }
    }

    if (fh_table_exists('pages')) {
        if (!fh_column_exists('pages', 'meta_title')) {
            db_query("ALTER TABLE pages ADD COLUMN meta_title VARCHAR(255) DEFAULT NULL AFTER content");
        }
        if (!fh_column_exists('pages', 'meta_desc')) {
            db_query("ALTER TABLE pages ADD COLUMN meta_desc TEXT DEFAULT NULL AFTER meta_title");
        }
        if (!fh_column_exists('pages::temp', 'meta_keywords')) { // Just checking column name uniqueness
            if (!fh_column_exists('pages', 'meta_keywords')) {
                db_query("ALTER TABLE pages ADD COLUMN meta_keywords VARCHAR(255) DEFAULT NULL AFTER meta_desc");
            }
        }
        if (!fh_column_exists('pages', 'status')) {
            db_query("ALTER TABLE pages ADD COLUMN status ENUM('published', 'draft', 'private', 'scheduled') NOT NULL DEFAULT 'published' AFTER meta_keywords");
            if (fh_column_exists('pages', 'is_published')) {
                db_query("UPDATE pages SET status = 'draft' WHERE is_published = 0");
            }
        }
        if (!fh_column_exists('pages', 'publish_at')) {
            db_query("ALTER TABLE pages ADD COLUMN publish_at DATETIME DEFAULT NULL AFTER status");
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

    // 6. Add default placements for watch page
    if (fh_table_exists('ad_placements')) {
        $watch_placements = [
            ['watch_sidebar', 'Watch Page Sidebar Banner', 'all'],
            ['watch_below_player', 'Watch Page Below Player Banner', 'all']
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

    // ── All migrations passed — write flag to skip on next request ──
    if (!is_dir($cache_dir)) {
        @mkdir($cache_dir, 0755, true);
    }
    @file_put_contents($flag_file, $migration_version);
}
