<?php
// ============================================================
// FreeHub.Live — Watch-time earnings, currency, channels
// ============================================================

function fh_currencies(): array {
    return [
        'USD' => ['label' => 'US Dollar', 'symbol' => '$'],
        'INR' => ['label' => 'Indian Rupee', 'symbol' => '₹'],
        'PKR' => ['label' => 'Pakistani Rupee', 'symbol' => '₨'],
        'EUR' => ['label' => 'Euro', 'symbol' => '€'],
        'GBP' => ['label' => 'British Pound', 'symbol' => '£'],
        'CAD' => ['label' => 'Canadian Dollar', 'symbol' => 'C$'],
        'AUD' => ['label' => 'Australian Dollar', 'symbol' => 'A$'],
        'BDT' => ['label' => 'Bangladeshi Taka', 'symbol' => '৳'],
        'AED' => ['label' => 'UAE Dirham', 'symbol' => 'د.إ'],
        'SAR' => ['label' => 'Saudi Riyal', 'symbol' => '﷼'],
    ];
}

function fh_currency_rates(): array {
    $json = setting('currency_rates_json', '');
    $rates = json_decode($json, true);
    if (!is_array($rates) || empty($rates['USD'])) {
        $rates = ['USD' => 1, 'INR' => 83.5, 'PKR' => 278, 'EUR' => 0.92, 'GBP' => 0.79];
    }
    $rates['USD'] = 1.0;
    return $rates;
}

function fh_convert_from_usd(float $usd, string $currency): float {
    $rates = fh_currency_rates();
    $rate = (float)($rates[$currency] ?? 1);
    return $usd * $rate;
}

/** Active display currency for the current visitor. */
function fh_user_currency(): string {
    $allowed = fh_currencies();
    $code    = 'USD';

    if (is_logged_in()) {
        $preview_uid = (int)($_GET['uid'] ?? 0);

        if ($preview_uid > 0 && is_admin()) {
            $row = db_fetch('SELECT preferred_currency FROM users WHERE id=?', [$preview_uid]);
            $code = strtoupper($row['preferred_currency'] ?? 'USD');
        } else {
            $code = strtoupper(auth_user()['preferred_currency'] ?? '');
            if ($code === '' || !isset($allowed[$code])) {
                $row = db_fetch(
                    'SELECT preferred_currency FROM users WHERE id=?',
                    [(int)auth_user()['id']]
                );
                $code = strtoupper($row['preferred_currency'] ?? 'USD');
                $_SESSION['user']['preferred_currency'] = $code;
            }
        }
    } else {
        $code = strtoupper($_COOKIE['fh_currency'] ?? $_SESSION['guest_currency'] ?? 'USD');
    }

    return isset($allowed[$code]) ? $code : 'USD';
}

/** Set display currency (DB for logged-in users, cookie for everyone). */
function fh_set_user_currency(string $code): bool {
    $code      = strtoupper(trim($code));
    $allowed   = fh_currencies();
    if (!isset($allowed[$code])) {
        return false;
    }

    if (is_logged_in()) {
        $uid = (int)auth_user()['id'];
        db_update('users', ['preferred_currency' => $code], 'id=?', [$uid]);
        $_SESSION['user']['preferred_currency'] = $code;
    } else {
        $_SESSION['guest_currency'] = $code;
    }

    setcookie('fh_currency', $code, [
        'expires'  => time() + 86400 * 365,
        'path'     => '/',
        'secure'   => false,
        'httponly' => false,
        'samesite' => 'Lax',
    ]);

    return true;
}

function fh_format_money(float $usdAmount, ?string $currency = null, int $decimals = 2): string {
    $currency = strtoupper($currency ?? fh_user_currency());
    $currencies = fh_currencies();
    $symbol = $currencies[$currency]['symbol'] ?? $currency . ' ';
    $local = fh_convert_from_usd($usdAmount, $currency);
    return $symbol . number_format($local, $decimals);
}

