(() => {
  const root = document.querySelector('[data-ready-create-root]');
  const form = document.getElementById('readyCreateForm');
  if (!root || !form) return;

  const copy = JSON.parse(document.getElementById('readyCreateCopy')?.textContent || '{}');
  const text = (key, fallback = key) => String(copy[key] || fallback);
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const toast = document.getElementById('readyLobbyToast');
  const errorNode = document.getElementById('readyCreateError');
  const stateNode = document.getElementById('readyCreateState');
  const tabs = [...document.querySelectorAll('[data-ready-form-tab]')];
  const panels = [...document.querySelectorAll('[data-ready-form-panel]')];
  let mmr = 0;

  const value = (id) => document.getElementById(id)?.value.trim() || '';
  const radio = (name) => form.querySelector(`input[name="${name}"]:checked`)?.value || '';

  const showToast = (message, error = false) => {
    if (!toast || !message) return;
    toast.textContent = message;
    toast.classList.toggle('error', error);
    toast.classList.add('show');
    clearTimeout(showToast.timer);
    showToast.timer = setTimeout(() => toast.classList.remove('show'), 2400);
  };

  const openPanel = (name) => {
    const target = panels.some((panel) => panel.dataset.readyFormPanel === name) ? name : 'general';
    tabs.forEach((tab) => {
      const active = tab.dataset.readyFormTab === target;
      tab.classList.toggle('active', active);
      tab.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    panels.forEach((panel) => {
      const active = panel.dataset.readyFormPanel === target;
      panel.hidden = !active;
      panel.classList.toggle('active', active);
    });
    const selected = tabs.find((tab) => tab.dataset.readyFormTab === target);
    document.getElementById('readyFormPanelTitle').textContent = selected?.dataset.title || text('general');
    document.getElementById('readyCreateScroll')?.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const platformLabel = () => {
    const select = document.getElementById('readyPlatform');
    return select?.options[select.selectedIndex]?.textContent || '—';
  };

  const renderSquad = () => {
    const total = Number(radio('mode') === 'duo' ? 2 : 3);
    const avatar = document.querySelector('.ready-create-preview-hero img')?.src || '/assets/vikinger/img/default-avatar.svg';
    const host = document.querySelector('.ready-create-preview-hero header strong')?.textContent || 'Hunter';
    const cards = [`<article class="filled"><img src="${avatar}" alt=""><div><span>HUNTER 1</span><strong>${host}</strong><small>Host · ready</small></div><i><svg><use href="#i-check"></use></svg></i></article>`];
    for (let index = 2; index <= total; index += 1) {
      cards.push(`<article class="empty"><span><svg><use href="#i-plus"></use></svg></span><div><span>HUNTER ${index}</span><strong>Freier Platz</strong><small>Matching aktiv</small></div></article>`);
    }
    document.getElementById('readySlotPreview').innerHTML = cards.join('');
    document.getElementById('readyPreviewSquad').innerHTML = cards.join('');
    document.getElementById('readySlotHeadline').textContent = `1 / ${total}`;
  };

  const completion = () => {
    const scores = {
      general: [value('readyTitle'), value('readyNote')].filter(Boolean).length / 2,
      mode: [radio('mode'), value('readyPlatform'), value('readyRegion'), value('readyLanguage'), value('readyPlaystyle')].filter(Boolean).length / 5,
      squad: [value('readyMood'), value('readyHandle')].filter(Boolean).length / 2,
      contact: [value('readyLobbyCode'), value('readyDiscord'), value('readySteam'), value('readyPsn'), value('readyXbox')].filter(Boolean).length ? 1 : .5,
    };
    const percentages = Object.fromEntries(Object.entries(scores).map(([key, score]) => [key, Math.round(score * 100)]));
    Object.entries(percentages).forEach(([key, percent]) => {
      const node = document.querySelector(`[data-ready-tab-status="${key}"]`);
      if (node) node.textContent = `${percent}%`;
    });
    const total = Math.round(percentages.general * .30 + percentages.mode * .40 + percentages.squad * .20 + percentages.contact * .10);
    ['readyCompletionNav', 'readyCompletionText', 'readyCompletionFooter', 'readyPreviewCompletionText'].forEach((id) => {
      const node = document.getElementById(id);
      if (node) node.textContent = `${total}%`;
    });
    document.getElementById('readyCompletionBar').style.width = `${total}%`;
    document.getElementById('readyPreviewCompletionBar').style.width = `${total}%`;
    document.getElementById('readyCompletionHint').textContent = total >= 80 ? text('publish') : text('complete_hint');
    return total;
  };

  const updatePlatformFields = () => {
    const platform = value('readyPlatform');
    document.querySelectorAll('[data-platform-contact]').forEach((field) => {
      field.hidden = field.dataset.platformContact !== platform;
    });
  };

  const updatePreview = () => {
    renderSquad();
    updatePlatformFields();
    document.getElementById('readyPreviewTitle').textContent = value('readyTitle') || text('title_placeholder');
    document.getElementById('readyPreviewNote').textContent = value('readyNote') || text('note_placeholder');
    document.getElementById('readyPreviewMode').textContent = radio('mode') === 'duo' ? text('duo') : text('trio');
    document.getElementById('readyPreviewPlatform').textContent = platformLabel();
    document.getElementById('readyPreviewRegion').textContent = value('readyRegion') || '—';
    document.getElementById('readyPreviewLanguage').textContent = value('readyLanguage') || '—';
    document.getElementById('readyMatchPlatform').textContent = platformLabel();
    document.getElementById('readyMatchRegion').textContent = value('readyRegion') || '—';
    document.getElementById('readyMatchStyle').textContent = value('readyPlaystyle') || '—';
    document.getElementById('readyMatchMmr').textContent = mmr ? `${mmr} ★` : text('open');
    document.getElementById('readyTitleCount').textContent = value('readyTitle').length;
    document.getElementById('readyNoteCount').textContent = value('readyNote').length;
    const match = Math.min(96, 70 + [value('readyPlatform'), value('readyRegion'), value('readyLanguage'), value('readyPlaystyle'), value('readyMood')].filter(Boolean).length * 4 + (mmr ? 2 : 0));
    document.getElementById('readyPreviewMatch').textContent = `${match}%`;
    completion();
  };

  const markDirty = () => {
    stateNode.classList.add('dirty');
    stateNode.classList.remove('published');
    stateNode.querySelector('span').textContent = text('not_live');
  };

  const payload = () => {
    const title = value('readyTitle');
    const note = value('readyNote');
    const combined = `${title}${note ? `\n${note}` : ''}`.slice(0, 500);
    const data = {
      mode: radio('mode'), platform: value('readyPlatform'),
      region: value('readyRegion') || null, language: value('readyLanguage') || null,
      voice_required: document.getElementById('readyVoice').checked,
      playstyle: value('readyPlaystyle') || null, mood: value('readyMood') || null,
      note: combined, platform_handle: value('readyHandle') || null,
      lobby_code: value('readyLobbyCode') || null, discord_handle: value('readyDiscord') || null,
      steam_id: value('readySteam') || null, psn_id: value('readyPsn') || null,
      xbox_gamertag: value('readyXbox') || null, mmr_stars: mmr || null,
    };
    Object.keys(data).forEach((key) => { if (data[key] === null || data[key] === '') delete data[key]; });
    return data;
  };

  const reset = () => {
    form.reset(); mmr = 0;
    document.querySelectorAll('#readyMmrStars button').forEach((button) => button.classList.remove('active'));
    document.getElementById('readyMmrValue').textContent = text('open');
    errorNode.hidden = true;
    stateNode.classList.remove('dirty', 'published');
    stateNode.querySelector('span').textContent = text('not_live');
    openPanel('general'); updatePreview();
  };

  const submit = async (event) => {
    event.preventDefault(); errorNode.hidden = true;
    if (!value('readyTitle') || !radio('mode') || !value('readyPlatform') || completion() < 60) {
      errorNode.textContent = text('validation'); errorNode.hidden = false;
      openPanel(!value('readyTitle') ? 'general' : 'mode');
      showToast(text('validation'), true); return;
    }
    const buttons = [...form.querySelectorAll('[type="submit"]'), document.getElementById('readyPublishTop')];
    buttons.forEach((button) => { button.disabled = true; });
    const footerButton = form.querySelector('[type="submit"]');
    const original = footerButton.textContent; footerButton.textContent = text('saving');
    try {
      const response = await fetch(root.dataset.storeUrl, {
        method: 'POST', credentials: 'same-origin',
        headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify(payload()),
      });
      const result = await response.json().catch(() => ({}));
      if (!response.ok) {
        const validation = result?.errors ? Object.values(result.errors).flat()[0] : null;
        throw new Error(validation || result?.message || text('error'));
      }
      stateNode.classList.remove('dirty'); stateNode.classList.add('published');
      stateNode.querySelector('span').textContent = text('live');
      showToast(result.message || text('created'));
      const id = result?.data?.id;
      window.setTimeout(() => { window.location.href = id ? `${root.dataset.indexUrl}/${encodeURIComponent(id)}` : root.dataset.indexUrl; }, 500);
    } catch (error) {
      errorNode.textContent = error.message; errorNode.hidden = false; showToast(error.message, true);
    } finally {
      buttons.forEach((button) => { button.disabled = false; }); footerButton.textContent = original;
    }
  };

  tabs.forEach((tab) => tab.addEventListener('click', () => openPanel(tab.dataset.readyFormTab)));
  form.addEventListener('input', () => { updatePreview(); markDirty(); });
  form.addEventListener('change', () => { updatePreview(); markDirty(); });
  form.addEventListener('submit', submit);
  document.getElementById('readyPublishTop').addEventListener('click', () => form.requestSubmit());
  document.getElementById('readyDiscardTop').addEventListener('click', reset);
  document.querySelectorAll('[data-mmr]').forEach((button) => button.addEventListener('click', () => {
    mmr = Number(button.dataset.mmr || 0);
    document.querySelectorAll('#readyMmrStars button:not(.clear)').forEach((star, index) => star.classList.toggle('active', index < mmr));
    document.getElementById('readyMmrValue').textContent = mmr ? `${mmr} ★` : text('open');
    updatePreview(); markDirty();
  }));

  openPanel('general'); updatePreview();
})();
