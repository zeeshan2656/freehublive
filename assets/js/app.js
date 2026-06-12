// ============================================================
// FreeHub.Live — Core App JavaScript
// ============================================================

const FH_BASE = document.querySelector('meta[name="base-url"]')?.content || '';

/** Fill missing duration badges by reading video metadata in the browser. */
function fhInitPendingDurations() {
  const pending = document.querySelectorAll('.video-duration--pending[data-video-id]');
  if (!pending.length || !FH_BASE) return;

  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries, obs) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const el = entry.target;
          obs.unobserve(el);
          probeDuration(el);
        }
      });
    }, { rootMargin: '100px' });

    pending.forEach(el => observer.observe(el));
  } else {
    pending.forEach(el => probeDuration(el));
  }

  function probeDuration(el) {
    const videoId = el.dataset.videoId;
    const src = el.dataset.videoSrc;
    if (!videoId || !src || el.dataset.probing === '1') return;
    el.dataset.probing = '1';

    const v = document.createElement('video');
    v.preload = 'metadata';
    v.muted = true;
    v.src = src;
    v.addEventListener('loadedmetadata', () => {
      const seconds = Math.floor(v.duration || 0);
      if (seconds < 1) return;
      fetch(FH_BASE + '/api/thumbnails.php?action=save_duration', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        credentials: 'same-origin',
        body: JSON.stringify({video_id: parseInt(videoId, 10), duration: seconds})
      })
        .then(r => r.json())
        .then(data => {
          if (data.success && data.formatted) {
            el.textContent = data.formatted;
            el.classList.remove('video-duration--pending');
            delete el.dataset.probing;
          }
        })
        .catch(() => { delete el.dataset.probing; });
    }, {once: true});
    v.addEventListener('error', () => { delete el.dataset.probing; }, {once: true});
  }
}

function fhTriggerPendingDurations() {
  if (window.requestIdleCallback) {
    window.requestIdleCallback(() => fhInitPendingDurations());
  } else {
    setTimeout(fhInitPendingDurations, 100);
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', fhTriggerPendingDurations);
} else {
  fhTriggerPendingDurations();
}

// ── Lazy image loading ────────────────────────────────────────
if ('IntersectionObserver' in window) {
  const io = new IntersectionObserver((entries, obs) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        const img = e.target;
        if (img.dataset.src) { img.src = img.dataset.src; delete img.dataset.src; }
        obs.unobserve(img);
      }
    });
  }, { rootMargin: '200px' });
  document.querySelectorAll('img[data-src]').forEach(img => io.observe(img));
}

// ── Mobile sidebar toggle ─────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  const menuBtn = document.getElementById('mobile-sidebar-toggle');
  const sidebar  = document.querySelector('.sidebar');
  const backdrop = document.getElementById('sidebar-backdrop');
  if (!menuBtn || !sidebar) return;

  function openSidebar() {
    sidebar.classList.add('open');
    if (backdrop) backdrop.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  function closeSidebar() {
    sidebar.classList.remove('open');
    if (backdrop) backdrop.classList.remove('active');
    document.body.style.overflow = '';
  }

  menuBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
  });

  if (backdrop) {
    backdrop.addEventListener('click', closeSidebar);
  }

  // Close sidebar when clicking a nav-item inside it (on mobile)
  sidebar.addEventListener('click', function(e) {
    const navItem = e.target.closest('.nav-item, .category-select-item');
    if (navItem && window.innerWidth <= 768) {
      closeSidebar();
    }
  });
});

