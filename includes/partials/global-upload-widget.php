<?php
// ============================================================
// FreeHub.Live — Global Upload Progress Widget
// ============================================================
// A floating mini-widget that monitors active uploads from any page.
// Reads IndexedDB state to show progress without re-uploading.
// ============================================================
if (!is_logged_in()) return;
$role = auth_user()['role'] ?? 'viewer';
if (!in_array($role, ['admin', 'creator'])) return;

// Don't show on the upload page itself (it has its own UI)
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
if ($currentPage === 'upload.php') return;
?>

<!-- Global Upload Monitor Widget -->
<div id="fh-upload-widget" style="display:none;">
  <div id="fh-upload-widget-pill" onclick="document.getElementById('fh-upload-widget').classList.toggle('expanded')">
    <div class="fh-uw-icon">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
      </svg>
    </div>
    <span id="fh-uw-label">0 uploads</span>
    <div class="fh-uw-progress-ring" id="fh-uw-ring">
      <svg viewBox="0 0 36 36">
        <circle cx="18" cy="18" r="15" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="3"/>
        <circle id="fh-uw-ring-fill" cx="18" cy="18" r="15" fill="none" stroke="var(--accent, #6366f1)" stroke-width="3" stroke-dasharray="94.25" stroke-dashoffset="94.25" stroke-linecap="round" transform="rotate(-90 18 18)"/>
      </svg>
    </div>
  </div>
  <div id="fh-upload-widget-details">
    <div class="fh-uw-header">
      <span style="font-weight:700; font-size:0.85rem;">Upload Progress</span>
      <a href="<?= BASE_URL ?>/creator/upload.php" style="font-size:0.75rem; color:var(--accent); font-weight:600;">Open Studio →</a>
    </div>
    <div id="fh-uw-list"></div>
  </div>
</div>

