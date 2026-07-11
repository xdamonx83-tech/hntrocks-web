/* Compatibility loader for the robust comment media bridge. */
(() => {
  const base = '/assets/themes/hnt_preview/dashboard-feed/';

  if (document.querySelector('script[data-real-comment-media-v2]')) return;

  const script = document.createElement('script');
  script.src = `${base}real-feed-comment-media-v2.js?v=20260711-1`;
  script.dataset.realCommentMediaV2 = '1';
  script.defer = true;
  document.body.appendChild(script);
})();