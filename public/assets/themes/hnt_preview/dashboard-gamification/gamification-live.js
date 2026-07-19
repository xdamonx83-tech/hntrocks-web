(() => {
  const root = document.querySelector('[data-gamification-dashboard]');
  if (!root) return;

  const tabs = [...root.querySelectorAll('[data-gamification-tab]')];
  const panels = [...root.querySelectorAll('[data-gamification-panel]')];
  const title = root.querySelector('#gamificationPanelTitle');
  const scroll = document.getElementById('gamificationScroll');
  const toastNode = document.getElementById('toast');
  const labels = {
    overview: root.dataset.overviewLabel || 'Übersicht',
    badges: root.dataset.badgesLabel || 'Badges',
    quests: root.dataset.questsLabel || 'Quests',
    history: root.dataset.historyLabel || 'XP-Verlauf'
  };

  function toast(message) {
    if (!message) return;
    if (typeof window.showToast === 'function') {
      window.showToast(message);
      return;
    }
    if (!toastNode) return;
    toastNode.textContent = message;
    toastNode.classList.add('visible');
    window.setTimeout(() => toastNode.classList.remove('visible'), 2200);
  }

  function openPanel(name, updateHash = true) {
    const panelName = panels.some((panel) => panel.dataset.gamificationPanel === name) ? name : 'overview';

    tabs.forEach((tab) => {
      const active = tab.dataset.gamificationTab === panelName;
      tab.classList.toggle('active', active);
      tab.setAttribute('aria-selected', active ? 'true' : 'false');
    });

    panels.forEach((panel) => {
      const active = panel.dataset.gamificationPanel === panelName;
      panel.hidden = !active;
      panel.classList.toggle('active', active);
    });

    if (title) title.textContent = labels[panelName] || labels.overview;
    if (updateHash) history.replaceState(null, '', `#${panelName}`);
    scroll?.scrollTo({ top: 0, behavior: 'smooth' });
  }

  tabs.forEach((tab) => tab.addEventListener('click', () => openPanel(tab.dataset.gamificationTab)));
  root.querySelectorAll('[data-open-panel]').forEach((button) => {
    button.addEventListener('click', () => openPanel(button.dataset.openPanel));
  });

  const selectedTitle = root.querySelector('#selectedQuestTitle');
  const selectedDescription = root.querySelector('#selectedQuestDescription');
  const selectedProgress = root.querySelector('#selectedQuestProgress');
  const selectedCount = root.querySelector('#selectedQuestCount');
  const selectedReward = root.querySelector('#selectedQuestReward');
  const selectedBar = root.querySelector('#selectedQuestBar');

  root.querySelectorAll('.quest-sidebar-list button[data-quest-title]').forEach((button) => {
    button.addEventListener('click', () => {
      root.querySelectorAll('.quest-sidebar-list button').forEach((item) => item.classList.remove('active'));
      button.classList.add('active');
      if (selectedTitle) selectedTitle.textContent = button.dataset.questTitle || '';
      if (selectedDescription) selectedDescription.textContent = button.dataset.questDescription || '';
      if (selectedProgress) selectedProgress.textContent = `${button.dataset.questProgress || 0}%`;
      if (selectedCount) selectedCount.textContent = button.dataset.questCount || '';
      if (selectedReward) selectedReward.textContent = button.dataset.questReward || '';
      if (selectedBar) selectedBar.style.width = `${button.dataset.questProgress || 0}%`;
      openPanel('overview');
    });
  });

  root.querySelectorAll('[data-gamification-toast]').forEach((button) => {
    button.addEventListener('click', () => toast(button.dataset.gamificationToast));
  });

  const requested = location.hash.replace('#', '');
  openPanel(requested || 'overview', false);
})();
