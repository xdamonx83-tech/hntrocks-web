(() => {
  'use strict';

  if (window.HNT_REAL_FEED_MOMENT_PREVIEW_READY) return;
  window.HNT_REAL_FEED_MOMENT_PREVIEW_READY = true;

  const isEnglish = (document.documentElement.lang || '').toLowerCase().startsWith('en');
  const labels = isEnglish ? {
    kicker: 'HNT MOMENT',
    fallback: 'Open moment',
    meta: 'Open in Moments',
  } : {
    kicker: 'HNT MOMENT',
    fallback: 'Moment öffnen',
    meta: 'In Moments öffnen',
  };

  const cssHref = '/assets/themes/hnt_preview/dashboard-feed/real-feed-moment-preview.css?v=1';
  if (!document.querySelector('link[data-hnt-feed-moment-preview]')) {
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = cssHref;
    link.dataset.hntFeedMomentPreview = '1';
    document.head.appendChild(link);
  }

  const playIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"></path></svg>';

  const escapeHtml = (value = '') => String(value).replace(/[&<>"']/g, (character) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  })[character]);

  const formatBody = (node, value) => {
    const clean = String(value || '').replace(/\n{3,}/g, '\n\n').trim();
    if (!clean) {
      node?.remove();
      return;
    }
    node.innerHTML = escapeHtml(clean).replace(/\n/g, '<br>');
  };

  const momentReference = (value = '') => {
    const text = String(value || '');
    const absolute = text.match(/https?:\/\/[^\s<]+\/moments\/r\/(\d+)[^\s<]*/i);
    const relative = absolute ? null : text.match(/\/moments\/r\/(\d+)[^\s<]*/i);
    const match = absolute || relative;
    if (!match) return null;

    const raw = String(match[0]).replace(/[),.!?]+$/, '');
    let url;
    try {
      url = new URL(raw, window.location.origin);
    } catch (_error) {
      return null;
    }

    if (url.origin !== window.location.origin) return null;

    return {
      id: String(match[1]),
      raw,
      url: url.href,
    };
  };

  const removeDuplicateVideo = (article) => {
    article.querySelectorAll('.real-post-video-item').forEach((item) => item.remove());
    article.querySelectorAll('.real-post-media-grid').forEach((grid) => {
      if (!grid.querySelector('.real-post-media-item')) grid.remove();
    });
  };

  const previewCard = (reference) => {
    const card = document.createElement('a');
    card.className = 'hnt-feed-moment-preview is-loading';
    card.href = reference.url;
    card.setAttribute('aria-label', labels.fallback);
    card.innerHTML = `
      <span class="hnt-feed-moment-preview-copy">
        <span class="hnt-feed-moment-preview-kicker">${labels.kicker}</span>
        <strong class="hnt-feed-moment-preview-title">${labels.fallback}</strong>
        <span class="hnt-feed-moment-preview-meta">${labels.meta}</span>
      </span>
      <span class="hnt-feed-moment-preview-play" aria-hidden="true">${playIcon}</span>`;
    return card;
  };

  const absoluteAssetUrl = (value = '', base = window.location.href) => {
    if (!value) return '';
    try {
      return new URL(value, base).href;
    } catch (_error) {
      return '';
    }
  };

  const parseMoment = (html, reference) => {
    const parsed = new DOMParser().parseFromString(html, 'text/html');
    const slide = parsed.querySelector(`.moment-slide[data-moment-id="${CSS.escape(reference.id)}"]`)
      || parsed.querySelector('.moment-slide[data-moment-id]');

    if (!slide) return null;

    const title = String(
      slide.dataset.caption
      || slide.querySelector('.moment-caption h2')?.textContent
      || labels.fallback
    ).trim();
    const duration = String(slide.querySelector('.moment-duration')?.textContent || '').trim();
    const video = slide.querySelector('.moment-video-card video');
    let poster = absoluteAssetUrl(video?.getAttribute('poster') || '', reference.url);

    if (!poster) {
      const ambientStyle = slide.querySelector('.moment-ambient')?.getAttribute('style') || '';
      const ambientMatch = ambientStyle.match(/--moment-poster\s*:\s*url\((['"]?)(.*?)\1\)/i);
      poster = absoluteAssetUrl(ambientMatch?.[2] || '', reference.url);
    }

    return { title, duration, poster };
  };

  const fillCard = (card, data) => {
    const title = card.querySelector('.hnt-feed-moment-preview-title');
    const meta = card.querySelector('.hnt-feed-moment-preview-meta');
    if (title) title.textContent = data?.title || labels.fallback;
    if (meta) meta.textContent = data?.duration ? `${data.duration} · ${labels.meta}` : labels.meta;
    if (data?.poster) {
      const safePoster = String(data.poster).replace(/["\\]/g, '\\$&');
      card.style.setProperty('--hnt-moment-preview-image', `url("${safePoster}")`);
    }
    card.classList.remove('is-loading');
  };

  const enhanceArticle = async (article) => {
    if (!(article instanceof Element) || article.dataset.hntMomentPreviewReady === '1') return;

    const body = article.querySelector('.post-body > p');
    const reference = momentReference(body?.textContent || '');
    if (!reference) return;

    article.dataset.hntMomentPreviewReady = '1';
    article.classList.add('has-moment-preview');

    if (body) formatBody(body, body.textContent.replace(reference.raw, ''));
    removeDuplicateVideo(article);

    const badge = article.querySelector('.post-badge');
    if (badge) {
      badge.textContent = 'Moment';
      badge.classList.add('hnt-moment-post-badge');
    }

    const postBody = article.querySelector('.post-body');
    if (!postBody) return;

    const card = previewCard(reference);
    postBody.appendChild(card);

    try {
      const response = await fetch(reference.url, {
        credentials: 'same-origin',
        headers: {
          Accept: 'text/html',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });
      if (!response.ok) throw new Error(`Moment preview failed with ${response.status}`);
      fillCard(card, parseMoment(await response.text(), reference));
    } catch (error) {
      console.warn('HNT Moment preview could not be loaded', error);
      fillCard(card, null);
    }
  };

  const scan = (root = document) => {
    if (root instanceof Element && root.matches('[data-real-feed-post]')) enhanceArticle(root);
    root.querySelectorAll?.('[data-real-feed-post]').forEach(enhanceArticle);
  };

  scan();

  const observer = new MutationObserver((records) => {
    records.forEach((record) => record.addedNodes.forEach((node) => {
      if (!(node instanceof Element)) return;
      scan(node);
    }));
  });
  observer.observe(document.documentElement, { childList: true, subtree: true });
})();
