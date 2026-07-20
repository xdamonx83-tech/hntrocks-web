(() => {
  const root = document.querySelector('[data-ready-lobby-root]');
  if (!root) return;

  const copyNode = document.getElementById('readyLobbyCopy');
  const copy = copyNode ? JSON.parse(copyNode.textContent || '{}') : {};
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const list = root.querySelector('[data-lobby-list]');
  const myStrip = root.querySelector('[data-my-strip]');
  const allCount = root.querySelector('[data-all-count]');
  const mineCount = root.querySelector('[data-mine-count]');
  const feedbackButton = root.querySelector('[data-feedback-open]');
  const feedbackCount = root.querySelector('[data-feedback-count]');
  const createModal = document.querySelector('[data-create-modal]');
  const detailModal = document.querySelector('[data-detail-modal]');
  const feedbackModal = document.querySelector('[data-feedback-modal]');
  const createForm = document.querySelector('[data-create-form]');
  const createError = document.querySelector('[data-create-error]');
  const detailContent = document.querySelector('[data-detail-content]');
  const detailTitle = document.querySelector('[data-detail-title]');
  const feedbackContent = document.querySelector('[data-feedback-content]');
  const feedbackTitle = document.querySelector('[data-feedback-title]');
  const toast = document.getElementById('readyLobbyToast');

  const endpoints = {
    list: root.dataset.listUrl,
    mine: root.dataset.mineUrl,
    store: root.dataset.storeUrl,
    page: root.dataset.pageTemplate,
    show: root.dataset.showTemplate,
    join: root.dataset.joinTemplate,
    leave: root.dataset.leaveTemplate,
    close: root.dataset.closeTemplate,
    feedback: root.dataset.feedbackUrl,
    feedbackSubmit: root.dataset.feedbackSubmitTemplate,
    feedbackDismiss: root.dataset.feedbackDismissTemplate,
  };

  const state = {
    scope: 'all',
    lobbies: [],
    mine: [],
    activeLobby: null,
    feedbackRequests: [],
    loading: false,
  };

  const text = (key, fallback = key) => String(copy[key] || fallback);
  const escapeHtml = (value = '') => String(value).replace(/[&<>"']/g, (character) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
  })[character]);
  const endpoint = (template, value, token = '__LOBBY__') => String(template || '').replace(token, encodeURIComponent(String(value || '')));
  const formatNumber = (value) => new Intl.NumberFormat(document.documentElement.lang || 'de-DE').format(Number(value) || 0);
  const normalizeData = (payload) => Array.isArray(payload?.data) ? payload.data : [];
  const platformLabel = (platform) => text(platform, String(platform || '—'));
  const modeLabel = (mode) => text(mode, String(mode || '—'));
  const statusLabel = (status) => text(status === 'closed' ? 'closed_status' : status, String(status || '—'));

  const showToast = (message, error = false) => {
    if (!toast || !message) return;
    toast.textContent = message;
    toast.classList.toggle('error', error);
    toast.classList.add('show');
    window.clearTimeout(showToast.timer);
    showToast.timer = window.setTimeout(() => toast.classList.remove('show'), 2200);
  };

  const openModal = (modal) => {
    if (!modal) return;
    modal.hidden = false;
    document.body.classList.add('ready-lobby-modal-open');
  };

  const closeModal = (modal) => {
    if (!modal) return;
    modal.hidden = true;
    if (![createModal, detailModal, feedbackModal].some((candidate) => candidate && !candidate.hidden)) {
      document.body.classList.remove('ready-lobby-modal-open');
    }
  };

  const requestJson = async (url, options = {}) => {
    const response = await fetch(url, {
      credentials: 'same-origin',
      cache: 'no-store',
      ...options,
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(options.method && options.method !== 'GET' ? {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
        } : {}),
        ...(options.headers || {}),
      },
    });

    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
      const validation = payload?.errors ? Object.values(payload.errors).flat()[0] : null;
      throw new Error(validation || payload?.message || text('unknown_error'));
    }
    return payload;
  };

  const currentFilters = () => {
    const params = new URLSearchParams();
    root.querySelectorAll('[data-filter]').forEach((field) => {
      const value = field.type === 'checkbox' ? (field.checked ? '1' : '') : String(field.value || '').trim();
      if (value) params.set(field.dataset.filter, value);
    });
    return params;
  };

  const countdownText = (lobby) => {
    const expiresAt = new Date(lobby.expires_at || 0).getTime();
    const remaining = Math.max(0, expiresAt - Date.now());
    const totalSeconds = Math.floor(remaining / 1000);
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;
    const label = lobby.status === 'full' ? text('full_expires') : text('expires');
    return `${label} ${minutes}:${String(seconds).padStart(2, '0')}`;
  };

  const slotMarkup = (lobby) => Array.from({ length: Number(lobby.slots_total) || 0 }, (_, index) =>
    `<span class="ready-lobby-slot${index < Number(lobby.slots_filled) ? ' filled' : ''}"></span>`
  ).join('');

  const lobbyCard = (lobby) => {
    const creator = lobby.creator || {};
    const viewer = lobby.viewer || {};
    const tags = [
      modeLabel(lobby.mode),
      platformLabel(lobby.platform),
      lobby.region,
      lobby.language,
      lobby.voice_required ? text('voice') : text('voice_optional'),
      lobby.playstyle,
      lobby.mood,
      creator.mentor_hunter ? text('mentor') : null,
    ].filter(Boolean);
    const memberNames = (lobby.members || []).slice(0, 3).map((member) => escapeHtml(member.display_name || member.username || 'Hunter')).join(', ');
    const buttonLabel = viewer.is_member ? text('details') : (viewer.can_join ? text('join') : text('details'));

    return `
      <article class="ready-lobby-card${viewer.is_member ? ' is-mine' : ''}" data-lobby-id="${escapeHtml(lobby.id)}">
        <div class="ready-lobby-card-head">
          <div class="ready-lobby-host">
            <img src="${escapeHtml(creator.avatar_url || '/assets/vikinger/img/default-avatar.svg')}" alt="">
            <div>
              <strong>${escapeHtml(creator.display_name || creator.username || 'HNT Hunter')}</strong>
              <small>${escapeHtml(creator.username ? `@${creator.username}` : '')}${memberNames ? ` · ${memberNames}` : ''}</small>
            </div>
          </div>
          <span class="ready-lobby-status ${escapeHtml(lobby.status)}">${escapeHtml(statusLabel(lobby.status))}</span>
        </div>
        <div class="ready-lobby-meta">
          <span><i class="ph ph-users-three"></i><strong>${formatNumber(lobby.slots_filled)}/${formatNumber(lobby.slots_total)}</strong></span>
          <span><i class="ph ph-game-controller"></i>${escapeHtml(modeLabel(lobby.mode))}</span>
          <span><i class="ph ph-monitor"></i>${escapeHtml(platformLabel(lobby.platform))}</span>
          ${lobby.region ? `<span><i class="ph ph-globe"></i>${escapeHtml(lobby.region)}</span>` : ''}
        </div>
        <div class="ready-lobby-slots">${slotMarkup(lobby)}</div>
        <p class="ready-lobby-note">${escapeHtml(lobby.note || `${formatNumber(lobby.missing_slots)} ${text('open_slots')}`)}</p>
        <div class="ready-lobby-tags">${tags.map((tag) => `<span>${escapeHtml(tag)}</span>`).join('')}</div>
        <div class="ready-lobby-card-foot">
          <div class="ready-lobby-countdown" data-countdown data-expires-at="${escapeHtml(lobby.expires_at || '')}" data-status="${escapeHtml(lobby.status)}">
            <span>${escapeHtml(lobby.status === 'full' ? text('full_expires') : text('expires'))}</span>
            <strong>${escapeHtml(countdownText(lobby).replace(`${lobby.status === 'full' ? text('full_expires') : text('expires')} `, ''))}</strong>
          </div>
          <button class="ready-lobby-action${viewer.can_join ? ' primary' : ''}" type="button" data-open-lobby="${escapeHtml(lobby.id)}">${escapeHtml(buttonLabel)}</button>
        </div>
      </article>
    `;
  };

  const renderList = () => {
    if (!list) return;
    const items = state.scope === 'mine' ? state.mine : state.lobbies;
    if (!items.length) {
      list.innerHTML = `
        <div class="ready-lobby-state">
          <i class="ph ph-users-three" style="font-size:38px;color:var(--accent-primary)"></i>
          <strong>${escapeHtml(text('no_lobbies'))}</strong>
          <p>${escapeHtml(text('no_lobbies_text'))}</p>
          <button class="ready-lobby-primary" type="button" data-create-open>${escapeHtml(text('create'))}</button>
        </div>`;
      return;
    }
    list.innerHTML = items.map(lobbyCard).join('');
    updateCountdowns();
  };

  const renderMyStrip = () => {
    if (!myStrip) return;
    const lobby = state.mine[0];
    if (!lobby) {
      myStrip.hidden = true;
      myStrip.innerHTML = '';
      return;
    }
    myStrip.hidden = false;
    myStrip.innerHTML = `
      <div><strong>${escapeHtml(text('mine'))}</strong><p>${escapeHtml(modeLabel(lobby.mode))} · ${formatNumber(lobby.slots_filled)}/${formatNumber(lobby.slots_total)} · ${escapeHtml(countdownText(lobby))}</p></div>
      <button class="ready-lobby-action" type="button" data-open-lobby="${escapeHtml(lobby.id)}">${escapeHtml(text('details'))}</button>`;
  };

  const loadingState = () => {
    if (!list) return;
    list.innerHTML = `<div class="ready-lobby-state"><span class="ready-lobby-spinner"></span><strong>${escapeHtml(text('loading'))}</strong></div>`;
  };

  const errorState = (message) => {
    if (!list) return;
    list.innerHTML = `<div class="ready-lobby-state"><i class="ph ph-warning-circle" style="font-size:38px;color:var(--accent-danger)"></i><strong>${escapeHtml(text('load_error'))}</strong><p>${escapeHtml(message || '')}</p><button class="ready-lobby-secondary" type="button" data-refresh>${escapeHtml(text('retry'))}</button></div>`;
  };

  const loadLobbies = async ({ quiet = false } = {}) => {
    if (state.loading) return;
    state.loading = true;
    if (!quiet) loadingState();
    try {
      const params = currentFilters();
      const [allPayload, minePayload] = await Promise.all([
        requestJson(`${endpoints.list}${params.toString() ? `?${params}` : ''}`),
        requestJson(endpoints.mine),
      ]);
      state.lobbies = normalizeData(allPayload);
      state.mine = normalizeData(minePayload);
      if (allCount) allCount.textContent = formatNumber(allPayload?.meta?.total ?? state.lobbies.length);
      if (mineCount) mineCount.textContent = formatNumber(minePayload?.meta?.total ?? state.mine.length);
      renderMyStrip();
      renderList();
    } catch (error) {
      errorState(error.message);
    } finally {
      state.loading = false;
    }
  };

  const memberMarkup = (member) => `
    <article class="ready-lobby-member">
      <img src="${escapeHtml(member.avatar_url || '/assets/vikinger/img/default-avatar.svg')}" alt="">
      <div class="ready-lobby-member-copy">
        <strong>${escapeHtml(member.display_name || member.username || 'Hunter')}</strong>
        <small>${escapeHtml(member.username ? `@${member.username}` : '')} · ${escapeHtml(text(member.role, member.role || text('member')))}${member.mmr_stars ? ` · ${member.mmr_stars} ★` : ''}</small>
      </div>
      <span class="ready-lobby-hunter-number" title="${escapeHtml(text('hunter_number'))}">${escapeHtml(member.hunter_number || '—')}</span>
    </article>`;

  const contactMarkup = (contact) => {
    const entries = [
      [text('lobby_code'), contact?.lobby_code],
      [text('steam'), contact?.steam_id],
      [text('psn'), contact?.psn_id],
      [text('gamertag'), contact?.xbox_gamertag],
      [text('discord'), contact?.discord_handle],
    ].filter(([, value]) => value);
    if (!entries.length) return `<p class="ready-lobby-note">${escapeHtml(text('contact_locked'))}</p>`;
    return `<div class="ready-lobby-contact-list">${entries.map(([label, value]) => `
      <article><small>${escapeHtml(label)}</small><strong>${escapeHtml(value)}</strong><button class="ready-lobby-action" type="button" data-copy-value="${escapeHtml(value)}">${escapeHtml(text('copy'))}</button></article>`).join('')}</div>`;
  };

  const joinFormMarkup = (lobby) => `
    <form class="ready-lobby-join-form" data-join-form data-lobby-id="${escapeHtml(lobby.id)}">
      <div class="ready-lobby-form-grid">
        <label><span>${escapeHtml(text('platform'))}</span><select name="platform" required>
          <option value="pc"${root.dataset.defaultPlatform === 'pc' ? ' selected' : ''}>PC</option>
          <option value="playstation"${root.dataset.defaultPlatform === 'playstation' ? ' selected' : ''}>PlayStation</option>
          <option value="xbox"${root.dataset.defaultPlatform === 'xbox' ? ' selected' : ''}>Xbox</option>
        </select></label>
        <label><span>${escapeHtml(text('mmr'))}</span><select name="mmr_stars"><option value="">—</option>${Array.from({ length: 6 }, (_, index) => `<option value="${index + 1}">${index + 1} ★</option>`).join('')}</select></label>
        <label class="ready-lobby-form-wide"><span>${escapeHtml(text('handle'))}</span><input name="platform_handle" maxlength="100"></label>
      </div>
      <p class="ready-lobby-form-error" data-join-error hidden></p>
      <footer><button class="ready-lobby-primary" type="submit">${escapeHtml(text('join_submit'))}</button></footer>
    </form>`;

  const renderDetail = (lobby) => {
    state.activeLobby = lobby;
    const creator = lobby.creator || {};
    const viewer = lobby.viewer || {};
    if (detailTitle) detailTitle.textContent = `${modeLabel(lobby.mode)} · ${creator.display_name || creator.username || text('title')}`;
    const tags = [platformLabel(lobby.platform), lobby.region, lobby.language, lobby.playstyle, lobby.mood, lobby.voice_required ? text('voice') : text('voice_optional')].filter(Boolean);
    const actions = [];
    if (viewer.can_leave) actions.push(`<button class="ready-lobby-action" type="button" data-lobby-action="leave">${escapeHtml(text('leave'))}</button>`);
    if (viewer.can_close) actions.push(`<button class="ready-lobby-primary" type="button" data-lobby-action="close">${escapeHtml(text('close'))}</button>`);

    detailContent.innerHTML = `
      <div class="ready-lobby-detail-hero">
        <div class="ready-lobby-detail-host">
          <img src="${escapeHtml(creator.avatar_url || '/assets/vikinger/img/default-avatar.svg')}" alt="">
          <div><h3>${escapeHtml(creator.display_name || creator.username || 'HNT Hunter')}</h3><p>${escapeHtml(creator.username ? `@${creator.username}` : '')} · ${escapeHtml(statusLabel(lobby.status))}</p></div>
        </div>
        <span class="ready-lobby-status ${escapeHtml(lobby.status)}">${escapeHtml(statusLabel(lobby.status))}</span>
      </div>
      <div class="ready-lobby-detail-section">
        <div class="ready-lobby-meta"><span><strong>${formatNumber(lobby.slots_filled)}/${formatNumber(lobby.slots_total)}</strong></span>${tags.map((tag) => `<span>${escapeHtml(tag)}</span>`).join('')}</div>
        <div class="ready-lobby-slots" style="margin-top:12px">${slotMarkup(lobby)}</div>
        ${lobby.note ? `<p class="ready-lobby-note" style="margin-top:12px">${escapeHtml(lobby.note)}</p>` : ''}
      </div>
      <div class="ready-lobby-detail-section"><h3>${escapeHtml(text('members'))}</h3><div class="ready-lobby-members">${(lobby.members || []).map(memberMarkup).join('')}</div></div>
      <div class="ready-lobby-detail-section"><h3>${escapeHtml(text('contact'))}</h3>${contactMarkup(lobby.contact)}</div>
      ${viewer.can_join ? joinFormMarkup(lobby) : ''}
      ${actions.length ? `<div class="ready-lobby-detail-section ready-lobby-detail-actions">${actions.join('')}</div>` : ''}`;
    openModal(detailModal);
  };

  const openLobby = async (id) => {
    if (!id) return;
    if (detailContent) detailContent.innerHTML = `<div class="ready-lobby-state"><span class="ready-lobby-spinner"></span><strong>${escapeHtml(text('loading'))}</strong></div>`;
    openModal(detailModal);
    try {
      const payload = await requestJson(endpoint(endpoints.show, id));
      renderDetail(payload.data);
    } catch (error) {
      detailContent.innerHTML = `<div class="ready-lobby-state"><strong>${escapeHtml(text('load_error'))}</strong><p>${escapeHtml(error.message)}</p></div>`;
    }
  };

  const objectFromForm = (form) => {
    const data = Object.fromEntries(new FormData(form).entries());
    form.querySelectorAll('input[type="checkbox"]').forEach((field) => {
      data[field.name] = field.checked;
    });
    ['mmr_stars'].forEach((name) => {
      if (data[name]) data[name] = Number(data[name]);
      else delete data[name];
    });
    Object.keys(data).forEach((key) => {
      if (data[key] === '') delete data[key];
    });
    return data;
  };

  const submitCreate = async (event) => {
    event.preventDefault();
    const button = createForm.querySelector('[type="submit"]');
    const original = button.textContent;
    button.disabled = true;
    button.textContent = text('saving');
    createError.hidden = true;
    try {
      const payload = await requestJson(endpoints.store, { method: 'POST', body: JSON.stringify(objectFromForm(createForm)) });
      closeModal(createModal);
      createForm.reset();
      showToast(payload.message || text('created'));
      await loadLobbies();
      if (payload.data?.id) await openLobby(payload.data.id);
    } catch (error) {
      createError.textContent = error.message;
      createError.hidden = false;
    } finally {
      button.disabled = false;
      button.textContent = original;
    }
  };

  const submitJoin = async (form) => {
    const id = form.dataset.lobbyId;
    const button = form.querySelector('[type="submit"]');
    const errorNode = form.querySelector('[data-join-error]');
    const original = button.textContent;
    button.disabled = true;
    button.textContent = text('saving');
    errorNode.hidden = true;
    try {
      const payload = await requestJson(endpoint(endpoints.join, id), { method: 'POST', body: JSON.stringify(objectFromForm(form)) });
      showToast(payload.message || text('joined'));
      renderDetail(payload.data);
      await loadLobbies({ quiet: true });
    } catch (error) {
      errorNode.textContent = error.message;
      errorNode.hidden = false;
    } finally {
      button.disabled = false;
      button.textContent = original;
    }
  };

  const runLobbyAction = async (action) => {
    const lobby = state.activeLobby;
    if (!lobby) return;
    if (action === 'close' && !window.confirm(text('confirm_close'))) return;
    if (action === 'leave' && !window.confirm(text('confirm_leave'))) return;
    const url = endpoint(action === 'close' ? endpoints.close : endpoints.leave, lobby.id);
    try {
      const payload = await requestJson(url, { method: 'POST', body: '{}' });
      showToast(payload.message || text(action === 'close' ? 'closed' : 'left'));
      closeModal(detailModal);
      state.activeLobby = null;
      await loadLobbies();
      await loadFeedback();
    } catch (error) {
      showToast(error.message, true);
    }
  };

  const feedbackOption = (name, value) => `<label><input type="checkbox" name="${escapeHtml(name)}[]" value="${escapeHtml(value)}"><span>${escapeHtml(text(value, value))}</span></label>`;

  const renderFeedback = () => {
    const request = state.feedbackRequests[0];
    if (!request) {
      feedbackContent.innerHTML = `<div class="ready-lobby-state"><strong>${escapeHtml(text('feedback_empty'))}</strong></div>`;
      if (feedbackTitle) feedbackTitle.textContent = text('feedback');
      return;
    }
    const user = request.target_user || {};
    if (feedbackTitle) feedbackTitle.textContent = `${text('feedback_for')} ${user.display_name || user.username || 'Hunter'}`;
    const positives = ['reliable', 'chill', 'teamplayer', 'good_communication', 'helpful', 'beginner_friendly', 'would_play_again'];
    const privateFlags = ['no_show', 'left_early', 'not_again', 'uncomfortable'];
    feedbackContent.innerHTML = `
      <form class="ready-lobby-feedback-form" data-feedback-form data-request-id="${escapeHtml(request.id)}">
        <div class="ready-lobby-feedback-user"><img src="${escapeHtml(user.avatar_url || '/assets/vikinger/img/default-avatar.svg')}" alt=""><div><strong>${escapeHtml(user.display_name || user.username || 'Hunter')}</strong><small>${escapeHtml(user.username ? `@${user.username}` : '')}</small></div></div>
        <section class="ready-lobby-feedback-group"><h3>${escapeHtml(text('positive'))}</h3><div class="ready-lobby-feedback-options">${positives.map((value) => feedbackOption('positive_tags', value)).join('')}</div></section>
        <section class="ready-lobby-feedback-group"><h3>${escapeHtml(text('private'))}</h3><div class="ready-lobby-feedback-options">${privateFlags.map((value) => feedbackOption('private_flags', value)).join('')}</div></section>
        <section class="ready-lobby-feedback-group"><h3>${escapeHtml(text('comment'))}</h3><textarea name="comment" maxlength="500"></textarea></section>
        <p class="ready-lobby-form-error" data-feedback-error hidden></p>
        <footer><button class="ready-lobby-secondary" type="button" data-feedback-dismiss="${escapeHtml(request.id)}">${escapeHtml(text('dismiss_feedback'))}</button><button class="ready-lobby-primary" type="submit">${escapeHtml(text('submit_feedback'))}</button></footer>
      </form>`;
  };

  const updateFeedbackButton = () => {
    const count = state.feedbackRequests.length;
    if (feedbackCount) feedbackCount.textContent = formatNumber(count);
    if (feedbackButton) feedbackButton.hidden = count === 0;
  };

  const loadFeedback = async () => {
    try {
      const payload = await requestJson(endpoints.feedback);
      state.feedbackRequests = normalizeData(payload);
      updateFeedbackButton();
      if (!feedbackModal.hidden) renderFeedback();
    } catch (error) {
      console.error('Ready Lobby feedback failed', error);
    }
  };

  const submitFeedback = async (form) => {
    const id = form.dataset.requestId;
    const button = form.querySelector('[type="submit"]');
    const errorNode = form.querySelector('[data-feedback-error]');
    const formData = new FormData(form);
    const payload = {
      positive_tags: formData.getAll('positive_tags[]'),
      private_flags: formData.getAll('private_flags[]'),
      comment: String(formData.get('comment') || '').trim() || null,
    };
    button.disabled = true;
    errorNode.hidden = true;
    try {
      await requestJson(endpoint(endpoints.feedbackSubmit, id, '__REQUEST__'), { method: 'POST', body: JSON.stringify(payload) });
      showToast(text('feedback_saved'));
      state.feedbackRequests.shift();
      updateFeedbackButton();
      renderFeedback();
      if (!state.feedbackRequests.length) window.setTimeout(() => closeModal(feedbackModal), 500);
    } catch (error) {
      errorNode.textContent = error.message;
      errorNode.hidden = false;
    } finally {
      button.disabled = false;
    }
  };

  const dismissFeedback = async (id) => {
    try {
      await requestJson(endpoint(endpoints.feedbackDismiss, id, '__REQUEST__'), { method: 'POST', body: '{}' });
      state.feedbackRequests = state.feedbackRequests.filter((item) => item.id !== id);
      updateFeedbackButton();
      renderFeedback();
      if (!state.feedbackRequests.length) closeModal(feedbackModal);
    } catch (error) {
      showToast(error.message, true);
    }
  };

  const updateCountdowns = () => {
    document.querySelectorAll('[data-countdown]').forEach((node) => {
      const expiresAt = new Date(node.dataset.expiresAt || 0).getTime();
      const remaining = Math.max(0, expiresAt - Date.now());
      const secondsTotal = Math.floor(remaining / 1000);
      const minutes = Math.floor(secondsTotal / 60);
      const seconds = secondsTotal % 60;
      const strong = node.querySelector('strong');
      if (strong) strong.textContent = `${minutes}:${String(seconds).padStart(2, '0')}`;
    });
  };

  const updatePlatformContactFields = () => {
    const platform = createForm?.querySelector('[name="platform"]')?.value || 'pc';
    createForm?.querySelectorAll('[data-platform-contact]').forEach((field) => {
      field.hidden = field.dataset.platformContact !== platform;
    });
  };

  document.addEventListener('click', (event) => {
    const createOpen = event.target.closest('[data-create-open]');
    if (createOpen) {
      event.preventDefault();
      const platform = createForm?.querySelector('[name="platform"]');
      if (platform) platform.value = root.dataset.defaultPlatform || 'pc';
      const region = createForm?.querySelector('[name="region"]');
      if (region && !region.value) region.value = root.dataset.defaultRegion || '';
      const language = createForm?.querySelector('[name="language"]');
      if (language && !language.value) language.value = root.dataset.defaultLanguage || '';
      updatePlatformContactFields();
      openModal(createModal);
      return;
    }

    const modalClose = event.target.closest('[data-modal-close]');
    if (modalClose) {
      closeModal(modalClose.closest('.ready-lobby-modal'));
      return;
    }

    const open = event.target.closest('[data-open-lobby]');
    if (open) {
      openLobby(open.dataset.openLobby);
      return;
    }

    const refresh = event.target.closest('[data-refresh]');
    if (refresh) {
      loadLobbies();
      return;
    }

    const action = event.target.closest('[data-lobby-action]');
    if (action) {
      runLobbyAction(action.dataset.lobbyAction);
      return;
    }

    const copyButton = event.target.closest('[data-copy-value]');
    if (copyButton) {
      navigator.clipboard?.writeText(copyButton.dataset.copyValue || '').then(() => showToast(text('copied')));
      return;
    }

    if (event.target.closest('[data-feedback-open]')) {
      renderFeedback();
      openModal(feedbackModal);
      return;
    }

    const dismiss = event.target.closest('[data-feedback-dismiss]');
    if (dismiss) dismissFeedback(dismiss.dataset.feedbackDismiss);
  });

  root.querySelectorAll('[data-lobby-scope]').forEach((button) => {
    button.addEventListener('click', () => {
      state.scope = button.dataset.lobbyScope || 'all';
      root.querySelectorAll('[data-lobby-scope]').forEach((candidate) => {
        const active = candidate === button;
        candidate.classList.toggle('active', active);
        candidate.setAttribute('aria-selected', active ? 'true' : 'false');
      });
      renderList();
    });
  });

  let filterTimer = null;
  root.querySelectorAll('[data-filter]').forEach((field) => {
    field.addEventListener(field.tagName === 'INPUT' && field.type !== 'checkbox' ? 'input' : 'change', () => {
      window.clearTimeout(filterTimer);
      filterTimer = window.setTimeout(() => loadLobbies(), field.tagName === 'INPUT' && field.type !== 'checkbox' ? 350 : 0);
    });
  });

  createForm?.addEventListener('submit', submitCreate);
  createForm?.querySelector('[name="platform"]')?.addEventListener('change', updatePlatformContactFields);
  detailContent?.addEventListener('submit', (event) => {
    const form = event.target.closest('[data-join-form]');
    if (!form) return;
    event.preventDefault();
    submitJoin(form);
  });
  feedbackContent?.addEventListener('submit', (event) => {
    const form = event.target.closest('[data-feedback-form]');
    if (!form) return;
    event.preventDefault();
    submitFeedback(form);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    [createModal, detailModal, feedbackModal].forEach((modal) => {
      if (modal && !modal.hidden) closeModal(modal);
    });
  });

  updatePlatformContactFields();
  loadLobbies();
  loadFeedback();
  window.setInterval(updateCountdowns, 1000);
  window.setInterval(() => loadLobbies({ quiet: true }), 20000);

  if (root.dataset.initialLobby) {
    openLobby(root.dataset.initialLobby);
  }
})();
