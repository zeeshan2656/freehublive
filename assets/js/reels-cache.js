/**
 * ReelsCache - Client-side IndexedDB caching engine for video metadata and binary blobs.
 * Enables zero-delay, local-first playback of reels.
 */
class ReelsCache {
  constructor() {
    this.dbName = 'ReelsDatabase';
    this.dbVersion = 1;
    this.db = null;
  }

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
      request.onsuccess = (e) => {
        this.db = e.target.result;
        resolve(this.db);
      };
      request.onerror = (e) => reject(e);
    });
  }

  async saveFeed(videos) {
    try {
      await this.init();
      return new Promise((resolve, reject) => {
        const tx = this.db.transaction(['metadata', 'feed_state'], 'readwrite');
        const metaStore = tx.objectStore('metadata');
        const stateStore = tx.objectStore('feed_state');

        // Clear old metadata
        metaStore.clear();

        // Save new metadata
        videos.forEach(v => {
          if (v && v.id) {
            metaStore.put(v);
          }
        });

        // Save feed order (array of IDs)
        const ids = videos.map(v => v.id);
        stateStore.put(ids, 'current_feed');

        tx.oncomplete = () => resolve();
        tx.onerror = (e) => reject(e);
      });
    } catch (err) {
      console.warn('ReelsCache saveFeed error:', err);
    }
  }

  async getFeed() {
    try {
      await this.init();
      const ids = await new Promise((resolve) => {
        const tx = this.db.transaction('feed_state', 'readonly');
        const req = tx.objectStore('feed_state').get('current_feed');
        req.onsuccess = () => resolve(req.result || []);
        req.onerror = () => resolve([]);
      });

      if (!ids.length) return [];

      return new Promise((resolve) => {
        const tx = this.db.transaction('metadata', 'readonly');
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

  async saveVideoBlob(videoId, blob) {
    try {
      await this.init();
      return new Promise((resolve, reject) => {
        const tx = this.db.transaction('video_blobs', 'readwrite');
        tx.objectStore('video_blobs').put(blob, parseInt(videoId, 10));
        tx.oncomplete = () => resolve();
        tx.onerror = (e) => reject(e);
      });
    } catch (err) {
      console.warn('ReelsCache saveVideoBlob error:', err);
    }
  }

  async getVideoBlob(videoId) {
    try {
      await this.init();
      return new Promise((resolve) => {
        const tx = this.db.transaction('video_blobs', 'readonly');
        const req = tx.objectStore('video_blobs').get(parseInt(videoId, 10));
        req.onsuccess = () => resolve(req.result || null);
        req.onerror = () => resolve(null);
      });
    } catch (err) {
      console.warn('ReelsCache getVideoBlob error:', err);
      return null;
    }
  }

  async removeVideoBlob(videoId) {
    try {
      await this.init();
      return new Promise((resolve) => {
        const tx = this.db.transaction('video_blobs', 'readwrite');
        tx.objectStore('video_blobs').delete(parseInt(videoId, 10));
        tx.oncomplete = () => resolve();
      });
    } catch (err) {
      console.warn('ReelsCache removeVideoBlob error:', err);
    }
  }

  async cleanOldBlobs(keepIds) {
    try {
      await this.init();
      const numericKeepIds = keepIds.map(id => parseInt(id, 10));
      return new Promise((resolve) => {
        const tx = this.db.transaction('video_blobs', 'readwrite');
        const store = tx.objectStore('video_blobs');
        const req = store.getAllKeys();
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
      console.warn('ReelsCache cleanOldBlobs error:', err);
    }
  }
}
window.ReelsCache = ReelsCache;
