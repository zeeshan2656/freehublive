<?php // FreeHub.Live — Shared Footer ?>
<style>
.footer-link {
  transition: all 0.15s ease;
  display: inline-block;
  text-decoration: none;
}
.footer-link:hover {
  color: var(--accent) !important;
  transform: translateX(4px);
}
</style>

<!-- Above Footer Ad Placement -->
<div class="ad-above-footer-container" style="padding: 0 20px; width: 100%; max-width: 1400px; margin: 20px auto 0;">
  <?= render_ad_placeholder('above_footer') ?>
</div>

<footer class="site-footer">
  <div class="container">
    <?php
    $sections = db_fetchAll("SELECT * FROM footer_sections ORDER BY sort_order ASC, id ASC");
    ?>
    <div class="grid footer-grid" style="margin-bottom:16px">
      <?php foreach ($sections as $section): ?>
      <div>
        <div class="footer-section-title" style="font-weight:600;margin-bottom:12px;font-size:.9rem;color:var(--text)"><?= e($section['name']) ?></div>
        <div class="footer-section-links">
          <?php
          $sec_pages = db_fetchAll(
              "SELECT title, slug FROM pages WHERE footer_section_id = ? AND is_published = 1 ORDER BY id ASC",
              [$section['id']]
          );
          
          $has_home = (strtolower(trim($section['name'])) === 'platform');
          if ($has_home): ?>
            <a href="<?= BASE_URL ?>/" class="text-muted text-sm footer-link">Home</a>
          <?php endif; ?>
          
          <?php foreach ($sec_pages as $idx => $p): ?>
            <?php if ($idx > 0 || $has_home): ?>
              <span class="footer-section-separator">·</span>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/page.php?slug=<?= e($p['slug']) ?>" class="text-muted text-sm footer-link"><?= e($p['title']) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="site-footer-bottom">
      <span>&copy; <?= date('Y') ?> <?= e(setting('site_name','FreeHub')) ?>. All rights reserved.</span>
      <span>v<?= VERSION ?></span>
    </div>
    <?php
    $is_admin_page = str_contains($_SERVER['PHP_SELF'] ?? '', '/admin/');
    if (!$is_admin_page && setting('ad_code_footer_enabled', '0') === '1' && !empty($ad_code_f = setting('ad_code_footer'))):
        echo $ad_code_f . "\n";
    endif;
    ?>
  </div>
</footer>

<script src="<?= fh_asset_url('assets/js/app.js') ?>" defer></script>
<script src="<?= fh_asset_url('assets/js/ads.js') ?>" defer></script>
<?php if (is_logged_in()): ?>
<script src="<?= fh_asset_url('assets/js/earnings-poll.js') ?>" defer></script>
<?php endif; ?>
<?php require_once __DIR__ . '/partials/date_filter_modal.php'; ?>

<?php if ($is_dashboard_page): ?>
    </div> <!-- /dashboard-ajax-content -->
  </div> <!-- /dashboard-main-viewport -->
</div> <!-- /dashboard-layout -->

<div id="dashboard-progress" style="position:fixed; top:0; left:0; height:3px; background:var(--accent); width:0%; opacity:0; transition:width 0.2s ease, opacity 0.2s ease; z-index:99999; pointer-events:none"></div>
<script src="<?= fh_asset_url('assets/js/dashboard-router.js') ?>" defer></script>
<?php endif; ?>
<?php
if (!$is_admin_page && setting('ad_code_body_enabled', '0') === '1' && setting('ad_code_body_placement', 'bottom') === 'bottom' && !empty($ad_code_b = setting('ad_code_body'))):
    echo $ad_code_b . "\n";
endif;
?>

</body>
</html>
