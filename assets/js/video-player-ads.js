/**
 * FreeHub — Video Player Ad Placement System
 * Extensible manager for Video Player Overlay and future roll ads (pre-roll, mid-roll, post-roll)
 */
class FHVideoAdManager {
  constructor(options) {
    this.playerId = options.playerId || 'fh-player';
    this.ytPlayerId = options.ytPlayerId || 'fh-youtube-player';
    this.videoId = options.videoId;
    this.baseUrl = options.baseUrl || '';
    this.device = options.device || 'desktop';
    
    this.player = document.getElementById(this.playerId);
    this.ytPlayer = document.getElementById(this.ytPlayerId);
    
    this.hasOverlayAd = false;
    this.overlayAdData = null;
    this.triggerPercentages = [];
    this.triggeredPoints = new Set();
    
    this.adTimerInterval = null;
    this.reloadTimer = null;
    this.isAdActive = false;
    this.adFetchPromise = null;
    
    this.init();
  }
  
  init() {
    this.setupAdOverlayUI();
    this.setupTriggers(3); // Start with default 3 triggers immediately
    this.bindEvents();
    
    if (this.ytPlayer) {
      this.initYouTubeAPI();
    }
    
    // Fetch ad metadata in parallel
    this.adFetchPromise = this.fetchAdData();
  }

  initYouTubeAPI() {
    if (!window.YT) {
      const tag = document.createElement('script');
      tag.src = "https://www.youtube.com/iframe_api";
      const firstScriptTag = document.getElementsByTagName('script')[0];
      firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
    }

    const self = this;
    const oldCallback = window.onYouTubeIframeAPIReady;
    window.onYouTubeIframeAPIReady = function() {
      if (oldCallback) oldCallback();
      self.setupYTPlayer();
    };

    if (window.YT && window.YT.Player) {
      this.setupYTPlayer();
    }
  }

  setupYTPlayer() {
    this.ytPlayerObj = new YT.Player(this.ytPlayerId, {
      events: {
        'onStateChange': (event) => {
          this.onYTStateChange(event.data);
        }
      }
    });
  }

  onYTStateChange(state) {
    // YT.PlayerState.PLAYING is 1
    if (state === 1) {
      if (!this.triggeredPoints.has(0)) {
        this.pauseVideo();
        this.showAd(0);
      } else {
        this.startYTPolling();
      }
    } else {
      this.stopYTPolling();
    }
  }

  startYTPolling() {
    if (this.ytPollingInterval) clearInterval(this.ytPollingInterval);
    this.ytPollingInterval = setInterval(() => {
      if (this.ytPlayerObj && typeof this.ytPlayerObj.getCurrentTime === 'function') {
        const currentTime = this.ytPlayerObj.getCurrentTime();
        const duration = this.ytPlayerObj.getDuration() || parseFloat(window.FH_VIDEO_DURATION) || 0;
        if (duration > 0) {
          const percent = currentTime / duration;
          this.checkAndTriggerAd(percent);
        }
      }
    }, 500);
  }

  stopYTPolling() {
    if (this.ytPollingInterval) {
      clearInterval(this.ytPollingInterval);
      this.ytPollingInterval = null;
    }
  }

  async fetchAdData() {
    try {
      const url = `${this.baseUrl}/api/ads.php?action=get_ad&placement=video_player_overlay&device=${this.device}&video_id=${this.videoId}`;
      const response = await fetch(url);
      const data = await response.json();
      if (data.success && data.ad) {
        this.overlayAdData = data.ad;
        this.hasOverlayAd = true;
        this.setupTriggers(); // Re-adjust triggers with real count
        return data.ad;
      }
    } catch (e) {
      console.error("[FHVideoAdManager] Failed to load overlay ad:", e);
    }
    return null;
  }

