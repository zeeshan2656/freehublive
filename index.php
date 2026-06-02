<?php
// ============================================================
// FreeHub.Live — Homepage
// ============================================================
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$meta_title = 'FreeHub — Watch, Share & Earn';
$meta_desc  = 'Discover trending videos, share and earn with our affiliate program.';
require_once __DIR__ . '/includes/header.php';

// ── Selected category filter ─────────────────────────────────
$sel_cat = (int)($_GET['cat'] ?? $_COOKIE['fh_category'] ?? 0);
$cat_filter = "";
$params = [];
if ($sel_cat) {
    $cat_filter = " AND (v.category_id = ? OR EXISTS (SELECT 1 FROM video_categories vc WHERE vc.video_id = v.id AND vc.category_id = ?))";
    $params = [$sel_cat, $sel_cat];
}

// ── Fetch hero / featured video ──────────────────────────────
$hero = null;

// ── Trending ─────────────────────────────────────────────────
$trending = [];

// ── Latest ───────────────────────────────────────────────────
$latest = db_fetchAll_cached(
    "SELECT v.*,u.username,u.channel_name,u.avatar
     FROM videos v
     JOIN users u ON u.id=v.user_id
     WHERE v.status='published' AND v.visibility='public' {$cat_filter}
     ORDER BY v.created_at DESC LIMIT 51",
    $params,
    60
);

// ── Categories ───────────────────────────────────────────────
$categories = db_fetchAll_cached(
    "SELECT c.*,(SELECT COUNT(*) FROM videos WHERE category_id=c.id AND status='published') as video_count
     FROM categories c WHERE c.is_active=1 ORDER BY c.sort_order LIMIT 10",
    [],
    60
);

// ── Selected Category Videos List ────────────────────────────
$cat_videos = [];


// Duration sync removed for performance — durations are synced on watch.php instead

$ref = auth_user()['ref_code'] ?? '';
$creatorId    = (is_logged_in() && is_creator()) ? (int)auth_user()['id'] : 0;
$earningsMap  = [];
if ($creatorId) {
    $pool = $latest;
    $ownIds = [];
    foreach ($pool as $row) {
        if ((int)($row['user_id'] ?? 0) === $creatorId) {
            $ownIds[] = (int)$row['id'];
        }
    }
    if ($ownIds) {
        $earningsMap = fh_creator_video_earnings_map($creatorId, $ownIds);
    }
}
?>

<div class="layout">
  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

  <!-- ── Main ── -->
  <main class="main-content" id="main">
    <?php foreach (get_flash() as $f): ?>
      <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
    <?php endforeach; ?>

    <!-- Latest Uploads -->
    <?php if ($latest): ?>
    <section class="section">
      <div class="section-header">
        <h2 class="section-title">&#127381; Latest Uploads</h2>
        <a href="<?= BASE_URL ?>/search.php?sort=latest" class="see-all">See all &rarr;</a>
      </div>
      <div class="grid grid-4" id="video-grid">
        <?php 
        $latest_subset = array_slice($latest, 0, 51);
        $i = 0;
        foreach ($latest_subset as $v) {
            $i++;
            echo render_video_card($v, fh_video_card_opts($v, $earningsMap, $ref));
            if ($i % 10 === 0) {
                $ad_key = ($sel_cat > 0) ? 'category_grid_' . $i : 'landing_latest_' . $i;
                echo render_ad_card($ad_key);
            }
        }
        if ($i < 10) {
            echo render_ad_card(($sel_cat > 0) ? 'category_grid' : 'landing_latest');
        }
        ?>
      </div>
      <?php if (count($latest) >= 51): ?>
      <div style="text-align:center;margin-top:24px">
        <button class="btn btn-outline" id="load-more" data-page="2">Load More</button>
      </div>
      <?php endif; ?>
    </section>
    <?= render_ad_placeholder('between_sections_3') ?>
    <?php endif; ?>

    <!-- No videos state -->
    <?php if (!$latest): ?>
    <div style="text-align:center;padding:80px 20px;color:var(--text2)">
      <svg width="64" height="64" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 16px;opacity:.4"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
      <h2 style="font-size:1.2rem;margin-bottom:8px;color:var(--text)">No videos yet</h2>
      <p>Be the first to upload — <a href="<?= BASE_URL ?>/creator/" style="color:var(--accent)">Join Creator Program</a></p>
    </div>
    <?php endif; ?>
  </main>
