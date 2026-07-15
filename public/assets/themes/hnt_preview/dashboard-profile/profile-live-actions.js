/* Real profile post reactions and bookmarks. Capture phase prevents the static
   prototype handlers from applying fake local-only counters. */
(() => {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  if (!window.fetch) return;

  /* The shared media viewer was originally written for the feed endpoint.
     Profile posts use the exact same payload, so only rewrite that one narrow
     data request instead of maintaining a second profile-only viewer. */
  if (!window.HNT_PROFILE_FEED_FETCH_BRIDGE) {
    window.HNT_PROFILE_FEED_FETCH_BRIDGE = true;
    const nativeFetch = window.fetch.bind(window);
    window.fetch = (input, init) => {
      try {
        const source = input instanceof Request ? input.url : String(input);
        const url = new URL(source, window.location.href);
        const isProfileViewerRequest = url.pathname === window.location.pathname
          && url.searchParams.get('data') === '1'
          && url.searchParams.has('post_id');

        if (isProfileViewerRequest) {
          url.pathname = '/feed';
          input = input instanceof Request
            ? new Request(url.toString(), input)
            : url.toString();
        }
      } catch (_) {
        // Leave unrelated requests untouched.
      }
      return nativeFetch(input, init);
    };
  }

  const loadScript = (src, attribute) => {
    if (document.querySelector(`script[data-${attribute}]`)) return;
    const script = document.createElement('script');
    script.src = src;
    script.setAttribute(`data-${attribute}`, '1');
    document.body.appendChild(script);
  };

  loadScript('/assets/themes/hnt_preview/dashboard-feed/shared-video-player.js?v=1', 'hnt-shared-video-player');
  loadScript('/assets/themes/hnt_preview/dashboard-feed/real-feed-translation.js?v=1', 'hnt-feed-translation');
  loadScript('/assets/themes/hnt_preview/dashboard-feed/real-feed-video-open.js?v=1', 'hnt-feed-video-open');

  const toast = (message) => {
    if (typeof window.showToast === 'function') window.showToast(message);
    else console.info(message);
  };

  const request = async (url, body = null) => {
    const response = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
      body: JSON.stringify(body || {}),
    });

    let payload = null;
    try { payload = await response.json(); } catch (_) { payload = null; }
    if (!response.ok) {
      const validation = payload?.errors ? Object.values(payload.errors).flat().find(Boolean) : null;
      throw new Error(validation || payload?.message || `Request failed with ${response.status}`);
    }
    return payload || {};
  };

  document.addEventListener('click', async (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;

    const likeButton = target.closest('[data-profile-like-url]');
    if (likeButton) {
      event.preventDefault();
      event.stopImmediatePropagation();
      if (likeButton.disabled) return;
      likeButton.disabled = true;

      try {
        const payload = await request(likeButton.dataset.profileLikeUrl, { type: 'like' });
        likeButton.classList.toggle('liked', Boolean(payload.reacted));
        likeButton.classList.toggle('is-active', Boolean(payload.reacted));
        const counter = likeButton.querySelector('span');
        if (counter) counter.textContent = new Intl.NumberFormat(document.documentElement.lang || 'de').format(Number(payload.count) || 0);
      } catch (error) {
        toast(error.message || 'Reaktion konnte nicht gespeichert werden');
      } finally {
        likeButton.disabled = false;
      }
      return;
    }

    const bookmarkButton = target.closest('[data-profile-bookmark-url]');
    if (bookmarkButton) {
      event.preventDefault();
      event.stopImmediatePropagation();
      if (bookmarkButton.disabled) return;
      bookmarkButton.disabled = true;

      try {
        const payload = await request(bookmarkButton.dataset.profileBookmarkUrl);
        bookmarkButton.classList.toggle('saved', Boolean(payload.bookmarked));
        bookmarkButton.classList.toggle('is-active', Boolean(payload.bookmarked));
        bookmarkButton.setAttribute('aria-label', payload.bookmarked ? 'Beitrag gespeichert' : 'Beitrag speichern');
        toast(payload.message || (payload.bookmarked ? 'Beitrag gespeichert' : 'Nicht mehr gespeichert'));
      } catch (error) {
        toast(error.message || 'Beitrag konnte nicht gespeichert werden');
      } finally {
        bookmarkButton.disabled = false;
      }
    }
  }, true);
})();