function fh_watch_rate_usd(): float {
    // Viewer (Watch & Earn) rate per hour
    return max(0, (float)setting('viewer_rate_usd', setting('watch_time_rate_usd', '0.50')));
}

function fh_creator_watch_rate_usd(): float {
    // Creator rate per hour of watch time generated on their videos
    return max(0, (float)setting('creator_rate_usd', setting('watch_time_rate_usd', '0.50')));
}

function fh_min_withdrawal_usd(int $userId = 0): float {
    if ($userId > 0) {
        $user = db_fetch("SELECT role FROM users WHERE id=?", [$userId]);
        if ($user && $user['role'] === 'creator') {
            return max(0, (float)setting('min_withdrawal_creator', setting('min_withdrawal', '25.00')));
        }
    }
    return max(0, (float)setting('min_withdrawal_viewer', setting('min_withdrawal', '25.00')));
}

function fh_get_admin_user(): ?array {
    return db_fetch("SELECT * FROM users WHERE role='admin' ORDER BY id ASC LIMIT 1");
}

function fh_admin_count(): int {
    return db_count('users', "role='admin'");
}

/** Ensure every user has a unique channel display name. */
function ensure_user_channel(int $userId, ?string $fallbackName = null): void {
    $user = db_fetch("SELECT id, username, channel_name FROM users WHERE id=?", [$userId]);
    if (!$user || !empty($user['channel_name'])) return;
    $name = $fallbackName ?: $user['username'];
    db_update('users', ['channel_name' => $name], 'id=?', [$userId]);
}

/** Sum of all approved watch-time earnings (USD). */
function fh_lifetime_watch_earnings_usd(int $userId): float {
    $user = db_fetch(
        "SELECT lifetime_watch_earnings, total_watch_seconds FROM users WHERE id=?",
        [$userId]
    );
    if (!$user) return 0.0;

    $cached = (float)($user['lifetime_watch_earnings'] ?? 0);
    $ledger = (float)(db_fetch(
        "SELECT COALESCE(SUM(amount), 0) AS t FROM earnings
         WHERE user_id=? AND type='watch_time' AND status IN ('approved','paid')",
        [$userId]
    )['t'] ?? 0);

    $lifetime = max($cached, $ledger);

    // Keep column in sync if ledger is ahead
    if ($ledger > $cached + 0.000001) {
        db_update('users', ['lifetime_watch_earnings' => $ledger], 'id=?', [$userId]);
        $lifetime = $ledger;
    }

    return round($lifetime, 6);
}

/** Credit USD balance and log approved watch-time earning. */
function fh_credit_user(float $usd, int $userId, string $description, ?int $referenceId = null): void {
    if ($usd <= 0 || $userId < 1) return;
    // Admin cannot earn from the platform
    $userRow = db_fetch("SELECT role, status FROM users WHERE id=?", [$userId]);
    if (!$userRow) return;
    if (($userRow['role'] ?? '') === 'admin') return;
    if (($userRow['status'] ?? 'pending') !== 'active') return;
    $amount = round($usd, 6);
    db_insert('earnings', [
        'user_id'      => $userId,
        'type'         => 'watch_time',
        'amount'       => $amount,
        'reference_id' => $referenceId,
        'description'  => $description,
        'status'       => 'approved',
    ]);
    db_query(
        "UPDATE users SET balance = balance + ?, lifetime_watch_earnings = lifetime_watch_earnings + ? WHERE id = ?",
        [$amount, $amount, $userId]
    );
    if (is_logged_in() && (int)auth_user()['id'] === $userId) {
        $_SESSION['user']['balance'] = (float)(db_fetch("SELECT balance FROM users WHERE id=?", [$userId])['balance'] ?? 0);
    }
}