</div>

<script>
const FH_CREATOR_ID = <?= (int)$creatorId ?>;


// ── Infinite scroll / Load More ───────────────────────────────
function bindLoadMore() {
  const loadMoreBtn = document.getElementById('load-more');
  if (!loadMoreBtn) return;
  
  // Remove existing listeners by cloning the button
  const newBtn = loadMoreBtn.cloneNode(true);
  loadMoreBtn.parentNode.replaceChild(newBtn, loadMoreBtn);
  
  newBtn.addEventListener('click', async function(){
    const btn = this;
    const page = parseInt(btn.dataset.page);
    btn.textContent = 'Loading…';
    btn.disabled = true;
    try {
      const catId = localStorage.getItem('fh_selected_category') || 0;
      const res = await fetch(`<?= BASE_URL ?>/api/videos.php?page=${page}&per_page=48&cat=${catId}`);
      const data = await res.json();
      const grid = document.getElementById('video-grid');
      if (data.videos && data.videos.length) {
        data.videos.forEach(v => {
          const el = document.createElement('article');
          el.className = 'video-card fade-in';
          const watchUrl = v.url || '<?= BASE_URL ?>/watch.php?v=' + encodeURIComponent(v.id);
          const channelUrl = '<?= BASE_URL ?>/channel.php?id=' + v.user_id + '&tab=videos';
          el.onclick = () => location.href = watchUrl;
          const durBadge = v.duration_fmt
            ? `<span class="video-duration">${v.duration_fmt}</span>`
            : `<span class="video-duration video-duration--pending">…</span>`;
          const earnHtml = (FH_CREATOR_ID && v.user_id === FH_CREATOR_ID && v.earnings_fmt)
            ? `<div class="video-card-earnings-box" title="Watch-time earnings on this video"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg><span>${v.earnings_fmt} earned</span></div>`
            : '';
          el.innerHTML = `
            <div class="video-thumb" style="position:relative">
              <img src="${v.thumbnail}" alt="${v.title}" loading="lazy" width="320" height="180" class="thumb-main">
              ${durBadge}
            </div>
            <div class="video-card-body">
              <div class="video-card-info-wrap">
                <a href="${channelUrl}" onclick="event.stopPropagation();" style="display:inline-block;flex-shrink:0;border-radius:50%;overflow:hidden;width:44px;height:44px;">
                  <img src="${v.avatar}" alt="${v.channel}" class="video-card-avatar" loading="lazy" width="44" height="44" style="width:100%;height:100%;object-fit:cover">
                </a>
                <div style="min-width:0;">
                  <div class="video-title">${v.title}</div>
                  <div class="video-card-subtitle">
                    <a href="${channelUrl}" onclick="event.stopPropagation();" class="video-card-channel-link">${v.channel}</a>
                  </div>
                </div>
              </div>
              <div class="video-card-stats-row">
                <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>${v.views}</span>
                <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>${v.likes || 0}</span>
                <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M8 9h8M8 13h4"/></svg>${v.comments || 0}</span>
                <span>·</span>
                <span>${v.ago}</span>
              </div>
              ${earnHtml}
            </div>`;
          grid.appendChild(el);
        });
        btn.dataset.page = page + 1;
        btn.textContent = 'Load More';
        btn.disabled = false;
        if (!data.has_next) btn.style.display = 'none';
      } else {
        btn.style.display = 'none';
      }
    } catch(e) {
      btn.textContent = 'Load More';
      btn.disabled = false;
    }
  });
}
bindLoadMore();
window.bindLoadMore = bindLoadMore;
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
