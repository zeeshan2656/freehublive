// ============================================================
// FreeHub.Live — Dynamic Ad Manager with Auto-Refresh
// ============================================================
(function() {
  // Dynamic BASE_URL from the page's own origin + path
  const BASE_URL = document.querySelector('meta[name="base-url"]')?.content
    || document.querySelector('meta[base-url]')?.content
    || '';

  function detectDeviceType() {
    if (window.innerWidth <= 900) {
      return "mobile";
    }
    return "desktop";
  }

  const device = detectDeviceType();

  function setupAdReload(container, reloadSeconds) {
    if (container.dataset.reloadIntervalId) {
      clearInterval(parseInt(container.dataset.reloadIntervalId));
      delete container.dataset.reloadIntervalId;
    }
    
    if (!reloadSeconds || reloadSeconds <= 0) {
      return;
    }
    
    const intervalId = setInterval(() => {
      if (!document.body.contains(container)) {
        clearInterval(intervalId);
        return;
      }
      loadAd(container);
    }, reloadSeconds * 1000);
    
    container.dataset.reloadIntervalId = intervalId;
  }

  async function loadAd(container) {
    const placement = container.dataset.placement;
    const position = container.dataset.position || 1;
    const videoId = container.dataset.videoId || window.FH_WATCH?.videoId || '';
    
    try {
      const url = `${BASE_URL}/api/ads.php?action=get_ad&placement=${placement}&position=${position}&device=${device}&video_id=${videoId}`;
      const response = await fetch(url);
      const data = await response.json();
      
      let reloadSeconds = parseInt(container.dataset.reloadInterval) || 0;
      
      if (data.success && data.ad) {
        const ad = data.ad;
        if (ad.reload_interval !== undefined) {
          reloadSeconds = parseInt(ad.reload_interval) || 0;
          container.dataset.reloadInterval = reloadSeconds;
        }
        
        container.innerHTML = '';
        container.classList.add('loaded');
        container.style.display = 'block';

        // Update device targeting classes dynamically
        container.classList.remove('ad-mobile-only', 'ad-desktop-only');
        const placementDevice = container.dataset.deviceTarget || 'all';
        if (ad.device_target === 'mobile' || placementDevice === 'mobile') {
          container.classList.add('ad-mobile-only');
        } else if (ad.device_target === 'desktop' || placementDevice === 'desktop') {
          container.classList.add('ad-desktop-only');
        }
        
        if (placement === 'home_mobile_top') {
          // Handled by CSS class ad-full-width-mobile
        } else {
          // Reset styles
          container.style.width = '';
          container.style.maxWidth = '';
          container.style.height = '';
          container.style.padding = '';
          container.style.display = '';
          container.style.flexDirection = '';
          container.style.justifyContent = '';

          if (ad.ad_width) {
            container.style.width = '100%';
            container.style.maxWidth = ad.ad_width + 'px';
          }
          if (ad.ad_height) {
            container.style.height = ad.ad_height + 'px';
            container.style.padding = '0 16px';
            container.style.display = 'flex';
            container.style.flexDirection = 'column';
            container.style.justifyContent = 'center';
          }
        }
        
        let sizeStyle = '';
        if (placement === 'home_mobile_top') {
          sizeStyle += 'width: 100% !important; ';
        } else if (ad.ad_width) {
          sizeStyle += `width: ${ad.ad_width}px; `;
        }
        if (ad.ad_height) sizeStyle += `height: ${ad.ad_height}px; `;
        
        const wrapper = document.createElement('div');
        wrapper.style.cssText = `margin: 0 auto; display: block; max-width: 100%; ${sizeStyle}`;
        wrapper.className = 'ad-creative-wrapper';
        
        if (ad.content_type === 'image' && ad.image_url) {
          const imgLink = document.createElement('a');
          imgLink.href = ad.target_url || '#';
          imgLink.target = '_blank';
          imgLink.rel = 'noopener';
          imgLink.style.cssText = 'display: block; width: 100%; height: 100%;';
          imgLink.className = 'ad-click-link';
          imgLink.dataset.adId = ad.id;
          imgLink.dataset.videoId = videoId;
          
          const img = document.createElement('img');
          img.src = ad.image_url;
          img.alt = ad.title;
          img.style.cssText = `width: 100%; ${ad.ad_height ? 'height: 100%; object-fit: contain;' : 'height: auto;'} display: block; border-radius: 4px;`;
          
          imgLink.appendChild(img);
          wrapper.appendChild(imgLink);
        } else if (ad.content_type === 'html') {
          const adDiv = document.createElement('div');
          adDiv.className = 'ad-html-content';
          adDiv.style.cssText = `width: 100%; ${ad.ad_height ? 'height: 100%;' : ''} display: block; margin: 0 auto; overflow: hidden;`;
          wrapper.appendChild(adDiv);
          
          try {
            const range = document.createRange();
            range.selectNode(adDiv);
            const fragment = range.createContextualFragment(ad.content);
            adDiv.appendChild(fragment);
          } catch (e) {
            adDiv.innerHTML = ad.content;
          }
        } else {
          const txtLink = document.createElement('a');
          txtLink.href = ad.target_url || '#';
          txtLink.target = '_blank';
          txtLink.rel = 'noopener';
          txtLink.className = 'ad-click-link';
          txtLink.dataset.adId = ad.id;
          txtLink.dataset.videoId = videoId;
          txtLink.style.cssText = 'font-weight: bold; color: var(--accent); text-decoration: underline;';
          txtLink.textContent = ad.content || ad.title;
          wrapper.appendChild(txtLink);
        }
        
        container.appendChild(wrapper);
        setupAdReload(container, reloadSeconds);
      } else {
        container.style.display = 'none';
        setupAdReload(container, reloadSeconds);
      }
    } catch (e) {
      console.error('Failed to load ad:', e);
      container.style.display = 'none';
      const reloadSeconds = parseInt(container.dataset.reloadInterval) || 0;
      setupAdReload(container, reloadSeconds);
    }
  }

  async function trackClick(adId, videoId) {
    const vid = videoId || '';
    try {
      await fetch(`${BASE_URL}/api/ads.php?action=track_click&id=${adId}&video_id=${vid}`, { method: 'POST' });
    } catch(e) {}
  }

  // Global ad click tracking listener
  document.addEventListener('click', function(ev) {
    const link = ev.target.closest('.ad-click-link');
    if (link && link.dataset.adId) {
      const adId = link.dataset.adId;
      const videoId = link.dataset.videoId || window.FH_WATCH?.videoId || '';
      trackClick(adId, videoId);
    }
  });

  function initAds() {
    const containers = document.querySelectorAll('.ad-sponsored-container');
    containers.forEach(container => {
      loadAd(container);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAds);
  } else {
    initAds();
  }

  window.initAds = initAds;
})();