function fh_credit_admin_ad_revenue(int $adId): void {
    $admin = fh_get_admin_user();
    if (!$admin) return;
    $usd = max(0, (float)setting('ad_revenue_per_click', '0.05'));
    if ($usd <= 0) return;
    db_insert('earnings', [
        'user_id'      => $admin['id'],
        'type'         => 'ad_revenue',
        'amount'       => $usd,
        'reference_id' => $adId,
        'description'  => 'Ad click revenue',
        'status'       => 'approved',
    ]);
    db_query("UPDATE users SET balance = balance + ? WHERE id = ?", [$usd, $admin['id']]);
}

/**
 * Process watch-time heartbeat: update stats and credit viewer + creator.
 */
function fh_process_watch_time(int $viewId, int $videoId, int $seconds, bool $isPlaying): array {
    $seconds = max(1, min(30, $seconds));
    if (!$isPlaying) {
        return ['credited' => 0, 'seconds' => 0];
    }

    $view = db_fetch(
        "SELECT vv.*, v.user_id AS creator_id, v.title
     FROM video_views vv JOIN videos v ON v.id = vv.video_id
     WHERE vv.id = ? AND vv.video_id = ?",
        [$viewId, $videoId]
    );
    if (!$view) {
        return ['error' => 'invalid_session'];
    }

    // Cap per view session at 4 hours credited
    $maxSession = 14400;
    if ((int)$view['watch_seconds'] >= $maxSession) {
        return ['credited' => 0, 'seconds' => 0, 'capped' => true];
    }
    $seconds = min($seconds, $maxSession - (int)$view['watch_seconds']);

    db_query(
        "UPDATE video_views SET watch_seconds = watch_seconds + ? WHERE id = ?",
        [$seconds, $viewId]
    );
    db_query(
        "UPDATE videos SET watch_time = watch_time + ? WHERE id = ?",
        [$seconds, $videoId]
    );

    $viewerId = $view['user_id'] ? (int)$view['user_id'] : null;
    $viewerStatus = 'pending';
    if ($viewerId) {
        $viewerRow = db_fetch("SELECT status FROM users WHERE id=?", [$viewerId]);
        $viewerStatus = $viewerRow['status'] ?? 'pending';
    }

    if ($viewerId && $viewerStatus === 'active') {
        db_query(
            "UPDATE users SET total_watch_seconds = total_watch_seconds + ? WHERE id = ?",
            [$seconds, $viewerId]
        );
        db_query(
            "INSERT INTO watch_history (user_id, video_id, watch_position, last_watched)
         VALUES (?,?,?,NOW())
         ON DUPLICATE KEY UPDATE watch_position = watch_position + VALUES(watch_position),
         last_watched = NOW()",
            [$viewerId, $videoId, $seconds]
        );
    }

    $viewerRate  = fh_watch_rate_usd();
    $creatorRate = fh_creator_watch_rate_usd();
    $creatorId = (int)$view['creator_id'];
    $creatorStatus = 'pending';
    if ($creatorId > 0) {
        $creatorRow = db_fetch("SELECT status FROM users WHERE id=?", [$creatorId]);
        $creatorStatus = $creatorRow['status'] ?? 'pending';
    }

    $affiliateId = $view['affiliate_id'] ? (int)$view['affiliate_id'] : null;
    $affiliateStatus = 'pending';
    if ($affiliateId > 0) {
        $affiliateRow = db_fetch("SELECT status FROM users WHERE id=?", [$affiliateId]);
        $affiliateStatus = $affiliateRow['status'] ?? 'pending';
    }

    $refWatchRate = max(0, (float)setting('referral_watch_rate_usd', '0.10'));
    $credited = 0;

    if ($viewerId && $viewerRate > 0 && $viewerStatus === 'active') {
        // Disabled watch-time payouts: Viewer
        /*
        $viewerUsd = ($seconds / 3600) * $viewerRate;
        fh_credit_user($viewerUsd, $viewerId, "Watch time: {$seconds}s on video #{$videoId}", $viewId);
        $credited += $viewerUsd;
        */
    }
    if ($creatorId > 0 && $creatorRate > 0 && $creatorId !== $viewerId && $creatorStatus === 'active' && ($viewerId === null || $viewerStatus === 'active')) {
        // Disabled watch-time payouts: Creator
        /*
        $creatorUsd = ($seconds / 3600) * $creatorRate;
        fh_credit_user($creatorUsd, $creatorId, "Creator watch time: {$seconds}s on video #{$videoId}", $viewId);
        db_query('UPDATE videos SET revenue = revenue + ? WHERE id = ?', [$creatorUsd, $videoId]);
        $credited += $creatorUsd;
        */
    }
    if ($affiliateId > 0 && $refWatchRate > 0 && $affiliateId !== $viewerId && $affiliateId !== $creatorId && $affiliateStatus === 'active' && ($viewerId === null || $viewerStatus === 'active')) {
        // Disabled watch-time payouts: Affiliate
        /*
        $affUsd = ($seconds / 3600) * $refWatchRate;
        $amount = round($affUsd, 6);
        if ($amount > 0) {
            db_insert('earnings', [
                'user_id'      => $affiliateId,
                'type'         => 'referral',
                'amount'       => $amount,
                'reference_id' => $viewId,
                'description'  => "Referral watch time: {$seconds}s on video #{$videoId}",
                'status'       => 'approved',
            ]);
            db_query(
                "UPDATE users SET balance = balance + ? WHERE id = ?",
                [$amount, $affiliateId]
            );
            $credited += $affUsd;
        }
        */
    }

    return [
        'credited'     => 0.0,
        'seconds'      => $seconds,
        'viewer_rate'  => $viewerRate,
        'creator_rate' => $creatorRate,
    ];
}

