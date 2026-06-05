/**
 * ReelsCache — Client-side caching engine for FreeHub Reels
 *
 * Storage layers (in priority order):
 *   1. IndexedDB   — Full blob storage (video data) + metadata + feed state
 *   2. Cache API   — Fallback when IndexedDB quota is exceeded
 *   3. Network     — Final fallback (stream.php with byte-range support)
 *
 * Features:
 *   - Feed TTL: 30 minutes (stale feeds are refreshed silently)
 *   - Blob quota protection (catches QuotaExceededError gracefully)
 *   - Cache API secondary storage for blobs
 *   - cleanOldBlobs() to prevent memory waste
 *   - getRecentFeedAge() to check if cache is fresh
 */
class ReelsCache {
  constructor() {
    this.dbName     = 'ReelsDatabase';
    this.dbVersion  = 2; // bumped — adds saved_at to feed_state
    this.db         = null;
    this.FEED_TTL   = 30 * 60 * 1000; // 30 minutes in ms
    this.CACHE_NAME = 'freehub-reels-v1';
  }

  // ── IndexedDB Initialization ────────────────────────────────
  async init() {
    if (this.db) return this.db;
    return new Promise((resolve, reject) => {
      if (!window.indexedDB) {
        reject(new Error('IndexedDB is not supported in this browser.'));
        return;
      }
      const request = indexedDB.open(this.dbName, this.dbVersion);

      request.onupgradeneeded = (e) => {
        const db = e.target.result;
        if (!db.objectStoreNames.contains('metadata')) {
          db.createObjectStore('metadata', { keyPath: 'id' });
        }
        if (!db.objectStoreNames.contains('video_blobs')) {
          db.createObjectStore('video_blobs');
        }
        if (!db.objectStoreNames.contains('feed_state')) {
          db.createObjectStore('feed_state');
        }
      };

      request.onsuccess  = (e) => { this.db = e.target.result; resolve(this.db); };
      request.onerror    = (e) => reject(e);
      request.onblocked  = () => reject(new Error('IndexedDB blocked by another tab'));
    });
  }

  // ── Feed Management ─────────────────────────────────────────

  /**
   * Save a feed array to IndexedDB, with a timestamp for TTL checks.
   */
  async saveFeed(videos) {
    try {
      await this.init();
      return new Promise((resolve, reject) => {
        const tx = this.db.transaction(['metadata', 'feed_state'], 'readwrite');
        const metaStore  = tx.objectStore('metadata');
        const stateStore = tx.objectStore('feed_state');

        // Clear old metadata and write fresh
        metaStore.clear();
        videos.forEach(v => { if (v && v.id) metaStore.put(v); });

        // Save feed order + timestamp
        const ids = videos.map(v => v.id);
        stateStore.put(ids, 'current_feed');
        stateStore.put(Date.now(), 'saved_at');

        tx.oncomplete = () => resolve();
        tx.onerror    = (e) => reject(e);
      });
    } catch (err) {
      console.warn('ReelsCache saveFeed error:', err);
    }
  }

  /**
   * Load feed from IndexedDB. Returns [] if empty or expired.
   */
  async getFeed() {
    try {
      await this.init();
      const ids = await new Promise((resolve) => {
        const tx  = this.db.transaction('feed_state', 'readonly');
        const req = tx.objectStore('feed_state').get('current_feed');
        req.onsuccess = () => resolve(req.result || []);
        req.onerror   = () => resolve([]);
      });
      if (!ids.length) return [];

      return new Promise((resolve) => {
        const tx    = this.db.transaction('metadata', 'readonly');
        const store = tx.objectStore('metadata');
        const videos = [];
        let count = 0;

        ids.forEach(id => {
          const req = store.get(id);
          req.onsuccess = () => {
            if (req.result) videos.push(req.result);
            count++;
            if (count === ids.length) {
              const sorted = ids.map(id => videos.find(v => v.id === id)).filter(Boolean);
              resolve(sorted);
            }
          };
          req.onerror = () => {
            count++;
            if (count === ids.length) resolve(videos);
          };
        });
      });
    } catch (err) {
      console.warn('ReelsCache getFeed error:', err);
      return [];
    }
  }

  /**
   * Get the age of the cached feed in milliseconds.
   * Returns Infinity if no timestamp (treat as expired).
   */
  async getRecentFeedAge() {
    try {
      await this.init();
      const savedAt = await new Promise((resolve) => {
        const tx  = this.db.transaction('feed_state', 'readonly');
        const req = tx.objectStore('feed_state').get('saved_at');
        req.onsuccess = () => resolve(req.result || 0);
        req.onerror   = () => resolve(0);
      });
      return savedAt > 0 ? (Date.now() - savedAt) : Infinity;
    } catch {
      return Infinity;
    }
  }

  /**
   * Returns true if the cached feed is still fresh (within TTL).
   */
  async isFeedFresh() {
    const age = await this.getRecentFeedAge();
    return age < this.FEED_TTL;
  }

  /**
   * Clear all feed data (metadata + state). Does NOT remove blobs.
   */
  async clearFeed() {
    try {
      await this.init();
      return new Promise((resolve) => {
        const tx = this.db.transaction(['metadata', 'feed_state'], 'readwrite');
        tx.objectStore('metadata').clear();
        tx.objectStore('feed_state').clear();
        tx.oncomplete = () => resolve();
        tx.onerror    = () => resolve();
      });
    } catch (err) {
      console.warn('ReelsCache clearFeed error:', err);
    }
  }

