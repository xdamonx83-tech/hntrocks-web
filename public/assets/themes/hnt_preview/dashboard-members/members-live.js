(() => {
  const shell = document.querySelector('.members-page-shell');
  const header = shell?.querySelector(':scope > .site-header');
  const modal = shell?.querySelector(':scope > [data-members-filter-modal]');

  if (shell && header && !shell.querySelector(':scope > .members-stage')) {
    const stage = document.createElement('div');
    const scroll = document.createElement('div');

    stage.className = 'members-stage';
    scroll.className = 'members-scroll';
    stage.append(scroll);
    shell.insertBefore(stage, header.nextSibling);

    [...shell.children]
      .filter((child) => child !== header && child !== stage && child !== modal)
      .forEach((child) => scroll.append(child));
  }

  const directoryCard = shell?.querySelector('.members-directory-card');

  if (directoryCard && !directoryCard.querySelector(':scope > .members-sticky-panel')) {
    const actionShelf = directoryCard.querySelector(':scope > .members-action-shelf');
    const directoryHead = directoryCard.querySelector(':scope > .members-directory-head');
    const filterStripElement = directoryCard.querySelector(':scope > .members-filter-strip');
    const table = directoryCard.querySelector(':scope > .members-table');
    const tableHead = table?.querySelector(':scope > .members-table-head');
    const stickyPanel = document.createElement('div');

    stickyPanel.className = 'members-sticky-panel';

    [actionShelf, directoryHead, filterStripElement, tableHead].forEach((element) => {
      if (element) stickyPanel.append(element);
    });

    directoryCard.insertBefore(stickyPanel, table || directoryCard.firstChild);
  }

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
