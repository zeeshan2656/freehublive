<?php
// Admin — SEO Management
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD']==='POST' && verify_csrf($_POST['csrf']??'')) {
    $keys = ['meta_keywords','meta_description','google_analytics','robots_txt','og_image'];
    foreach ($keys as $k) {
        $v = trim($_POST[$k]??'');
        db_query("INSERT INTO settings (`key`,`value`,`group`) VALUES (?,?,'seo') ON DUPLICATE KEY UPDATE `value`=?",[$k,$v,$v]);
    }
    flash('success','SEO settings saved.');
    redirect(BASE_URL.'/admin/seo.php');
}

$meta_title='SEO Management';
require_once __DIR__.'/partials/admin_head.php';
$sitemap_url = BASE_URL.'/sitemap.php';
?>
<div class="admin-content">
  <?php foreach(get_flash() as $f): ?><div class="alert alert-<?= $f['type'] ?>"><?= e($f['msg']) ?></div><?php endforeach; ?>

  <div class="stat-grid-2">
    <div class="card">
      <h3 style="font-weight:700;margin-bottom:16px">Meta Tags</h3>
      <form method="POST">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="form-group">
          <label class="form-label">Default Meta Keywords</label>
          <input class="form-input" type="text" name="meta_keywords" value="<?= e(setting('meta_keywords','')) ?>" placeholder="video, streaming, watch online">
        </div>
        <div class="form-group">
          <label class="form-label">Default Meta Description</label>
          <textarea class="form-input" name="meta_description" rows="3"><?= e(setting('meta_description','')) ?></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Default OG Image URL</label>
          <input class="form-input" type="url" name="og_image" value="<?= e(setting('og_image','')) ?>" placeholder="https://...">
        </div>
        <div class="form-group">
          <label class="form-label">Google Analytics ID</label>
          <input class="form-input" type="text" name="google_analytics" value="<?= e(setting('google_analytics','')) ?>" placeholder="G-XXXXXXXXXX">
        </div>
        <div class="form-group">
          <label class="form-label">Robots.txt content</label>
          <textarea class="form-input" name="robots_txt" rows="5" placeholder="User-agent: *&#10;Allow: /"><?= e(setting('robots_txt','User-agent: *'."\n".'Allow: /'."\n".'Sitemap: '.BASE_URL.'/sitemap.php')) ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Save SEO Settings</button>
      </form>
    </div>

    <div>
      <div class="card" style="margin-bottom:16px">
        <h3 style="font-weight:700;margin-bottom:12px">Sitemap</h3>
        <p class="text-sm text-muted" style="margin-bottom:12px">Auto-generated XML sitemap includes all public videos and pages.</p>
        <div class="flex gap-2">
          <input class="form-input" value="<?= $sitemap_url ?>" readonly style="font-size:.82rem">
          <a href="<?= $sitemap_url ?>" target="_blank" class="btn btn-outline btn-sm" style="white-space:nowrap">View</a>
        </div>
      </div>

      <div class="card" style="margin-bottom:16px">
        <h3 style="font-weight:700;margin-bottom:12px">Schema Markup</h3>
        <p class="text-sm text-muted" style="margin-bottom:8px">Structured data automatically added to every page:</p>
        <ul style="font-size:.82rem;color:var(--text2);line-height:2">
          <li>&#10003; VideoObject schema on watch pages</li>
          <li>&#10003; WebSite schema on homepage</li>
          <li>&#10003; BreadcrumbList on category pages</li>
          <li>&#10003; Organization schema sitewide</li>
        </ul>
      </div>

      <div class="card">
        <h3 style="font-weight:700;margin-bottom:12px">Performance Checklist</h3>
        <ul style="font-size:.82rem;line-height:2.2">
          <li style="color:var(--green)">&#10003; Gzip compression (.htaccess)</li>
          <li style="color:var(--green)">&#10003; Browser caching headers</li>
          <li style="color:var(--green)">&#10003; Lazy image loading</li>
          <li style="color:var(--green)">&#10003; Deferred JS loading</li>
          <li style="color:var(--green)">&#10003; File-based page caching</li>
          <li style="color:var(--green)">&#10003; PDO prepared statements</li>
          <li style="color:var(--green)">&#10003; Indexed DB queries</li>
        </ul>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__.'/partials/admin_foot.php'; ?>
