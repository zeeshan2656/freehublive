<?php // FreeHub.Live — Shared Footer ?>
<footer class="site-footer">
  <div class="container">
    <div class="grid grid-4" style="margin-bottom:32px">
      <div>
        <div style="margin-bottom:10px"><?= render_site_logo('footer', false) ?></div>
        <p style="font-size:.83rem;color:var(--text2);line-height:1.7"><?= e(setting('site_tagline','Watch. Share. Earn.')) ?></p>
      </div>
      <div>
        <div style="font-weight:600;margin-bottom:10px;font-size:.9rem">Platform</div>
        <div class="flex-col gap-2">
          <a href="<?= BASE_URL ?>/" class="text-muted text-sm" style="transition:color .15s" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color=''">Home</a>
          <a href="<?= BASE_URL ?>/search.php" class="text-muted text-sm">Trending</a>
          <a href="<?= BASE_URL ?>/categories.php" class="text-muted text-sm">Categories</a>
          <a href="<?= BASE_URL ?>/live.php" class="text-muted text-sm">Live</a>
        </div>
      </div>
      <div>
        <div style="font-weight:600;margin-bottom:10px;font-size:.9rem">Programs</div>
        <div class="flex-col gap-2">
          <a href="<?= BASE_URL ?>/affiliate/register.php" class="text-muted text-sm">Affiliate Program</a>
          <a href="<?= BASE_URL ?>/auth/register.php?role=creator" class="text-muted text-sm">Creator Program</a>
          <a href="<?= BASE_URL ?>/advertise.php" class="text-muted text-sm">Advertise</a>
        </div>
      </div>
      <div>
        <div style="font-weight:600;margin-bottom:10px;font-size:.9rem">Legal</div>
        <div class="flex-col gap-2">
          <a href="<?= BASE_URL ?>/terms.php" class="text-muted text-sm">Terms of Service</a>
          <a href="<?= BASE_URL ?>/privacy.php" class="text-muted text-sm">Privacy Policy</a>
          <a href="<?= BASE_URL ?>/dmca.php" class="text-muted text-sm">DMCA</a>
          <a href="<?= BASE_URL ?>/contact.php" class="text-muted text-sm">Contact</a>
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
<script src="<?= BASE_URL ?>/assets/js/dashboard-router.js?v=<?= filemtime(__DIR__ . '/../assets/js/dashboard-router.js') ?>"></script>
<?php endif; ?>

</body>
</html>
