/* Comment media bridge for the isolated dashboard feed preview. */
(() => {
  const modal = document.getElementById('commentsModal');
  const list = document.getElementById('commentsList');

  if (!modal || !list || !window.fetch) return;

  let activePostId = 0;
  let requestSequence = 0;
  let mediaByComment = new Map();

  const style = document.createElement('style');
  style.id = 'real-comment-media-styles';
  style.textContent = `
    .real-comment-media-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 7px;
      margin-top: 11px;
      overflow: hidden;
      border-radius: 13px;
    }

    .real-comment-media-grid[data-count="1"] {
      grid-template-columns: minmax(0, 1fr);
      max-width: 560px;
    }

    .real-comment-media-item {
      position: relative;
      min-width: 0;
      min-height: 118px;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 1px solid rgba(214, 208, 188, .72);
      border-radius: 12px;
      background: #efede4;
      color: #514f48;
      text-decoration: none;
    }

    .real-comment-media-grid[data-count="1"] .real-comment-media-item {
      min-height: 180px;
      max-height: 300px;
    }

    .real-comment-media-item img,
    .real-comment-media-item video {
      width: 100%;
      height: 100%;
      min-height: inherit;
      max-height: 300px;
      display: block;
      object-fit: cover;
      background: #1d1d1b;
    }

    .real-comment-media-file {
      padding: 18px;
      font-weight: 600;
      text-align: center;
    }

    .real-comment-media-more {
      position: absolute;
      inset: 0;
      display: grid;
      place-items: center;
      background: rgba(24, 24, 22, .62);
      color: #fff;
      font-size: 22px;
      font-weight: 600;
      pointer-events: none;
    }

    .real-comment-item.is-reply .real-comment-media-grid {
      max-width: 470px;
    }

    @media (max-width: 699px) {
      .real-comment-media-grid {
        gap: 5px;
      }

      .real-comment-media-item {
        min-height: 92px;
      }

      .real-comment-media-grid[data-count="1"] .real-comment-media-item {
        min-height: 150px;
        max-height: 240px;
      }

      .real-comment-media-item img,
      .real-comment-media-item video {
        max-height: 240px;
      }
    }
  `;
  document.head.appendChild(style);

  const escapeHtml = (value = '') => String(value).replace(/[&<>"']/g, (character) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  })[character]);

  const renderMedia = (media = []) => {
    if (!Array.isArray(media) || media.length === 0) return '';

    const visible = media.slice(0, 4);
    const items = visible.map((item, index) => {
      const extra = index === visible.length - 1 && media.length > visible.length
        ? `<span class="real-comment-media-more">+${media.length - visible.length}</span>`
        : '';

      if (item.type === 'image') {
        return `
          <a class="real-comment-media-item" href="${escapeHtml(item.url)}" target="_blank" rel="noopener">
            <img src="${escapeHtml(item.url)}" alt="${escapeHtml(item.name || 'Kommentarbild')}" loading="lazy">
            ${extra}
          </a>
        `;
      }

      if (item.type === 'video') {
        return `
          <div class="real-comment-media-item">
            <video controls playsinline preload="metadata">
              <source src="${escapeHtml(item.url)}" type="${escapeHtml(item.mime || 'video/mp4')}">
            </video>
            ${extra}
          </div>
        `;
      }

      return `
        <a class="real-comment-media-item real-comment-media-file" href="${escapeHtml(item.url)}" target="_blank" rel="noopener">
          ${escapeHtml(item.name || 'Anhang öffnen')}
          ${extra}
        </a>
      `;
    }).join('');

    return `<div class="real-comment-media-grid" data-count="${Math.min(media.length, 4)}">${items}</div>`;
  };

  const applyMedia = () => {
    list.querySelectorAll('.real-comment-item[data-comment-id]').forEach((article) => {
      const commentId = Number.parseInt(article.dataset.commentId || '0', 10);
      const media = mediaByComment.get(commentId) || [];
      const bubble = article.querySelector('.comment-bubble');
      const text = bubble?.querySelector('[data-comment-text]');

      article.querySelector('.real-comment-media-grid')?.remove();

      if (!bubble || media.length === 0) return;

      const wrapper = document.createElement('div');
      wrapper.innerHTML = renderMedia(media);
      const grid = wrapper.firstElementChild;
      if (!grid) return;

      if (text) {
        text.insertAdjacentElement('afterend', grid);
      } else {
        bubble.prepend(grid);
      }
    });
  };

  const loadMedia = async (postId) => {
    const sequence = ++requestSequence;
    activePostId = postId;
    mediaByComment = new Map();

    try {
      const url = `${window.location.pathname}?comment_media_post_id=${encodeURIComponent(String(postId))}`;
      const response = await fetch(url, {
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      if (!response.ok) throw new Error(`Comment media request failed with ${response.status}`);

      const payload = await response.json();
      if (sequence !== requestSequence || Number(payload.post_id) !== activePostId) return;

      mediaByComment = new Map((payload.comments || []).map((comment) => [
        Number(comment.id),
        Array.isArray(comment.media) ? comment.media : [],
      ]));

      applyMedia();
    } catch (error) {
      console.error('HNT comment media preview failed', error);
    }
  };

  window.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;

    const button = target.closest('[data-real-preview-comments]');
    if (!button) return;

    const article = button.closest('[data-real-feed-post]');
    const postId = Number.parseInt(article?.dataset.realFeedPost || '0', 10);
    if (!postId) return;

    window.setTimeout(() => loadMedia(postId), 0);
  }, true);

  const observer = new MutationObserver(() => applyMedia());
  observer.observe(list, { childList: true, subtree: true });

  const modalObserver = new MutationObserver(() => {
    if (!modal.classList.contains('is-open')) {
      activePostId = 0;
      mediaByComment = new Map();
    }
  });
  modalObserver.observe(modal, { attributes: true, attributeFilter: ['class'] });
})();
