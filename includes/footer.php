<?php // FreeHub.Live — Shared Footer ?>
<?php if (isset($is_reels) && $is_reels): ?>
  </div> <!-- /reels-page-wrapper -->
  
  <script src="<?= fh_asset_url('assets/js/app.min.js') ?>" defer></script>
  <script src="<?= fh_asset_url('assets/js/ads.min.js') ?>" defer></script>

  <?php require_once __DIR__ . '/partials/global-upload-widget.php'; ?>

  <?php
  $is_admin_page = str_contains($_SERVER['PHP_SELF'] ?? '', '/admin/');
  if (!$is_admin_page && setting('ad_code_body_enabled', '0') === '1' && setting('ad_code_body_placement', 'bottom') === 'bottom' && !empty($ad_code_b = setting('ad_code_body'))):
      echo $ad_code_b . "\n";
  endif;
  ?>
  </body>
  </html>
<?php else: ?>
<!-- Above Footer Ad Placement -->
<div class="ad-above-footer-container" style="padding: 0 20px; width: 100%; max-width: 1400px; margin: 20px auto 0;">
  <?= render_ad_placeholder('above_footer') ?>
</div>

<footer class="site-footer">
  <div class="container">
    <div class="site-footer-bottom" style="display:flex; justify-content:space-between; align-items:center; padding: 20px 0; border-top: 1px solid var(--border)">
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

<script src="<?= fh_asset_url('assets/js/app.min.js') ?>" defer></script>
<script src="<?= fh_asset_url('assets/js/ads.min.js') ?>" defer></script>

<?php require_once __DIR__ . '/partials/date_filter_modal.php'; ?>

<?php if ($is_dashboard_page): ?>
    </div> <!-- /dashboard-ajax-content -->
  </div> <!-- /dashboard-main-viewport -->
</div> <!-- /dashboard-layout -->

<?php endif; ?>
<?php
if (!$is_admin_page && setting('ad_code_body_enabled', '0') === '1' && setting('ad_code_body_placement', 'bottom') === 'bottom' && !empty($ad_code_b = setting('ad_code_body'))):
    echo $ad_code_b . "\n";
endif;
?>
<?php require_once __DIR__ . '/partials/global-upload-widget.php'; ?>

</body>
</html>
<?php endif; ?>
