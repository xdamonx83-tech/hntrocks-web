/* Real login streak, Rocks naming and 30-day activity for the isolated dashboard preview. */
(() => {
  const root = document.querySelector('.feed-shell');
  if (!root || !window.fetch) return;

  let streakStatus = null;
  let activity = null;

  const number = (value) => new Intl.NumberFormat(document.documentElement.lang || 'de')
    .format(Number.parseInt(value, 10) || 0);

  const setText = (node, value) => {
    if (node) node.textContent = value;
  };

  const escapeHtml = (value = '') => String(value).replace(/[&<>"']/g, (character) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  })[character]);

  const applyRocksLabels = () => {
    setText(document.querySelector('.profile-stats article:first-child span'), 'Rocks');
    setText(document.querySelector('.header-profile-stats span:first-child small'), 'Rocks');

    const overviewMetrics = document.querySelectorAll('.overview-progress .overview-metric');
    setText(overviewMetrics[3]?.querySelector(':scope > span'), 'Rocks');

    const summaries = document.querySelectorAll('.personal-activity-summary article');
    setText(summaries[2]?.querySelector('span'), 'Rocks verfügbar');

    document.querySelectorAll('*').forEach((node) => {
      if (node.children.length) return;
      const value = node.textContent?.trim();
      if (value === 'Bounty Marks' || value === 'Marks') node.textContent = 'Rocks';
      if (value === 'Marks verfügbar') node.textContent = 'Rocks verfügbar';
    });
  };

  const applyStreak = () => {
    if (!streakStatus) return;

    const current = Math.max(0, Number(streakStatus.current_streak) || 0);
    const max = Math.max(1, Number(streakStatus.max_streak_days) || 7);
    const percent = Math.max(0, Math.min(100, Math.round((current / max) * 100)));

    const summaries = document.querySelectorAll('.personal-activity-summary article');
    setText(summaries[0]?.querySelector('strong'), number(current));
    setText(summaries[0]?.querySelector('span'), 'Login-Serie');

    const metrics = document.querySelectorAll('.overview-progress .overview-metric');
    const metric = metrics[1];
    if (metric) {
      setText(metric.querySelector(':scope > span'), 'Login-Serie');
      const bar = metric.querySelector('.bar');
      if (bar) {
        bar.textContent = `${current} / ${max}`;
        bar.style.setProperty('--real-progress', `${percent}%`);
        bar.dataset.realProgress = '1';
      }
    }
  };

  const addActivityStyle = () => {
    if (document.getElementById('real-dashboard-activity-style')) return;

    const style = document.createElement('style');
    style.id = 'real-dashboard-activity-style';
    style.textContent = `
      .personal-heatmap[data-real-activity="1"] {
        display: grid !important;
        grid-template-columns: repeat(10, 20px) !important;
        grid-auto-rows: 20px;
        justify-content: center;
        align-content: center;
        gap: 10px !important;
      }

      .personal-heatmap[data-real-activity="1"] span {
        width: 20px !important;
        height: 20px !important;
        margin: 0 !important;
        border-radius: 50%;
        background: #5b5c5a !important;
        transition: transform .18s ease, background-color .18s ease;
      }

      .personal-heatmap[data-real-activity="1"] span:hover {
        transform: scale(1.18);
      }

      .personal-heatmap[data-real-activity="1"] .activity-level-1 { background: rgba(255, 204, 68, .32) !important; }
      .personal-heatmap[data-real-activity="1"] .activity-level-2 { background: rgba(255, 204, 68, .52) !important; }
      .personal-heatmap[data-real-activity="1"] .activity-level-3 { background: rgba(255, 204, 68, .76) !important; }
      .personal-heatmap[data-real-activity="1"] .activity-level-4 { background: rgb(255, 204, 68) !important; }

      @media (max-width: 520px) {
        .personal-heatmap[data-real-activity="1"] {
          grid-template-columns: repeat(10, 16px) !important;
          grid-auto-rows: 16px;
          gap: 7px !important;
        }

        .personal-heatmap[data-real-activity="1"] span {
          width: 16px !important;
          height: 16px !important;
        }
      }
    `;
    document.head.appendChild(style);
  };

  const applyActivity = () => {
    if (!activity) return;

    const values = document.querySelectorAll('.personal-activity-values > div');
    setText(values[0]?.querySelector('strong'), number(activity.active_days || 0));
    setText(values[0]?.querySelector('span'), 'Tage aktiv');
    setText(values[1]?.querySelector('strong'), number(activity.actions || 0));
    setText(values[1]?.querySelector('span'), 'Aktionen');

    const summaries = document.querySelectorAll('.personal-activity-summary article');
    setText(summaries[1]?.querySelector('strong'), number(activity.xp_this_week || 0));
    setText(summaries[1]?.querySelector('span'), 'XP diese Woche');

    const heatmap = document.querySelector('.personal-heatmap');
    if (!heatmap || !Array.isArray(activity.days)) return;

    addActivityStyle();
    heatmap.dataset.realActivity = '1';
    heatmap.setAttribute('aria-label', 'Aktivität der letzten 30 Tage');
    heatmap.innerHTML = activity.days.map((day) => {
      const count = Math.max(0, Number(day.count) || 0);
      const level = Math.max(0, Math.min(4, Number(day.level) || 0));
      const label = `${day.date}: ${count} ${count === 1 ? 'Aktivität' : 'Aktivitäten'}`;

      return `<span class="activity-level-${level}" title="${escapeHtml(label)}" aria-label="${escapeHtml(label)}"></span>`;
    }).join('');
  };

  const applyAll = () => {
    applyRocksLabels();
    applyStreak();
    applyActivity();
  };

  const load = async () => {
    applyRocksLabels();

    try {
      const url = new URL(window.location.href);
      url.search = '';
      url.hash = '';
      url.searchParams.set('dashboard_streak', '1');

      const response = await fetch(url.toString(), {
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      if (!response.ok) throw new Error(`Dashboard activity request failed with ${response.status}`);
      const payload = await response.json();
      streakStatus = payload.daily_streak || null;
      activity = payload.activity || null;
      applyAll();
    } catch (error) {
      console.error('HNT dashboard activity failed', error);
    }

    [250, 700, 1400, 2400].forEach((delay) => window.setTimeout(applyAll, delay));
  };

  window.setTimeout(load, 160);
})();
