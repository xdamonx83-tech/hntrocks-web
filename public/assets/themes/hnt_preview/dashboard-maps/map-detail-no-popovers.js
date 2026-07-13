(() => {
  'use strict';

  if (!window.L?.Map) {
    return;
  }

  window.L.Map.prototype.openPopup = function () {
    this.closePopup?.();
    return this;
  };

  document.querySelectorAll('.leaflet-popup-pane').forEach((pane) => pane.remove());
})();
