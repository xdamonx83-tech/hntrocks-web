/* Compatibility loader for the stable comment media bridge. */
(() => {
  const base = '/assets/themes/hnt_preview/dashboard-feed/';

  const loadMediaBridge = () => {
    if (document.querySelector('script[data-real-comment-media-v3]')) return;

    const script = document.createElement('script');
    script.src = `${base}real-feed-comment-media-v3.js?v=20260711-2`;
    script.dataset.realCommentMediaV3 = '1';
    script.defer = true;
    document.body.appendChild(script);
  };

  if (document.querySelector('script[data-real-comment-upload-progress]')) {
    loadMediaBridge();
  } else {
    const progress = document.createElement('script');
    progress.src = `${base}real-feed-comment-upload-progress.js?v=20260711-1`;
    progress.dataset.realCommentUploadProgress = '1';
    progress.onload = loadMediaBridge;
    progress.onerror = loadMediaBridge;
    document.body.appendChild(progress);
  }
})();

/* Load real dashboard blocks. Agenda, community and header start immediately to remove static demo content. */
(() => {
  const base = '/assets/themes/hnt_preview/dashboard-feed/';

  const loadAgendaBehaviorFix = () => {
    if (document.querySelector('script[data-real-dashboard-agenda-behavior-fix]')) return;

    const script = document.createElement('script');
    script.src = `${base}real-dashboard-agenda-behavior-fix.js?v=20260717-1`;
    script.dataset.realDashboardAgendaBehaviorFix = '1';
    script.defer = true;
    document.body.appendChild(script);
  };

  const loadAgendaExpand = () => {
    const existing = document.querySelector('script[data-real-dashboard-agenda-expand]');
    if (existing) {
      if (existing.dataset.loaded === '1') loadAgendaBehaviorFix();
      else {
        existing.addEventListener('load', loadAgendaBehaviorFix, { once: true });
        existing.addEventListener('error', loadAgendaBehaviorFix, { once: true });
      }
      return;
    }

    const script = document.createElement('script');
    script.src = `${base}real-dashboard-agenda-expand.js?v=20260711-2`;
    script.dataset.realDashboardAgendaExpand = '1';
    script.defer = true;
    script.onload = () => {
      script.dataset.loaded = '1';
      loadAgendaBehaviorFix();
    };
    script.onerror = loadAgendaBehaviorFix;
    document.body.appendChild(script);
  };

  const loadAgenda = () => {
    const existing = document.querySelector('script[data-real-dashboard-agenda]');
    if (existing) {
      loadAgendaExpand();
      return;
    }

    const script = document.createElement('script');
    script.src = `${base}real-dashboard-agenda.js?v=20260711-4`;
    script.dataset.realDashboardAgenda = '1';
    script.defer = true;
    script.onload = loadAgendaExpand;
    script.onerror = loadAgendaExpand;
    document.body.appendChild(script);
  };

  const loadCommunityGuard = () => {
    if (document.querySelector('script[data-real-dashboard-community-guard]')) return;

    const guard = document.createElement('script');
    guard.src = `${base}real-dashboard-community-guard.js?v=20260711-1`;
    guard.dataset.realDashboardCommunityGuard = '1';
    guard.defer = true;
    document.body.appendChild(guard);
  };

  const loadCommunity = () => {
    if (document.querySelector('script[data-real-dashboard-community]')) {
      loadCommunityGuard();
      return;
    }

    const script = document.createElement('script');
    script.src = `${base}real-dashboard-community.js?v=20260711-3`;
    script.dataset.realDashboardCommunity = '1';
    script.defer = true;
    script.onload = loadCommunityGuard;
    script.onerror = loadCommunityGuard;
    document.body.appendChild(script);
  };

  const ensureHeaderFooterContrast = () => {
    if (document.getElementById('real-dashboard-header-footer-contrast')) return;

    const style = document.createElement('style');
    style.id = 'real-dashboard-header-footer-contrast';
    style.textContent = `
      .header-dropdown a.header-dropdown-footer,
      .header-dropdown button.header-dropdown-footer {
        color: #fff !important;
      }

      .header-dropdown .header-dropdown-footer svg,
      .header-dropdown .header-dropdown-footer svg * {
        color: #fff !important;
        stroke: currentColor !important;
      }

      .header-dropdown .header-dropdown-footer:hover,
      .header-dropdown .header-dropdown-footer:focus-visible {
        color: #fff !important;
      }
    `;
    document.head.appendChild(style);
  };

  const loadHeaderLive = () => {
    if (document.querySelector('script[data-real-dashboard-header-live]')) return;

    const live = document.createElement('script');
    live.src = `${base}real-dashboard-header-live.js?v=20260711-1`;
    live.dataset.realDashboardHeaderLive = '1';
    live.defer = true;
    document.body.appendChild(live);
  };

  const loadHeader = () => {
    ensureHeaderFooterContrast();
    if (document.querySelector('script[data-real-dashboard-header]')) {
      loadHeaderLive();
      return;
    }

    const script = document.createElement('script');
    script.src = `${base}real-dashboard-header.js?v=20260711-1`;
    script.dataset.realDashboardHeader = '1';
    script.defer = true;
    script.onload = loadHeaderLive;
    script.onerror = loadHeaderLive;
    document.body.appendChild(script);
  };

  const loadStreakRocksAndActivity = () => {
    if (document.querySelector('script[data-real-dashboard-streak-rocks]')) return;

    const script = document.createElement('script');
    script.src = `${base}real-dashboard-streak-rocks.js?v=20260711-2`;
    script.dataset.realDashboardStreakRocks = '1';
    script.defer = true;
    document.body.appendChild(script);
  };

  const loadProgress = () => {
    if (document.querySelector('script[data-real-dashboard-progress]')) return;

    const script = document.createElement('script');
    script.src = window.HNT_DASHBOARD_FEED_LIVE === true
      ? `${base}real-dashboard-progress-live.js?v=20260711-1`
      : `${base}real-dashboard-progress.js?v=20260711-1`;
    script.dataset.realDashboardProgress = '1';
    script.defer = true;
    document.body.appendChild(script);
  };

  loadAgenda();
  loadCommunity();
  loadHeader();
  loadProgress();
  loadStreakRocksAndActivity();
})();
