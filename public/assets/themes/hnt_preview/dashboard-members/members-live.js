(() => {
  const modal = document.querySelector('[data-members-filter-modal]');
  const openButton = document.querySelector('[data-members-filter-open]');
  const closeButton = document.querySelector('[data-members-filter-close]');
  const stream = document.querySelector('[data-members-stream]');
  const loadMoreWrap = document.querySelector('[data-members-load-more-wrap]');
  const loadMoreButton = document.querySelector('[data-members-load-more]');
  const copy = window.HNT_MEMBERS_COPY || {};

  const closeModal = () => {
    if (!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('members-filter-open');
    openButton?.focus();
  };

  const openModal = () => {
    if (!modal) return;
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('members-filter-open');
    window.setTimeout(() => modal.querySelector('select, input, button')?.focus(), 0);
  };

  openButton?.addEventListener('click', openModal);
  closeButton?.addEventListener('click', closeModal);

  modal?.addEventListener('click', (event) => {
    if (event.target === modal) closeModal();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && modal?.classList.contains('is-open')) closeModal();
  });

  loadMoreButton?.addEventListener('click', async () => {
    if (!stream || loadMoreButton.disabled) return;

    const nextUrl = loadMoreButton.dataset.nextUrl;
    if (!nextUrl) return;

    loadMoreButton.disabled = true;
    const label = loadMoreButton.querySelector('[data-members-load-more-label]');
    if (label) label.textContent = copy.loading || 'Lädt …';

    try {
      const url = new URL(nextUrl, window.location.origin);
      url.searchParams.set('fragment', '1');

      const response = await fetch(url.toString(), {
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
      });

      if (!response.ok) throw new Error(`Members request failed with ${response.status}`);

      const payload = await response.json();
      if (typeof payload.html !== 'string') throw new Error('Members response did not contain HTML.');

      const fragment = document.createRange().createContextualFragment(payload.html);
      stream.append(fragment);

      if (payload.hasMorePages && payload.nextPageUrl) {
        loadMoreButton.dataset.nextUrl = payload.nextPageUrl;
        loadMoreButton.disabled = false;
        if (label) label.textContent = copy.ready || 'Mehr laden';
      } else {
        loadMoreWrap?.remove();
      }
    } catch (error) {
      console.error('HNT members could not load the next page.', error);
      loadMoreButton.disabled = false;
      if (label) label.textContent = copy.error || 'Erneut versuchen';
    }
  });
})();
