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
    $per     = min(100, max(4, (int)($_GET['per_page'] ?? 50)));
    $cat        = (int)($_GET['cat'] ?? 0);
    $sort       = $_GET['sort'] ?? 'latest';
    $ref        = $_GET['ref'] ?? '';
    $q          = trim($_GET['q'] ?? '');
    $channel_id = (int)($_GET['channel_id'] ?? 0);
    $is_reel    = isset($_GET['is_reel']) ? (int)$_GET['is_reel'] : null;

    $where_params = [];
    if ($channel_id) {
        $is_owner = is_logged_in() && auth_user()['id'] == $channel_id;
        if ($is_owner) {
            $where = "v.user_id=?";
        } else {
            $where = "v.user_id=? AND v.status='published' AND v.visibility='public'";
        }
        $where_params[] = $channel_id;
    } else {
        $where = "v.status='published' AND v.visibility='public'";
    }

    if ($is_reel !== null) {
        $where .= " AND v.is_reel = ?";
        $where_params[] = $is_reel;
    } else {
        $where .= " AND v.is_reel = 0";
    }

    if ($q) {
        $where .= " AND MATCH(v.title,v.description,v.tags) AGAINST(? IN BOOLEAN MODE)";
        $where_params[] = $q . '*';
    }
    if ($cat) {
        $where .= " AND (v.category_id=? OR EXISTS (SELECT 1 FROM video_categories vc WHERE vc.video_id = v.id AND vc.category_id = ?))";
        $where_params[] = $cat;
        $where_params[] = $cat;
    }

    $order = match($sort) {
        'views'   => 'v.views DESC',
        'likes'   => 'v.likes DESC',
        'oldest'  => 'v.created_at ASC',
        'latest'  => 'v.created_at DESC',
        default   => $q ? 'MATCH(v.title,v.description,v.tags) AGAINST(?) DESC' : 'v.created_at DESC',
    };

    $order_params = [];
    if (!in_array($sort, ['views', 'likes', 'oldest', 'latest']) && $q) {
        $order_params[] = $q . '*';
    }

    $offset = ($page - 1) * $per;
    $total  = db_count('videos v', $where, $where_params);
    $all_params = array_merge($where_params, $order_params);
    $videos = db_fetchAll(
        "SELECT v.id,v.user_id,v.title,v.thumbnail,v.duration,v.views,v.published_at,v.is_reel,
                u.username,u.channel_name,u.avatar
         FROM videos v JOIN users u ON u.id=v.user_id
         WHERE $where ORDER BY $order LIMIT $per OFFSET $offset",
        $all_params
    );

    $ref_param = $ref ? '&ref=' . urlencode($ref) : '';
    $out = array_map(function($v) use ($ref_param) {
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
            'is_reel'      => (int)($v['is_reel'] ?? 0),
        ];
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

    // Initialize an upload: create ONLY an upload_sessions row (NO videos record yet)
    // The actual video record is created only on successful finalization.
    if ($action === 'init_upload') {
        $meta = $body['meta'] ?? [];
        $title = trim($meta['title'] ?? '');
        if ($title === '') {
            $title = 'Video #' . rand(10000, 99999);
        }

        $description = trim($meta['description'] ?? '');
        $tags = trim($meta['tags'] ?? '');
        $visibility = $meta['visibility'] ?? 'public';
        if (!in_array($visibility, ['public', 'unlisted', 'private'])) {
            $visibility = 'public';
        }
        $category_ids = $meta['category_ids'] ?? [];

        $upload_token = bin2hex(random_bytes(32));

        // Store all metadata as JSON in upload_sessions — no videos row yet
        $meta_json = json_encode([
            'title'        => $title,
            'description'  => $description,
            'tags'         => $tags,
            'visibility'   => $visibility,
            'category_ids' => $category_ids,
            'is_reel'      => 0,
            'duration'     => 0,
        ], JSON_UNESCAPED_UNICODE);

        $session_id = db_insert('upload_sessions', [
            'video_id'   => null,
            'user_id'    => $uid,
            'token'      => $upload_token,
            'meta_json'  => $meta_json,
            'status'     => 'active',
        ]);

        json_success(['session_id' => $session_id, 'upload_token' => $upload_token]);
    }

    // Save/update metadata — supports both session_id (pre-publish) and video_id (post-publish)
    if ($action === 'save_metadata') {
        $meta = $body['meta'] ?? [];
        $video_id   = (int)($meta['video_id'] ?? 0);
        $session_id = (int)($meta['session_id'] ?? 0);

        // ── Pre-publish: update upload_sessions.meta_json ──
        if ($session_id && !$video_id) {
            $session = db_fetch('SELECT id, user_id, meta_json FROM upload_sessions WHERE id=?', [$session_id]);
            if (!$session) json_error('Session not found', 404);
            if ((int)$session['user_id'] !== $uid && !is_admin()) json_error('Forbidden', 403);

            $existing = json_decode($session['meta_json'] ?? '{}', true) ?: [];
            if (isset($meta['title'])) {
                $t = trim($meta['title']);
                $existing['title'] = $t !== '' ? $t : 'Video #' . rand(10000, 99999);
            }
            if (isset($meta['description'])) $existing['description'] = trim($meta['description']);
            if (isset($meta['tags']))        $existing['tags'] = trim($meta['tags']);
            if (isset($meta['visibility']) && in_array($meta['visibility'], ['public','unlisted','private'])) {
                $existing['visibility'] = $meta['visibility'];
            }
            if (isset($meta['category_ids']) && is_array($meta['category_ids'])) {
                $existing['category_ids'] = $meta['category_ids'];
            }
            if (isset($meta['is_reel'])) {
                $existing['is_reel'] = (int)$meta['is_reel'] === 1 ? 1 : 0;
            }

            db_update('upload_sessions', ['meta_json' => json_encode($existing, JSON_UNESCAPED_UNICODE)], 'id=?', [$session_id]);
            json_success(['updated' => true, 'session_id' => $session_id]);
        }

        // ── Post-publish: update videos table directly ──
        if (!$video_id) json_error('Missing video_id or session_id');
        $video = db_fetch('SELECT id,user_id FROM videos WHERE id=?', [$video_id]);
        if (!$video) json_error('Not found', 404);
        if ((int)$video['user_id'] !== $uid && !is_admin()) json_error('Forbidden', 403);

        $fields = [];
        if (isset($meta['title'])) {
            $t = trim($meta['title']);
            if ($t === '') {
                $t = 'Video #' . rand(10000, 99999);
            }
            $fields['title'] = $t;
        }
        if (isset($meta['description'])) $fields['description'] = trim($meta['description']);
        if (isset($meta['tags'])) $fields['tags'] = trim($meta['tags']);
        if (isset($meta['visibility']) && in_array($meta['visibility'], ['public','unlisted','private'])) $fields['visibility'] = $meta['visibility'];
        if (isset($meta['category_ids']) && is_array($meta['category_ids'])) {
            $first = (int)($meta['category_ids'][0] ?? 0);
            if ($first > 0) $fields['category_id'] = $first;
        }
        if (isset($meta['is_reel'])) {
            $fields['is_reel'] = (int)$meta['is_reel'] === 1 ? 1 : 0;
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
            'title' => $name,
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
