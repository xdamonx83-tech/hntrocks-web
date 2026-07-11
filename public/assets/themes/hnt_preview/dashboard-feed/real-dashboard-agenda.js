/* Real personal agenda for the isolated HNT dashboard preview. */
(() => {
  const panel = document.querySelector('.hnt-agenda-panel');
  const tabs = [...document.querySelectorAll('.hnt-agenda-row > button')];
  const itemsHost = document.querySelector('.hnt-agenda-items');
  const axisHost = document.querySelector('.hnt-agenda-axis');

  if (!panel || tabs.length < 4 || !itemsHost || !axisHost || !window.fetch) return;

  let agenda = { items: [], counts: {} };
  let activeFilter = 'now';

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
        align-content: start;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-items {
        align-content: start !important;
        justify-content: stretch !important;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-axis {
        align-self: start;
        height: var(--real-agenda-axis-height, auto) !important;
        min-height: var(--real-agenda-axis-height, 180px) !important;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card[data-real-agenda-card] {
        min-height: 108px;
        height: auto;
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        grid-template-areas:
          "copy action"
          "progress progress";
        align-items: center;
        column-gap: 18px;
        row-gap: 10px;
        padding: 20px 18px;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card[data-real-agenda-card]:not(.has-progress) {
        grid-template-areas: "copy action";
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card[data-real-agenda-card] .hnt-agenda-copy {
        grid-area: copy;
        min-width: 0;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card[data-real-agenda-card] .hnt-agenda-copy h3,
      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card[data-real-agenda-card] .hnt-agenda-copy p {
        overflow: hidden;
        text-overflow: ellipsis;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card > .hnt-agenda-action,
      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card-footer > .hnt-agenda-action {
        width: auto !important;
        min-width: 96px;
        max-width: 180px;
        height: 48px !important;
        min-height: 48px !important;
        max-height: 48px !important;
        flex: 0 0 auto;
        align-self: center;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 24px;
        margin: 0;
        border: 0;
        border-radius: 999px;
        background: #2f2f2c;
        color: #fff;
        font: inherit;
        font-size: 13px;
        font-weight: 600;
        line-height: 1;
        text-decoration: none;
        white-space: nowrap;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card > .hnt-agenda-action {
        grid-area: action;
        justify-self: end;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card-footer {
        grid-area: action;
        min-width: 0;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 12px;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card.dark > .hnt-agenda-action,
      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card.dark .hnt-agenda-card-footer > .hnt-agenda-action {
        background: #ffd04f;
        color: #2f2f2c;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card.highlight > .hnt-agenda-action {
        min-width: 56px;
        width: 56px !important;
        padding: 0;
        background: #ffd04f;
        color: #2f2f2c;
        font-size: 18px;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-progress {
        position: static !important;
        grid-area: progress;
        width: 100%;
        margin: 0;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-empty {
        min-height: 180px;
        display: grid;
        place-items: center;
        padding: 30px;
        border-radius: 28px;
        background: rgba(255,255,255,.7);
        text-align: center;
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
        .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card[data-real-agenda-card] {
          min-height: 100px;
          column-gap: 12px;
          padding: 17px 15px;
        }

        .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card > .hnt-agenda-action,
        .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card-footer > .hnt-agenda-action {
          min-width: 88px;
          height: 44px !important;
          min-height: 44px !important;
          max-height: 44px !important;
          padding-inline: 18px;
          font-size: 12px;
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
    const action = item.url && item.button
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

  const renderAxis = (items) => {
    const axisHeight = Math.max(180, (items.length * 122) + 18);
    axisHost.style.setProperty('--real-agenda-axis-height', `${axisHeight}px`);

    if (!items.length) {
      axisHost.innerHTML = '<span class="dark">JETZT</span><i class="time-dot dark bottom">⌄</i>';
      return;
    }

    axisHost.innerHTML = items.map((item, index) => {
      const axisClass = item.style === 'dark'
        ? 'dark'
        : (item.style === 'cup' || item.style === 'highlight' ? 'yellow' : '');
      return `<span class="${axisClass}">${escapeHtml(item.axis || (index === 0 ? 'JETZT' : 'WOCHE'))}</span>`;
    }).join('') + '<i class="time-dot dark bottom">⌄</i>';
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
    }
  };

  window.setTimeout(load, 180);
})();