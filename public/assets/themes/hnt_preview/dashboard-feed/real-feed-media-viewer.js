/* Media viewer that reuses the live comments modal as its right-hand panel. */
(() => {
  const commentsModal = document.getElementById('commentsModal');
  const commentsClose = document.getElementById('commentsClose');
  const previewEndpoint = `${window.location.pathname}?data=1`;

  if (!commentsModal || !window.fetch) return;

  const state = {
    open: false,
    postId: 0,
    media: [],
    index: 0,
    previousUrl: '',
    touchStartX: null,
  };

  const escapeHtml = (value = '') => String(value).replace(/[&<>"']/g, (character) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  })[character]);

  const commentsOrigin = document.createComment('hnt-comments-modal-origin');
  commentsModal.parentNode?.insertBefore(commentsOrigin, commentsModal);

  const viewer = document.createElement('div');
  viewer.className = 'real-media-viewer';
  viewer.hidden = true;
  viewer.setAttribute('aria-hidden', 'true');
  viewer.innerHTML = `
    <div class="real-media-viewer__backdrop" data-media-viewer-close></div>
    <section class="real-media-viewer__dialog" role="dialog" aria-modal="true" aria-label="Medien und Kommentare">
      <div class="real-media-viewer__stage">
        <header class="real-media-viewer__head">
          <div>
            <strong>HNT.ROCKS</strong>
            <span data-media-viewer-counter>1 / 1</span>
          </div>
          <button class="real-media-viewer__close" type="button" aria-label="Viewer schließen" data-media-viewer-close>×</button>
        </header>
        <div class="real-media-viewer__canvas" data-media-viewer-canvas>
          <button class="real-media-viewer__nav real-media-viewer__nav--prev" type="button" aria-label="Vorheriges Medium" data-media-viewer-prev>‹</button>
          <div class="real-media-viewer__media" data-media-viewer-media></div>
          <button class="real-media-viewer__nav real-media-viewer__nav--next" type="button" aria-label="Nächstes Medium" data-media-viewer-next>›</button>
        </div>
        <div class="real-media-viewer__thumbs" data-media-viewer-thumbs></div>
      </div>
      <aside class="real-media-viewer__side">
        <div class="real-media-viewer__comments-slot" data-media-viewer-comments></div>
      </aside>
    </section>
  `;
  document.body.appendChild(viewer);

  const mediaHost = viewer.querySelector('[data-media-viewer-media]');
  const counter = viewer.querySelector('[data-media-viewer-counter]');
  const thumbs = viewer.querySelector('[data-media-viewer-thumbs]');
  const previousButton = viewer.querySelector('[data-media-viewer-prev]');
  const nextButton = viewer.querySelector('[data-media-viewer-next]');
  const commentsSlot = viewer.querySelector('[data-media-viewer-comments]');
  const canvas = viewer.querySelector('[data-media-viewer-canvas]');

  const requestJson = async (url) => {
    const response = await fetch(url, {
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    let payload = null;
    try {
      payload = await response.json();
    } catch (_) {
      payload = null;
    }

    if (!response.ok) {
      throw new Error(payload?.message || `Request failed with ${response.status}`);
    }

    return payload || {};
  };

  const postData = async (postId) => {
    const payload = await requestJson(`${previewEndpoint}&post_id=${encodeURIComponent(String(postId))}`);
    return payload.post || null;
  };

  const stopCurrentMedia = () => {
    mediaHost?.querySelectorAll('video').forEach((video) => {
      try {
        video.pause();
      } catch (_) {
        // Browser can reject pause while a source is still loading.
      }
    });
  };

  const normalizeIndex = (index) => {
    if (!state.media.length) return 0;
    return (index + state.media.length) % state.media.length;
  };

  const updateUrl = () => {
    if (!state.postId) return;
    const hash = `#post-${state.postId}-media-${state.index + 1}`;
    history.replaceState(null, '', `${window.location.pathname}${window.location.search}${hash}`);
  };

  const renderThumbs = () => {
    if (!thumbs) return;

    thumbs.hidden = state.media.length <= 1;
    thumbs.innerHTML = state.media.map((item, index) => {
      const preview = item.type === 'image'
        ? `<img src="${escapeHtml(item.url)}" alt="" loading="lazy">`
        : '<span>▶</span>';

      return `
        <button class="real-media-viewer__thumb${index === state.index ? ' is-active' : ''}" type="button" data-media-thumb="${index}" aria-label="Medium ${index + 1} öffnen">
          ${preview}
        </button>
      `;
    }).join('');
  };

  const renderCurrent = () => {
    if (!mediaHost || !state.media.length) return;

    stopCurrentMedia();
    state.index = normalizeIndex(state.index);
    const item = state.media[state.index];

    mediaHost.innerHTML = item.type === 'video'
      ? `
        <video controls playsinline autoplay preload="metadata">
          <source src="${escapeHtml(item.url)}" type="${escapeHtml(item.mime || 'video/mp4')}">
        </video>
      `
      : `<img src="${escapeHtml(item.url)}" alt="${escapeHtml(item.alt || 'Medieninhalt')}">`;

    if (counter) counter.textContent = `${state.index + 1} / ${state.media.length}`;
    if (previousButton) previousButton.hidden = state.media.length <= 1;
    if (nextButton) nextButton.hidden = state.media.length <= 1;

    renderThumbs();
    updateUrl();
  };

  const restoreCommentsModal = () => {
    commentsModal.classList.remove('real-media-viewer-hosted');
    commentsModal.classList.remove('is-open');
    commentsModal.setAttribute('aria-hidden', 'true');

    if (commentsOrigin.parentNode) {
      commentsOrigin.parentNode.insertBefore(commentsModal, commentsOrigin.nextSibling);
    }
  };

  const closeViewer = () => {
    if (!state.open) return;

    stopCurrentMedia();
    state.open = false;
    viewer.hidden = true;
    viewer.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('real-media-viewer-open', 'comments-open');
    restoreCommentsModal();

    if (state.previousUrl) {
      history.replaceState(null, '', state.previousUrl);
    }

    state.postId = 0;
    state.media = [];
    state.index = 0;
  };

  const hostCommentsModal = () => {
    if (!commentsSlot) return;
    commentsModal.classList.add('real-media-viewer-hosted', 'is-open');
    commentsModal.setAttribute('aria-hidden', 'false');
    commentsSlot.appendChild(commentsModal);
  };

  const openCommentsForPost = (article) => {
    const commentsButton = article?.querySelector('[data-real-preview-comments]');
    if (!commentsButton) return;

    commentsButton.click();
    window.setTimeout(hostCommentsModal, 0);
  };

  const openViewer = async (article, requestedIndex = 0) => {
    const postId = Number.parseInt(article?.dataset.realFeedPost || '0', 10);
    if (!postId) return;

    try {
      const post = await postData(postId);
      const media = Array.isArray(post?.media)
        ? post.media.filter((item) => item?.url && (item.type === 'image' || item.type === 'video'))
        : [];

      if (!media.length) return;

      state.previousUrl = `${window.location.pathname}${window.location.search}${window.location.hash}`;
      state.open = true;
      state.postId = postId;
      state.media = media;
      state.index = Math.min(Math.max(0, requestedIndex), media.length - 1);

      viewer.hidden = false;
      viewer.setAttribute('aria-hidden', 'false');
      document.body.classList.add('real-media-viewer-open');

      openCommentsForPost(article);
      hostCommentsModal();
      renderCurrent();
      viewer.querySelector('[data-media-viewer-close]')?.focus();
    } catch (error) {
      console.error('HNT media viewer failed', error);
      if (typeof showToast === 'function') showToast('Medium konnte nicht geöffnet werden');
    }
  };

  const openCommentMedia = (item) => {
    const grid = item.closest('.real-comment-media-grid');
    if (!grid || !state.open) return false;

    const nodes = [...grid.querySelectorAll('.real-comment-media-item')];
    const media = nodes.map((node) => {
      const image = node.querySelector('img');
      const videoSource = node.querySelector('video source');
      const href = node.getAttribute('href');

      if (image) {
        return {
          type: 'image',
          url: image.currentSrc || image.src || href || '',
          alt: image.alt || 'Kommentarbild',
          mime: '',
        };
      }

      if (videoSource) {
        return {
          type: 'video',
          url: videoSource.src,
          mime: videoSource.type || 'video/mp4',
          alt: 'Kommentarvideo',
        };
      }

      return null;
    }).filter((entry) => entry?.url);

    if (!media.length) return false;

    state.media = media;
    state.index = Math.max(0, nodes.indexOf(item));
    renderCurrent();
    return true;
  };

  const changeMedia = (direction) => {
    if (state.media.length <= 1) return;
    state.index = normalizeIndex(state.index + direction);
    renderCurrent();
  };

  document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;

    const close = target.closest('[data-media-viewer-close]');
    if (close && state.open) {
      event.preventDefault();
      closeViewer();
      return;
    }

    const previous = target.closest('[data-media-viewer-prev]');
    if (previous && state.open) {
      event.preventDefault();
      changeMedia(-1);
      return;
    }

    const next = target.closest('[data-media-viewer-next]');
    if (next && state.open) {
      event.preventDefault();
      changeMedia(1);
      return;
    }

    const thumb = target.closest('[data-media-thumb]');
    if (thumb && state.open) {
      event.preventDefault();
      state.index = Number.parseInt(thumb.dataset.mediaThumb || '0', 10) || 0;
      renderCurrent();
      return;
    }

    const commentMedia = target.closest('.real-comment-media-item');
    if (commentMedia && state.open && openCommentMedia(commentMedia)) {
      event.preventDefault();
      event.stopPropagation();
      return;
    }

    const mediaItem = target.closest('.real-post-media-item');
    const article = mediaItem?.closest('[data-real-feed-post]');
    if (!mediaItem || !article) return;

    if (mediaItem.classList.contains('real-post-file-item')) return;
    if (target.closest('video') && !event.altKey) return;

    event.preventDefault();
    event.stopPropagation();

    const siblings = [...mediaItem.parentElement.querySelectorAll('.real-post-media-item:not(.real-post-file-item)')];
    const index = Math.max(0, siblings.indexOf(mediaItem));
    openViewer(article, index);
  }, true);

  commentsClose?.addEventListener('click', (event) => {
    if (!state.open) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    closeViewer();
  }, true);

  canvas?.addEventListener('pointerdown', (event) => {
    state.touchStartX = event.clientX;
  }, { passive: true });

  canvas?.addEventListener('pointerup', (event) => {
    if (state.touchStartX === null) return;
    const distance = event.clientX - state.touchStartX;
    state.touchStartX = null;

    if (Math.abs(distance) < 55) return;
    changeMedia(distance > 0 ? -1 : 1);
  }, { passive: true });

  document.addEventListener('keydown', (event) => {
    if (!state.open) return;

    if (event.key === 'Escape') {
      event.preventDefault();
      closeViewer();
    } else if (event.key === 'ArrowLeft') {
      event.preventDefault();
      changeMedia(-1);
    } else if (event.key === 'ArrowRight') {
      event.preventDefault();
      changeMedia(1);
    }
  }, true);
})();
