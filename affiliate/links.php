<?php
// Affiliate — Share Links page
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['affiliate','admin']);
$site_theme = setting('active_theme', 'dark-minimal');
$primary    = setting('primary_color', '#6366f1');
$uid  = auth_user()['id'];
$user = db_fetch("SELECT * FROM users WHERE id=?",[$uid]);
$ref  = $user['ref_code'];

// Recent published videos to share
$videos = db_fetchAll(
    "SELECT v.*,u.channel_name,u.username FROM videos v JOIN users u ON u.id=v.user_id
     WHERE v.status='published' AND v.visibility='public' ORDER BY v.views DESC LIMIT 20"
);
?><!DOCTYPE html>
<html lang="en" data-theme="<?= e($site_theme) ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Share Links — <?= e(setting('site_name','FreeHub')) ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
<style>:root{--accent:<?= e($primary) ?>;--accent2:<?= e($primary) ?>cc}</style>
<script>const _st=localStorage.getItem('fh_theme');if(_st)document.documentElement.setAttribute('data-theme',_st);</script>
<style>
.aff-layout{display:grid;grid-template-columns:220px 1fr;min-height:100vh}
.aff-sidebar{background:var(--bg2);border-right:1px solid var(--border);padding:20px 12px}
.aff-nav-item{display:flex;align-items:center;gap:9px;padding:9px 12px;border-radius:8px;color:var(--text2);font-size:.875rem;font-weight:500;transition:all .15s;margin-bottom:2px}
.aff-nav-item:hover{background:var(--bg3);color:var(--text)}
.aff-nav-item.active{background:rgba(99,102,241,.12);color:var(--accent)}
.link-row{display:flex;align-items:center;gap:12px;padding:12px;border-radius:var(--radius);border:1px solid var(--border);margin-bottom:8px;background:var(--bg2);transition:border-color .15s}
.link-row:hover{border-color:var(--accent)}
.copy-btn{flex-shrink:0;padding:6px 14px;background:var(--accent);color:#fff;border:none;border-radius:6px;font-size:.78rem;font-weight:600;cursor:pointer;transition:filter .15s}
.copy-btn:hover{filter:brightness(1.1)}
</style>
</head>
<body>
<div class="studio-sidebar-backdrop" id="studio-sidebar-backdrop"></div>
<div class="studio-mobile-bar" style="display:none; height:48px; background:var(--bg2); border-bottom:1px solid var(--border); align-items:center; padding:0 16px; position:fixed; top:0; left:0; right:0; z-index:90">
  <button class="btn-icon" id="studio-sidebar-toggle" style="margin-right:8px; display:flex; align-items:center; justify-content:center" aria-label="Toggle Menu">
    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
  </button>
  <span style="font-weight:700; font-size:.9rem">Affiliate Panel</span>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const toggleBtn = document.getElementById('studio-sidebar-toggle');
  const sidebar = document.querySelector('.aff-sidebar');
  const backdrop = document.getElementById('studio-sidebar-backdrop');
  
  toggleBtn?.addEventListener('click', function(e) {
    e.stopPropagation();
    sidebar?.classList.toggle('open');
    backdrop?.classList.toggle('active');
  });
  
  backdrop?.addEventListener('click', function() {
    sidebar?.classList.remove('open');
    this.classList.remove('active');
  });
});
</script>
<div class="aff-layout">
  <aside class="aff-sidebar">
    <div style="padding:4px 4px 20px"><?= render_site_logo('studio') ?></div>
    <a href="<?= BASE_URL ?>/affiliate/" class="aff-nav-item">&#128202; Dashboard</a>
    <a href="<?= BASE_URL ?>/affiliate/links.php" class="aff-nav-item active">&#128279; My Links</a>
    <a href="<?= BASE_URL ?>/affiliate/analytics.php" class="aff-nav-item">&#128200; Analytics</a>
    <a href="<?= BASE_URL ?>/affiliate/earnings.php" class="aff-nav-item">&#128176; Earnings</a>
    <a href="<?= BASE_URL ?>/profile.php" class="aff-nav-item">&#128100; Edit Profile</a>
    <a href="<?= BASE_URL ?>/auth/logout.php" class="aff-nav-item" style="color:var(--red)">&#x21B5; Logout</a>
  </aside>

  <main style="padding:28px">
    <h1 style="font-size:1.2rem;font-weight:800;margin-bottom:8px">Share Links</h1>
    <p class="text-muted text-sm" style="margin-bottom:24px">Share these links. Every click and view through your link earns you money.</p>

    <!-- Master link -->
    <div class="card" style="margin-bottom:24px;background:linear-gradient(135deg,rgba(99,102,241,.15),rgba(129,140,248,.05))">
      <div style="font-weight:700;margin-bottom:8px">&#127975; Homepage Referral Link</div>
      <div class="flex gap-2">
        <input class="form-input" id="master-link" value="<?= BASE_URL ?>/?ref=<?= $ref ?>" readonly style="font-size:.85rem">
        <button class="copy-btn" onclick="copyLink('master-link',this)">Copy</button>
      </div>
      <div class="flex gap-2" style="margin-top:10px;flex-wrap:wrap">
        <a href="https://wa.me/?text=<?= urlencode('Check out '.setting('site_name','FreeHub').'! '.BASE_URL.'/?ref='.$ref) ?>" target="_blank" class="btn btn-sm btn-outline">&#128242; WhatsApp</a>
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(BASE_URL.'/?ref='.$ref) ?>" target="_blank" class="btn btn-sm btn-outline">&#128100; Facebook</a>
        <a href="https://twitter.com/intent/tweet?url=<?= urlencode(BASE_URL.'/?ref='.$ref) ?>&text=<?= urlencode('Watch amazing videos on '.setting('site_name','FreeHub').'!') ?>" target="_blank" class="btn btn-sm btn-outline">&#128038; X / Twitter</a>
        <a href="https://t.me/share/url?url=<?= urlencode(BASE_URL.'/?ref='.$ref) ?>" target="_blank" class="btn btn-sm btn-outline">&#9992; Telegram</a>
      </div>
    </div>

    <!-- Per-video links -->
    <h2 style="font-size:1rem;font-weight:700;margin-bottom:12px">Video Share Links</h2>
    <?php foreach($videos as $i => $v):
      $link = BASE_URL.'/watch.php?v='.$v['id'].'&ref='.$ref;
    ?>
    <div class="link-row">
      <img src="<?= thumb_url($v['thumbnail']) ?>" style="width:72px;aspect-ratio:16/9;object-fit:cover;border-radius:4px;flex-shrink:0" loading="lazy">
      <div style="min-width:0;flex:1">
        <div style="font-size:.85rem;font-weight:600;overflow:hidden;white-space:nowrap;text-overflow:ellipsis"><?= e($v['title']) ?></div>
        <div class="text-xs text-muted"><?= e($v['channel_name']??$v['username']) ?> · <?= format_number((int)$v['views']) ?> views</div>
        <div style="font-size:.75rem;color:var(--accent);margin-top:3px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis"><?= $link ?></div>
      </div>
      <button class="copy-btn" onclick="copyText('<?= addslashes($link) ?>',this)">Copy</button>
    </div>
    <?php endforeach; ?>
    <?php if(!$videos): ?><p class="text-muted text-sm">No videos available to share yet.</p><?php endif; ?>
  </main>
</div>

<script>
function copyLink(id, btn) {
  navigator.clipboard.writeText(document.getElementById(id).value);
  btn.textContent = 'Copied! ✓';
  setTimeout(() => btn.textContent = 'Copy', 2500);
}
function copyText(text, btn) {
  navigator.clipboard.writeText(text);
  btn.textContent = 'Copied! ✓';
  setTimeout(() => btn.textContent = 'Copy', 2500);
}
</script>
</body></html>
