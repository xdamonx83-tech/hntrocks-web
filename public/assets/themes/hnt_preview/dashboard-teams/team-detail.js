(() => {
  const loadScriptOnce = (src, marker) => new Promise((resolve, reject) => {
    const existing = document.querySelector(`script[data-${marker}]`)
      || document.querySelector(`script[src*="${src.split('/').pop()}"]`);

    if (existing) {
      if (existing.dataset.loaded === '1' || existing.readyState === 'complete') {
        resolve();
        return;
      }

      existing.addEventListener('load', resolve, { once: true });
      existing.addEventListener('error', reject, { once: true });
      return;
    }

    const script = document.createElement('script');
    script.src = `${src}?v=20260716-1`;
    script.async = false;
    script.dataset[marker.replace(/-([a-z])/g, (_, letter) => letter.toUpperCase())] = '1';
    script.addEventListener('load', () => {
      script.dataset.loaded = '1';
      resolve();
    }, { once: true });
    script.addEventListener('error', reject, { once: true });
    document.head.appendChild(script);
  });

  window.HNT_DASHBOARD_HEADER_ENDPOINT = window.HNT_DASHBOARD_HEADER_ENDPOINT || '/feed';

  loadScriptOnce(
    '/assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js',
    'hnt-team-detail-real-header',
  )
    .then(() => loadScriptOnce(
      '/assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js',
      'hnt-team-detail-real-header-live',
    ))
    .catch((error) => console.error('HNT team detail header runtime failed', error));

  document.querySelectorAll('[data-team-detail-leave-form]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      const confirmation = form.dataset.confirm || '';
      if (confirmation && !window.confirm(confirmation)) {
        event.preventDefault();
      }
    });
  });

  document.querySelectorAll('.team-detail-primary-actions form').forEach((form) => {
    form.addEventListener('submit', () => {
      const button = form.querySelector('button[type="submit"]');
      if (!button) return;
      button.disabled = true;
      button.setAttribute('aria-busy', 'true');
    });
  });

  const composerModal = document.getElementById('composerModal');
  if (composerModal && document.querySelector('.team-detail-flash.is-error')) {
    composerModal.classList.add('open');
    composerModal.setAttribute('aria-hidden', 'false');
  }
})();