/** Credit USD balance and log approved ad earning (impression or click). */
function fh_credit_ad_earnings(int $userId, float $amount, string $userRole, string $eventType, int $adId, ?int $videoId = null, string $placement = ''): void {
    if ($amount <= 0 || $userId < 1) return;
    $userRow = db_fetch("SELECT role, status FROM users WHERE id=?", [$userId]);
    if (!$userRow) return;
    if (($userRow['role'] ?? '') === 'admin') return;
    if (($userRow['status'] ?? 'pending') !== 'active') return;
    
    $amount = round($amount, 6);
    $type = $eventType === 'impression' ? 'ad_impression' : 'ad_click';
    $desc = ucfirst($userRole) . " ad " . $eventType . " on " . ($videoId ? "video #{$videoId}" : "page") . " (Ad #{$adId})";
    
    db_insert('earnings', [
        'user_id'      => $userId,
        'type'         => $type,
        'amount'       => $amount,
        'reference_id' => $adId,
        'placement'    => $placement,
        'description'  => $desc,
        'status'       => 'approved',
    ]);
    
    db_query(
        "UPDATE users SET balance = balance + ?, lifetime_ad_earnings = lifetime_ad_earnings + ? WHERE id = ?",
        [$amount, $amount, $userId]
    );
    
    if (is_logged_in() && (int)auth_user()['id'] === $userId) {
        $_SESSION['user']['balance'] = (float)(db_fetch("SELECT balance FROM users WHERE id=?", [$userId])['balance'] ?? 0);
    }
}

/**
 * Track an ad impression or click with duplicate/fake protection and award payouts.
 */
