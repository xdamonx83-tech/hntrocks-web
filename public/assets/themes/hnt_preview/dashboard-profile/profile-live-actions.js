/* Real profile post actions: reactions, bookmarks, anchored post menu and edit/report/delete. */
(() => {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  if (!window.fetch) return;

  const profileComposerFixes = document.createElement('style');
  profileComposerFixes.textContent = `
    #postComposerModal [data-composer-type="LFG"],
    #postComposerModal [data-composer-type="Moment"],
    #postComposerModal #composerLfgButton { display:none!important; }
    .post-composer-modal.is-editing .composer-textarea-shell { min-height:180px; max-height:min(290px,32vh); overflow:hidden; }
    .post-composer-modal.is-editing #postComposerInput { height:min(270px,30vh)!important; min-height:180px!important; max-height:min(270px,30vh)!important; overflow-x:hidden!important; overflow-y:auto!important; overscroll-behavior:contain; resize:none; scrollbar-gutter:stable; }
    .post-composer-modal.is-editing .post-composer-body { min-height:0; overflow-y:auto; overscroll-behavior:contain; }
  `;
  document.head.appendChild(profileComposerFixes);

  /* Shared feed viewers request the current page with ?data=1. On profile pages,
     only post payload requests are bridged to the real feed endpoint. */
  if (!window.HNT_PROFILE_FEED_FETCH_BRIDGE) {
    window.HNT_PROFILE_FEED_FETCH_BRIDGE = true;
    const nativeFetch = window.fetch.bind(window);
    window.fetch = (input, init) => {
      try {
        const source = input instanceof Request ? input.url : String(input);
        const url = new URL(source, window.location.href);
        const isProfileViewerRequest = url.pathname === window.location.pathname
          && url.searchParams.get('data') === '1'
          && url.searchParams.has('post_id');

        if (isProfileViewerRequest) {
          url.pathname = '/feed';
          input = input instanceof Request ? new Request(url.toString(), input) : url.toString();
        }
      } catch (_) {
        // Leave unrelated requests untouched.
      }
      return nativeFetch(input, init);
    };
  }

  const loadScript = (src, attribute) => {
    if (document.querySelector(`script[data-${attribute}]`)) return;
    const script = document.createElement('script');
    script.src = src;
    script.setAttribute(`data-${attribute}`, '1');
    document.body.appendChild(script);
  };

  loadScript('/assets/themes/hnt_preview/dashboard-feed/shared-video-player.js?v=1', 'hnt-shared-video-player');
  loadScript('/assets/themes/hnt_preview/dashboard-feed/real-feed-translation.js?v=1', 'hnt-feed-translation');
  loadScript('/assets/themes/hnt_preview/dashboard-feed/real-feed-video-open.js?v=1', 'hnt-feed-video-open');

  const toast = (message) => {
    if (typeof window.showToast === 'function') window.showToast(message);
    else console.info(message);
  };

  const requestJson = async (url, { method = 'POST', json = null, formData = null } = {}) => {
    const headers = {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
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

  const copyText = async (value) => {
    if (!navigator.clipboard) throw new Error('Link konnte nicht kopiert werden');
    await navigator.clipboard.writeText(value);
  };

  const escapeHtml = (value = '') => String(value).replace(/[&<>"']/g, (character) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
  })[character]);

  /* ---------- Profile post menu ---------- */
  let activeMenu = null;
  let menuRequestId = 0;

  const closeMenu = () => {
    activeMenu?.remove();
    activeMenu = null;
  };

  const positionMenu = (menu, trigger, article) => {
    const triggerRect = trigger.getBoundingClientRect();
    const articleRect = article.getBoundingClientRect();
    const menuWidth = 205;
    const left = Math.max(14, Math.min(articleRect.width - menuWidth - 14, triggerRect.right - articleRect.left - menuWidth));
    menu.style.top = `${Math.max(56, triggerRect.bottom - articleRect.top + 8)}px`;
    menu.style.left = `${left}px`;
  };

  const fetchPost = async (postId) => {
    const payload = await requestJson(`/feed?data=1&post_id=${encodeURIComponent(String(postId))}`, { method: 'GET' });
    return payload.post || null;
  };

  /* ---------- Edit modal ---------- */
  const modal = document.getElementById('postComposerModal');
  const input = document.getElementById('postComposerInput');
  const counter = document.getElementById('postComposerCounter');
  const publish = document.getElementById('publishComposerPost');
  const publishLabel = publish?.querySelector('span');
  const title = document.getElementById('postComposerTitle');
  const audienceLabel = document.getElementById('composerAudience')?.querySelector('span');
  const attachment = document.getElementById('composerAttachment');

  const editState = {
    post: null,
    visibility: 'public',
    existingMedia: [],
    removedMediaUrls: new Set(),
    files: [],
    objectUrls: [],
    busy: false,
  };
  const visibilityLabels = { public: 'Öffentlich', followers: 'Freunde', private: 'Privat' };
  const visibilityOrder = ['public', 'followers', 'private'];

  const editFileInput = document.createElement('input');
  editFileInput.type = 'file';
  editFileInput.accept = 'image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime';
  editFileInput.multiple = true;
  editFileInput.hidden = true;
  document.body.appendChild(editFileInput);

  const clearObjectUrls = () => {
    editState.objectUrls.forEach((url) => URL.revokeObjectURL(url));
    editState.objectUrls = [];
  };

  const updateEditPublishState = () => {
    if (!editState.post || !publish || !input) return;
    const keptExisting = editState.existingMedia.filter((item) => !editState.removedMediaUrls.has(item.url)).length;
    const hasContent = input.value.trim().length > 0 || keptExisting > 0 || editState.files.length > 0;
    publish.disabled = editState.busy || !hasContent;
    if (counter) counter.textContent = `${input.value.length}/1000`;
  };

  const renderEditMedia = () => {
    if (!attachment || !editState.post) return;
    clearObjectUrls();
    const items = [];

    editState.existingMedia
      .filter((item) => !editState.removedMediaUrls.has(item.url))
      .forEach((item) => {
        const media = item.type === 'video'
          ? `<video muted playsinline preload="metadata"><source src="${escapeHtml(item.url)}" type="${escapeHtml(item.mime || 'video/mp4')}"></video>`
          : `<img src="${escapeHtml(item.url)}" alt="${escapeHtml(item.alt || 'Bestehendes Medium')}">`;
        items.push(`<article class="real-composer-media-item" data-profile-existing-url="${escapeHtml(item.url)}">${media}<button type="button" aria-label="Medium entfernen" data-profile-remove-existing><svg><use href="#i-x"></use></svg></button></article>`);
      });

    editState.files.forEach((file, index) => {
      const url = URL.createObjectURL(file);
      editState.objectUrls.push(url);
      const media = file.type.startsWith('video/')
        ? `<video muted playsinline preload="metadata"><source src="${escapeHtml(url)}" type="${escapeHtml(file.type)}"></video>`
        : `<img src="${escapeHtml(url)}" alt="${escapeHtml(file.name)}">`;
      items.push(`<article class="real-composer-media-item" data-profile-new-index="${index}">${media}<button type="button" aria-label="Medium entfernen" data-profile-remove-new><svg><use href="#i-x"></use></svg></button></article>`);
    });

    if (!items.length) {
      attachment.hidden = true;
      attachment.innerHTML = '';
    } else {
      attachment.hidden = false;
      attachment.innerHTML = `<div class="real-composer-media-grid">${items.join('')}<div class="real-composer-media-note">Bis zu 12 Bilder oder Videos. Bestehende Medien bleiben erhalten, solange du sie nicht entfernst.</div></div>`;
    }
    updateEditPublishState();
  };

  const clearEditState = () => {
    editState.post = null;
    editState.visibility = 'public';
    editState.existingMedia = [];
    editState.removedMediaUrls.clear();
    editState.files = [];
    editState.busy = false;
    clearObjectUrls();
    editFileInput.value = '';
    modal?.querySelector('.post-composer-modal')?.classList.remove('is-editing');
    if (title) title.textContent = 'Post erstellen';
    if (publishLabel) publishLabel.textContent = 'Veröffentlichen';
    if (audienceLabel) audienceLabel.textContent = visibilityLabels.public;
    if (input) {
      input.value = '';
      input.style.height = '';
      input.scrollTop = 0;
      input.dispatchEvent(new Event('input', { bubbles: true }));
    }
    if (attachment) {
      attachment.hidden = true;
      attachment.innerHTML = '';
    }
  };

  const openEdit = async (postId) => {
    try {
      const post = await fetchPost(postId);
      if (!post?.viewer?.can_edit) throw new Error('Dieser Beitrag kann nicht bearbeitet werden');

      closeMenu();
      editState.post = post;
      editState.visibility = ['followers', 'private'].includes(post.visibility) ? post.visibility : 'public';
      editState.existingMedia = Array.isArray(post.media) ? post.media : [];
      editState.removedMediaUrls.clear();
      editState.files = [];
      editState.busy = false;

      if (input) {
        input.value = post.body || '';
        input.style.height = '';
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.scrollTop = 0;
      }
      if (audienceLabel) audienceLabel.textContent = visibilityLabels[editState.visibility];
      if (title) title.textContent = 'Post bearbeiten';
      if (publishLabel) publishLabel.textContent = 'Änderungen speichern';
      modal?.querySelector('.post-composer-modal')?.classList.add('is-editing');
      renderEditMedia();

      modal?.classList.add('is-open');
      modal?.setAttribute('aria-hidden', 'false');
      document.body.classList.add('composer-open');

      window.setTimeout(() => {
        input?.focus({ preventScroll: true });
        input?.setSelectionRange(0, 0);
        if (input) input.scrollTop = 0;
      }, 90);
    } catch (error) {
      toast(error.message || 'Beitrag konnte nicht geladen werden');
    }
  };

  const submitEdit = async () => {
    if (!editState.post || editState.busy || !input) return;
    const keptExisting = editState.existingMedia.filter((item) => !editState.removedMediaUrls.has(item.url)).length;
    const body = input.value.trim();
    if (!body && !keptExisting && !editState.files.length) {
      toast('Schreibe etwas oder füge Medien hinzu');
      input.focus();
      return;
    }

    const formData = new FormData();
    formData.append('_method', 'PUT');
    formData.append('body', body);
    formData.append('visibility', editState.visibility);
    formData.append('background_style', 'none');
    formData.append('feeling_key', 'none');
    editState.files.forEach((file) => formData.append('media[]', file));
    editState.removedMediaUrls.forEach((url) => formData.append('media_remove_urls[]', url));

    editState.busy = true;
    updateEditPublishState();
    if (publishLabel) publishLabel.textContent = 'Wird gespeichert';

    try {
      const payload = await requestJson(`/feed/${encodeURIComponent(String(editState.post.id))}`, { method: 'POST', formData });
      toast(payload.message || 'Beitrag aktualisiert');
      history.replaceState(null, '', `${window.location.pathname}${window.location.search}#post-${editState.post.id}`);
      window.location.reload();
    } catch (error) {
      editState.busy = false;
      if (publishLabel) publishLabel.textContent = 'Änderungen speichern';
      updateEditPublishState();
      toast(error.message || 'Beitrag konnte nicht gespeichert werden');
    }
  };

  editFileInput.addEventListener('change', () => {
    const existingCount = editState.existingMedia.filter((item) => !editState.removedMediaUrls.has(item.url)).length;
    const room = Math.max(0, 12 - existingCount - editState.files.length);
    const incoming = [...(editFileInput.files || [])];
    if (incoming.length > room) toast(`Maximal 12 Medien. ${room} weitere sind möglich.`);
    editState.files.push(...incoming.slice(0, room));
    editFileInput.value = '';
    renderEditMedia();
  });

  attachment?.addEventListener('click', (event) => {
    if (!editState.post || !(event.target instanceof Element)) return;
    const existing = event.target.closest('[data-profile-remove-existing]');
    if (existing) {
      const item = existing.closest('[data-profile-existing-url]');
      if (item?.dataset.profileExistingUrl) editState.removedMediaUrls.add(item.dataset.profileExistingUrl);
      renderEditMedia();
      return;
    }
    const fresh = event.target.closest('[data-profile-remove-new]');
    if (fresh) {
      const item = fresh.closest('[data-profile-new-index]');
      const index = Number.parseInt(item?.dataset.profileNewIndex || '-1', 10);
      if (index >= 0) editState.files.splice(index, 1);
      renderEditMedia();
    }
  });

  /* ---------- Report dialog ---------- */
  const reportDialog = document.createElement('div');
  reportDialog.className = 'real-post-report';
  reportDialog.hidden = true;
  reportDialog.innerHTML = `
    <div class="real-post-report__backdrop" data-profile-report-cancel></div>
    <form class="real-post-report__card">
      <span>BEITRAG MELDEN</span><h3>Was stimmt mit diesem Beitrag nicht?</h3>
      <p>Die Meldung wird vertraulich an das HNT-Team übermittelt.</p>
      <label>Grund<select name="reason" required><option value="spam">Spam</option><option value="abuse">Belästigung oder Beleidigung</option><option value="hate">Hassrede</option><option value="nsfw">Unangemessener Inhalt</option><option value="fraud">Betrug oder Täuschung</option><option value="cheating">Cheating</option><option value="privacy">Privatsphäre</option><option value="other">Sonstiges</option></select></label>
      <label>Zusätzliche Angaben<textarea name="body" maxlength="2000" placeholder="Optional: Beschreibe kurz das Problem."></textarea></label>
      <div class="real-post-report__actions"><button type="button" class="real-post-report__cancel" data-profile-report-cancel>Abbrechen</button><button type="submit" class="real-post-report__submit">Meldung senden</button></div>
    </form>`;
  document.body.appendChild(reportDialog);
  let reportPostId = null;

  const closeReport = () => {
    reportDialog.hidden = true;
    reportPostId = null;
    reportDialog.querySelector('form')?.reset();
  };
  reportDialog.querySelectorAll('[data-profile-report-cancel]').forEach((node) => node.addEventListener('click', closeReport));
  reportDialog.querySelector('form')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!reportPostId) return;
    const form = event.currentTarget;
    const submit = form.querySelector('[type="submit"]');
    submit.disabled = true;
    try {
      const payload = await requestJson('/reports', { json: { type: 'feed_post', id: reportPostId, reason: form.reason.value, body: form.body.value.trim() || null } });
      closeReport();
      toast(payload.message || 'Beitrag wurde gemeldet');
    } catch (error) {
      toast(error.message || 'Meldung konnte nicht gesendet werden');
    } finally {
      submit.disabled = false;
    }
  });

  const showMenu = async (trigger, article) => {
    closeMenu();
    const requestId = ++menuRequestId;
    const postId = Number.parseInt(article.dataset.realFeedPost || article.dataset.profilePostId || '0', 10);
    if (!postId) return;

    try {
      const post = await fetchPost(postId);
      if (requestId !== menuRequestId || !post || !trigger.isConnected) return;
      const permalink = post.permalink || article.dataset.realPermalink || `/feed/posts/${postId}`;
      const menu = document.createElement('div');
      menu.className = 'real-feed-menu real-feed-menu--anchored';
      menu.setAttribute('role', 'menu');
      menu.innerHTML = `
        <button type="button" role="menuitem" data-profile-menu-open>Beitrag öffnen</button>
        <button type="button" role="menuitem" data-profile-menu-copy>Link kopieren</button>
        ${post.viewer?.can_edit ? '<button type="button" role="menuitem" data-profile-menu-edit>Beitrag bearbeiten</button>' : ''}
        ${post.viewer?.can_report ? '<button type="button" role="menuitem" data-profile-menu-report>Beitrag melden</button>' : ''}
        ${post.viewer?.can_delete ? '<button type="button" role="menuitem" class="danger" data-profile-menu-delete>Beitrag löschen</button>' : ''}`;

      article.appendChild(menu);
      positionMenu(menu, trigger, article);
      activeMenu = menu;
      requestAnimationFrame(() => menu.classList.add('is-open'));

      menu.querySelector('[data-profile-menu-open]')?.addEventListener('click', () => { window.location.href = permalink; });
      menu.querySelector('[data-profile-menu-copy]')?.addEventListener('click', async () => {
        try { await copyText(permalink); toast('Link kopiert'); } catch (error) { toast(error.message); }
        closeMenu();
      });
      menu.querySelector('[data-profile-menu-edit]')?.addEventListener('click', () => openEdit(postId));
      menu.querySelector('[data-profile-menu-report]')?.addEventListener('click', () => {
        closeMenu();
        reportPostId = postId;
        reportDialog.hidden = false;
        window.setTimeout(() => reportDialog.querySelector('select')?.focus(), 40);
      });
      menu.querySelector('[data-profile-menu-delete]')?.addEventListener('click', async () => {
        if (!window.confirm('Diesen Beitrag wirklich löschen?')) return;
        try {
          await requestJson(`/feed/${encodeURIComponent(String(postId))}`, { method: 'DELETE', json: {} });
          article.remove();
          closeMenu();
          toast('Beitrag gelöscht');
        } catch (error) {
          toast(error.message || 'Beitrag konnte nicht gelöscht werden');
        }
      });
    } catch (error) {
      toast(error.message || 'Beitragsmenü konnte nicht geladen werden');
    }
  };

  /* Window capture runs before the older document-capture composer bridge. */
  window.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;

    if (editState.post && target.closest('#publishComposerPost')) {
      event.preventDefault();
      event.stopImmediatePropagation();
      submitEdit();
      return;
    }
    if (editState.post && target.closest('#composerMediaButton')) {
      event.preventDefault();
      event.stopImmediatePropagation();
      editFileInput.click();
      return;
    }
    if (editState.post && target.closest('#composerAudience')) {
      event.preventDefault();
      event.stopImmediatePropagation();
      const index = visibilityOrder.indexOf(editState.visibility);
      editState.visibility = visibilityOrder[(index + 1) % visibilityOrder.length];
      if (audienceLabel) audienceLabel.textContent = visibilityLabels[editState.visibility];
      return;
    }

    const trigger = target.closest('.profile-real-post .post-more');
    if (trigger) {
      event.preventDefault();
      event.stopImmediatePropagation();
      const article = trigger.closest('.profile-real-post');
      if (article) showMenu(trigger, article);
      return;
    }

    if (activeMenu && !activeMenu.contains(target)) closeMenu();
  }, true);

  input?.addEventListener('input', () => {
    if (editState.post) updateEditPublishState();
  });

  const modalObserver = modal ? new MutationObserver(() => {
    if (!modal.classList.contains('is-open') && editState.post && !editState.busy) clearEditState();
  }) : null;
  modalObserver?.observe(modal, { attributes: true, attributeFilter: ['class'] });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    if (!reportDialog.hidden) closeReport();
    else closeMenu();
  });

  document.addEventListener('click', async (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;

    const likeButton = target.closest('[data-profile-like-url]');
    if (likeButton) {
      event.preventDefault();
      event.stopImmediatePropagation();
      if (likeButton.disabled) return;
      likeButton.disabled = true;
      try {
        const payload = await requestJson(likeButton.dataset.profileLikeUrl, { json: { type: 'like' } });
        likeButton.classList.toggle('liked', Boolean(payload.reacted));
        likeButton.classList.toggle('is-active', Boolean(payload.reacted));
        const count = likeButton.querySelector('span');
        if (count) count.textContent = new Intl.NumberFormat(document.documentElement.lang || 'de').format(Number(payload.count) || 0);
      } catch (error) {
        toast(error.message || 'Reaktion konnte nicht gespeichert werden');
      } finally {
        likeButton.disabled = false;
      }
      return;
    }

    const bookmarkButton = target.closest('[data-profile-bookmark-url]');
    if (bookmarkButton) {
      event.preventDefault();
      event.stopImmediatePropagation();
      if (bookmarkButton.disabled) return;
      bookmarkButton.disabled = true;
      try {
        const payload = await requestJson(bookmarkButton.dataset.profileBookmarkUrl, { json: {} });
        bookmarkButton.classList.toggle('saved', Boolean(payload.bookmarked));
        bookmarkButton.classList.toggle('is-active', Boolean(payload.bookmarked));
        bookmarkButton.setAttribute('aria-label', payload.bookmarked ? 'Beitrag gespeichert' : 'Beitrag speichern');
        toast(payload.message || (payload.bookmarked ? 'Beitrag gespeichert' : 'Nicht mehr gespeichert'));
      } catch (error) {
        toast(error.message || 'Beitrag konnte nicht gespeichert werden');
      } finally {
        bookmarkButton.disabled = false;
      }
    }
  }, true);
})();
