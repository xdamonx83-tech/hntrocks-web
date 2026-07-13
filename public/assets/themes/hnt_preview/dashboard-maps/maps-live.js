(() => {
  const root = document.querySelector('[data-maps-root]');
  if (!root) return;

  const buttons = [...root.querySelectorAll('[data-maps-view]')];
  const panels = [...root.querySelectorAll('[data-maps-panel]')];

  function activateView(name) {
    buttons.forEach((button) => {
      const active = button.dataset.mapsView === name;
      button.classList.toggle('active', active);
      button.setAttribute('aria-selected', String(active));
    });

    panels.forEach((panel) => {
      const active = panel.dataset.mapsPanel === name;
      panel.classList.toggle('active', active);
      panel.hidden = !active;
    });
  }

  buttons.forEach((button) => button.addEventListener('click', () => activateView(button.dataset.mapsView)));

  root.querySelectorAll('[data-maps-view-shortcut]').forEach((button) => {
    button.addEventListener('click', () => activateView(button.dataset.mapsViewShortcut));
  });

  root.querySelectorAll('[data-map-preview]').forEach((button) => {
    button.addEventListener('click', () => {
      const slug = button.dataset.mapPreview;
      root.querySelectorAll('[data-map-card]').forEach((card) => {
        card.classList.toggle('selected', card.dataset.mapCard === slug);
      });

      const detailHref = button.dataset.detailHref;
      if (detailHref && detailHref !== '#') {
        window.location.href = detailHref;
      }
    });
  });

  activateView('maps');
})();
