(() => {
  'use strict';

  if (window.matchMedia('(max-width: 768px)').matches) {
    return;
  }

  const zoomIn = document.querySelector('#hntMap .leaflet-control-zoom-in');
  const resetButton = document.querySelector('[data-map-reset]');

  if (!(zoomIn instanceof HTMLElement)) {
    return;
  }

  const query = new URLSearchParams(window.location.search);
  const hasSharedView = ['x', 'y', 'z'].every((key) => query.has(key));

  const applyPreferredStartZoom = () => {
    zoomIn.click();
  };

  if (!hasSharedView) {
    window.requestAnimationFrame(() => {
      window.requestAnimationFrame(applyPreferredStartZoom);
    });
  }

  resetButton?.addEventListener('click', () => {
    window.requestAnimationFrame(applyPreferredStartZoom);
  });
})();
