/* Real personal agenda for the isolated HNT dashboard preview. */
(() => {
  const panel = document.querySelector('.hnt-agenda-panel');
  const tabs = [...document.querySelectorAll('.hnt-agenda-row > button')];
  const itemsHost = document.querySelector('.hnt-agenda-items');
  const axisHost = document.querySelector('.hnt-agenda-axis');

  if (!panel || tabs.length < 4 || !itemsHost || !axisHost || !window.fetch) return;

  let agenda = { items: [], counts: {} };
  let activeFilter = 'now';
  let axisFrame = 0;

  const filters = ['now', 'today', 'tomorrow', 'week'];
  const escapeHtml = (value = '') => String(value).replace(/[&<>"']/g, (character) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  })[character]);

  const addStyle = () => {
    if (document.getElementById('real-dashboard-agenda-style')) return;

    const style = document.createElement('style');
    style.id = 'real-dashboard-agenda-style';
    style.textContent = `
      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-timeline {
        align-items: start !important;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-items {
        display: flex !important;
        flex-direction: column !important;
        align-content: initial !important;
        justify-content: flex-start !important;
        gap: 12px !important;
        min-height: 0 !important;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card[data-real-agenda-card] {
        box-sizing: border-box !important;
        width: 100% !important;
        height: auto !important;
        min-height: 0 !important;
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) auto !important;
        grid-template-areas: "copy action" !important;
        align-items: center !important;
        column-gap: 16px !important;
        row-gap: 0 !important;
        padding: 16px 18px !important;
        overflow: hidden !important;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card[data-agenda-type="message"] {
        min-height: 76px !important;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card[data-agenda-type="lfg"] {
        min-height: 90px !important;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card[data-agenda-type="cup"] {
        min-height: 78px !important;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card[data-agenda-type="contract"] {
        min-height: 82px !important;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card[data-agenda-type="challenge"] {
        min-height: 88px !important;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card[data-agenda-type="moment"] {
        min-height: 82px !important;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card.has-progress {
        grid-template-columns: minmax(0, 1fr) !important;
        grid-template-areas:
          "copy"
          "progress" !important;
        row-gap: 10px !important;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-copy {
        grid-area: copy !important;
        min-width: 0 !important;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-copy h3,
      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-copy p {
        max-width: 100% !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card > .hnt-agenda-action,
      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card-footer > .hnt-agenda-action {
        box-sizing: border-box !important;
        width: auto !important;
        min-width: 0 !important;
        max-width: none !important;
        height: 48px !important;
        min-height: 48px !important;
        max-height: 48px !important;
        flex: 0 0 auto !important;
        align-self: center !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0 20px !important;
        margin: 0 !important;
        border: 0 !important;
        border-radius: 999px !important;
        background: #2f2f2c !important;
        color: #fff !important;
        font-family: inherit !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        line-height: 1 !important;
        text-decoration: none !important;
        white-space: nowrap !important;
        transform: none !important;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card > .hnt-agenda-action {
        grid-area: action !important;
        justify-self: end !important;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card-footer {
        grid-area: action !important;
        min-width: 0 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: flex-end !important;
        gap: 10px !important;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card.dark > .hnt-agenda-action,
      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card.dark .hnt-agenda-card-footer > .hnt-agenda-action {
        background: #ffd04f !important;
        color: #2f2f2c !important;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card.highlight > .hnt-agenda-action {
        width: 52px !important;
        min-width: 52px !important;
        padding: 0 !important;
        background: #ffd04f !important;
        color: #2f2f2c !important;
        font-size: 17px !important;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-progress {
        position: static !important;
        grid-area: progress !important;
        width: 100% !important;
        margin: 0 !important;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-axis {
        position: relative !important;
        display: block !important;
        align-self: start !important;
        height: var(--real-agenda-axis-height, 180px) !important;
        min-height: 0 !important;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-axis::before {
        content: "" !important;
        position: absolute !important;
        left: 50% !important;
        top: 14px !important;
        bottom: 14px !important;
        width: 1px !important;
        background: #aaa79d !important;
        transform: translateX(-50%) !important;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-axis > span {
        position: absolute !important;
        left: 50% !important;
        margin: 0 !important;
        transform: translate(-50%, -50%) !important;
        z-index: 1 !important;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-axis > .time-dot {
        position: absolute !important;
        left: 50% !important;
        margin: 0 !important;
        transform: translate(-50%, -50%) !important;
        z-index: 1 !important;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-empty {
        min-height: 150px !important;
        display: grid !important;
        place-items: center !important;
        padding: 26px !important;
        border-radius: 28px !important;
        background: rgba(255,255,255,.7) !important;
        text-align: center !important;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-empty strong {
        display: block;
        color: #2f2f2c;
        font-size: 18px;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-empty span {
        display: block;
        margin-top: 5px;
        color: #9a968d;
        font-size: 13px;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card.is-entering {
        animation: realAgendaEnter .32s ease both;
      }

      @media (max-width: 560px) {
        .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-items {
          gap: 10px !important;
        }

        .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card[data-real-agenda-card] {
          column-gap: 10px !important;
          padding: 14px 14px !important;
        }

        .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card > .hnt-agenda-action,
        .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card-footer > .hnt-agenda-action {
          height: 42px !important;
          min-height: 42px !important;
          max-height: 42px !important;
          padding-inline: 16px !important;
          font-size: 12px !important;
        }
      }

      @keyframes realAgendaEnter {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: none; }
      }
    `;
    document.head.appendChild(style);
  };

  const visibleItems = () => {
    const items = Array.isArray(agenda.items) ? agenda.items : [];

    return items.filter((item) => {
      if (activeFilter === 'week') return true;
      if (activeFilter === 'today') return item.timeframe === 'now' || item.timeframe === 'today';
      return item.timeframe === activeFilter;
    });
  };

  const renderAvatars = (avatars = []) => {
    if (!Array.isArray(avatars) || avatars.length === 0) return '';

    return `
      <div class="hnt-mini-avatars">
        ${avatars.slice(0, 3).map((avatar) => `<img alt="" src="${escapeHtml(avatar)}">`).join('')}
      </div>
    `;
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
    const showAction = item.type !== 'contract' && item.url && item.button;
    const action = showAction
      ? `<a class="hnt-agenda-action" href="${escapeHtml(item.url)}">${escapeHtml(item.button)}</a>`
      : '';
    const avatars = renderAvatars(item.avatars);
    const footer = avatars
      ? `<div class="hnt-agenda-card-footer">${avatars}${action}</div>`
      : action;

    return `
      <article class="hnt-agenda-card${styleClass}${hasProgress ? ' has-progress' : ''} is-entering" data-real-agenda-card data-agenda-type="${escapeHtml(item.type || '')}" style="animation-delay:${index * 35}ms">
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

  const syncAxis = () => {
    window.cancelAnimationFrame(axisFrame);
    axisFrame = window.requestAnimationFrame(() => {
      const cards = [...itemsHost.querySelectorAll('[data-real-agenda-card]')];
      const labels = [...axisHost.querySelectorAll(':scope > span')];
      const height = Math.max(150, itemsHost.scrollHeight || 0);

      axisHost.style.setProperty('--real-agenda-axis-height', `${height}px`);

      labels.forEach((label, index) => {
        const card = cards[index];
        if (!card) return;
        label.style.top = `${Math.max(22, card.offsetTop + 22)}px`;
      });

      const dot = axisHost.querySelector(':scope > .time-dot');
      if (dot) dot.style.top = `${Math.max(34, height - 20)}px`;
    });
  };

  const renderAxis = (items) => {
    if (!items.length) {
      axisHost.innerHTML = '<span class="dark">JETZT</span><i class="time-dot dark bottom">⌄</i>';
      syncAxis();
      return;
    }

    axisHost.innerHTML = items.map((item, index) => {
      const axisClass = item.style === 'dark'
        ? 'dark'
        : (item.style === 'cup' || item.style === 'highlight' ? 'yellow' : '');
      return `<span class="${axisClass}">${escapeHtml(item.axis || (index === 0 ? 'JETZT' : 'WOCHE'))}</span>`;
    }).join('') + '<i class="time-dot dark bottom">⌄</i>';

    syncAxis();
  };

  const render = () => {
    const items = visibleItems().slice(0, 7);
    panel.dataset.agendaCount = String(items.length);

    if (!items.length) {
      itemsHost.innerHTML = `
        <div class="hnt-agenda-empty">
          <div><strong>Nichts Dringendes</strong><span>Für diesen Zeitraum gibt es aktuell keine Einträge.</span></div>
        </div>
      `;
    } else {
      itemsHost.innerHTML = items.map(renderCard).join('');
    }

    renderAxis(items);
    window.setTimeout(syncAxis, 80);
  };

  const updateTabs = () => {
    const counts = agenda.counts || {};

    tabs.forEach((tab, index) => {
      const filter = filters[index];
      const small = tab.querySelector('small');
      tab.dataset.agendaFilter = filter;
      tab.removeAttribute('data-toast');
      tab.classList.toggle('active', activeFilter === filter);
      tab.setAttribute('aria-pressed', String(activeFilter === filter));

      if (small) {
        const value = Number(counts[filter]) || 0;
        small.textContent = filter === 'now' && value > 0 ? 'Live' : String(value);
      }
    });
  };

  const chooseInitialFilter = () => {
    const counts = agenda.counts || {};
    if ((Number(counts.now) || 0) > 0) return 'now';
    if ((Number(counts.today) || 0) > 0) return 'today';
    if ((Number(counts.tomorrow) || 0) > 0) return 'tomorrow';
    return 'week';
  };

  tabs.forEach((tab, index) => {
    tab.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopImmediatePropagation();
      activeFilter = filters[index];
      updateTabs();
      render();
    }, true);
  });

  window.addEventListener('resize', syncAxis, { passive: true });

  if ('ResizeObserver' in window) {
    new ResizeObserver(syncAxis).observe(itemsHost);
  }

  const load = async () => {
    panel.dataset.realAgenda = '1';
    addStyle();

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
      itemsHost.innerHTML = `
        <div class="hnt-agenda-empty">
          <div><strong>Agenda nicht verfügbar</strong><span>Die Daten konnten gerade nicht geladen werden.</span></div>
        </div>
      `;
      syncAxis();
    }
  };

  window.setTimeout(load, 180);
})();