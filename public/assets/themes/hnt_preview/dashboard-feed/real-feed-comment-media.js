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
    return;
  }

  const progress = document.createElement('script');
  progress.src = `${base}real-feed-comment-upload-progress.js?v=20260711-1`;
  progress.dataset.realCommentUploadProgress = '1';
  progress.onload = loadMediaBridge;
  progress.onerror = loadMediaBridge;
  document.body.appendChild(progress);
})();

/* Load the real dashboard-data blocks without changing the preview controller. */
(() => {
  const base = '/assets/themes/hnt_preview/dashboard-feed/';

  const loadAgenda = () => {
    if (document.querySelector('script[data-real-dashboard-agenda]')) return;

    const script = document.createElement('script');
    script.src = `${base}real-dashboard-agenda.js?v=20260711-1`;
    script.dataset.realDashboardAgenda = '1';
    script.defer = true;
    document.body.appendChild(script);
  };

  const loadStreakRocksAndActivity = () => {
    if (document.querySelector('script[data-real-dashboard-streak-rocks]')) {
      loadAgenda();
      return;
    }

    const script = document.createElement('script');
    script.src = `${base}real-dashboard-streak-rocks.js?v=20260711-2`;
    script.dataset.realDashboardStreakRocks = '1';
    script.defer = true;
    script.onload = loadAgenda;
    script.onerror = loadAgenda;
    document.body.appendChild(script);
  };

  const existing = document.querySelector('script[data-real-dashboard-progress]');
  if (existing) {
    loadStreakRocksAndActivity();
    return;
  }

  const script = document.createElement('script');
  script.src = `${base}real-dashboard-progress.js?v=20260711-1`;
  script.dataset.realDashboardProgress = '1';
  script.defer = true;
  script.onload = loadStreakRocksAndActivity;
  script.onerror = loadStreakRocksAndActivity;
  document.body.appendChild(script);
})();
