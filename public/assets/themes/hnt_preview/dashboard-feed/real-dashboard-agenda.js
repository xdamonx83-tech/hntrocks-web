/* Real personal agenda using the original HNT demo structure and dimensions. */
(() => {
  const panel = document.querySelector('.hnt-agenda-panel');
  const tabs = [...document.querySelectorAll('.hnt-agenda-row > button')];
  const itemsHost = document.querySelector('.hnt-agenda-items');
  const axisHost = document.querySelector('.hnt-agenda-axis');

  if (!panel || tabs.length < 4 || !itemsHost || !axisHost || !window.fetch) return;

  const filters = ['now', 'today', 'tomorrow', 'week'];
  const desktopRows = {
    message: 54,
    lfg: 79,
    cup: 69,
    contract: 71,
    challenge: 73,
    moment: 70,
    default: 69,
  };
  const mobileRows = {
    message: 76,
    lfg: 104,
    cup: 90,
    contract: 92,
    challenge: 96,
    moment: 92,
    default: 90,
  };

  let agenda = { items: [], counts: {} };
  let activeFilter = 'now';
  let renderedItems = [];

  const escapeHtml = (value = '') => String(value).replace(/[&<>"']/g, (character) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  })[character]);

  const visibleItems = () => {
    const items = Array.isArray(agenda.items) ? agenda.items : [];

    return items.filter((item) => {
      if (activeFilter === 'week') return true;
      if (activeFilter === 'today') return item.timeframe === 'now' || item.timeframe === 'today';
      return item.timeframe === activeFilter;
    }).slice(0, 6);
  };

  const rowHeightFor = (item) => {
    const rows = window.matchMedia('(max-width: 899px)').matches ? mobileRows : desktopRows;
    return rows[item?.type] || rows.default;
  };

  const syncRows = () => {
    if (!renderedItems.length) {
      itemsHost.style.gridTemplateRows = '150px';
      axisHost.style.gridTemplateRows = '150px';
      return;
    }

    const template = renderedItems.map((item) => `${rowHeightFor(item)}px`).join(' ');
    itemsHost.style.gridTemplateRows = template;
    axisHost.style.gridTemplateRows = template;
  };

  const renderAvatars = (avatars = []) => {
    if (!Array.isArray(avatars) || avatars.length === 0) return '';

    return `
      <div class="hnt-mini-avatars">
        ${avatars.slice(0, 3).map((avatar) => `<img alt="" src="${escapeHtml(avatar)}">`).join('')}
      </div>
    `;
  };

  const renderAction = (item) => {
    if (!item?.url || !item?.button || item.type === 'contract') return '';

    return `<button type="button" data-agenda-url="${escapeHtml(item.url)}">${escapeHtml(item.button)}</button>`;
  };

  const renderCard = (item, index) => {
    const styleClass = item.style ? ` ${escapeHtml(item.style)}` : '';
    const hasProgress = item.progress !== null
      && item.progress !== undefined
      && item.progress !== ''
      && Number.isFinite(Number(item.progress));
    const progress = hasProgress
      ? `<div class="hnt-agenda-progress"><i style="width:${Math.max(0, Math.min(100, Number(item.progress)))}%"></i></div>`
      : '';
    const action = renderAction(item);
    const avatars = renderAvatars(item.avatars);
    const footer = avatars
      ? `<div class="hnt-agenda-card-footer">${avatars}${action}</div>`
      : action;

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

  const renderEmpty = () => {
    renderedItems = [];
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
    syncRows();
  };

  const render = () => {
    const items = visibleItems();
    renderedItems = items;

    if (!items.length) {
      renderEmpty();
      return;
    }

    itemsHost.innerHTML = items.map(renderCard).join('');
    renderAxis(items);
    syncRows();
  };

  const updateTabs = () => {
    const counts = agenda.counts || {};

    tabs.forEach((tab, index) => {
      const filter = filters[index];
      const small = tab.querySelector('small');
      const value = Number(counts[filter]) || 0;

      tab.dataset.agendaFilter = filter;
      tab.removeAttribute('data-toast');
      tab.classList.toggle('active', activeFilter === filter);
      tab.setAttribute('aria-pressed', String(activeFilter === filter));

      if (small) small.textContent = filter === 'now' && value > 0 ? 'Live' : String(value);
    });
  };

  const chooseInitialFilter = () => {
    const counts = agenda.counts || {};
    if ((Number(counts.now) || 0) > 0) return 'now';
    if ((Number(counts.today) || 0) > 0) return 'today';
    if ((Number(counts.tomorrow) || 0) > 0) return 'tomorrow';
    return 'week';
  };

  panel.dataset.realAgenda = '1';

  /* Remove the static demo immediately so it never flashes as real content. */
  itemsHost.innerHTML = `
    <article class="hnt-agenda-card">
      <div class="hnt-agenda-copy">
        <span>HNT.ROCKS</span>
        <h3>Agenda wird geladen</h3>
        <p>Echte Inhalte werden vorbereitet.</p>
      </div>
    </article>
  `;
  axisHost.innerHTML = '<span class="dark">JETZT</span>';
  syncRows();

  tabs.forEach((tab, index) => {
    tab.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopImmediatePropagation();
      activeFilter = filters[index];
      updateTabs();
      render();
    }, true);
  });

  itemsHost.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-agenda-url]');
    if (!button) return;

    const url = button.dataset.agendaUrl;
    if (url) window.location.assign(url);
  });

  window.addEventListener('resize', syncRows, { passive: true });

  const load = async () => {
    try {
      const url = new URL(window.location.href);
      url.search = '';
      url.hash = '';
      url.searchParams.set('dashboard_agenda', '1');

      const response = await fetch(url.toString(), {
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      if (!response.ok) throw new Error(`Dashboard agenda request failed with ${response.status}`);

      const payload = await response.json();
      agenda = payload.agenda || { items: [], counts: {} };
      activeFilter = chooseInitialFilter();
      updateTabs();
      render();
    } catch (error) {
      console.error('HNT dashboard agenda failed', error);
      renderedItems = [];
      itemsHost.innerHTML = `
        <article class="hnt-agenda-card">
          <div class="hnt-agenda-copy">
            <span>HNT.ROCKS</span>
            <h3>Agenda nicht verfügbar</h3>
            <p>Die Daten konnten gerade nicht geladen werden.</p>
          </div>
        </article>
      `;
      axisHost.innerHTML = '<span class="dark">JETZT</span>';
      syncRows();
    }
  };

  load();
})();