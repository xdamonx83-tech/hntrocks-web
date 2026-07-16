(() => {
  const ensureStylesheet = (href, marker) => {
    if (document.querySelector(`link[${marker}]`)) return;
    const style = document.createElement('link');
    style.rel = 'stylesheet';
    style.href = href;
    style.setAttribute(marker, '1');
    document.head.appendChild(style);
  };

  ensureStylesheet('/assets/themes/hnt_preview/dashboard-feed/real-feed.css?v=20260714-1', 'data-cups-real-feed');
  ensureStylesheet('/assets/themes/hnt_preview/dashboard-feed/real-feed-polish.css?v=20260714-1', 'data-cups-real-feed-polish');
  ensureStylesheet('/assets/themes/hnt_preview/dashboard-cups/cup-community-access.css?v=20260714-1', 'data-cup-community-access');
  ensureStylesheet('/assets/themes/hnt_preview/dashboard-cups/cups-header-dropdown-reference.css?v=20260715-1', 'data-cups-header-dropdown-reference');

  const shell = document.querySelector('.cups-page-shell');
  if (!shell) return;

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

(() => {
  const stage = document.querySelector('.cups-stage');
  const scroll = document.getElementById('cupsScroll');
  const center = document.getElementById('cupsCenterFlow');
  const left = document.getElementById('cupsFixedLeft');
  const right = document.getElementById('cupsFixedRight');
  const stickyHead = document.querySelector('.cups-center-head');
  let frame = 0;

  function updateFixedColumns() {
    frame = 0;
    if (!stage || !scroll || !center || !left || !right) return;

    if (window.matchMedia('(max-width: 899px)').matches) {
      left.style.removeProperty('top');
      right.style.removeProperty('top');
      stage.classList.remove('cups-columns-docked');
      scroll.classList.remove('cups-content-docked');
      stickyHead?.classList.remove('is-stuck');
      return;
    }

    const styles = getComputedStyle(stage);
    const gap = Number.parseFloat(styles.getPropertyValue('--cups-sticky-gap')) || 12;
    const stageRect = stage.getBoundingClientRect();
    const centerRect = center.getBoundingClientRect();
    const naturalTop = centerRect.top - stageRect.top;
    const top = Math.max(gap, naturalTop);
    const docked = naturalTop <= gap + 1;

    left.style.top = `${top}px`;
    right.style.top = `${top}px`;
    stage.classList.toggle('cups-columns-docked', docked);
    scroll.classList.toggle('cups-content-docked', docked);

    if (stickyHead) {
      const headRect = stickyHead.getBoundingClientRect();
      const scrollRect = scroll.getBoundingClientRect();
      stickyHead.classList.toggle('is-stuck', scroll.scrollTop > 0 && headRect.top <= scrollRect.top + gap + 1);
    }
  }

  function requestUpdate() {
    if (frame) return;
    frame = requestAnimationFrame(updateFixedColumns);
  }

  scroll?.addEventListener('scroll', requestUpdate, { passive: true });
  window.addEventListener('resize', requestUpdate);
  window.addEventListener('load', requestUpdate);
  requestUpdate();

  const tabs = [...document.querySelectorAll('[data-cups-tab]')];
  const shortcuts = [...document.querySelectorAll('[data-cups-tab-shortcut]')];
  const serverFilterForm = document.querySelector('[data-cups-server-filter]');
  const platform = document.getElementById('cupPlatform');
  const reset = document.getElementById('resetCupFilters');

  if (serverFilterForm) {
    tabs.forEach((tab) => {
      tab.addEventListener('click', () => {
        if (tab.dataset.url) window.location.assign(tab.dataset.url);
      });
    });

    shortcuts.forEach((button) => {
      button.addEventListener('click', () => {
        const targetTab = tabs.find((tab) => tab.dataset.cupsTab === button.dataset.cupsTabShortcut);
        if (targetTab?.dataset.url) window.location.assign(targetTab.dataset.url);
      });
    });

    platform?.addEventListener('change', () => serverFilterForm.requestSubmit());
    reset?.addEventListener('click', () => {
      if (reset.dataset.resetUrl) window.location.assign(reset.dataset.resetUrl);
    });

    document.querySelectorAll('[data-url]').forEach((control) => {
      if (control.matches('[data-cups-tab]')) return;
      control.addEventListener('click', () => {
        if (control.dataset.url) window.location.assign(control.dataset.url);
      });
    });

    return;
  }

  const title = document.getElementById('cupsPanelTitle');
  const search = document.getElementById('cupSearch');
  const count = document.getElementById('visibleCupCount');
  const empty = document.getElementById('cupsEmptyState');
  let activeFilter = 'all';

  function applyFilters() {
    const query = (search?.value || '').trim().toLowerCase();
    const platformValue = platform?.value || 'all';
    const items = [...document.querySelectorAll('[data-cup-item]')];
    let visibleGridCards = 0;

    items.forEach((item) => {
      const status = item.dataset.status || '';
      const itemPlatform = item.dataset.platform || '';
      const mine = item.dataset.mine === 'true';
      const text = item.dataset.search || '';

      const matchesTab = activeFilter === 'all' || activeFilter === status || (activeFilter === 'mine' && mine);
      const matchesSearch = !query || text.includes(query);
      const matchesPlatform = platformValue === 'all' || itemPlatform === platformValue ||
        (platformValue === 'console' && ['console', 'ps5', 'xbox'].includes(itemPlatform));

      const visible = matchesTab && matchesSearch && matchesPlatform;
      item.hidden = !visible;
      if (visible && item.classList.contains('cup-card')) visibleGridCards += 1;
    });

    if (count) count.textContent = `${visibleGridCards} ${visibleGridCards === 1 ? 'Cup' : 'Cups'}`;
    if (empty) empty.hidden = visibleGridCards > 0;
  }

  function activateTab(name) {
    activeFilter = name;
    tabs.forEach((tab) => {
      const active = tab.dataset.cupsTab === name;
      tab.classList.toggle('active', active);
      tab.setAttribute('aria-selected', String(active));
      if (active && title) title.textContent = tab.dataset.title || tab.textContent.trim();
    });
    applyFilters();
  }

  tabs.forEach((tab) => tab.addEventListener('click', () => activateTab(tab.dataset.cupsTab)));
  shortcuts.forEach((button) => button.addEventListener('click', () => activateTab(button.dataset.cupsTabShortcut)));
  search?.addEventListener('input', applyFilters);
  platform?.addEventListener('change', applyFilters);
  reset?.addEventListener('click', () => {
    search.value = '';
    platform.value = 'all';
    activateTab('all');
  });

  activateTab('all');
})();
