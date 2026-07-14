(() => {
  'use strict';

  if (window.HNT_REAL_FEED_VIDEO_OPEN_READY) return;
  window.HNT_REAL_FEED_VIDEO_OPEN_READY = true;

  document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;

    const mediaItem = target.closest('.real-post-video-item');
    if (!mediaItem || mediaItem.closest('.real-media-viewer')) return;
    if (mediaItem.dataset.hntViewerRedispatch === '1') return;

    /* Keep the compact transport controls usable. Clicking the actual video
       surface or the large centre button opens the shared media viewer. */
    if (target.closest('.hnt-shared-video-controls')) return;

    event.preventDefault();
    event.stopImmediatePropagation();

    mediaItem.dataset.hntViewerRedispatch = '1';
    try {
      mediaItem.dispatchEvent(new MouseEvent('click', {
        bubbles: true,
        cancelable: true,
        view: window,
      }));
    } finally {
      delete mediaItem.dataset.hntViewerRedispatch;
    }
  }, true);
})();
