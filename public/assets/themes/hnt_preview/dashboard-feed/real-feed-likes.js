/* Shared compact viewer for post and comment likes in the live feed and profile.
   The heart keeps toggling the reaction; only the visible number opens the list. */
(() => {
  if (!window.fetch || document.documentElement.dataset.hntLikeViewersReady === '1') return;
  document.documentElement.dataset.hntLikeViewersReady = '1';

  const countSelector = [
    '[data-real-feed-post] .like-button > span',
    '[data-comment-id] .comment-like > span',
    '[data-hnt-preview-post] [data-hnt-simple-like-count]',
    '[data-hnt-comment-item] [data-hnt-simple-like-count]',
  ].join(',');
  const english = (document.documentElement.lang || '').toLowerCase().startsWith('en');
  const numberFormatter = new Intl.NumberFormat(english ? 'en' : 'de');
  let lastTrigger = null;
  let activeRequest = null;
  let annotateFrame = 0;

  const labels = english ? {
    kicker: 'LIKES',
    close: 'Close likes',
    loading: 'Loading likes …',
    emptyTitle: 'No likes yet',
    emptyText: 'Hunters who like this will appear here.',
    errorTitle: 'Could not load likes',
    postText: 'These hunters liked this post.',
    commentText: 'These hunters liked this comment.',
    one: 'Liked by 1 person',
    many: (count) => `Liked by ${numberFormatter.format(count)} people`,
    open: 'See who liked this',
  } : {
    kicker: 'GEFÄLLT MIR',
    close: 'Likes schließen',
    loading: 'Likes werden geladen …',
    emptyTitle: 'Noch keine Likes',
    emptyText: 'Hier erscheinen Hunter, sobald sie liken.',
    errorTitle: 'Likes konnten nicht geladen werden',
    postText: 'Diese Hunter haben den Beitrag geliked.',
    commentText: 'Diese Hunter haben den Kommentar geliked.',
    one: 'Gefällt 1 Person',
    many: (count) => `Gefällt ${numberFormatter.format(count)} Personen`,
    open: 'Anzeigen, wer geliked hat',
  };

  const escapeHtml = (value = '') => String(value).replace(/[&<>"']/g, (character) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  })[character]);

  const numericCount = (node) => {
    const digits = String(node?.textContent || '').replace(/[^0-9]/g, '');
    return Number.parseInt(digits || '0', 10) || 0;
  };

  const targetFor = (trigger) => {
    const comment = trigger.closest('[data-comment-id], [data-hnt-comment-item]');
    if (comment) {
      const id = Number.parseInt(comment.dataset.commentId || comment.dataset.hntCommentItem || '0', 10);
      return id > 0 ? {
        type: 'comment',
        id,
        url: `/feed/comments/${encodeURIComponent(String(id))}/reactions`,
      } : null;
    }

    const post = trigger.closest('[data-real-feed-post], [data-hnt-preview-post]');
    if (post) {
      const id = Number.parseInt(post.dataset.realFeedPost || post.dataset.postId || '0', 10);
      return id > 0 ? {
        type: 'post',
        id,
        url: `/feed/${encodeURIComponent(String(id))}/reactions`,
      } : null;
    }

    return null;
  };

  const ensureModal = () => {
    let modal = document.querySelector('[data-hnt-like-viewers-modal]');
    if (modal) return modal;

    modal = document.createElement('div');
    modal.className = 'hnt-like-viewers-backdrop';
    modal.dataset.hntLikeViewersModal = '1';
    modal.setAttribute('aria-hidden', 'true');
    modal.innerHTML = `
      <button class="hnt-like-viewers-dismiss" type="button" tabindex="-1" aria-label="${escapeHtml(labels.close)}" data-hnt-like-viewers-close></button>
      <section class="hnt-like-viewers-card" role="dialog" aria-modal="true" aria-labelledby="hntLikeViewersTitle">
        <header class="hnt-like-viewers-head">
          <div>
            <span>${escapeHtml(labels.kicker)}</span>
            <h2 id="hntLikeViewersTitle" data-hnt-like-viewers-title></h2>
            <p data-hnt-like-viewers-subtitle></p>
          </div>
          <button class="hnt-like-viewers-close" type="button" aria-label="${escapeHtml(labels.close)}" data-hnt-like-viewers-close>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"></path></svg>
          </button>
        </header>
        <div class="hnt-like-viewers-body" data-hnt-like-viewers-body></div>
      </section>
    `;
    document.body.appendChild(modal);
    return modal;
  };

  const modal = ensureModal();
  const title = modal.querySelector('[data-hnt-like-viewers-title]');
  const subtitle = modal.querySelector('[data-hnt-like-viewers-subtitle]');
  const body = modal.querySelector('[data-hnt-like-viewers-body]');
  const closeButton = modal.querySelector('.hnt-like-viewers-close');

  const setTitle = (total) => {
    const count = Math.max(0, Number(total) || 0);
    if (title) title.textContent = count === 1 ? labels.one : labels.many(count);
  };

  const closeModal = () => {
    if (activeRequest) {
      activeRequest.abort();
      activeRequest = null;
    }
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('hnt-like-viewers-open');
    if (lastTrigger instanceof HTMLElement) lastTrigger.focus({ preventScroll: true });
    lastTrigger = null;
  };

  const renderState = (heading, copy, error = false) => {
    if (!body) return;
    body.innerHTML = `
      <div class="hnt-like-viewers-state${error ? ' is-error' : ''}">
        <span></span>
        <strong>${escapeHtml(heading)}</strong>
        ${copy ? `<small>${escapeHtml(copy)}</small>` : ''}
      </div>
    `;
  };

  const renderUsers = (users) => {
    const list = Array.isArray(users) ? users : [];
    if (!list.length) {
      renderState(labels.emptyTitle, labels.emptyText);
      return;
    }

    body.innerHTML = `<div class="hnt-like-viewers-list">${list.map((user) => {
      const name = user?.name || 'HNT Hunter';
      const username = user?.username || '';
      const reactedAt = user?.reacted_at || '';
      const profileUrl = user?.profile_url || '#';
      const avatar = user?.avatar || '/assets/vikinger/img/default-avatar.svg';
      const meta = [username, reactedAt].filter(Boolean).join(' · ');
      const reaction = user?.reaction_emoji || '♥';

      return `
        <a class="hnt-like-viewers-user" href="${escapeHtml(profileUrl)}">
          <img src="${escapeHtml(avatar)}" alt="${escapeHtml(name)}" loading="lazy">
          <span>
            <strong>${escapeHtml(name)}</strong>
            <small>${escapeHtml(meta)}</small>
          </span>
          <i aria-hidden="true">${escapeHtml(reaction)}</i>
        </a>
      `;
    }).join('')}</div>`;
  };

  const openModal = async (trigger) => {
    const target = targetFor(trigger);
    const visibleCount = numericCount(trigger);
    if (!target || visibleCount <= 0) return;

    if (activeRequest) activeRequest.abort();
    activeRequest = new AbortController();
    lastTrigger = trigger;

    setTitle(visibleCount);
    if (subtitle) subtitle.textContent = target.type === 'comment' ? labels.commentText : labels.postText;
    renderState(labels.loading, '');
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('hnt-like-viewers-open');
    window.setTimeout(() => closeButton?.focus({ preventScroll: true }), 40);

    try {
      const response = await fetch(target.url, {
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        signal: activeRequest.signal,
      });
      let payload = null;
      try { payload = await response.json(); } catch (_) { payload = null; }
      if (!response.ok) throw new Error(payload?.message || labels.errorTitle);

      const total = Number(payload?.total) || 0;
      setTitle(total);
      renderUsers(payload?.users || []);
    } catch (error) {
      if (error?.name === 'AbortError') return;
      renderState(labels.errorTitle, error?.message || '', true);
    } finally {
      activeRequest = null;
    }
  };

  const annotate = (root = document) => {
    const nodes = [];
    if (root instanceof Element && root.matches(countSelector)) nodes.push(root);
    if (root.querySelectorAll) nodes.push(...root.querySelectorAll(countSelector));

    nodes.forEach((node) => {
      const target = targetFor(node);
      if (!target) return;
      const count = numericCount(node);
      node.classList.add('hnt-like-count-trigger');
      node.setAttribute('role', 'button');
      node.setAttribute('tabindex', count > 0 ? '0' : '-1');
      node.setAttribute('aria-disabled', count > 0 ? 'false' : 'true');
      node.setAttribute('aria-label', labels.open);
      node.setAttribute('title', count > 0 ? labels.open : '');
    });
  };

  const scheduleAnnotate = () => {
    if (annotateFrame) return;
    annotateFrame = window.requestAnimationFrame(() => {
      annotateFrame = 0;
      annotate(document);
    });
  };

  document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;

    const close = target.closest('[data-hnt-like-viewers-close]');
    if (close) {
      event.preventDefault();
      closeModal();
      return;
    }

    const trigger = target.closest('.hnt-like-count-trigger');
    if (!trigger) return;
    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();
    openModal(trigger);
  }, true);

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && modal.classList.contains('is-open')) {
      event.preventDefault();
      closeModal();
      return;
    }

    if (event.key !== 'Enter' && event.key !== ' ') return;
    const target = event.target;
    if (!(target instanceof Element) || !target.matches('.hnt-like-count-trigger')) return;
    event.preventDefault();
    event.stopPropagation();
    openModal(target);
  }, true);

  const observer = new MutationObserver(scheduleAnnotate);
  observer.observe(document.body, { childList: true, subtree: true, characterData: true });
  annotate(document);
})();
