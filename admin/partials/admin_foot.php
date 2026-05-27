</div>

<script data-page-script="true">
(function() {
  const panel = document.getElementById('admin-add-panel');
  document.querySelectorAll('[data-scroll-to-add]').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      if (!panel) return;
      e.preventDefault();
      panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
      panel.classList.add('admin-add-panel--focus');
      const input = panel.querySelector('input[type="text"], input:not([type="hidden"])');
      if (input) setTimeout(function() { input.focus(); }, 400);
      setTimeout(function() { panel.classList.remove('admin-add-panel--focus'); }, 2000);
    });
  });
  if (panel && window.location.hash === '#admin-add-panel') {
    panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    panel.classList.add('admin-add-panel--focus');
    setTimeout(function() { panel.classList.remove('admin-add-panel--focus'); }, 2000);
  }
})();
</script>
<?php
require_once __DIR__ . '/../../includes/partials/date_filter_modal.php';
require_once __DIR__ . '/../../includes/footer.php';
?>