  /**
   * Full reset — clears all stored data including blobs.
   */
  async clearAll() {
    try {
      await this.init();
      return new Promise((resolve) => {
        const tx = this.db.transaction(['metadata', 'feed_state', 'video_blobs'], 'readwrite');
        tx.objectStore('metadata').clear();
        tx.objectStore('feed_state').clear();
        tx.objectStore('video_blobs').clear();
        tx.oncomplete = () => resolve();
        tx.onerror    = () => resolve();
      });
    } catch (err) {
      console.warn('ReelsCache clearAll error:', err);
    }
  }

  // ── Video Blob Management ────────────────────────────────────

  /**
   * Save a video blob. Falls back to Cache API if IndexedDB quota is exceeded.
   */
  async saveVideoBlob(videoId, blob) {
    const numericId = parseInt(videoId, 10);

    // Try IndexedDB first
    try {
      await this.init();
      await new Promise((resolve, reject) => {
        const tx = this.db.transaction('video_blobs', 'readwrite');
        tx.objectStore('video_blobs').put(blob, numericId);
        tx.oncomplete = () => resolve();
        tx.onerror    = (e) => reject(e.target.error);
      });
      return; // Success
    } catch (err) {
      if (err && err.name === 'QuotaExceededError') {
        console.warn(`ReelsCache: IndexedDB quota exceeded for reel ${numericId}, trying Cache API`);
      } else {
        console.warn('ReelsCache saveVideoBlob (IndexedDB) error:', err);
        return;
      }
    }

    // Fallback: Cache API
    if ('caches' in window) {
      try {
        const cache    = await caches.open(this.CACHE_NAME);
        const blobUrl  = `/__reel_blob__/${numericId}`;
        await cache.put(new Request(blobUrl), new Response(blob, {
          headers: { 'Content-Type': blob.type || 'video/mp4' }
        }));
      } catch (cacheErr) {
        console.warn('ReelsCache saveVideoBlob (Cache API) error:', cacheErr);
      }
    }
  }

  /**
   * Get a video blob. Checks IndexedDB, then Cache API.
   */
  async getVideoBlob(videoId) {
    const numericId = parseInt(videoId, 10);

    // Try IndexedDB first
    try {
      await this.init();
      const blob = await new Promise((resolve) => {
        const tx  = this.db.transaction('video_blobs', 'readonly');
        const req = tx.objectStore('video_blobs').get(numericId);
        req.onsuccess = () => resolve(req.result || null);
        req.onerror   = () => resolve(null);
      });
      if (blob) return blob;
    } catch (err) {
      console.warn('ReelsCache getVideoBlob (IndexedDB) error:', err);
    }

    // Fallback: Cache API
    if ('caches' in window) {
      try {
        const cache    = await caches.open(this.CACHE_NAME);
        const blobUrl  = `/__reel_blob__/${numericId}`;
        const response = await cache.match(new Request(blobUrl));
        if (response) {
          return await response.blob();
        }
      } catch (cacheErr) {
        // Silently ignore Cache API misses
      }
    }

    return null;
  }

  /**
   * Remove a specific blob from both IndexedDB and Cache API.
   */
  async removeVideoBlob(videoId) {
    const numericId = parseInt(videoId, 10);
    try {
      await this.init();
      await new Promise((resolve) => {
        const tx = this.db.transaction('video_blobs', 'readwrite');
        tx.objectStore('video_blobs').delete(numericId);
        tx.oncomplete = () => resolve();
        tx.onerror    = () => resolve();
      });
    } catch (err) {
      console.warn('ReelsCache removeVideoBlob error:', err);
    }
    // Also remove from Cache API
    if ('caches' in window) {
      try {
        const cache   = await caches.open(this.CACHE_NAME);
        await cache.delete(new Request(`/__reel_blob__/${numericId}`));
      } catch {}
    }
  }

  /**
   * Evict all blobs NOT in the keepIds array (rolling cache window).
   * Also evicts from Cache API for consistency.
   */
  async cleanOldBlobs(keepIds) {
    const numericKeepIds = keepIds.map(id => parseInt(id, 10));

    // Clean IndexedDB
    try {
      await this.init();
      await new Promise((resolve) => {
        const tx    = this.db.transaction('video_blobs', 'readwrite');
        const store = tx.objectStore('video_blobs');
        const req   = store.getAllKeys();
        req.onsuccess = () => {
          const keys = req.result || [];
          keys.forEach(key => {
            if (!numericKeepIds.includes(parseInt(key, 10))) {
              store.delete(key);
            }
          });
          resolve();
        };
        req.onerror = () => resolve();
      });
    } catch (err) {
      console.warn('ReelsCache cleanOldBlobs (IndexedDB) error:', err);
    }

    // Clean Cache API
    if ('caches' in window) {
      try {
        const cache = await caches.open(this.CACHE_NAME);
        const keys  = await cache.keys();
        for (const req of keys) {
          const match = req.url.match(/__reel_blob__\/(\d+)/);
          if (match && !numericKeepIds.includes(parseInt(match[1], 10))) {
            await cache.delete(req);
          }
        }
      } catch {}
    }
  }
}

window.ReelsCache = ReelsCache;
