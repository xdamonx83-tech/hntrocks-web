/* Live create/edit/report actions for the isolated HNT dashboard preview. */
(() => {
  const modal = document.getElementById('postComposerModal');
  const input = document.getElementById('postComposerInput');
  const counter = document.getElementById('postComposerCounter');
  const publishButton = document.getElementById('publishComposerPost');
  const publishLabel = publishButton?.querySelector('span');
  const modalTitle = document.getElementById('postComposerTitle');
  const audienceButton = document.getElementById('composerAudience');
  const audienceLabel = audienceButton?.querySelector('span');
  const attachment = document.getElementById('composerAttachment');
  const typePanel = document.getElementById('composerTypePanel');
  const openButton = document.getElementById('openPostComposer');
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const previewEndpoint = `${window.location.pathname}?data=1`;

  if (!modal || !input || !publishButton || !window.fetch) return;

  const state = {
    editPost: null,
    visibility: 'public',
    files: [],
    existingMedia: [],
    removedMediaUrls: new Set(),
    objectUrls: [],
    busy: false,
  };

  const visibilityLabels = {
    public: 'Öffentlich',
    followers: 'Freunde',
    private: 'Privat',
  };
  const visibilityOrder = ['public', 'followers', 'private'];

  const toast = (message) => {
    if (typeof showToast === 'function') showToast(message);
    else console.info(message);
  };

  const escapeHtml = (value = '') => String(value).replace(/[&<>"']/g, (character) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
  })[character]);

  const requestJson = async (url, { method = 'GET', json = null, formData = null } = {}) => {
    const headers = {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
    };
    const options = { method, credentials: 'same-origin', headers };

    if (formData) options.body = formData;
    else if (json !== null) {
      headers['Content-Type'] = 'application/json';
      options.body = JSON.stringify(json);
    }

    const response = await fetch(url, options);
    let payload = null;
    try { payload = await response.json(); } catch (_) { payload = null; }

    if (!response.ok) {
      const validation = payload?.errors ? Object.values(payload.errors).flat().find(Boolean) : null;
      throw new Error(validation || payload?.message || `Request failed with ${response.status}`);
    }

    return payload || {};
  };

  const fileInput = document.createElement('input');
  fileInput.type = 'file';
  fileInput.accept = 'image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime';
  fileInput.multiple = true;
  fileInput.hidden = true;
  document.body.appendChild(fileInput);

  const clearObjectUrls = () => {
    state.objectUrls.forEach((url) => URL.revokeObjectURL(url));
    state.objectUrls = [];
  };

  const activeType = () => document.querySelector('[data-composer-type].active')?.dataset.composerType || 'Beitrag';

  const setAudience = (visibility) => {
    state.visibility = visibilityLabels[visibility] ? visibility : 'public';
    if (audienceLabel) audienceLabel.textContent = visibilityLabels[state.visibility];
  };

  const updatePublishState = () => {
    const hasContent = input.value.trim().length > 0 || state.files.length > 0 || state.existingMedia.length > state.removedMediaUrls.size;
    publishButton.disabled = state.busy || !hasContent;
    if (counter) counter.textContent = `${input.value.length}/1000`;
  };

  const renderMedia = () => {
    if (!attachment) return;

    clearObjectUrls();
    const keptExisting = state.existingMedia.filter((item) => !state.removedMediaUrls.has(item.url));
    const items = [];

    keptExisting.forEach((item) => {
      const media = item.type === 'video'
        ? `<video muted playsinline preload="metadata"><source src="${escapeHtml(item.url)}" type="${escapeHtml(item.mime || 'video/mp4')}"></video>`
        : `<img src="${escapeHtml(item.url)}" alt="${escapeHtml(item.alt || 'Bestehendes Medium')}">`;

      items.push(`
        <article class="real-composer-media-item" data-existing-url="${escapeHtml(item.url)}">
          ${media}
          <button type="button" aria-label="Medium entfernen" data-remove-existing><svg><use href="#i-x"></use></svg></button>
        </article>
      `);
    });

    state.files.forEach((file, index) => {
      const objectUrl = URL.createObjectURL(file);
      state.objectUrls.push(objectUrl);
      const media = file.type.startsWith('video/')
        ? `<video muted playsinline preload="metadata"><source src="${escapeHtml(objectUrl)}" type="${escapeHtml(file.type)}"></video>`
        : file.type.startsWith('image/')
          ? `<img src="${escapeHtml(objectUrl)}" alt="${escapeHtml(file.name)}">`
          : `<div class="real-composer-media-file"><strong>${escapeHtml(file.name)}</strong><small>${Math.ceil(file.size / 1024)} KB</small></div>`;

      items.push(`
        <article class="real-composer-media-item" data-new-index="${index}">
          ${media}
          <button type="button" aria-label="Medium entfernen" data-remove-new><svg><use href="#i-x"></use></svg></button>
        </article>
      `);
    });

    if (!items.length) {
      attachment.hidden = true;
      attachment.innerHTML = '';
      updatePublishState();
      return;
    }

    attachment.hidden = false;
    attachment.innerHTML = `
      <div class="real-composer-media-grid">
        ${items.join('')}
        <div class="real-composer-media-note">Bis zu 12 Bilder oder Videos. Bestehende Medien bleiben erhalten, solange du sie nicht entfernst.</div>
      </div>
    `;
    updatePublishState();
  };

  const resetComposerState = () => {
    state.editPost = null;
    state.files = [];
    state.existingMedia = [];
    state.removedMediaUrls.clear();
    state.busy = false;
    clearObjectUrls();
    setAudience('public');
    modal.querySelector('.post-composer-modal')?.classList.remove('is-editing');
    if (modalTitle) modalTitle.textContent = 'Post erstellen';
    if (publishLabel) publishLabel.textContent = 'Veröffentlichen';
    if (attachment) {
      attachment.hidden = true;
      attachment.innerHTML = '';
    }
    fileInput.value = '';
    updatePublishState();
  };

  const showQuestionPanel = () => {
    if (!typePanel) return;
    typePanel.innerHTML = `
      <div class="real-composer-poll">
        <span>COMMUNITY-FRAGE</span>
        <strong>Füge mindestens zwei Antwortmöglichkeiten hinzu.</strong>
        <input maxlength="180" data-poll-option placeholder="Antwort 1">
        <input maxlength="180" data-poll-option placeholder="Antwort 2">
        <input maxlength="180" data-poll-option placeholder="Antwort 3 (optional)">
        <input maxlength="180" data-poll-option placeholder="Antwort 4 (optional)">
      </div>
    `;
  };

  const fetchPost = async (postId) => {
    const payload = await requestJson(`${previewEndpoint}&post_id=${encodeURIComponent(String(postId))}`);
    return payload.post || null;
  };

  const openEdit = async (postId) => {
    try {
      const post = await fetchPost(postId);
      if (!post?.viewer?.can_edit) throw new Error('Dieser Beitrag kann nicht bearbeitet werden');

      state.editPost = post;
      state.files = [];
      state.existingMedia = Array.isArray(post.media) ? post.media : [];
      state.removedMediaUrls.clear();
      setAudience(post.visibility === 'followers' || post.visibility === 'private' ? post.visibility : 'public');

      input.value = post.body || '';
      input.dispatchEvent(new Event('input', { bubbles: true }));
      document.querySelectorAll('[data-composer-type]').forEach((button) => {
        button.classList.toggle('active', button.dataset.composerType === 'Beitrag');
      });
      if (typePanel) typePanel.innerHTML = '<div class="composer-type-copy"><span>BEITRAG BEARBEITEN</span><strong>Text, Sichtbarkeit und Medien dieses Beitrags anpassen.</strong></div>';
      modal.querySelector('.post-composer-modal')?.classList.add('is-editing');
      if (modalTitle) modalTitle.textContent = 'Post bearbeiten';
      if (publishLabel) publishLabel.textContent = 'Änderungen speichern';
      renderMedia();

      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('composer-open');
      window.setTimeout(() => input.focus(), 80);
    } catch (error) {
      toast(error.message || 'Beitrag konnte nicht geladen werden');
    }
  };

  const setBusy = (busy, label = null) => {
    state.busy = busy;
    publishButton.disabled = busy;
    if (publishLabel) {
      publishLabel.classList.toggle('real-composer-publish-state', busy);
      publishLabel.textContent = busy ? (label || 'Wird gespeichert') : (state.editPost ? 'Änderungen speichern' : 'Veröffentlichen');
    }
    updatePublishState();
  };

  const submitComposer = async () => {
    if (state.busy) return;
    const type = activeType();

    if (!state.editPost && type === 'LFG') {
      window.location.href = '/lfg/create';
      return;
    }
    if (!state.editPost && type === 'Moment') {
      window.location.href = '/moments/create';
      return;
    }

    const body = input.value.trim();
    const keptMediaCount = state.existingMedia.filter((item) => !state.removedMediaUrls.has(item.url)).length;
    if (!body && !state.files.length && !keptMediaCount) {
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
    state.removedMediaUrls.forEach((url) => formData.append('media_remove_urls[]', url));

    if (!state.editPost && type === 'Frage') {
      const options = [...typePanel.querySelectorAll('[data-poll-option]')]
        .map((field) => field.value.trim())
        .filter(Boolean);
      if (options.length < 2) {
        toast('Eine Frage braucht mindestens zwei Antworten');
        typePanel.querySelector('[data-poll-option]')?.focus();
        return;
      }
      formData.append('poll_question', body || 'Community-Frage');
      options.forEach((option) => formData.append('poll_options[]', option));
    }

    const editId = state.editPost?.id || null;
    if (editId) formData.append('_method', 'PUT');

    setBusy(true, editId ? 'Wird gespeichert' : 'Wird veröffentlicht');
    try {
      const url = editId ? `/feed/${encodeURIComponent(String(editId))}` : '/feed';
      const payload = await requestJson(url, { method: 'POST', formData });
      toast(payload.message || (editId ? 'Beitrag aktualisiert' : 'Post veröffentlicht'));
      window.setTimeout(() => window.location.reload(), 350);
    } catch (error) {
      toast(error.message || 'Post konnte nicht gespeichert werden');
      setBusy(false);
    }
  };

  fileInput.addEventListener('change', () => {
    const incoming = [...(fileInput.files || [])];
    const room = Math.max(0, 12 - state.files.length - state.existingMedia.filter((item) => !state.removedMediaUrls.has(item.url)).length);
    if (incoming.length > room) toast(`Maximal 12 Medien. ${room} weitere sind möglich.`);
    state.files.push(...incoming.slice(0, room));
    fileInput.value = '';
    renderMedia();
  });

  const reportDialog = document.createElement('div');
  reportDialog.className = 'real-post-report';
  reportDialog.hidden = true;
  reportDialog.innerHTML = `
    <div class="real-post-report__backdrop" data-post-report-cancel></div>
    <form class="real-post-report__card">
      <span>BEITRAG MELDEN</span>
      <h3>Was stimmt mit diesem Beitrag nicht?</h3>
      <p>Die Meldung wird vertraulich an das HNT-Team übermittelt.</p>
      <label>Grund
        <select name="reason" required>
          <option value="spam">Spam</option>
          <option value="abuse">Belästigung oder Beleidigung</option>
          <option value="hate">Hassrede</option>
          <option value="nsfw">Unangemessener Inhalt</option>
          <option value="fraud">Betrug oder Täuschung</option>
          <option value="cheating">Cheating</option>
          <option value="privacy">Privatsphäre</option>
          <option value="other">Sonstiges</option>
        </select>
      </label>
      <label>Zusätzliche Angaben
        <textarea name="body" maxlength="2000" placeholder="Optional: Beschreibe kurz das Problem."></textarea>
      </label>
      <div class="real-post-report__actions">
        <button type="button" class="real-post-report__cancel" data-post-report-cancel>Abbrechen</button>
        <button type="submit" class="real-post-report__submit">Meldung senden</button>
      </div>
    </form>
  `;
  document.body.appendChild(reportDialog);
  let reportPostId = null;

  const closeReport = () => {
    reportDialog.hidden = true;
    reportPostId = null;
    reportDialog.querySelector('form')?.reset();
  };

  reportDialog.querySelectorAll('[data-post-report-cancel]').forEach((element) => element.addEventListener('click', closeReport));
  reportDialog.querySelector('form')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!reportPostId) return;
    const form = event.currentTarget;
    const submit = form.querySelector('[type="submit"]');
    submit.disabled = true;
    try {
      const payload = await requestJson('/reports', {
        method: 'POST',
        json: {
          type: 'feed_post',
          id: reportPostId,
          reason: form.reason.value,
          body: form.body.value.trim() || null,
        },
      });
      closeReport();
      toast(payload.message || 'Beitrag wurde gemeldet');
    } catch (error) {
      toast(error.message || 'Meldung konnte nicht gesendet werden');
    } finally {
      submit.disabled = false;
    }
  });

  const enhanceMenu = async (menu) => {
    if (!(menu instanceof Element) || menu.dataset.composeEnhanced === '1') return;
    menu.dataset.composeEnhanced = '1';
    const article = menu.closest('[data-real-feed-post]');
    const postId = Number.parseInt(article?.dataset.realFeedPost || '0', 10);
    if (!postId) return;

    try {
      const post = await fetchPost(postId);
      if (!menu.isConnected || !post) return;
      const deleteButton = menu.querySelector('[data-polish-delete]');

      if (post.viewer?.can_edit) {
        const edit = document.createElement('button');
        edit.type = 'button';
        edit.setAttribute('role', 'menuitem');
        edit.dataset.composeEdit = '1';
        edit.textContent = 'Beitrag bearbeiten';
        menu.insertBefore(edit, deleteButton || null);
        edit.addEventListener('click', () => {
          menu.remove();
          openEdit(postId);
        });
      }

      if (post.viewer?.can_report) {
        const report = document.createElement('button');
        report.type = 'button';
        report.setAttribute('role', 'menuitem');
        report.dataset.composeReport = '1';
        report.textContent = 'Beitrag melden';
        menu.insertBefore(report, deleteButton || null);
        report.addEventListener('click', () => {
          menu.remove();
          reportPostId = postId;
          reportDialog.hidden = false;
          window.setTimeout(() => reportDialog.querySelector('select')?.focus(), 40);
        });
      }
    } catch (error) {
      console.error('HNT post menu enhancement failed', error);
    }
  };

  const menuObserver = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
      if (!(node instanceof Element)) return;
      if (node.matches('.real-feed-menu--anchored')) enhanceMenu(node);
      node.querySelectorAll?.('.real-feed-menu--anchored').forEach(enhanceMenu);
    }));
  });
  menuObserver.observe(document.body, { childList: true, subtree: true });

  attachment?.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;
    const existingButton = target.closest('[data-remove-existing]');
    if (existingButton) {
      const item = existingButton.closest('[data-existing-url]');
      if (item?.dataset.existingUrl) state.removedMediaUrls.add(item.dataset.existingUrl);
      renderMedia();
      return;
    }
    const newButton = target.closest('[data-remove-new]');
    if (newButton) {
      const item = newButton.closest('[data-new-index]');
      const index = Number.parseInt(item?.dataset.newIndex || '-1', 10);
      if (index >= 0) state.files.splice(index, 1);
      renderMedia();
    }
  });

  document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;

    if (target.closest('#publishComposerPost')) {
      event.preventDefault();
      event.stopImmediatePropagation();
      submitComposer();
      return;
    }

    if (target.closest('#composerMediaButton')) {
      event.preventDefault();
      event.stopImmediatePropagation();
      fileInput.click();
      return;
    }

    if (target.closest('#removeComposerAttachment')) {
      event.preventDefault();
      event.stopImmediatePropagation();
      state.files = [];
      state.existingMedia.forEach((item) => state.removedMediaUrls.add(item.url));
      renderMedia();
      return;
    }

    if (target.closest('#composerAudience')) {
      event.preventDefault();
      event.stopImmediatePropagation();
      const index = visibilityOrder.indexOf(state.visibility);
      setAudience(visibilityOrder[(index + 1) % visibilityOrder.length]);
      return;
    }

    if (target.closest('#saveComposerDraft')) {
      event.preventDefault();
      event.stopImmediatePropagation();
      localStorage.setItem('hnt_preview_post_draft', input.value);
      toast('Entwurf lokal gespeichert');
      return;
    }

    const typeButton = target.closest('[data-composer-type]');
    if (typeButton && !state.editPost) {
      window.setTimeout(() => {
        if (typeButton.dataset.composerType === 'Frage') showQuestionPanel();
      }, 0);
    }

    if (target.closest('#openPostComposer')) {
      window.setTimeout(() => {
        if (!state.editPost) {
          resetComposerState();
          const draft = localStorage.getItem('hnt_preview_post_draft');
          if (draft && !input.value) {
            input.value = draft;
            input.dispatchEvent(new Event('input', { bubbles: true }));
          }
        }
      }, 0);
    }
  }, true);

  input.addEventListener('input', updatePublishState);

  const modalObserver = new MutationObserver(() => {
    if (!modal.classList.contains('is-open') && !state.busy) resetComposerState();
  });
  modalObserver.observe(modal, { attributes: true, attributeFilter: ['class'] });

  resetComposerState();
})();
