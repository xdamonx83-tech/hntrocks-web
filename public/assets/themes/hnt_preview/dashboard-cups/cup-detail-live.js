(() => {
  const ensureStylesheet = (href, marker) => {
    if (document.querySelector(`link[${marker}]`)) return;

    const style = document.createElement('link');
    style.rel = 'stylesheet';
    style.href = href;
    style.setAttribute(marker, '1');
    document.head.appendChild(style);
  };

  ensureStylesheet(
    '/assets/themes/hnt_preview/dashboard-feed/real-feed.css?v=20260714-1',
    'data-cup-detail-real-feed'
  );
  ensureStylesheet(
    '/assets/themes/hnt_preview/dashboard-feed/real-feed-polish.css?v=20260714-1',
    'data-cup-detail-real-feed-polish'
  );
  ensureStylesheet(
    '/assets/themes/hnt_preview/dashboard-cups/cup-detail-feed-alignment.css?v=20260714-1',
    'data-cup-detail-feed-alignment'
  );
  ensureStylesheet(
    '/assets/themes/hnt_preview/dashboard-cups/cup-community-access.css?v=20260714-1',
    'data-cup-community-access'
  );
  ensureStylesheet(
    '/assets/themes/hnt_preview/shared/hnt-modal.css?v=20260715-1',
    'data-hnt-shared-modal'
  );

  const shell = document.querySelector('.cup-detail-page-shell');
  if (!shell) return;

  window.HNT_DASHBOARD_HEADER_ENDPOINT = '/feed';

  const loadScript = (src, marker, onload) => {
    const existing = document.querySelector(`script[${marker}]`);
    if (existing) {
      onload?.();
      return;
    }

    const script = document.createElement('script');
    script.src = src;
    script.async = false;
    script.setAttribute(marker, '1');
    if (onload) script.addEventListener('load', onload, { once: true });
    document.body.appendChild(script);
  };

  loadScript(
    '/assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js?v=20260714-1',
    'data-cup-detail-header-runtime',
    () => loadScript(
      '/assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js?v=20260714-1',
      'data-cup-detail-header-live'
    )
  );

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

  const cupsMenu = cupsNav?.querySelector('.main-nav-menu-grid');
  if (cupsMenu && !cupsMenu.querySelector('[data-community-cup-create]')) {
    const createControl = document.createElement('button');
    const english = document.documentElement.lang.toLowerCase().startsWith('en');
    createControl.type = 'button';
    createControl.dataset.communityCupCreate = '1';
    createControl.innerHTML = `<span class="main-nav-menu-icon"><svg><use href="#i-plus"></use></svg></span><span><strong>${english ? 'Create cup' : 'Cup erstellen'}</strong><small>${english ? 'Host your own community cup' : 'Eigenen Community-Cup veranstalten'}</small></span>`;
    createControl.addEventListener('click', () => window.location.assign('/cups/create'));
    cupsMenu.insertBefore(createControl, cupsMenu.children[1] || null);
  }

  const tabs = Array.from(shell.querySelectorAll('[data-cup-tab]'));
  const panels = Array.from(shell.querySelectorAll('[data-cup-panel]'));
  const title = shell.querySelector('#cupSectionTitle');
  const pageScroll = shell.querySelector('.cup-detail-stage');

  function activateTab(name, updateUrl = true) {
    const activeTab = tabs.find((tab) => tab.dataset.cupTab === name) || tabs[0];
    if (!activeTab) return;

    tabs.forEach((tab) => {
      const active = tab === activeTab;
      tab.classList.toggle('active', active);
      tab.setAttribute('aria-selected', String(active));
    });

    panels.forEach((panel) => {
      const active = panel.dataset.cupPanel === activeTab.dataset.cupTab;
      panel.classList.toggle('active', active);
      panel.hidden = !active;
    });

    if (title) title.textContent = activeTab.dataset.title || activeTab.textContent.trim();

    if (updateUrl && activeTab.dataset.url) {
      window.history.replaceState({}, '', activeTab.dataset.url);
    }
  }

  tabs.forEach((tab) => {
    tab.addEventListener('click', () => activateTab(tab.dataset.cupTab));
  });

  shell.querySelectorAll('[data-cup-tab-shortcut]').forEach((control) => {
    control.addEventListener('click', () => {
      activateTab(control.dataset.cupTabShortcut);
      shell.querySelector('#cupSections')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });

  const initial = shell.dataset.cupActiveSection || 'overview';
  activateTab(initial, false);

  shell.querySelector('[data-cup-share]')?.addEventListener('click', async () => {
    const url = shell.dataset.cupShareUrl || window.location.href;
    try {
      if (navigator.share) {
        await navigator.share({ title: document.title, url });
        return;
      }
      await navigator.clipboard.writeText(url);
      window.showToast?.('Link kopiert');
    } catch (_error) {
      window.prompt('Link kopieren', url);
    }
  });

  shell.querySelector('[data-cup-file]')?.addEventListener('change', (event) => {
    const input = event.currentTarget;
    const file = input.files?.[0];
    const zone = input.closest('.cup-upload-zone');
    const titleNode = zone?.querySelector('strong');
    if (file && titleNode) titleNode.textContent = file.name;
  });

  const setupTeamCreateModal = () => {
    const participation = shell.querySelector('.cup-my-team');
    const action = participation?.querySelector(':scope > .cup-team-button');
    const alreadyHasTeam = Boolean(participation?.querySelector('.cup-team-members'));

    if (!action || alreadyHasTeam) return;

    let teamStoreUrl;
    try {
      const candidate = new URL(action.href, window.location.origin);
      if (!/\/cups\/[^/]+\/teams\/?$/.test(candidate.pathname)) return;
      teamStoreUrl = candidate.href;
    } catch (_error) {
      return;
    }

    const english = document.documentElement.lang.toLowerCase().startsWith('en');
    const labels = english
      ? {
          create: 'Create team',
          find: 'Find an existing team',
          kicker: 'HNT.ROCKS CUP',
          title: 'Create your team',
          intro: 'Create a team for this Cup. You become captain and can invite your teammates afterwards.',
          cup: 'Cup',
          role: 'Your role',
          captain: 'Captain',
          teamSize: 'Team size',
          members: 'members',
          field: 'Team name',
          placeholder: 'e.g. Bayou Hunters',
          noteTitle: 'You stay in control',
          note: 'After creating the team, you can manage its name, invitations and members.',
          cancel: 'Cancel',
          submitting: 'Creating team…',
          close: 'Close modal',
        }
      : {
          create: 'Team erstellen',
          find: 'Bestehendes Team finden',
          kicker: 'HNT.ROCKS CUP',
          title: 'Dein Team erstellen',
          intro: 'Erstelle dein Team für diesen Cup. Du wirst Captain und kannst anschließend deine Mitspieler einladen.',
          cup: 'Cup',
          role: 'Deine Rolle',
          captain: 'Captain',
          teamSize: 'Teamgröße',
          members: 'Mitglieder',
          field: 'Teamname',
          placeholder: 'z. B. Bayou Hunters',
          noteTitle: 'Du behältst die Kontrolle',
          note: 'Nach der Erstellung kannst du Teamname, Einladungen und Mitglieder verwalten.',
          cancel: 'Abbrechen',
          submitting: 'Team wird erstellt…',
          close: 'Modal schließen',
        };

    const escapeHtml = (value = '') => String(value).replace(/[&<>"']/g, (character) => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;',
    })[character]);

    const cupTitle = shell.querySelector('.cup-cover-copy strong')?.textContent.trim()
      || document.title.split('·')[0].trim()
      || 'HNT.ROCKS Cup';

    const teamSizeRow = Array.from(shell.querySelectorAll('.cup-data-card dl > div')).find((row) => {
      const key = row.querySelector('dt')?.textContent.trim().toLowerCase() || '';
      return key.includes('teamgröße') || key.includes('team size');
    });
    const teamSize = teamSizeRow?.querySelector('dd')?.textContent.trim() || '3';
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    action.innerHTML = `${escapeHtml(labels.create)} <svg><use href="#i-plus"></use></svg>`;
    action.setAttribute('role', 'button');
    action.setAttribute('aria-haspopup', 'dialog');
    action.setAttribute('aria-controls', 'hntCupTeamCreateModal');

    const findLink = document.createElement('a');
    findLink.className = 'hnt-team-create-secondary';
    findLink.href = teamStoreUrl;
    findLink.textContent = labels.find;
    action.insertAdjacentElement('afterend', findLink);

    const backdrop = document.createElement('div');
    backdrop.className = 'hnt-modal-backdrop';
    backdrop.id = 'hntCupTeamCreateModal';
    backdrop.setAttribute('aria-hidden', 'true');
    backdrop.innerHTML = `
      <section class="hnt-modal hnt-team-create-modal" role="dialog" aria-modal="true" aria-labelledby="hntCupTeamCreateTitle">
        <form action="${escapeHtml(teamStoreUrl)}" method="post" data-hnt-team-create-form>
          <input type="hidden" name="_token" value="${escapeHtml(csrf)}">
          <header class="hnt-modal__head">
            <div class="hnt-modal__head-copy">
              <span class="hnt-modal__kicker">${escapeHtml(labels.kicker)}</span>
              <h2 id="hntCupTeamCreateTitle">${escapeHtml(labels.title)}</h2>
              <p>${escapeHtml(labels.intro)}</p>
            </div>
            <button class="hnt-modal__close" type="button" aria-label="${escapeHtml(labels.close)}" data-hnt-team-modal-close>
              <svg><use href="#i-x"></use></svg>
            </button>
          </header>
          <div class="hnt-modal__body">
            <div class="hnt-modal__facts">
              <div class="hnt-modal__fact"><span>${escapeHtml(labels.cup)}</span><strong>${escapeHtml(cupTitle)}</strong></div>
              <div class="hnt-modal__fact"><span>${escapeHtml(labels.teamSize)}</span><strong>${escapeHtml(teamSize)} ${escapeHtml(labels.members)}</strong></div>
              <div class="hnt-modal__fact"><span>${escapeHtml(labels.role)}</span><strong>${escapeHtml(labels.captain)}</strong></div>
            </div>
            <label class="hnt-modal__field">
              <span class="hnt-modal__field-head"><span>${escapeHtml(labels.field)}</span><small data-hnt-team-name-count>0 / 100</small></span>
              <input class="hnt-modal__input" name="name" type="text" maxlength="100" required autocomplete="off" placeholder="${escapeHtml(labels.placeholder)}" data-hnt-team-name-input>
            </label>
            <div class="hnt-modal__note">
              <span class="hnt-modal__note-icon"><svg><use href="#i-users"></use></svg></span>
              <div><strong>${escapeHtml(labels.noteTitle)}</strong><span>${escapeHtml(labels.note)}</span></div>
            </div>
          </div>
          <footer class="hnt-modal__footer">
            <button class="hnt-modal__button hnt-modal__button--secondary" type="button" data-hnt-team-modal-close>${escapeHtml(labels.cancel)}</button>
            <button class="hnt-modal__button hnt-modal__button--primary" type="submit" data-hnt-team-create-submit>${escapeHtml(labels.create)}</button>
          </footer>
        </form>
      </section>
    `;
    document.body.appendChild(backdrop);

    const modal = backdrop.querySelector('.hnt-modal');
    const form = backdrop.querySelector('[data-hnt-team-create-form]');
    const input = backdrop.querySelector('[data-hnt-team-name-input]');
    const count = backdrop.querySelector('[data-hnt-team-name-count]');
    const submit = backdrop.querySelector('[data-hnt-team-create-submit]');
    const closeButtons = backdrop.querySelectorAll('[data-hnt-team-modal-close]');
    let previousFocus = null;

    const focusableSelector = 'button:not([disabled]), a[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

    const openModal = () => {
      previousFocus = document.activeElement;
      backdrop.classList.add('is-open');
      backdrop.setAttribute('aria-hidden', 'false');
      document.documentElement.classList.add('hnt-modal-is-open');
      window.requestAnimationFrame(() => input?.focus());
    };

    const closeModal = () => {
      backdrop.classList.remove('is-open');
      backdrop.setAttribute('aria-hidden', 'true');
      document.documentElement.classList.remove('hnt-modal-is-open');
      previousFocus?.focus?.();
    };

    action.addEventListener('click', (event) => {
      event.preventDefault();
      openModal();
    });

    closeButtons.forEach((button) => button.addEventListener('click', closeModal));
    backdrop.addEventListener('click', (event) => {
      if (event.target === backdrop) closeModal();
    });

    input?.addEventListener('input', () => {
      if (count) count.textContent = `${input.value.length} / 100`;
    });

    form?.addEventListener('submit', (event) => {
      if (!form.checkValidity()) {
        event.preventDefault();
        input?.reportValidity();
        return;
      }

      if (submit) {
        submit.disabled = true;
        submit.textContent = labels.submitting;
      }
    });

    backdrop.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        event.preventDefault();
        closeModal();
        return;
      }

      if (event.key !== 'Tab' || !modal) return;
      const focusable = Array.from(modal.querySelectorAll(focusableSelector)).filter((element) => !element.hasAttribute('hidden'));
      if (focusable.length === 0) return;
      const first = focusable[0];
      const last = focusable[focusable.length - 1];

      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    });
  };

  setupTeamCreateModal();

  const setupReviewControls = async () => {
    let capabilities;

    try {
      const url = new URL(window.location.href);
      url.searchParams.set('review_capabilities', '1');
      const response = await fetch(url, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
      });
      if (!response.ok) return;
      capabilities = await response.json();
    } catch (_error) {
      return;
    }

    if (!capabilities?.can_manage) return;
    const labels = capabilities.labels || {};

    shell.querySelectorAll('.cup-submission-actions').forEach((actions) => {
      const approveForm = Array.from(actions.querySelectorAll('form')).find((form) => /\/approve\/?$/.test(form.action));
      if (!approveForm) return;

      const csrf = approveForm.querySelector('input[name="_token"]')?.value || document.querySelector('meta[name="csrf-token"]')?.content || '';

      if (capabilities.verification_mode === 'manual') {
        const manualAction = approveForm.action.replace(/\/approve\/?$/, '/manual-score');
        const manualForm = document.createElement('form');
        manualForm.method = 'post';
        manualForm.action = manualAction;
        manualForm.className = 'cup-manual-score-form';
        manualForm.innerHTML = `
          <input type="hidden" name="_token" value="${csrf}">
          <label>${labels.kills || 'Kills'}<input name="kills" type="number" min="0" max="99" value="0" required></label>
          <label>${labels.bounty || 'Bounty'}<input name="bounty_tokens" type="number" min="0" max="4" value="0" required></label>
          <label>${labels.points || 'Punkte optional'}<input name="points" type="number" min="0" max="999" placeholder="Auto"></label>
          <label>${labels.note || 'Prüfnotiz'}<input name="review_note" maxlength="1200"></label>
          <button type="submit">${labels.manual_score || 'Manuell werten'}</button>
        `;
        approveForm.replaceWith(manualForm);

        const rejectButton = actions.querySelector('form[action$="/reject"] button');
        if (rejectButton) rejectButton.textContent = labels.reject || 'Ablehnen';
        return;
      }

      if (capabilities.verification_mode === 'ai' && capabilities.can_ai_review) {
        const rescoreAction = approveForm.action.replace(/\/approve\/?$/, '/rescore');
        const rescoreForm = document.createElement('form');
        rescoreForm.method = 'post';
        rescoreForm.action = rescoreAction;
        rescoreForm.className = 'cup-ai-rescore-form';
        rescoreForm.innerHTML = `<input type="hidden" name="_token" value="${csrf}"><button type="submit">${labels.ai_rescore || 'KI erneut prüfen'}</button>`;
        actions.appendChild(rescoreForm);
      }
    });
  };

  setupReviewControls();

  pageScroll?.addEventListener('scroll', () => {
    shell.classList.toggle('cup-detail-is-scrolled', pageScroll.scrollTop > 10);
  }, { passive: true });
})();
