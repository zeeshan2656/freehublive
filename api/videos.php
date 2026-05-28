<?php
// ============================================================
// FreeHub.Live — Videos API
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$action = $_GET['action'] ?? '';

// ── GET: paginated video list ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !$action) {
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $per     = min(24, max(4, (int)($_GET['per_page'] ?? 12)));
    $cat     = (int)($_GET['cat'] ?? 0);
    $sort    = $_GET['sort'] ?? 'latest';
    $ref     = $_GET['ref'] ?? '';

    $where = "v.status='published' AND v.visibility='public'";
    if ($cat) {
        $where .= " AND (v.category_id=$cat OR EXISTS (SELECT 1 FROM video_categories vc WHERE vc.video_id = v.id AND vc.category_id = $cat))";
    }
    $order = match($sort) {
        'views'   => 'v.views DESC',
        'likes'   => 'v.likes DESC',
        'oldest'  => 'v.published_at ASC',
        default   => 'v.published_at DESC',
    };

    $offset = ($page - 1) * $per;
    $total  = db_count('videos v', $where);
    $videos = db_fetchAll(
        "SELECT v.id,v.user_id,v.title,v.thumbnail,v.duration,v.views,v.published_at,
                u.username,u.channel_name,u.avatar
         FROM videos v JOIN users u ON u.id=v.user_id
         WHERE $where ORDER BY $order LIMIT $per OFFSET $offset"
    );

    $creatorId   = (is_logged_in() && is_creator()) ? (int)auth_user()['id'] : 0;
    $earningsMap = [];
    if ($creatorId) {
        $ownIds = [];
        foreach ($videos as $v) {
            if ((int)$v['user_id'] === $creatorId) {
                $ownIds[] = (int)$v['id'];
            }
        }
        if ($ownIds) {
            $earningsMap = fh_creator_video_earnings_map($creatorId, $ownIds);
        }
    }

    $ref_param = $ref ? '&ref=' . urlencode($ref) : '';
    $currency  = fh_user_currency();
    $out = array_map(function($v) use ($ref_param, $creatorId, $earningsMap, $currency) {
        $durSec = (int)$v['duration'];
        $item   = [
            'id'           => $v['id'],
            'user_id'      => (int)$v['user_id'],
            'title'        => $v['title'],
            'thumbnail'    => thumb_url($v['thumbnail']),
            'duration_fmt' => $durSec > 0 ? format_duration($durSec) : '',
            'views'        => format_number((int)$v['views']),
            'ago'          => time_ago($v['published_at'] ?? ''),
            'channel'      => $v['channel_name'] ?? $v['username'],
            'avatar'       => avatar_url($v['avatar']),
            'url'          => BASE_URL . '/watch.php?v=' . $v['id'] . $ref_param,

        ];
        if ($creatorId && (int)$v['user_id'] === $creatorId) {
            $usd = $earningsMap[(int)$v['id']] ?? 0.0;
            $item['earnings_fmt'] = fh_format_money($usd, $currency);
        }
        return $item;
    }, $videos);

    json_response(['videos' => $out, 'has_next' => ($offset + $per) < $total]);
}

