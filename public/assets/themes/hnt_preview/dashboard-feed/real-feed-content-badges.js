/* Keep compact post badges consistent and decorate structured Cup crossposts. */
(() => {
  const feedList = document.querySelector('.post-list');
  if (!feedList) return;

  const english = (document.documentElement.lang || '').toLowerCase().startsWith('en');
  const labels = english
    ? { post: 'Post', poll: 'Poll', video: 'Video', image: 'Image', cup: 'Cup' }
    : { post: 'Beitrag', poll: 'Umfrage', video: 'Video', image: 'Bild', cup: 'Cup' };
  const postCache = new Map();

  if (!document.querySelector('link[data-real-feed-cup-card]')) {
    const style = document.createElement('link');
    style.rel = 'stylesheet';
    style.href = '/assets/themes/hnt_preview/dashboard-feed/cup-card.css?v=20260715-1';
    style.setAttribute('data-real-feed-cup-card', '1');
    document.head.appendChild(style);
  }

  const escapeHtml = (value = '') => String(value).replace(/[&<>"']/g, (character) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  })[character]);

  const setBadge = (badge, label, variant = '') => {
    badge.textContent = label;
    badge.classList.remove('moment', 'cup', 'rocks', 'discussion');
    if (variant) badge.classList.add(variant);
  };

  const classify = (article) => {
    if (!(article instanceof Element) || !article.matches('[data-real-feed-post]')) return;

    const badge = article.querySelector('.post-badge');
    if (!badge) return;

    if (article.querySelector('.hnt-feed-cup-card')) {
      setBadge(badge, labels.cup, 'cup');
      article.classList.add('is-cup-crosspost');
      return;
    }

    const hasPoll = Boolean(article.querySelector('.real-feed-poll, .profile-real-poll'));
    const hasVideo = Boolean(article.querySelector('.real-post-video-item, video'));
    const hasMedia = Boolean(article.querySelector('.real-post-media-grid, .profile-real-media-grid'));

    if (hasPoll) {
      setBadge(badge, labels.poll, 'cup');
    } else if (hasVideo) {
      setBadge(badge, labels.video, 'moment');
    } else if (hasMedia) {
      setBadge(badge, labels.image);
    } else {
      setBadge(badge, labels.post, 'discussion');
    }
  };

  const renderCupCard = (cup) => `
    <section class="hnt-feed-cup-card" aria-label="${escapeHtml(`${cup.kicker || 'Community Cup'} ${cup.title || ''}`)}">
      <div class="hnt-feed-cup-card-copy">
        <span>${escapeHtml(cup.kicker || 'COMMUNITY CUP')}</span>
        <h3>${escapeHtml(cup.title || 'Community Cup')}</h3>
        <p>${escapeHtml(cup.mode || '')} · ${escapeHtml(cup.platforms || '')} · ${escapeHtml(cup.date || '')}</p>
      </div>
      <a class="hnt-feed-cup-card-action" href="${escapeHtml(cup.details_url || '/cups')}">
        ${escapeHtml(cup.details_label || 'Details')}
      </a>
    </section>
  `;

  const decorateCupPost = (article, post) => {
    const cup = post?.cup_crosspost;
    if (!cup || article.querySelector('.hnt-feed-cup-card')) {
      classify(article);
      return;
    }

    const body = article.querySelector('.post-body');
    const badge = article.querySelector('.post-badge');
    if (!body || !badge) return;

    const wrapper = document.createElement('div');
    wrapper.innerHTML = renderCupCard(cup).trim();
    const card = wrapper.firstElementChild;
    const firstRichContent = body.querySelector('.real-post-media-grid, .profile-real-media-grid, .real-feed-poll, .profile-real-poll');
    body.insertBefore(card, firstRichContent || null);

    article.classList.add('is-cup-crosspost');
    setBadge(badge, labels.cup, 'cup');
  };

  const fetchPost = async (id) => {
    if (postCache.has(id)) return postCache.get(id);

    const promise = fetch(`/feed?data=1&post_id=${encodeURIComponent(id)}`, {
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    }).then(async (response) => {
      if (!response.ok) return null;
      const payload = await response.json();
      return payload?.post || null;
    }).catch(() => null);

    postCache.set(id, promise);
    return promise;
  };

  const hydrate = async (article) => {
    if (!(article instanceof Element) || !article.matches('[data-real-feed-post]')) return;
    if (article.dataset.cupCrosspostChecked === '1') {
      classify(article);
      return;
    }

    article.dataset.cupCrosspostChecked = '1';
    classify(article);

    const id = Number.parseInt(article.dataset.realFeedPost || '0', 10);
    if (!id) return;

    const post = await fetchPost(id);
    if (!article.isConnected || !post) return;
    decorateCupPost(article, post);
  };

  const hydrateWithin = (root) => {
    if (!(root instanceof Element)) return;
    if (root.matches('[data-real-feed-post]')) hydrate(root);
    root.querySelectorAll('[data-real-feed-post]').forEach(hydrate);
  };

  hydrateWithin(feedList);

  const observer = new MutationObserver((records) => {
    records.forEach((record) => {
      record.addedNodes.forEach((node) => {
        if (node instanceof Element) hydrateWithin(node);
      });
    });
  });

  observer.observe(feedList, { childList: true, subtree: true });
})();
