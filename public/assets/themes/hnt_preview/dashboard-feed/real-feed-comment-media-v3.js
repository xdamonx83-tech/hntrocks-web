/* Stable comment-media display and upload bridge for the isolated dashboard preview. */
(() => {
  const modal = document.getElementById('commentsModal');
  const list = document.getElementById('commentsList');
  const composer = document.getElementById('commentsComposer');
  const mainInput = document.getElementById('commentsInput');
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

  if (!modal || !list || !composer || !mainInput || !window.fetch) return;

  const state = {
    postId: 0,
    permalink: '',
    trigger: null,
    mediaByComment: new Map(),
    requestId: 0,
    renderQueued: false,
  };

  const formStates = new WeakMap();

  const style = document.createElement('style');
  style.id = 'real-comment-media-v3-styles';
  style.textContent = `
    .real-comment-media-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:7px;margin-top:11px;overflow:hidden;border-radius:13px}
    .real-comment-media-grid[data-count="1"]{grid-template-columns:minmax(0,1fr);max-width:560px}
    .real-comment-media-item{position:relative;min-width:0;min-height:118px;overflow:hidden;display:flex;align-items:center;justify-content:center;border:1px solid rgba(214,208,188,.72);border-radius:12px;background:#efede4;color:#514f48;text-decoration:none;cursor:zoom-in}
    .real-comment-media-grid[data-count="1"] .real-comment-media-item{min-height:180px;max-height:320px}
    .real-comment-media-item img{width:100%;height:100%;min-height:inherit;max-height:320px;display:block;object-fit:cover;background:#1d1d1b}
    .real-comment-upload-preview{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:7px;margin:9px 0 2px}
    .real-comment-upload-preview[hidden]{display:none!important}
    .real-comment-upload-item{position:relative;min-width:0;aspect-ratio:1;overflow:hidden;border:1px solid rgba(214,208,188,.9);border-radius:11px;background:#ece9df}
    .real-comment-upload-item img{width:100%;height:100%;display:block;object-fit:cover}
    .real-comment-upload-remove{position:absolute;top:5px;right:5px;width:25px;height:25px;display:grid;place-items:center;border:0;border-radius:50%;background:rgba(24,24,22,.86);color:#fff;font-size:17px;line-height:1;cursor:pointer}
    .real-comment-media-trigger{position:relative}
    .real-comment-media-trigger.has-files::after{content:attr(data-count);position:absolute;top:-5px;right:-5px;min-width:17px;height:17px;padding:0 4px;display:grid;place-items:center;border-radius:999px;background:#d5a92f;color:#151512;font-size:10px;font-weight:800}
    .real-comment-reply-form .real-comment-upload-preview{grid-template-columns:repeat(4,58px)}
    .real-comment-reply-form .real-comment-media-trigger{width:34px;height:34px;display:inline-grid;place-items:center;border:1px solid rgba(214,208,188,.9);border-radius:10px;background:#fffdf6;color:#5f5a4f;cursor:pointer}
    @media(max-width:699px){.real-comment-media-grid{gap:5px}.real-comment-media-item{min-height:92px}.real-comment-media-grid[data-count="1"] .real-comment-media-item{min-height:150px;max-height:250px}.real-comment-media-item img{max-height:250px}}
  `;
  document.head.appendChild(style);

  const toast = (message) => {
    if (typeof showToast === 'function') showToast(message);
    else console.info(message);
  };

  const escapeHtml = (value = '') => String(value).replace(/[&<>"']/g, (character) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
  })[character]);

  const syncActivePost = (sourceButton = null) => {
    const sourceArticle = sourceButton?.closest('[data-real-feed-post]');
    const sourcePostId = Number.parseInt(sourceArticle?.dataset.realFeedPost || '0', 10);
    const modalPostId = Number.parseInt(modal.dataset.realPostId || '0', 10);
    const postId = sourcePostId || modalPostId || state.postId;
    if (!postId) return false;

    const article = sourceArticle || document.querySelector(`[data-real-feed-post="${postId}"]`);
    state.postId = postId;
    state.permalink = article?.dataset.realPermalink || state.permalink || `/feed/posts/${postId}`;
    state.trigger = sourceButton || article?.querySelector('[data-real-preview-comments]') || state.trigger;
    return true;
  };

  const requestJson = async (url, formData) => {
    const response = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
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

  const getFormState = (form) => {
    let current = formStates.get(form);
    if (current) return current;

    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = 'image/jpeg,image/png,image/webp,image/gif';
    fileInput.multiple = true;
    fileInput.hidden = true;
    form.appendChild(fileInput);

    const preview = document.createElement('div');
    preview.className = 'real-comment-upload-preview';
    preview.hidden = true;

    if (form.id === 'commentsComposer') {
      const shell = form.querySelector('.comments-input-shell');
      const actions = shell?.querySelector('.comments-compose-actions');
      if (shell && actions) shell.insertBefore(preview, actions);
      else form.appendChild(preview);
    } else {
      const host = form.querySelector('div') || form;
      const footer = form.querySelector('footer');
      if (footer) host.insertBefore(preview, footer);
      else host.appendChild(preview);
    }

    current = { files: [], urls: [], input: fileInput, preview, trigger: null };
    formStates.set(form, current);

    const renderSelection = () => {
      current.urls.forEach((url) => URL.revokeObjectURL(url));
      current.urls = current.files.map((file) => URL.createObjectURL(file));

      if (!current.files.length) {
        preview.innerHTML = '';
        preview.hidden = true;
        current.trigger?.classList.remove('has-files');
        current.trigger?.removeAttribute('data-count');
        return;
      }

      preview.hidden = false;
      preview.innerHTML = current.files.map((file, index) => `
        <article class="real-comment-upload-item">
          <img src="${escapeHtml(current.urls[index])}" alt="${escapeHtml(file.name)}">
          <button class="real-comment-upload-remove" type="button" aria-label="Bild entfernen" data-remove-comment-upload="${index}">×</button>
        </article>
      `).join('');
      current.trigger?.classList.add('has-files');
      current.trigger?.setAttribute('data-count', String(current.files.length));
    };

    fileInput.addEventListener('change', () => {
      const incoming = [...(fileInput.files || [])];
      const room = Math.max(0, 4 - current.files.length);
      if (incoming.length > room) toast(`Maximal 4 Bilder pro Kommentar. ${room} weitere sind möglich.`);
      current.files.push(...incoming.slice(0, room));
      fileInput.value = '';
      renderSelection();
    });

    preview.addEventListener('click', (event) => {
      const target = event.target;
      if (!(target instanceof Element)) return;
      const remove = target.closest('[data-remove-comment-upload]');
      if (!remove) return;
      const index = Number.parseInt(remove.dataset.removeCommentUpload || '-1', 10);
      if (index < 0) return;
      current.files.splice(index, 1);
      renderSelection();
    });

    current.render = renderSelection;
    return current;
  };

  const clearSelection = (form) => {
    const current = getFormState(form);
    current.urls.forEach((url) => URL.revokeObjectURL(url));
    current.urls = [];
    current.files = [];
    current.input.value = '';
    current.preview.innerHTML = '';
    current.preview.hidden = true;
    current.trigger?.classList.remove('has-files');
    current.trigger?.removeAttribute('data-count');
  };

  const ensureMainComposer = () => {
    const current = getFormState(composer);
    const trigger = composer.querySelector('[aria-label="Medien hinzufügen"]');
    if (!trigger) return;
    trigger.classList.add('real-comment-media-trigger');
    trigger.removeAttribute('data-toast');
    current.trigger = trigger;
  };

  const ensureReplyComposer = (form) => {
    if (!(form instanceof HTMLFormElement) || !form.classList.contains('real-comment-reply-form')) return;
    const current = getFormState(form);
    if (current.trigger) return;
    const footer = form.querySelector('footer');
    if (!footer) return;

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'real-comment-media-trigger';
    trigger.setAttribute('aria-label', 'Bild hinzufügen');
    trigger.innerHTML = '<svg><use href="#i-image"></use></svg>';
    footer.insertBefore(trigger, footer.firstChild);
    current.trigger = trigger;
  };

  const mediaSignature = (media) => media.map((item) => item.url).join('|');

  const renderMediaGrid = (media, signature) => {
    const items = media.slice(0, 4).map((item) => `
      <a class="real-comment-media-item" href="${escapeHtml(item.url)}" target="_blank" rel="noopener">
        <img src="${escapeHtml(item.url)}" alt="${escapeHtml(item.alt || 'Kommentarbild')}" loading="lazy">
      </a>
    `).join('');

    return `<div class="real-comment-media-grid" data-count="${Math.min(media.length, 4)}" data-media-signature="${escapeHtml(signature)}">${items}</div>`;
  };

  const applyExistingMedia = () => {
    list.querySelectorAll('.real-comment-item[data-comment-id]').forEach((article) => {
      const commentId = Number.parseInt(article.dataset.commentId || '0', 10);
      const media = state.mediaByComment.get(commentId) || [];
      const bubble = article.querySelector('.comment-bubble');
      if (!bubble) return;

      const existing = [...bubble.children].find((child) => child.classList?.contains('real-comment-media-grid')) || null;
      if (!media.length) {
        existing?.remove();
        return;
      }

      const signature = mediaSignature(media);
      if (existing?.dataset.mediaSignature === signature) return;

      const wrapper = document.createElement('div');
      wrapper.innerHTML = renderMediaGrid(media, signature);
      const grid = wrapper.firstElementChild;
      if (!grid) return;

      if (existing) {
        existing.replaceWith(grid);
        return;
      }

      const text = [...bubble.children].find((child) => child.matches?.('[data-comment-text]')) || null;
      if (text) text.insertAdjacentElement('afterend', grid);
      else bubble.appendChild(grid);
    });
  };

  const queueEnhancement = () => {
    if (state.renderQueued) return;
    state.renderQueued = true;
    window.requestAnimationFrame(() => {
      state.renderQueued = false;
      list.querySelectorAll('.real-comment-reply-form').forEach(ensureReplyComposer);
      applyExistingMedia();
    });
  };

  const mediaMapFromHtml = (html) => {
    const parsed = new DOMParser().parseFromString(html, 'text/html');
    const result = new Map();

    parsed.querySelectorAll('[data-hnt-comment-item]').forEach((comment) => {
      const commentId = Number.parseInt(comment.getAttribute('data-hnt-comment-item') || '0', 10);
      if (!commentId) return;

      const media = [...comment.querySelectorAll('.hnt-comment-media-thumb')].map((item) => {
        const image = item.querySelector('img');
        const url = item.getAttribute('href') || item.dataset.hntLightboxSrc || image?.src || '';
        return url ? { url, alt: image?.alt || 'Kommentarbild' } : null;
      }).filter(Boolean);

      if (media.length) result.set(commentId, media);
    });

    return result;
  };

  const loadExistingMedia = async () => {
    if (!syncActivePost() || !state.permalink) return;
    const requestId = ++state.requestId;

    try {
      const url = new URL(state.permalink, window.location.origin);
      url.searchParams.set('hnt_preview_comments', '1');
      const response = await fetch(url.toString(), {
        credentials: 'same-origin',
        headers: { Accept: 'text/html,application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      if (!response.ok) throw new Error(`Comment media request failed with ${response.status}`);

      const contentType = response.headers.get('content-type') || '';
      const html = contentType.includes('application/json') ? (await response.json())?.html || '' : await response.text();
      if (requestId !== state.requestId) return;

      state.mediaByComment = mediaMapFromHtml(html);
      queueEnhancement();
    } catch (error) {
      console.error('HNT comment media loading failed', error);
    }
  };

  const refreshComments = () => {
    syncActivePost();
    if (!state.trigger) return;
    window.setTimeout(() => {
      state.trigger.click();
      window.setTimeout(loadExistingMedia, 350);
    }, 40);
  };

  const submitWithMedia = async (form, submitButton) => {
    syncActivePost();
    const current = getFormState(form);
    if (!state.postId || !current.files.length) return;

    const textarea = form.id === 'commentsComposer' ? mainInput : form.querySelector('textarea');
    const parentId = form.classList.contains('real-comment-reply-form')
      ? Number.parseInt(form.dataset.parentId || '0', 10)
      : 0;

    submitButton.disabled = true;
    try {
      const formData = new FormData();
      formData.append('body', textarea?.value.trim() || '');
      if (parentId) formData.append('parent_id', String(parentId));
      current.files.forEach((file) => formData.append('media[]', file));

      const payload = await requestJson(`/feed/${encodeURIComponent(String(state.postId))}/comments`, formData);
      if (textarea) {
        textarea.value = '';
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
      }
      clearSelection(form);
      toast(payload.message || (parentId ? 'Antwort veröffentlicht' : 'Kommentar veröffentlicht'));
      refreshComments();
    } catch (error) {
      toast(error.message || 'Bild konnte nicht veröffentlicht werden');
    } finally {
      submitButton.disabled = false;
    }
  };

  ensureMainComposer();

  window.addEventListener('pointerdown', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;
    const commentsButton = target.closest('[data-real-preview-comments]');
    if (commentsButton) syncActivePost(commentsButton);
  }, true);

  document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;

    const mediaTrigger = target.closest('.real-comment-media-trigger, .comments-composer [aria-label="Medien hinzufügen"]');
    if (mediaTrigger) {
      const form = mediaTrigger.closest('form');
      if (!form) return;
      event.preventDefault();
      event.stopImmediatePropagation();
      syncActivePost();
      const current = getFormState(form);
      current.trigger = mediaTrigger;
      current.input.click();
      return;
    }

    const submitButton = target.closest('#commentsComposer [type="submit"], .real-comment-reply-form [type="submit"]');
    if (!submitButton) return;
    const form = submitButton.closest('form');
    if (!form) return;
    const current = getFormState(form);
    if (!current.files.length) return;

    event.preventDefault();
    event.stopImmediatePropagation();
    submitWithMedia(form, submitButton);
  }, true);

  const listObserver = new MutationObserver((records) => {
    const meaningful = records.some((record) => [...record.addedNodes].some((node) => {
      if (!(node instanceof Element)) return false;
      return !node.classList.contains('real-comment-media-grid');
    }));
    if (meaningful) queueEnhancement();
  });
  listObserver.observe(list, { childList: true, subtree: true });

  const modalObserver = new MutationObserver(() => {
    if (modal.classList.contains('is-open')) {
      if (syncActivePost()) window.setTimeout(loadExistingMedia, 90);
      return;
    }

    state.requestId += 1;
    state.postId = 0;
    state.permalink = '';
    state.trigger = null;
    state.mediaByComment = new Map();
    clearSelection(composer);
  });
  modalObserver.observe(modal, { attributes: true, attributeFilter: ['class', 'data-real-post-id'] });
})();