// ── Dashboard mobile/desktop sidebar toggle ───────────────────
document.addEventListener('DOMContentLoaded', function() {
  const dashboardToggle = document.getElementById('dashboard-sidebar-toggle');
  const dashboardSidebar = document.querySelector('.dashboard-sidebar-container');
  const dashboardBackdrop = document.getElementById('dashboard-sidebar-backdrop');

  if (dashboardToggle && dashboardSidebar) {
    function openDashboardSidebar() {
      dashboardSidebar.classList.add('open');
      if (dashboardBackdrop) dashboardBackdrop.classList.add('active');
      document.body.style.overflow = 'hidden';
    }

    function closeDashboardSidebar() {
      dashboardSidebar.classList.remove('open');
      if (dashboardBackdrop) dashboardBackdrop.classList.remove('active');
      document.body.style.overflow = '';
    }

    dashboardToggle.addEventListener('click', function(e) {
      e.stopPropagation();
      if (window.innerWidth <= 768) {
        dashboardSidebar.classList.contains('open') ? closeDashboardSidebar() : openDashboardSidebar();
      } else {
        const isCollapsed = document.body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebar_collapsed', isCollapsed ? '1' : '0');
      }
    });

    if (dashboardBackdrop) {
      dashboardBackdrop.addEventListener('click', closeDashboardSidebar);
    }

    dashboardSidebar.addEventListener('click', function(e) {
      const navItem = e.target.closest('.studio-nav-item');
      if (navItem && window.innerWidth <= 768) {
        closeDashboardSidebar();
      }
    });
  }
});

// ── Dropdown toggle on button click ──────────────────────────
document.addEventListener('click', e => {
  const target = e.target instanceof Element ? e.target : null;
  if (!target) return;

  // Check if a dropdown toggle button was clicked
  const toggleBtn = target.closest('[aria-haspopup="true"]');
  if (toggleBtn) {
    const dropdown = toggleBtn.closest('.dropdown');
    if (!dropdown) return;
    const menu = dropdown.querySelector('.dropdown-menu');
    if (!menu) return;

    const isOpen = menu.classList.contains('open');

    // Close all other open dropdowns first
    document.querySelectorAll('.dropdown-menu.open').forEach(m => m.classList.remove('open'));
    document.querySelectorAll('[aria-haspopup="true"]').forEach(b => b.setAttribute('aria-expanded', 'false'));

    if (!isOpen) {
      menu.classList.add('open');
      toggleBtn.setAttribute('aria-expanded', 'true');
    }
    return;
  }

  // Close all open dropdowns when clicking outside
  document.querySelectorAll('.dropdown-menu.open').forEach(m => {
    const dropdown = m.closest('.dropdown');
    if (!dropdown?.contains(target)) {
      m.classList.remove('open');
      const btn = dropdown?.querySelector('[aria-haspopup="true"]');
      if (btn) btn.setAttribute('aria-expanded', 'false');
    }
  });
});

// ── Category item click handler ───────────────────────────────
document.addEventListener('click', e => {
  const target = e.target instanceof Element ? e.target : null;
  if (!target) return;

  const item = target.closest('.category-select-item');
  if (!item) return;

  e.preventDefault();

  const catId   = parseInt(item.getAttribute('data-cat-id') || '0', 10);
  const catName = item.getAttribute('data-cat-name') || 'All Categories';

  // Close any open dropdown
  document.querySelectorAll('.dropdown-menu.open').forEach(m => m.classList.remove('open'));
  document.querySelectorAll('[aria-haspopup="true"]').forEach(b => b.setAttribute('aria-expanded', 'false'));

  // Also close mobile sidebar on selection
  const sidebar = document.querySelector('.sidebar');
  const backdrop = document.getElementById('sidebar-backdrop');
  if (sidebar) sidebar.classList.remove('open');
  if (backdrop) backdrop.classList.remove('active');

  if (typeof selectCategory === 'function') {
    selectCategory(catId, catName);
  }
});

// ── Auto-close alerts ─────────────────────────────────────────
document.querySelectorAll('.alert').forEach(a => {
  setTimeout(() => { a.style.opacity = '0'; a.style.transition = 'opacity .5s'; setTimeout(() => a.remove(), 500); }, 5000);
});

