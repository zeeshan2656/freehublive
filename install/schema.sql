-- ============================================================
-- FreeHub.Live — Database Schema
-- ============================================================
CREATE DATABASE IF NOT EXISTS freehub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE freehub;

-- ============================================================
-- USERS (Admin, Affiliate, Creator, Viewer)
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    email       VARCHAR(120) NOT NULL UNIQUE,
    first_name  VARCHAR(50)  DEFAULT NULL,
    last_name   VARCHAR(50)  DEFAULT NULL,
    phone       VARCHAR(20)  DEFAULT NULL,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('admin','affiliate','creator','viewer') NOT NULL DEFAULT 'viewer',
    status      ENUM('active','suspended','pending') NOT NULL DEFAULT 'active',
    avatar      VARCHAR(255) DEFAULT NULL,
    cover_image VARCHAR(255) DEFAULT NULL,
    bio         TEXT         DEFAULT NULL,
    channel_name VARCHAR(100) DEFAULT NULL,
    subscribers INT UNSIGNED NOT NULL DEFAULT 0,
    total_views  BIGINT UNSIGNED NOT NULL DEFAULT 0,
    balance     DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    preferred_currency VARCHAR(3) NOT NULL DEFAULT 'USD',
    total_watch_seconds BIGINT UNSIGNED NOT NULL DEFAULT 0,
    lifetime_watch_earnings DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    ref_code    VARCHAR(20)  UNIQUE DEFAULT NULL,
    referred_by INT UNSIGNED DEFAULT NULL,
    email_verified TINYINT(1) NOT NULL DEFAULT 0,
    last_login  DATETIME     DEFAULT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role   (role),
    INDEX idx_status (status),
    INDEX idx_ref    (ref_code)
) ENGINE=InnoDB;

-- ============================================================
-- CATEGORIES
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(80) NOT NULL UNIQUE,
    slug        VARCHAR(80) NOT NULL UNIQUE,
    icon        VARCHAR(50) DEFAULT NULL,
    color       VARCHAR(7)  DEFAULT '#6366f1',
    description TEXT        DEFAULT NULL,
    image       VARCHAR(255) DEFAULT NULL,
    sort_order  SMALLINT    NOT NULL DEFAULT 0,
    is_active   TINYINT(1)  NOT NULL DEFAULT 1,
    created_at  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- VIDEOS
