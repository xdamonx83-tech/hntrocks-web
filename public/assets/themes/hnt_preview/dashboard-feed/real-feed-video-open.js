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

    /* The existing viewer already handles its large play button. This bridge
       adds the missing click target on the actual video surface itself. */
    const clickedVideoSurface = target instanceof HTMLVideoElement
      || Boolean(target.closest('video'));
    if (!clickedVideoSurface) return;
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
