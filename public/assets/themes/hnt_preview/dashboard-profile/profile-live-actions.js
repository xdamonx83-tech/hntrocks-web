/* Real profile post reactions and bookmarks. Capture phase prevents the static
   prototype handlers from applying fake local-only counters. */
(() => {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  if (!window.fetch) return;

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
