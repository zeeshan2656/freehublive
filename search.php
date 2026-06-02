<?php
// ============================================================
// FreeHub.Live — Search Page
// ============================================================
$q    = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'relevance';
$cat  = (int)($_GET['cat'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$meta_title = ($q ? '"' . $q . '" — Search' : 'Search') . ' — ' . setting('site_name','FreeHub');
$meta_desc  = $q ? "Search results for \"$q\" on " . setting('site_name','FreeHub') : 'Search videos';

$where  = "v.status='published' AND v.visibility='public'";
$where_params = [];
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
    'views'  => 'v.views DESC',
    'likes'  => 'v.likes DESC',
    'oldest' => 'v.created_at ASC',
    'latest' => 'v.created_at DESC',
    default  => $q ? "MATCH(v.title,v.description,v.tags) AGAINST(?) DESC" : "v.created_at DESC",
};
// ORDER BY params are separate — db_count doesn't use ORDER BY
$order_params = [];
if ($sort === 'relevance' && $q) $order_params[] = $q . '*';

$total = db_count('videos v', $where, $where_params);

$all_params = array_merge($where_params, $order_params);
$videos = db_fetchAll(
    "SELECT v.*,u.username,u.channel_name,u.avatar
     FROM videos v JOIN users u ON u.id=v.user_id
     WHERE $where ORDER BY $order LIMIT 51",
    $all_params
);
$categories = db_fetchAll("SELECT * FROM categories WHERE is_active=1 ORDER BY sort_order");
$ref = auth_user()['ref_code'] ?? '';
$creatorId   = (is_logged_in() && is_creator()) ? (int)auth_user()['id'] : 0;
$earningsMap = [];
if ($creatorId && $videos) {
    $ownIds = [];
    foreach ($videos as $v) {
        if ((int)($v['user_id'] ?? 0) === $creatorId) {
            $ownIds[] = (int)$v['id'];
        }
    }
    if ($ownIds) {
        $earningsMap = fh_creator_video_earnings_map($creatorId, $ownIds);
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="layout">
  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
  <main class="main-content">
    <!-- Search Bar -->
    <form method="GET" action="<?= BASE_URL ?>/search.php" style="margin-bottom:20px">
      <div class="flex gap-3 search-form-row">
        <div class="search-bar search-bar-secondary" style="max-width:100%;flex:1">
          <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search videos, channels, tags…"
                 class="form-input" style="border-radius:24px;padding:11px 44px 11px 20px;font-size:1rem" autofocus>
          <button type="submit" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);color:var(--text2)">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          </button>
        </div>
        <select name="sort" class="form-input form-select" style="width:auto" onchange="this.form.submit()">
          <option value="relevance" <?= $sort==='relevance'?'selected':'' ?>>Most Relevant</option>
          <option value="views"     <?= $sort==='views'?'selected':'' ?>>Most Viewed</option>
          <option value="latest"    <?= $sort==='latest'?'selected':'' ?>>Latest</option>
          <option value="oldest"    <?= $sort==='oldest'?'selected':'' ?>>Oldest</option>
        </select>
      </div>
    </form>



    <!-- Results header -->
    <?php if ($q): ?>
    <div class="flex" style="justify-content:space-between;align-items:center;margin-bottom:16px">
      <p class="text-muted text-sm">
        <?= $total ?> result<?= $total!=1?'s':'' ?> for <strong style="color:var(--text)">"<?= e($q) ?>"</strong>
      </p>
    </div>
    <?php endif; ?>

    <!-- Grid -->
    <?php if ($videos): ?>
    <div class="grid grid-4" id="video-grid">
      <?php 
      $videos_subset = array_slice($videos, 0, 51);
      $i = 0;
      foreach ($videos_subset as $v) {
          $i++;
          echo render_video_card($v, fh_video_card_opts($v, $earningsMap, $ref));
          if ($i % 10 === 0) {
              echo render_ad_card('search_grid_' . $i);
          }
      }
      if ($i < 10) {
          echo render_ad_card('search_grid');
      }
      ?>
    </div>

    <!-- Pagination -->
    <?php if ($total > 51): ?>
    <div style="text-align:center;margin-top:24px">
      <button class="btn btn-outline" id="load-more" data-page="2" data-q="<?= e($q) ?>" data-sort="<?= e($sort) ?>" data-cat="<?= $cat ?>">Load More</button>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div style="text-align:center;padding:80px 20px;color:var(--text2)">
      <svg width="64" height="64" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 16px;opacity:.4"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      <h2 style="font-size:1.2rem;font-weight:700;color:var(--text);margin-bottom:8px">No results found</h2>
      <p>Try different keywords or <a href="<?= BASE_URL ?>/" style="color:var(--accent)">browse all videos</a></p>
    </div>
    <?php endif; ?>
  </main>
</div>

<script>
const FH_CREATOR_ID = <?= (int)$creatorId ?>;

function bindLoadMore() {
  const loadMoreBtn = document.getElementById('load-more');
  if (!loadMoreBtn) return;
  
  const newBtn = loadMoreBtn.cloneNode(true);
  loadMoreBtn.parentNode.replaceChild(newBtn, loadMoreBtn);
  
  newBtn.addEventListener('click', async function(){
    const btn = this;
    const page = parseInt(btn.dataset.page);
    const q = encodeURIComponent(btn.dataset.q || '');
    const sort = encodeURIComponent(btn.dataset.sort || 'relevance');
    const cat = parseInt(btn.dataset.cat || 0);
    
    btn.textContent = 'Loading…';
    btn.disabled = true;
    try {
      const res = await fetch(`<?= BASE_URL ?>/api/videos.php?page=${page}&per_page=48&q=${q}&sort=${sort}&cat=${cat}`);
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
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
