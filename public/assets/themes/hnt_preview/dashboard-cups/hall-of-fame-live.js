(() => {
  const root = document.querySelector('[data-hall-root]');
  if (!root) return;

  const cupsNav = document.querySelector('.nav-cups');
  const cupsTrigger = cupsNav?.querySelector(':scope > .main-nav-trigger');
  const hallMenuItem = cupsNav?.querySelector('[data-navigation-label="Hall of Fame"]');
  cupsNav?.classList.add('is-current');
  cupsTrigger?.classList.add('is-current');
  hallMenuItem?.classList.add('is-active');

  const modeButtons = [...root.querySelectorAll('[data-hall-mode]')];
  const teamSections = [...root.querySelectorAll('[data-hall-teams-section]')];
  const hunterSection = root.querySelector('[data-hall-hunters-section]');
  const seasonSelect = root.querySelector('[data-hall-season]');
  const toast = document.getElementById('toast');

  const showToast = (message) => {
    if (!toast || !message) return;
    toast.textContent = message;
    toast.classList.add('show');
    window.clearTimeout(showToast.timer);
    showToast.timer = window.setTimeout(() => toast.classList.remove('show'), 1800);
  };

  const seasonMatches = (element, season) => {
    if (!season || season === 'all') return true;
    const seasons = String(element.dataset.seasons || element.dataset.season || '')
      .split(',')
      .map((value) => value.trim())
      .filter(Boolean);
    return seasons.includes(season);
  };

  const applySeason = () => {
    const season = seasonSelect?.value || 'all';
    root.querySelectorAll('[data-seasons], [data-season]').forEach((element) => {
      element.classList.toggle('hall-filter-hidden', !seasonMatches(element, season));
    });
  };

  const setMode = (mode) => {
    const hunterMode = mode === 'hunters';
    modeButtons.forEach((button) => {
      const active = button.dataset.hallMode === mode;
      button.classList.toggle('active', active);
      button.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    teamSections.forEach((section) => {
      section.hidden = hunterMode;
    });
    if (hunterSection) hunterSection.hidden = !hunterMode;
    applySeason();
  };

  modeButtons.forEach((button) => {
    button.addEventListener('click', () => setMode(button.dataset.hallMode || 'teams'));
  });

  seasonSelect?.addEventListener('change', applySeason);

  root.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-toast]');
    if (trigger) showToast(trigger.dataset.toast || '');
  });

  setMode('teams');
})();
