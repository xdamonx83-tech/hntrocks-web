(() => {
  const ensureStylesheet = (href, marker) => {
    if (document.querySelector(`link[${marker}]`)) return;
    const style = document.createElement('link');
    style.rel = 'stylesheet';
    style.href = href;
    style.setAttribute(marker, '1');
    document.head.appendChild(style);
  };

  ensureStylesheet('/assets/themes/hnt_preview/dashboard-feed/real-feed.css?v=20260714-1', 'data-cup-detail-real-feed');
  ensureStylesheet('/assets/themes/hnt_preview/dashboard-feed/real-feed-polish.css?v=20260714-1', 'data-cup-detail-real-feed-polish');
  ensureStylesheet('/assets/themes/hnt_preview/dashboard-cups/cup-community-access.css?v=20260714-1', 'data-cup-detail-community-access');
  ensureStylesheet('/assets/themes/hnt_preview/dashboard-cups/cups-header-dropdown-reference.css?v=20260715-1', 'data-cup-detail-header-dropdown-reference');

  const shell = document.querySelector('.cup-detail-page-shell');
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
})();

(() => {
  const tabs = [...document.querySelectorAll('[data-cup-tab]')];
  const panels = [...document.querySelectorAll('[data-cup-panel]')];
  const title = document.getElementById('cupSectionTitle');
  const shell = document.querySelector('.cup-detail-page-shell');

  function activateCupTab(tabName) {
    tabs.forEach((button) => {
      const active = button.dataset.cupTab === tabName;
      button.classList.toggle('active', active);
      button.setAttribute('aria-selected', String(active));
    });

    panels.forEach((panel) => {
      const active = panel.dataset.cupPanel === tabName;
      panel.classList.toggle('active', active);
      panel.hidden = !active;
    });

    const activeTab = tabs.find((button) => button.dataset.cupTab === tabName);
    if (title && activeTab) title.textContent = activeTab.dataset.title || activeTab.textContent.trim();
  }

  tabs.forEach((button) => button.addEventListener('click', () => activateCupTab(button.dataset.cupTab)));
  document.querySelectorAll('[data-cup-tab-shortcut]').forEach((button) => {
    button.addEventListener('click', () => activateCupTab(button.dataset.cupTabShortcut));
  });

  document.querySelector('.cup-upload-zone input')?.addEventListener('change', (event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    const zone = event.target.closest('.cup-upload-zone');
    const label = zone?.querySelector('strong');
    if (label) label.textContent = file.name;
    if (typeof showToast === 'function') showToast(file.name + ' ausgewählt');
  });

  const sectionFromPath = {
    rules: 'rules',
    prizes: 'prizes',
    leaderboard: 'teams',
    participants: 'teams',
    teams: 'teams',
    submit: 'submit',
    submissions: 'submissions',
  };
  const routeSection = window.location.pathname.split('/').filter(Boolean).at(-1) || '';
  const requestedTab = shell?.dataset.cupActiveSection || sectionFromPath[routeSection] || 'overview';
  const initialTab = tabs.some((button) => button.dataset.cupTab === requestedTab) ? requestedTab : 'overview';

  activateCupTab(initialTab);
})();

(() => {
  const stage = document.querySelector('.cup-detail-stage');
  const scroll = document.getElementById('cupDetailScroll');
  const center = document.getElementById('cupCenterFlow');
  const fixedLeft = document.getElementById('cupFixedLeft');
  const fixedRight = document.getElementById('cupFixedRight');
  const sectionHead = document.querySelector('.cup-section-head');

  if (!stage || !scroll || !center || !fixedLeft || !fixedRight) return;

  let frame = 0;

  function updateCupLayout() {
    frame = 0;

    if (window.matchMedia('(max-width: 899px)').matches) {
      fixedLeft.style.removeProperty('top');
      fixedRight.style.removeProperty('top');
      stage.classList.remove('cup-columns-docked');
      scroll.classList.remove('cup-content-docked');
      sectionHead?.classList.remove('is-stuck');
      return;
    }

    const styles = getComputedStyle(stage);
    const gap = Number.parseFloat(styles.getPropertyValue('--cup-sticky-gap')) || 12;
    const stageRect = stage.getBoundingClientRect();
    const centerRect = center.getBoundingClientRect();
    const naturalTop = centerRect.top - stageRect.top;
    const docked = naturalTop <= gap + 1;
    const top = docked ? gap : naturalTop;

    fixedLeft.style.top = `${top}px`;
    fixedRight.style.top = `${top}px`;

    stage.classList.toggle('cup-columns-docked', docked);
    scroll.classList.toggle('cup-content-docked', docked);

    if (sectionHead) {
      const headRect = sectionHead.getBoundingClientRect();
      const scrollRect = scroll.getBoundingClientRect();
      sectionHead.classList.toggle(
        'is-stuck',
        scroll.scrollTop > 0 && headRect.top <= scrollRect.top + gap + 1
      );
    }
  }

  function requestCupLayoutUpdate() {
    if (frame) return;
    frame = window.requestAnimationFrame(updateCupLayout);
  }

  scroll.addEventListener('scroll', requestCupLayoutUpdate, { passive: true });
  window.addEventListener('resize', requestCupLayoutUpdate);
  window.addEventListener('load', requestCupLayoutUpdate);

  requestCupLayoutUpdate();
})();

(() => {
  const modal = document.getElementById('cupTeamCreateModal');
  if (!modal) return;

  const input = modal.querySelector('input[name="name"]');
  const openButtons = [...document.querySelectorAll('[data-open-cup-team-modal]')];
  const closeButtons = [...modal.querySelectorAll('[data-close-cup-team-modal]')];

  const openModal = () => {
    modal.hidden = false;
    document.body.classList.add('cup-team-modal-open');
    window.setTimeout(() => input?.focus(), 30);
  };

  const closeModal = () => {
    modal.hidden = true;
    document.body.classList.remove('cup-team-modal-open');
  };

  openButtons.forEach((button) => button.addEventListener('click', openModal));
  closeButtons.forEach((button) => button.addEventListener('click', closeModal));

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !modal.hidden) closeModal();
  });

  if (modal.dataset.autoOpen === '1') openModal();
})();
