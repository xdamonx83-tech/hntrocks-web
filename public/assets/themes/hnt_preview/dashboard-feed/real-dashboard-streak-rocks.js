/* Real login streak + final Rocks naming for the isolated dashboard preview. */
(() => {
  const root = document.querySelector('.feed-shell');
  if (!root || !window.fetch) return;

  let streakStatus = null;

  const number = (value) => new Intl.NumberFormat(document.documentElement.lang || 'de')
    .format(Number.parseInt(value, 10) || 0);

  const setText = (node, value) => {
    if (node) node.textContent = value;
  };

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

  const applyAll = () => {
    applyRocksLabels();
    applyStreak();
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

      if (!response.ok) throw new Error(`Login streak request failed with ${response.status}`);
      const payload = await response.json();
      streakStatus = payload.daily_streak || null;
      applyAll();
    } catch (error) {
      console.error('HNT dashboard login streak failed', error);
    }

    [250, 700, 1400, 2400].forEach((delay) => window.setTimeout(applyAll, delay));
  };

  window.setTimeout(load, 160);
})();
