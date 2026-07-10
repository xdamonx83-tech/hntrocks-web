/* Live actions + endless scrolling for the isolated dashboard feed preview.
   Loaded before real-feed.js so capture handlers can replace demo actions
   without changing the 1:1 template markup. */
(() => {
  const feedList = document.querySelector('.post-list');
  const feedScroll = document.getElementById('feedScroll');
  const tabs = [...document.querySelectorAll('.feed-tabs > button:not(.compose-button)')];
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

  if (!feedList || !feedScroll || !window.fetch) return;

  const endpoint = `${window.location.pathname}?data=1`;
  const state = {
    mode: 'for-you',
    nextPage: null,
    hasMore: false,
    loading: false,
    requestId: 0,
    posts: new Map(),
  };

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

  const formatCount = (value) => new Intl.NumberFormat(document.documentElement.lang || 'de')
    .format(Number.parseInt(value, 10) || 0);

  const requestJson = async (url, { method = 'GET', json = null, formData = null } = {}) => {
    const headers = {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    };

    if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken;

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

  const routeFor = (postId, action) => {
    const id = encodeURIComponent(String(postId));
    const routes = {
      reaction: `/feed/${id}/reaction`,
      bookmark: `/feed/${id}/bookmark`,
      poll: `/feed/${id}/poll/vote`,
      comments: `/feed/${id}/comments`,
      delete: `/feed/${id}`,
      permalink: `/feed/posts/${id}`,
    };
    return routes[action] || '#';
  };

  const normalizePost = (post) => ({
    ...post,
    routes: {
      reaction: routeFor(post.id, 'reaction'),
      bookmark: routeFor(post.id, 'bookmark'),
      poll: routeFor(post.id, 'poll'),
      comments: routeFor(post.id, 'comments'),
      delete: routeFor(post.id, 'delete'),
      ...(post.routes || {}),
    },
  });

  const postForArticle = (article) => {
    const id = Number.parseInt(article?.dataset.realFeedPost || '0', 10);
    if (!id) return null;

    return state.posts.get(id) || normalizePost({
      id,
      permalink: article.dataset.realPermalink || routeFor(id, 'permalink'),
      counts: {},
      viewer: {},
      comments: [],
    });
  };

  const renderMedia = (media = [], permalink = '#') => {
    if (!Array.isArray(media) || media.length === 0) return '';

    const visible = media.slice(0, 4);
    const items = visible.map((item, index) => {
      const extra = index === visible.length - 1 && media.length > visible.length
        ? `<span class="real-post-media-more">+${media.length - visible.length}</span>`
        : '';

      if (item.type === 'image') {
        return `
          <a class="real-post-media-item" href="${escapeHtml(permalink)}" aria-label="Beitrag öffnen">
            <img src="${escapeHtml(item.url)}" alt="${escapeHtml(item.alt || '')}" loading="lazy">
            ${extra}
          </a>
        `;
      }

      if (item.type === 'video') {
        return `
          <div class="real-post-media-item real-post-video-item">
            <video controls muted playsinline preload="metadata">
              <source src="${escapeHtml(item.url)}" type="${escapeHtml(item.mime || 'video/mp4')}">
            </video>
            ${extra}
          </div>
        `;
      }

      return `
        <a class="real-post-media-item real-post-file-item" href="${escapeHtml(item.url)}" target="_blank" rel="noopener">
          <span>Datei öffnen</span>
          ${extra}
        </a>
      `;
    }).join('');

    return `<div class="real-post-media-grid real-post-media-count-${Math.min(media.length, 4)}">${items}</div>`;
  };

  const renderPoll = (poll) => {
    if (!poll || !Array.isArray(poll.options) || poll.options.length === 0) return '';

    const options = poll.options.map((option) => `
      <button type="button"
              class="${option.selected ? 'selected' : ''}"
              data-real-poll-option
              data-option-id="${Number(option.id)}"
              style="--poll:${Number(option.percent) || 0}%">
        <span>${escapeHtml(option.body)}</span>
        <b>${formatCount(option.percent)}%</b>
      </button>
    `).join('');

    return `
      <div class="poll real-feed-poll">
        <strong class="real-feed-poll-question">${escapeHtml(poll.question || 'Community-Umfrage')}</strong>
        ${options}
        <small>${formatCount(poll.total_votes)} Stimmen</small>
      </div>
    `;
  };

  const createPostArticle = (rawPost) => {
    const post = normalizePost(rawPost);
    state.posts.set(Number(post.id), post);

    const article = document.createElement('article');
    article.className = 'social-post real-feed-post';
    article.dataset.realFeedPost = String(post.id);
    article.dataset.realPermalink = post.permalink || routeFor(post.id, 'permalink');

    const body = post.body
      ? `<p>${escapeHtml(post.body).replace(/\n/g, '<br>')}</p>`
      : '';
    const metaSuffix = post.team?.name || post.visibility || 'Öffentlich';
    const pinned = post.is_pinned ? '<span class="real-feed-pinned">Angeheftet</span>' : '';

    article.innerHTML = `
      <header class="post-head">
        <a class="real-feed-author-avatar" href="${escapeHtml(post.author?.profile_url || '#')}">
          <img alt="${escapeHtml(post.author?.name || 'HNT Hunter')}" src="${escapeHtml(post.author?.avatar || '')}">
        </a>
        <div class="post-author">
          <a href="${escapeHtml(post.author?.profile_url || '#')}"><strong>${escapeHtml(post.author?.name || 'HNT Hunter')}</strong></a>
          <span>${escapeHtml(post.author?.handle || '@hunter')} · ${escapeHtml(post.created_at || 'gerade eben')} · ${escapeHtml(metaSuffix)}</span>
        </div>
        ${pinned}
        <span class="post-badge ${escapeHtml(post.badge_class || 'discussion')}">${escapeHtml(post.badge || 'Beitrag')}</span>
        <button class="post-more" type="button" data-real-post-more aria-label="Beitragsoptionen"><svg><use href="#i-more"></use></svg></button>
      </header>
      <div class="post-body">
        ${body}
        ${renderMedia(post.media, post.permalink)}
        ${renderPoll(post.poll)}
      </div>
      <footer class="post-actions">
        <button class="like-button ${post.viewer?.reacted ? 'liked' : ''}" type="button" data-real-preview-like>
          <svg><use href="#i-heart"></use></svg><span>${formatCount(post.counts?.reactions)}</span>
        </button>
        <button aria-label="Kommentare öffnen" class="comment-button" type="button" data-real-preview-comments>
          <svg><use href="#i-comment"></use></svg><span>${formatCount(post.counts?.comments)}</span>
        </button>
        <button type="button" data-real-preview-share>
          <svg><use href="#i-share"></use></svg><span>Teilen</span>
        </button>
        <button class="save-button ${post.viewer?.bookmarked ? 'saved' : ''}" type="button" data-real-preview-save aria-label="Speichern">
          <svg><use href="#i-bookmark"></use></svg>
        </button>
      </footer>
    `;

    return article;
  };

  const annotateArticle = (article) => {
    const post = postForArticle(article);
    if (!post) return;

    article.dataset.realPermalink = post.permalink || article.dataset.realPermalink || '#';
    const pollButtons = [...article.querySelectorAll('[data-real-poll-option]')];
    const options = post.poll?.options || [];

    pollButtons.forEach((button, index) => {
      const option = options[index];
      if (!option) return;
      button.dataset.optionId = String(option.id);
      button.style.setProperty('--poll', `${Number(option.percent) || 0}%`);
      button.classList.toggle('selected', Boolean(option.selected));
    });
  };

  const annotateAll = () => {
    document.querySelectorAll('[data-real-feed-post]').forEach(annotateArticle);
  };

  const applyPayload = (payload, { append = false } = {}) => {
    (payload.posts || []).forEach((post) => state.posts.set(Number(post.id), normalizePost(post)));

    state.hasMore = Boolean(payload.pagination?.has_more);
    state.nextPage = payload.pagination?.next_page || null;
    loader.hidden = !state.hasMore;

    if (append && Array.isArray(payload.posts)) {
      const fragment = document.createDocumentFragment();
      payload.posts.forEach((post) => {
        if (feedList.querySelector(`[data-real-feed-post="${Number(post.id)}"]`)) return;
        fragment.appendChild(createPostArticle(post));
      });
      feedList.insertBefore(fragment, loader);
    }

    annotateAll();
  };

  const loadPage = async (page, { append = false, mode = state.mode } = {}) => {
    const requestId = ++state.requestId;
    const url = `${endpoint}&mode=${encodeURIComponent(mode)}&page=${encodeURIComponent(page)}`;
    const payload = await requestJson(url);

    if (requestId !== state.requestId || mode !== state.mode) return null;
    applyPayload(payload, { append });
    return payload;
  };

  const bootstrapMode = async (mode) => {
    state.mode = mode;
    state.nextPage = null;
    state.hasMore = false;
    state.posts.clear();
    loader.hidden = true;

    try {
      const payload = await loadPage(1, { append: false, mode });
      if (!payload) return;

      window.setTimeout(() => {
        if (!feedList.querySelector('[data-real-feed-post]') && payload.posts?.length) {
          const fragment = document.createDocumentFragment();
          payload.posts.forEach((post) => fragment.appendChild(createPostArticle(post)));
          feedList.insertBefore(fragment, loader);
        }
        annotateAll();
      }, 450);
    } catch (error) {
      console.error('HNT feed metadata bootstrap failed', error);
    }
  };

  const loadMore = async () => {
    if (state.loading || !state.hasMore || !state.nextPage) return;

    state.loading = true;
    loader.hidden = false;
    loader.classList.add('is-loading');

    try {
      await loadPage(state.nextPage, { append: true, mode: state.mode });
    } catch (error) {
      console.error('HNT endless feed failed', error);
      toast('Weitere Beiträge konnten nicht geladen werden');
    } finally {
      state.loading = false;
      loader.classList.remove('is-loading');
    }
  };

  const updatePollDom = (article, post) => {
    if (!post.poll) return;

    article.querySelectorAll('[data-real-poll-option]').forEach((button) => {
      const optionId = Number.parseInt(button.dataset.optionId || '0', 10);
      const option = post.poll.options.find((item) => Number(item.id) === optionId);
      if (!option) return;

      button.classList.toggle('selected', Boolean(option.selected));
      button.style.setProperty('--poll', `${Number(option.percent) || 0}%`);
      const percent = button.querySelector('b');
      if (percent) percent.textContent = `${formatCount(option.percent)}%`;
    });

    const total = article.querySelector('.real-feed-poll > small');
    if (total) total.textContent = `${formatCount(post.poll.total_votes)} Stimmen`;
  };

  const handleLike = async (button, article, post) => {
    button.disabled = true;
    try {
      const payload = await requestJson(post.routes.reaction, {
        method: 'POST',
        json: { type: 'like' },
      });
      button.classList.toggle('liked', Boolean(payload.reacted));
      const counter = button.querySelector('span');
      if (counter) counter.textContent = formatCount(payload.count);
      post.viewer = { ...(post.viewer || {}), reacted: Boolean(payload.reacted), reaction_type: payload.type || null };
      post.counts = { ...(post.counts || {}), reactions: Number(payload.count) || 0 };
    } catch (error) {
      toast(error.message || 'Reaktion konnte nicht gespeichert werden');
    } finally {
      button.disabled = false;
    }
  };

  const handleBookmark = async (button, post) => {
    button.disabled = true;
    try {
      const payload = await requestJson(post.routes.bookmark, { method: 'POST', json: {} });
      button.classList.toggle('saved', Boolean(payload.bookmarked));
      post.viewer = { ...(post.viewer || {}), bookmarked: Boolean(payload.bookmarked) };
      post.counts = { ...(post.counts || {}), bookmarks: Number(payload.count) || 0 };
      toast(payload.message || (payload.bookmarked ? 'Gespeichert' : 'Aus Gespeichert entfernt'));
    } catch (error) {
      toast(error.message || 'Speichern war nicht möglich');
    } finally {
      button.disabled = false;
    }
  };

  const handlePollVote = async (button, article, post) => {
    const optionId = Number.parseInt(button.dataset.optionId || '0', 10);
    if (!optionId || !post.poll) return;

    button.disabled = true;
    try {
      await requestJson(post.routes.poll, {
        method: 'POST',
        json: { poll_option_id: optionId },
      });

      const oldOption = post.poll.options.find((option) => option.selected);
      const newOption = post.poll.options.find((option) => Number(option.id) === optionId);
      if (!newOption) return;

      if (!oldOption) {
        post.poll.total_votes = Number(post.poll.total_votes || 0) + 1;
      } else if (Number(oldOption.id) !== optionId) {
        oldOption.votes = Math.max(0, Number(oldOption.votes || 0) - 1);
      }

      if (!oldOption || Number(oldOption.id) !== optionId) {
        newOption.votes = Number(newOption.votes || 0) + 1;
      }

      post.poll.options.forEach((option) => {
        option.selected = Number(option.id) === optionId;
        option.percent = post.poll.total_votes > 0
          ? Math.round((Number(option.votes || 0) / post.poll.total_votes) * 100)
          : 0;
      });

      updatePollDom(article, post);
      toast('Stimme gespeichert');
    } catch (error) {
      toast(error.message || 'Abstimmung war nicht möglich');
    } finally {
      button.disabled = false;
    }
  };

  const openComments = (button, post) => {
    const modal = document.getElementById('commentsModal');
    const input = document.getElementById('commentsInput');
    if (!modal) return;

    modal.dataset.realPreview = '1';
    modal.dataset.realPostId = String(post.id);

    if (typeof activeCommentButton !== 'undefined') activeCommentButton = button;
    if (typeof activeCommentData !== 'undefined') {
      activeCommentData = (post.comments || []).map((comment) => ({
        name: comment.name,
        handle: comment.handle,
        time: comment.time,
        avatar: comment.avatar,
        text: comment.text,
        likes: comment.likes,
        reply: false,
      }));
    }
    if (typeof commentsSortNewest !== 'undefined') commentsSortNewest = false;

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

    if (typeof updateModalCommentCount === 'function') {
      updateModalCommentCount(post.counts?.comments || post.comments?.length || 0);
    }
    if (typeof renderModalComments === 'function') renderModalComments();

    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('comments-open');
    window.setTimeout(() => input?.focus(), 120);
  };

  const submitComment = async (form) => {
    const modal = document.getElementById('commentsModal');
    const input = document.getElementById('commentsInput');
    const postId = Number.parseInt(modal?.dataset.realPostId || '0', 10);
    const post = state.posts.get(postId);
    const body = input?.value.trim() || '';

    if (!post || !body) {
      if (!body) toast('Schreibe zuerst einen Kommentar');
      return;
    }

    const submit = form.querySelector('[type="submit"]');
    if (submit) submit.disabled = true;

    try {
      const formData = new FormData();
      formData.append('body', body);
      const payload = await requestJson(post.routes.comments, { method: 'POST', formData });
      const comment = payload.comment || {};
      const normalized = {
        name: comment.user?.name || 'HNT Hunter',
        handle: '',
        time: comment.created_at_label || 'gerade eben',
        avatar: comment.user?.avatar_url || '',
        text: comment.body || body,
        likes: Number(comment.reaction_count || 0),
        reply: false,
      };

      post.comments = [normalized, ...(post.comments || [])];
      post.counts = {
        ...(post.counts || {}),
        comments: Number(post.counts?.comments || 0) + Number(payload.comment_count_delta || 1),
      };

      if (typeof activeCommentData !== 'undefined') activeCommentData = post.comments.map((item) => ({ ...item }));
      if (typeof renderModalComments === 'function') renderModalComments();
      if (typeof updateModalCommentCount === 'function') updateModalCommentCount(post.counts.comments);

      const article = feedList.querySelector(`[data-real-feed-post="${postId}"]`);
      const count = article?.querySelector('[data-real-preview-comments] span');
      if (count) count.textContent = formatCount(post.counts.comments);

      if (input) {
        input.value = '';
        input.dispatchEvent(new Event('input', { bubbles: true }));
      }
      toast('Kommentar veröffentlicht');
    } catch (error) {
      toast(error.message || 'Kommentar konnte nicht veröffentlicht werden');
    } finally {
      if (submit) submit.disabled = false;
    }
  };

  const sharePost = async (post) => {
    const url = post.permalink || routeFor(post.id, 'permalink');
    try {
      if (navigator.share) {
        await navigator.share({ title: `${post.author?.name || 'HNT Hunter'} auf HNT.rocks`, url });
      } else if (navigator.clipboard) {
        await navigator.clipboard.writeText(url);
        toast('Link kopiert');
      }
    } catch (error) {
      if (error?.name !== 'AbortError') toast('Teilen war nicht möglich');
    }
  };

  let openMenu = null;
  const closeMenu = () => {
    openMenu?.remove();
    openMenu = null;
  };

  const showPostMenu = (button, article, post) => {
    closeMenu();
    const rect = button.getBoundingClientRect();
    const menu = document.createElement('div');
    menu.className = 'real-feed-menu';
    menu.style.top = `${Math.min(window.innerHeight - 180, rect.bottom + 6)}px`;
    menu.style.left = `${Math.max(12, rect.right - 180)}px`;
    menu.innerHTML = `
      <button type="button" data-menu-open>Beitrag öffnen</button>
      <button type="button" data-menu-copy>Link kopieren</button>
      ${post.viewer?.can_delete ? '<button type="button" class="danger" data-menu-delete>Beitrag löschen</button>' : ''}
    `;

    menu.querySelector('[data-menu-open]')?.addEventListener('click', () => {
      window.location.href = post.permalink || routeFor(post.id, 'permalink');
    });
    menu.querySelector('[data-menu-copy]')?.addEventListener('click', async () => {
      await navigator.clipboard?.writeText(post.permalink || routeFor(post.id, 'permalink'));
      toast('Link kopiert');
      closeMenu();
    });
    menu.querySelector('[data-menu-delete]')?.addEventListener('click', async () => {
      if (!window.confirm('Diesen Beitrag wirklich löschen?')) return;
      try {
        await requestJson(post.routes.delete, { method: 'DELETE', json: {} });
        state.posts.delete(Number(post.id));
        article.remove();
        closeMenu();
        toast('Beitrag gelöscht');
      } catch (error) {
        toast(error.message || 'Beitrag konnte nicht gelöscht werden');
      }
    });

    document.body.appendChild(menu);
    openMenu = menu;
  };

  const loader = document.createElement('div');
  loader.className = 'real-feed-loader';
  loader.hidden = true;
  loader.innerHTML = '<span></span><b>Weitere Beiträge werden geladen</b>';
  feedList.appendChild(loader);

  const observer = new MutationObserver(() => annotateAll());
  observer.observe(feedList, { childList: true });

  const intersection = new IntersectionObserver((entries) => {
    if (entries.some((entry) => entry.isIntersecting)) loadMore();
  }, { root: feedScroll, rootMargin: '900px 0px', threshold: 0.01 });
  intersection.observe(loader);

  tabs.forEach((button, index) => {
    button.addEventListener('click', () => {
      closeMenu();
      bootstrapMode(index === 1 ? 'following' : 'for-you');
    }, true);
  });

  document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;

    if (openMenu && !openMenu.contains(target) && !target.closest('[data-real-post-more]')) closeMenu();

    const article = target.closest('[data-real-feed-post]');
    if (!article) return;
    const post = postForArticle(article);
    if (!post) return;

    const like = target.closest('[data-real-preview-like]');
    if (like) {
      event.preventDefault();
      event.stopImmediatePropagation();
      handleLike(like, article, post);
      return;
    }

    const save = target.closest('[data-real-preview-save]');
    if (save) {
      event.preventDefault();
      event.stopImmediatePropagation();
      handleBookmark(save, post);
      return;
    }

    const pollOption = target.closest('[data-real-poll-option]');
    if (pollOption) {
      event.preventDefault();
      event.stopImmediatePropagation();
      handlePollVote(pollOption, article, post);
      return;
    }

    const comments = target.closest('[data-real-preview-comments]');
    if (comments) {
      event.preventDefault();
      event.stopImmediatePropagation();
      openComments(comments, post);
      return;
    }

    const share = target.closest('[data-real-preview-share]');
    if (share) {
      event.preventDefault();
      event.stopImmediatePropagation();
      sharePost(post);
      return;
    }

    const more = target.closest('[data-real-post-more]');
    if (more) {
      event.preventDefault();
      event.stopImmediatePropagation();
      showPostMenu(more, article, post);
    }
  }, true);

  document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || form.id !== 'commentsComposer') return;
    const modal = document.getElementById('commentsModal');
    if (modal?.dataset.realPreview !== '1') return;

    event.preventDefault();
    event.stopImmediatePropagation();
    submitComment(form);
  }, true);

  bootstrapMode('for-you');
})();
