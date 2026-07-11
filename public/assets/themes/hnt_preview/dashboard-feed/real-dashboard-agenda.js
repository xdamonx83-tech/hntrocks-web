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
      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card > a,
      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card-footer > a {
        display:inline-flex;
        align-items:center;
        justify-content:center;
        min-height:48px;
        padding:0 24px;
        border:0;
        border-radius:999px;
        background:#2f2f2c;
        color:#fff;
        font:inherit;
        font-weight:600;
        text-decoration:none;
        white-space:nowrap;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card.dark > a {
        background:#ffd04f;
        color:#2f2f2c;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card.highlight > a {
        min-width:56px;
        padding:0 18px;
        background:#ffd04f;
        color:#2f2f2c;
        font-size:18px;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-empty {
        min-height:180px;
        display:grid;
        place-items:center;
        padding:30px;
        border-radius:28px;
        background:rgba(255,255,255,.7);
        text-align:center;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-empty strong {
        display:block;
        color:#2f2f2c;
        font-size:18px;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-empty span {
        display:block;
        margin-top:5px;
        color:#9a968d;
        font-size:13px;
      }

      .hnt-agenda-panel[data-real-agenda="1"] .hnt-agenda-card.is-entering {
        animation:realAgendaEnter .32s ease both;
      }

      @keyframes realAgendaEnter {
        from { opacity:0; transform:translateY(8px); }
        to { opacity:1; transform:none; }
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
    const progress = Number.isFinite(Number(item.progress))
      ? `<div class="hnt-agenda-progress"><i style="width:${Math.max(0, Math.min(100, Number(item.progress)))}%"></i></div>`
      : '';
    const action = item.url && item.button
      ? `<a href="${escapeHtml(item.url)}">${escapeHtml(item.button)}</a>`
      : '';
    const avatars = renderAvatars(item.avatars);
    const footer = avatars
      ? `<div class="hnt-agenda-card-footer">${avatars}${action}</div>`
      : action;

    return `
      <article class="hnt-agenda-card${styleClass} is-entering" data-real-agenda-card data-agenda-type="${escapeHtml(item.type || '')}" style="animation-delay:${index * 35}ms">
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
