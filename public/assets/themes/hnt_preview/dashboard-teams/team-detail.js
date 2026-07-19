(() => {
  const tabs = [...document.querySelectorAll('[data-team-tab]')];
  const panels = [...document.querySelectorAll('[data-team-panel]')];
  const openers = [...document.querySelectorAll('[data-open-team-tab]')];
  const title = document.getElementById('teamPanelTitle');
  const scroll = document.getElementById('teamDetailScroll');
  const locale = document.documentElement.lang?.toLowerCase().startsWith('en') ? 'en' : 'de';

  if (!panels.length) return;

  const labels = {
    overview: locale === 'en' ? 'Overview' : 'Übersicht',
    posts: locale === 'en' ? 'Posts' : 'Beiträge',
    info: 'Info',
    members: locale === 'en' ? 'Members' : 'Mitglieder',
    requests: locale === 'en' ? 'Requests' : 'Anfragen',
  };

  const showToast = (message) => {
    if (typeof window.showToast === 'function') {
      window.showToast(message);
      return;
    }

    const node = document.getElementById('toast');
    if (!node) return;
    node.textContent = message;
    node.classList.add('show');
    window.clearTimeout(node._hntTimer);
    node._hntTimer = window.setTimeout(() => node.classList.remove('show'), 2200);
  };

  const syncScrollState = () => {
    scroll?.classList.toggle('is-scrolled', (scroll?.scrollTop || 0) > 8);
  };

  const openTeamPanel = (requestedName, updateHash = true) => {
    const name = panels.some((panel) => panel.dataset.teamPanel === requestedName)
      ? requestedName
      : 'overview';

    tabs.forEach((tab) => {
      const active = tab.dataset.teamTab === name;
      tab.classList.toggle('active', active);
      tab.setAttribute('aria-selected', active ? 'true' : 'false');
    });

    openers.forEach((opener) => {
      opener.classList.toggle('active', opener.dataset.openTeamTab === name);
    });

    panels.forEach((panel) => {
      const active = panel.dataset.teamPanel === name;
      panel.hidden = !active;
      panel.classList.toggle('active', active);
    });

    if (title) title.textContent = labels[name] || labels.overview;
    if (updateHash) history.replaceState(null, '', `#${name}`);
    scroll?.scrollTo({ top: 0, behavior: 'smooth' });
    syncScrollState();
  };

  tabs.forEach((tab) => {
    tab.addEventListener('click', () => openTeamPanel(tab.dataset.teamTab));
  });

  openers.forEach((opener) => {
    opener.addEventListener('click', () => openTeamPanel(opener.dataset.openTeamTab));
  });

  document.querySelectorAll('[data-copy-team-url]').forEach((button) => {
    button.addEventListener('click', async () => {
      const url = button.dataset.copyTeamUrl || window.location.href;

      try {
        await navigator.clipboard.writeText(url);
        showToast(locale === 'en' ? 'Team link copied' : 'Teamlink kopiert');
      } catch (_error) {
        const helper = document.createElement('textarea');
        helper.value = url;
        helper.setAttribute('readonly', '');
        helper.style.position = 'fixed';
        helper.style.opacity = '0';
        document.body.appendChild(helper);
        helper.select();
        document.execCommand('copy');
        helper.remove();
        showToast(locale === 'en' ? 'Team link copied' : 'Teamlink kopiert');
      }
    });
  });

  document.querySelectorAll('.team-media-picker input[type="file"]').forEach((input) => {
    input.addEventListener('change', () => {
      const label = input.closest('.team-media-picker');
      if (!label) return;
      const count = input.files?.length || 0;
      label.classList.toggle('has-files', count > 0);
      label.dataset.fileCount = String(count);
      if (count > 0) {
        showToast(locale === 'en'
          ? `${count} file${count === 1 ? '' : 's'} selected`
          : `${count} Datei${count === 1 ? '' : 'en'} ausgewählt`);
      }
    });
  });

  scroll?.addEventListener('scroll', syncScrollState, { passive: true });
  syncScrollState();
  openTeamPanel(window.location.hash.replace('#', '') || 'overview', false);
})();
