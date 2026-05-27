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
<footer class="site-footer">
  <div class="container">
    <div class="grid grid-4" style="margin-bottom:32px">
      <!-- Column 1: Brand Info -->
      <div>
        <div style="margin-bottom:10px"><?= render_site_logo('footer', false) ?></div>
        <p style="font-size:.83rem;color:var(--text2);line-height:1.7"><?= e(setting('site_tagline','Watch. Share. Earn.')) ?></p>
      </div>
      
      <!-- Column 2: Platform -->
      <div>
        <div style="font-weight:600;margin-bottom:12px;font-size:.9rem;color:var(--text)">Platform</div>
        <div class="flex-col gap-2">
          <a href="<?= BASE_URL ?>/" class="text-muted text-sm footer-link">Home</a>
          <a href="<?= BASE_URL ?>/page.php?slug=about-us" class="text-muted text-sm footer-link">About Us</a>
          <a href="<?= BASE_URL ?>/page.php?slug=contact-us" class="text-muted text-sm footer-link">Contact Us</a>
        </div>
      </div>
      
      <!-- Column 3: Guidelines -->
      <div>
        <div style="font-weight:600;margin-bottom:12px;font-size:.9rem;color:var(--text)">Programs & Guidelines</div>
        <div class="flex-col gap-2">
          <a href="<?= BASE_URL ?>/page.php?slug=creator-page" class="text-muted text-sm footer-link">Creator Page</a>
          <a href="<?= BASE_URL ?>/page.php?slug=viewer-page" class="text-muted text-sm footer-link">Viewer Page</a>
          <a href="<?= BASE_URL ?>/page.php?slug=community-guidelines" class="text-muted text-sm footer-link">Community Guidelines</a>
        </div>
      </div>
      
      <!-- Column 4: Legal -->
      <div>
        <div style="font-weight:600;margin-bottom:12px;font-size:.9rem;color:var(--text)">Legal & Policies</div>
        <div class="flex-col gap-2">
          <a href="<?= BASE_URL ?>/page.php?slug=privacy-policy" class="text-muted text-sm footer-link">Privacy Policy</a>
          <a href="<?= BASE_URL ?>/page.php?slug=disclaimer" class="text-muted text-sm footer-link">Disclaimer</a>
          <a href="<?= BASE_URL ?>/page.php?slug=payment-policy" class="text-muted text-sm footer-link">Payment & Payout Policy</a>
          <a href="<?= BASE_URL ?>/page.php?slug=terms-conditions" class="text-muted text-sm footer-link">Terms & Conditions</a>
        </div>
      </div>
    </div>
    <div class="site-footer-bottom">
      <span>&copy; <?= date('Y') ?> <?= e(setting('site_name','FreeHub')) ?>. All rights reserved.</span>
      <span>v<?= VERSION ?></span>
    </div>
  </div>
</footer>

<script src="<?= BASE_URL ?>/assets/js/app.js?v=<?= filemtime(__DIR__ . '/../assets/js/app.js') ?>" defer></script>
<script src="<?= BASE_URL ?>/assets/js/ads.js?v=<?= filemtime(__DIR__ . '/../assets/js/ads.js') ?>" defer></script>
<?php if (is_logged_in()): ?>
<script src="<?= BASE_URL ?>/assets/js/earnings-poll.js?v=<?= filemtime(__DIR__ . '/../assets/js/earnings-poll.js') ?>" defer></script>
<?php endif; ?>
<?php require_once __DIR__ . '/partials/date_filter_modal.php'; ?>

<?php if ($is_dashboard_page): ?>
    </div> <!-- /dashboard-ajax-content -->
  </div> <!-- /dashboard-main-viewport -->
</div> <!-- /dashboard-layout -->

<div id="dashboard-progress" style="position:fixed; top:0; left:0; height:3px; background:var(--accent); width:0%; opacity:0; transition:width 0.2s ease, opacity 0.2s ease; z-index:99999; pointer-events:none"></div>
<script src="<?= BASE_URL ?>/assets/js/dashboard-router.js?v=<?= filemtime(__DIR__ . '/../assets/js/dashboard-router.js') ?>" defer></script>
<?php endif; ?>

</body>
</html>
