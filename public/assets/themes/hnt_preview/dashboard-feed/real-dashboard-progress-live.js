/* Real progress data for the public dashboard feed activation.
   Uses JSON endpoints only; no preview-theme HTML scraping. */
(() => {
  const root = document.querySelector('.feed-shell');
  if (!root || !window.fetch || window.HNT_DASHBOARD_FEED_LIVE !== true) return;

  const i18n = window.HNT_DASHBOARD_I18N || {};
  const t = (key, fallback = '', replacements = {}) => {
    let value = String(i18n[key] || fallback);
    Object.entries(replacements).forEach(([name, replacement]) => {
      value = value.replace(`:${name}`, String(replacement));
    });
    return value;
  };

  const formatNumber = (value) => new Intl.NumberFormat(document.documentElement.lang || 'de')
    .format(Number.parseInt(value, 10) || 0);

  const escapeHtml = (value = '') => String(value).replace(/[&<>"']/g, (character) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
  })[character]);

  const fetchJson = async (url) => {
    const response = await fetch(url, {
      credentials: 'same-origin',
      cache: 'no-store',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    if (!response.ok) throw new Error(`Dashboard request failed with ${response.status}`);
    return response.json();
  };

  const setMetric = (metric, label, value, percent = null) => {
    if (!metric) return;
    const labelNode = metric.querySelector(':scope > span');
    const bar = metric.querySelector('.bar');
    if (labelNode) labelNode.textContent = label;
    if (!bar) return;

    bar.textContent = value;
    if (percent !== null) {
      bar.style.setProperty('--real-progress', `${Math.max(0, Math.min(100, Number(percent) || 0))}%`);
      bar.dataset.realProgress = '1';
    }
  };

  const applyRocksLabels = () => {
    const metrics = document.querySelectorAll('.overview-progress .overview-metric');
    const rocksLabel = metrics[3]?.querySelector(':scope > span');
    if (rocksLabel) rocksLabel.textContent = 'Rocks';

    const profileLabel = document.querySelector('.profile-stats article:first-child span');
    const headerLabel = document.querySelector('.header-profile-stats span:first-child small');
    if (profileLabel) profileLabel.textContent = 'Rocks';
    if (headerLabel) headerLabel.textContent = 'Rocks';
  };

  const updateActivityRocks = (profile) => {
    const cards = [...document.querySelectorAll('.personal-activity-summary article')];
    const card = cards.find((candidate) => /rocks/i.test(candidate.querySelector('span')?.textContent || '')) || cards[2];
    if (!card) return;

    const value = card.querySelector('strong');
    const label = card.querySelector('span');
    if (value) value.textContent = formatNumber(profile.rocks || 0);
    if (label) label.textContent = t('rocks_available', 'Rocks verfügbar');
  };

  const updateOverview = (profile, badges, progress) => {
    const metrics = document.querySelectorAll('.overview-progress .overview-metric');
    setMetric(
      metrics[0],
      t('weekly_contracts', 'Weekly Contracts'),
      `${progress.completed || 0} / ${progress.total || 0}`,
      progress.completion_percent || 0,
    );
    setMetric(metrics[2], t('level_progress', 'Level Progress'), `${progress.level_progress || 0}%`, progress.level_progress || 0);
    setMetric(metrics[3], 'Rocks', formatNumber(profile.rocks || 0));

    const counters = document.querySelectorAll('.overview-counts article');
    const values = [
      [badges.messages || 0, t('messages', 'Messages')],
      [badges.notifications || 0, t('notifications', 'Notifications')],
      [badges.friends || 0, t('requests', 'Requests')],
    ];

    counters.forEach((counter, index) => {
      const strong = counter.querySelector('strong');
      const span = counter.querySelector('span');
      if (strong) strong.textContent = formatNumber(values[index]?.[0] || 0);
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

  const updateProgressTable = (profile, progress) => {
    const table = document.querySelector('.personal-progress-table');
    if (!table) return;

    const total = Number(progress.total) || 0;
    const open = Number(progress.open) || 0;
    const completed = Number(progress.completed) || 0;
    const next = progress.next_open || null;
    const level = Math.max(1, Number(profile.level) || 1);
    const aggregateStatus = total === 0
      ? { label: t('none', 'None'), css: 'review-status' }
      : open === 0
        ? { label: t('done', 'Done'), css: 'open-status' }
        : { label: t('active', 'Active'), css: 'active-status' };

    const rows = [
      progressRow({
        icon: 'W',
        iconClass: 'contract',
        title: t('weekly_contracts', 'Weekly Contracts'),
        subtitle: total > 0
          ? t('open_remaining', ':count still open', { count: open })
          : t('no_active_contracts', 'No active contracts right now'),
        value: `${completed} / ${total}`,
        percent: progress.completion_percent || 0,
        reward: `${formatNumber(progress.available_xp || 0)} XP`,
        status: aggregateStatus.label,
        statusClass: aggregateStatus.css,
      }),
      next
        ? progressRow({
            icon: 'N',
            iconClass: 'challenge',
            title: next.name || t('weekly_contract', 'Weekly Contract'),
            subtitle: next.action || t('weekly_contract', 'Weekly Contract'),
            value: `${next.current || 0} / ${next.target || 0}`,
            percent: next.percent || 0,
            reward: next.reward || '+0 XP',
            status: t('open', 'Open'),
            statusClass: 'open-status',
          })
        : progressRow({
            icon: '✓',
            iconClass: 'challenge',
            title: total > 0
              ? t('all_contracts_done', 'All contracts completed')
              : t('no_weekly_contracts', 'No weekly contracts'),
            subtitle: total > 0
              ? t('strong_weekly_progress', 'Strong weekly progress')
              : t('nothing_open', 'Nothing is open right now'),
            value: total > 0 ? '100%' : '—',
            percent: total > 0 ? 100 : 0,
            reward: `${formatNumber(progress.claimed_xp || 0)} XP`,
            status: total > 0 ? t('finished', 'Finished') : t('none', 'None'),
            statusClass: total > 0 ? 'open-status' : 'review-status',
          }),
      progressRow({
        icon: 'L',
        iconClass: 'profile',
        title: `Level ${level}`,
        subtitle: t('progress_to_level', 'Progress to level :level', { level: level + 1 }),
        value: `${progress.level_progress || 0}%`,
        percent: progress.level_progress || 0,
        reward: `Level ${level + 1}`,
        status: t('running', 'In progress'),
        statusClass: 'active-status',
      }),
    ].join('');

    table.innerHTML = `
      <div class="personal-progress-labels">
        <span>${escapeHtml(t('activity', 'Activity'))}</span><span>${escapeHtml(t('progress', 'Progress'))}</span><span>${escapeHtml(t('reward', 'Reward'))}</span><span>${escapeHtml(t('status', 'Status'))}</span>
      </div>
      ${rows}
    `;
  };

  const updateAttention = (badges) => {
    const host = document.querySelector('.personal-attention-strip > div');
    if (!host) return;

    host.innerHTML = `
      <button type="button"><b>${formatNumber(badges.messages || 0)}</b> ${escapeHtml(t('messages', 'Messages'))}</button>
      <button type="button"><b>${formatNumber(badges.notifications || 0)}</b> ${escapeHtml(t('notifications', 'Notifications'))}</button>
      <button type="button"><b>${formatNumber(badges.friends || 0)}</b> ${escapeHtml(t('requests', 'Requests'))}</button>
    `;
  };

  const updateProfileLevel = (profile, progress) => {
    const level = Math.max(1, Number(profile.level) || 1);
    const percent = Math.max(0, Math.min(100, Number(progress.level_progress) || 0));
    const label = document.querySelector('.profile-level .level-row span');
    const value = document.querySelector('.profile-level .level-row strong');
    const bar = document.querySelector('.profile-level .level-bar i');
    const hint = document.querySelector('.profile-level small');

    if (label) label.textContent = `Level ${level}`;
    if (value) value.textContent = `${percent}%`;
    if (bar) bar.style.width = `${percent}%`;
    if (hint) hint.textContent = t('progress_to_level', 'Progress to level :level', { level: level + 1 });
  };

  const renderUnavailable = () => {
    const table = document.querySelector('.personal-progress-table');
    if (!table) return;
    table.innerHTML = `
      <article class="personal-progress-row" data-real-dashboard-row>
        <span class="personal-progress-icon contract">!</span>
        <div class="personal-progress-copy">
          <strong>${escapeHtml(t('progress_unavailable', 'Progress unavailable'))}</strong>
          <small>${escapeHtml(t('real_values_unavailable', 'The live values could not be loaded right now.'))}</small>
        </div>
        <div class="personal-progress-value"><strong>—</strong></div>
        <span class="personal-reward">—</span>
        <span class="status review-status"><i></i>${escapeHtml(t('error', 'Error'))}</span>
      </article>
    `;
  };

  const addStyle = () => {
    if (document.getElementById('real-dashboard-progress-live-style')) return;
    const style = document.createElement('style');
    style.id = 'real-dashboard-progress-live-style';
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
    addStyle();
    applyRocksLabels();
    const table = document.querySelector('.personal-progress-table');
    table?.setAttribute('data-real-loading', '1');

    const base = new URL(window.location.href);
    base.search = '';
    base.hash = '';
    const dashboardUrl = new URL(base);
    dashboardUrl.searchParams.set('data', '1');
    dashboardUrl.searchParams.set('mode', 'for-you');
    dashboardUrl.searchParams.set('page', '1');
    const progressUrl = new URL(base);
    progressUrl.searchParams.set('dashboard_progress', '1');

    try {
      const [dashboardPayload, progressPayload] = await Promise.all([
        fetchJson(dashboardUrl.toString()),
        fetchJson(progressUrl.toString()),
      ]);

      const profile = dashboardPayload.profile || {};
      const badges = dashboardPayload.badges || {};
      const progress = progressPayload.progress || {};

      updateOverview(profile, badges, progress);
      updateProgressTable(profile, progress);
      updateAttention(badges);
      updateProfileLevel(profile, progress);
      updateActivityRocks(profile);
      applyRocksLabels();
    } catch (error) {
      console.error('HNT live dashboard progress failed', error);
      renderUnavailable();
    } finally {
      table?.removeAttribute('data-real-loading');
    }
  };

  window.setTimeout(boot, 80);
})();