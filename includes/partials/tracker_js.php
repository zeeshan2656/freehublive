<?php
// FreeHub.Live — Client-side Tracking Script Injection
$is_admin_check = str_contains($_SERVER['PHP_SELF'] ?? '', '/admin/');
if ($is_admin_check) return;
?>
<script>
const FHTracker = {
    pageviewId: null,
    heartbeatInterval: null,
    currentVideoId: null,
    currentReelId: null,
    
    init(params = {}) {
        this.stopHeartbeat();
        
        const payload = {
            url: window.location.href,
            referer: document.referrer,
            is_reel: params.is_reel ? 1 : 0,
            is_video: params.is_video ? 1 : 0,
            content_id: params.content_id ? parseInt(params.content_id, 10) : null
        };
        
        this.currentVideoId = payload.is_video ? payload.content_id : null;
        this.currentReelId = payload.is_reel ? payload.content_id : null;
        
        fetch('<?= BASE_URL ?>/api/tracker.php?action=init', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data && data.success) {
                if (data.pageview_id) {
                    this.pageviewId = data.pageview_id;
                    this.startHeartbeat();
                }
                if (data.views !== null && data.views !== undefined) {
                    if (payload.is_reel) {
                        const activeSlide = document.querySelector(`.reel-slide[data-id="${payload.content_id}"]`);
                        if (activeSlide) {
                            const viewsSpan = activeSlide.querySelector('.views-display-btn span');
                            if (viewsSpan) {
                                viewsSpan.textContent = Number(data.views).toLocaleString();
                            }
                        }
                    }
                }
            }
        })
        .catch(err => console.warn('Tracker init error:', err));
    },
    
    startHeartbeat() {
        this.stopHeartbeat();
        this.heartbeatInterval = setInterval(() => {
            if (!this.pageviewId) return;
            fetch('<?= BASE_URL ?>/api/tracker.php?action=heartbeat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    pageview_id: this.pageviewId,
                    inc: 10
                })
            })
            .catch(err => console.warn('Tracker heartbeat error:', err));
        }, 10000);
    },
    
    stopHeartbeat() {
        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
            this.heartbeatInterval = null;
        }
        this.pageviewId = null;
    },
    
    trackReel(reelId) {
        const parsedId = parseInt(reelId, 10);
        if (this.currentReelId === parsedId) return;
        this.init({
            is_reel: 1,
            content_id: parsedId
        });
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const pathname = window.location.pathname;
    const urlParams = new URLSearchParams(window.location.search);
    
    let isVideo = 0;
    let isReel = 0;
    let contentId = null;
    
    if (pathname.includes('watch.php')) {
        isVideo = 1;
        contentId = urlParams.get('v');
    } else if (pathname.includes('reels.php')) {
        isReel = 1;
        contentId = urlParams.get('id');
    }
    
    if (!pathname.includes('reels.php')) {
        FHTracker.init({
            is_video: isVideo,
            content_id: contentId
        });
    }
});
</script>
