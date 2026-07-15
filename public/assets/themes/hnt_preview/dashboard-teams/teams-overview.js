(() => {
  const directory = document.querySelector('[data-teams-directory]');
  const toggle = document.querySelector('[data-teams-filter-toggle]');

  if (directory && toggle) {
    toggle.addEventListener('click', () => {
      const open = !directory.classList.contains('is-filter-open');
      directory.classList.toggle('is-filter-open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  document.querySelectorAll('[data-teams-join-form]').forEach((form) => {
    form.addEventListener('submit', () => {
      const button = form.querySelector('button[type="submit"]');
      if (!button || button.disabled) return;

      button.disabled = true;
      button.setAttribute('aria-busy', 'true');
    });
  });
})();
