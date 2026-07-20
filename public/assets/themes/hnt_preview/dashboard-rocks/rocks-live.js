(() => {
  const root = document.querySelector('[data-rocks-dashboard]');
  if (!root) return;

  if (!document.querySelector('link[data-rocks-reference-fix]')) {
    const referenceStyle = document.createElement('link');
    referenceStyle.rel = 'stylesheet';
    referenceStyle.href = '/assets/themes/hnt_preview/dashboard-rocks/rocks-reference-fix.css?v=20260720-2';
    referenceStyle.dataset.rocksReferenceFix = '1';
    document.head.appendChild(referenceStyle);
  }

  if (!document.querySelector('link[data-rocks-scroll-behavior]')) {
    const scrollStyle = document.createElement('link');
    scrollStyle.rel = 'stylesheet';
    scrollStyle.href = '/assets/themes/hnt_preview/dashboard-rocks/rocks-scroll-behavior.css?v=20260720-1';
    scrollStyle.dataset.rocksScrollBehavior = '1';
    document.head.appendChild(scrollStyle);
  }

  if (!document.querySelector('link[data-rocks-header-dropdown-fix]')) {
    const dropdownStyle = document.createElement('link');
    dropdownStyle.rel = 'stylesheet';
    dropdownStyle.href = '/assets/themes/hnt_preview/dashboard-rocks/rocks-header-dropdown-fix.css?v=20260720-1';
    dropdownStyle.dataset.rocksHeaderDropdownFix = '1';
    document.head.appendChild(dropdownStyle);
  }

  const scroll = document.getElementById('rocksScroll');
  const stage = document.querySelector('.rocks-stage');
  const center = document.getElementById('rocksCenterFlow');
  const left = document.getElementById('rocksFixedLeft');
  const right = document.getElementById('rocksFixedRight');
  const stickyHead = document.querySelector('.rocks-center-head');
  const tabs = [...root.querySelectorAll('[data-rocks-tab]')];
  const panels = [...root.querySelectorAll('[data-rocks-panel]')];
  const title = document.getElementById('rocksPanelTitle');
  const toast = document.getElementById('toast');
  let frame = 0;
  let toastTimer = 0;

  function showToast(message) {
    if (!toast || !message) return;
    window.clearTimeout(toastTimer);
    toast.textContent = message;
    toast.classList.add('show');
    toastTimer = window.setTimeout(() => toast.classList.remove('show'), 2600);
  }

  function activateTab(name, updateUrl = true) {
    const valid = panels.some((panel) => panel.dataset.rocksPanel === name);
    const next = valid ? name : 'overview';

    tabs.forEach((tab) => {
      const active = tab.dataset.rocksTab === next;
      tab.classList.toggle('active', active);
      tab.setAttribute('aria-selected', String(active));
    });

    panels.forEach((panel) => {
      const active = panel.dataset.rocksPanel === next;
      panel.hidden = !active;
      panel.classList.toggle('active', active);
    });

    const activeTab = tabs.find((tab) => tab.dataset.rocksTab === next);
    if (title && activeTab) title.textContent = activeTab.dataset.title || activeTab.textContent.trim();

    if (updateUrl) {
      const url = new URL(window.location.href);
      if (next === 'overview') url.searchParams.delete('tab');
      else url.searchParams.set('tab', next);
      window.history.replaceState(null, '', url);
    }

    requestUpdate();
  }

  function updateFixedColumns() {
    frame = 0;
    if (!stage || !scroll || !center || !left || !right) return;

    if (window.matchMedia('(max-width: 1180px)').matches) {
      left.style.removeProperty('top');
      right.style.removeProperty('top');
      stickyHead?.classList.remove('is-stuck');
      scroll.classList.remove('rocks-content-docked');
      stage.classList.remove('rocks-columns-docked');
      return;
    }

    const styles = getComputedStyle(root);
    const gap = Number.parseFloat(styles.getPropertyValue('--rocks-sticky-gap')) || 12;
    const stageRect = stage.getBoundingClientRect();
    const centerRect = center.getBoundingClientRect();
    const naturalTop = centerRect.top - stageRect.top;
    const top = Math.max(gap, naturalTop);

    left.style.top = `${top}px`;
    right.style.top = `${top}px`;

    if (stickyHead) {
      const headRect = stickyHead.getBoundingClientRect();
      const scrollRect = scroll.getBoundingClientRect();
      const isStuck = scroll.scrollTop > 0 && headRect.top <= scrollRect.top + gap + 1;

      stickyHead.classList.toggle('is-stuck', isStuck);
      scroll.classList.toggle('rocks-content-docked', isStuck);
      stage.classList.toggle('rocks-columns-docked', isStuck);
    }
  }

  function requestUpdate() {
    if (frame) return;
    frame = window.requestAnimationFrame(updateFixedColumns);
  }

  tabs.forEach((tab) => tab.addEventListener('click', () => activateTab(tab.dataset.rocksTab)));
  root.querySelectorAll('[data-rocks-tab-shortcut]').forEach((button) => {
    button.addEventListener('click', () => {
      activateTab(button.dataset.rocksTabShortcut);
      scroll?.scrollTo({ top: Math.max(0, center?.offsetTop || 0), behavior: 'smooth' });
    });
  });

  root.querySelectorAll('[data-history-filter]').forEach((button) => {
    button.addEventListener('click', () => {
      const filter = button.dataset.historyFilter || 'all';
      root.querySelectorAll('[data-history-filter]').forEach((item) => item.classList.toggle('active', item === button));
      root.querySelectorAll('[data-history-type]').forEach((row) => {
        row.hidden = filter !== 'all' && row.dataset.historyType !== filter;
      });
    });
  });

  root.querySelectorAll('[data-rocks-toast]').forEach((button) => {
    button.addEventListener('click', () => showToast(button.dataset.rocksToast));
  });

  scroll?.addEventListener('scroll', requestUpdate, { passive: true });
  window.addEventListener('resize', requestUpdate);
  window.addEventListener('load', requestUpdate);

  activateTab(root.dataset.initialTab || 'overview', false);
  requestUpdate();
})();
