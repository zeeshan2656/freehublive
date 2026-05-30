// ============================================================
// FreeHub.Live — Premium SPA-Like Public Router
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
  const origin = window.location.origin;
  const pageCache = {};
  const fetchingUrls = new Set();
  
  // ── 1. Setup Linear Top Progress Bar ──
  let progress = document.getElementById('public-progress');
  if (!progress) {
    progress = document.createElement('div');
    progress.id = 'public-progress';
    progress.style.cssText = 'position:fixed;top:0;left:0;height:3px;background:var(--accent);width:0%;opacity:0;transition:width 0.2s ease, opacity 0.2s ease;z-index:99999;pointer-events:none';
    document.body.appendChild(progress);
  }

  function startProgress() {
    progress.style.width = '0%';
    progress.style.opacity = '1';
    setTimeout(() => {
      progress.style.width = '40%';
    }, 50);
  }

  function completeProgress() {
    progress.style.width = '100%';
    setTimeout(() => {
      progress.style.opacity = '0';
      setTimeout(() => {
        progress.style.width = '0%';
      }, 200);
    }, 150);
  }

  // ── 2. Determine Content Container ──
  function getContainer(doc = document) {
    // Public pages use main.main-content, Reels page uses .reels-container
    return doc.querySelector('main.main-content') || doc.querySelector('.reels-container');
  }

  // ── 3. Stop Active Media & Cleanup Previous Page ──
  function cleanupPreviousPage() {
    // Pause any playing standard video player
    document.querySelectorAll('video').forEach(video => {
      try {
        video.pause();
        video.src = '';
        video.load();
      } catch (e) {}
    });

    // Pause any YouTube player if loaded in iframe
    document.querySelectorAll('iframe#fh-youtube-player').forEach(iframe => {
      try {
        iframe.contentWindow.postMessage('{"event":"command","func":"pauseVideo","args":""}', '*');
      } catch (e) {}
    });

    // Remove any suggestions overlays from search autocomplete
    document.querySelectorAll('#main-search ~ div').forEach(div => {
      div.style.display = 'none';
    });

    // Close any open modals
    document.querySelectorAll('.modal-backdrop.open').forEach(modal => {
      modal.classList.remove('open');
    });
  }

  // ── 4. Parse HTML Content ──
  function parsePageHTML(html, url) {
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');
    const container = getContainer(doc);
    
    if (!container) return null;

    // Collect all valid inline/page scripts
    const scripts = [];
    doc.querySelectorAll('script').forEach(script => {
      const src = script.getAttribute('src');
      if (src) {
        // Skip common global libraries to avoid re-evaluating
        if (src.includes('app.js') || src.includes('ads.js') || src.includes('public-router.js') || src.includes('dashboard-router.js')) return;
        scripts.push({ type: 'external', value: src });
      } else {
        scripts.push({ type: 'inline', value: script.textContent });
      }
    });

    return {
      title: doc.title || 'FreeHub',
      htmlContent: container.innerHTML,
      containerTag: container.tagName.toLowerCase(),
      containerClass: container.className,
      bodyClass: doc.body.className,
      scripts: scripts
    };
  }

  // ── 5. Fetch Page (with Caching) ──
  async function fetchPage(url) {
    if (pageCache[url]) {
      // Soft background revalidation
      fetch(url)
        .then(res => {
          if (res.ok) return res.text();
        })
        .then(html => {
          if (html) {
            const parsed = parsePageHTML(html, url);
            if (parsed) pageCache[url] = parsed;
          }
        })
        .catch(() => {});
      return pageCache[url];
    }

    if (fetchingUrls.has(url)) {
      // Wait for existing fetch
      return new Promise(resolve => {
        const interval = setInterval(() => {
          if (pageCache[url]) {
            clearInterval(interval);
            resolve(pageCache[url]);
          }
        }, 50);
      });
    }

    fetchingUrls.add(url);
    try {
      const res = await fetch(url);
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const html = await res.text();
      const parsed = parsePageHTML(html, url);
      if (parsed) {
        pageCache[url] = parsed;
      }
      fetchingUrls.delete(url);
      return parsed;
    } catch (e) {
      fetchingUrls.delete(url);
      console.warn('Fetch failed for public page router: ', e);
      return null;
    }
  }

  // ── 6. Transition and Load Content ──
  async function loadPage(url, pushState = true, scrollPos = 0) {
    startProgress();

    const currentContainer = getContainer();
    if (!currentContainer) {
      window.location.href = url;
      return;
    }

    // Apply soft fade-out transition
    currentContainer.style.transition = 'opacity 0.15s ease, transform 0.15s ease';
    currentContainer.style.opacity = '0';
    currentContainer.style.transform = 'translateY(8px)';

    const pageData = await fetchPage(url);
    if (!pageData) {
      window.location.href = url;
      return;
    }

    cleanupPreviousPage();

    setTimeout(() => {
      // 1. Sync document metadata
      document.title = pageData.title;
      document.body.className = pageData.bodyClass;

      // 2. Resolve layouts (handling reels container vs normal main content tag)
      let targetContainer = getContainer();
      if (targetContainer.tagName.toLowerCase() !== pageData.containerTag || targetContainer.className !== pageData.containerClass) {
        // If container wrappers differ (e.g. index.php -> reels.php), re-render full layout
        const parent = targetContainer.parentNode;
        const newContainer = document.createElement(pageData.containerTag);
        newContainer.className = pageData.containerClass;
        newContainer.style.opacity = '0';
        newContainer.style.transform = 'translateY(8px)';
        parent.replaceChild(newContainer, targetContainer);
        targetContainer = newContainer;
      }

      // 3. Swap HTML content
      targetContainer.innerHTML = pageData.htmlContent;

      // 4. Update sidebar links active state
      const currentURL = new URL(url, origin);
      document.querySelectorAll('.sidebar .nav-item, .sidebar .category-select-item').forEach(link => {
        const href = link.getAttribute('href');
        if (!href) return;
        const linkURL = new URL(href, origin);
        if (linkURL.pathname === currentURL.pathname && linkURL.search === currentURL.search) {
          link.classList.add('active');
        } else {
          link.classList.remove('active');
        }
      });

      // 5. Restore scroll coordinate
      if (scrollPos > 0) {
        window.scrollTo(0, scrollPos);
      } else {
        window.scrollTo(0, 0);
      }

      // 6. Push history entry
      if (pushState) {
        history.pushState({ url: url, scroll: scrollPos }, '', url);
      }

      // 7. Inject and execute page scripts
      pageData.scripts.forEach(script => {
        const scriptEl = document.createElement('script');
        if (script.type === 'external') {
          scriptEl.src = script.value;
        } else {
          scriptEl.textContent = script.value;
        }
        document.body.appendChild(scriptEl);
      });

      // 8. Re-run core global integrations (lazy image, autocomplete triggers, active durations)
      if (typeof fhInitPendingDurations === 'function') {
        fhInitPendingDurations();
      }
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
      
      // Bind infinite scroll / load more on home page
      if (typeof bindLoadMore === 'function') {
        bindLoadMore();
      }

      // 9. Fade content back in smoothly
      targetContainer.style.transition = 'opacity 0.15s ease, transform 0.15s ease';
      targetContainer.style.opacity = '1';
      targetContainer.style.transform = 'translateY(0px)';

      completeProgress();
    }, 150);
  }

  // ── 7. Global Link Interception (Event Delegation) ──
  document.addEventListener('click', function(e) {
    const link = e.target.closest('a');
    if (!link) return;

    const href = link.getAttribute('href');
    if (!href) return;

    // Skip helper, external, action, target="_blank", or logout links
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

    const linkUrl = new URL(href, window.location.href);
    if (linkUrl.origin !== origin) return;

    // Skip dashboard links (these have their own router or require full context changes)
    const path = linkUrl.pathname;
    const isDashboardRoute = (
      path.includes('/admin/') ||
      path.includes('/creator/') ||
      path.includes('/affiliate/') ||
      path.endsWith('dashboard.php') ||
      path.endsWith('withdrawal.php') ||
      path.endsWith('settings.php') ||
      path.endsWith('profile.php')
    );

    if (isDashboardRoute) return;

    // Intercept transition
    e.preventDefault();
    
    // Save current scroll position in current state before navigating away
    const currentUrl = window.location.href;
    history.replaceState({ url: currentUrl, scroll: window.scrollY }, '');

    loadPage(href, true);
  });

  // ── 8. Hover Prefetching (Pointerenter Integration) ──
  document.addEventListener('pointerenter', function(e) {
    const link = e.target.closest('a');
    if (!link) return;

    const href = link.getAttribute('href');
    if (!href) return;

    if (
      href.startsWith('#') ||
      href.startsWith('javascript:') ||
      link.getAttribute('target') === '_blank' ||
      href.includes('logout.php')
    ) return;

    const linkUrl = new URL(href, window.location.href);
    if (linkUrl.origin !== origin) return;

    const path = linkUrl.pathname;
    const isDashboardRoute = (
      path.includes('/admin/') ||
      path.includes('/creator/') ||
      path.includes('/affiliate/') ||
      path.endsWith('dashboard.php')
    );

    if (isDashboardRoute) return;

    // Start prefetching page HTML 100-300ms before click
    fetchPage(href);
  }, { passive: true });

  // ── 9. Popstate (Back/Forward Navigation) ──
  window.addEventListener('popstate', function(e) {
    const state = e.state;
    if (state && state.url) {
      loadPage(state.url, false, state.scroll || 0);
    } else {
      loadPage(window.location.href, false, 0);
    }
  });

  // Initialize initial history state with current scroll
  history.replaceState({ url: window.location.href, scroll: window.scrollY }, '');
});
