(() => {
  'use strict';

  if (window.HNT_MOMENTS_HEADER_LIVE_READY || !window.fetch) return;
  window.HNT_MOMENTS_HEADER_LIVE_READY = true;

  const endpoints = Object.assign({
    badges: '/socialite/header/live-badges',
    notifications: '/socialite/header/notifications',
    messages: '/socialite/header/messages',
    friends: '/socialite/header/friend-requests',
    markAll: '/notifications/read-all',
  }, window.HNT_MOMENTS_HEADER_ENDPOINTS || {});

  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const isEnglish = (document.documentElement.lang || '').toLowerCase().startsWith('en');
  const labels = isEnglish ? {
    loading: 'Loading current data …',
    noNotifications: 'No notifications',
    noMessages: 'No conversations yet',
    noFriends: 'No open friend requests',
    reload: 'Please reload the page.',
  } : {
    loading: 'Aktuelle Daten werden geladen …',
    noNotifications: 'Keine Benachrichtigungen',
    noMessages: 'Noch keine Unterhaltungen',
    noFriends: 'Keine offenen Freundschaftsanfragen',
    reload: 'Bitte lade die Seite neu.',
  };

  const friendsList = document.querySelector('.header-request-list');
  const messagesList = document.querySelector('.header-message-list');
  const notificationsList = document.querySelector('.header-notification-list');
  const markAllButton = document.querySelector('.header-mark-all');

  if (!friendsList || !messagesList || !notificationsList) return;

  const escapeHtml = (value = '') => String(value).replace(/[&<>"']/g, (character) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
  })[character]);

  const parse = (html = '') => {
    const template = document.createElement('template');
    template.innerHTML = String(html).trim();
    return template.content;
  };

  const requestJson = async (url, options = {}) => {
    const response = await fetch(url, {
      credentials: 'same-origin',
      cache: 'no-store',
      ...options,
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(options.method && options.method !== 'GET' ? { 'X-CSRF-TOKEN': csrf } : {}),
        ...(options.headers || {}),
      },
    });

    if (!response.ok) throw new Error(`Header request failed with ${response.status}`);
    return response.json();
  };

  const setBadge = (type, value) => {
    const count = Math.max(0, Number.parseInt(value, 10) || 0);
    const badge = document.querySelector(`[data-header-badge="${type}"]`);
    const countLabel = document.querySelector(`[data-dropdown-count="${type}"]`);

    if (badge) {
      badge.textContent = count > 99 ? '99+' : String(count);
      badge.classList.toggle('is-empty', count === 0);
    }

    if (countLabel) {
      countLabel.textContent = isEnglish
        ? `${count} ${type === 'friends' ? 'open' : 'unread'}`
        : `${count} ${type === 'friends' ? 'offen' : 'ungelesen'}`;
    }
  };

  const stateMarkup = (title, text = '') => `
    <div class="header-live-state">
      <strong>${escapeHtml(title)}</strong>
      ${escapeHtml(text)}
    </div>
  `;

  const renderMessages = (payload) => {
    const fragment = parse(payload.html || '');
    const rows = [...fragment.querySelectorAll('a.hnt-message-shell-item')];

    if (!rows.length) {
      messagesList.innerHTML = stateMarkup(labels.noMessages);
      setBadge('messages', payload.unread_count);
      return;
    }

    messagesList.innerHTML = rows.map((row) => {
      const href = row.getAttribute('href') || '/messages';
      const chatUrl = row.getAttribute('data-hnt-chat-tab-url') || `${href.replace(/\/$/, '')}/chat-tab`;
      const conversationId = row.getAttribute('data-hnt-chat-conversation-id') || '';
      const avatar = row.querySelector('img')?.getAttribute('src') || '/assets/vikinger/img/default-avatar.svg';
      const title = row.querySelector('.hnt-message-shell-title-row strong')?.textContent?.trim() || 'HNT Hunter';
      const preview = row.querySelector('.hnt-message-shell-preview')?.textContent?.trim() || '';
      const meta = row.querySelector('small')?.textContent?.trim() || '';
      const unread = row.classList.contains('is-unread');

      return `
        <a class="header-message-item${unread ? ' unread' : ''}"
           href="${escapeHtml(href)}"
           role="menuitem"
           data-hnt-chat-tab-open
           data-hnt-chat-conversation-id="${escapeHtml(conversationId)}"
           data-hnt-chat-tab-url="${escapeHtml(chatUrl)}">
          <img alt="${escapeHtml(title)}" src="${escapeHtml(avatar)}">
          <span><strong>${escapeHtml(title)}</strong><small>${escapeHtml(preview)}</small></span>
          <time>${escapeHtml(meta)}</time>
        </a>
      `;
    }).join('');

    setBadge('messages', payload.unread_count);
  };

  const renderNotifications = (payload) => {
    const fragment = parse(payload.html || '');
    const rows = [...fragment.querySelectorAll('[data-hnt-notification-item]')];

    if (!rows.length) {
      notificationsList.innerHTML = stateMarkup(labels.noNotifications);
      setBadge('notifications', payload.unread_count);
      if (markAllButton) markAllButton.disabled = true;
      return;
    }

    notificationsList.innerHTML = rows.map((row) => {
      const title = row.querySelector('h3')?.textContent?.trim() || labels.noNotifications;
      const body = row.querySelector('p')?.textContent?.trim() || '';
      const meta = row.querySelector('small')?.textContent?.trim() || '';
      const avatar = row.querySelector('img')?.getAttribute('src') || '/assets/vikinger/img/default-avatar.svg';
      const readUrl = row.querySelector('form')?.getAttribute('action') || '';
      const unread = row.classList.contains('is-unread');

      return `
        <button class="header-notification-item${unread ? ' unread' : ''}"
                type="button"
                role="menuitem"
                data-moment-notification-read="${escapeHtml(readUrl)}">
          <span class="header-notification-icon"><img alt="" src="${escapeHtml(avatar)}"></span>
          <span><strong>${escapeHtml(title)}</strong><small>${escapeHtml(body)}</small></span>
          <time>${escapeHtml(meta)}</time>
        </button>
      `;
    }).join('');

    setBadge('notifications', payload.unread_count);
    if (markAllButton) markAllButton.disabled = Number(payload.unread_count || 0) === 0;
  };

  const renderFriends = (payload) => {
    const fragment = parse(payload.html || '');
    const rows = [...fragment.querySelectorAll('[data-hnt-friend-request-item]')];

    if (!rows.length) {
      friendsList.innerHTML = stateMarkup(labels.noFriends);
      setBadge('friends', payload.count);
      return;
    }

    friendsList.innerHTML = rows.map((row) => {
      const profileLink = row.querySelector('a.hnt-notification-shell-avatar');
      const profileUrl = profileLink?.getAttribute('href') || '/members';
      const avatar = profileLink?.querySelector('img')?.getAttribute('src') || '/assets/vikinger/img/default-avatar.svg';
      const title = row.querySelector('h3')?.textContent?.trim() || 'HNT Hunter';
      const meta = row.querySelector('small')?.textContent?.trim() || '';
      const forms = [...row.querySelectorAll('form')];
      const acceptUrl = forms.find((form) => form.querySelector('.is-accept'))?.getAttribute('action') || '';
      const declineUrl = forms.find((form) => form.querySelector('.is-decline'))?.getAttribute('action') || '';

      return `
        <article class="header-request-item">
          <a href="${escapeHtml(profileUrl)}" aria-label="${escapeHtml(title)}"><img alt="${escapeHtml(title)}" src="${escapeHtml(avatar)}"></a>
          <div><strong>${escapeHtml(title)}</strong><small>${escapeHtml(meta)}</small></div>
          <div class="header-request-actions">
            <button class="friend-accept" type="button" data-moment-friend-action="${escapeHtml(acceptUrl)}" aria-label="${isEnglish ? 'Accept' : 'Annehmen'}"><svg><use href="#i-check"></use></svg></button>
            <button class="friend-decline" type="button" data-moment-friend-action="${escapeHtml(declineUrl)}" aria-label="${isEnglish ? 'Decline' : 'Ablehnen'}"><svg><use href="#i-x"></use></svg></button>
          </div>
        </article>
      `;
    }).join('');

    setBadge('friends', payload.count);
  };

  const loadBadges = async () => {
    const payload = await requestJson(endpoints.badges);
    setBadge('notifications', payload.notifications_unread);
    setBadge('messages', payload.messages_unread);
    setBadge('friends', payload.friend_request_count);
  };

  const loadMessages = async () => renderMessages(await requestJson(endpoints.messages));
  const loadNotifications = async () => renderNotifications(await requestJson(endpoints.notifications));
  const loadFriends = async () => renderFriends(await requestJson(endpoints.friends));

  const loadAll = async () => {
    friendsList.innerHTML = stateMarkup(labels.loading);
    messagesList.innerHTML = stateMarkup(labels.loading);
    notificationsList.innerHTML = stateMarkup(labels.loading);

    const results = await Promise.allSettled([loadBadges(), loadMessages(), loadNotifications(), loadFriends()]);
    results.forEach((result) => {
      if (result.status === 'rejected') console.debug?.('Moments header live data skipped', result.reason);
    });
  };

  document.querySelector('#messagesMenuTrigger')?.addEventListener('click', () => window.setTimeout(loadMessages, 20));
  document.querySelector('#notificationsMenuTrigger')?.addEventListener('click', () => window.setTimeout(loadNotifications, 20));
  document.querySelector('#friendsMenuTrigger')?.addEventListener('click', () => window.setTimeout(loadFriends, 20));

  notificationsList.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-moment-notification-read]');
    if (!button) return;
    const url = button.getAttribute('data-moment-notification-read');
    if (!url) return;

    button.disabled = true;
    try {
      const payload = await requestJson(url, { method: 'POST' });
      button.classList.remove('unread');
      setBadge('notifications', payload.unread_count);
      if (payload.action_url) window.location.assign(payload.action_url);
    } catch (error) {
      console.debug?.('Notification action failed', error);
      button.disabled = false;
    }
  });

  friendsList.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-moment-friend-action]');
    if (!button) return;
    const url = button.getAttribute('data-moment-friend-action');
    if (!url) return;

    const row = button.closest('.header-request-item');
    row?.querySelectorAll('button').forEach((item) => { item.disabled = true; });
    try {
      await requestJson(url, { method: 'POST' });
      row?.remove();
      await loadBadges();
      if (!friendsList.querySelector('.header-request-item')) friendsList.innerHTML = stateMarkup(labels.noFriends);
    } catch (error) {
      console.debug?.('Friend request action failed', error);
      row?.querySelectorAll('button').forEach((item) => { item.disabled = false; });
    }
  });

  markAllButton?.addEventListener('click', async (event) => {
    event.preventDefault();
    event.stopImmediatePropagation();
    if (markAllButton.disabled) return;

    markAllButton.disabled = true;
    try {
      await requestJson(endpoints.markAll, { method: 'POST' });
      notificationsList.querySelectorAll('.unread').forEach((item) => item.classList.remove('unread'));
      setBadge('notifications', 0);
    } catch (error) {
      console.debug?.('Mark all notifications failed', error);
      markAllButton.disabled = false;
    }
  }, true);

  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) loadAll();
  });

  loadAll();
  window.setInterval(() => {
    if (!document.hidden) loadAll();
  }, 20000);
})();
