/* Keep the live dashboard presence count honest for every authenticated user. */
(() => {
  if (window.HNT_DASHBOARD_FEED_LIVE !== true || !window.fetch) return;
  if (window.HNT_DASHBOARD_PRESENCE_SYNC_STARTED) return;
  window.HNT_DASHBOARD_PRESENCE_SYNC_STARTED = true;

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
  let refreshing = false;
  let heartbeatRunning = false;

  const formatNumber = (value) => new Intl.NumberFormat(document.documentElement.lang || 'de')
    .format(Math.max(0, Number(value) || 0));

  const sendHeartbeat = async () => {
    if (!csrfToken || heartbeatRunning) return;
    heartbeatRunning = true;

    try {
      await fetch('/presence/heartbeat', {
        method: 'POST',
        credentials: 'same-origin',
        cache: 'no-store',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrfToken,
        },
      });
    } catch (_) {
      // Presence is best effort and must never interrupt the feed.
    } finally {
      heartbeatRunning = false;
    }
  };

  const applyCommunityPresence = (community = {}) => {
    const panel = document.getElementById('compositionPanel');
    if (!panel) return;

    const online = Math.max(0, Number(community.online_now) || 0);
    const activeToday = Math.max(0, Number(community.active_today) || 0);
    const total = Math.max(0, Number(community.total_members) || 0);
    const activeRate = Math.max(0, Math.min(100, Number(community.active_rate) || 0));

    const live = panel.querySelector('.composition-live');
    if (live) live.innerHTML = `<i></i> ${formatNumber(online)} online`;

    const values = panel.querySelectorAll('.composition-values strong');
    if (values[0]) values[0].textContent = formatNumber(activeToday);
    if (values[1]) values[1].textContent = formatNumber(online);

    const stats = panel.querySelectorAll('.composition-stat-grid article strong');
    if (stats[0]) stats[0].textContent = formatNumber(activeToday);

    const ring = panel.querySelector('.composition-ring');
    if (ring) {
      const degrees = Math.round((342 * activeRate) / 100);
      ring.style.background = `conic-gradient(var(--yellow) 0 ${degrees}deg, var(--dark) ${degrees}deg 342deg, transparent 342deg 360deg)`;
      const totalNode = ring.querySelector('strong');
      if (totalNode) totalNode.textContent = formatNumber(total);
    }
  };

  const refreshCommunity = async () => {
    if (refreshing) return;
    refreshing = true;

    try {
      const url = new URL(window.location.href);
      url.search = '';
      url.hash = '';
      url.searchParams.set('dashboard_community', '1');

      const response = await fetch(url.toString(), {
        credentials: 'same-origin',
        cache: 'no-store',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      if (!response.ok) return;
      const payload = await response.json();
      applyCommunityPresence(payload.community || {});
    } catch (_) {
      // Keep the last known value when a background refresh fails.
    } finally {
      refreshing = false;
    }
  };

  const sync = async () => {
    await sendHeartbeat();
    window.setTimeout(refreshCommunity, 250);
  };

  sync();
  window.setInterval(sync, 30000);
  window.setInterval(refreshCommunity, 15000);

  window.addEventListener('focus', sync);
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) sync();
  });
})();
