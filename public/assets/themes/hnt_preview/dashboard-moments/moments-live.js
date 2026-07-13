(() => {
  'use strict';

  const root = document.body;
  if (root?.dataset.page !== 'moments') return;

  const copy = window.HNT_MOMENTS_COPY || {};
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const scroll = document.getElementById('momentsScroll');
  const previous = document.getElementById('previousMoment');
  const next = document.getElementById('nextMoment');
  const position = document.getElementById('momentPosition');
  const dots = document.getElementById('momentDots');
  const filterEmpty = document.getElementById('momentsFilterEmpty');
  const feedButtons = [...document.querySelectorAll('[data-moments-feed]')];
  const allSlides = [...document.querySelectorAll('.moment-slide[data-moment-id]')];

  const modal = document.getElementById('momentsCommentsModal');
  const modalClose = document.getElementById('momentsCommentsClose');
  const modalList = document.getElementById('momentsCommentsList');
  const modalCount = document.getElementById('momentsCommentsCount');
  const modalAvatar = document.getElementById('momentsCommentsAvatar');
  const modalAuthor = document.getElementById('momentsCommentsAuthor');
  const modalMeta = document.getElementById('momentsCommentsMeta');
  const modalExcerpt = document.getElementById('momentsCommentsExcerpt');
  const composer = document.getElementById('momentsCommentsComposer');
  const commentInput = document.getElementById('momentsCommentsInput');
  const commentParent = document.getElementById('momentsCommentParent');
  const commentCounter = document.getElementById('momentsCommentsCounter');

  let visibleSlides = [...allSlides];
  let activeIndex = 0;
  let activeSlide = visibleSlides[0] || null;
  let activeComments = [];
  let wheelLocked = false;
  let dragging = false;
  let dragStartY = 0;
  let dragStartScroll = 0;

  const isEnglish = (document.documentElement.lang || '').toLowerCase().startsWith('en');
  const formatNumber = (value) => new Intl.NumberFormat(document.documentElement.lang || 'de-DE')
    .format(Math.max(0, Number(value) || 0));

  const escapeHtml = (value = '') => String(value).replace(/[&<>"']/g, (character) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
  })[character]);

  const toast = (message) => {
    if (typeof window.showToast === 'function') {
      window.showToast(message);
      return;
    }

    const node = document.getElementById('toast');
    if (!node) return;
    node.textContent = message;
    node.classList.add('show');
    window.clearTimeout(toast.timer);
    toast.timer = window.setTimeout(() => node.classList.remove('show'), 1900);
  };

  const requestJson = async (url, options = {}) => {
    const response = await fetch(url, {
      credentials: 'same-origin',
      cache: 'no-store',
      ...options,
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrf,
        ...(options.headers || {}),
      },
    });

    let payload = {};
    try {
      payload = await response.json();
    } catch (_error) {
      payload = {};
    }

    if (!response.ok) {
      const validation = payload.errors ? Object.values(payload.errors).flat()[0] : null;
      throw new Error(validation || payload.message || copy.error || 'Action failed');
    }

    return payload;
  };

  const parseComments = (slide) => {
    try {
      return JSON.parse(slide?.querySelector('.moment-comments-data')?.textContent || '[]');
    } catch (_error) {
      return [];
    }
  };

  const updateDots = () => {
    if (!dots) return;
    dots.innerHTML = visibleSlides.map((_slide, index) => `<i class="${index === activeIndex ? 'active' : ''}"></i>`).join('');
  };

  const pauseInactiveVideos = () => {
    allSlides.forEach((slide) => {
      const video = slide.querySelector('video');
      if (!video) return;
      if (slide === activeSlide && !slide.classList.contains('is-paused') && !modal?.classList.contains('is-open')) {
        video.play().catch(() => {});
      } else {
        video.pause();
      }
    });
  };

  const setActive = (index) => {
    if (!visibleSlides.length) {
      activeIndex = 0;
      activeSlide = null;
      if (position) position.textContent = '0 / 0';
      if (previous) previous.disabled = true;
      if (next) next.disabled = true;
      updateDots();
      pauseInactiveVideos();
      return;
    }

    activeIndex = Math.max(0, Math.min(visibleSlides.length - 1, index));
    activeSlide = visibleSlides[activeIndex];
    allSlides.forEach((slide) => slide.classList.toggle('is-active', slide === activeSlide));

    if (position) position.textContent = `${activeIndex + 1} / ${visibleSlides.length}`;
    if (previous) previous.disabled = activeIndex === 0;
    if (next) next.disabled = activeIndex === visibleSlides.length - 1;
    updateDots();
    pauseInactiveVideos();
  };

  const goTo = (index, behavior = 'smooth') => {
    const target = visibleSlides[Math.max(0, Math.min(visibleSlides.length - 1, index))];
    if (!target || !scroll) return;
    scroll.scrollTo({ top: target.offsetTop, behavior });
    window.setTimeout(() => setActive(visibleSlides.indexOf(target)), behavior === 'smooth' ? 360 : 0);
  };

  const applyFeed = (mode) => {
    feedButtons.forEach((button) => button.classList.toggle('active', button.dataset.momentsFeed === mode));
    allSlides.forEach((slide) => {
      slide.hidden = mode === 'following' && slide.dataset.following !== '1';
    });

    visibleSlides = allSlides.filter((slide) => !slide.hidden);
    if (filterEmpty) filterEmpty.hidden = visibleSlides.length > 0;
    if (scroll) scroll.scrollTop = 0;
    setActive(0);
  };

  feedButtons.forEach((button) => button.addEventListener('click', () => applyFeed(button.dataset.momentsFeed || 'foryou')));
  previous?.addEventListener('click', () => goTo(activeIndex - 1));
  next?.addEventListener('click', () => goTo(activeIndex + 1));

  let scrollTimer;
  scroll?.addEventListener('scroll', () => {
    window.clearTimeout(scrollTimer);
    scrollTimer = window.setTimeout(() => {
      if (!visibleSlides.length) return;
      const nearest = visibleSlides.reduce((best, slide, index) => {
        const distance = Math.abs(slide.offsetTop - scroll.scrollTop);
        return distance < best.distance ? { index, distance } : best;
      }, { index: 0, distance: Number.POSITIVE_INFINITY });
      setActive(nearest.index);
    }, 70);
  }, { passive: true });

  scroll?.addEventListener('wheel', (event) => {
    if (Math.abs(event.deltaY) < 18 || wheelLocked || modal?.classList.contains('is-open')) return;
    event.preventDefault();
    wheelLocked = true;
    goTo(activeIndex + (event.deltaY > 0 ? 1 : -1));
    window.setTimeout(() => { wheelLocked = false; }, 620);
  }, { passive: false });

  scroll?.addEventListener('pointerdown', (event) => {
    if (event.pointerType !== 'mouse' || event.target.closest('button,a,input,textarea,select')) return;
    dragging = true;
    dragStartY = event.clientY;
    dragStartScroll = scroll.scrollTop;
    scroll.classList.add('dragging');
    scroll.setPointerCapture(event.pointerId);
  });

  scroll?.addEventListener('pointermove', (event) => {
    if (dragging) scroll.scrollTop = dragStartScroll - (event.clientY - dragStartY);
  });

  const endDrag = () => {
    if (!dragging) return;
    dragging = false;
    scroll?.classList.remove('dragging');
    goTo(activeIndex);
  };

  scroll?.addEventListener('pointerup', endDrag);
  scroll?.addEventListener('pointercancel', endDrag);

  allSlides.forEach((slide) => {
    const video = slide.querySelector('video');
    const playButton = slide.querySelector('.moment-play-toggle');
    const soundButton = slide.querySelector('.moment-sound-toggle');
    const progress = slide.querySelector('.moment-progress i');

    playButton?.addEventListener('click', () => {
      if (!video) return;
      const paused = !video.paused;
      if (paused) video.pause(); else video.play().catch(() => {});
      slide.classList.toggle('is-paused', paused);
      playButton.querySelector('use')?.setAttribute('href', paused ? '#i-play' : '#i-pause');
      playButton.setAttribute('aria-label', paused ? (copy.play || 'Play') : (copy.pause || 'Pause'));
    });

    soundButton?.addEventListener('click', () => {
      if (!video) return;
      video.muted = !video.muted;
      soundButton.querySelector('use')?.setAttribute('href', video.muted ? '#i-volume' : '#i-volume-off');
      soundButton.setAttribute('aria-label', video.muted ? (copy.unmute || 'Unmute') : (copy.mute || 'Mute'));
    });

    video?.addEventListener('timeupdate', () => {
      if (!progress || !Number.isFinite(video.duration) || video.duration <= 0) return;
      progress.style.width = `${Math.min(100, Math.max(0, (video.currentTime / video.duration) * 100))}%`;
    });
  });

  document.addEventListener('click', async (event) => {
    const likeButton = event.target.closest('.moment-live-like');
    if (likeButton) {
      const slide = likeButton.closest('.moment-slide');
      const url = slide?.dataset.likeUrl;
      if (!url || likeButton.classList.contains('is-busy')) return;
      likeButton.classList.add('is-busy');
      try {
        const payload = await requestJson(url, { method: 'POST' });
        likeButton.classList.toggle('liked', Boolean(payload.liked));
        const counter = likeButton.querySelector('[data-live-like-count]');
        if (counter) counter.textContent = formatNumber(payload.count);
      } catch (error) {
        toast(error.message || copy.error);
      } finally {
        likeButton.classList.remove('is-busy');
      }
      return;
    }

    const saveButton = event.target.closest('.moment-live-save');
    if (saveButton) {
      const slide = saveButton.closest('.moment-slide');
      const url = slide?.dataset.bookmarkUrl;
      if (!url || saveButton.classList.contains('is-busy')) return;
      saveButton.classList.add('is-busy');
      try {
        const payload = await requestJson(url, { method: 'POST' });
        saveButton.classList.toggle('saved', Boolean(payload.saved));
        const label = saveButton.querySelector('[data-live-save-label]');
        if (label) label.textContent = payload.saved ? (copy.saved || 'Saved') : (copy.save || 'Save');
        saveButton.setAttribute('aria-label', label?.textContent || 'Save');
      } catch (error) {
        toast(error.message || copy.error);
      } finally {
        saveButton.classList.remove('is-busy');
      }
      return;
    }

    const shareButton = event.target.closest('.moment-live-share');
    if (shareButton) {
      const slide = shareButton.closest('.moment-slide');
      const url = new URL(slide?.dataset.shareUrl || window.location.href, window.location.origin).href;
      const title = slide?.dataset.caption || 'HNT Moment';
      try {
        if (navigator.share) {
          await navigator.share({ title, url });
        } else {
          await navigator.clipboard.writeText(url);
          toast(copy.copied || 'Link copied');
        }
      } catch (error) {
        if (error?.name !== 'AbortError') toast(copy.error || 'Action failed');
      }
      return;
    }

    const commentButton = event.target.closest('.moment-live-comments');
    if (commentButton) {
      openComments(commentButton.closest('.moment-slide'));
      return;
    }

    const friendButton = event.target.closest('.moment-follow-button[data-friend-url]');
    if (friendButton) {
      const url = friendButton.dataset.friendUrl;
      if (!url || friendButton.classList.contains('is-busy')) return;
      friendButton.classList.add('is-busy');
      try {
        await fetch(url, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        });
        friendButton.classList.remove('is-busy');
        friendButton.classList.add('is-requested');
        friendButton.disabled = true;
        friendButton.querySelector('use')?.setAttribute('href', '#i-check');
        friendButton.setAttribute('aria-label', copy.requested || 'Request sent');
        toast(copy.requested || 'Request sent');
      } catch (_error) {
        friendButton.classList.remove('is-busy');
        toast(copy.error || 'Action failed');
      }
    }
  });

  const countLabel = (count) => isEnglish
    ? `${count} ${count === 1 ? 'comment' : 'comments'}`
    : `${count} ${count === 1 ? 'Kommentar' : 'Kommentare'}`;

  const renderCommentItem = (comment) => `
    <article class="comment-item" data-comment-id="${Number(comment.id) || 0}">
      <img class="comment-avatar" src="${escapeHtml(comment.author?.avatar)}" alt="${escapeHtml(comment.author?.name)}">
      <div class="comment-bubble">
        <div class="comment-head"><strong>${escapeHtml(comment.author?.name)}</strong><span>${escapeHtml(comment.author?.handle)} · ${escapeHtml(comment.created_at)}</span></div>
        <p>${escapeHtml(comment.body)}</p>
        <div class="comment-actions">
          <button class="comment-live-like${comment.liked ? ' is-liked' : ''}" type="button" data-url="${escapeHtml(comment.reaction_url)}"><svg><use href="#i-heart"></use></svg><span>${formatNumber(comment.likes_count)}</span></button>
          <button class="comment-live-reply" type="button" data-parent-id="${Number(comment.parent_id || comment.id)}" data-handle="${escapeHtml(comment.author?.handle)}"><svg><use href="#i-reply"></use></svg><span>${escapeHtml(copy.reply || 'Reply')}</span></button>
        </div>
      </div>
    </article>`;

  const renderComments = () => {
    if (!modalList) return;
    if (!activeComments.length) {
      modalList.innerHTML = `<div class="comments-empty-live">${escapeHtml(copy.no_comments || 'No comments yet.')}</div>`;
      return;
    }

    modalList.innerHTML = activeComments.map((comment) => `
      <div class="real-comment-thread" data-thread-id="${Number(comment.id)}">
        ${renderCommentItem(comment)}
        ${Array.isArray(comment.replies) && comment.replies.length
          ? `<div class="real-comment-replies">${comment.replies.map(renderCommentItem).join('')}</div>`
          : ''}
      </div>`).join('');
  };

  function openComments(slide) {
    if (!slide || !modal) return;
    activeSlide = slide;
    activeComments = parseComments(slide);
    const count = Number(slide.querySelector('[data-live-comment-count]')?.textContent?.replace(/\D/g, '') || activeComments.length);
    if (modalCount) modalCount.textContent = countLabel(count);
    if (modalAvatar) modalAvatar.src = slide.dataset.authorAvatar || modalAvatar.src;
    if (modalAvatar) modalAvatar.alt = slide.dataset.authorName || '';
    if (modalAuthor) modalAuthor.textContent = slide.dataset.authorName || 'HNT Hunter';
    if (modalMeta) modalMeta.textContent = `${slide.dataset.authorHandle || '@hunter'} · ${copy.published || ''}`;
    if (modalExcerpt) modalExcerpt.textContent = slide.dataset.caption || slide.dataset.description || '';
    renderComments();
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('comments-open');
    pauseInactiveVideos();
    window.setTimeout(() => commentInput?.focus(), 100);
  }

  const closeComments = () => {
    modal?.classList.remove('is-open');
    modal?.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('comments-open');
    if (commentParent) commentParent.value = '';
    composer?.classList.remove('is-replying');
    pauseInactiveVideos();
  };

  modalClose?.addEventListener('click', closeComments);
  modal?.addEventListener('click', (event) => {
    if (event.target === modal) closeComments();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && modal?.classList.contains('is-open')) closeComments();
  });

  modalList?.addEventListener('click', async (event) => {
    const likeButton = event.target.closest('.comment-live-like');
    if (likeButton) {
      const url = likeButton.dataset.url;
      if (!url || likeButton.disabled) return;
      likeButton.disabled = true;
      try {
        const payload = await requestJson(url, { method: 'POST' });
        likeButton.classList.toggle('is-liked', Boolean(payload.liked));
        const count = likeButton.querySelector('span');
        if (count) count.textContent = formatNumber(payload.count);
      } catch (error) {
        toast(error.message || copy.error);
      } finally {
        likeButton.disabled = false;
      }
      return;
    }

    const replyButton = event.target.closest('.comment-live-reply');
    if (replyButton && commentInput && commentParent) {
      commentParent.value = replyButton.dataset.parentId || '';
      commentInput.value = `${replyButton.dataset.handle || ''} `;
      commentInput.dispatchEvent(new Event('input'));
      composer?.classList.add('is-replying');
      commentInput.focus();
    }
  });

  commentInput?.addEventListener('input', () => {
    if (commentCounter) commentCounter.textContent = `${commentInput.value.length}/1000`;
    commentInput.style.height = 'auto';
    commentInput.style.height = `${Math.min(commentInput.scrollHeight, 130)}px`;
  });

  composer?.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!activeSlide || !commentInput) return;
    const body = commentInput.value.trim();
    if (!body) return;

    const submit = composer.querySelector('.comments-send');
    submit?.setAttribute('disabled', 'disabled');

    try {
      const payload = await requestJson(activeSlide.dataset.commentUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ body, parent_id: commentParent?.value || null }),
      });

      const comment = payload.comment;
      if (comment.parent_id) {
        const parent = activeComments.find((item) => Number(item.id) === Number(comment.parent_id));
        if (parent) {
          parent.replies = Array.isArray(parent.replies) ? parent.replies : [];
          parent.replies.push(comment);
        } else {
          activeComments.unshift({ ...comment, parent_id: null, replies: [] });
        }
      } else {
        activeComments.unshift({ ...comment, replies: [] });
      }

      const dataNode = activeSlide.querySelector('.moment-comments-data');
      if (dataNode) dataNode.textContent = JSON.stringify(activeComments);
      const visibleCount = activeSlide.querySelector('[data-live-comment-count]');
      if (visibleCount) visibleCount.textContent = formatNumber(payload.count);
      if (modalCount) modalCount.textContent = countLabel(payload.count);

      commentInput.value = '';
      commentInput.dispatchEvent(new Event('input'));
      if (commentParent) commentParent.value = '';
      composer.classList.remove('is-replying');
      renderComments();
    } catch (error) {
      toast(error.message || copy.error);
    } finally {
      submit?.removeAttribute('disabled');
    }
  });

  setActive(0);
})();
