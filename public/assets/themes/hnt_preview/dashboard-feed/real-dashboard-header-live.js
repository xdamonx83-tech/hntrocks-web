/* Restore the existing fast-live header badge behaviour inside the dashboard preview. */
(() => {
  if (!window.fetch) return;

  const endpoint = '/socialite/header/live-badges';
  const intervalMs = 15000;
  let timer = 0;
  let running = false;
  let queued = false;
  const lastCounts = new Map();

  const loadThemeStyle = (selector, href, datasetKey) => {
    if (document.querySelector(selector)) return;

    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = href;
    link.dataset[datasetKey] = '1';
    document.head.appendChild(link);
  };

  const loadThemeColors = () => {
    loadThemeStyle(
      'link[data-hnt-theme-colors]',
      '/assets/themes/hnt_preview/theme-colors.css?v=20260713-2',
      'hntThemeColors',
    );

    loadThemeStyle(
      'link[data-hnt-theme-page-polish]',
      '/assets/themes/hnt_preview/theme-page-polish.css?v=20260713-1',
      'hntThemePagePolish',
    );
  };

  const addStyle = () => {
    if (document.getElementById('real-dashboard-header-live-style')) return;

    const style = document.createElement('style');
    style.id = 'real-dashboard-header-live-style';
    style.textContent = `
      .header-action-badge.is-live-pulse {
        animation: hntHeaderBadgePulse .55s ease;
      }

      @keyframes hntHeaderBadgePulse {
        0%, 100% { transform: scale(1); }
        45% { transform: scale(1.22); }
      }
    `;
    document.head.appendChild(style);
  };

  const normalize = (value) => Math.max(0, Number.parseInt(value, 10) || 0);

  const setBadge = (type, value) => {
    const count = normalize(value);
    const badge = document.querySelector(`[data-header-badge="${type}"]`);
    const label = document.querySelector(`[data-dropdown-count="${type}"]`);
    const previous = lastCounts.has(type) ? lastCounts.get(type) : count;

    if (badge) {
      badge.textContent = count > 99 ? '99+' : String(count);
      badge.classList.toggle('is-empty', count === 0);

      if (count > previous) {
        badge.classList.remove('is-live-pulse');
        void badge.offsetWidth;
        badge.classList.add('is-live-pulse');
        window.setTimeout(() => badge.classList.remove('is-live-pulse'), 650);
      }
    }

    if (label) {
      const suffix = type === 'friends' ? 'offen' : 'ungelesen';
      label.textContent = `${count} ${suffix}`;
    }

    lastCounts.set(type, count);
  };

  const apply = (payload = {}) => {
    if (payload.authenticated === false) return;

    setBadge('notifications', payload.notifications_unread);
    setBadge('messages', payload.messages_unread);
    setBadge('friends', payload.friend_request_count);

    document.dispatchEvent(new CustomEvent('hnt:dashboard-live-badges-updated', {
      detail: {
        notifications: normalize(payload.notifications_unread),
        messages: normalize(payload.messages_unread),
        friends: normalize(payload.friend_request_count),
      },
    }));
  };

  const refresh = async () => {
    if (running) {
      queued = true;
      return;
    }

    running = true;
    queued = false;

    try {
      const response = await fetch(`${endpoint}?_=${Date.now()}`, {
        credentials: 'same-origin',
        cache: 'no-store',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      if (!response.ok) throw new Error(`Live badge request failed with ${response.status}`);
      apply(await response.json());
    } catch (error) {
      console.debug?.('HNT live badges skipped', error);
    } finally {
      running = false;
      if (queued) window.setTimeout(refresh, 100);
    }
  };

  const schedule = (delay = intervalMs) => {
    window.clearTimeout(timer);
    timer = window.setTimeout(async () => {
      await refresh();
      schedule(intervalMs);
    }, delay);
  };

  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
      refresh();
      schedule(intervalMs);
    }
  });

  window.addEventListener('focus', () => {
    refresh();
    schedule(intervalMs);
  });

  document.addEventListener('hnt:preview-live-badges-refresh', () => {
    refresh();
    schedule(intervalMs);
  });

  document.addEventListener('hnt:notification-created', refresh);
  document.addEventListener('hnt:message-created', refresh);
  document.addEventListener('hnt:friend-request-created', refresh);

  loadThemeColors();
  addStyle();
  window.setTimeout(refresh, 450);
  schedule(intervalMs);
})();
