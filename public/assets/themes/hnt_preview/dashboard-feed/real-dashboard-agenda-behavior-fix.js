/* Correct agenda overview filtering and keep the expanded card at a stable size. */
(() => {
  const panel = document.querySelector('.hnt-agenda-panel');
  const tabsHost = panel?.querySelector('.hnt-agenda-row');
  const tabs = tabsHost ? [...tabsHost.querySelectorAll(':scope > button')] : [];
  const itemsHost = panel?.querySelector('.hnt-agenda-items');
  const axisHost = panel?.querySelector('.hnt-agenda-axis');

  if (!panel || !tabsHost || tabs.length < 4 || !itemsHost || !axisHost || !window.fetch) return;
  if (panel.dataset.agendaBehaviorFix === '1') return;
  panel.dataset.agendaBehaviorFix = '1';

  const filters = ['now', 'today', 'tomorrow', 'week'];
  const compactDesktopRows = { message: 54, messages: 54, lfg: 79, cup: 69, contract: 71, challenge: 73, moment: 70, default: 69 };
  const compactMobileRows = { message: 76, messages: 76, lfg: 104, cup: 90, contract: 92, challenge: 96, moment: 92, default: 90 };
  const expandedDesktopRows = { message: 64, messages: 64, lfg: 92, cup: 82, contract: 84, challenge: 86, moment: 84, default: 82 };
  const expandedMobileRows = { message: 80, messages: 80, lfg: 108, cup: 96, contract: 98, challenge: 102, moment: 98, default: 96 };

  let agenda = { items: [], counts: {} };
  let activeFilter = 'now';
  let renderedItems = [];
  let ignoreMutations = false;
  let agendaReady = false;
  let rerenderFrame = 0;

  const escapeHtml = (value = '') => String(value).replace(/[&<>"']/g, (character) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
  })[character]);

  const visibleItems = () => {
    const items = Array.isArray(agenda.items) ? agenda.items : [];

    if (activeFilter === 'now' || activeFilter === 'week') return items.slice(0, 7);
    if (activeFilter === 'today') {
      return items.filter((item) => item.timeframe === 'now' || item.timeframe === 'today').slice(0, 7);
    }

    return items.filter((item) => item.timeframe === 'tomorrow').slice(0, 7);
  };

  const rowsForCurrentState = () => {
    if (panel.classList.contains('is-expanded')) {
      return window.matchMedia('(max-width: 720px)').matches ? expandedMobileRows : expandedDesktopRows;
    }

    return window.matchMedia('(max-width: 899px)').matches ? compactMobileRows : compactDesktopRows;
  };

  const syncRows = () => {
    if (!renderedItems.length) {
      itemsHost.style.gridTemplateRows = '150px';
      axisHost.style.gridTemplateRows = '150px';
      return;
    }

    const rows = rowsForCurrentState();
    const template = renderedItems
      .map((item) => `${rows[item?.type] || rows.default}px`)
      .join(' ');

    itemsHost.style.gridTemplateRows = template;
    axisHost.style.gridTemplateRows = template;
  };

  const renderAvatars = (avatars = []) => {
    if (!Array.isArray(avatars) || avatars.length === 0) return '';
    return `<div class="hnt-mini-avatars">${avatars.slice(0, 3).map((avatar) => `<img alt="" src="${escapeHtml(avatar)}">`).join('')}</div>`;
  };

  const renderAction = (item) => {
    if (!item?.url || !item?.button || item.type === 'contract') return '';
    return `<button type="button" data-agenda-url="${escapeHtml(item.url)}">${escapeHtml(item.button)}</button>`;
  };

  const renderCard = (item, index) => {
    const styleClass = item.style ? ` ${escapeHtml(item.style)}` : '';
    const numericProgress = Number(item.progress);
    const hasProgress = item.progress !== null && item.progress !== undefined && item.progress !== '' && Number.isFinite(numericProgress);
    const progress = hasProgress
      ? `<div class="hnt-agenda-progress"><i style="width:${Math.max(0, Math.min(100, numericProgress))}%"></i></div>`
      : '';
    const action = renderAction(item);
    const avatars = renderAvatars(item.avatars);
    const footer = avatars ? `<div class="hnt-agenda-card-footer">${avatars}${action}</div>` : action;

    return `
      <article class="hnt-agenda-card${styleClass}" data-agenda-type="${escapeHtml(item.type || '')}" style="animation-delay:${index * 30}ms">
        <div class="hnt-agenda-copy">
          <span>${escapeHtml(item.eyebrow || 'HNT.ROCKS')}</span>
          <h3>${escapeHtml(item.title || 'Aktivität')}</h3>
          <p>${escapeHtml(item.meta || '')}</p>
        </div>
        ${footer}
        ${progress}
      </article>
    `;
  };

  const axisClassFor = (item) => {
    if (item?.style === 'dark') return 'dark';
    if (item?.style === 'cup' || item?.style === 'highlight') return 'yellow';
    return '';
  };

  const renderAxis = (items) => {
    if (!items.length) {
      axisHost.innerHTML = '<span class="dark">JETZT</span>';
      return;
    }

    if (items.length === 1) {
      const item = items[0];
      axisHost.innerHTML = `<span class="${axisClassFor(item)}">${escapeHtml(item.axis || 'JETZT')}</span>`;
      return;
    }

    let previousLabel = null;
    const labels = items.slice(0, -1).map((item, index) => {
      const label = String(item.axis || (index === 0 ? 'JETZT' : 'WOCHE'));
      const duplicate = previousLabel === label;
      previousLabel = label;
      return `<span class="${axisClassFor(item)}"${duplicate ? ' style="visibility:hidden"' : ''}>${escapeHtml(label)}</span>`;
    }).join('');

    axisHost.innerHTML = `${labels}<i class="time-dot dark bottom">⌄</i>`;
  };

  const updateTabs = () => {
    const counts = agenda.counts || {};

    tabs.forEach((tab, index) => {
      const filter = filters[index];
      const small = tab.querySelector('small');
      const value = Number(counts[filter]) || 0;

      tab.dataset.agendaFilter = filter;
      tab.classList.toggle('active', activeFilter === filter);
      tab.setAttribute('aria-pressed', String(activeFilter === filter));
      if (small) small.textContent = filter === 'now' && value > 0 ? 'Live' : String(value);
    });
  };

  const restoreExpandedHeight = (height) => {
    if (!height) return;
    window.requestAnimationFrame(() => {
      window.requestAnimationFrame(() => {
        if (panel.classList.contains('is-expanded')) {
          panel.style.height = `${Math.round(height)}px`;
        }
      });
    });
  };

  const render = () => {
    const lockedHeight = panel.classList.contains('is-expanded')
      ? panel.getBoundingClientRect().height
      : 0;
    const items = visibleItems();
    renderedItems = items;
    ignoreMutations = true;

    if (!items.length) {
      itemsHost.innerHTML = `
        <article class="hnt-agenda-card">
          <div class="hnt-agenda-copy">
            <span>HNT.ROCKS</span>
            <h3>Nichts Dringendes</h3>
            <p>Für diesen Zeitraum gibt es aktuell keine Einträge.</p>
          </div>
        </article>
      `;
      axisHost.innerHTML = '<span class="dark">JETZT</span>';
    } else {
      itemsHost.innerHTML = items.map(renderCard).join('');
      renderAxis(items);
    }

    syncRows();
    restoreExpandedHeight(lockedHeight);
    queueMicrotask(() => { ignoreMutations = false; });
  };

  tabsHost.addEventListener('click', (event) => {
    const button = event.target instanceof Element ? event.target.closest('button') : null;
    if (!button || button.parentElement !== tabsHost) return;

    const index = tabs.indexOf(button);
    if (index < 0) return;

    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();
    activeFilter = filters[index];
    updateTabs();
    render();
  }, true);

  itemsHost.addEventListener('click', (event) => {
    const button = event.target instanceof Element ? event.target.closest('button[data-agenda-url]') : null;
    if (!button) return;
    const url = button.dataset.agendaUrl;
    if (url) window.location.assign(url);
  });

  const externalRenderObserver = new MutationObserver(() => {
    if (!agendaReady || ignoreMutations || rerenderFrame) return;
    rerenderFrame = window.requestAnimationFrame(() => {
      rerenderFrame = 0;
      render();
    });
  });
  externalRenderObserver.observe(itemsHost, { childList: true });

  window.addEventListener('resize', () => {
    syncRows();
  }, { passive: true });

  const load = async () => {
    try {
      const url = new URL(window.location.href);
      url.search = '';
      url.hash = '';
      url.searchParams.set('dashboard_agenda', '1');

      const response = await fetch(url.toString(), {
        credentials: 'same-origin',
        cache: 'no-store',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      if (!response.ok) throw new Error(`Dashboard agenda request failed with ${response.status}`);
      const payload = await response.json();
      agenda = payload.agenda || { items: [], counts: {} };
      agendaReady = true;
      activeFilter = 'now';
      updateTabs();
      render();
    } catch (error) {
      console.error('HNT dashboard agenda behavior fix failed', error);
    }
  };

  void load();
})();
