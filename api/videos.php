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
$is_reel_feed = isset($_GET['is_reel']) && (int)$_GET['is_reel'] === 1;
$channel_id = (int)($_GET['channel_id'] ?? 0);
$is_public_feed = !$action && !$channel_id && !is_logged_in();

// ── GET: paginated video list ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !$action) {
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $per     = min(100, max(4, (int)($_GET['per_page'] ?? 24))); // reduced from 50 to 24
    $cat        = (int)($_GET['cat'] ?? 0);
    $sort       = $_GET['sort'] ?? 'latest';
    $ref        = $_GET['ref'] ?? '';
    $q          = trim($_GET['q'] ?? '');
    $channel_id = (int)($_GET['channel_id'] ?? 0);
    $is_reel    = isset($_GET['is_reel']) ? (int)$_GET['is_reel'] : null;
    $is_public_feed = !$channel_id && !is_logged_in();

    if ($is_reel === 1) {
        $where_params = [];
        if ($channel_id) {
            $is_owner = is_logged_in() && auth_user()['id'] == $channel_id;
            if ($is_owner) {
                $where = "v.user_id=?";
            } else {
                $where = "v.user_id=? AND v.status='published'";
            }
            $where_params[] = $channel_id;
        } else {
            $where = "v.status='published'";
        }
        
        $start_id = isset($_GET['start_id']) ? (int)$_GET['start_id'] : 0;
        $offset = ($page - 1) * $per;

        $select_fields = "v.id, v.video_url, v.user_id, u.username, u.channel_name, u.avatar, v.title, v.views, v.likes, COALESCE(v.comments_count, 0) AS comments_count, v.created_at";

        if ($start_id > 0 && $page === 1) {
            $videos_start = db_fetchAll(
                "SELECT $select_fields
                 FROM reels v JOIN users u ON u.id=v.user_id
                 WHERE v.id=? AND v.status='published'", [$start_id]
            );
            $rest_limit = max(0, $per - count($videos_start));
            $videos_rest = db_fetchAll(
                "SELECT $select_fields
                 FROM reels v JOIN users u ON u.id=v.user_id
                 WHERE $where AND v.id!=?
                 ORDER BY v.created_at DESC, v.id DESC LIMIT $rest_limit OFFSET 0", array_merge($where_params, [$start_id])
            );
            $videos = array_merge($videos_start, $videos_rest);
            $total  = db_count('reels v', $where, $where_params);
        } else {
            $total  = db_count('reels v', $where, $where_params);
            if ($start_id > 0) {
                $where .= " AND v.id!=?";
                $where_params[] = $start_id;
            }
            $videos = db_fetchAll(
                "SELECT $select_fields
                 FROM reels v JOIN users u ON u.id=v.user_id
                 WHERE $where ORDER BY v.created_at DESC, v.id DESC LIMIT $per OFFSET $offset",
                $where_params
            );
        }
        
        $out = array_map(function($v) {
            return [
                'id'          => $v['id'],
                'user_id'     => (int)$v['user_id'],
                'video_src'   => reel_url($v['video_url']),
                'channel'     => $v['channel_name'] ?? $v['username'],
                'avatar'      => avatar_url($v['avatar']),
                'description' => $v['title'] ?? '',
                'title'       => $v['title'] ?? '',
                'views'       => format_number((int)$v['views']),
                'views_raw'   => (int)$v['views'],
                'likes'       => format_number((int)$v['likes']),
                'comments'    => format_number((int)$v['comments_count']),
                'ago'         => time_ago($v['created_at'] ?? ''),
                'is_reel'     => 1,
                'url'         => BASE_URL . '/reels/' . $v['id'],
            ];
        }, $videos);

        $response = ['videos' => $out, 'has_next' => ($offset + $per) < $total];

        // Add HTTP cache headers for public reel feeds (60s browser cache)
        if ($is_public_feed && $page === 1 && !$start_id) {
            $etag = '"reels_' . md5(serialize($out)) . '"';
            header('Cache-Control: public, max-age=60, stale-while-revalidate=30');
            header('ETag: ' . $etag);
            if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
                http_response_code(304);
                exit;
            }
        }

        json_response($response);
    }

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

    $where .= " AND v.is_reel = 0";

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

    $select_fields = "v.id,v.user_id,v.title,v.thumbnail,v.duration,v.views,v.published_at,v.is_reel,v.video_url,
                      u.username,u.channel_name,u.avatar";

    $videos = db_fetchAll(
        "SELECT $select_fields
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
            'thumbnail'    => thumb_url($v['thumbnail'], $v['video_url']),
            'duration_fmt' => $durSec > 0 ? format_duration($durSec) : '',
            'views'        => format_number((int)$v['views']),
            'ago'          => time_ago($v['published_at'] ?? ''),
            'channel'      => $v['channel_name'] ?? $v['username'],
            'avatar'       => avatar_url($v['avatar']),
            'url'          => BASE_URL . '/video/watch/' . $v['id'] . ($ref_param ? '?' . ltrim($ref_param, '&') : ''),
            'is_reel'      => 0,
            'video_src'    => video_url($v['video_url']),
        ];
        return $item;
    }, $videos);

    $response = ['videos' => $out, 'has_next' => ($offset + $per) < $total];

    // Add HTTP cache headers for public video feeds
    if ($is_public_feed && $page === 1 && !$q) {
        $etag = '"videos_' . md5(serialize($out)) . '"';
        header('Cache-Control: public, max-age=60, stale-while-revalidate=30');
        header('ETag: ' . $etag);
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
            http_response_code(304);
            exit;
        }
    }

    json_response($response);
}

