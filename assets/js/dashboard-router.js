// ============================================================
// FreeHub.Live — Dashboard Layout Controller
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
  // ── Mobile Sidebar Toggles ─────────────────────────────────
  const sidebar = document.getElementById('dashboard-sidebar');
  const backdrop = document.getElementById('dashboard-sidebar-backdrop');
  const toggleBtn = document.getElementById('dashboard-sidebar-toggle');

  function openSidebar() {
    sidebar?.classList.add('open');
    backdrop?.classList.add('active');
  }

  function closeSidebar() {
    sidebar?.classList.remove('open');
    backdrop?.classList.remove('active');
  }

  toggleBtn?.addEventListener('click', function(e) {
    e.stopPropagation();
    if (window.innerWidth <= 768) {
      if (sidebar?.classList.contains('open')) {
        closeSidebar();
      } else {
        openSidebar();
      }
    } else {
      document.body.classList.toggle('sidebar-collapsed');
      const isCollapsed = document.body.classList.contains('sidebar-collapsed');
      localStorage.setItem('sidebar_collapsed', isCollapsed ? '1' : '0');
    }
  });

  backdrop?.addEventListener('click', closeSidebar);

  // Close sidebar on link click (mobile)
  sidebar?.querySelectorAll('.studio-nav-item').forEach(link => {
    link.addEventListener('click', closeSidebar);
  });

  // ── Dashboard User Dropdown Toggle ─────────────────────────
  const dashDropBtn = document.getElementById('dashboard-user-dropdown-btn');
  const dashDropMenu = document.getElementById('dashboard-user-dropdown-menu');
  if (dashDropBtn && dashDropMenu) {
    dashDropBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      const isOpen = dashDropMenu.classList.contains('open');
      
      // Close other open dropdowns
      document.querySelectorAll('.dropdown-menu.open').forEach(m => {
        if (m !== dashDropMenu) m.classList.remove('open');
      });
      document.querySelectorAll('[aria-haspopup="true"]').forEach(b => {
        if (b !== dashDropBtn) b.setAttribute('aria-expanded', 'false');
      });

      if (isOpen) {
        dashDropMenu.classList.remove('open');
        dashDropBtn.setAttribute('aria-expanded', 'false');
      } else {
        dashDropMenu.classList.add('open');
        dashDropBtn.setAttribute('aria-expanded', 'true');
      }
    });

    // Close when clicking anywhere else
    document.addEventListener('click', function(e) {
      const target = e.target;
      if (!dashDropBtn.contains(target) && !dashDropMenu.contains(target)) {
        dashDropMenu.classList.remove('open');
        dashDropBtn.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // ── Theme Cycle Integration ────────────────────────────────
  const themeBtn = document.getElementById('dashboard-theme-toggle');
  themeBtn?.addEventListener('click', function(e) {
    e.preventDefault();
    if (typeof window._cycleTheme === 'function') {
      window._cycleTheme();
    } else {
      // Fallback if _cycleTheme not defined
      const themes = ['light-white', 'dark-minimal', 'gray', 'light-blue', 'pink', 'red-black', 'green', 'light-green'];
      let current = document.documentElement.getAttribute('data-theme') || 'dark-minimal';
      let nextIndex = (themes.indexOf(current) + 1) % themes.length;
      let next = themes[nextIndex];
      document.documentElement.setAttribute('data-theme', next);
      localStorage.setItem('fh_theme', next);
    }
  });

  // Page tab binding utility
  function rebindDashboardEvents() {
    const tabButtons = document.querySelectorAll('.profile-tab[data-tab]');
    tabButtons.forEach(btn => {
      btn.addEventListener('click', function() {
        tabButtons.forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const targetTab = this.getAttribute('data-tab');
        document.querySelectorAll('.profile-tab-content').forEach(content => {
          content.style.display = content.id === `tab-${targetTab}` ? 'block' : 'none';
        });
      });
    });
  }

  // Init
  rebindDashboardEvents();
});
