/* Localizes the live dashboard feed chrome without touching user-generated post content. */
(() => {
  const labels = window.HNT_DASHBOARD_I18N || {};
  if (!labels || typeof labels !== 'object') return;

  const text = (key, fallback = '') => String(labels[key] || fallback);
  const badgeLabels = new Map([
    ['Beitrag', text('post', 'Beitrag')],
    ['Post', text('post', 'Post')],
    ['Bild', text('image', 'Bild')],
    ['Image', text('image', 'Image')],
    ['Umfrage', text('poll', 'Umfrage')],
    ['Poll', text('poll', 'Poll')],
  ]);

  const localizePost = (article) => {
    if (!(article instanceof Element)) return;

    const share = article.querySelector('[data-real-preview-share] span, [data-profile-share-url] span');
    if (share) share.textContent = text('share', share.textContent);

    const comments = article.querySelector('[data-real-preview-comments]');
    if (comments) comments.setAttribute('aria-label', text('open_comments', comments.getAttribute('aria-label') || ''));

    const save = article.querySelector('[data-real-preview-save]');
    if (save) save.setAttribute('aria-label', text('save', save.getAttribute('aria-label') || ''));

    const more = article.querySelector('[data-real-post-more]');
    if (more) more.setAttribute('aria-label', text('post_options', more.getAttribute('aria-label') || ''));

    article.querySelectorAll('.post-badge').forEach((badge) => {
      const current = badge.textContent.trim();
      if (badgeLabels.has(current)) badge.textContent = badgeLabels.get(current);
    });

    article.querySelectorAll('.real-feed-pinned').forEach((node) => {
      node.textContent = text('pinned', node.textContent);
    });

    article.querySelectorAll('.real-post-file-item span').forEach((node) => {
      node.textContent = text('open_file', node.textContent);
    });

    article.querySelectorAll('.real-post-media-item[aria-label]').forEach((node) => {
      node.setAttribute('aria-label', text('open_post', node.getAttribute('aria-label') || ''));
    });

    const authorMeta = article.querySelector('.post-author > span, .post-author .hnt-post-meta-row > span');
    if (authorMeta) {
      authorMeta.textContent = authorMeta.textContent
        .replace(/ · Öffentlich\s*$/, ` · ${text('public', 'Öffentlich')}`)
        .replace(/ · Public\s*$/, ` · ${text('public', 'Public')}`);
    }

    const pollTotal = article.querySelector('.real-feed-poll > small');
    if (pollTotal) {
      const match = pollTotal.textContent.trim().match(/^(\d+)\s+(?:Stimme|Stimmen|vote|votes)$/i);
      if (match) {
        const count = Number(match[1]) || 0;
        pollTotal.textContent = `${count} ${count === 1 ? text('vote', 'Stimme') : text('votes', 'Stimmen')}`;
      }
    }
  };

  const localizeStaticChrome = () => {
    const feedTitle = document.querySelector('.social-feed-head h2');
    if (feedTitle) feedTitle.textContent = text('community_feed', feedTitle.textContent);

    const tabs = document.querySelectorAll('.social-feed-head .feed-tabs > button:not(.compose-button)');
    if (tabs[0]) tabs[0].textContent = text('for_you', tabs[0].textContent);
    if (tabs[1]) tabs[1].textContent = text('following', tabs[1].textContent);

    const compose = document.querySelector('.social-feed-head .compose-button');
    if (compose) compose.setAttribute('aria-label', text('create_post', compose.getAttribute('aria-label') || ''));

    document.querySelectorAll('.post-list .social-post').forEach(localizePost);
  };

  localizeStaticChrome();

  const list = document.querySelector('.post-list');
  if (list) {
    const observer = new MutationObserver((records) => {
      records.forEach((record) => {
        record.addedNodes.forEach((node) => {
          if (!(node instanceof Element)) return;
          if (node.matches('.social-post')) localizePost(node);
          node.querySelectorAll?.('.social-post').forEach(localizePost);
        });
      });
    });
    observer.observe(list, { childList: true, subtree: true });
  }

  document.addEventListener('hnt:feed-i18n-refresh', localizeStaticChrome);
})();

/* Shared live-feed modules. */
(() => {
  const load = (src, key) => {
    if (document.querySelector(`script[data-${key}]`)) return;
    const script = document.createElement('script');
    script.src = src;
    script.dataset[key.replace(/-([a-z])/g, (_, letter) => letter.toUpperCase())] = '1';
    document.body.appendChild(script);
  };

  load('/assets/themes/hnt_preview/dashboard-feed/shared-video-player.js?v=1', 'hnt-shared-video-player');
  load('/assets/themes/hnt_preview/dashboard-feed/real-feed-moment-preview.js?v=1', 'hnt-feed-moment-preview');
  load('/assets/themes/hnt_preview/dashboard-feed/real-feed-translation.js?v=1', 'hnt-feed-translation');
  load('/assets/themes/hnt_preview/dashboard-feed/real-feed-video-open.js?v=1', 'hnt-feed-video-open');
})();
