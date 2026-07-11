(() => {
  const ensureSharedHeaderStyles = () => {
    const href = '/assets/themes/hnt_preview/dashboard-feed/feed.css';
    if ([...document.styleSheets].some((sheet) => sheet.href?.includes(href))) return;

    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = `${href}?v=${Date.now()}`;
    link.dataset.hntSharedHeaderStyles = '1';
    document.head.appendChild(link);
  };

  ensureSharedHeaderStyles();

  const tabs = [...document.querySelectorAll('[data-profile-tab]')];
  const panels = [...document.querySelectorAll('[data-profile-panel]')];
  const title = document.getElementById('profileTabTitle');

  const activate = (name, focus = false) => {
    tabs.forEach((tab) => {
      const active = tab.dataset.profileTab === name;
      tab.classList.toggle('active', active);
      tab.setAttribute('aria-selected', String(active));
      if (active && focus) tab.focus();
    });

    panels.forEach((panel) => {
      const active = panel.dataset.profilePanel === name;
      panel.hidden = !active;
      panel.classList.toggle('active', active);
    });

    const activeTab = tabs.find((tab) => tab.dataset.profileTab === name);
    if (title && activeTab) title.textContent = activeTab.dataset.title || activeTab.textContent.trim();
  };

  tabs.forEach((tab) => {
    tab.addEventListener('click', () => activate(tab.dataset.profileTab));
    tab.addEventListener('keydown', (event) => {
      if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
      event.preventDefault();
      const index = tabs.indexOf(tab);
      const direction = event.key === 'ArrowRight' ? 1 : -1;
      const next = tabs[(index + direction + tabs.length) % tabs.length];
      activate(next.dataset.profileTab, true);
    });
  });

  document.querySelectorAll('[data-profile-share]').forEach((button) => {
    button.addEventListener('click', async () => {
      try {
        if (navigator.share) {
          await navigator.share({ title: document.title, url: window.location.href });
        } else if (navigator.clipboard) {
          await navigator.clipboard.writeText(window.location.href);
          if (typeof window.showToast === 'function') window.showToast('Profil-Link kopiert');
        }
      } catch (error) {
        if (error?.name !== 'AbortError' && typeof window.showToast === 'function') {
          window.showToast('Teilen war nicht möglich');
        }
      }
    });
  });

  activate('posts');
})();
