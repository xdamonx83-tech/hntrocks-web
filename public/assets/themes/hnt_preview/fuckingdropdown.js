/*
 * HNT.ROCKS shared topbar runtime.
 *
 * Loaded by the shared header partial on every page. It owns dropdown opening,
 * live header data, navigation buttons and notification/friend actions.
 */
(() => {
  if (window.HNT_SHARED_TOPBAR_ACTIVE) return;
  window.HNT_SHARED_TOPBAR_ACTIVE = true;

  const header = document.querySelector('[data-hnt-shared-header]');
  if (!(header instanceof HTMLElement)) return;

  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const friendsList = header.querySelector('.header-request-list');
  const messagesList = header.querySelector('.header-message-list');
  const notificationsList = header.querySelector('.header-notification-list');
  const markAllButton = header.querySelector('.header-mark-all');
  const i18n = window.HNT_PREVIEW_I18N?.header || {};

  const fallback = {
    friend_requests: 'Freundschaftsanfragen',
    messages: 'Nachrichten',
    notifications: 'Benachrichtigungen',
    loading_real_data: 'Echte Daten werden geladen …',
    no_open_requests: 'Keine offenen Anfragen',
    requests_auto: 'Neue Anfragen erscheinen automatisch hier.',
    no_conversations: 'Noch keine Unterhaltungen',
    messages_auto: 'Deine privaten Nachrichten erscheinen hier.',
    no_notifications: 'Keine Benachrichtigungen',
    notifications_auto: 'Neue Hinweise erscheinen automatisch hier.',
    requests_unavailable: 'Anfragen nicht verfügbar',
    messages_unavailable: 'Nachrichten nicht verfügbar',
    notifications_unavailable: 'Benachrichtigungen nicht verfügbar',
    reload_page: 'Bitte lade die Seite neu.',
    settings: 'Einstellungen',
    rocks: 'Rocks',
    friends: 'Freunde',
    posts: 'Posts',
    all_read: 'Alles gelesen',
    mark_all_read: 'Alle als gelesen markieren',
    level: 'Level :level',
    open_count: ':count offen',
    unread_count: ':count ungelesen',
    accept_user: ':name annehmen',
    decline_user: ':name ablehnen',
  };

  const text = (key, replacements = {}) => {
    let value = String(i18n[key] || fallback[key] || key);
    Object.entries(replacements).forEach(([name, replacement]) => {
      value = value.replaceAll(`:${name}`, String(replacement));
    });
    return value;
  };

  const escapeHtml = (value = '') => String(value).replace(/[&<>"']/g, (character) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  })[character]);

  const formatNumber = (value) => new Intl.NumberFormat(document.documentElement.lang || 'de-DE')
    .format(Math.max(0, Number(value) || 0));

  let lastPayload = null;
  let lastLoadedAt = 0;
  let loading = null;

  const actionMenus = [...header.querySelectorAll('.header-action-menu')];
  const navItems = [...header.querySelectorAll('.main-nav-item')];

  const closeActionMenus = (except = null) => {
    actionMenus.forEach((menu) => {
      if (menu === except) return;
      menu.classList.remove('is-open');
      menu.querySelector('.header-dropdown-trigger')?.setAttribute('aria-expanded', 'false');
    });
  };

  const closeNavMenus = (except = null) => {
    navItems.forEach((item) => {
      if (item === except) return;
      item.classList.remove('is-open');
      item.querySelector(':scope > .main-nav-trigger')?.setAttribute('aria-expanded', 'false');
    });
  };

  const closeAllMenus = () => {
    closeActionMenus();
    closeNavMenus();
  };

  document.addEventListener('click', (event) => {
    if (!header.contains(event.target)) closeAllMenus();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeAllMenus();
  });

  const loadingMarkup = (label) => `
    <div class="header-live-state">
      <strong>${escapeHtml(label)}</strong>
      ${escapeHtml(text('loading_real_data'))}
    </div>
  `;

  const setLoading = () => {
    if (friendsList) friendsList.innerHTML = loadingMarkup(text('friend_requests'));
    if (messagesList) messagesList.innerHTML = loadingMarkup(text('messages'));
    if (notificationsList) notificationsList.innerHTML = loadingMarkup(text('notifications'));
    header.querySelectorAll('[data-header-badge]').forEach((badge) => {
      badge.textContent = '0';
      badge.classList.add('is-empty');
    });
  };

  const setBadge = (type, value) => {
    const count = Math.max(0, Number(value) || 0);
    const badge = header.querySelector(`[data-header-badge="${type}"]`);
    const label = header.querySelector(`[data-dropdown-count="${type}"]`);

    if (badge) {
      badge.textContent = count > 99 ? '99+' : String(count);
      badge.classList.toggle('is-empty', count === 0);
    }

    if (label) {
      label.textContent = text(type === 'friends' ? 'open_count' : 'unread_count', { count });
    }
  };

  const renderFriends = (items = []) => {
    if (!friendsList) return;
    if (!Array.isArray(items) || items.length === 0) {
      friendsList.innerHTML = `<div class="header-live-state"><strong>${escapeHtml(text('no_open_requests'))}</strong>${escapeHtml(text('requests_auto'))}</div>`;
      return;
    }

    friendsList.innerHTML = items.map((item) => `
      <article class="header-request-item" data-real-friend-request="${Number(item.id)}">
        <a href="${escapeHtml(item.profile_url)}" aria-label="${escapeHtml(item.name)}">
          <img alt="${escapeHtml(item.name)}" src="${escapeHtml(item.avatar)}">
        </a>
        <div>
          <strong>${escapeHtml(item.name)}</strong>
          <small>${escapeHtml(item.handle)} · ${escapeHtml(item.time)}</small>
        </div>
        <div class="header-request-actions">
          <button aria-label="${escapeHtml(text('accept_user', { name: item.name }))}" class="friend-accept" data-friend-action="accept" data-url="${escapeHtml(item.accept_url)}" type="button">
            <svg><use href="#i-check"></use></svg>
          </button>
          <button aria-label="${escapeHtml(text('decline_user', { name: item.name }))}" class="friend-decline" data-friend-action="decline" data-url="${escapeHtml(item.decline_url)}" type="button">
            <svg><use href="#i-x"></use></svg>
          </button>
        </div>
      </article>
    `).join('');
  };

  const renderMessages = (items = []) => {
    if (!messagesList) return;
    if (!Array.isArray(items) || items.length === 0) {
      messagesList.innerHTML = `<div class="header-live-state"><strong>${escapeHtml(text('no_conversations'))}</strong>${escapeHtml(text('messages_auto'))}</div>`;
      return;
    }

    messagesList.innerHTML = items.map((item) => {
      const conversationId = Math.max(0, Number(item.id) || 0);
      const showUrl = String(item.url || '');
      const chatTabUrl = String(item.chat_tab_url || (showUrl ? `${showUrl.replace(/\/$/, '')}/chat-tab` : ''));
      const unread = Math.max(0, Number(item.unread) || 0);

      return `
        <a class="header-message-item${unread > 0 ? ' unread' : ''}"
           href="${escapeHtml(showUrl)}"
           role="menuitem"
           data-hnt-chat-tab-open
           data-hnt-chat-conversation-id="${conversationId}"
           data-hnt-chat-tab-url="${escapeHtml(chatTabUrl)}">
          <img alt="${escapeHtml(item.title)}" src="${escapeHtml(item.avatar)}">
          <span>
            <strong>${escapeHtml(item.title)}${unread > 0 ? ` <em>${unread}</em>` : ''}</strong>
            <small>${escapeHtml(item.preview)}</small>
          </span>
          <time>${escapeHtml(item.time)}</time>
        </a>
      `;
    }).join('');
  };

  const renderNotifications = (items = []) => {
    if (!notificationsList) return;
    if (!Array.isArray(items) || items.length === 0) {
      notificationsList.innerHTML = `<div class="header-live-state"><strong>${escapeHtml(text('no_notifications'))}</strong>${escapeHtml(text('notifications_auto'))}</div>`;
      return;
    }

    notificationsList.innerHTML = items.map((item) => `
      <button class="header-notification-item${item.unread ? ' unread' : ''}" type="button" role="menuitem"
              data-notification-read-url="${escapeHtml(item.read_url)}"
              data-notification-action-url="${escapeHtml(item.action_url || '')}">
        <span class="header-notification-icon${item.unread ? ' yellow' : ''}"><svg><use href="#i-bell"></use></svg></span>
        <span>
          <strong>${escapeHtml(item.title)}</strong>
          <small>${escapeHtml(item.body || item.actor || '')}</small>
        </span>
        <time>${escapeHtml(item.time)}</time>
      </button>
    `).join('');
  };

  const updateProfile = (profile = {}) => {
    const summary = header.querySelector('.header-profile-summary');
    const avatar = summary?.querySelector('img');
    const name = summary?.querySelector('strong');
    const meta = summary?.querySelector('span');
    const stats = header.querySelectorAll('.header-profile-stats > span');

    if (avatar) {
      avatar.src = profile.avatar || avatar.src;
      avatar.alt = profile.name || 'HNT Hunter';
    }
    if (name) name.textContent = profile.name || 'HNT Hunter';
    if (meta) meta.textContent = `${profile.handle || '@hunter'} · ${text('level', { level: Math.max(1, Number(profile.level) || 1) })}`;

    const values = [
      [profile.rocks, text('rocks')],
      [profile.friends, text('friends')],
      [profile.posts, text('posts')],
    ];

    stats.forEach((stat, index) => {
      const strong = stat.querySelector('strong');
      const small = stat.querySelector('small');
      if (strong) strong.textContent = formatNumber(values[index]?.[0]);
      if (small) small.textContent = values[index]?.[1] || '';
    });
  };

  const replaceWithLink = (node, url) => {
    if (!node || !url) return;
    if (node.tagName === 'A') {
      node.href = url;
      return;
    }

    node.removeAttribute('data-toast');
    node.dataset.navigationUrl = url;
    if (node.dataset.navigationBound === '1') return;
    node.dataset.navigationBound = '1';
    node.addEventListener('click', (event) => {
      if (event.defaultPrevented || node.getAttribute('aria-disabled') === 'true') return;
      const target = node.dataset.navigationUrl;
      if (!target) return;
      if (event.ctrlKey || event.metaKey) {
        window.open(target, '_blank', 'noopener');
      } else {
        window.location.assign(target);
      }
    });
  };

  const wireMenuLinks = (links = {}) => {
    const navigation = links.navigation || {};
    header.querySelectorAll('.main-nav-menu-grid > button').forEach((button) => {
      const label = button.dataset.navigationLabel || button.querySelector('strong')?.textContent?.trim();
      if (label && navigation[label]) {
        replaceWithLink(button, navigation[label]);
      } else if (button.dataset.unavailable === '1') {
        button.removeAttribute('data-toast');
        button.setAttribute('aria-disabled', 'true');
      }
    });

    header.querySelectorAll('.main-nav-direct').forEach((item) => {
      const label = item.querySelector('span')?.textContent?.trim();
      if (label && navigation[label]) replaceWithLink(item, navigation[label]);
    });

    const settingsLinks = links.settings_menu || {};
    header.querySelectorAll('#settingsDropdown .header-menu-list > button').forEach((button) => {
      const label = button.dataset.navigationLabel || button.querySelector('strong')?.textContent?.trim();
      if (label && settingsLinks[label]) replaceWithLink(button, settingsLinks[label]);
    });

    const profileLinks = links.profile_menu || {};
    header.querySelectorAll('#profileDropdown .profile-menu-list > button').forEach((button) => {
      const label = button.dataset.navigationLabel || button.querySelector('strong')?.textContent?.trim();
      if (label && profileLinks[label]) replaceWithLink(button, profileLinks[label]);
    });

    const languageValue = header.querySelector('#settingsDropdown .header-menu-value');
    if (languageValue && links.language_label) languageValue.textContent = links.language_label;

    replaceWithLink(header.querySelector('#friendsDropdown .header-dropdown-footer'), links.friends);
    replaceWithLink(header.querySelector('#messagesDropdown .header-dropdown-footer'), links.messages);
    replaceWithLink(header.querySelector('#notificationsDropdown .header-dropdown-footer'), links.notifications);
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

    if (!response.ok) throw new Error(`Shared header request failed with ${response.status}`);
    return response.json();
  };

  const render = (payload) => {
    const headerPayload = payload || {};
    lastPayload = headerPayload;
    setBadge('friends', headerPayload.counts?.friends);
    setBadge('messages', headerPayload.counts?.messages);
    setBadge('notifications', headerPayload.counts?.notifications);
    renderFriends(headerPayload.friend_requests);
    renderMessages(headerPayload.messages);
    renderNotifications(headerPayload.notifications);
    updateProfile(headerPayload.profile);
    wireMenuLinks(headerPayload.links);

    if (markAllButton) {
      const unread = Number(headerPayload.counts?.notifications || 0);
      markAllButton.disabled = unread === 0;
      markAllButton.textContent = unread === 0 ? text('all_read') : text('mark_all_read');
    }
  };

  const load = async (force = false) => {
    if (!friendsList || !messagesList || !notificationsList) return null;
    if (loading) return loading;
    if (!force && lastPayload && Date.now() - lastLoadedAt < 20000) return lastPayload;

    loading = (async () => {
      const endpoint = header.dataset.hntHeaderEndpoint || window.HNT_DASHBOARD_HEADER_ENDPOINT || '/feed';
      const url = new URL(endpoint, window.location.origin);
      url.search = '';
      url.hash = '';
      url.searchParams.set('dashboard_header', '1');
      const payload = await requestJson(url.toString());
      render(payload.header || {});
      lastLoadedAt = Date.now();
      return payload.header || {};
    })();

    try {
      return await loading;
    } catch (error) {
      console.error('HNT shared header failed', error);
      if (friendsList) friendsList.innerHTML = `<div class="header-live-state"><strong>${escapeHtml(text('requests_unavailable'))}</strong>${escapeHtml(text('reload_page'))}</div>`;
      if (messagesList) messagesList.innerHTML = `<div class="header-live-state"><strong>${escapeHtml(text('messages_unavailable'))}</strong>${escapeHtml(text('reload_page'))}</div>`;
      if (notificationsList) notificationsList.innerHTML = `<div class="header-live-state"><strong>${escapeHtml(text('notifications_unavailable'))}</strong>${escapeHtml(text('reload_page'))}</div>`;
      return null;
    } finally {
      loading = null;
    }
  };

  header.addEventListener('click', async (event) => {
    const actionTrigger = event.target.closest('.header-dropdown-trigger');
    if (actionTrigger && header.contains(actionTrigger)) {
      event.preventDefault();
      event.stopImmediatePropagation();
      const menu = actionTrigger.closest('.header-action-menu');
      if (!menu) return;
      const willOpen = !menu.classList.contains('is-open');
      closeActionMenus(menu);
      closeNavMenus();
      menu.classList.toggle('is-open', willOpen);
      actionTrigger.setAttribute('aria-expanded', String(willOpen));
      if (willOpen && /friendsMenuTrigger|messagesMenuTrigger|notificationsMenuTrigger/.test(actionTrigger.id)) {
        window.setTimeout(() => load(true), 20);
      }
      return;
    }

    const navTrigger = event.target.closest('.main-nav-item > .main-nav-trigger');
    if (navTrigger && header.contains(navTrigger)) {
      event.preventDefault();
      event.stopImmediatePropagation();
      const item = navTrigger.closest('.main-nav-item');
      if (!item) return;
      const willOpen = !item.classList.contains('is-open');
      closeNavMenus(item);
      closeActionMenus();
      item.classList.toggle('is-open', willOpen);
      navTrigger.setAttribute('aria-expanded', String(willOpen));
      return;
    }

    const friendButton = event.target.closest('[data-friend-action]');
    if (friendButton && friendsList?.contains(friendButton)) {
      event.preventDefault();
      event.stopImmediatePropagation();
      const row = friendButton.closest('[data-real-friend-request]');
      row?.querySelectorAll('button').forEach((button) => { button.disabled = true; });
      try {
        const payload = await requestJson(friendButton.dataset.url, { method: 'POST' });
        row?.remove();
        const count = payload.friend_request_count ?? Math.max(0, Number(lastPayload?.counts?.friends || 0) - 1);
        setBadge('friends', count);
        if (lastPayload?.counts) lastPayload.counts.friends = Number(count || 0);
        if (!friendsList.querySelector('[data-real-friend-request]')) renderFriends([]);
      } catch (error) {
        console.error('Friend request action failed', error);
        row?.querySelectorAll('button').forEach((button) => { button.disabled = false; });
      }
      return;
    }

    const notificationItem = event.target.closest('[data-notification-read-url]');
    if (notificationItem && notificationsList?.contains(notificationItem)) {
      event.preventDefault();
      event.stopImmediatePropagation();
      notificationItem.disabled = true;
      try {
        const payload = await requestJson(notificationItem.dataset.notificationReadUrl, { method: 'POST' });
        notificationItem.classList.remove('unread');
        notificationItem.disabled = false;
        setBadge('notifications', payload.unread_count || 0);
        if (lastPayload?.counts) lastPayload.counts.notifications = Number(payload.unread_count || 0);
        const target = payload.action_url || notificationItem.dataset.notificationActionUrl;
        if (target) window.location.assign(target);
      } catch (error) {
        console.error('Notification action failed', error);
        notificationItem.disabled = false;
      }
      return;
    }

    if (markAllButton && event.target.closest('.header-mark-all') === markAllButton) {
      event.preventDefault();
      event.stopImmediatePropagation();
      const url = lastPayload?.links?.notifications_read_all;
      if (!url) return;
      markAllButton.disabled = true;
      try {
        await requestJson(url, { method: 'POST' });
        notificationsList?.querySelectorAll('.unread').forEach((item) => item.classList.remove('unread'));
        setBadge('notifications', 0);
        markAllButton.textContent = text('all_read');
        if (lastPayload?.counts) lastPayload.counts.notifications = 0;
      } catch (error) {
        console.error('Mark all notifications failed', error);
        markAllButton.disabled = false;
      }
      return;
    }

    const logoutButton = event.target.closest('.header-profile-logout');
    if (logoutButton && header.contains(logoutButton)) {
      event.preventDefault();
      event.stopImmediatePropagation();
      const form = logoutButton.closest('form');
      if (!form) return;
      logoutButton.disabled = true;
      form.submit();
    }
  }, true);

  header.querySelectorAll('.header-dropdown,.main-nav-dropdown').forEach((dropdown) => {
    dropdown.addEventListener('click', (event) => event.stopPropagation());
  });

  setLoading();
  load(true);
})();
