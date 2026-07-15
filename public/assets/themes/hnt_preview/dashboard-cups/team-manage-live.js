(() => {
  const shell = document.querySelector('.team-manage-page-shell');
  if (!shell) return;

  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const scroll = document.getElementById('teamManageScroll');
  const cupsNav = shell.querySelector('.nav-cups');
  const cupsTrigger = cupsNav?.querySelector(':scope > .main-nav-trigger');
  cupsNav?.classList.add('is-current');
  cupsTrigger?.classList.add('is-current');

  const routeMap = {
    'Aktive Cups': shell.dataset.cupsActiveUrl,
    'Meine Cup-Teams': shell.dataset.cupsMineUrl,
    'Einreichungen': shell.dataset.cupsSubmissionsUrl,
    'Hall of Fame': shell.dataset.cupsHallUrl,
  };

  cupsNav?.querySelectorAll('[data-navigation-label]').forEach((control) => {
    const target = routeMap[control.dataset.navigationLabel];
    if (!target) return;
    control.removeAttribute('aria-disabled');
    control.removeAttribute('data-unavailable');
    control.addEventListener('click', (event) => {
      event.preventDefault();
      window.location.assign(target);
    });
  });

  const tabs = Array.from(shell.querySelectorAll('[data-team-tab]'));
  const panels = Array.from(shell.querySelectorAll('[data-team-panel]'));
  const title = document.getElementById('teamPanelTitle');

  function activateTab(name, scrollToPanel = false) {
    const activeTab = tabs.find((tab) => tab.dataset.teamTab === name) || tabs[0];
    if (!activeTab) return;

    tabs.forEach((tab) => {
      const active = tab === activeTab;
      tab.classList.toggle('active', active);
      tab.setAttribute('aria-selected', String(active));
    });

    panels.forEach((panel) => {
      const active = panel.dataset.teamPanel === activeTab.dataset.teamTab;
      panel.classList.toggle('active', active);
      panel.hidden = !active;
    });

    if (title) title.textContent = activeTab.dataset.title || activeTab.textContent.trim();
    window.history.replaceState({}, '', `#team-${activeTab.dataset.teamTab}`);

    if (scrollToPanel) {
      shell.querySelector('.team-center-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  tabs.forEach((tab) => tab.addEventListener('click', () => activateTab(tab.dataset.teamTab)));
  shell.querySelectorAll('[data-team-tab-shortcut]').forEach((button) => {
    button.addEventListener('click', () => activateTab(button.dataset.teamTabShortcut, true));
  });

  const hashTab = window.location.hash.replace('#team-', '');
  activateTab(tabs.some((tab) => tab.dataset.teamTab === hashTab) ? hashTab : 'overview');

  shell.querySelector('[data-copy-invite]')?.addEventListener('click', async (event) => {
    const input = document.getElementById('teamInviteLink');
    if (!input) return;

    try {
      await navigator.clipboard.writeText(input.value);
      window.showToast?.('Einladungslink kopiert');
    } catch (_error) {
      input.focus();
      input.select();
      document.execCommand('copy');
      window.showToast?.('Einladungslink kopiert');
    }

    event.currentTarget.textContent = 'Kopiert';
    window.setTimeout(() => { event.currentTarget.textContent = 'Link kopieren'; }, 1400);
  });

  const leaveModal = shell.querySelector('[data-team-leave-modal]');
  const openLeave = shell.querySelector('[data-open-team-leave]');
  const closeLeaveButtons = shell.querySelectorAll('[data-close-team-leave]');

  function setLeaveModal(open) {
    if (!leaveModal) return;
    leaveModal.classList.toggle('is-open', open);
    leaveModal.setAttribute('aria-hidden', String(!open));
    document.documentElement.classList.toggle('hnt-modal-is-open', open);
    if (open) leaveModal.querySelector('[data-close-team-leave]')?.focus();
  }

  openLeave?.addEventListener('click', () => setLeaveModal(true));
  closeLeaveButtons.forEach((button) => button.addEventListener('click', () => setLeaveModal(false)));
  leaveModal?.addEventListener('click', (event) => {
    if (event.target === leaveModal) setLeaveModal(false);
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && leaveModal?.classList.contains('is-open')) setLeaveModal(false);
  });

  const chatPanel = shell.querySelector('[data-team-chat-panel]');
  const chatList = shell.querySelector('[data-team-chat-list]');
  const chatForm = shell.querySelector('[data-team-chat-form]');
  const chatCount = shell.querySelector('[data-team-chat-count]');
  const chatIndexUrl = chatPanel?.dataset.chatIndexUrl;
  let chatBusy = false;

  function scrollChatToEnd() {
    if (chatList) chatList.scrollTop = chatList.scrollHeight;
  }

  async function refreshChat(keepPosition = true) {
    if (!chatIndexUrl || chatBusy || document.hidden) return;
    chatBusy = true;
    const previousBottom = chatList ? chatList.scrollHeight - chatList.scrollTop - chatList.clientHeight : 0;

    try {
      const response = await fetch(chatIndexUrl, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });
      if (!response.ok) return;
      const payload = await response.json();
      if (chatList && typeof payload.html === 'string') {
        chatList.innerHTML = payload.html || '<p class="tm-empty" data-team-chat-empty>Noch keine Nachrichten.</p>';
        if (!keepPosition || previousBottom < 45) scrollChatToEnd();
      }
      if (chatCount && Number.isFinite(Number(payload.count))) chatCount.textContent = String(payload.count);
    } catch (_error) {
      // Existing content remains visible when a refresh fails.
    } finally {
      chatBusy = false;
    }
  }

  chatForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (chatBusy) return;

    const input = chatForm.querySelector('input[name="body"]');
    const body = input?.value.trim();
    if (!body) return;

    chatBusy = true;
    const submit = chatForm.querySelector('button[type="submit"]');
    submit?.setAttribute('disabled', 'disabled');

    try {
      const data = new FormData(chatForm);
      const response = await fetch(chatForm.action, {
        method: 'POST',
        body: data,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrf,
        },
        credentials: 'same-origin',
      });
      const payload = await response.json().catch(() => ({}));
      if (!response.ok) throw new Error(payload.message || 'Nachricht konnte nicht gesendet werden.');

      chatList?.querySelector('[data-team-chat-empty]')?.remove();
      if (chatList && payload.html) chatList.insertAdjacentHTML('beforeend', payload.html);
      if (chatCount && Number.isFinite(Number(payload.count))) chatCount.textContent = String(payload.count);
      if (input) input.value = '';
      scrollChatToEnd();
    } catch (error) {
      window.showToast?.(error.message || 'Nachricht konnte nicht gesendet werden.');
    } finally {
      chatBusy = false;
      submit?.removeAttribute('disabled');
      input?.focus();
    }
  });

  scrollChatToEnd();
  window.setInterval(() => refreshChat(true), 15000);
  window.addEventListener('focus', () => refreshChat(true));

  scroll?.addEventListener('scroll', () => {
    shell.classList.toggle('team-manage-is-scrolled', scroll.scrollTop > 10);
  }, { passive: true });
})();
