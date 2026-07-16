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

  window.HNT_DASHBOARD_HEADER_ENDPOINT = window.HNT_DASHBOARD_HEADER_ENDPOINT
    || document.querySelector('.site-header .brand')?.href
    || '/feed';

  loadScriptOnce(
    '/assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js',
    'hnt-teams-real-header',
  )
    .then(() => loadScriptOnce(
      '/assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js',
      'hnt-teams-real-header-live',
    ))
    .catch((error) => console.error('HNT teams header runtime failed', error));

  const directory = document.querySelector('[data-teams-directory]');
  const toggle = document.querySelector('[data-teams-filter-toggle]');

  if (directory && toggle) {
    toggle.addEventListener('click', () => {
      const open = !directory.classList.contains('is-filter-open');
      directory.classList.toggle('is-filter-open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  document.querySelectorAll('[data-teams-join-form]').forEach((form) => {
    form.addEventListener('submit', () => {
      const button = form.querySelector('button[type="submit"]');
      if (!button || button.disabled) return;

      button.disabled = true;
      button.setAttribute('aria-busy', 'true');
    });
  });
})();