// ── POST actions ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_logged_in()) json_error('Login required', 401);
    if ((auth_user()['status'] ?? 'pending') !== 'active') {
        json_error('Account not active', 403);
    }
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $uid  = auth_user()['id'];

    // React (like/dislike)
    if ($action === 'react') {
        $vid  = (int)($body['video_id'] ?? 0);
        $type = in_array($body['type']??'', ['like','dislike']) ? $body['type'] : 'like';
        if (!$vid) json_error('Invalid video');

        $new_reaction = $type;
        $existing = db_fetch("SELECT id,type FROM video_reactions WHERE video_id=? AND user_id=?", [$vid,$uid]);
        if ($existing) {
            if ($existing['type'] === $type) {
                db_query("DELETE FROM video_reactions WHERE id=?", [$existing['id']]);
                $new_reaction = 'none';
            } else {
                db_update('video_reactions', ['type'=>$type], 'id=?', [$existing['id']]);
            }
        } else {
            db_insert('video_reactions', ['video_id'=>$vid,'user_id'=>$uid,'type'=>$type]);
        }
        $video = db_fetch("SELECT likes,dislikes FROM videos WHERE id=?", [$vid]);
        $likes = db_count('video_reactions', "video_id=? AND type='like'", [$vid]);
        db_update('videos', ['likes'=>$likes], 'id=?', [$vid]);
        json_success(['likes' => format_number($likes), 'reaction' => $new_reaction]);
    }

    // Initialize an upload: create placeholder video record and return upload token
    if ($action === 'init_upload') {
        $meta = $body['meta'] ?? [];
        $title = trim($meta['title'] ?? 'Untitled');
        $description = trim($meta['description'] ?? '');
        $tags = trim($meta['tags'] ?? '');
        $category_ids = $meta['category_ids'] ?? [];
        $visibility = in_array($meta['visibility'] ?? '', ['public','unlisted','private']) ? $meta['visibility'] : 'public';

        if (strlen($title) < 1) $title = 'Untitled';
        $slug = slugify($title);
        $base = $slug; $i = 1;
        while (db_fetch("SELECT id FROM videos WHERE slug=?", [$slug])) {
            $slug = $base . '-' . $i++;
        }

        $upload_token = bin2hex(random_bytes(16));
        $approvalMode  = setting('video_approval_mode', 'manual');
        $initialStatus = ($approvalMode === 'auto') ? 'published' : 'pending';
        $publishedAt   = ($approvalMode === 'auto') ? date('Y-m-d H:i:s') : null;

        $new_id = db_insert('videos', [
            'user_id' => $uid,
            'category_id' => !empty($category_ids) ? (int)$category_ids[0] : null,
            'title' => $title,
            'slug'  => $slug,
            'description' => $description,
            'tags' => $tags,
            'video_url' => '',
            'thumbnail' => null,
            'file_size' => 0,
            'duration' => 0,
            'visibility' => $visibility,
            'status' => $initialStatus,
            'published_at' => $publishedAt
        ]);

        if ($new_id && !empty($category_ids)) {
            foreach ($category_ids as $cid) {
                db_insert('video_categories', ['video_id' => $new_id, 'category_id' => (int)$cid]);
            }
        }

        // ensure upload_sessions table exists (best-effort)
        db_query("CREATE TABLE IF NOT EXISTS upload_sessions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            video_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            token VARCHAR(64) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_video (video_id)
        ) ENGINE=InnoDB;");
        db_insert('upload_sessions', ['video_id'=>$new_id, 'user_id'=>$uid, 'token'=>$upload_token]);

        json_success(['video_id' => $new_id, 'upload_token' => $upload_token]);
    }

    // Save/update metadata for an existing video (can be called before upload completes)
    if ($action === 'save_metadata') {
        $meta = $body['meta'] ?? [];
        $video_id = (int)($meta['video_id'] ?? 0);
        if (!$video_id) json_error('Missing video_id');
        $video = db_fetch('SELECT id,user_id FROM videos WHERE id=?', [$video_id]);
        if (!$video) json_error('Not found', 404);
        if ((int)$video['user_id'] !== $uid && !is_admin()) json_error('Forbidden', 403);

        $fields = [];
        if (isset($meta['title'])) $fields['title'] = trim($meta['title']);
        if (isset($meta['description'])) $fields['description'] = trim($meta['description']);
        if (isset($meta['tags'])) $fields['tags'] = trim($meta['tags']);
        if (isset($meta['visibility']) && in_array($meta['visibility'], ['public','unlisted','private'])) $fields['visibility'] = $meta['visibility'];
        if (isset($meta['category_ids']) && is_array($meta['category_ids'])) {
            $first = (int)($meta['category_ids'][0] ?? 0);
            if ($first > 0) $fields['category_id'] = $first;
        }

        if ($fields) db_update('videos', $fields, 'id=?', [$video_id]);
        json_success(['updated' => true, 'video_id' => $video_id]);
    }

    // Comments
    if ($action === 'comment') {
        $vid     = (int)($body['video_id'] ?? 0);
        $content = trim($body['content'] ?? '');
        if (!$vid || strlen($content) < 1) json_error('Invalid data');
        if (!rate_limit('comment_'.$uid, 10, 60)) json_error('Slow down!');
        $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        db_insert('comments', ['video_id'=>$vid,'user_id'=>$uid,'content'=>$content]);
        db_query("UPDATE videos SET comments_count=comments_count+1 WHERE id=?", [$vid]);
        json_success(null, 'Comment posted');
    }

    // Load comments
    if ($action === 'comments' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $vid = (int)($_GET['video_id'] ?? 0);
        if (!$vid) json_error('Invalid video');
        $comments = db_fetchAll(
            "SELECT c.*,u.username,u.avatar FROM comments c
             JOIN users u ON u.id=c.user_id
             WHERE c.video_id=? AND c.status='visible'
             ORDER BY c.is_pinned DESC, c.created_at DESC LIMIT 30", [$vid]
        );
        $out = array_map(fn($c) => [
            'username' => e($c['username']),
            'avatar'   => avatar_url($c['avatar']),
            'content'  => e($c['content']),
            'ago'      => time_ago($c['created_at']),
        ], $comments);
        json_success($out);
    }

    // Watch Later
    if ($action === 'watch_later') {
        $vid = (int)($body['video_id'] ?? 0);
        if (!$vid) json_error('Invalid video');
        $exists = db_fetch("SELECT id FROM watch_later WHERE user_id=? AND video_id=?", [$uid,$vid]);
        if ($exists) {
            db_query("DELETE FROM watch_later WHERE user_id=? AND video_id=?", [$uid,$vid]);
            json_success(['saved'=>false]);
        } else {
            db_insert('watch_later', ['user_id'=>$uid,'video_id'=>$vid]);
            json_success(['saved'=>true]);
        }
    }

    // Subscribe
    if ($action === 'subscribe') {
        $channel_id = (int)($body['channel_id'] ?? 0);
        if (!$channel_id || $channel_id === $uid) json_error('Invalid');
        $exists = db_fetch("SELECT id FROM subscriptions WHERE subscriber_id=? AND channel_id=?", [$uid,$channel_id]);
        if ($exists) {
            db_query("DELETE FROM subscriptions WHERE subscriber_id=? AND channel_id=?", [$uid,$channel_id]);
            db_query("UPDATE users SET subscribers=GREATEST(0,subscribers-1) WHERE id=?", [$channel_id]);
            json_success(['subscribed'=>false]);
        } else {
            db_insert('subscriptions', ['subscriber_id'=>$uid,'channel_id'=>$channel_id]);
            db_query("UPDATE users SET subscribers=subscribers+1 WHERE id=?", [$channel_id]);
            json_success(['subscribed'=>true]);
        }
    }

    // Create playlist
    if ($action === 'create_playlist') {
        if (!is_creator()) json_error('Unauthorized', 403);
        $name = trim($body['name'] ?? '');
        $desc = trim($body['description'] ?? '');
        if (empty($name)) json_error('Playlist name required');
        db_insert('playlists', [
            'user_id' => $uid,
            'name' => $name,
            'description' => $desc,
            'visibility' => 'private'
        ]);
        json_success(['message' => 'Playlist created']);
    }

    // Delete playlist
    if ($action === 'delete_playlist') {
        $playlist_id = (int)($body['playlist_id'] ?? 0);
        if (!$playlist_id) json_error('Invalid playlist');
        $playlist = db_fetch("SELECT user_id FROM playlists WHERE id=?", [$playlist_id]);
        if (!$playlist) json_error('Playlist not found', 404);
        if ($playlist['user_id'] != $uid) json_error('Unauthorized', 403);
        db_query("DELETE FROM playlists WHERE id=?", [$playlist_id]);
        json_success(['message' => 'Playlist deleted']);
    }

    json_error('Unknown action', 404);
}

// ── GET: comments ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'comments') {
    $vid = (int)($_GET['video_id'] ?? 0);
    if (!$vid) json_error('Invalid video');
    $comments = db_fetchAll(
        "SELECT c.*,u.username,u.avatar FROM comments c
         JOIN users u ON u.id=c.user_id
         WHERE c.video_id=? AND c.status='visible'
         ORDER BY c.is_pinned DESC, c.created_at DESC LIMIT 30", [$vid]
    );
    $out = array_map(fn($c) => [
        'username' => htmlspecialchars($c['username']),
        'avatar'   => avatar_url($c['avatar']),
        'content'  => htmlspecialchars($c['content']),
        'ago'      => time_ago($c['created_at']),
    ], $comments);
    json_success($out);
}

json_error('Not found', 404);
