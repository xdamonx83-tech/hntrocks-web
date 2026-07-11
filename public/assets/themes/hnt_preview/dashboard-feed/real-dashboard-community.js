/* Real community overview for the isolated HNT dashboard preview. */
(() => {
  const panel = document.getElementById('compositionPanel');
  const title = panel?.querySelector('.composition-top h2');
  const live = panel?.querySelector('.composition-live');
  const ring = panel?.querySelector('.composition-ring');
  const values = panel?.querySelector('.composition-values');
  const note = panel?.querySelector('.community-note');
  const statGrid = panel?.querySelector('.composition-stat-grid');
  const activityState = document.getElementById('activityState');
  const activityList = document.getElementById('compositionActivity');
  const trending = panel?.querySelector('.composition-trending');

  if (!panel || !title || !live || !ring || !values || !note || !statGrid || !activityList) return;
  if (panel.dataset.realCommunity === '1') return;
  panel.dataset.realCommunity = '1';

  const escapeHtml = (value = '') => String(value).replace(/[&<>"']/g, (character) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  })[character]);

  const formatNumber = (value) => new Intl.NumberFormat('de-DE').format(Math.max(0, Number(value) || 0));

  const addStyle = () => {
    if (document.getElementById('real-dashboard-community-style')) return;

    const style = document.createElement('style');
    style.id = 'real-dashboard-community-style';
    style.textContent = `
      .composition-panel[data-real-community="1"] .composition-top h2 {
        max-width: 150px;
        line-height: 1.05;
      }

      .composition-panel[data-real-community="1"] .composition-live {
        white-space: nowrap;
      }

      .composition-panel[data-real-community="1"] .composition-values {
        gap: 16px;
      }

      .composition-panel[data-real-community="1"] .composition-values > span {
        display: grid;
        grid-template-columns: auto auto;
        grid-template-rows: auto auto;
        align-items: center;
        column-gap: 6px;
      }

      .composition-panel[data-real-community="1"] .composition-values > span > i {
        grid-row: 1 / span 2;
      }

      .composition-panel[data-real-community="1"] .composition-values strong {
        line-height: 1;
      }

      .composition-panel[data-real-community="1"] .composition-values small {
        grid-column: 2;
        margin-top: 3px;
        color: #918e85;
        font-size: 7px;
        white-space: nowrap;
      }

      .composition-panel[data-real-community="1"] .community-note small {
        line-height: 1.35;
      }

      .composition-panel[data-real-community="1"] .activity-item {
        color: inherit;
        text-decoration: none;
      }

      .composition-panel[data-real-community="1"] .activity-item[data-community-url] {
        cursor: pointer;
      }

      .composition-panel[data-real-community="1"] .activity-item[data-community-url]:hover {
        background: rgba(255,255,255,.88);
      }

      .composition-panel[data-real-community="1"] .activity-item strong,
      .composition-panel[data-real-community="1"] .activity-item small {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      .composition-panel[data-real-community="1"] .trend-tags button {
        cursor: default;
      }

      .composition-panel[data-real-community="1"] .community-loading {
        opacity: .62;
      }

      @media (max-width: 899px) {
        .composition-panel[data-real-community="1"] .composition-top h2 {
          max-width: none;
        }

        .composition-panel[data-real-community="1"] .composition-values small {
          font-size: 10px;
        }
      }
    `;
    document.head.appendChild(style);
  };

  const setLoading = () => {
    title.textContent = 'Community im Überblick';
    live.innerHTML = '<i></i> Live-Daten';
    ring.classList.add('community-loading');
    ring.querySelector('strong').textContent = '—';
    ring.querySelector('span').textContent = 'Hunter';
    values.innerHTML = `
      <span><i class="yellow"></i><strong>—</strong><small>heute aktiv</small></span>
      <span><i class="dark"></i><strong>—</strong><small>gerade online</small></span>
    `;
    note.innerHTML = '<span>HNT.ROCKS</span><strong>Community wird geladen</strong><small>Echte Daten werden vorbereitet.</small>';
    statGrid.innerHTML = `
      <article><span>Heute aktiv</span><strong>—</strong><small>Hunter</small></article>
      <article><span>Offene LFGs</span><strong>—</strong><small>aktuell</small></article>
      <article><span>Moments</span><strong>—</strong><small>heute</small></article>
      <article><span>Cup-Teams</span><strong>—</strong><small>aktiv</small></article>
    `;
    activityList.innerHTML = `
      <article class="activity-item community-loading">
        <img src="/assets/vikinger/img/default-avatar.svg" alt="">
        <div><strong>Community-Aktivität wird geladen</strong><small>Echte Ereignisse werden vorbereitet.</small></div>
        <span>…</span>
      </article>
    `;
    if (activityState) activityState.textContent = 'Wird geladen';
  };

  const renderActivity = (items = []) => {
    if (!Array.isArray(items) || items.length === 0) {
      activityList.innerHTML = `
        <article class="activity-item">
          <img src="/assets/vikinger/img/default-avatar.svg" alt="">
          <div><strong>Noch keine neue Aktivität</strong><small>Hier erscheinen neue Community-Ereignisse.</small></div>
          <span>—</span>
        </article>
      `;
      if (activityState) activityState.textContent = '0 neu';
      return;
    }

    activityList.innerHTML = items.map((item) => `
      <article class="activity-item"${item.url ? ` data-community-url="${escapeHtml(item.url)}" tabindex="0" role="link"` : ''}>
        <img src="${escapeHtml(item.avatar)}" alt="">
        <div><strong>${escapeHtml(item.title)}</strong><small>${escapeHtml(item.meta)}</small></div>
        <span>${escapeHtml(item.time)}</span>
      </article>
    `).join('');

    if (activityState) activityState.textContent = `${items.length} neu`;
  };

  const render = (community) => {
    const total = Number(community.total_members) || 0;
    const activeToday = Number(community.active_today) || 0;
    const onlineNow = Number(community.online_now) || 0;
    const activeRate = Math.max(0, Math.min(100, Number(community.active_rate) || 0));
    const ringDegrees = Math.round((342 * activeRate) / 100);

    title.textContent = 'Community im Überblick';
    live.innerHTML = `<i></i> ${formatNumber(onlineNow)} online`;

    ring.classList.remove('community-loading');
    ring.style.background = `conic-gradient(var(--yellow) 0 ${ringDegrees}deg, var(--dark) ${ringDegrees}deg 342deg, transparent 342deg 360deg)`;
    ring.querySelector('strong').textContent = formatNumber(total);
    ring.querySelector('span').textContent = 'Hunter';

    values.innerHTML = `
      <span><i class="yellow"></i><strong>${formatNumber(activeToday)}</strong><small>heute aktiv</small></span>
      <span><i class="dark"></i><strong>${formatNumber(onlineNow)}</strong><small>gerade online</small></span>
    `;

    const newThisWeek = Number(community.new_this_week) || 0;
    note.innerHTML = `
      <span>HNT.ROCKS</span>
      <strong>${newThisWeek > 0 ? 'Community wächst' : 'Community ist aktiv'}</strong>
      <small>${newThisWeek > 0 ? `+${formatNumber(newThisWeek)} neue ${newThisWeek === 1 ? 'Hunter' : 'Hunter'} diese Woche` : 'Noch keine neuen Hunter seit Wochenbeginn'}</small>
    `;

    statGrid.innerHTML = `
      <article><span>Heute aktiv</span><strong>${formatNumber(activeToday)}</strong><small>${activeRate}% der Community</small></article>
      <article><span>Offene LFGs</span><strong>${formatNumber(community.open_lfgs)}</strong><small>spielbereit</small></article>
      <article><span>Moments</span><strong>${formatNumber(community.moments_today)}</strong><small>heute</small></article>
      <article><span>Cup-Teams</span><strong>${formatNumber(community.active_cup_teams)}</strong><small>aktiv</small></article>
    `;

    renderActivity(community.activity);

    if (trending) {
      trending.innerHTML = `
        <div class="activity-title"><h3>Schnellübersicht</h3><span>Echte Daten</span></div>
        <div class="trend-tags">
          <button type="button">${formatNumber(community.open_lfgs)} LFGs</button>
          <button type="button">${formatNumber(community.moments_today)} Moments heute</button>
          <button type="button">${formatNumber(community.active_cup_teams)} Cup-Teams</button>
          <button type="button">${formatNumber(total)} Hunter</button>
        </div>
      `;
    }
  };

  const showError = () => {
    ring.classList.remove('community-loading');
    live.innerHTML = '<i></i> Nicht verfügbar';
    note.innerHTML = '<span>HNT.ROCKS</span><strong>Community-Daten nicht verfügbar</strong><small>Die Werte konnten gerade nicht geladen werden.</small>';
    if (activityState) activityState.textContent = 'Fehler';
    activityList.innerHTML = `
      <article class="activity-item">
        <img src="/assets/vikinger/img/default-avatar.svg" alt="">
        <div><strong>Keine Live-Daten</strong><small>Bitte die Preview neu laden.</small></div>
        <span>—</span>
      </article>
    `;
  };

  activityList.addEventListener('click', (event) => {
    const item = event.target.closest('[data-community-url]');
    if (item?.dataset.communityUrl) window.location.assign(item.dataset.communityUrl);
  });

  activityList.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter' && event.key !== ' ') return;
    const item = event.target.closest('[data-community-url]');
    if (!item?.dataset.communityUrl) return;
    event.preventDefault();
    window.location.assign(item.dataset.communityUrl);
  });

  const load = async () => {
    addStyle();
    setLoading();

    try {
      const url = new URL(window.location.href);
      url.search = '';
      url.hash = '';
      url.searchParams.set('dashboard_community', '1');

      const response = await fetch(url.toString(), {
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      if (!response.ok) throw new Error(`Dashboard community request failed with ${response.status}`);

      const payload = await response.json();
      render(payload.community || {});
    } catch (error) {
      console.error('HNT dashboard community failed', error);
      showError();
    }
  };

  load();
})();
