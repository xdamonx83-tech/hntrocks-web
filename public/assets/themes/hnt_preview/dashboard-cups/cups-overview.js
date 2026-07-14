(() => {
  const shell = document.querySelector('.cups-page-shell');
  if (!shell) return;

  if (!document.querySelector('link[data-cups-feed-alignment]')) {
    const style = document.createElement('link');
    style.rel = 'stylesheet';
    style.href = '/assets/themes/hnt_preview/dashboard-cups/cups-feed-alignment.css?v=20260714-2';
    style.dataset.cupsFeedAlignment = '1';
    document.head.appendChild(style);
  }

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