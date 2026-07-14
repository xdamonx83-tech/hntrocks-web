(() => {
  const ensureStylesheet = (href, marker) => {
    if (document.querySelector(`link[${marker}]`)) return;

    const style = document.createElement('link');
    style.rel = 'stylesheet';
    style.href = href;
    style.setAttribute(marker, '1');
    document.head.appendChild(style);
  };

  ensureStylesheet(
    '/assets/themes/hnt_preview/dashboard-feed/real-feed.css?v=20260714-1',
    'data-cups-real-feed'
  );
  ensureStylesheet(
    '/assets/themes/hnt_preview/dashboard-feed/real-feed-polish.css?v=20260714-1',
    'data-cups-real-feed-polish'
  );
  ensureStylesheet(
    '/assets/themes/hnt_preview/dashboard-cups/cups-feed-alignment.css?v=20260714-3',
    'data-cups-feed-alignment'
  );

  const shell = document.querySelector('.cups-page-shell');
  if (!shell) return;

  /* Use the exact same live header endpoint and runtime as Feed/Profile and
   * the Cup detail page. */
  window.HNT_DASHBOARD_HEADER_ENDPOINT = '/feed';

  const loadScript = (src, marker, onload) => {
    const existing = document.querySelector(`script[${marker}]`);
    if (existing) {
      onload?.();
      return;
    }

    const script = document.createElement('script');
    script.src = src;
    script.async = false;
    script.setAttribute(marker, '1');
    if (onload) script.addEventListener('load', onload, { once: true });
    document.body.appendChild(script);
  };

  loadScript(
    '/assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js?v=20260714-1',
    'data-cups-header-runtime',
    () => loadScript(
      '/assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js?v=20260714-1',
      'data-cups-header-live'
    )
  );

  const cupsNav = shell.querySelector('.nav-cups');
  const cupsTrigger = cupsNav?.querySelector(':scope > .main-nav-trigger');

  cupsNav?.classList.add('is-current');
  cupsTrigger?.classList.add('is-current');

  const routeMap = {
    'Aktive Cups': shell.dataset.cupsActiveUrl,
    'Meine Cup-Teams': shell.dataset.cupsMineUrl,
    'Einreichungen': shell.dataset.cupsSubmissionsUrl,
    'Hall of Fame': shell.dataset.cupsHallUrl,
  };

  cupsNav?.querySelectorAll('[data-navigation-label]').forEach((control) => {
    const target = routeMap[control.dataset.navigationLabel];
    if (!target) return;

    control.removeAttribute('aria-disabled');
    control.removeAttribute('data-unavailable');
    control.addEventListener('click', (event) => {
      event.preventDefault();
      window.location.assign(target);
    });
  });
})();
