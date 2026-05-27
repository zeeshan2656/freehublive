// ============================================================
// FreeHub.Live — AJAX Dashboard Router & Layout Controller
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
    if (sidebar?.classList.contains('open')) {
      closeSidebar();
    } else {
      openSidebar();
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

  // ── AJAX Page Navigation (PJAX) ───────────────────────────
  const progress = document.getElementById('dashboard-progress');

  function loadDashboardPage(url, pushState = true) {
    // Close user dropdown if open during AJAX page load
    const dashDropMenu = document.getElementById('dashboard-user-dropdown-menu');
    if (dashDropMenu) {
      dashDropMenu.classList.remove('open');
      const dashDropBtn = document.getElementById('dashboard-user-dropdown-btn');
      if (dashDropBtn) dashDropBtn.setAttribute('aria-expanded', 'false');
    }

    if (progress) {
      progress.style.width = '30%';
      progress.style.opacity = '1';
    }

    fetch(url)
      .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.text();
      })
      .then(html => {
        if (progress) progress.style.width = '70%';
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        // Check if destination is a dashboard layout page
        const newContent = doc.getElementById('dashboard-ajax-content');
        if (!newContent) {
          window.location.href = url; // Fallback to full load
          return;
        }

        // 1. Update Title
        document.title = doc.title;

        // 2. Update Content
        const currentContent = document.getElementById('dashboard-ajax-content');
        if (currentContent) {
          currentContent.innerHTML = newContent.innerHTML;
        }

        // 3. Update Page Title Label in Sticky Header
        const newTitle = doc.getElementById('page-title-label');
        const currentTitle = document.getElementById('page-title-label');
        if (newTitle && currentTitle) {
          currentTitle.innerHTML = newTitle.innerHTML;
        }

        // 4. Update Page Action Buttons in Sticky Header
        const newActions = doc.getElementById('page-actions-container');
        const currentActions = document.getElementById('page-actions-container');
        if (newActions && currentActions) {
          currentActions.innerHTML = newActions.innerHTML;
        }

        // 5. Update Sidebar Links Active States
        const origin = window.location.origin;
        const currentPath = new URL(url, origin).pathname;
        const currentSearch = new URL(url, origin).search;

        document.querySelectorAll('.studio-nav-item').forEach(link => {
          const hrefAttr = link.getAttribute('href');
          if (!hrefAttr) return;
          const linkUrl = new URL(hrefAttr, origin);
          
          // Match path, and optionally specific query parameters (like ?status or ?tab or ?filter)
          const isSamePath = linkUrl.pathname === currentPath;
          let isSameQuery = true;
          if (linkUrl.search !== '') {
            isSameQuery = currentSearch.includes(linkUrl.search) || linkUrl.search === currentSearch;
          }

          if (isSamePath && isSameQuery) {
            link.classList.add('active');
          } else {
            link.classList.remove('active');
          }
        });

        // 6. Update URL in Browser History
        if (pushState) {
          history.pushState({ url: url }, '', url);
        }

        // 7. Extract and execute Javascript scripts in the loaded page
        doc.querySelectorAll('#dashboard-ajax-content script, script[data-page-script]').forEach(script => {
          const newScript = document.createElement('script');
          if (script.src) {
            // Avoid reloading standard global scripts
            if (script.src.includes('app.js') || script.src.includes('ads.js') || script.src.includes('dashboard-router.js')) return;
            newScript.src = script.src;
          } else {
            newScript.textContent = script.textContent;
          }
          document.body.appendChild(newScript);
        });

        // 8. Re-evaluate / dispatch loaded event for page scripts
        document.dispatchEvent(new CustomEvent('dashboard-page-loaded', { detail: { url: url } }));
        
        // 9. Re-bind dropdown & listeners if elements inside got replaced
        rebindDashboardEvents();

        // Scroll to top of view
        const viewPort = document.querySelector('.dashboard-main-viewport');
        if (viewPort) viewPort.scrollTop = 0;

        if (progress) {
          progress.style.width = '100%';
          setTimeout(() => { progress.style.opacity = '0'; }, 300);
        }
      })
      .catch(err => {
        console.error('AJAX Load failed, redirecting:', err);
        window.location.href = url;
      });
  }

  // Intercept all link clicks in the dashboard
  function interceptDashboardLinks() {
    document.addEventListener('click', function(e) {
      const link = e.target.closest('a');
      if (!link) return;

      const href = link.getAttribute('href');
      if (!href) return;

      // Skip special/external links
      if (
        href.startsWith('#') ||
        href.startsWith('javascript:') ||
        href.startsWith('mailto:') ||
        href.startsWith('tel:') ||
        link.getAttribute('target') === '_blank' ||
        link.classList.contains('logout-link') ||
        href.includes('logout.php')
      ) {
        return;
      }

      // Check if it's an internal link
      const origin = window.location.origin;
      const linkUrl = new URL(href, window.location.href);
      if (linkUrl.origin !== origin) return;

      // Determine if it's a dashboard-related route
      const path = linkUrl.pathname;
      const isDashboardPage = (
        path.includes('/admin/') ||
        path.includes('/creator/') ||
        path.endsWith('dashboard.php') ||
        path.endsWith('withdrawal.php') ||
        path.endsWith('settings.php') ||
        path.endsWith('profile.php')
      );

      if (isDashboardPage) {
        e.preventDefault();
        loadDashboardPage(href);
      }
    });
  }

  // Handle back/forward history navigation
  window.addEventListener('popstate', function(e) {
    if (e.state && e.state.url) {
      loadDashboardPage(e.state.url, false);
    } else {
      // Default fallback
      loadDashboardPage(window.location.href, false);
    }
  });

  // Page rebind utility
  function rebindDashboardEvents() {
    // Toggles, forms validation and bindings inside Ajax content
    // Setup file inputs, tab switching etc. if needed
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
  interceptDashboardLinks();
  rebindDashboardEvents();
});
