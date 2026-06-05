-- ============================================================
-- FreeHub.Live — Advanced Analytics Database Schema
-- ============================================================

CREATE TABLE IF NOT EXISTS analytics_pageviews (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS analytics_daily_stats (
    `date` DATE PRIMARY KEY,
    visits INT UNSIGNED NOT NULL DEFAULT 0,
    visitors INT UNSIGNED NOT NULL DEFAULT 0,
    pageviews INT UNSIGNED NOT NULL DEFAULT 0,
    video_views INT UNSIGNED NOT NULL DEFAULT 0,
    reel_views INT UNSIGNED NOT NULL DEFAULT 0,
    avg_duration DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    bounce_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS analytics_device_stats (
    `date` DATE NOT NULL,
    device_type VARCHAR(20) NOT NULL,
    os VARCHAR(50) NOT NULL,
    browser VARCHAR(50) NOT NULL,
    `count` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`date`, device_type, os, browser),
    INDEX idx_date (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS analytics_geo_stats (
    `date` DATE NOT NULL,
    country VARCHAR(3) NOT NULL,
    city VARCHAR(100) NOT NULL,
    `count` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`date`, country, city),
    INDEX idx_date (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS analytics_source_stats (
    `date` DATE NOT NULL,
    source VARCHAR(50) NOT NULL,
    `count` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`date`, source),
    INDEX idx_date (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
