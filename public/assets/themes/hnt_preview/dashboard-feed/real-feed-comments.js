/* Threaded live comments for the isolated dashboard feed preview.
   Intercepts only real Laravel posts and leaves the static demo untouched. */
(() => {
  const feedList = document.querySelector('.post-list');
  const feedScroll = document.getElementById('feedScroll');
  const modal = document.getElementById('commentsModal');
  const list = document.getElementById('commentsList');
  const composer = document.getElementById('commentsComposer');
  const input = document.getElementById('commentsInput');
  const sortButton = document.getElementById('commentsSort');
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const endpoint = `${window.location.pathname}?data=1`;

  if (!feedList || !modal || !list || !composer || !input || !window.fetch) return;

  let activePost = null;
  let activeTrigger = null;
  let newestFirst = false;
  let activeMenu = null;
  let pendingDeleteComment = null;
  let pendingReportComment = null;

  const toast = (message) => {
    if (typeof showToast === 'function') {
      showToast(message);
      return;
    }

    console.info(message);
  };

  const escapeHtml = (value = '') => String(value).replace(/[&<>"']/g, (character) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  })[character]);

  const requestJson = async (url, { method = 'GET', json = null, formData = null } = {}) => {
    const headers = {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
    };
    const options = {
      method,
      credentials: 'same-origin',
      headers,
    };

    if (formData) {
      options.body = formData;
    } else if (json !== null) {
      headers['Content-Type'] = 'application/json';
      options.body = JSON.stringify(json);
    }

    const response = await fetch(url, options);
    let payload = null;

    try {
      payload = await response.json();
    } catch (_) {
      payload = null;
    }

    if (!response.ok) {
      const validationMessage = payload?.errors
        ? Object.values(payload.errors).flat().find(Boolean)
        : null;
      throw new Error(validationMessage || payload?.message || `Request failed with ${response.status}`);
    }

    return payload || {};
  };

  const commentsForTree = (comments = []) => {
    const byId = new Map(comments.map((comment) => [Number(comment.id), comment]));
    const roots = [];
    const replies = new Map();

    comments.forEach((comment) => {
      const parentId = Number(comment.parent_id || 0);
      if (!parentId || !byId.has(parentId)) {
        roots.push(comment);
        return;
      }

      if (!replies.has(parentId)) replies.set(parentId, []);
      replies.get(parentId).push(comment);
    });

    const timestamp = (comment) => Date.parse(comment.created_at || '') || Number(comment.id) || 0;
    roots.sort((a, b) => newestFirst ? timestamp(b) - timestamp(a) : timestamp(b) - timestamp(a));
    replies.forEach((items) => items.sort((a, b) => timestamp(a) - timestamp(b)));

    return { roots, replies };
  };

  const commentActions = (comment) => {
    const canEdit = Boolean(comment.viewer?.can_edit);
    const canDelete = Boolean(comment.viewer?.can_delete);
    const canReport = Boolean(comment.viewer?.can_report);

    if (!canEdit && !canDelete && !canReport) return '';

    return `
      <button class="real-comment-more" type="button" aria-label="Kommentaroptionen" data-comment-more>
        <svg><use href="#i-more"></use></svg>
      </button>
    `;
  };

  const renderComment = (comment, isReply = false) => `
    <article class="comment-item real-comment-item${isReply ? ' is-reply' : ''}" data-comment-id="${Number(comment.id)}">
      <img class="comment-avatar" src="${escapeHtml(comment.avatar || '')}" alt="">
      <div class="comment-bubble">
        <div class="comment-head">
          <div>
            <strong>${escapeHtml(comment.name || 'HNT Hunter')}</strong>
            <span>${escapeHtml(comment.handle || '@hunter')} · ${escapeHtml(comment.time || 'gerade eben')}</span>
          </div>
          ${commentActions(comment)}
        </div>
        <p data-comment-text>${escapeHtml(comment.text || '').replace(/\n/g, '<br>')}</p>
        <div class="comment-actions">
          <button type="button" class="comment-like${comment.reacted ? ' is-liked' : ''}" data-comment-like>
            <svg><use href="#i-heart"></use></svg><span>${Number(comment.likes || 0)}</span>
          </button>
          <button type="button" class="comment-reply" data-comment-reply>
            <svg><use href="#i-reply"></use></svg><span>Antworten</span>
          </button>
        </div>
        <div class="real-comment-inline-slot"></div>
      </div>
    </article>
  `;

  const renderComments = () => {
    const comments = Array.isArray(activePost?.comments) ? activePost.comments : [];

    if (comments.length === 0) {
      list.innerHTML = `
        <div class="real-comments-empty">
          <strong>Noch keine Kommentare</strong>
          <span>Starte die Diskussion und schreibe den ersten Kommentar.</span>
        </div>
      `;
      return;
    }

    const { roots, replies } = commentsForTree(comments);
    list.innerHTML = roots.map((root) => {
      const children = replies.get(Number(root.id)) || [];
      return `
        <section class="real-comment-thread">
          ${renderComment(root)}
          ${children.length ? `<div class="real-comment-replies">${children.map((reply) => renderComment(reply, true)).join('')}</div>` : ''}
        </section>
      `;
    }).join('');
  };

  const updatePostCounter = () => {
    if (!activePost) return;
    const article = feedList.querySelector(`[data-real-feed-post="${Number(activePost.id)}"]`);
    const counter = article?.querySelector('[data-real-preview-comments] span');
    if (counter) counter.textContent = String(Number(activePost.counts?.comments || activePost.comments?.length || 0));

    if (typeof updateModalCommentCount === 'function') {
      updateModalCommentCount(Number(activePost.counts?.comments || activePost.comments?.length || 0));
    }
  };

  const setModalContext = (post) => {
    const avatar = document.getElementById('commentsPostAvatar');
    const author = document.getElementById('commentsPostAuthor');
    const meta = document.getElementById('commentsPostMeta');
    const excerpt = document.getElementById('commentsPostExcerpt');
    const badge = document.getElementById('commentsPostBadge');

    if (avatar) avatar.src = post.author?.avatar || '';
    if (author) author.textContent = post.author?.name || 'HNT Hunter';
    if (meta) meta.textContent = `${post.author?.handle || '@hunter'} · ${post.created_at || 'gerade eben'}`;
    if (excerpt) excerpt.textContent = post.excerpt || post.body || 'Beitrag';
    if (badge) badge.textContent = post.badge || 'Beitrag';
  };

  const loadPost = async (postId) => {
    const payload = await requestJson(`${endpoint}&post_id=${encodeURIComponent(String(postId))}`);
    return payload.post || null;
  };

  const refreshComments = async () => {
    if (!activePost?.id) return;
    const post = await loadPost(activePost.id);
    if (!post) return;
    activePost = post;
    renderComments();
    updatePostCounter();
  };

  const openComments = async (button, postId) => {
    activeTrigger = button;
    newestFirst = false;
    list.innerHTML = '<div class="real-comments-loading"><span></span><b>Kommentare werden geladen</b></div>';
    modal.dataset.realPreview = '2';
    modal.dataset.realPostId = String(postId);
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('comments-open');

    try {
      const post = await loadPost(postId);
      if (!post) throw new Error('Beitrag konnte nicht geladen werden');
      activePost = post;
      setModalContext(post);
      renderComments();
      updatePostCounter();
      if (sortButton) sortButton.childNodes[0].textContent = 'Relevant ';
    } catch (error) {
      list.innerHTML = `<div class="real-comments-empty"><strong>Kommentare konnten nicht geladen werden</strong><span>${escapeHtml(error.message)}</span></div>`;
    }
  };

  const currentViewerAvatar = () => document.querySelector('.comments-composer > img')?.getAttribute('src') || '';

  const closeInlineComposers = () => {
    list.querySelectorAll('.real-comment-reply-form, .real-comment-edit-form').forEach((form) => form.remove());
  };

  const openReplyComposer = (comment, article) => {
    closeInlineComposers();
    const slot = article.querySelector('.real-comment-inline-slot');
    if (!slot) return;

    const form = document.createElement('form');
    form.className = 'real-comment-reply-form';
    form.dataset.parentId = String(comment.root_id || comment.id);
    form.innerHTML = `
      <img src="${escapeHtml(currentViewerAvatar())}" alt="">
      <div>
        <textarea maxlength="500" rows="1" placeholder="${escapeHtml(comment.handle || comment.name || 'Antwort')} antworten …"></textarea>
        <footer>
          <button type="button" data-reply-cancel>Abbrechen</button>
          <button type="submit">Antworten</button>
        </footer>
      </div>
    `;
    slot.appendChild(form);
    form.querySelector('textarea')?.focus();
  };

  const submitComment = async (body, parentId = null) => {
    if (!activePost?.routes?.comments || !body.trim()) return;

    const formData = new FormData();
    formData.append('body', body.trim());
    if (parentId) formData.append('parent_id', String(parentId));

    await requestJson(activePost.routes.comments, { method: 'POST', formData });
    await refreshComments();
  };

  const handleCommentLike = async (button, comment) => {
    if (!comment?.routes?.reaction) return;
    button.disabled = true;

    try {
      const payload = await requestJson(comment.routes.reaction, {
        method: 'POST',
        json: { type: 'like' },
      });
      comment.reacted = Boolean(payload.reacted);
      comment.likes = Number(payload.count || 0);
      button.classList.toggle('is-liked', comment.reacted);
      const counter = button.querySelector('span');
      if (counter) counter.textContent = String(comment.likes);
    } catch (error) {
      toast(error.message || 'Kommentar-Reaktion konnte nicht gespeichert werden');
    } finally {
      button.disabled = false;
    }
  };

  const closeCommentMenu = () => {
    activeMenu?.remove();
    activeMenu = null;
  };

  const deleteDialog = document.createElement('div');
  deleteDialog.className = 'real-comment-dialog';
  deleteDialog.hidden = true;
  deleteDialog.innerHTML = `
    <div class="real-comment-dialog__backdrop" data-comment-delete-cancel></div>
    <section class="real-comment-dialog__card" role="dialog" aria-modal="true">
      <span>KOMMENTAR LÖSCHEN</span>
      <h3>Kommentar wirklich entfernen?</h3>
      <p>Der Kommentar und seine Antworten werden dauerhaft gelöscht.</p>
      <div>
        <button type="button" data-comment-delete-cancel>Abbrechen</button>
        <button type="button" class="danger" data-comment-delete-confirm>Löschen</button>
      </div>
    </section>
  `;
  document.body.appendChild(deleteDialog);

  const closeDeleteDialog = () => {
    deleteDialog.hidden = true;
    pendingDeleteComment = null;
  };

  deleteDialog.querySelectorAll('[data-comment-delete-cancel]').forEach((button) => button.addEventListener('click', closeDeleteDialog));
  deleteDialog.querySelector('[data-comment-delete-confirm]')?.addEventListener('click', async (event) => {
    if (!pendingDeleteComment?.routes?.delete) return;
    const button = event.currentTarget;
    button.disabled = true;

    try {
      await requestJson(pendingDeleteComment.routes.delete, { method: 'DELETE', json: {} });
      closeDeleteDialog();
      await refreshComments();
      toast('Kommentar gelöscht');
    } catch (error) {
      toast(error.message || 'Kommentar konnte nicht gelöscht werden');
    } finally {
      button.disabled = false;
    }
  });

  const reportDialog = document.createElement('div');
  reportDialog.className = 'real-comment-dialog';
  reportDialog.hidden = true;
  reportDialog.innerHTML = `
    <div class="real-comment-dialog__backdrop" data-comment-report-cancel></div>
    <form class="real-comment-dialog__card real-comment-report-card">
      <span>KOMMENTAR MELDEN</span>
      <h3>Was stimmt mit diesem Kommentar nicht?</h3>
      <label>
        <small>Grund</small>
        <select name="reason" required>
          <option value="spam">Spam</option>
          <option value="abuse">Belästigung oder Beleidigung</option>
          <option value="hate">Hassrede</option>
          <option value="nsfw">Unangemessener Inhalt</option>
          <option value="fraud">Betrug</option>
          <option value="cheating">Cheating</option>
          <option value="privacy">Privatsphäre</option>
          <option value="other">Sonstiges</option>
        </select>
      </label>
      <label>
        <small>Zusätzliche Angaben</small>
        <textarea name="body" maxlength="2000" rows="4" placeholder="Optional"></textarea>
      </label>
      <div>
        <button type="button" data-comment-report-cancel>Abbrechen</button>
        <button type="submit">Meldung senden</button>
      </div>
    </form>
  `;
  document.body.appendChild(reportDialog);

  const closeReportDialog = () => {
    reportDialog.hidden = true;
    pendingReportComment = null;
    reportDialog.querySelector('form')?.reset();
  };

  reportDialog.querySelectorAll('[data-comment-report-cancel]').forEach((button) => button.addEventListener('click', closeReportDialog));
  reportDialog.querySelector('form')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!pendingReportComment?.routes?.report) return;

    const form = event.currentTarget;
    const submit = form.querySelector('[type="submit"]');
    const data = new FormData(form);
    submit.disabled = true;

    try {
      const payload = await requestJson(pendingReportComment.routes.report, {
        method: 'POST',
        json: {
          type: 'feed_comment',
          id: Number(pendingReportComment.id),
          reason: data.get('reason'),
          body: data.get('body') || null,
        },
      });
      closeReportDialog();
      toast(payload.message || 'Kommentar gemeldet');
    } catch (error) {
      toast(error.message || 'Kommentar konnte nicht gemeldet werden');
    } finally {
      submit.disabled = false;
    }
  });

  const openEditForm = (comment, article) => {
    closeInlineComposers();
    const slot = article.querySelector('.real-comment-inline-slot');
    if (!slot) return;

    const form = document.createElement('form');
    form.className = 'real-comment-edit-form';
    form.innerHTML = `
      <textarea maxlength="50000" rows="3">${escapeHtml(comment.text || '')}</textarea>
      <footer>
        <button type="button" data-edit-cancel>Abbrechen</button>
        <button type="submit">Speichern</button>
      </footer>
    `;
    slot.appendChild(form);
    form.querySelector('textarea')?.focus();

    form.querySelector('[data-edit-cancel]')?.addEventListener('click', () => form.remove());
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const textarea = form.querySelector('textarea');
      const body = textarea?.value.trim() || '';
      if (!body || !comment.routes?.update) return;
      const submit = form.querySelector('[type="submit"]');
      submit.disabled = true;

      try {
        await requestJson(comment.routes.update, { method: 'PATCH', json: { body } });
        await refreshComments();
        toast('Kommentar bearbeitet');
      } catch (error) {
        toast(error.message || 'Kommentar konnte nicht bearbeitet werden');
      } finally {
        submit.disabled = false;
      }
    });
  };

  const showCommentMenu = (button, article, comment) => {
    closeCommentMenu();
    const menu = document.createElement('div');
    menu.className = 'real-comment-menu';
    menu.innerHTML = `
      ${comment.viewer?.can_edit ? '<button type="button" data-comment-edit>Bearbeiten</button>' : ''}
      ${comment.viewer?.can_delete ? '<button type="button" class="danger" data-comment-delete>Löschen</button>' : ''}
      ${comment.viewer?.can_report ? '<button type="button" data-comment-report>Melden</button>' : ''}
    `;
    article.appendChild(menu);
    activeMenu = menu;

    menu.querySelector('[data-comment-edit]')?.addEventListener('click', () => {
      closeCommentMenu();
      openEditForm(comment, article);
    });
    menu.querySelector('[data-comment-delete]')?.addEventListener('click', () => {
      closeCommentMenu();
      pendingDeleteComment = comment;
      deleteDialog.hidden = false;
    });
    menu.querySelector('[data-comment-report]')?.addEventListener('click', () => {
      closeCommentMenu();
      pendingReportComment = comment;
      reportDialog.hidden = false;
    });
  };

  window.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;

    const commentButton = target.closest('[data-real-preview-comments]');
    if (commentButton) {
      const article = commentButton.closest('[data-real-feed-post]');
      const postId = Number.parseInt(article?.dataset.realFeedPost || '0', 10);
      if (!postId) return;
      event.preventDefault();
      event.stopImmediatePropagation();
      openComments(commentButton, postId);
      return;
    }

    if (modal.dataset.realPreview !== '2') return;

    const sort = target.closest('#commentsSort');
    if (sort) {
      event.preventDefault();
      event.stopImmediatePropagation();
      newestFirst = !newestFirst;
      sort.childNodes[0].textContent = newestFirst ? 'Neueste ' : 'Relevant ';
      renderComments();
      return;
    }

    const article = target.closest('[data-comment-id]');
    const commentId = Number.parseInt(article?.dataset.commentId || '0', 10);
    const comment = activePost?.comments?.find((item) => Number(item.id) === commentId);

    const likeButton = target.closest('[data-comment-like]');
    if (likeButton && comment) {
      event.preventDefault();
      event.stopImmediatePropagation();
      handleCommentLike(likeButton, comment);
      return;
    }

    const replyButton = target.closest('[data-comment-reply]');
    if (replyButton && comment && article) {
      event.preventDefault();
      event.stopImmediatePropagation();
      openReplyComposer(comment, article);
      return;
    }

    const moreButton = target.closest('[data-comment-more]');
    if (moreButton && comment && article) {
      event.preventDefault();
      event.stopImmediatePropagation();
      showCommentMenu(moreButton, article, comment);
      return;
    }

    const cancelReply = target.closest('[data-reply-cancel]');
    if (cancelReply) {
      event.preventDefault();
      cancelReply.closest('form')?.remove();
      return;
    }

    if (activeMenu && !activeMenu.contains(target) && !target.closest('[data-comment-more]')) closeCommentMenu();
  }, true);

  window.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || modal.dataset.realPreview !== '2') return;

    if (form.id === 'commentsComposer') {
      event.preventDefault();
      event.stopImmediatePropagation();
      const body = input.value.trim();
      if (!body) return;
      const submit = form.querySelector('[type="submit"]');
      submit.disabled = true;

      try {
        await submitComment(body);
        input.value = '';
        input.dispatchEvent(new Event('input', { bubbles: true }));
        toast('Kommentar veröffentlicht');
      } catch (error) {
        toast(error.message || 'Kommentar konnte nicht veröffentlicht werden');
      } finally {
        submit.disabled = false;
      }
      return;
    }

    if (form.classList.contains('real-comment-reply-form')) {
      event.preventDefault();
      event.stopImmediatePropagation();
      const textarea = form.querySelector('textarea');
      const body = textarea?.value.trim() || '';
      const parentId = Number.parseInt(form.dataset.parentId || '0', 10);
      if (!body || !parentId) return;
      const submit = form.querySelector('[type="submit"]');
      submit.disabled = true;

      try {
        await submitComment(body, parentId);
        toast('Antwort veröffentlicht');
      } catch (error) {
        toast(error.message || 'Antwort konnte nicht veröffentlicht werden');
      } finally {
        submit.disabled = false;
      }
    }
  }, true);

  feedScroll?.addEventListener('scroll', closeCommentMenu, { passive: true });
  modal.querySelector('.comments-modal-content')?.addEventListener('scroll', closeCommentMenu, { passive: true });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    if (!deleteDialog.hidden) closeDeleteDialog();
    else if (!reportDialog.hidden) closeReportDialog();
    else closeCommentMenu();
  });
})();
