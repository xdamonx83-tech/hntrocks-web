(() => {
  const directory = document.querySelector('[data-teams-directory]');
  const toggle = document.querySelector('[data-teams-filter-toggle]');
  const scroll = document.getElementById('teamsScroll');

  if (directory && toggle) {
    toggle.addEventListener('click', () => {
      const open = !directory.classList.contains('is-filter-open');
      directory.classList.toggle('is-filter-open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  if (scroll) {
    const key = `hnt:teams:scroll:${window.location.pathname}${window.location.search}`;
    const saved = Number.parseInt(sessionStorage.getItem(key) || '0', 10);
    if (Number.isFinite(saved) && saved > 0) scroll.scrollTop = saved;
    window.addEventListener('pagehide', () => sessionStorage.setItem(key, String(scroll.scrollTop)), { once: true });
  }

  document.querySelectorAll('.teams-table-row form[action*="/join"]').forEach((form) => {
    form.addEventListener('submit', () => {
      const button = form.querySelector('button[type="submit"]');
      if (!button) return;
      button.disabled = true;
      button.setAttribute('aria-busy', 'true');
    });
  });
})();
