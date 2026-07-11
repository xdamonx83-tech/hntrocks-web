/* Prevent the legacy dashboard demo script from restoring fake community activity. */
(() => {
  const panel = document.getElementById('compositionPanel');
  const list = document.getElementById('compositionActivity');
  const state = document.getElementById('activityState');

  if (!panel || !list) return;

  let scheduled = false;

  const replaceLegacyRows = () => {
    scheduled = false;

    const rows = [...list.children];
    const hasLegacyRows = rows.some((row) =>
      row.dataset.communityReal !== '1' && row.dataset.communityGuard !== '1'
    );

    if (!hasLegacyRows) return;

    list.innerHTML = `
      <article class="activity-item" data-community-guard="1">
        <img src="/assets/vikinger/img/default-avatar.svg" alt="">
        <div>
          <strong>Keine bestätigte Live-Aktivität</strong>
          <small>Hier erscheinen ausschließlich echte Community-Ereignisse.</small>
        </div>
        <span>—</span>
      </article>
    `;

    if (state) state.textContent = '0 neu';
  };

  const schedule = () => {
    if (scheduled) return;
    scheduled = true;
    window.requestAnimationFrame(replaceLegacyRows);
  };

  new MutationObserver(schedule).observe(list, { childList: true });

  if (state) {
    new MutationObserver(schedule).observe(state, {
      childList: true,
      characterData: true,
      subtree: true,
    });
  }

  schedule();
})();
