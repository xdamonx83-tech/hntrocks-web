(() => {
  const notify = (message) => {
    if (typeof window.showToast === 'function') {
      window.showToast(message);
      return;
    }

    const toast = document.getElementById('toast');
    if (!toast) return;
    toast.textContent = message;
    toast.classList.add('show');
    window.clearTimeout(toast._hntTimer);
    toast._hntTimer = window.setTimeout(() => toast.classList.remove('show'), 2200);
  };

  const initTeamDetailDemo = () => {
    const scroll = document.getElementById('teamDetailScroll');
    const tabs = Array.from(document.querySelectorAll('[data-team-tab]'));
    const openers = Array.from(document.querySelectorAll('[data-open-team-tab]'));
    const panels = Array.from(document.querySelectorAll('[data-team-panel]'));
    const title = document.getElementById('teamPanelTitle');
    const labels = {
      overview: 'Übersicht',
      posts: 'Beiträge',
      members: 'Mitglieder'
    };

    const openPanel = (requestedName, updateHash = true) => {
      const name = panels.some((panel) => panel.dataset.teamPanel === requestedName)
        ? requestedName
        : 'overview';

      tabs.forEach((tab) => {
        const active = tab.dataset.teamTab === name;
        tab.classList.toggle('active', active);
        tab.setAttribute('aria-selected', active ? 'true' : 'false');
      });

      openers.forEach((button) => {
        button.classList.toggle('active', button.dataset.openTeamTab === name);
      });

      panels.forEach((panel) => {
        const active = panel.dataset.teamPanel === name;
        panel.hidden = !active;
        panel.classList.toggle('active', active);
      });

      if (title) title.textContent = labels[name] || labels.overview;
      if (updateHash) history.replaceState(null, '', `#${name}`);
      scroll?.scrollTo({ top: 0, behavior: 'smooth' });
    };

    tabs.forEach((tab) => {
      tab.addEventListener('click', () => openPanel(tab.dataset.teamTab));
    });

    openers.forEach((button) => {
      button.addEventListener('click', () => openPanel(button.dataset.openTeamTab));
    });

    if (scroll) {
      const syncScrollState = () => {
        scroll.classList.toggle('is-scrolled', scroll.scrollTop > 8);
      };
      scroll.addEventListener('scroll', syncScrollState, { passive: true });
      syncScrollState();
    }

    const composer = document.getElementById('teamComposerText');
    const publishButton = document.getElementById('publishTeamPost');
    const feedList = document.getElementById('teamFeedList');

    publishButton?.addEventListener('click', () => {
      const text = composer?.value.trim() || '';
      if (!text) {
        notify('Schreibe zuerst einen Team-Beitrag');
        composer?.focus();
        return;
      }

      const post = document.createElement('article');
      post.className = 'social-post newly-added';
      post.innerHTML = `
        <header class="post-head">
          <img src="/assets/themes/hnt_preview/dashboard-feed/assets/amelie.jpg" alt="Valentina">
          <div class="post-author">
            <strong>Valentina</strong>
            <span>@valentina · gerade eben</span>
          </div>
          <span class="post-badge discussion">Team</span>
          <button class="post-more" type="button" data-toast="Post-Optionen geöffnet">
            <svg><use href="#i-more"></use></svg>
          </button>
        </header>
        <div class="post-body"><p></p></div>
        <footer class="post-actions">
          <button class="like-button" type="button"><svg><use href="#i-heart"></use></svg><span>0</span></button>
          <button class="comment-button" type="button" aria-label="Kommentare öffnen"><svg><use href="#i-comment"></use></svg><span>0</span></button>
          <button type="button" data-toast="Post geteilt"><svg><use href="#i-share"></use></svg><span>Teilen</span></button>
          <button class="save-button" type="button" data-toast="Gespeichert"><svg><use href="#i-bookmark"></use></svg></button>
        </footer>
      `;
      post.querySelector('.post-body p').textContent = text;
      feedList?.prepend(post);
      if (composer) composer.value = '';
      notify('Team-Beitrag als Demo veröffentlicht');
    });

    openPanel(window.location.hash.replace('#', '') || 'overview', false);
  };

  const loadScriptOnce = (src, marker) => new Promise((resolve, reject) => {
    const existing = document.querySelector(`script[src*="${src.split('/').pop()}"]`);
    if (existing) {
      resolve();
      return;
    }

    const script = document.createElement('script');
    script.src = `${src}?v=20260716-4`;
    script.async = false;
    script.dataset[marker] = '1';
    script.addEventListener('load', resolve, { once: true });
    script.addEventListener('error', reject, { once: true });
    document.head.appendChild(script);
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTeamDetailDemo, { once: true });
  } else {
    initTeamDetailDemo();
  }

  loadScriptOnce('/assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js', 'hntTeamDetailHeader')
    .then(() => loadScriptOnce('/assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js', 'hntTeamDetailHeaderLive'))
    .catch((error) => console.error('HNT team detail header runtime failed', error));
})();
