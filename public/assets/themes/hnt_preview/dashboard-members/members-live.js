(() => {
  const modal = document.querySelector('[data-members-filter-modal]');
  const openButtons = document.querySelectorAll('[data-members-filter-open]');
  const closeButton = document.querySelector('[data-members-filter-close]');
  const filterStrip = document.querySelector('[data-members-filter-strip]');
  const toggleButtons = document.querySelectorAll('[data-members-filter-toggle]');

  const setModalOpen = (open) => {
    if (!modal) return;
    modal.classList.toggle('is-open', open);
    modal.setAttribute('aria-hidden', String(!open));
    document.body.classList.toggle('members-filter-open', open);

    if (open) {
      window.setTimeout(() => modal.querySelector('select, input, button')?.focus(), 0);
    }
  };

  openButtons.forEach((button) => button.addEventListener('click', () => setModalOpen(true)));
  closeButton?.addEventListener('click', () => setModalOpen(false));

  modal?.addEventListener('click', (event) => {
    if (event.target === modal) setModalOpen(false);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && modal?.classList.contains('is-open')) {
      setModalOpen(false);
    }
  });

  const setFilterStripCollapsed = (collapsed) => {
    if (!filterStrip) return;
    filterStrip.classList.toggle('is-collapsed', collapsed);
    toggleButtons.forEach((button) => button.setAttribute('aria-expanded', String(!collapsed)));
  };

  toggleButtons.forEach((button) => {
    button.setAttribute('aria-controls', 'membersFilterStrip');
    button.setAttribute('aria-expanded', 'true');
    button.addEventListener('click', () => {
      setFilterStripCollapsed(!filterStrip?.classList.contains('is-collapsed'));
    });
  });

  if (filterStrip) filterStrip.id = 'membersFilterStrip';
})();