// ── Search autocomplete ───────────────────────────────────────
(function() {
  const input = document.getElementById('main-search');
  if (!input) return;
  let timer;
  const suggestions = document.createElement('div');
  suggestions.style.cssText = 'position:absolute;top:calc(100% + 4px);left:0;right:0;background:var(--bg2);border:1px solid var(--border);border-radius:10px;z-index:200;display:none;box-shadow:var(--shadow)';
  input.parentElement.style.position = 'relative';
  input.parentElement.appendChild(suggestions);

  input.addEventListener('input', () => {
    clearTimeout(timer);
    const q = input.value.trim();
    if (q.length < 2) { suggestions.style.display = 'none'; return; }
    timer = setTimeout(async () => {
      try {
        const res  = await fetch(FH_BASE + `/api/videos.php?q=${encodeURIComponent(q)}&per_page=5`);
        const data = await res.json();
        if (!data.videos?.length) { suggestions.style.display = 'none'; return; }
        suggestions.innerHTML = data.videos.map(v =>
          `<a href="${v.url}" style="display:flex;align-items:center;gap:10px;padding:10px 14px;color:var(--text);font-size:.85rem;transition:background .12s" onmouseover="this.style.background='var(--bg3)'" onmouseout="this.style.background=''">
            <img src="${v.thumbnail}" style="width:48px;aspect-ratio:16/9;object-fit:cover;border-radius:4px;flex-shrink:0">
            <span style="overflow:hidden;white-space:nowrap;text-overflow:ellipsis">${v.title}</span>
          </a>`
        ).join('');
        suggestions.style.display = 'block';
      } catch {}
    }, 280);
  });

  document.addEventListener('click', e => {
    const target = e.target instanceof Element ? e.target : null;
    if (!target || !input.contains(target)) suggestions.style.display = 'none';
  });
})();

// ── Toast notification system ─────────────────────────────────
window.toast = function(msg, type = 'info', duration = 3500) {
  const colors = { success: '#22c55e', error: '#ef4444', info: '#6366f1', warn: '#eab308' };
  const el = document.createElement('div');
  el.style.cssText = `position:fixed;bottom:24px;right:24px;background:var(--bg2);border:1px solid var(--border);
    border-left:4px solid ${colors[type]||colors.info};border-radius:10px;padding:14px 18px;
    font-size:.875rem;max-width:340px;z-index:9999;box-shadow:var(--shadow);
    animation:fadeIn .3s ease;color:var(--text)`;
  el.textContent = msg;
  document.body.appendChild(el);
  setTimeout(() => { el.style.opacity='0'; el.style.transition='opacity .4s'; setTimeout(()=>el.remove(),400); }, duration);
};

// ── Scroll-to-top button ──────────────────────────────────────
(function() {
  const btn = document.createElement('button');
  btn.innerHTML = '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"/></svg>';
  btn.style.cssText = 'position:fixed;bottom:24px;left:24px;width:40px;height:40px;border-radius:50%;background:var(--bg2);border:1px solid var(--border);color:var(--text2);display:flex;align-items:center;justify-content:center;z-index:99;opacity:0;transition:opacity .3s,transform .3s;cursor:pointer;box-shadow:var(--shadow-sm)';
  btn.setAttribute('aria-label','Scroll to top');
  document.body.appendChild(btn);
  window.addEventListener('scroll', () => {
    const show = window.scrollY > 400;
    btn.style.opacity = show ? '1' : '0';
    btn.style.pointerEvents = show ? 'auto' : 'none';
  });
  btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
})();

// ── Restore saved theme ───────────────────────────────────────
const savedTheme = localStorage.getItem('fh_theme');
if (savedTheme) document.documentElement.setAttribute('data-theme', savedTheme);

// ── Theme toggle button click ─────────────────────────────────
document.addEventListener('click', e => {
  const target = e.target instanceof Element ? e.target : null;
  if (!target) return;
  if (target.closest('#theme-toggle, #theme-toggle-mobile, #dashboard-theme-toggle, .theme-toggle-btn') && typeof _cycleTheme === 'function') {
    _cycleTheme();
  }
});

// ── Mobile search bar toggle ──────────────────────────────────
document.addEventListener('click', e => {
  const target = e.target instanceof Element ? e.target : null;
  if (!target) return;
  if (target.closest('#search-toggle-mobile')) {
    const searchBar = document.getElementById('sub-header-search');
    if (searchBar) {
      const isActive = searchBar.classList.toggle('active');
      if (isActive) {
        const input = document.getElementById('mobile-search');
        if (input) input.focus();
      }
    }
  }
});