<style>
#fh-upload-widget {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 10001;
  font-family: var(--font, 'Inter', sans-serif);
}
#fh-upload-widget-pill {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(15, 23, 42, 0.95);
  border: 1px solid var(--border, rgba(255,255,255,0.1));
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border-radius: 99px;
  padding: 8px 16px 8px 12px;
  cursor: pointer;
  box-shadow: 0 8px 32px rgba(0,0,0,0.4);
  transition: all 0.2s ease;
  color: #fff;
  font-size: 0.82rem;
  font-weight: 600;
}
#fh-upload-widget-pill:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 40px rgba(0,0,0,0.5);
  border-color: var(--accent, #6366f1);
}
.fh-uw-icon {
  display: flex;
  align-items: center;
  color: var(--accent, #6366f1);
  animation: fh-uw-bounce 2s ease infinite;
}
@keyframes fh-uw-bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-3px); }
}
.fh-uw-progress-ring {
  width: 28px;
  height: 28px;
  flex-shrink: 0;
}
.fh-uw-progress-ring svg {
  width: 100%;
  height: 100%;
}
#fh-upload-widget-details {
  display: none;
  position: absolute;
  bottom: calc(100% + 12px);
  right: 0;
  width: 320px;
  background: rgba(15, 23, 42, 0.97);
  border: 1px solid var(--border, rgba(255,255,255,0.1));
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.5);
  overflow: hidden;
  animation: fh-uw-slideUp 0.25s ease;
}
@keyframes fh-uw-slideUp {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
#fh-upload-widget.expanded #fh-upload-widget-details {
  display: block;
}
.fh-uw-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 16px;
  border-bottom: 1px solid rgba(255,255,255,0.06);
  color: #fff;
}
#fh-uw-list {
  max-height: 280px;
  overflow-y: auto;
  padding: 8px;
}
.fh-uw-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 8px;
  border-radius: 10px;
  transition: background 0.15s;
}
.fh-uw-item:hover {
  background: rgba(255,255,255,0.03);
}
.fh-uw-item-info {
  flex: 1;
  min-width: 0;
}
.fh-uw-item-title {
  font-size: 0.78rem;
  font-weight: 600;
  color: #e2e8f0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.fh-uw-item-status {
  font-size: 0.7rem;
  color: #94a3b8;
  margin-top: 2px;
}
.fh-uw-item-bar {
  width: 100%;
  height: 3px;
  background: rgba(255,255,255,0.06);
  border-radius: 2px;
  margin-top: 6px;
  overflow: hidden;
}
.fh-uw-item-bar-fill {
  height: 100%;
  background: var(--accent, #6366f1);
  border-radius: 2px;
  transition: width 0.3s ease;
}
.fh-uw-item-bar-fill.done { background: #10b981; }
.fh-uw-item-bar-fill.failed { background: #ef4444; }
.fh-uw-item-pct {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--accent, #6366f1);
  flex-shrink: 0;
  width: 36px;
  text-align: right;
}
.fh-uw-empty {
  text-align: center;
  padding: 24px 16px;
  color: #64748b;
  font-size: 0.8rem;
}
</style>

<script>
(function() {
  const widget = document.getElementById('fh-upload-widget');
  const label = document.getElementById('fh-uw-label');
  const list = document.getElementById('fh-uw-list');
  const ringFill = document.getElementById('fh-uw-ring-fill');
  const DB_NAME = 'FreeHubUploadManager';
  const STORE_NAME = 'videoQueue';
  const CIRCUMFERENCE = 94.25; // 2 * PI * 15

  function openDB() {
    return new Promise((resolve, reject) => {
      const req = indexedDB.open(DB_NAME, 1);
      req.onerror = () => reject(req.error);
      req.onsuccess = () => resolve(req.result);
      req.onupgradeneeded = (e) => {
        const db = e.target.result;
        if (!db.objectStoreNames.contains(STORE_NAME)) {
          db.createObjectStore(STORE_NAME, { keyPath: 'id' });
        }
      };
    });
  }

  async function getAll() {
    try {
      const db = await openDB();
      return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readonly');
        const store = tx.objectStore(STORE_NAME);
        const req = store.getAll();
        req.onsuccess = () => resolve(req.result || []);
        req.onerror = () => resolve([]);
      });
    } catch (e) {
      return [];
    }
  }

  function escapeHtml(s) {
    return s ? s.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;") : '';
  }

  async function pollUploads() {
    const items = await getAll();
    const active = items.filter(i => i.status && i.status !== 'published' && i.status !== 'completed');

    if (active.length === 0) {
      widget.style.display = 'none';
      return;
    }

    widget.style.display = 'block';
    label.textContent = active.length + (active.length === 1 ? ' upload' : ' uploads');

    // Calculate overall progress
    let totalProgress = 0;
    active.forEach(i => totalProgress += (i.progress || 0));
    const avgProgress = Math.floor(totalProgress / active.length);
    const offset = CIRCUMFERENCE - (CIRCUMFERENCE * avgProgress / 100);
    ringFill.style.strokeDashoffset = offset;

    // Render list
    let html = '';
    active.forEach(item => {
      const pct = item.progress || 0;
      const statusText = item.status === 'uploading' ? `Uploading • ${pct}%` :
                         item.status === 'processing' ? 'Publishing...' :
                         item.status === 'queued' ? 'Queued' :
                         item.status === 'paused' ? 'Paused' :
                         item.status === 'retrying' ? 'Retrying...' :
                         item.status === 'failed' ? 'Failed' : item.status;
      const barClass = item.status === 'failed' ? 'failed' : (item.status === 'published' ? 'done' : '');
      const isReel = item.isReel ? '🎬 ' : '🎥 ';

      html += `
        <div class="fh-uw-item">
          <div class="fh-uw-item-info">
            <div class="fh-uw-item-title">${isReel}${escapeHtml(item.title || 'Untitled')}</div>
            <div class="fh-uw-item-status">${statusText}</div>
            <div class="fh-uw-item-bar"><div class="fh-uw-item-bar-fill ${barClass}" style="width:${pct}%"></div></div>
          </div>
          <div class="fh-uw-item-pct">${pct}%</div>
        </div>
      `;
    });

    if (!html) {
      html = '<div class="fh-uw-empty">No active uploads</div>';
    }
    list.innerHTML = html;
  }

  // Poll every 2 seconds
  pollUploads();
  setInterval(pollUploads, 2000);

  // Close on outside click
  document.addEventListener('click', (e) => {
    if (!widget.contains(e.target)) {
      widget.classList.remove('expanded');
    }
  });
})();
</script>
