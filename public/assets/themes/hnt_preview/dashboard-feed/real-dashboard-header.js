/* Real header dropdowns, profile data and navigation for the HNT dashboard preview. */
(() => {
  if (!window.fetch) return;

  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const friendsList = document.querySelector('.header-request-list');
  const messagesList = document.querySelector('.header-message-list');
  const notificationsList = document.querySelector('.header-notification-list');
  const markAllButton = document.querySelector('.header-mark-all');

  if (!friendsList || !messagesList || !notificationsList) return;

  const i18n = window.HNT_PREVIEW_I18N?.header || {};
  const text = (key, replacements = {}) => {
    let value = String(i18n[key] || '');
    Object.entries(replacements).forEach(([name, replacement]) => {
      value = value.replaceAll(`:${name}`, String(replacement));
    });
    return value;
  };

  let lastPayload = null;
  let lastLoadedAt = 0;
  let loading = null;

  const escapeHtml = (value = '') => String(value).replace(/[&<>"']/g, (character) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  })[character]);

  const formatNumber = (value) => new Intl.NumberFormat(document.documentElement.lang || 'de-DE')
    .format(Math.max(0, Number(value) || 0));

  const addStyle = () => {
    if (document.getElementById('real-dashboard-header-style')) return;

    const style = document.createElement('style');
    style.id = 'real-dashboard-header-style';
    style.textContent = `
      .header-action-badge.is-empty{display:none}
      .header-dropdown .header-live-state{padding:18px;text-align:center;color:#918e85;font-size:12px}
      .header-dropdown .header-live-state strong{display:block;color:#2f2f2c;font-size:13px;margin-bottom:3px}
      .header-request-item > a{display:block;flex:0 0 auto}
      .header-request-item > a img{display:block}
      .header-message-item,.header-dropdown-footer{color:inherit;text-decoration:none}
      .header-notification-item[disabled],.header-request-actions button[disabled]{opacity:.5;pointer-events:none}
      .header-profile-logout.is-busy{opacity:.55;pointer-events:none}
      .main-nav-menu-grid > a,.header-menu-list > a{color:inherit;text-decoration:none}
      .main-nav-menu-grid > button[data-unavailable="1"]{opacity:.6;cursor:not-allowed}
      #community-hashtags{scroll-margin-top:96px}
    `;
    document.head.appendChild(style);
  };

  const loadingMarkup = (label) => `
    <div class="header-live-state">
      <strong>${escapeHtml(label)}</strong>
      ${escapeHtml(text('loading_real_data'))}
    </div>
  `;

  const setLoading = () => {
    friendsList.innerHTML = loadingMarkup(text('friend_requests'));
    messagesList.innerHTML = loadingMarkup(text('messages'));
    notificationsList.innerHTML = loadingMarkup(text('notifications'));
    document.querySelectorAll('[data-header-badge]').forEach((badge) => {
      badge.textContent = '0';
      badge.classList.add('is-empty');
    });
  };

  const setBadge = (type, value) => {
    const count = Math.max(0, Number(value) || 0);
    const badge = document.querySelector(`[data-header-badge="${type}"]`);
    const label = document.querySelector(`[data-dropdown-count="${type}"]`);

    if (badge) {
      badge.textContent = String(count);
      badge.classList.toggle('is-empty', count === 0);
    }

    if (label) {
      label.textContent = text(type === 'friends' ? 'open_count' : 'unread_count', { count });
    }
  };

  const renderFriends = (items = []) => {
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
          <button aria-label="${escapeHtml(text('accept_user', { name: item.name }))}" class="friend-accept" data-friend-action="accept" data-url="${escapeHtml(item.accept_url)}">
            <svg><use href="#i-check"></use></svg>
          </button>
          <button aria-label="${escapeHtml(text('decline_user', { name: item.name }))}" class="friend-decline" data-friend-action="decline" data-url="${escapeHtml(item.decline_url)}">
            <svg><use href="#i-x"></use></svg>
          </button>
        </div>
      </article>
    `).join('');
  };

  const renderMessages = (items = []) => {
    if (!Array.isArray(items) || items.length === 0) {
      messagesList.innerHTML = `<div class="header-live-state"><strong>${escapeHtml(text('no_conversations'))}</strong>${escapeHtml(text('messages_auto'))}</div>`;
      return;
    }

    messagesList.innerHTML = items.map((item) => {
      const conversationId = Math.max(0, Number(item.id) || 0);
      const showUrl = String(item.url || '');
      const chatTabUrl = String(item.chat_tab_url || (showUrl ? `${showUrl.replace(/\/$/, '')}/chat-tab` : ''));

      return `
        <a class="header-message-item${Number(item.unread) > 0 ? ' unread' : ''}"
           href="${escapeHtml(showUrl)}"
           role="menuitem"
           data-hnt-chat-tab-open
           data-hnt-chat-conversation-id="${conversationId}"
           data-hnt-chat-tab-url="${escapeHtml(chatTabUrl)}">
          <img alt="${escapeHtml(item.title)}" src="${escapeHtml(item.avatar)}">
          <span>
            <strong>${escapeHtml(item.title)}${Number(item.unread) > 0 ? ` <em>${Number(item.unread)}</em>` : ''}</strong>
            <small>${escapeHtml(item.preview)}</small>
          </span>
          <time>${escapeHtml(item.time)}</time>
        </a>
      `;
    }).join('');
  };

  const renderNotifications = (items = []) => {
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
    const summary = document.querySelector('.header-profile-summary');
    const avatar = summary?.querySelector('img');
    const name = summary?.querySelector('strong');
    const meta = summary?.querySelector('span');
    const stats = document.querySelectorAll('.header-profile-stats > span');

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
    if (!node || !url) return node;

    if (node.tagName === 'A') {
      node.href = url;
      return node;
    }

    node.removeAttribute('data-toast');
    node.dataset.navigationUrl = url;

    if (node.dataset.navigationBound !== '1') {
      node.dataset.navigationBound = '1';
      node.addEventListener('click', (event) => {
        if (event.defaultPrevented || node.getAttribute('aria-disabled') === 'true') return;

        const target = node.dataset.navigationUrl;
        if (!target) return;

        if (event.ctrlKey || event.metaKey) {
          window.open(target, '_blank', 'noopener');
          return;
        }

        window.location.assign(target);
      });
    }

    return node;
  };

  const wireMenuLinks = (links = {}) => {
    const navigation = links.navigation || {};
    document.querySelectorAll('.main-nav-menu-grid > button').forEach((button) => {
      const label = button.dataset.navigationLabel || button.querySelector('strong')?.textContent?.trim();
      if (label && navigation[label]) {
        replaceWithLink(button, navigation[label]);
      } else if (button.dataset.unavailable === '1') {
        button.removeAttribute('data-toast');
        button.setAttribute('aria-disabled', 'true');
        button.addEventListener('click', (event) => event.preventDefault());
      }
    });

    document.querySelectorAll('.main-nav-direct').forEach((button) => {
      const label = button.querySelector('span')?.textContent?.trim();
      if (label && navigation[label]) replaceWithLink(button, navigation[label]);
    });

    const settingsLinks = links.settings_menu || {};
    document.querySelectorAll('#settingsDropdown .header-menu-list > button').forEach((button) => {
      const label = button.dataset.navigationLabel || button.querySelector('strong')?.textContent?.trim();
      if (label && settingsLinks[label]) replaceWithLink(button, settingsLinks[label]);
    });

    const profileLinks = links.profile_menu || {};
    document.querySelectorAll('#profileDropdown .profile-menu-list > button').forEach((button) => {
      const label = button.dataset.navigationLabel || button.querySelector('strong')?.textContent?.trim();
      if (label && profileLinks[label]) replaceWithLink(button, profileLinks[label]);
    });

    const languageValue = document.querySelector('#settingsDropdown .header-menu-value');
    if (languageValue && links.language_label) languageValue.textContent = links.language_label;

    const hashtagBlock = document.querySelector('.composition-trending');
    if (hashtagBlock) hashtagBlock.id = 'community-hashtags';

    replaceWithLink(document.querySelector('#friendsDropdown .header-dropdown-footer'), links.friends);
    replaceWithLink(document.querySelector('#messagesDropdown .header-dropdown-footer'), links.messages);
    replaceWithLink(document.querySelector('#notificationsDropdown .header-dropdown-footer'), links.notifications);

    const settingsTriggerText = document.querySelector('#settingsMenuTrigger span');
    if (settingsTriggerText) settingsTriggerText.textContent = text('settings');
  };

  const requestJson = async (url, options = {}) => {
    const response = await fetch(url, {
      credentials: 'same-origin',
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

  const render = (header) => {
    lastPayload = header;
    setBadge('friends', header.counts?.friends);
    setBadge('messages', header.counts?.messages);
    setBadge('notifications', header.counts?.notifications);
    renderFriends(header.friend_requests);
    renderMessages(header.messages);
    renderNotifications(header.notifications);
    updateProfile(header.profile);
    wireMenuLinks(header.links);

    if (markAllButton) {
      markAllButton.disabled = Number(header.counts?.notifications || 0) === 0;
      markAllButton.textContent = Number(header.counts?.notifications || 0) === 0 ? text('all_read') : text('mark_all_read');
    }
  };

  const load = async (force = false) => {
    if (loading) return loading;
    if (!force && lastPayload && Date.now() - lastLoadedAt < 20000) return lastPayload;

    loading = (async () => {
      const url = new URL(window.HNT_DASHBOARD_HEADER_ENDPOINT || window.location.href, window.location.origin);
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
      console.error('HNT dashboard header failed', error);
      friendsList.innerHTML = `<div class="header-live-state"><strong>${escapeHtml(text('requests_unavailable'))}</strong>${escapeHtml(text('reload_page'))}</div>`;
      messagesList.innerHTML = `<div class="header-live-state"><strong>${escapeHtml(text('messages_unavailable'))}</strong>${escapeHtml(text('reload_page'))}</div>`;
      notificationsList.innerHTML = `<div class="header-live-state"><strong>${escapeHtml(text('notifications_unavailable'))}</strong>${escapeHtml(text('reload_page'))}</div>`;
      return null;
    } finally {
      loading = null;
    }
  };

  friendsList.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-friend-action]');
    if (!button) return;
    event.preventDefault();
    event.stopPropagation();

    const row = button.closest('[data-real-friend-request]');
    row?.querySelectorAll('button').forEach((item) => { item.disabled = true; });

    try {
      const payload = await requestJson(button.dataset.url, { method: 'POST' });
      row?.remove();
      setBadge('friends', payload.friend_request_count ?? Math.max(0, Number(lastPayload?.counts?.friends || 0) - 1));
      if (!friendsList.querySelector('[data-real-friend-request]')) renderFriends([]);
      if (lastPayload?.counts) lastPayload.counts.friends = Number(payload.friend_request_count || 0);
    } catch (error) {
      console.error('Friend request action failed', error);
      row?.querySelectorAll('button').forEach((item) => { item.disabled = false; });
    }
  });

  notificationsList.addEventListener('click', async (event) => {
    const item = event.target.closest('[data-notification-read-url]');
    if (!item) return;
    event.preventDefault();
    item.disabled = true;

    try {
      const payload = await requestJson(item.dataset.notificationReadUrl, { method: 'POST' });
      item.classList.remove('unread');
      item.disabled = false;
      setBadge('notifications', payload.unread_count || 0);
      if (lastPayload?.counts) lastPayload.counts.notifications = Number(payload.unread_count || 0);
      const target = payload.action_url || item.dataset.notificationActionUrl;
      if (target) window.location.assign(target);
    } catch (error) {
      console.error('Notification read failed', error);
      item.disabled = false;
    }
  });

  markAllButton?.addEventListener('click', async (event) => {
    event.preventDefault();
    event.stopImmediatePropagation();
    if (!lastPayload?.links?.notifications_read_all) return;
    markAllButton.disabled = true;

    try {
      await requestJson(lastPayload.links.notifications_read_all, { method: 'POST' });
      notificationsList.querySelectorAll('.unread').forEach((item) => item.classList.remove('unread'));
      setBadge('notifications', 0);
      markAllButton.textContent = text('all_read');
      if (lastPayload?.counts) lastPayload.counts.notifications = 0;
    } catch (error) {
      console.error('Mark all notifications failed', error);
      markAllButton.disabled = false;
    }
  }, true);

  const logoutButton = document.querySelector('.header-profile-logout');
  logoutButton?.addEventListener('click', (event) => {
    event.preventDefault();
    event.stopImmediatePropagation();
    const url = lastPayload?.links?.logout;
    if (!url) return;

    logoutButton.classList.add('is-busy');
    const form = document.createElement('form');
    form.method = 'post';
    form.action = url;
    form.innerHTML = `<input type="hidden" name="_token" value="${escapeHtml(csrf)}">`;
    document.body.appendChild(form);
    form.submit();
  }, true);

  document.querySelectorAll('#friendsMenuTrigger,#messagesMenuTrigger,#notificationsMenuTrigger').forEach((trigger) => {
    trigger.addEventListener('click', () => window.setTimeout(() => load(true), 30));
  });

  addStyle();
  setLoading();
  load(true);
})();