function fh_track_ad_event(int $adId, string $eventType, ?int $videoId = null, string $placement = ''): bool {
    // VPN protection - do not count or log anything if VPN is active
    if (fh_is_vpn_active()) {
        return false;
    }

    $adId = (int)$adId;
    if (!$adId) return false;
    $eventType = ($eventType === 'click') ? 'click' : 'impression';
    
    $ad = db_fetch("SELECT id FROM ads WHERE id=?", [$adId]);
    if (!$ad) return false;

    // Eligibility Checks
    $viewer_placements = array_filter(array_map('trim', explode(',', setting('viewer_eligible_placements', ''))), 'strlen');
    $creator_placements = array_filter(array_map('trim', explode(',', setting('creator_eligible_placements', ''))), 'strlen');

    $viewer_eligible = in_array((string)$placement, $viewer_placements);
    $creator_eligible = in_array((string)$placement, $creator_placements);

    // Skip credit / logs entirely if placement is not eligible for both
    if (!$viewer_eligible && !$creator_eligible) {
        return false;
    }
    
    $ipHash = hash_ip(get_ip());
    $viewerId = is_logged_in() ? (int)auth_user()['id'] : null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    // Fraud/Spam Protection: 10-minute duplicate check per IP/user, per ad, per event type
    if ($viewerId) {
        $recent = db_fetch(
            "SELECT id FROM ad_logs 
             WHERE (viewer_id = ? OR ip_hash = ?) AND ad_id = ? AND type = ? AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)",
            [$viewerId, $ipHash, $adId, $eventType]
        );
    } else {
        $recent = db_fetch(
            "SELECT id FROM ad_logs 
             WHERE ip_hash = ? AND ad_id = ? AND type = ? AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)",
            [$ipHash, $adId, $eventType]
        );
    }
    
    if ($recent) {
        // Prevent duplicate earning/payouts and fake counts
        return false;
    }

    // Calculate Payouts per single event using thousand-based CPM/CPC-1000 logic
    $creatorEarning = 0.0;
    $viewerEarning  = 0.0;
    
    // Find creator of the video
    $creatorId = null;
    if ($videoId > 0) {
        $video = db_fetch("SELECT user_id FROM videos WHERE id=?", [$videoId]);
        if ($video) {
            $creatorId = (int)$video['user_id'];
        }
    }
    
    // Only pay if event is on a single video page ($videoId exists)
    if ($videoId > 0) {
        if ($creator_eligible) {
            $cpm = (float)setting('creator_cpm', '1.00');
            $cpc = (float)setting('creator_cpc', '5.00');
            $creatorEarning = ($eventType === 'impression') ? ($cpm / 1000.0) : ($cpc / 1000.0);
        }
        if ($viewer_eligible) {
            $cpm = (float)setting('viewer_cpm', '0.50');
            $cpc = (float)setting('viewer_cpc', '2.00');
            $viewerEarning = ($eventType === 'impression') ? ($cpm / 1000.0) : ($cpc / 1000.0);
        }
    }
    
    // Perform updates
    // Increment global ad stats
    if ($eventType === 'impression') {
        db_query("UPDATE ads SET impressions = impressions + 1 WHERE id = ?", [$adId]);
    } else {
        db_query("UPDATE ads SET clicks = clicks + 1 WHERE id = ?", [$adId]);
    }
    
    // Increment video-level ad stats
    if ($videoId > 0) {
        if ($eventType === 'impression') {
            db_query("UPDATE videos SET ad_impressions = ad_impressions + 1 WHERE id = ?", [$videoId]);
        } else {
            db_query("UPDATE videos SET ad_clicks = ad_clicks + 1 WHERE id = ?", [$videoId]);
        }
    }
    
    // Increment user-level stats (impressions/clicks count)
    if ($viewerId) {
        if ($eventType === 'impression') {
            db_query("UPDATE users SET total_ad_impressions = total_ad_impressions + 1 WHERE id = ?", [$viewerId]);
        } else {
            db_query("UPDATE users SET total_ad_clicks = total_ad_clicks + 1 WHERE id = ?", [$viewerId]);
        }
    }
    if ($creatorId && $creatorId !== $viewerId) {
        if ($eventType === 'impression') {
            db_query("UPDATE users SET total_ad_impressions = total_ad_impressions + 1 WHERE id = ?", [$creatorId]);
        } else {
            db_query("UPDATE users SET total_ad_clicks = total_ad_clicks + 1 WHERE id = ?", [$creatorId]);
        }
    }
    
    // Distribute payouts (only if active & not admin)
    if ($creatorId > 0 && $creatorEarning > 0) {
        fh_credit_ad_earnings($creatorId, $creatorEarning, 'creator', $eventType, $adId, $videoId, $placement);
    }
    if ($viewerId > 0 && $viewerEarning > 0) {
        fh_credit_ad_earnings($viewerId, $viewerEarning, 'viewer', $eventType, $adId, $videoId, $placement);
    }
    
    // Write to ad_logs
    db_insert('ad_logs', [
        'ad_id'            => $adId,
        'video_id'         => $videoId ?: null,
        'viewer_id'        => $viewerId ?: null,
        'creator_id'       => $creatorId ?: null,
        'type'             => $eventType,
        'placement'        => $placement,
        'ip_hash'          => $ipHash,
        'user_agent'       => $userAgent,
        'earnings_viewer'  => $viewerEarning,
        'earnings_creator' => $creatorEarning,
    ]);
    return true;
}

