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

  const enhanceCashSpotModal = () => {
    const modal = document.querySelector('[data-map-cash-spot-modal]');
    const panel = modal?.querySelector('.hnt-map-cash-submission-panel');
    const form = panel?.querySelector('[data-map-cash-spot-form]');
    const header = panel?.querySelector('.hnt-map-lightbox-header');
    const title = header?.querySelector('h2');
    const closeButton = header?.querySelector('[data-map-cash-spot-cancel]');
    const status = form?.querySelector('[data-map-cash-spot-status]');
    const actions = form?.querySelector('.hnt-map-cash-submission-actions');

    if (!(modal instanceof HTMLElement) || !(panel instanceof HTMLElement) || !(form instanceof HTMLFormElement) || !(header instanceof HTMLElement) || !(title instanceof HTMLElement)) {
      return;
    }

    modal.classList.add('map-cash-modal');
    panel.classList.add('map-cash-modal-panel');
    header.classList.add('map-cash-modal-head');
    form.classList.add('map-cash-modal-form');
    closeButton?.classList.add('map-cash-modal-close');
    status?.classList.add('map-cash-modal-status');
    actions?.classList.add('map-cash-modal-actions');

    if (!header.querySelector('.map-cash-modal-title')) {
      const titleBlock = document.createElement('div');
      const kicker = document.createElement('span');

      titleBlock.className = 'map-cash-modal-title';
      kicker.textContent = 'CASH SPOT';
      title.before(titleBlock);
      titleBlock.append(kicker, title);
    }

    const intro = form.querySelector(':scope > p:not([data-map-cash-spot-status])');
    intro?.classList.add('map-cash-modal-intro');

    form.querySelectorAll(':scope > label').forEach((label) => {
      label.classList.add('map-cash-modal-field');
      if (label.querySelector('input[type="file"]')) {
        label.classList.add('map-cash-modal-field--upload');
      }
      if (label.matches('[data-map-cash-spot-guest-field]')) {
        label.classList.add('map-cash-modal-field--guest');
      }
    });

    let coords = form.querySelector('.map-cash-modal-coords');
    if (!coords) {
      coords = document.createElement('div');
      coords.className = 'map-cash-modal-coords';

      const label = document.createElement('span');
      const value = document.createElement('strong');
      const isEnglish = (document.documentElement.lang || '').toLowerCase().startsWith('en');

      label.textContent = isEnglish ? 'Selected position' : 'Ausgewählte Position';
      value.textContent = 'X 0 · Y 0';
      value.dataset.mapCashSpotCoords = '';
      coords.append(label, value);

      if (status) {
        status.before(coords);
      } else if (actions) {
        actions.before(coords);
      } else {
        form.append(coords);
      }
    }

    const syncCoordinates = () => {
      const x = Number(form.elements.x?.value);
      const y = Number(form.elements.y?.value);
      const output = coords?.querySelector('[data-map-cash-spot-coords]');

      if (!(output instanceof HTMLElement)) {
        return;
      }

      output.textContent = Number.isFinite(x) && Number.isFinite(y)
        ? `X ${Math.round(x)} · Y ${Math.round(y)}`
        : 'X 0 · Y 0';
    };

    new MutationObserver(() => {
      if (!modal.hidden) {
        syncCoordinates();
      }
    }).observe(modal, { attributes: true, attributeFilter: ['hidden'] });

    form.addEventListener('reset', () => {
      window.setTimeout(syncCoordinates, 0);
    });

    syncCoordinates();
  };

  enhanceCashSpotModal();
  updateFilterState();
})();
