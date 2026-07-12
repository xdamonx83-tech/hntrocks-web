/* Real profile composer bridge. It intercepts the static prototype handlers in
   capture phase, stores through the real Laravel feed endpoint and reloads the
   profile directly onto the newly created post. */
(() => {
  const modal = document.getElementById('postComposerModal');
  const input = document.getElementById('postComposerInput');
  const counter = document.getElementById('postComposerCounter');
  const publish = document.getElementById('publishComposerPost');
  const publishLabel = publish?.querySelector('span');
  const audience = document.getElementById('composerAudience');
  const audienceLabel = audience?.querySelector('span');
  const attachment = document.getElementById('composerAttachment');
  const typePanel = document.getElementById('composerTypePanel');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

  if (!modal || !input || !publish || !window.fetch) return;

  const state = {
    visibility: 'public',
    files: [],
    objectUrls: [],
    busy: false,
  };

  const labels = {
    public: 'Öffentlich',
    followers: 'Freunde',
    private: 'Privat',
  };
  const visibilityOrder = ['public', 'followers', 'private'];

  const toast = (message) => {
    if (typeof window.showToast === 'function') window.showToast(message);
    else console.info(message);
  };

  const escapeHtml = (value = '') => String(value).replace(/[&<>"']/g, (character) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
  })[character]);

  const activeType = () => document.querySelector('[data-composer-type].active')?.dataset.composerType || 'Beitrag';

  const updateState = () => {
    if (counter) counter.textContent = `${input.value.length}/1000`;
    publish.disabled = state.busy || (!input.value.trim() && state.files.length === 0);
  };

  const clearObjectUrls = () => {
    state.objectUrls.forEach((url) => URL.revokeObjectURL(url));
    state.objectUrls = [];
  };

  const renderMedia = () => {
    if (!attachment) return;
    clearObjectUrls();

    if (!state.files.length) {
      attachment.hidden = true;
      attachment.innerHTML = '';
      updateState();
      return;
    }

    const items = state.files.map((file, index) => {
      const url = URL.createObjectURL(file);
      state.objectUrls.push(url);
      const preview = file.type.startsWith('video/')
        ? `<video muted playsinline preload="metadata"><source src="${escapeHtml(url)}" type="${escapeHtml(file.type)}"></video>`
        : `<img src="${escapeHtml(url)}" alt="${escapeHtml(file.name)}">`;

      return `<article class="real-composer-media-item" data-profile-media-index="${index}">
        ${preview}
        <button type="button" aria-label="Medium entfernen" data-profile-remove-media><svg><use href="#i-x"></use></svg></button>
      </article>`;
    }).join('');

    attachment.hidden = false;
    attachment.innerHTML = `<div class="real-composer-media-grid">${items}<div class="real-composer-media-note">Bis zu 12 Bilder oder Videos.</div></div>`;
    updateState();
  };

  const reset = () => {
    state.visibility = 'public';
    state.files = [];
    state.busy = false;
    clearObjectUrls();
    if (audienceLabel) audienceLabel.textContent = labels.public;
    if (attachment) {
      attachment.hidden = true;
      attachment.innerHTML = '';
    }
    fileInput.value = '';
    updateState();
  };

  const showQuestionPanel = () => {
    if (!typePanel) return;
    typePanel.innerHTML = `<div class="real-composer-poll">
      <span>COMMUNITY-FRAGE</span>
      <strong>Füge mindestens zwei Antwortmöglichkeiten hinzu.</strong>
      <input maxlength="180" data-profile-poll-option placeholder="Antwort 1">
      <input maxlength="180" data-profile-poll-option placeholder="Antwort 2">
      <input maxlength="180" data-profile-poll-option placeholder="Antwort 3 (optional)">
      <input maxlength="180" data-profile-poll-option placeholder="Antwort 4 (optional)">
    </div>`;
  };

  const requestJson = async (url, formData) => {
    const response = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
      body: formData,
    });

    let payload = null;
    try { payload = await response.json(); } catch (_) { payload = null; }
    if (!response.ok) {
      const validation = payload?.errors ? Object.values(payload.errors).flat().find(Boolean) : null;
      throw new Error(validation || payload?.message || `Request failed with ${response.status}`);
    }
    return payload || {};
  };

  const submit = async () => {
    if (state.busy) return;
    const type = activeType();

    if (type === 'LFG') {
      window.location.href = '/lfg/create';
      return;
    }
    if (type === 'Moment') {
      window.location.href = '/moments/create';
      return;
    }

    const body = input.value.trim();
    if (!body && !state.files.length) {
      toast('Schreibe etwas oder füge Medien hinzu');
      input.focus();
      return;
    }

    const formData = new FormData();
    formData.append('body', body);
    formData.append('visibility', state.visibility);
    formData.append('background_style', 'none');
    formData.append('feeling_key', 'none');
    state.files.forEach((file) => formData.append('media[]', file));

    if (type === 'Frage') {
      const options = [...typePanel.querySelectorAll('[data-profile-poll-option]')]
        .map((field) => field.value.trim())
        .filter(Boolean);
      if (options.length < 2) {
        toast('Eine Frage braucht mindestens zwei Antworten');
        typePanel.querySelector('[data-profile-poll-option]')?.focus();
        return;
      }
      formData.append('poll_question', body || 'Community-Frage');
      options.forEach((option) => formData.append('poll_options[]', option));
    }

    state.busy = true;
    publish.disabled = true;
    if (publishLabel) publishLabel.textContent = 'Wird veröffentlicht';

    try {
      const payload = await requestJson('/feed', formData);
      const postId = Number(payload.post_id || payload.id || 0);
      toast(payload.message || 'Post veröffentlicht');
      if (postId) history.replaceState(null, '', `${window.location.pathname}${window.location.search}#post-${postId}`);
      window.location.reload();
    } catch (error) {
      toast(error.message || 'Post konnte nicht gespeichert werden');
      state.busy = false;
      if (publishLabel) publishLabel.textContent = 'Veröffentlichen';
      updateState();
    }
  };

  const fileInput = document.createElement('input');
  fileInput.type = 'file';
  fileInput.accept = 'image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime';
  fileInput.multiple = true;
  fileInput.hidden = true;
  document.body.appendChild(fileInput);

  fileInput.addEventListener('change', () => {
    const incoming = [...(fileInput.files || [])];
    const room = Math.max(0, 12 - state.files.length);
    if (incoming.length > room) toast(`Maximal 12 Medien. ${room} weitere sind möglich.`);
    state.files.push(...incoming.slice(0, room));
    fileInput.value = '';
    renderMedia();
  });

  attachment?.addEventListener('click', (event) => {
    const remove = event.target instanceof Element ? event.target.closest('[data-profile-remove-media]') : null;
    if (!remove) return;
    const item = remove.closest('[data-profile-media-index]');
    const index = Number.parseInt(item?.dataset.profileMediaIndex || '-1', 10);
    if (index < 0) return;
    state.files.splice(index, 1);
    renderMedia();
  });

  input.addEventListener('input', updateState);

  document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;

    if (target.closest('#publishComposerPost')) {
      event.preventDefault();
      event.stopImmediatePropagation();
      submit();
      return;
    }

    if (target.closest('#composerMediaButton')) {
      event.preventDefault();
      event.stopImmediatePropagation();
      fileInput.click();
      return;
    }

    if (target.closest('#composerAudience')) {
      event.preventDefault();
      event.stopImmediatePropagation();
      const current = visibilityOrder.indexOf(state.visibility);
      state.visibility = visibilityOrder[(current + 1) % visibilityOrder.length];
      if (audienceLabel) audienceLabel.textContent = labels[state.visibility];
      return;
    }

    if (target.closest('#removeComposerAttachment')) {
      event.preventDefault();
      event.stopImmediatePropagation();
      state.files = [];
      renderMedia();
      return;
    }

    if (target.closest('#saveComposerDraft')) {
      event.preventDefault();
      event.stopImmediatePropagation();
      localStorage.setItem('hnt_profile_post_draft', input.value);
      toast('Entwurf lokal gespeichert');
      return;
    }

    const typeButton = target.closest('[data-composer-type]');
    if (typeButton) {
      window.setTimeout(() => {
        if (typeButton.dataset.composerType === 'Frage') showQuestionPanel();
      }, 0);
      return;
    }

    if (target.closest('#openPostComposer')) {
      window.setTimeout(() => {
        reset();
        const draft = localStorage.getItem('hnt_profile_post_draft');
        if (draft && !input.value) {
          input.value = draft;
          input.dispatchEvent(new Event('input', { bubbles: true }));
        }
      }, 0);
    }
  }, true);

  reset();
})();
