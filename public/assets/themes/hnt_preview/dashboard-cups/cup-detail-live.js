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
