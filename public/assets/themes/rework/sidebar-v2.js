(() => {
  const sidebar = document.getElementById('appSidebar') || document.querySelector('[data-rework-sidebar]');
  const sidebarToggle = document.getElementById('sidebarToggle') || document.querySelector('[data-sidebar-toggle]');
  const dashboardToggle = document.getElementById('dashboardToggle');
  const routes = sidebar ? sidebar.querySelectorAll('[data-route]') : [];

  if (!sidebar || !sidebarToggle) return;

  const storageKey = 'hnt.rework.sidebar.v2.state';

  const setCollapsed = (collapsed, persist = true) => {
    sidebar.classList.toggle('is-collapsed', collapsed);
    sidebar.classList.toggle('is-expanded', !collapsed);
    document.body.classList.toggle('rework-sidebar-collapsed', collapsed);
    sidebarToggle.setAttribute('aria-expanded', String(!collapsed));
    sidebarToggle.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');

    if (!collapsed) {
      sidebar.classList.remove('is-flyout-open');
    }

    if (persist) {
      try {
        window.localStorage.setItem(storageKey, collapsed ? 'collapsed' : 'expanded');
      } catch (error) {
        // Sidebar preference is optional.
      }
    }
  };

  let stored = null;
  try {
    stored = window.localStorage.getItem(storageKey);
  } catch (error) {
    stored = null;
  }

  setCollapsed(stored === 'collapsed', false);

  sidebarToggle.addEventListener('click', () => {
    setCollapsed(!sidebar.classList.contains('is-collapsed'));
  });

  if (dashboardToggle) {
    dashboardToggle.addEventListener('click', (event) => {
      const collapsed = sidebar.classList.contains('is-collapsed');

      if (collapsed) {
        event.preventDefault();
        sidebar.classList.toggle('is-flyout-open');
        return;
      }

      // Keep Feed navigable on normal click. Alt-click toggles the visual submenu for testing.
      if (event.altKey) {
        event.preventDefault();
        sidebar.classList.toggle('is-sub-closed');
      }
    });
  }

  routes.forEach((route) => {
    route.addEventListener('click', () => {
      if (route.closest('.sub-list')) {
        sidebar.querySelectorAll('.sub-list .sub-item').forEach((item) => {
          item.classList.remove('sub-active');
          const glow = item.querySelector('.sub-light');
          if (glow) glow.remove();
        });

        route.classList.add('sub-active');
        if (!route.querySelector('.sub-light')) {
          const glow = document.createElement('span');
          glow.className = 'sub-light';
          glow.setAttribute('aria-hidden', 'true');
          route.prepend(glow);
        }
      }

      sidebar.classList.remove('is-flyout-open');
    });
  });

  document.addEventListener('click', (event) => {
    if (!sidebar.contains(event.target)) {
      sidebar.classList.remove('is-flyout-open');
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      sidebar.classList.remove('is-flyout-open');
    }
  });
})();