// ── POST actions ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $guest_allowed_actions = ['react', 'comment', 'reel_react', 'reel_comment'];
    $is_guest_action = in_array($action, $guest_allowed_actions, true);

    if (!$is_guest_action) {
        if (!is_logged_in()) json_error('Login required', 401);
        if ((auth_user()['status'] ?? 'pending') !== 'active') {
            json_error('Account not active', 403);
        }
    }

    $uid = is_logged_in() ? auth_user()['id'] : null;

    // React (like/dislike)
    if ($action === 'react') {
        $vid  = (int)($body['video_id'] ?? 0);
        $type = in_array($body['type']??'', ['like','dislike']) ? $body['type'] : 'like';
        if (!$vid) json_error('Invalid video');

        $new_reaction = $type;
        if ($uid) {
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
            $likes = db_count('video_reactions', "video_id=? AND type='like'", [$vid]);
            db_update('videos', ['likes'=>$likes], 'id=?', [$vid]);
        } else {
            // Guest reaction tracking using PHP session
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (!isset($_SESSION['video_reactions'])) {
                $_SESSION['video_reactions'] = [];
            }
            $old_reaction = $_SESSION['video_reactions'][$vid] ?? 'none';
            if ($old_reaction === $type) {
                unset($_SESSION['video_reactions'][$vid]);
                $new_reaction = 'none';
                if ($type === 'like') {
                    db_query("UPDATE videos SET likes = GREATEST(0, CAST(likes AS SIGNED) - 1) WHERE id = ?", [$vid]);
                } else {
                    db_query("UPDATE videos SET dislikes = GREATEST(0, CAST(dislikes AS SIGNED) - 1) WHERE id = ?", [$vid]);
                }
            } else {
                $_SESSION['video_reactions'][$vid] = $type;
                if ($old_reaction === 'none') {
                    if ($type === 'like') {
                        db_query("UPDATE videos SET likes = likes + 1 WHERE id = ?", [$vid]);
                    } else {
                        db_query("UPDATE videos SET dislikes = dislikes + 1 WHERE id = ?", [$vid]);
                    }
                } else {
                    if ($type === 'like') {
                        db_query("UPDATE videos SET likes = likes + 1, dislikes = GREATEST(0, CAST(dislikes AS SIGNED) - 1) WHERE id = ?", [$vid]);
                    } else {
                        db_query("UPDATE videos SET dislikes = dislikes + 1, likes = GREATEST(0, CAST(likes AS SIGNED) - 1) WHERE id = ?", [$vid]);
                    }
                }
            }
            $video = db_fetch("SELECT likes FROM videos WHERE id=?", [$vid]);
            $likes = (int)($video['likes'] ?? 0);
        }
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

        $is_reel = isset($meta['is_reel']) ? (int)$meta['is_reel'] : 0;
        $upload_token = bin2hex(random_bytes(32));

        // Store all metadata as JSON in upload_sessions — no videos row yet
        $meta_json = json_encode([
            'title'        => $title,
            'description'  => $description,
            'tags'         => $tags,
            'visibility'   => $visibility,
            'category_ids' => $category_ids,
            'is_reel'      => $is_reel,
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

        // ── Post-publish: update videos or reels table directly ──
        if (!$video_id) json_error('Missing video_id or session_id');
        
        $reel = db_fetch('SELECT id, user_id FROM reels WHERE id=?', [$video_id]);
        if ($reel) {
            if ((int)$reel['user_id'] !== $uid && !is_admin()) json_error('Forbidden', 403);
            $fields = [];
            if (isset($meta['title'])) {
                $fields['title'] = trim($meta['title']) ?: null;
            }
            if ($fields) db_update('reels', $fields, 'id=?', [$video_id]);
            json_success(['updated' => true, 'video_id' => $video_id]);
        }

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

        if ($fields) db_update('videos', $fields, 'id=?', [$video_id]);
        json_success(['updated' => true, 'video_id' => $video_id]);
    }

    // Comments
    if ($action === 'comment') {
        $vid     = (int)($body['video_id'] ?? 0);
        $content = trim($body['content'] ?? '');
        if (!$vid || strlen($content) < 1) json_error('Invalid data');
        $rl_key  = $uid ? 'comment_'.$uid : 'comment_guest_'.($_SERVER['REMOTE_ADDR'] ?? 'anon');
        if (!rate_limit($rl_key, 10, 60)) json_error('Slow down!');
        $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        $comment_uid = $uid ?: get_guest_user_id();
        db_insert('comments', ['video_id'=>$vid,'user_id'=>$comment_uid,'content'=>$content]);
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



    // Reel React (like/dislike)
    if ($action === 'reel_react') {
        $reel_id = (int)($body['video_id'] ?? 0);
        if (!$reel_id) json_error('Invalid reel');

        $new_reaction = 'like';
        if ($uid) {
            $existing = db_fetch("SELECT id, type FROM reel_reactions WHERE reel_id=? AND user_id=?", [$reel_id, $uid]);
            if ($existing) {
                db_query("DELETE FROM reel_reactions WHERE id=?", [$existing['id']]);
                $new_reaction = 'none';
            } else {
                db_insert('reel_reactions', ['reel_id'=>$reel_id, 'user_id'=>$uid, 'type'=>'like']);
            }
            $likes = db_count('reel_reactions', "reel_id=? AND type='like'", [$reel_id]);
            db_update('reels', ['likes'=>$likes], 'id=?', [$reel_id]);
        } else {
            // Guest reel reaction tracking using PHP session
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (!isset($_SESSION['reel_reactions'])) {
                $_SESSION['reel_reactions'] = [];
            }
            $old_reaction = $_SESSION['reel_reactions'][$reel_id] ?? 'none';
            if ($old_reaction === 'like') {
                unset($_SESSION['reel_reactions'][$reel_id]);
                $new_reaction = 'none';
                db_query("UPDATE reels SET likes = GREATEST(0, CAST(likes AS SIGNED) - 1) WHERE id = ?", [$reel_id]);
            } else {
                $_SESSION['reel_reactions'][$reel_id] = 'like';
                db_query("UPDATE reels SET likes = likes + 1 WHERE id = ?", [$reel_id]);
            }
            $reel = db_fetch("SELECT likes FROM reels WHERE id=?", [$reel_id]);
            $likes = (int)($reel['likes'] ?? 0);
        }
        json_success(['likes' => format_number($likes), 'user_reaction' => $new_reaction]);
    }

    // Reel Comments
    if ($action === 'reel_comment') {
        $reel_id = (int)($body['video_id'] ?? 0);
        $content = trim($body['content'] ?? '');
        if (!$reel_id || strlen($content) < 1) json_error('Invalid data');
        $rl_key  = $uid ? 'comment_'.$uid : 'comment_guest_'.($_SERVER['REMOTE_ADDR'] ?? 'anon');
        if (!rate_limit($rl_key, 10, 60)) json_error('Slow down!');
        $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        $comment_uid = $uid ?: get_guest_user_id();
        db_insert('reel_comments', ['reel_id'=>$reel_id, 'user_id'=>$comment_uid, 'content'=>$content]);
        db_query("UPDATE reels SET comments_count=comments_count+1 WHERE id=?", [$reel_id]);
        json_success(null, 'Comment posted');
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

// ── GET: reel comments ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'reel_comments') {
    $reel_id = (int)($_GET['video_id'] ?? 0);
    if (!$reel_id) json_error('Invalid reel');
    $comments = db_fetchAll(
        "SELECT c.*,u.username,u.avatar FROM reel_comments c
         JOIN users u ON u.id=c.user_id
         WHERE c.reel_id=? AND c.status='visible'
         ORDER BY c.created_at DESC LIMIT 30", [$reel_id]
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
