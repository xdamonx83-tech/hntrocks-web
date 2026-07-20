(() => {
  const root = document.querySelector('[data-ready-lobby-root]');
  if (!root) return;

  const copy = JSON.parse(document.getElementById('readyLobbyCopy')?.textContent || '{}');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const text = (key, fallback = key) => String(copy[key] || fallback);
  const esc = (value = '') => String(value).replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char]);
  const endpoint = (template, value, token = '__LOBBY__') => String(template || '').replace(token, encodeURIComponent(String(value || '')));
  const number = (value) => new Intl.NumberFormat(document.documentElement.lang || 'de-DE').format(Number(value) || 0);
  const items = (payload) => Array.isArray(payload?.data) ? payload.data : [];

  const endpoints = {
    list: root.dataset.listUrl, mine: root.dataset.mineUrl, create: root.dataset.createUrl,
    show: root.dataset.showTemplate, join: root.dataset.joinTemplate,
    leave: root.dataset.leaveTemplate, close: root.dataset.closeTemplate,
    feedback: root.dataset.feedbackUrl, feedbackSubmit: root.dataset.feedbackSubmitTemplate,
    feedbackDismiss: root.dataset.feedbackDismissTemplate,
  };
  const state = { lobbies: [], mine: [], visible: [], selected: null, activeLobby: null, filter: 'all', feedbackRequests: [], loading: false };

  const radarNodes = root.querySelector('[data-radar-nodes]');
  const radarEmpty = root.querySelector('[data-radar-empty]');
  const radarSelection = root.querySelector('[data-radar-selection]');
  const lobbyList = root.querySelector('[data-lobby-list]');
  const bestMatch = root.querySelector('[data-best-match]');
  const hunterList = root.querySelector('[data-hunter-list]');
  const detailModal = document.querySelector('[data-detail-modal]');
  const feedbackModal = document.querySelector('[data-feedback-modal]');
  const detailContent = document.querySelector('[data-detail-content]');
  const detailTitle = document.querySelector('[data-detail-title]');
  const feedbackContent = document.querySelector('[data-feedback-content]');
  const feedbackTitle = document.querySelector('[data-feedback-title]');
  const feedbackButton = document.querySelector('[data-feedback-open]');
  const feedbackCount = document.querySelector('[data-feedback-count]');
  const toast = document.getElementById('readyLobbyToast');

  const requestJson = async (url, options = {}) => {
    const response = await fetch(url, {
      credentials: 'same-origin', cache: 'no-store', ...options,
      headers: {
        Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest',
        ...(options.method && options.method !== 'GET' ? { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf } : {}),
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

  const showToast = (message, error = false) => {
    if (!toast || !message) return;
    toast.textContent = message; toast.classList.toggle('error', error); toast.classList.add('show');
    clearTimeout(showToast.timer); showToast.timer = setTimeout(() => toast.classList.remove('show'), 2400);
  };
  const openModal = (modal) => { if (modal) { modal.hidden = false; document.body.classList.add('ready-lobby-modal-open'); } };
  const closeModal = (modal) => {
    if (!modal) return; modal.hidden = true;
    if ([detailModal, feedbackModal].every((item) => !item || item.hidden)) document.body.classList.remove('ready-lobby-modal-open');
  };

  const modeLabel = (mode) => mode === 'duo' ? text('duo') : text('trio');
  const statusLabel = (status) => text(status === 'closed' ? 'closed_status' : status, status || '—');
  const poolLabel = (lobby) => lobby.crossplay_pool === 'console' ? text('console') : 'PC';
  const contentFor = (lobby) => {
    const raw = String(lobby.note || '').trim();
    const lines = raw.split(/\r?\n/);
    if (lines.length > 1 && lines[0].length <= 80) return { title: lines.shift().trim(), note: lines.join('\n').trim() };
    const style = lobby.playstyle || lobby.mood;
    return { title: style ? `${style} · ${modeLabel(lobby.mode)}` : `${modeLabel(lobby.mode)} Ready Lobby`, note: raw };
  };
  const matchPercent = (lobby) => {
    if (lobby?.common_ground?.self) return 99;
    const score = Number(lobby?.common_ground?.score || 0), max = Number(lobby?.common_ground?.max_score || 0);
    return max ? Math.max(72, Math.min(96, Math.round(72 + (score / max) * 24))) : 72;
  };
  const remainingText = (lobby) => {
    const remaining = Math.max(0, new Date(lobby.expires_at || 0).getTime() - Date.now());
    const seconds = Math.floor(remaining / 1000);
    return `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;
  };
  const hash = (value) => { let result = 0; for (const char of String(value || '')) result = ((result << 5) - result + char.charCodeAt(0)) | 0; return Math.abs(result); };
  const coordinates = (lobby, index) => {
    const presets = [[24,35],[48,22],[67,47],[80,25],[37,68],[73,76],[17,72],[57,63],[87,58],[34,18]];
    const base = presets[index % presets.length], variance = hash(lobby.id) % 7;
    return [Math.min(90, base[0] + variance - 3), Math.min(82, base[1] + ((variance * 2) % 7) - 3)];
  };
  const filterMatches = (lobby) => state.filter === 'all'
    || ((state.filter === 'duo' || state.filter === 'trio') && lobby.mode === state.filter)
    || (state.filter === 'console' && lobby.crossplay_pool === 'console')
    || (state.filter === 'pc' && lobby.crossplay_pool === 'pc');

  const setCounts = () => {
    const all = state.lobbies;
    const hunters = all.reduce((sum, lobby) => sum + Number(lobby.slots_filled || 0), 0);
    const full = all.filter((lobby) => lobby.status === 'full').length;
    root.querySelector('[data-ready-hunter-count]').textContent = number(hunters);
    root.querySelector('[data-ready-lobby-count]').textContent = number(all.length);
    root.querySelector('[data-ready-full-count]').textContent = number(full);
    root.querySelector('[data-ready-hunter-side-count]').textContent = number(hunters);
    const counts = {
      all: all.length, duo: all.filter((lobby) => lobby.mode === 'duo').length,
      trio: all.filter((lobby) => lobby.mode === 'trio').length,
      console: all.filter((lobby) => lobby.crossplay_pool === 'console').length,
      pc: all.filter((lobby) => lobby.crossplay_pool === 'pc').length,
    };
    Object.entries(counts).forEach(([key, value]) => { const node = root.querySelector(`[data-filter-count="${key}"]`); if (node) node.textContent = number(value); });
  };

  const selectionMarkup = (lobby) => {
    const creator = lobby.creator || {}, content = contentFor(lobby);
    const action = lobby.viewer?.can_join ? text('join') : text('view');
    return `<header><img alt="" src="${esc(creator.avatar_url || '/assets/vikinger/img/default-avatar.svg')}"><div><span>${esc(creator.display_name || creator.username || 'Hunter')}</span><strong>${esc(content.title)}</strong></div><em>${matchPercent(lobby)}% Match</em></header><div><span>${esc(lobby.region || '—')} · ${esc(poolLabel(lobby))} · ${esc(lobby.language || '—')}</span><strong>${number(lobby.slots_filled)} / ${number(lobby.slots_total)}</strong><small>${esc(remainingText(lobby))}</small></div><button type="button" data-open-lobby="${esc(lobby.id)}">${esc(action)} <svg><use href="#i-arrow"></use></svg></button>`;
  };
  const selectLobby = (lobby) => {
    state.selected = lobby;
    root.querySelectorAll('.radar-node').forEach((node) => node.classList.toggle('selected', node.dataset.lobbyId === String(lobby.id)));
    if (radarSelection) { radarSelection.hidden = false; radarSelection.innerHTML = selectionMarkup(lobby); }
    renderBestMatch();
  };
  const renderRadar = () => {
    if (!radarNodes || !radarEmpty) return;
    if (!state.visible.length) {
      radarNodes.innerHTML = ''; radarSelection.hidden = true; radarEmpty.hidden = false;
      radarEmpty.innerHTML = `<strong>${esc(text('no_lobbies'))}</strong><span>${esc(text('no_lobbies_text'))}</span>`; return;
    }
    radarEmpty.hidden = true;
    radarNodes.innerHTML = state.visible.slice(0, 10).map((lobby, index) => {
      const creator = lobby.creator || {}, [x, y] = coordinates(lobby, index);
      const size = index === 0 ? 'large' : index < 3 ? 'medium' : index < 6 ? 'small' : 'tiny';
      return `<button class="radar-node ${size}" data-lobby-id="${esc(lobby.id)}" style="--x:${x}%;--y:${y}%" type="button">${creator.avatar_url ? `<img alt="" src="${esc(creator.avatar_url)}">` : ''}<span>${matchPercent(lobby)}%</span></button>`;
    }).join('');
    selectLobby(state.visible.find((lobby) => lobby.id === state.selected?.id) || state.visible[0]);
  };
  const renderList = () => {
    if (!lobbyList) return;
    if (!state.visible.length) {
      lobbyList.innerHTML = `<div class="ready-list-empty"><strong>${esc(text('no_lobbies'))}</strong><p>${esc(text('no_lobbies_text'))}</p><a href="${esc(endpoints.create)}">${esc(text('create'))}</a></div>`; return;
    }
    lobbyList.innerHTML = state.visible.map((lobby) => {
      const creator = lobby.creator || {}, content = contentFor(lobby), action = lobby.viewer?.can_join ? text('join') : text('view');
      return `<article data-lobby-row="${esc(lobby.id)}"><img alt="" src="${esc(creator.avatar_url || '/assets/vikinger/img/default-avatar.svg')}"><div><span>${esc(modeLabel(lobby.mode).toUpperCase())}</span><strong>${esc(content.title)}</strong><small>${esc(creator.display_name || creator.username || 'Hunter')} · ${esc(lobby.region || '—')} · ${esc(poolLabel(lobby))}${lobby.voice_required ? ` · ${esc(text('voice'))}` : ''}</small></div><div class="lobby-list-metric"><strong>${matchPercent(lobby)}%</strong><span>Match</span></div><div class="lobby-list-metric"><strong>${number(lobby.slots_filled)} / ${number(lobby.slots_total)}</strong><span>${esc(text('members'))}</span></div><div class="lobby-list-metric" data-countdown data-expires-at="${esc(lobby.expires_at || '')}"><strong>${esc(remainingText(lobby))}</strong><span>${esc(text('expires'))}</span></div><button type="button" data-open-lobby="${esc(lobby.id)}">${esc(action)}</button></article>`;
    }).join('');
  };
  const renderBestMatch = () => {
    if (!bestMatch) return;
    const lobby = state.selected || state.visible[0];
    if (!lobby) { bestMatch.innerHTML = `<div class="ready-side-empty">${esc(text('no_lobbies'))}</div>`; return; }
    const percent = matchPercent(lobby), checks = Array.isArray(lobby.common_ground?.items) ? lobby.common_ground.items.slice(0, 4) : [];
    bestMatch.innerHTML = `<div class="ready-match-ring" style="--match:${percent}"><div><strong>${percent}%</strong><span>${esc(text('very_good'))}</span></div></div><div class="match-checks">${checks.length ? checks.map((item) => `<article><i class="good"></i><div><strong>${esc(item.key.replace('_', ' '))}</strong><small>${esc(item.label)}</small></div><span>OK</span></article>`).join('') : `<article><i></i><div><strong>Matching</strong><small>${esc(poolLabel(lobby))} · ${esc(lobby.region || '—')}</small></div><span>${percent}%</span></article>`}</div>`;
  };
  const renderHunters = () => {
    if (!hunterList) return;
    const unique = new Map();
    state.lobbies.forEach((lobby) => (lobby.members || []).forEach((member) => { if (!unique.has(member.id)) unique.set(member.id, { ...member, lobby }); }));
    const hunters = [...unique.values()].slice(0, 6);
    hunterList.innerHTML = hunters.length ? hunters.map((hunter) => `<article><img alt="" src="${esc(hunter.avatar_url || '/assets/vikinger/img/default-avatar.svg')}"><div><strong>${esc(hunter.display_name || hunter.username || 'Hunter')}</strong><small>${esc(modeLabel(hunter.lobby.mode))} · ${esc(poolLabel(hunter.lobby))}${hunter.mmr_stars ? ` · ${hunter.mmr_stars} ★` : ''}</small></div><i></i><span>${esc(remainingText(hunter.lobby))}</span></article>`).join('') : `<div class="ready-side-empty">${esc(text('no_lobbies'))}</div>`;
  };
  const renderMyStatus = () => {
    const lobby = state.mine[0], label = root.querySelector('[data-my-status-label]'), copyNode = root.querySelector('[data-my-status-copy]'), link = root.querySelector('[data-my-status-link]');
    if (!label || !copyNode || !link) return;
    if (!lobby) {
      label.textContent = text('not_ready'); copyNode.textContent = text('status_text'); link.textContent = text('create_own'); link.href = endpoints.create; link.removeAttribute('data-open-lobby'); return;
    }
    label.textContent = statusLabel(lobby.status);
    copyNode.textContent = `${text('active_lobby')} ${modeLabel(lobby.mode)} · ${number(lobby.slots_filled)}/${number(lobby.slots_total)} · ${remainingText(lobby)}`;
    link.textContent = text('manage'); link.href = '#'; link.dataset.openLobby = lobby.id;
  };
  const applyFilter = () => {
    state.visible = state.lobbies.filter(filterMatches).sort((a, b) => matchPercent(b) - matchPercent(a));
    renderRadar(); renderList(); renderBestMatch();
  };
  const loading = () => {
    if (radarEmpty) { radarEmpty.hidden = false; radarEmpty.innerHTML = `<span class="ready-lobby-spinner"></span><strong>${esc(text('loading'))}</strong>`; }
    if (lobbyList) lobbyList.innerHTML = `<div class="ready-list-empty"><span class="ready-lobby-spinner"></span><strong>${esc(text('loading'))}</strong></div>`;
  };
  const loadLobbies = async ({ quiet = false } = {}) => {
    if (state.loading) return; state.loading = true; if (!quiet) loading();
    try {
      const [allPayload, minePayload] = await Promise.all([requestJson(endpoints.list), requestJson(endpoints.mine)]);
      state.lobbies = items(allPayload); state.mine = items(minePayload);
      setCounts(); renderHunters(); renderMyStatus(); applyFilter();
    } catch (error) {
      if (radarEmpty) { radarEmpty.hidden = false; radarEmpty.innerHTML = `<strong>${esc(text('load_error'))}</strong><span>${esc(error.message)}</span>`; }
      if (lobbyList) lobbyList.innerHTML = `<div class="ready-list-empty"><strong>${esc(text('load_error'))}</strong><p>${esc(error.message)}</p></div>`;
    } finally { state.loading = false; }
  };

  const slotMarkup = (lobby) => Array.from({ length: Number(lobby.slots_total) || 0 }, (_, index) => `<span class="ready-lobby-slot${index < Number(lobby.slots_filled) ? ' filled' : ''}"></span>`).join('');
  const memberMarkup = (member) => `<article class="ready-lobby-member"><img src="${esc(member.avatar_url || '/assets/vikinger/img/default-avatar.svg')}" alt=""><div><strong>${esc(member.display_name || member.username || 'Hunter')}</strong><small>${esc(member.username ? `@${member.username}` : '')} · ${esc(text(member.role, member.role || text('member')))}${member.mmr_stars ? ` · ${member.mmr_stars} ★` : ''}</small></div><span>${esc(member.hunter_number || '—')}</span></article>`;
  const contactMarkup = (contact) => {
    const values = [['Lobby Code', contact?.lobby_code],['Steam', contact?.steam_id],['PSN', contact?.psn_id],['Xbox', contact?.xbox_gamertag],['Discord', contact?.discord_handle]].filter(([, value]) => value);
    if (!values.length) return `<p class="ready-detail-note">${esc(text('contact_locked'))}</p>`;
    return `<div class="ready-lobby-contact-list">${values.map(([label, value]) => `<article><small>${esc(label)}</small><strong>${esc(value)}</strong><button type="button" data-copy-value="${esc(value)}">${esc(text('copy'))}</button></article>`).join('')}</div>`;
  };
  const joinMarkup = (lobby) => `<form class="ready-lobby-join-form" data-join-form data-lobby-id="${esc(lobby.id)}"><label><span>${esc(text('platform'))}</span><select name="platform" required><option value="pc"${root.dataset.defaultPlatform === 'pc' ? ' selected' : ''}>PC</option><option value="playstation"${root.dataset.defaultPlatform === 'playstation' ? ' selected' : ''}>PlayStation</option><option value="xbox"${root.dataset.defaultPlatform === 'xbox' ? ' selected' : ''}>Xbox</option></select></label><label><span>${esc(text('mmr'))}</span><select name="mmr_stars"><option value="">—</option>${Array.from({ length: 6 }, (_, i) => `<option value="${i + 1}">${i + 1} ★</option>`).join('')}</select></label><label class="wide"><span>${esc(text('handle'))}</span><input name="platform_handle" maxlength="100"></label><p data-join-error hidden></p><button type="submit">${esc(text('join_submit'))}</button></form>`;
  const renderDetail = (lobby) => {
    state.activeLobby = lobby;
    const creator = lobby.creator || {}, viewer = lobby.viewer || {}, content = contentFor(lobby);
    if (detailTitle) detailTitle.textContent = content.title;
    const actions = [];
    if (viewer.can_leave) actions.push(`<button type="button" data-lobby-action="leave">${esc(text('leave'))}</button>`);
    if (viewer.can_close) actions.push(`<button class="danger" type="button" data-lobby-action="close">${esc(text('close'))}</button>`);
    detailContent.innerHTML = `<section class="ready-detail-hero"><img src="${esc(creator.avatar_url || '/assets/vikinger/img/default-avatar.svg')}" alt=""><div><span>${esc(modeLabel(lobby.mode))} · ${esc(poolLabel(lobby))}</span><h3>${esc(content.title)}</h3><p>${esc(creator.display_name || creator.username || 'Hunter')} · ${esc(lobby.region || '—')} · ${esc(lobby.language || '—')}</p></div><em>${matchPercent(lobby)}% Match</em></section>${content.note ? `<p class="ready-detail-note">${esc(content.note)}</p>` : ''}<div class="ready-detail-meta"><span>${number(lobby.slots_filled)}/${number(lobby.slots_total)} Hunter</span><span>${esc(lobby.voice_required ? text('voice') : text('voice_optional'))}</span><span>${esc(remainingText(lobby))}</span></div><div class="ready-lobby-slots">${slotMarkup(lobby)}</div><section class="ready-detail-section"><h3>${esc(text('members'))}</h3><div class="ready-lobby-members">${(lobby.members || []).map(memberMarkup).join('')}</div></section><section class="ready-detail-section"><h3>${esc(text('contact'))}</h3>${contactMarkup(lobby.contact)}</section>${viewer.can_join ? joinMarkup(lobby) : ''}${actions.length ? `<div class="ready-detail-actions">${actions.join('')}</div>` : ''}`;
    openModal(detailModal);
  };
  const openLobby = async (id) => {
    if (!id) return;
    detailContent.innerHTML = `<div class="ready-list-empty"><span class="ready-lobby-spinner"></span><strong>${esc(text('loading'))}</strong></div>`; openModal(detailModal);
    try { renderDetail((await requestJson(endpoint(endpoints.show, id))).data); }
    catch (error) { detailContent.innerHTML = `<div class="ready-list-empty"><strong>${esc(text('load_error'))}</strong><p>${esc(error.message)}</p></div>`; }
  };
  const formObject = (form) => {
    const data = Object.fromEntries(new FormData(form).entries());
    if (data.mmr_stars) data.mmr_stars = Number(data.mmr_stars); else delete data.mmr_stars;
    Object.keys(data).forEach((key) => { if (data[key] === '') delete data[key]; }); return data;
  };
  const submitJoin = async (form) => {
    const button = form.querySelector('[type="submit"]'), errorNode = form.querySelector('[data-join-error]'), original = button.textContent;
    button.disabled = true; button.textContent = text('saving'); errorNode.hidden = true;
    try {
      const payload = await requestJson(endpoint(endpoints.join, form.dataset.lobbyId), { method: 'POST', body: JSON.stringify(formObject(form)) });
      showToast(payload.message || text('joined')); renderDetail(payload.data); await loadLobbies({ quiet: true });
    } catch (error) { errorNode.textContent = error.message; errorNode.hidden = false; }
    finally { button.disabled = false; button.textContent = original; }
  };
  const runAction = async (action) => {
    const lobby = state.activeLobby; if (!lobby) return;
    if (action === 'close' && !confirm(text('confirm_close'))) return;
    if (action === 'leave' && !confirm(text('confirm_leave'))) return;
    try {
      const payload = await requestJson(endpoint(action === 'close' ? endpoints.close : endpoints.leave, lobby.id), { method: 'POST', body: '{}' });
      showToast(payload.message || text(action === 'close' ? 'closed' : 'left')); closeModal(detailModal); await loadLobbies(); await loadFeedback();
    } catch (error) { showToast(error.message, true); }
  };

  const feedbackOption = (name, value) => `<label><input type="checkbox" name="${esc(name)}[]" value="${esc(value)}"><span>${esc(text(value, value))}</span></label>`;
  const renderFeedback = () => {
    const request = state.feedbackRequests[0];
    if (!request) { feedbackContent.innerHTML = `<div class="ready-list-empty"><strong>${esc(text('feedback_empty'))}</strong></div>`; return; }
    const user = request.target_user || {};
    if (feedbackTitle) feedbackTitle.textContent = `${text('feedback_for')} ${user.display_name || user.username || 'Hunter'}`;
    const positives = ['reliable','chill','teamplayer','good_communication','helpful','beginner_friendly','would_play_again'];
    const flags = ['no_show','left_early','not_again','uncomfortable'];
    feedbackContent.innerHTML = `<form class="ready-lobby-feedback-form" data-feedback-form data-request-id="${esc(request.id)}"><div class="ready-feedback-user"><img src="${esc(user.avatar_url || '/assets/vikinger/img/default-avatar.svg')}" alt=""><div><strong>${esc(user.display_name || user.username || 'Hunter')}</strong><small>${esc(user.username ? `@${user.username}` : '')}</small></div></div><section><h3>${esc(text('positive'))}</h3><div class="ready-feedback-options">${positives.map((value) => feedbackOption('positive_tags', value)).join('')}</div></section><section><h3>${esc(text('private'))}</h3><div class="ready-feedback-options">${flags.map((value) => feedbackOption('private_flags', value)).join('')}</div></section><section><h3>${esc(text('comment'))}</h3><textarea name="comment" maxlength="500"></textarea></section><p data-feedback-error hidden></p><footer><button type="button" data-feedback-dismiss="${esc(request.id)}">${esc(text('dismiss_feedback'))}</button><button class="primary" type="submit">${esc(text('submit_feedback'))}</button></footer></form>`;
  };
  const updateFeedbackButton = () => { const count = state.feedbackRequests.length; if (feedbackCount) feedbackCount.textContent = number(count); if (feedbackButton) feedbackButton.hidden = count === 0; };
  const loadFeedback = async () => { try { state.feedbackRequests = items(await requestJson(endpoints.feedback)); updateFeedbackButton(); } catch (error) { console.error('Ready Lobby feedback failed', error); } };
  const submitFeedback = async (form) => {
    const id = form.dataset.requestId, data = new FormData(form), errorNode = form.querySelector('[data-feedback-error]');
    try {
      await requestJson(endpoint(endpoints.feedbackSubmit, id, '__REQUEST__'), { method: 'POST', body: JSON.stringify({ positive_tags: data.getAll('positive_tags[]'), private_flags: data.getAll('private_flags[]'), comment: String(data.get('comment') || '').trim() || null }) });
      showToast(text('feedback_saved')); state.feedbackRequests.shift(); updateFeedbackButton(); renderFeedback(); if (!state.feedbackRequests.length) setTimeout(() => closeModal(feedbackModal), 450);
    } catch (error) { errorNode.textContent = error.message; errorNode.hidden = false; }
  };
  const dismissFeedback = async (id) => {
    try { await requestJson(endpoint(endpoints.feedbackDismiss, id, '__REQUEST__'), { method: 'POST', body: '{}' }); state.feedbackRequests = state.feedbackRequests.filter((item) => item.id !== id); updateFeedbackButton(); renderFeedback(); if (!state.feedbackRequests.length) closeModal(feedbackModal); }
    catch (error) { showToast(error.message, true); }
  };
  const updateCountdowns = () => {
    document.querySelectorAll('[data-countdown]').forEach((node) => {
      const remaining = Math.max(0, new Date(node.dataset.expiresAt || 0).getTime() - Date.now()), seconds = Math.floor(remaining / 1000), strong = node.querySelector('strong');
      if (strong) strong.textContent = `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;
    });
    if (state.selected && radarSelection && !radarSelection.hidden) radarSelection.innerHTML = selectionMarkup(state.selected);
    renderMyStatus();
  };

  root.addEventListener('click', (event) => {
    const filter = event.target.closest('[data-ready-filter]');
    if (filter) { state.filter = filter.dataset.readyFilter || 'all'; root.querySelectorAll('[data-ready-filter]').forEach((button) => button.classList.toggle('active', button === filter)); applyFilter(); return; }
    const node = event.target.closest('.radar-node');
    if (node) { const lobby = state.lobbies.find((item) => String(item.id) === node.dataset.lobbyId); if (lobby) selectLobby(lobby); return; }
    const open = event.target.closest('[data-open-lobby]');
    if (open) { event.preventDefault(); openLobby(open.dataset.openLobby); return; }
    if (event.target.closest('[data-refresh]')) { loadLobbies(); return; }
    if (event.target.closest('[data-open-best]') && state.selected) { openLobby(state.selected.id); return; }
    if (event.target.closest('[data-feedback-open]')) { renderFeedback(); openModal(feedbackModal); }
  });
  document.addEventListener('click', (event) => {
    const close = event.target.closest('[data-modal-close]'); if (close) closeModal(close.closest('.ready-lobby-modal'));
    const action = event.target.closest('[data-lobby-action]'); if (action) runAction(action.dataset.lobbyAction);
    const copyButton = event.target.closest('[data-copy-value]'); if (copyButton) navigator.clipboard?.writeText(copyButton.dataset.copyValue || '').then(() => showToast(text('copied')));
    const dismiss = event.target.closest('[data-feedback-dismiss]'); if (dismiss) dismissFeedback(dismiss.dataset.feedbackDismiss);
  });
  detailContent?.addEventListener('submit', (event) => { const form = event.target.closest('[data-join-form]'); if (!form) return; event.preventDefault(); submitJoin(form); });
  feedbackContent?.addEventListener('submit', (event) => { const form = event.target.closest('[data-feedback-form]'); if (!form) return; event.preventDefault(); submitFeedback(form); });

  let zoom = 1;
  root.querySelectorAll('[data-radar-zoom]').forEach((button) => button.addEventListener('click', () => { zoom = Math.max(.9, Math.min(1.12, zoom + (button.dataset.radarZoom === 'in' ? .04 : -.04))); document.getElementById('lobbyRadar')?.style.setProperty('--radar-scale', zoom); }));
  document.addEventListener('keydown', (event) => { if (event.key === 'Escape') [detailModal, feedbackModal].forEach((modal) => { if (modal && !modal.hidden) closeModal(modal); }); });

  loadLobbies(); loadFeedback(); setInterval(updateCountdowns, 1000); setInterval(() => loadLobbies({ quiet: true }), 20000);
  if (root.dataset.initialLobby) openLobby(root.dataset.initialLobby);
})();