  setupAdOverlayUI() {
    const playerWrapper = document.getElementById('player-wrapper');
    if (!playerWrapper) return;

    // Remove any existing overlay to prevent duplicates
    const oldOverlay = document.getElementById('fh-ad-overlay');
    if (oldOverlay) oldOverlay.remove();

    // Create ad overlay element
    const overlay = document.createElement('div');
    overlay.id = 'fh-ad-overlay';
    overlay.className = 'ad-overlay-layer';
    overlay.style.display = 'none';

    overlay.innerHTML = `
      <div class="ad-overlay-content" style="position: relative;">
        <div id="ad-overlay-media" class="ad-overlay-media" style="position: relative;"></div>
        <button id="ad-overlay-close-btn" class="ad-overlay-close-btn" disabled></button>
        <div id="ad-overlay-progress-bar" class="ad-overlay-progress-bar"></div>
      </div>
    `;
    
    playerWrapper.appendChild(overlay);
    
    // Add close button listener
    const closeBtn = overlay.querySelector('#ad-overlay-close-btn');
    closeBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      this.closeAd();
    });

    // Make sure click on overlay background doesn't trigger player controls
    overlay.addEventListener('click', (e) => {
      e.stopPropagation();
    });
  }

  setupTriggers(fallbackCount) {
    const triggerCount = this.overlayAdData ? (parseInt(this.overlayAdData.ad_trigger_count) || 3) : (fallbackCount || 3);
    this.triggerPercentages = [];
    if (triggerCount === 1) {
      this.triggerPercentages = [0];
    } else if (triggerCount === 2) {
      this.triggerPercentages = [0, 0.5];
    } else if (triggerCount === 3) {
      this.triggerPercentages = [0, 0.5, 0.9];
    } else {
      this.triggerPercentages.push(0);
      for (let i = 1; i < triggerCount; i++) {
        this.triggerPercentages.push(0.9 * (i / (triggerCount - 1)));
      }
    }
  }

  bindEvents() {
    // 1. Bind to HTML5 player
    if (this.player) {
      // Intercept play to show initial (0%) ad
      this.player.addEventListener('play', (e) => {
        if (!this.triggeredPoints.has(0)) {
          this.player.pause();
          this.showAd(0);
        }
      });
      
      // Listen to timeupdate for mid/end triggers
      this.player.addEventListener('timeupdate', () => {
        const duration = this.player.duration || parseFloat(window.FH_VIDEO_DURATION) || 0;
        if (duration > 0) {
          const percent = this.player.currentTime / duration;
          this.checkAndTriggerAd(percent);
        }
      });
    }
  }

  checkAndTriggerAd(percent) {
    if (this.isAdActive) return; // Prevent overlapping ads
    
    for (let i = 0; i < this.triggerPercentages.length; i++) {
      const triggerPercent = this.triggerPercentages[i];
      // If we crossed a trigger point and haven't triggered it yet
      if (percent >= triggerPercent && !this.triggeredPoints.has(i)) {
        if (i === 0) continue; // 0% is handled by play/onload listeners
        this.showAd(i);
        break;
      }
    }
  }

  async showAd(triggerIndex) {
    if (this.isAdActive) return;
    this.isAdActive = true;
    this.triggeredPoints.add(triggerIndex);
    
    // Pause main video and audio
    this.pauseVideo();
    
    const overlay = document.getElementById('fh-ad-overlay');
    if (!overlay) {
      this.isAdActive = false;
      this.playVideo();
      return;
    }
    
    overlay.style.display = 'flex';
    setTimeout(() => overlay.classList.add('show'), 10);
    
    // Show spinner immediately inside media container
    const mediaContainer = document.getElementById('ad-overlay-media');
    if (mediaContainer) {
      mediaContainer.innerHTML = '<div class="ad-overlay-spinner"></div>';
      mediaContainer.style.display = 'flex';
      mediaContainer.style.justifyContent = 'center';
      mediaContainer.style.alignItems = 'center';
    }

    // Hide close button and progress line during load
    const closeBtn = document.getElementById('ad-overlay-close-btn');
    const progressBar = document.getElementById('ad-overlay-progress-bar');
    if (closeBtn) closeBtn.style.visibility = 'hidden';
    if (progressBar) progressBar.style.visibility = 'hidden';

    // Retrieve ad data
    let currentAd = this.overlayAdData;
    if (triggerIndex === 0) {
      if (this.adFetchPromise) {
        currentAd = await this.adFetchPromise;
      }
    } else {
      // If it's a mid/end play, fetch a fresh ad creative (dynamic ad injection)
      try {
        const url = `${this.baseUrl}/api/ads.php?action=get_ad&placement=video_player_overlay&device=${this.device}&video_id=${this.videoId}`;
        const response = await fetch(url);
        const data = await response.json();
        if (data.success && data.ad) {
          currentAd = data.ad;
        }
      } catch (e) {
        console.error("[FHVideoAdManager] Dynamic ad injection failed:", e);
      }
    }

    // Handle missing ad gracefully (prevent freeze)
    if (!currentAd) {
      this.isAdActive = false;
      overlay.classList.remove('show');
      overlay.style.display = 'none';
      this.playVideo();
      return;
    }
    
    this.renderAdCreative(currentAd);
    
    // Track impression asynchronously
    this.trackImpression(currentAd.id);

    // Show control button and progress line now that ad is loaded
    if (closeBtn) {
      closeBtn.style.visibility = 'visible';
      closeBtn.disabled = true;
    }
    if (progressBar) {
      progressBar.style.visibility = 'visible';
    }

    // Setup countdown timer
    let totalDuration = parseInt(currentAd.ad_display_duration) || 5;
    let duration = totalDuration;
    if (closeBtn) {
      closeBtn.textContent = duration;
    }
    
    if (progressBar) {
      progressBar.style.transition = 'none';
      progressBar.style.width = '0%';
      progressBar.offsetHeight; // trigger reflow
      progressBar.style.transition = `width ${totalDuration}s linear`;
      progressBar.style.width = '100%';
    }
    
    clearInterval(this.adTimerInterval);
    this.adTimerInterval = setInterval(() => {
      duration--;
      if (duration > 0) {
        if (closeBtn) closeBtn.textContent = duration;
      } else {
        clearInterval(this.adTimerInterval);
        if (closeBtn) {
          closeBtn.disabled = false;
          closeBtn.innerHTML = '&#x2715;'; // X close icon
        }
      }
    }, 1000);
    
    // Setup reload timer if configured
    const reloadTiming = parseInt(currentAd.reload_interval) || 0;
    if (reloadTiming > 0) {
      this.setupReload(reloadTiming);
    }
  }

  renderAdCreative(ad) {
    const mediaContainer = document.getElementById('ad-overlay-media');
    if (!mediaContainer) return;
    
    mediaContainer.innerHTML = '';
    
    mediaContainer.style.width = '100%';
    mediaContainer.style.height = '100%';
    mediaContainer.style.maxWidth = 'none';
    
    if (ad.content_type === 'image' && ad.image_url) {
      const link = document.createElement('a');
      link.href = ad.target_url || '#';
      link.target = '_blank';
      link.className = 'ad-click-link';
      link.dataset.adId = ad.id;
      link.dataset.videoId = this.videoId;
      link.style.display = 'block';
      link.style.width = '100%';
      link.style.height = '100%';
      
      const img = document.createElement('img');
      img.src = ad.image_url;
      img.alt = ad.title;
      img.style.width = '100%';
      img.style.height = '100%';
      img.style.objectFit = 'contain';
      
      link.appendChild(img);
      mediaContainer.appendChild(link);
    } else if (ad.content_type === 'html') {
      const adDiv = document.createElement('div');
      adDiv.className = 'ad-html-content';
      adDiv.style.cssText = 'width: 100%; height: 100%; display: flex; justify-content: center; align-items: center; overflow: hidden;';
      mediaContainer.appendChild(adDiv);
      
      const iframe = document.createElement('iframe');
      iframe.style.cssText = 'width:100%;height:100%;border:none;display:block;overflow:hidden;background:transparent;';
      adDiv.appendChild(iframe);
      
      try {
        const doc = iframe.contentDocument || iframe.contentWindow.document;
        doc.open();
        doc.write(`
          <!DOCTYPE html>
          <html>
          <head>
            <meta charset="utf-8">
            <style>
              html, body { margin: 0; padding: 0; background: transparent; overflow: hidden; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; }
            </style>
          </head>
          <body>
            ${ad.content}
          </body>
          </html>
        `);
        doc.close();
      } catch (e) {
        console.error('[FHVideoAdManager] Failed to write to iframe:', e);
        adDiv.innerHTML = ad.content;
      }
      
      adDiv.addEventListener('click', () => {
        this.trackClick(ad.id);
      });
    } else {
      const link = document.createElement('a');
      link.href = ad.target_url || '#';
      link.target = '_blank';
      link.className = 'ad-click-link';
      link.dataset.adId = ad.id;
      link.dataset.videoId = this.videoId;
      link.style.fontWeight = 'bold';
      link.style.color = 'var(--accent)';
      link.style.textDecoration = 'underline';
      link.textContent = ad.content || ad.title;
      mediaContainer.appendChild(link);
    }
  }

  setupReload(seconds) {
    if (this.reloadTimer) clearTimeout(this.reloadTimer);
    
    this.reloadTimer = setTimeout(async () => {
      if (!this.isAdActive) return;
      
      try {
        const url = `${this.baseUrl}/api/ads.php?action=get_ad&placement=video_player_overlay&device=${this.device}&video_id=${this.videoId}`;
        const response = await fetch(url);
        const data = await response.json();
        if (data.success && data.ad) {
          this.renderAdCreative(data.ad);
          
          // Re-setup reload if needed
          const newReload = parseInt(data.ad.reload_interval) || 0;
          if (newReload > 0) {
            this.setupReload(newReload);
          }
        }
      } catch(e) {
        console.error("[FHVideoAdManager] Reload ad failed:", e);
      }
    }, seconds * 1000);
  }

  async trackImpression(adId) {
    try {
      const url = `${this.baseUrl}/api/ads.php?action=track_impression&id=${adId}&video_id=${this.videoId}&placement=video_player_overlay`;
      await fetch(url, { method: 'POST' });
    } catch (e) {}
  }

  async trackClick(adId) {
    try {
      const url = `${this.baseUrl}/api/ads.php?action=track_click&id=${adId}&video_id=${this.videoId}&placement=video_player_overlay`;
      await fetch(url, { method: 'POST' });
    } catch (e) {}
  }

  closeAd() {
    clearInterval(this.adTimerInterval);
    if (this.reloadTimer) clearTimeout(this.reloadTimer);
    
    const overlay = document.getElementById('fh-ad-overlay');
    if (overlay) {
      overlay.classList.remove('show');
      setTimeout(() => {
        overlay.style.display = 'none';
        this.isAdActive = false;
        this.playVideo(); // Auto play video after ad closed
      }, 300);
    } else {
      this.isAdActive = false;
      this.playVideo();
    }
  }

  pauseVideo() {
    if (this.player) {
      this.player.pause();
    }
    if (this.ytPlayerObj && typeof this.ytPlayerObj.pauseVideo === 'function') {
      this.ytPlayerObj.pauseVideo();
    } else if (this.ytPlayer) {
      this.ytPlayer.contentWindow.postMessage('{"event":"command","func":"pauseVideo","args":""}', '*');
    }
  }

  playVideo() {
    if (this.player) {
      this.player.play().catch(e => console.log("HTML5 Play interrupted/blocked:", e));
    }
    if (this.ytPlayerObj && typeof this.ytPlayerObj.playVideo === 'function') {
      this.ytPlayerObj.playVideo();
    } else if (this.ytPlayer) {
      this.ytPlayer.contentWindow.postMessage('{"event":"command","func":"playVideo","args":""}', '*');
    }
  }
}