-- ============================================================
CREATE TABLE IF NOT EXISTS videos (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL,
    category_id   INT UNSIGNED DEFAULT NULL,
    title         VARCHAR(200) NOT NULL,
    slug          VARCHAR(200) NOT NULL UNIQUE,
    description   TEXT         DEFAULT NULL,
    tags          VARCHAR(500) DEFAULT NULL,
    thumbnail     VARCHAR(255) DEFAULT NULL,
    video_url     VARCHAR(500) NOT NULL,
    hls_url       VARCHAR(500) DEFAULT NULL,
    duration      INT UNSIGNED NOT NULL DEFAULT 0,
    file_size     BIGINT UNSIGNED DEFAULT 0,
    resolution    VARCHAR(20)  DEFAULT NULL,
    status        ENUM('draft','pending','published','rejected','processing') NOT NULL DEFAULT 'pending',
    visibility    ENUM('public','unlisted','private') NOT NULL DEFAULT 'public',
    views         BIGINT UNSIGNED NOT NULL DEFAULT 0,
    likes         INT UNSIGNED NOT NULL DEFAULT 0,
    dislikes      INT UNSIGNED NOT NULL DEFAULT 0,
    comments_count INT UNSIGNED NOT NULL DEFAULT 0,
    watch_time    BIGINT UNSIGNED NOT NULL DEFAULT 0,
    revenue       DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    featured      TINYINT(1)  NOT NULL DEFAULT 0,
    allow_comments TINYINT(1) NOT NULL DEFAULT 1,
    scheduled_at  DATETIME    DEFAULT NULL,
    published_at  DATETIME    DEFAULT NULL,
    created_at    DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FULLTEXT KEY ft_search (title, description, tags),
    INDEX idx_user     (user_id),
    INDEX idx_category (category_id),
    INDEX idx_status   (status),
    INDEX idx_views    (views DESC),
    INDEX idx_featured (featured),
    INDEX idx_created  (created_at DESC),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- VIDEO CATEGORIES (many-to-many)
-- ============================================================
CREATE TABLE IF NOT EXISTS video_categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    video_id    INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    UNIQUE KEY uq_vc (video_id, category_id),
    FOREIGN KEY (video_id)    REFERENCES videos(id)     ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- VIDEO VIEWS (per-session tracking)
-- ============================================================
CREATE TABLE IF NOT EXISTS video_views (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    video_id     INT UNSIGNED NOT NULL,
    user_id      INT UNSIGNED DEFAULT NULL,
    affiliate_id INT UNSIGNED DEFAULT NULL,
    ip_hash      VARCHAR(64)  NOT NULL,
    session_id   VARCHAR(64)  DEFAULT NULL,
    watch_seconds INT UNSIGNED NOT NULL DEFAULT 0,
    is_unique    TINYINT(1)   NOT NULL DEFAULT 1,
    ref_code     VARCHAR(20)  DEFAULT NULL,
    country      VARCHAR(3)   DEFAULT NULL,
    device       ENUM('desktop','mobile','tablet') DEFAULT 'desktop',
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_video     (video_id),
    INDEX idx_affiliate (affiliate_id),
    INDEX idx_date      (created_at),
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- AFFILIATE CLICKS
-- ============================================================
CREATE TABLE IF NOT EXISTS affiliate_clicks (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    affiliate_id INT UNSIGNED NOT NULL,
    video_id     INT UNSIGNED DEFAULT NULL,
    ip_hash      VARCHAR(64)  NOT NULL,
    ref_code     VARCHAR(20)  NOT NULL,
    converted    TINYINT(1)   NOT NULL DEFAULT 0,
    country      VARCHAR(3)   DEFAULT NULL,
    device       ENUM('desktop','mobile','tablet') DEFAULT 'desktop',
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_affiliate (affiliate_id),
    INDEX idx_date      (created_at),
    FOREIGN KEY (affiliate_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- COMMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS comments (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    video_id   INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NOT NULL,
    parent_id  INT UNSIGNED DEFAULT NULL,
    content    TEXT         NOT NULL,
    likes      INT UNSIGNED NOT NULL DEFAULT 0,
    is_pinned  TINYINT(1)  NOT NULL DEFAULT 0,
    status     ENUM('visible','hidden','spam') NOT NULL DEFAULT 'visible',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_video (video_id),
    INDEX idx_user  (user_id),
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- LIKES / DISLIKES
-- ============================================================
CREATE TABLE IF NOT EXISTS video_reactions (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    video_id   INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NOT NULL,
    type       ENUM('like','dislike') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_reaction (video_id, user_id),
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- PLAYLISTS
-- ============================================================
CREATE TABLE IF NOT EXISTS playlists (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    title       VARCHAR(150) NOT NULL,
    description TEXT         DEFAULT NULL,
    thumbnail   VARCHAR(255) DEFAULT NULL,
    visibility  ENUM('public','private','unlisted') NOT NULL DEFAULT 'public',
    video_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS playlist_videos (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    playlist_id INT UNSIGNED NOT NULL,
    video_id    INT UNSIGNED NOT NULL,
    sort_order  SMALLINT    NOT NULL DEFAULT 0,
    added_at    DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pv (playlist_id, video_id),
    FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE,
    FOREIGN KEY (video_id)    REFERENCES videos(id)    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SUBSCRIPTIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS subscriptions (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subscriber_id INT UNSIGNED NOT NULL,
    channel_id   INT UNSIGNED NOT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_sub (subscriber_id, channel_id),
    FOREIGN KEY (subscriber_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (channel_id)    REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- WATCH HISTORY
-- ============================================================
CREATE TABLE IF NOT EXISTS watch_history (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,
    video_id     INT UNSIGNED NOT NULL,
    watch_position INT UNSIGNED NOT NULL DEFAULT 0,
    last_watched DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_wh (user_id, video_id),
    INDEX idx_user (user_id),
    FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- WATCH LATER / BOOKMARKS
-- ============================================================
CREATE TABLE IF NOT EXISTS watch_later (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    video_id   INT UNSIGNED NOT NULL,
    added_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_wl (user_id, video_id),
    FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- EARNINGS / TRANSACTIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS earnings (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,
    type         ENUM('video_view','affiliate_click','affiliate_view','payout','bonus','watch_time','ad_revenue') NOT NULL,
    amount       DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    reference_id INT UNSIGNED DEFAULT NULL,
    description  VARCHAR(255) DEFAULT NULL,
    status       ENUM('pending','approved','paid','rejected') NOT NULL DEFAULT 'pending',
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_type (type),
    INDEX idx_date (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- WITHDRAWAL REQUESTS
-- ============================================================
CREATE TABLE IF NOT EXISTS withdrawal_requests (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    amount          DECIMAL(12,4) NOT NULL,
    currency        VARCHAR(3) NOT NULL DEFAULT 'USD',
    payment_method  VARCHAR(80) NOT NULL,
    payment_details TEXT NOT NULL,
    country         VARCHAR(80) DEFAULT NULL,
    status          ENUM('pending','processing','paid','rejected') NOT NULL DEFAULT 'pending',
    admin_note      TEXT DEFAULT NULL,
    due_by          DATE DEFAULT NULL,
    processed_at    DATETIME DEFAULT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SITE SETTINGS
-- ============================================================
CREATE TABLE IF NOT EXISTS settings (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key`      VARCHAR(80)  NOT NULL UNIQUE,
    `value`    TEXT         DEFAULT NULL,
    `group`    VARCHAR(40)  NOT NULL DEFAULT 'general',
    updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- NOTIFICATIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    type       VARCHAR(40)  NOT NULL,
    title      VARCHAR(150) NOT NULL,
    message    TEXT         DEFAULT NULL,
    url        VARCHAR(255) DEFAULT NULL,
    is_read    TINYINT(1)   NOT NULL DEFAULT 0,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user   (user_id, is_read),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- ADS
-- ============================================================
CREATE TABLE IF NOT EXISTS ads (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title         VARCHAR(150) NOT NULL,
    content_type  ENUM('image','html','banner') NOT NULL DEFAULT 'image',
    content       TEXT         DEFAULT NULL,
    image_url     VARCHAR(500) DEFAULT NULL,
    target_url    VARCHAR(500) DEFAULT NULL,
    placement     INT UNSIGNED NOT NULL DEFAULT 0,
    device_target ENUM('all','desktop','mobile') NOT NULL DEFAULT 'all',
    ad_width      SMALLINT UNSIGNED DEFAULT NULL,
    ad_height     SMALLINT UNSIGNED DEFAULT NULL,
    position_after INT UNSIGNED NOT NULL DEFAULT 1  COMMENT 'Show after Nth section',
    impressions   INT UNSIGNED NOT NULL DEFAULT 0,
    clicks        INT UNSIGNED NOT NULL DEFAULT 0,
    is_active     TINYINT(1)  NOT NULL DEFAULT 1,
    start_date    DATE         DEFAULT NULL,
    end_date      DATE         DEFAULT NULL,
    sort_order    SMALLINT    NOT NULL DEFAULT 0,
    created_at    DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active    (is_active),
    INDEX idx_placement (placement),
    INDEX idx_device    (device_target)
) ENGINE=InnoDB;

-- ============================================================
-- DEFAULT DATA
-- ============================================================
INSERT IGNORE INTO users (username, email, password, role, status, email_verified, channel_name) VALUES
('admin', 'admin@freehub.live', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', 1, 'FreeHub Official');

INSERT IGNORE INTO categories (name, slug, icon, color, sort_order) VALUES
('Gaming',       'gaming',       'gamepad-2',       '#6366f1', 1),
('Music',        'music',        'music',           '#ec4899', 2),
('Technology',   'technology',   'cpu',             '#06b6d4', 3),
('Education',    'education',    'graduation-cap',  '#f59e0b', 4),
('Sports',       'sports',       'trophy',          '#10b981', 5),
('Entertainment','entertainment','film',            '#f97316', 6),
('Lifestyle',    'lifestyle',    'heart',           '#a855f7', 7),
('News',         'news',         'newspaper',       '#64748b', 8),
('Comedy',       'comedy',       'laugh',           '#eab308', 9),
('Travel',       'travel',       'map-pin',         '#14b8a6', 10);

INSERT IGNORE INTO settings (`key`, `value`, `group`) VALUES
('site_name',      'FreeHub',                 'general'),
('site_tagline',   'Watch. Share. Earn.',     'general'),
('site_logo',      '',                        'general'),
('active_theme',   'dark-minimal',            'appearance'),
('primary_color',  '#6366f1',                 'appearance'),
('watch_time_rate_usd', '0.50',               'earnings'),
('min_withdrawal',      '25.00',              'earnings'),
('min_payout',          '25.00',              'earnings'),
('ad_revenue_per_click','0.05',               'earnings'),
('currency_rates_json', '{"USD":1,"INR":83.5,"PKR":278,"EUR":0.92,"GBP":0.79,"CAD":1.36,"AUD":1.52,"BDT":110,"AED":3.67,"SAR":3.75}', 'earnings'),
('schema_version',      '2',                  'general'),
('allow_register', '1',                       'auth'),
('maintenance',    '0',                       'general');
