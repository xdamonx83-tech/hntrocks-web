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

  const isEnglish = (document.documentElement.lang || '').toLowerCase().startsWith('en');

  if (!document.querySelector('link[data-hnt-map-cash-modal]')) {
    const modalStyles = document.createElement('link');
    modalStyles.rel = 'stylesheet';
    modalStyles.href = '/assets/themes/hnt_preview/dashboard-maps/map-detail-cash-modal.css?v=1';
    modalStyles.dataset.hntMapCashModal = '1';
    document.head.append(modalStyles);
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

  const enhanceCashSpotSubmissionModal = () => {
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

  const enhanceCashSpotDetailModal = (modal) => {
    if (!(modal instanceof HTMLElement) || modal.dataset.hntDemoModal === '1') {
      return;
    }

    const panel = modal.querySelector('.hnt-map-cash-detail-panel');
    const body = panel?.querySelector('.hnt-map-cash-detail-body');
    const media = body?.querySelector('.hnt-map-cash-detail-media');
    const info = body?.querySelector('.hnt-map-cash-detail-info');
    const header = info?.querySelector('.hnt-map-cash-detail-header');
    const titleBlock = header?.querySelector('.hnt-map-cash-detail-title-block');
    const genericTitle = titleBlock?.querySelector('h2');
    const markerTitle = titleBlock?.querySelector('.hnt-map-cash-detail-label');
    const closeButton = header?.querySelector('.hnt-map-lightbox-close');
    const voteSection = info?.querySelector('.hnt-map-cash-detail-votes');
    const comments = info?.querySelector('.hnt-map-cash-detail-comments');

    if (!(panel instanceof HTMLElement) || !(body instanceof HTMLElement) || !(media instanceof HTMLElement) || !(info instanceof HTMLElement) || !(header instanceof HTMLElement) || !(titleBlock instanceof HTMLElement) || !(markerTitle instanceof HTMLElement) || !(closeButton instanceof HTMLButtonElement) || !(voteSection instanceof HTMLElement) || !(comments instanceof HTMLElement)) {
      return;
    }

    modal.dataset.hntDemoModal = '1';
    modal.classList.add('map-cash-detail-demo');
    panel.classList.add('map-cash-detail-demo-panel');
    body.classList.add('map-cash-detail-demo-body');
    media.classList.add('map-cash-detail-demo-media');
    info.classList.add('map-cash-detail-demo-info');
    header.classList.add('map-cash-detail-demo-head');
    titleBlock.classList.add('map-cash-detail-demo-title-block');
    genericTitle?.classList.add('map-cash-detail-demo-generic-title');
    markerTitle.classList.add('map-cash-detail-demo-title');
    closeButton.classList.add('map-cash-detail-demo-close');
    voteSection.classList.add('map-cash-detail-demo-votes');

    const grip = panel.querySelector('.hnt-map-cash-detail-grip');
    grip?.remove();
    panel.insertBefore(header, body);

    let typeBadge = media.querySelector('.map-cash-detail-demo-type');
    if (!typeBadge) {
      typeBadge = document.createElement('span');
      typeBadge.className = 'map-cash-detail-demo-type';
      typeBadge.textContent = 'Cash Spot';
      media.append(typeBadge);
    }

    const meta = document.createElement('div');
    meta.className = 'map-cash-detail-demo-meta';
    meta.innerHTML = `
      <article><span>${isEnglish ? 'Area' : 'Bereich'}</span><strong data-cash-detail-area></strong></article>
      <article><span>${isEnglish ? 'Coordinates' : 'Koordinaten'}</span><strong data-cash-detail-coords></strong></article>
      <article><span>Status</span><strong class="verified">${isEnglish ? 'Approved' : 'Bestätigt'}</strong></article>
    `;
    info.insertBefore(meta, voteSection);

    const footer = document.createElement('footer');
    const commentsButton = document.createElement('button');
    const footerCloseButton = document.createElement('button');

    footer.className = 'map-cash-detail-demo-actions';
    commentsButton.type = 'button';
    commentsButton.textContent = isEnglish ? 'View comments' : 'Kommentare ansehen';
    footerCloseButton.type = 'button';
    footerCloseButton.textContent = isEnglish ? 'Close' : 'Schließen';
    footer.append(commentsButton, footerCloseButton);
    info.append(footer);

    const commentsModal = document.createElement('div');
    const commentsPanel = document.createElement('div');
    const commentsClose = document.createElement('button');
    const commentsHeader = comments.querySelector(':scope > header');

    commentsModal.className = 'map-overlay-modal map-cash-comments-modal';
    commentsModal.hidden = true;
    commentsPanel.className = 'map-overlay-panel map-cash-comments-panel';
    commentsClose.type = 'button';
    commentsClose.className = 'map-cash-comments-close';
    commentsClose.setAttribute('aria-label', isEnglish ? 'Close comments' : 'Kommentare schließen');
    commentsClose.textContent = '×';
    commentsHeader?.append(commentsClose);
    comments.classList.add('map-cash-comments-demo-card');
    commentsPanel.append(comments);
    commentsModal.append(commentsPanel);
    document.body.append(commentsModal);

    const closeComments = () => {
      commentsModal.hidden = true;
      document.body.classList.remove('map-overlay-open');
      commentsButton.focus();
    };

    commentsButton.addEventListener('click', () => {
      commentsModal.hidden = false;
      document.body.classList.add('map-overlay-open');
      commentsClose.focus();
    });
    commentsClose.addEventListener('click', closeComments);
    commentsModal.addEventListener('click', (event) => {
      if (event.target === commentsModal) {
        closeComments();
      }
    });
    footerCloseButton.addEventListener('click', () => closeButton.click());

    const syncDetail = () => {
      const label = markerTitle.textContent?.trim() || '';
      const marker = (config.markers || []).find((item) => item.type === 'cash' && String(item.label || '').trim() === label);
      const area = meta.querySelector('[data-cash-detail-area]');
      const coords = meta.querySelector('[data-cash-detail-coords]');
      const mapName = document.querySelector('.map-canvas-toolbar h2')?.textContent?.trim() || document.querySelector('.map-detail-heading h1')?.textContent?.trim() || '';

      if (area instanceof HTMLElement) {
        area.textContent = mapName;
      }
      if (coords instanceof HTMLElement) {
        coords.textContent = marker
          ? `X ${Math.round(Number(marker.x) || 0)} · Y ${Math.round(Number(marker.y) || 0)}`
          : 'X 0 · Y 0';
      }
      commentsModal.hidden = true;
    };

    new MutationObserver(() => {
      if (!modal.hidden) {
        syncDetail();
      } else {
        commentsModal.hidden = true;
        document.body.classList.remove('map-overlay-open');
      }
    }).observe(modal, { attributes: true, attributeFilter: ['hidden'] });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && !commentsModal.hidden) {
        event.stopImmediatePropagation();
        closeComments();
      }
    }, true);

    syncDetail();
  };

  const detailModalObserver = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (!(node instanceof Element)) {
          return;
        }
        if (node.matches('.hnt-map-cash-detail')) {
          enhanceCashSpotDetailModal(node);
        }
        node.querySelectorAll?.('.hnt-map-cash-detail').forEach(enhanceCashSpotDetailModal);
      });
    });
  });

  detailModalObserver.observe(document.body, { childList: true, subtree: true });
  document.querySelectorAll('.hnt-map-cash-detail').forEach(enhanceCashSpotDetailModal);

  enhanceCashSpotSubmissionModal();
  updateFilterState();
})();
