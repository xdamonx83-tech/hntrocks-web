/* Real progress data for the isolated HNT dashboard preview.
   Uses the existing preview feed payload and the existing authenticated contracts page.
   No duplicate quest system and no invented activity values. */
(() => {
  const root = document.querySelector('.feed-shell');
  if (!root || !window.fetch || !window.DOMParser) return;

  const number = (value) => new Intl.NumberFormat(document.documentElement.lang || 'de')
    .format(Number.parseInt(value, 10) || 0);

  const escapeHtml = (value = '') => String(value).replace(/[&<>"']/g, (character) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  })[character]);

  const text = (node, fallback = '') => node?.textContent?.trim() || fallback;
  const percentFromStyle = (node) => {
    const match = String(node?.getAttribute('style') || '').match(/width\s*:\s*(\d+(?:\.\d+)?)%/i);
    return Math.max(0, Math.min(100, Math.round(Number(match?.[1] || 0))));
  };

  const fetchJson = async (url) => {
    const response = await fetch(url, {
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    if (!response.ok) throw new Error(`Dashboard request failed with ${response.status}`);
    return response.json();
  };

  const fetchHtml = async (url) => {
    const response = await fetch(url, {
      credentials: 'same-origin',
      headers: {
        Accept: 'text/html',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    if (!response.ok) throw new Error(`Contracts request failed with ${response.status}`);
    return response.text();
  };

  const parseRatio = (value) => {
    const match = String(value || '').match(/(\d+)\s*\/\s*(\d+)/);
    return {
      current: Number(match?.[1] || 0),
      total: Number(match?.[2] || 0),
    };
  };

  const parseContracts = (html) => {
    const parsed = new DOMParser().parseFromString(html, 'text/html');
    const cards = [...parsed.querySelectorAll('.contract-card')].map((card) => {
      const progressMeta = card.querySelectorAll('.contract-progress-meta span');
      const ratio = parseRatio(text(progressMeta[0], '0 / 0'));
      const percentText = Number.parseInt(text(progressMeta[1], '0').replace(/\D/g, ''), 10) || 0;

      return {
        name: text(card.querySelector('.contract-card-top h2'), 'Wochenauftrag'),
        action: text(card.querySelector('.contract-card-top small'), 'Wochenauftrag'),
        reward: text(card.querySelector('.contract-card-top > strong'), '+0 XP'),
        status: text(card.querySelector('b'), card.classList.contains('complete') ? 'Erledigt' : 'Offen'),
        completed: card.classList.contains('complete'),
        current: ratio.current,
        target: ratio.total,
        percent: Math.max(0, Math.min(100, percentText)),
      };
    });

    const summaryRatio = parseRatio(text(parsed.querySelector('.contracts-hero-stats > div:first-child strong'), ''));
    const completionPercent = Number.parseInt(
      text(parsed.querySelector('.contracts-week-panel > strong'), '0').replace(/\D/g, ''),
      10,
    ) || 0;
    const xpRatio = parseRatio(text(parsed.querySelector('.contracts-hero-stats > div:nth-child(2) strong'), '0 / 0'));
    const levelProgress = percentFromStyle(parsed.querySelector('.game-progress-track > span'));

    const completed = summaryRatio.total > 0
      ? summaryRatio.current
      : cards.filter((card) => card.completed).length;
    const total = summaryRatio.total > 0 ? summaryRatio.total : cards.length;

    return {
      cards,
      completed,
      total,
      open: Math.max(0, total - completed),
      completionPercent: total > 0
        ? Math.max(0, Math.min(100, completionPercent || Math.round((completed / total) * 100)))
        : 0,
      claimedXp: xpRatio.current,
      availableXp: xpRatio.total,
      levelProgress,
      nextOpen: cards.find((card) => !card.completed) || null,
    };
  };

  const setMetric = (metric, label, value, percent = null) => {
    if (!metric) return;
    const labelNode = metric.querySelector(':scope > span');
    const bar = metric.querySelector('.bar');
    if (labelNode) labelNode.textContent = label;
    if (bar) {
      bar.textContent = value;
      if (percent !== null) {
        bar.style.setProperty('--real-progress', `${Math.max(0, Math.min(100, percent))}%`);
        bar.dataset.realProgress = '1';
      }
    }
  };

  const updateOverview = (profile, badges, contracts) => {
    const metrics = document.querySelectorAll('.overview-progress .overview-metric');
    setMetric(metrics[0], 'Wochenaufträge', `${contracts.completed} / ${contracts.total}`, contracts.completionPercent);
    setMetric(metrics[1], 'Offene Aufträge', `${contracts.open} offen`, contracts.total > 0 ? (contracts.open / contracts.total) * 100 : 0);
    setMetric(metrics[2], 'Level-Fortschritt', `${contracts.levelProgress}%`, contracts.levelProgress);
    setMetric(metrics[3], 'Bounty Marks', number(profile.rocks));

    const counters = document.querySelectorAll('.overview-counts article');
    const values = [
      [badges.messages || 0, 'Nachrichten'],
      [badges.notifications || 0, 'Hinweise'],
      [badges.friends || 0, 'Anfragen'],
    ];

    counters.forEach((counter, index) => {
      const strong = counter.querySelector('strong');
      const span = counter.querySelector('span');
      if (strong) strong.textContent = number(values[index]?.[0] || 0);
      if (span) span.textContent = values[index]?.[1] || '';
    });
  };

  const progressRow = ({ icon, iconClass, title, subtitle, value, percent, reward, status, statusClass }) => `
    <article class="personal-progress-row" data-real-dashboard-row>
      <span class="personal-progress-icon ${escapeHtml(iconClass)}">${escapeHtml(icon)}</span>
      <div class="personal-progress-copy">
        <strong>${escapeHtml(title)}</strong>
        <small>${escapeHtml(subtitle)}</small>
      </div>
      <div class="personal-progress-value">
        <strong>${escapeHtml(value)}</strong>
        <div><i style="width:${Math.max(0, Math.min(100, Number(percent) || 0))}%"></i></div>
      </div>
      <span class="personal-reward">${escapeHtml(reward)}</span>
      <span class="status ${escapeHtml(statusClass)}"><i></i>${escapeHtml(status)}</span>
    </article>
  `;

  const updateProgressTable = (profile, contracts) => {
    const table = document.querySelector('.personal-progress-table');
    if (!table) return;

    const aggregateStatus = contracts.total === 0
      ? { label: 'Keine', css: 'review-status' }
      : contracts.open === 0
        ? { label: 'Erledigt', css: 'open-status' }
        : { label: 'Aktiv', css: 'active-status' };

    const next = contracts.nextOpen;
    const level = Math.max(1, Number(profile.level) || 1);
    const rows = [
      progressRow({
        icon: 'W',
        iconClass: 'contract',
        title: 'Wochenaufträge',
        subtitle: contracts.total > 0 ? `${contracts.open} noch offen` : 'Aktuell keine aktiven Aufträge',
        value: `${contracts.completed} / ${contracts.total}`,
        percent: contracts.completionPercent,
        reward: `${number(contracts.availableXp)} XP`,
        status: aggregateStatus.label,
        statusClass: aggregateStatus.css,
      }),
      next
        ? progressRow({
            icon: 'N',
            iconClass: 'challenge',
            title: next.name,
            subtitle: next.action,
            value: `${next.current} / ${next.target}`,
            percent: next.percent,
            reward: next.reward,
            status: next.status.split('·')[0].trim() || 'Offen',
            statusClass: 'open-status',
          })
        : progressRow({
            icon: '✓',
            iconClass: 'challenge',
            title: 'Alle Aufträge erledigt',
            subtitle: 'Starker Wochenfortschritt',
            value: '100%',
            percent: 100,
            reward: `${number(contracts.claimedXp)} XP`,
            status: 'Fertig',
            statusClass: 'open-status',
          }),
      progressRow({
        icon: 'L',
        iconClass: 'profile',
        title: `Level ${level}`,
        subtitle: `Fortschritt zu Level ${level + 1}`,
        value: `${contracts.levelProgress}%`,
        percent: contracts.levelProgress,
        reward: `Level ${level + 1}`,
        status: 'Läuft',
        statusClass: 'active-status',
      }),
    ].join('');

    table.innerHTML = `
      <div class="personal-progress-labels">
        <span>Aktivität</span><span>Fortschritt</span><span>Belohnung</span><span>Status</span>
      </div>
      ${rows}
    `;
  };

  const updateAttention = (badges) => {
    const host = document.querySelector('.personal-attention-strip > div');
    if (!host) return;

    host.innerHTML = `
      <button data-toast="Nachrichten geöffnet"><b>${number(badges.messages || 0)}</b> Nachrichten</button>
      <button data-toast="Benachrichtigungen geöffnet"><b>${number(badges.notifications || 0)}</b> Hinweise</button>
      <button data-toast="Freundschaftsanfragen geöffnet"><b>${number(badges.friends || 0)}</b> Anfragen</button>
    `;
  };

  const updateProfileLevel = (profile, contracts) => {
    const level = Math.max(1, Number(profile.level) || 1);
    const label = document.querySelector('.profile-level .level-row span');
    const value = document.querySelector('.profile-level .level-row strong');
    const bar = document.querySelector('.profile-level .level-bar i');
    const hint = document.querySelector('.profile-level small');

    if (label) label.textContent = `Level ${level}`;
    if (value) value.textContent = `${contracts.levelProgress}%`;
    if (bar) bar.style.width = `${contracts.levelProgress}%`;
    if (hint) hint.textContent = `Fortschritt zu Level ${level + 1}`;
  };

  const renameMarks = () => {
    const labels = [
      document.querySelector('.profile-stats article:first-child span'),
      document.querySelector('.header-profile-stats span:first-child small'),
    ];
    labels.forEach((label) => {
      if (label) label.textContent = 'Marks';
    });
  };

  const clearFakeActivity = (profile) => {
    document.querySelectorAll('.personal-activity-values strong').forEach((value) => {
      value.innerHTML = '—';
    });
    const activityLabels = document.querySelectorAll('.personal-activity-values span');
    if (activityLabels[0]) activityLabels[0].textContent = 'Aktive Tage werden ermittelt';
    if (activityLabels[1]) activityLabels[1].textContent = 'Aktionen werden ermittelt';

    document.querySelectorAll('.personal-heatmap .y').forEach((dot) => dot.classList.remove('y'));

    const summaries = document.querySelectorAll('.personal-activity-summary article');
    if (summaries[0]) {
      summaries[0].querySelector('strong').textContent = '—';
      summaries[0].querySelector('span').textContent = 'Login-Serie';
    }
    if (summaries[1]) {
      summaries[1].querySelector('strong').textContent = '—';
      summaries[1].querySelector('span').textContent = 'XP diese Woche';
    }
    if (summaries[2]) {
      summaries[2].querySelector('strong').textContent = number(profile.rocks);
      summaries[2].querySelector('span').textContent = 'Marks verfügbar';
    }
  };

  const addProgressCss = () => {
    if (document.getElementById('real-dashboard-progress-style')) return;
    const style = document.createElement('style');
    style.id = 'real-dashboard-progress-style';
    style.textContent = `
      .overview-progress .bar[data-real-progress="1"]{
        background-image:linear-gradient(90deg,rgba(255,205,76,.94) 0 var(--real-progress),rgba(47,47,44,.12) var(--real-progress) 100%);
        color:#2f2f2c;
      }
      .personal-progress-table[data-real-loading="1"]{opacity:.62;pointer-events:none}
    `;
    document.head.appendChild(style);
  };

  const boot = async () => {
    addProgressCss();
    const table = document.querySelector('.personal-progress-table');
    table?.setAttribute('data-real-loading', '1');

    try {
      const endpoint = `${window.location.pathname}?data=1&mode=for-you&page=1`;
      const [dashboardPayload, contractsHtml] = await Promise.all([
        fetchJson(endpoint),
        fetchHtml('/contracts'),
      ]);

      const profile = dashboardPayload.profile || {};
      const badges = dashboardPayload.badges || {};
      const contracts = parseContracts(contractsHtml);

      updateOverview(profile, badges, contracts);
      updateProgressTable(profile, contracts);
      updateAttention(badges);
      updateProfileLevel(profile, contracts);
      renameMarks();
      clearFakeActivity(profile);
    } catch (error) {
      console.error('HNT real dashboard progress failed', error);
      if (typeof showToast === 'function') showToast('Fortschrittsdaten konnten nicht geladen werden');
    } finally {
      table?.removeAttribute('data-real-loading');
    }
  };

  window.setTimeout(boot, 120);
})();
