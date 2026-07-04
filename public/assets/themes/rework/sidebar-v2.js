(() => {
  const sidebar = document.querySelector('[data-rework-sidebar]');
  const toggle = document.querySelector('[data-sidebar-toggle]');
  if (!sidebar || !toggle) return;

  const storageKey = 'hnt.rework.sidebar.v2.state';

  const setCollapsed = (collapsed, persist = true) => {
    document.body.classList.toggle('rework-sidebar-collapsed', collapsed);
    toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');

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

  toggle.addEventListener('click', () => {
    setCollapsed(!document.body.classList.contains('rework-sidebar-collapsed'));
  });
})();
