(() => {
    const root = document.querySelector('[data-maps-root]');
    if (!root) return;

    const buttons = [...root.querySelectorAll('[data-maps-view]')];
    const panels = [...root.querySelectorAll('[data-maps-panel]')];

    const activateView = (name, updateHash = false) => {
        const targetName = name === 'features' ? 'features' : 'maps';

        buttons.forEach((button) => {
            const active = button.dataset.mapsView === targetName;
            button.classList.toggle('active', active);
            button.setAttribute('aria-selected', String(active));
            button.tabIndex = active ? 0 : -1;
        });

        panels.forEach((panel) => {
            const active = panel.dataset.mapsPanel === targetName;
            panel.classList.toggle('active', active);
            panel.hidden = !active;
        });

        if (updateHash && window.history?.replaceState) {
            const nextUrl = new URL(window.location.href);
            nextUrl.hash = targetName === 'features' ? 'features' : '';
            window.history.replaceState(null, '', nextUrl);
        }
    };

    buttons.forEach((button) => {
        button.addEventListener('click', () => activateView(button.dataset.mapsView, true));

        button.addEventListener('keydown', (event) => {
            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
            event.preventDefault();

            const currentIndex = buttons.indexOf(button);
            let nextIndex = currentIndex;

            if (event.key === 'ArrowLeft') nextIndex = Math.max(0, currentIndex - 1);
            if (event.key === 'ArrowRight') nextIndex = Math.min(buttons.length - 1, currentIndex + 1);
            if (event.key === 'Home') nextIndex = 0;
            if (event.key === 'End') nextIndex = buttons.length - 1;

            buttons[nextIndex]?.focus();
            activateView(buttons[nextIndex]?.dataset.mapsView, true);
        });
    });

    root.querySelectorAll('[data-maps-view-shortcut]').forEach((button) => {
        button.addEventListener('click', () => {
            activateView(button.dataset.mapsViewShortcut, true);
            root.querySelector('.maps-view-switch')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    root.querySelectorAll('[data-map-card]').forEach((card) => {
        card.addEventListener('pointerenter', () => {
            root.querySelectorAll('[data-map-card]').forEach((item) => item.classList.remove('selected'));
            card.classList.add('selected');
        });
    });

    activateView(window.location.hash === '#features' ? 'features' : 'maps');
})();
