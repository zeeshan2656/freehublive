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

  async function loadAd(container) {
    const placement = container.dataset.placement;
    const position = container.dataset.position || 1;
    
    try {
      const url = `${BASE_URL}/api/ads.php?action=get_ad&placement=${placement}&position=${position}&device=${device}`;
      const response = await fetch(url);
      const data = await response.json();
      
      if (data.success && data.ad) {
        const ad = data.ad;
        container.innerHTML = '';
        container.classList.add('loaded');
        container.style.display = 'block';
        
        if (ad.ad_width) {
          container.style.maxWidth = (parseInt(ad.ad_width) + 32) + 'px';
          container.style.width = '100%';
          container.style.marginLeft = 'auto';
          container.style.marginRight = 'auto';
        }
        
        let sizeStyle = '';
        if (ad.ad_width) sizeStyle += `width: ${ad.ad_width}px; `;
        if (ad.ad_height) sizeStyle += `height: ${ad.ad_height}px; `;
        
        const wrapper = document.createElement('div');
        wrapper.style.cssText = `margin: 0 auto; display: block; max-width: 100%; ${sizeStyle}`;
        wrapper.className = 'ad-creative-wrapper';
        
        if (ad.content_type === 'image' && ad.image_url) {
          const imgLink = document.createElement('a');
          imgLink.href = ad.target_url || '#';
          imgLink.target = '_blank';
          imgLink.rel = 'noopener';
          imgLink.addEventListener('click', () => trackClick(ad.id));
          
          const img = document.createElement('img');
          img.src = ad.image_url;
          img.alt = ad.title;
          img.style.cssText = 'max-width: 100%; height: auto; display: block; border-radius: 4px;';
          
          imgLink.appendChild(img);
          wrapper.appendChild(imgLink);
        } else if (ad.content_type === 'html') {
          const adDiv = document.createElement('div');
          adDiv.className = 'ad-html-content';
          adDiv.style.cssText = 'width:100%; display:block; margin:0 auto; overflow:hidden;';
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
          txtLink.style.cssText = 'font-weight: bold; color: var(--accent); text-decoration: underline;';
          txtLink.textContent = ad.content || ad.title;
          txtLink.addEventListener('click', () => trackClick(ad.id));
          wrapper.appendChild(txtLink);
        }
        
        container.appendChild(wrapper);
      } else {
        container.style.display = 'none';
      }
    } catch (e) {
      console.error('Failed to load ad:', e);
      container.style.display = 'none';
    }
  }

  async function trackClick(adId) {
    try {
      await fetch(`${BASE_URL}/api/ads.php?action=track_click&id=${adId}`, { method: 'POST' });
    } catch(e) {}
  }

  function initAds() {
    const containers = document.querySelectorAll('.ad-sponsored-container');
    containers.forEach(container => {
      loadAd(container);
      
      // Auto-refresh visible ads every 60s
      if (!container.dataset.refreshInterval) {
        const intervalId = setInterval(() => {
          const rect = container.getBoundingClientRect();
          const isVisible = (
            rect.top >= -500 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) + 500 &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
          );
          if (isVisible) {
            loadAd(container);
          }
        }, 60000);
        container.dataset.refreshInterval = intervalId;
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAds);
  } else {
    initAds();
  }

  window.initAds = initAds;
})();
