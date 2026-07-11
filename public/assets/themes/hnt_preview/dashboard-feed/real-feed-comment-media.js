/* Compatibility loader for the stable comment media bridge. */
(() => {
  const base = '/assets/themes/hnt_preview/dashboard-feed/';

  if (document.querySelector('script[data-real-comment-media-v3]')) return;

  const script = document.createElement('script');
  script.src = `${base}real-feed-comment-media-v3.js?v=20260711-1`;
  script.dataset.realCommentMediaV3 = '1';
  script.defer = true;
  document.body.appendChild(script);
})();
