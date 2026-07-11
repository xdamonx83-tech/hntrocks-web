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

/* Load the first real dashboard-data block without changing the preview controller. */
(() => {
  const base = '/assets/themes/hnt_preview/dashboard-feed/';
  if (document.querySelector('script[data-real-dashboard-progress]')) return;

  const script = document.createElement('script');
  script.src = `${base}real-dashboard-progress.js?v=20260711-1`;
  script.dataset.realDashboardProgress = '1';
  script.defer = true;
  document.body.appendChild(script);
})();
