(() => {
  const loadScriptOnce = (src, marker) => new Promise((resolve, reject) => {
    const existing = document.querySelector(`script[src*="${src.split('/').pop()}"]`);
    if (existing) { resolve(); return; }
    const script = document.createElement('script');
    script.src = `${src}?v=20260716-3`;
    script.async = false;
    script.dataset[marker] = '1';
    script.addEventListener('load', resolve, { once: true });
    script.addEventListener('error', reject, { once: true });
    document.head.appendChild(script);
  });
  loadScriptOnce('/assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js','hntTeamDetailHeader')
    .then(() => loadScriptOnce('/assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js','hntTeamDetailHeaderLive'))
    .catch((error) => console.error('HNT team detail header runtime failed', error));
})();
