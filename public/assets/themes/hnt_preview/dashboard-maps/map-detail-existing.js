(() => {
  'use strict';

  const configElement = document.getElementById('hntMapConfig');
  const mapHost = document.getElementById('hntMap');

  if (!configElement || !mapHost) {
    return;
  }

  let config = {};
  try {
    config = JSON.parse(configElement.textContent || '{}');
  } catch (_) {
    return;
  }

  document.querySelectorAll('[data-existing-map-url]').forEach((button) => {
    button.addEventListener('click', () => {
      const url = button.dataset.existingMapUrl;
      if (url) {
        window.location.assign(url);
      }
    });
  });

  const clickLeafletControl = (selector) => {
    const control = mapHost.querySelector(selector);
    if (control instanceof HTMLElement) {
      control.click();
    }
  };

  document.querySelector('[data-detail-zoom-in]')?.addEventListener('click', () => {
    clickLeafletControl('.leaflet-control-zoom-in');
  });

  document.querySelector('[data-detail-zoom-out]')?.addEventListener('click', () => {
    clickLeafletControl('.leaflet-control-zoom-out');
  });

  const filterLabels = [...document.querySelectorAll('.map-filter-chip')];
  const filterInputs = [...document.querySelectorAll('[data-map-filter]')];
  const visibleCount = document.getElementById('mapVisibleCount');
  const searchInput = document.getElementById('hntMapSearch');

  const updateFilterState = () => {
    filterLabels.forEach((label) => {
      const input = label.querySelector('[data-map-filter]');
      label.classList.toggle('active', Boolean(input?.checked));
    });

    if (visibleCount) {
      const activeTypes = new Set(filterInputs.filter((input) => input.checked).map((input) => input.value));
      const count = (config.markers || []).filter((marker) => activeTypes.has(marker.type)).length;
      visibleCount.textContent = `${new Intl.NumberFormat(document.documentElement.lang || 'de-DE').format(count)} Marker`;
    }
  };

  filterInputs.forEach((input) => input.addEventListener('change', updateFilterState));

  document.getElementById('resetMapFilters')?.addEventListener('click', () => {
    filterInputs.forEach((input) => {
      if (!input.checked) {
        input.checked = true;
        input.dispatchEvent(new Event('change', { bubbles: true }));
      }
    });

    if (searchInput) {
      searchInput.value = '';
      searchInput.dispatchEvent(new Event('input', { bubbles: true }));
    }

    updateFilterState();
  });

  document.getElementById('toggleMapLabels')?.addEventListener('change', (event) => {
    mapHost.classList.toggle('hide-compound-labels', !event.target.checked);
  });

  const mapSelect = document.querySelector('[data-map-select]');
  if (mapSelect instanceof HTMLSelectElement) {
    mapSelect.hidden = true;
  }

  updateFilterState();
})();
