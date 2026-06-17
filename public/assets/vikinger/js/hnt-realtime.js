(function () {
  const config = window.HH_REALTIME || {};

  if (!config.enabled || !config.appKey || !config.host || !config.userId || !window.WebSocket) {
    return;
  }

  const debug = (...args) => {
    if (window.HH_REALTIME_DEBUG && window.console && typeof window.console.debug === 'function') {
      window.console.debug('[HH Realtime]', ...args);
    }
  };

  const channelName = `private-user.${config.userId}`;
  const csrfToken = config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const endpoints = config.endpoints || {};
  const scheme = String(config.scheme || 'https').toLowerCase() === 'http' ? 'ws' : 'wss';
  const port = Number(config.port || 443);
  const portPart = port && !((scheme === 'wss' && port === 443) || (scheme === 'ws' && port === 80)) ? `:${port}` : '';
  const socketUrl = `${scheme}://${config.host}${portPart}/app/${encodeURIComponent(config.appKey)}?protocol=7&client=js&version=8.4.0&flash=false`;

  let socket = null;
  let socketId = '';
  let reconnectTimer = 0;
  let reconnectAttempt = 0;
  let refreshTimer = 0;
  let refreshRunning = false;
  let refreshAgain = false;

  const capBadgeValue = (value) => {
    const count = Number(value || 0);
    return count > 99 ? '99+' : String(count);
  };

  const ensureBadge = (triggerSelector, badgeSelector, className) => {
    let badge = document.querySelector(`${triggerSelector} ${badgeSelector}`);

    if (badge) {
      return badge;
    }

    const trigger = document.querySelector(triggerSelector);
    if (!trigger) {
      return null;
    }

    badge = document.createElement('span');
    badge.className = className;
    badge.setAttribute(badgeSelector.replace(/^\[|\]$/g, ''), '');
    trigger.appendChild(badge);

    return badge;
  };

  const ensureBadges = (selector) => {
    if (selector === '[data-hh-notification-count]') {
      ensureBadge('[data-hh-header-dropdown-trigger="notifications"]', selector, 'hh-action-badge');
      ensureBadge('[data-hh-header-dropdown-trigger="mobile-notifications"]', selector, 'hh-mobile-alert-badge');
    }

    if (selector === '[data-hh-friend-request-count]') {
      ensureBadge('[data-hh-header-dropdown-trigger="friend-requests"]', selector, 'hh-action-badge');
      ensureBadge('[data-hh-header-dropdown-trigger="mobile-friend-requests"]', selector, 'hh-mobile-alert-badge');
    }

    if (selector === '[data-hh-message-count]') {
      ensureBadge('[data-hh-chat-dock-open="list"]', selector, 'hh-action-badge');
    }
  };

  const setHeaderBadge = (selector, value) => {
    const count = Number(value || 0);

    if (count > 0) {
      ensureBadges(selector);
    }

    document.querySelectorAll(selector).forEach((badge) => {
      if (count > 0) {
        badge.textContent = capBadgeValue(count);
        badge.hidden = false;
      } else {
        badge.remove();
      }
    });
  };

  const fetchJson = async (url, options = {}) => {
    if (!url) {
      return null;
    }

    const response = await fetch(url, {
      ...options,
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(options.headers || {})
      }
    });

    if (!response.ok) {
      throw new Error(`Request failed: ${response.status}`);
    }

    return response.json();
  };

  const refreshHeader = async () => {
    if (refreshRunning) {
      refreshAgain = true;
      return;
    }

    refreshRunning = true;
    refreshAgain = false;

    try {
      if (window.HNT_PREVIEW_LIVE_BADGES && window.HNT_PREVIEW_LIVE_BADGES.endpoint) {
        document.dispatchEvent(new CustomEvent('hnt:preview-live-badges-refresh'));
        return;
      }

      const badges = await fetchJson(endpoints.badges);

      if (badges && badges.authenticated !== false) {
        setHeaderBadge('[data-hh-notification-count]', badges.notifications_unread);
        setHeaderBadge('[data-hh-message-count]', badges.messages_unread);
        setHeaderBadge('[data-hh-friend-request-count]', badges.friend_request_count);
      }
    } catch (error) {
      debug('Header refresh skipped', error);
    } finally {
      refreshRunning = false;

      if (refreshAgain) {
        scheduleRefresh();
      }
    }
  };

  const scheduleRefresh = () => {
    window.clearTimeout(refreshTimer);
    refreshTimer = window.setTimeout(refreshHeader, 500);
  };

  const send = (payload) => {
    if (!socket || socket.readyState !== WebSocket.OPEN) {
      return;
    }

    socket.send(JSON.stringify(payload));
  };

  const authorizeAndSubscribe = async () => {
    if (!socketId || !csrfToken || !config.authEndpoint) {
      return;
    }

    const body = new URLSearchParams();
    body.set('socket_id', socketId);
    body.set('channel_name', channelName);

    const auth = await fetchJson(config.authEndpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
        'X-CSRF-TOKEN': csrfToken
      },
      body
    });

    if (!auth || !auth.auth) {
      return;
    }

    send({
      event: 'pusher:subscribe',
      data: {
        auth: auth.auth,
        channel: channelName
      }
    });
  };

  const handleMessage = (rawMessage) => {
    let message = null;

    try {
      message = JSON.parse(rawMessage.data || '{}');
    } catch (error) {
      debug('Invalid socket payload', error);
      return;
    }

    if (message.event === 'pusher:connection_established') {
      try {
        const data = typeof message.data === 'string' ? JSON.parse(message.data) : message.data;
        socketId = data?.socket_id || '';
      } catch (error) {
        socketId = '';
      }

      authorizeAndSubscribe().catch((error) => debug('Subscribe skipped', error));
      return;
    }

    if (message.event === 'pusher:ping') {
      send({ event: 'pusher:pong', data: {} });
      return;
    }

    if (message.event === 'pusher_internal:subscription_succeeded') {
      debug('Subscribed', channelName);
      return;
    }

    const eventName = String(message.event || '').toLowerCase();
    const shouldRefresh = eventName === 'user.notification.created'
      || eventName === 'conversation.message.created'
      || eventName.includes('notification')
      || eventName.includes('message');

    if (shouldRefresh) {
      scheduleRefresh();
    }
  };

  const scheduleReconnect = () => {
    window.clearTimeout(reconnectTimer);
    reconnectAttempt += 1;
    const delay = Math.min(30000, 1000 * Math.max(1, reconnectAttempt));
    reconnectTimer = window.setTimeout(connect, delay);
  };

  function connect() {
    window.clearTimeout(reconnectTimer);

    try {
      socket = new WebSocket(socketUrl);
    } catch (error) {
      debug('Socket creation failed', error);
      scheduleReconnect();
      return;
    }

    socket.addEventListener('open', () => {
      reconnectAttempt = 0;
    });

    socket.addEventListener('message', handleMessage);
    socket.addEventListener('error', (error) => debug('Socket error', error));
    socket.addEventListener('close', () => {
      socketId = '';
      scheduleReconnect();
    });
  }

  connect();
})();