/**
 * Per-video creator earnings (USD) for the logged-in creator's own uploads.
 *
 * @param int[] $videoIds
 * @return array<int,float> video_id => usd
 */
function fh_creator_video_earnings_map(int $creatorId, array $videoIds): array {
    if ($creatorId < 1 || empty($videoIds)) {
        return [];
    }
    $videoIds = array_values(array_unique(array_filter(array_map('intval', $videoIds))));
    if (empty($videoIds)) {
        return [];
    }

    $c_placements = array_filter(array_map('trim', explode(',', setting('creator_eligible_placements', ''))), 'strlen');
    if (empty($c_placements)) {
        $map = [];
        foreach ($videoIds as $vid) {
            $map[(int)$vid] = 0.0;
        }
        return $map;
    }

    $place_placeholders = implode(',', array_fill(0, count($c_placements), '?'));
    $video_placeholders = implode(',', array_fill(0, count($videoIds), '?'));
    $params = array_merge([$creatorId], $c_placements, [$creatorId], $videoIds);

    $rows = db_fetchAll(
        "SELECT v.id AS video_id,
                COALESCE((
                    SELECT SUM(al.earnings_creator)
                    FROM ad_logs al
                    WHERE al.video_id = v.id
                      AND al.creator_id = ?
                      AND al.placement IN ($place_placeholders)
                ), 0) AS earned
         FROM videos v
         WHERE v.user_id = ? AND v.id IN ($video_placeholders)",
        $params
    );

    $map = [];
    foreach ($rows as $r) {
        $map[(int)$r['video_id']] = round((float)$r['earned'], 4);
    }
    return $map;
}

function fh_user_watch_stats(int $userId): array {
    $user = db_fetch(
        "SELECT total_watch_seconds, balance, preferred_currency, lifetime_watch_earnings FROM users WHERE id=?",
        [$userId]
    );
    $secs = (int)($user['total_watch_seconds'] ?? 0);
    $rate = fh_watch_rate_usd();
    $lifetimeUsd = fh_lifetime_watch_earnings_usd($userId);
    $currency = $user['preferred_currency'] ?? 'USD';

    return [
        'total_watch_seconds'   => $secs,
        'watch_hours'           => round($secs / 3600, 2),
        'lifetime_watch_usd'    => $lifetimeUsd,
        'estimated_usd'         => $lifetimeUsd,
        'lifetime_watch_formatted' => fh_format_money($lifetimeUsd, $currency),
        'balance_usd'           => (float)($user['balance'] ?? 0),
        'balance_formatted'     => fh_format_money((float)($user['balance'] ?? 0), $currency),
        'currency'              => $currency,
        'rate_usd'              => $rate,
        'rate_formatted'        => fh_format_money($rate, 'USD') . '/hr',
    ];
}

function fh_pending_withdrawal(int $userId): ?array {
    return db_fetch(
        "SELECT * FROM withdrawal_requests WHERE user_id=? AND status IN ('pending','processing') ORDER BY id DESC LIMIT 1",
        [$userId]
    );
}
