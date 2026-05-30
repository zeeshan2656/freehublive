<?php
// ============================================================
// FreeHub.Live — Playlists
// ============================================================
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$playlist_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($playlist_id > 0) {
    // ── Single Playlist View ────────────────────────────────
    $playlist = db_fetch(
        "SELECT p.*, u.username, u.channel_name, u.avatar 
         FROM playlists p 
         JOIN users u ON u.id = p.user_id 
         WHERE p.id = ?", 
        [$playlist_id]
    );

    if (!$playlist) {
        http_response_code(404);
        die('Playlist not found');
    }

    $is_owner = is_logged_in() && (auth_user()['id'] == $playlist['user_id'] || auth_user()['role'] === 'admin');
    $uid = is_logged_in() ? auth_user()['id'] : 0;

    // Check visibility / access control
    if ($playlist['visibility'] === 'private' && !$is_owner) {
        http_response_code(403);
        die('Access denied');
    }

    // Handle POST actions for single playlist
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? '')) {
        $action = $_POST['action'] ?? '';
        if ($action === 'delete' && $is_owner) {
            db_query("DELETE FROM playlists WHERE id=?", [$playlist_id]);
            redirect(BASE_URL . '/playlists.php');
        }
        if ($action === 'remove_video' && $is_owner) {
            $vid = (int)($_POST['video_id'] ?? 0);
            db_query("DELETE FROM playlist_videos WHERE playlist_id=? AND video_id=?", [$playlist_id, $vid]);
            db_query("DELETE FROM playlist_items WHERE playlist_id=? AND video_id=?", [$playlist_id, $vid]);
            db_query("UPDATE playlists SET video_count = GREATEST(0, CAST(video_count AS SIGNED) - 1) WHERE id = ?", [$playlist_id]);
            redirect(BASE_URL . '/playlists.php?id=' . $playlist_id);
        }
    }

    // Fetch videos in this playlist
    $playlist_videos = db_fetchAll(
        "SELECT v.*, u.username, u.channel_name, u.avatar 
         FROM playlist_videos pv 
         JOIN videos v ON v.id = pv.video_id 
         JOIN users u ON u.id = v.user_id 
         WHERE pv.playlist_id = ? AND (v.visibility = 'public' OR v.user_id = ? OR ? = 1)
         ORDER BY pv.sort_order ASC",
        [$playlist_id, $uid, is_logged_in() && auth_user()['role'] === 'admin' ? 1 : 0]
    );

    $meta_title = e($playlist['title']) . ' — Playlist';
    require_once __DIR__ . '/includes/header.php';
    ?>
    
    <style>
    .playlist-detail-container {
      display: grid;
      grid-template-columns: 360px 1fr;
      gap: 30px;
      margin-top: 10px;
    }
    .playlist-sidebar-card {
      background: linear-gradient(180deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.01) 100%);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 16px;
      padding: 24px;
      position: sticky;
      top: 70px;
      height: fit-content;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }
    .playlist-sidebar-thumb-wrapper {
      width: 100%;
      aspect-ratio: 16/9;
      border-radius: 12px;
      overflow: hidden;
      margin-bottom: 20px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.4);
      background: var(--bg3);
      position: relative;
    }
    .playlist-sidebar-thumb {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .playlist-sidebar-title {
      font-size: 1.4rem;
      font-weight: 800;
      color: #fff;
      margin-bottom: 12px;
      line-height: 1.25;
      word-wrap: break-word;
    }
    .playlist-sidebar-meta {
      font-size: 0.85rem;
      color: var(--text2);
      margin-bottom: 20px;
      line-height: 1.5;
    }
    .playlist-sidebar-desc {
      font-size: 0.9rem;
      color: var(--text2);
      line-height: 1.6;
      margin-top: 16px;
      padding-top: 16px;
      border-top: 1px solid rgba(255,255,255,0.08);
      word-break: break-word;
    }
    .playlist-video-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    .playlist-video-item {
      display: flex;
      gap: 16px;
      padding: 12px;
      background: var(--bg2);
      border: 1px solid var(--border);
      border-radius: 12px;
      transition: all 0.2s ease;
      align-items: center;
      position: relative;
    }
    .playlist-video-item:hover {
      background: var(--bg3);
      border-color: var(--accent);
      transform: translateY(-2px);
    }
    .playlist-video-index {
      font-size: 0.9rem;
      font-weight: 700;
      color: var(--text3);
      width: 24px;
      text-align: center;
      flex-shrink: 0;
    }
    .playlist-video-thumb-wrapper {
      width: 160px;
      aspect-ratio: 16/9;
      border-radius: 8px;
      overflow: hidden;
      position: relative;
      flex-shrink: 0;
      background: var(--bg3);
    }
    .playlist-video-thumb {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .playlist-video-details {
      flex: 1;
      min-width: 0;
    }
    .playlist-video-title {
      font-weight: 700;
      font-size: 0.95rem;
      margin-bottom: 6px;
      color: var(--text);
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      line-height: 1.35;
    }
    .playlist-video-meta {
      font-size: 0.8rem;
      color: var(--text2);
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 6px;
    }
    .playlist-video-remove {
      background: none;
      border: none;
      color: var(--text3);
      cursor: pointer;
      padding: 8px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s;
    }
    .playlist-video-remove:hover {
      color: #ef4444;
      background: rgba(239, 68, 68, 0.1);
    }
    @media(max-width: 860px) {
      .playlist-detail-container {
        grid-template-columns: 1fr;
        gap: 20px;
      }
      .playlist-sidebar-card {
        position: static;
      }
      .playlist-video-thumb-wrapper {
        width: 120px;
      }
    }
    </style>

    <div class="layout">
      <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

      <main class="main-content">
        <div class="playlist-detail-container">
          
          <!-- Playlist Sidebar Details -->
          <div class="playlist-sidebar-card">
            <?php
            $first_thumb = !empty($playlist_videos) ? thumb_url($playlist_videos[0]['thumbnail']) : BASE_URL . '/assets/img/default-thumb.jpg';
            ?>
            <div class="playlist-sidebar-thumb-wrapper">
              <img src="<?= $first_thumb ?>" alt="<?= e($playlist['title']) ?>" class="playlist-sidebar-thumb">
            </div>
            
            <h1 class="playlist-sidebar-title"><?= e($playlist['title']) ?></h1>
            
            <div class="playlist-sidebar-meta">
              <div style="font-weight:700;margin-bottom:8px">
                <a href="<?= BASE_URL ?>/channel.php?id=<?= $playlist['user_id'] ?>" style="color:var(--accent);display:flex;align-items:center;gap:8px">
                  <img src="<?= avatar_url($playlist['avatar']) ?>" class="avatar" style="width:24px;height:24px;border-radius:50%">
                  <?= e($playlist['channel_name'] ?: $playlist['username']) ?>
                </a>
              </div>
              <div class="flex gap-2" style="font-size:0.8rem;color:var(--text2);margin-bottom:12px">
                <span><?= (int)$playlist['video_count'] ?> videos</span>
                <span>·</span>
                <span><?= ucfirst($playlist['visibility']) ?></span>
                <span>·</span>
                <span>Created <?= time_ago($playlist['created_at']) ?></span>
              </div>
              
              <?php if (!empty($playlist_videos)): ?>
                <a href="<?= BASE_URL ?>/watch.php?v=<?= $playlist_videos[0]['id'] ?>&list=<?= $playlist['id'] ?>" class="btn btn-primary w-full" style="justify-content:center;border-radius:24px;padding:12px 24px;font-weight:700">
                  ▶ Play All
                </a>
              <?php endif; ?>
            </div>

            <?php if ($playlist['description']): ?>
              <div class="playlist-sidebar-desc">
                <?= nl2br(e($playlist['description'])) ?>
              </div>
            <?php endif; ?>

            <?php if ($is_owner): ?>
              <div style="margin-top:20px;display:flex;gap:10px">
                <form method="POST" style="flex:1" onsubmit="return confirm('Delete this playlist?')">
                  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                  <input type="hidden" name="action" value="delete">
                  <button type="submit" class="btn btn-outline w-full btn-sm" style="color:#ef4444;border-color:#ef4444;justify-content:center">
                    Delete Playlist
                  </button>
                </form>
              </div>
            <?php endif; ?>
          </div>

          <!-- Videos List -->
          <div>
            <h2 style="font-size:1.15rem;font-weight:800;margin-bottom:16px">Playlist Videos</h2>
            
            <?php if (empty($playlist_videos)): ?>
              <div style="text-align:center;padding:80px 20px;color:var(--text2);background:var(--bg2);border-radius:12px;border:1px solid var(--border)">
                <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 12px;opacity:.4"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
                <h3 style="font-size:1rem;color:var(--text);margin-bottom:6px">No videos in this playlist</h3>
                <p class="text-sm">Assign videos to this playlist when uploading or editing.</p>
              </div>
            <?php else: ?>
              <div class="playlist-video-list">
                <?php foreach ($playlist_videos as $idx => $v): ?>
                  <div class="playlist-video-item">
                    <div class="playlist-video-index"><?= $idx + 1 ?></div>
                    
                    <a href="<?= BASE_URL ?>/watch.php?v=<?= $v['id'] ?>&list=<?= $playlist['id'] ?>" class="playlist-video-thumb-wrapper">
                      <img src="<?= thumb_url($v['thumbnail']) ?>" alt="<?= e($v['title']) ?>" class="playlist-video-thumb" loading="lazy">
                      <span style="position:absolute;bottom:6px;right:6px;background:rgba(0,0,0,0.8);color:#fff;font-size:0.7rem;font-weight:600;padding:2px 6px;border-radius:4px">
                        <?= format_duration((int)$v['duration']) ?>
                      </span>
                    </a>
                    
                    <div class="playlist-video-details">
                      <a href="<?= BASE_URL ?>/watch.php?v=<?= $v['id'] ?>&list=<?= $playlist['id'] ?>">
                        <h3 class="playlist-video-title"><?= e($v['title']) ?></h3>
                      </a>
                      <div class="playlist-video-meta">
                        <a href="<?= BASE_URL ?>/channel.php?id=<?= $v['user_id'] ?>" style="color:var(--text2)">
                          <?= e($v['channel_name'] ?: $v['username']) ?>
                        </a>
                        <span>·</span>
                        <span><?= format_number((int)$v['views']) ?> views</span>
                        <span>·</span>
                        <span><?= time_ago($v['published_at'] ?? $v['created_at']) ?></span>
                      </div>
                    </div>

                    <?php if ($is_owner): ?>
                      <form method="POST" onsubmit="return confirm('Remove this video from playlist?')">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="remove_video">
                        <input type="hidden" name="video_id" value="<?= $v['id'] ?>">
                        <button type="submit" class="playlist-video-remove" title="Remove from playlist">
                          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

        </div>
      </main>
    </div>

    <?php
    require_once __DIR__ . '/includes/footer.php';
} else {
    // ── Playlist Manager View (Requires Login) ──────────────────
    require_login();
    $uid = auth_user()['id'];

    // Handle POST actions for playlist manager
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? '')) {
        $action = $_POST['action'] ?? '';
        if ($action === 'create') {
            $title = trim($_POST['title'] ?? '');
            if (strlen($title) >= 1) {
                db_insert('playlists', [
                    'user_id' => $uid,
                    'title'   => $title,
                    'description' => trim($_POST['description'] ?? '') ?: null,
                    'visibility'  => in_array($_POST['visibility'] ?? '', ['public','private','unlisted']) ? $_POST['visibility'] : 'public',
                ]);
            }
            redirect(BASE_URL . '/playlists.php');
        }
        if ($action === 'delete') {
            $pid = (int)($_POST['playlist_id'] ?? 0);
            db_query("DELETE FROM playlists WHERE id=? AND user_id=?", [$pid, $uid]);
            redirect(BASE_URL . '/playlists.php');
        }
    }

    $playlists = db_fetchAll(
        "SELECT p.*, 
                (SELECT COUNT(*) FROM playlist_videos pv WHERE pv.playlist_id = p.id) as video_count,
                (SELECT v.thumbnail FROM playlist_videos pv JOIN videos v ON v.id = pv.video_id WHERE pv.playlist_id = p.id ORDER BY pv.sort_order LIMIT 1) as thumbnail
         FROM playlists p
         WHERE p.user_id = ?
         ORDER BY p.created_at DESC",
        [$uid]
    );

    require_once __DIR__ . '/includes/header.php';
    ?>
    <div class="layout">
      <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

      <main class="main-content">
        <div class="section-header" style="margin-bottom:20px">
          <h1 style="font-size:1.2rem;font-weight:800;display:flex;align-items:center;gap:8px">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            My Playlists
          </h1>
          <button class="btn btn-primary btn-sm" onclick="document.getElementById('create-playlist-form').style.display='block';this.style.display='none'">
            + New Playlist
          </button>
        </div>

        <!-- Create Playlist Form (hidden by default) -->
        <div id="create-playlist-form" class="card" style="display:none;margin-bottom:20px;padding:20px">
          <h3 style="font-weight:700;font-size:.95rem;margin-bottom:12px">Create New Playlist</h3>
          <form method="POST">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
              <label class="form-label">Playlist Title *</label>
              <input class="form-input" type="text" name="title" required maxlength="150" placeholder="My awesome playlist">
            </div>
            <div class="form-group">
              <label class="form-label">Description</label>
              <textarea class="form-input" name="description" rows="2" placeholder="Optional description…" style="resize:vertical"></textarea>
            </div>
            <div class="form-group">
              <label class="form-label">Visibility</label>
              <select class="form-input form-select" name="visibility">
                <option value="public">Public</option>
                <option value="unlisted">Unlisted</option>
                <option value="private">Private</option>
              </select>
            </div>
            <div class="flex gap-2">
              <button type="submit" class="btn btn-primary btn-sm">Create</button>
              <button type="button" class="btn btn-outline btn-sm" onclick="this.closest('#create-playlist-form').style.display='none'">Cancel</button>
            </div>
          </form>
        </div>

        <?php if (!$playlists): ?>
          <div style="text-align:center;padding:80px 20px;color:var(--text2)">
            <svg width="56" height="56" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 16px;opacity:.4"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            <h2 style="font-size:1.1rem;margin-bottom:6px;color:var(--text)">No playlists yet</h2>
            <p>Create your first playlist to organize your favorite videos.</p>
          </div>
        <?php else: ?>
          <div class="grid grid-6">
            <?php foreach ($playlists as $p):
              $thumb = $p['thumbnail'] ? thumb_url($p['thumbnail']) : BASE_URL . '/assets/img/default-thumb.jpg';
            ?>
            <article class="video-card fade-in" style="position:relative;cursor:pointer" onclick="location.href='<?= BASE_URL ?>/playlists.php?id=<?= $p['id'] ?>'">
              <div class="video-thumb" style="position:relative">
                <img src="<?= $thumb ?>" alt="<?= e($p['title']) ?>" loading="lazy" width="320" height="180" class="thumb-main" style="opacity:.8">
                <span class="video-duration"><?= (int)$p['video_count'] ?> videos</span>
              </div>
              <div class="video-info" style="padding:10px 0">
                <div class="video-title"><?= e($p['title']) ?></div>
                <div class="video-meta">
                  <span><?= ucfirst($p['visibility']) ?></span>
                  <span>·</span>
                  <span>Created <?= time_ago($p['created_at']) ?></span>
                </div>
                <?php if ($p['description']): ?>
                  <div style="font-size:.78rem;color:var(--text2);margin-top:4px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis"><?= e($p['description']) ?></div>
                <?php endif; ?>
              </div>
              <form method="POST" style="position:absolute;top:8px;right:8px;z-index:5" onclick="event.stopPropagation()">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="playlist_id" value="<?= $p['id'] ?>">
                <button type="submit" class="btn btn-sm" title="Delete playlist"
                        style="background:rgba(0,0,0,.7);color:#fff;border:none;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;font-size:.9rem"
                        onclick="return confirm('Delete this playlist?')">&times;</button>
              </form>
            </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </main>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
}
