(() => {
  'use strict';

  const assetTypes = new Set(['boss', 'spawn', 'supply', 'cash', 'tower', 'bugs', 'wild']);
  const fallbackLabels = {
    boss: 'B',
    spawn: 'S',
    supply: 'S',
    cash: 'C',
    tower: 'T',
    bugs: 'B',
    wild: 'W',
  };

  const markerTypeFromElement = (element) => {
    const typeClass = Array.from(element.classList).find((className) => className.startsWith('marker-'));
    return typeClass ? typeClass.slice('marker-'.length) : '';
  };

  const restoreMarkerAsset = (element) => {
    if (!(element instanceof HTMLElement) || element.dataset.hntMarkerAssetReady === '1') {
      return;
    }

    const type = markerTypeFromElement(element);
    if (!assetTypes.has(type)) {
      return;
    }

    const holder = element.querySelector(':scope > span');
    if (!holder) {
      return;
    }

    const fallback = holder.textContent?.trim() || fallbackLabels[type] || '•';
    const image = document.createElement('img');
    image.src = `/assets/hnt/maps/icons/${type}.webp`;
    image.alt = '';
    image.draggable = false;
    image.decoding = 'async';

    image.addEventListener('error', () => {
      image.remove();
      holder.textContent = fallback;
      element.classList.remove('map-detail-leaflet-icon--asset');
      element.classList.add('map-detail-leaflet-icon--fallback');
    }, { once: true });

    holder.replaceChildren(image);
    element.classList.add('map-detail-leaflet-icon--asset');
    element.dataset.hntMarkerAssetReady = '1';
  };

  const scanMarkerAssets = (root = document) => {
    if (root instanceof Element && root.matches('.map-detail-leaflet-icon')) {
      restoreMarkerAsset(root);
    }

    root.querySelectorAll?.('.map-detail-leaflet-icon').forEach(restoreMarkerAsset);
  };

  const clearAutomaticSelection = () => {
    const popover = document.getElementById('mapSelectedPopover');
    const detailsButton = document.getElementById('mapOpenMarkerDetails');
    const commentsButton = document.getElementById('mapOpenMarkerComments');

    if (popover) {
      popover.hidden = true;
      popover.style.removeProperty('left');
      popover.style.removeProperty('top');
    }

    if (detailsButton) {
      detailsButton.disabled = true;
    }

    if (commentsButton) {
      commentsButton.disabled = true;
    }

    document.querySelectorAll('.map-detail-leaflet-icon.selected').forEach((element) => {
      element.classList.remove('selected');
    });
  };

  const fitCompleteMap = () => {
    const resetButton = document.getElementById('mapResetView');
    if (!resetButton) {
      return;
    }

    window.dispatchEvent(new Event('resize'));
    resetButton.click();

    const toast = document.getElementById('toast');
    if (toast) {
      toast.classList.remove('show');
      toast.textContent = '';
    }
  };

  const boot = () => {
    const mapHost = document.getElementById('mapCanvasSurface');
    if (!mapHost) {
      return;
    }

    clearAutomaticSelection();
    scanMarkerAssets(mapHost);

    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
          if (node instanceof Element) {
            scanMarkerAssets(node);
          }
        });
      });
    });

    observer.observe(mapHost, { childList: true, subtree: true });

    requestAnimationFrame(() => {
      clearAutomaticSelection();
      fitCompleteMap();
    });

    window.setTimeout(fitCompleteMap, 180);
    window.setTimeout(fitCompleteMap, 520);
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }
})();
