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
    'data-cup-detail-real-feed'
  );
  ensureStylesheet(
    '/assets/themes/hnt_preview/dashboard-feed/real-feed-polish.css?v=20260714-1',
    'data-cup-detail-real-feed-polish'
  );
  ensureStylesheet(
    '/assets/themes/hnt_preview/dashboard-cups/cup-detail-feed-alignment.css?v=20260714-1',
    'data-cup-detail-feed-alignment'
  );

  const shell = document.querySelector('.cup-detail-page-shell');
  if (!shell) return;

  /* Header data and actions must use the exact same live runtime as Feed and
   * Profile. The Feed endpoint already supports dashboard_header JSON. */
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
    'data-cup-detail-header-runtime',
    () => loadScript(
      '/assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js?v=20260714-1',
      'data-cup-detail-header-live'
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

  const tabs = Array.from(shell.querySelectorAll('[data-cup-tab]'));
  const panels = Array.from(shell.querySelectorAll('[data-cup-panel]'));
  const title = shell.querySelector('#cupSectionTitle');
  const pageScroll = shell.querySelector('.cup-detail-stage');

  function activateTab(name, updateUrl = true) {
    const activeTab = tabs.find((tab) => tab.dataset.cupTab === name) || tabs[0];
    if (!activeTab) return;

    tabs.forEach((tab) => {
      const active = tab === activeTab;
      tab.classList.toggle('active', active);
      tab.setAttribute('aria-selected', String(active));
    });

    panels.forEach((panel) => {
      const active = panel.dataset.cupPanel === activeTab.dataset.cupTab;
      panel.classList.toggle('active', active);
      panel.hidden = !active;
    });

    if (title) title.textContent = activeTab.dataset.title || activeTab.textContent.trim();

    if (updateUrl && activeTab.dataset.url) {
      window.history.replaceState({}, '', activeTab.dataset.url);
    }
  }

  tabs.forEach((tab) => {
    tab.addEventListener('click', () => activateTab(tab.dataset.cupTab));
  });

  shell.querySelectorAll('[data-cup-tab-shortcut]').forEach((control) => {
    control.addEventListener('click', () => {
      activateTab(control.dataset.cupTabShortcut);
      shell.querySelector('#cupSections')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });

  const initial = shell.dataset.cupActiveSection || 'overview';
  activateTab(initial, false);

  shell.querySelector('[data-cup-share]')?.addEventListener('click', async () => {
    const url = shell.dataset.cupShareUrl || window.location.href;
    try {
      if (navigator.share) {
        await navigator.share({ title: document.title, url });
        return;
      }
      await navigator.clipboard.writeText(url);
      window.showToast?.('Link kopiert');
    } catch (_error) {
      window.prompt('Link kopieren', url);
    }
  });

  shell.querySelector('[data-cup-file]')?.addEventListener('change', (event) => {
    const input = event.currentTarget;
    const file = input.files?.[0];
    const zone = input.closest('.cup-upload-zone');
    const titleNode = zone?.querySelector('strong');
    if (file && titleNode) titleNode.textContent = file.name;
  });

  pageScroll?.addEventListener('scroll', () => {
    shell.classList.toggle('cup-detail-is-scrolled', pageScroll.scrollTop > 10);
  }, { passive: true });
})();
