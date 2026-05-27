/**
 * FreeHub — watch-time heartbeat (credits earnings while video plays)
 */
(function () {
  const cfg = window.FH_WATCH;
  if (!cfg || !cfg.viewId || !cfg.videoId) return;

  const INTERVAL_MS = 30000;
  let lastTick = Date.now();
  let timer = null;

  function isPlaying() {
    const v = document.getElementById('fh-player');
    return v && !v.paused && !v.ended && v.readyState > 2;
  }

  function applyStats(stats) {
    if (!stats) return;
    const bal = document.querySelector('[data-fh-balance]');
    const life = document.querySelector('[data-fh-lifetime-earnings]');
    const wt = document.querySelector('[data-fh-watch-time]');
    const wh = document.querySelector('[data-fh-watch-hours]');
    if (bal && stats.balance_formatted) bal.textContent = stats.balance_formatted;
    if (life && stats.lifetime_watch_formatted) life.textContent = stats.lifetime_watch_formatted;
    if (wt && stats.watch_time_formatted) wt.textContent = stats.watch_time_formatted;
    if (wh && stats.watch_hours != null) wh.textContent = Number(stats.watch_hours).toFixed(2) + 'h';
  }

  function sendHeartbeat() {
    if (!isPlaying()) return;
    const now = Date.now();
    const secs = Math.min(30, Math.max(1, Math.round((now - lastTick) / 1000)));
    lastTick = now;

    fetch(cfg.endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      body: JSON.stringify({
        view_id: cfg.viewId,
        video_id: cfg.videoId,
        seconds: secs,
        playing: true,
      }),
    })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d && d.success && d.stats) applyStats(d.stats);
      })
      .catch(function () {});
  }

  function start() {
    if (timer) return;
    lastTick = Date.now();
    timer = setInterval(sendHeartbeat, INTERVAL_MS);
    sendHeartbeat();
  }

  function stop() {
    if (timer) {
      clearInterval(timer);
      timer = null;
    }
  }

  const player = document.getElementById('fh-player');
  if (!player) return;

  player.addEventListener('play', start);
  player.addEventListener('pause', stop);
  player.addEventListener('ended', stop);
  document.addEventListener('visibilitychange', function () {
    if (document.hidden) stop();
    else if (isPlaying()) start();
  });
  if (isPlaying()) start();
})();
