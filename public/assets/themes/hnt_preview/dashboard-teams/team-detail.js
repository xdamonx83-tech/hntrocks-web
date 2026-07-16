(() => {
  const assetBase = '/assets/themes/hnt_preview/dashboard-feed/assets';

  function ensureDemoStructure() {
    const quickNav = document.querySelector('.team-quick-nav-card nav');
    const memberQuick = quickNav?.querySelector('[data-open-team-tab="members"]');

    if (quickNav && !quickNav.querySelector('[data-open-team-tab="info"]')) {
      memberQuick?.insertAdjacentHTML('beforebegin', `
        <button data-open-team-tab="info" type="button">
          <svg><use href="#i-eye"></use></svg>
          <span><strong>Info</strong><small>Beschreibung und Regeln</small></span>
          <i>06</i>
        </button>
      `);
    }

    if (quickNav && !quickNav.querySelector('[data-open-team-tab="requests"]')) {
      quickNav.insertAdjacentHTML('beforeend', `
        <button data-open-team-tab="requests" type="button">
          <svg><use href="#i-user"></use></svg>
          <span><strong>Anfragen</strong><small>Eine neue Anfrage</small></span>
          <i>1</i>
        </button>
      `);
    }

    const tabsNav = document.querySelector('.team-tabs');
    const memberTab = tabsNav?.querySelector('[data-team-tab="members"]');

    if (tabsNav && !tabsNav.querySelector('[data-team-tab="info"]')) {
      memberTab?.insertAdjacentHTML('beforebegin', '<button data-team-tab="info" type="button">Info</button>');
    }

    if (tabsNav && !tabsNav.querySelector('[data-team-tab="requests"]')) {
      tabsNav.insertAdjacentHTML('beforeend', '<button data-team-tab="requests" type="button">Anfragen <i>1</i></button>');
    }

    const panels = document.querySelector('.team-panels');
    const memberPanel = panels?.querySelector('[data-team-panel="members"]');

    if (panels && !panels.querySelector('[data-team-panel="info"]')) {
      memberPanel?.insertAdjacentHTML('beforebegin', `
        <section class="team-panel" data-team-panel="info" hidden>
          <div class="team-info-intro">
            <div><span>ÜBER DAS TEAM</span><h3>Gemeinsam jagen. Ruhig kommunizieren.</h3></div>
            <p>Night Ravens ist ein kleines Konsolen-Team für regelmäßige Hunt-Abende, gemeinsame Trainings und taktische Trio-Runden.</p>
          </div>

          <section class="team-info-layout">
            <article class="team-description-card">
              <span>BESCHREIBUNG</span>
              <h3>Wofür Night Ravens steht</h3>
              <p>Wir spielen zielorientiert, aber ohne unnötigen Druck. Klare Calls, verlässliche Zeiten und ein respektvoller Umgang sind wichtiger als einzelne Ergebnisse. Neue Mitspieler sollten regelmäßig abends verfügbar sein und Voice nutzen.</p>
              <div class="team-tag-list">
                <span>Competitive</span><span>Konsole</span><span>EU</span><span>Voice</span><span>Training</span>
              </div>
            </article>

            <article class="team-data-card">
              <span>TEAMDATEN</span>
              <dl>
                <div><dt>Gründung</dt><dd>18. Mai 2026</dd></div>
                <div><dt>Sichtbarkeit</dt><dd>Öffentlich</dd></div>
                <div><dt>Recruiting</dt><dd>Offen</dd></div>
                <div><dt>Plattform</dt><dd>PS5 / Xbox</dd></div>
                <div><dt>Region</dt><dd>Europa</dd></div>
                <div><dt>Sprache</dt><dd>Deutsch / Englisch</dd></div>
              </dl>
            </article>
          </section>

          <section class="team-rules-card">
            <header><div><span>TEAMREGELN</span><h3>Einfach und klar</h3></div><small>Für alle Mitglieder</small></header>
            <div class="team-rules-list">
              <article><span>01</span><div><strong>Respekt zuerst</strong><small>Keine Beleidigungen, kein unnötiger Druck und kein toxisches Verhalten.</small></div></article>
              <article><span>02</span><div><strong>Absprachen einhalten</strong><small>Bei Sessions und Teamabenden rechtzeitig zu- oder absagen.</small></div></article>
              <article><span>03</span><div><strong>Klare Kommunikation</strong><small>Voice nutzen, relevante Calls kurz halten und anderen Raum lassen.</small></div></article>
              <article><span>04</span><div><strong>Team vor Ego</strong><small>Gemeinsame Entscheidungen gehen vor Einzelaktionen.</small></div></article>
            </div>
          </section>

          <section class="team-organizer-card">
            <header><div><span>ORGANISATION</span><h3>Teamleitung</h3></div><button data-open-team-tab="members" type="button">Alle Mitglieder</button></header>
            <div>
              <img alt="Valentina" src="${assetBase}/amelie.jpg">
              <div><strong>Valentina</strong><span>Captain · Teamgründerin</span><small>Planung, Sessions und Recruiting</small></div>
              <a href="#" data-toast="Demo-Profil geöffnet"><svg><use href="#i-arrow"></use></svg></a>
            </div>
          </section>
        </section>
      `);
    }

    if (panels && !panels.querySelector('[data-team-panel="requests"]')) {
      panels.insertAdjacentHTML('beforeend', `
        <section class="team-panel" data-team-panel="requests" hidden>
          <div class="team-request-intro">
            <div><span>BEITRITTSANFRAGEN</span><h3>Eine offene Anfrage</h3><p>Prüfe Profil, Plattform und Spielstil, bevor du eine Anfrage annimmst.</p></div>
            <span class="request-counter">1 offen</span>
          </div>

          <div class="team-request-list" id="teamRequestList">
            <article class="team-request-item">
              <img alt="Katy Fuller" src="${assetBase}/katy.jpg">
              <div class="team-request-copy">
                <span>BEITRITTSANFRAGE</span>
                <h3>Katy Fuller</h3>
                <small>@katy · Xbox · EU · Teamplay</small>
                <p>Ich spiele meistens abends und suche ein ruhiges festes Team. Voice ist kein Problem und regelmäßige Sessions passen gut.</p>
                <div><span>Level 21</span><span>94 Freunde</span><span>23 Moments</span></div>
              </div>
              <div class="team-request-actions">
                <a href="#" data-toast="Demo-Profil geöffnet">Profil ansehen <svg><use href="#i-arrow"></use></svg></a>
                <button class="accept-request" type="button">Annehmen</button>
                <button class="reject-request" type="button">Ablehnen</button>
              </div>
            </article>
          </div>

          <article class="team-request-empty" hidden id="teamRequestEmpty">
            <span><svg><use href="#i-check"></use></svg></span>
            <div><strong>Keine offenen Anfragen</strong><small>Neue Anfragen erscheinen automatisch in diesem Bereich.</small></div>
          </article>
        </section>
      `);
    }
  }

  ensureDemoStructure();

  const tabs = [...document.querySelectorAll('[data-team-tab]')];
  const panels = [...document.querySelectorAll('[data-team-panel]')];
  const openers = [...document.querySelectorAll('[data-open-team-tab]')];
  const title = document.getElementById('teamPanelTitle');
  const scroll = document.getElementById('teamDetailScroll');

  const toast = (message) => {
    if (typeof window.showToast === 'function') {
      window.showToast(message);
      return;
    }

    const node = document.getElementById('toast');
    if (!node) return;
    node.textContent = message;
    node.classList.add('show');
    window.clearTimeout(node._hntTimer);
    node._hntTimer = window.setTimeout(() => node.classList.remove('show'), 2200);
  };

  const labels = {
    overview: 'Übersicht',
    posts: 'Beiträge',
    info: 'Info',
    members: 'Mitglieder',
    requests: 'Anfragen'
  };

  const syncScrollState = () => {
    scroll?.classList.toggle('is-scrolled', (scroll?.scrollTop || 0) > 8);
  };

  function openTeamPanel(requestedName, updateHash = true) {
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
    syncScrollState();
  }

  tabs.forEach((tab) => {
    tab.addEventListener('click', () => openTeamPanel(tab.dataset.teamTab));
  });

  openers.forEach((button) => {
    button.addEventListener('click', () => openTeamPanel(button.dataset.openTeamTab));
  });

  scroll?.addEventListener('scroll', syncScrollState, { passive: true });
  syncScrollState();

  const composer = document.getElementById('teamComposerText');
  const publishButton = document.getElementById('publishTeamPost');
  const feedList = document.getElementById('teamFeedList');

  publishButton?.addEventListener('click', () => {
    const text = composer?.value.trim() || '';
    if (!text) {
      toast('Schreibe zuerst einen Team-Beitrag');
      composer?.focus();
      return;
    }

    const post = document.createElement('article');
    post.className = 'social-post newly-added';
    post.innerHTML = `
      <header class="post-head">
        <img src="${assetBase}/amelie.jpg" alt="Valentina">
        <div class="post-author"><strong>Valentina</strong><span>@valentina · gerade eben</span></div>
        <span class="post-badge discussion">Team</span>
        <button class="post-more" type="button" data-toast="Post-Optionen geöffnet"><svg><use href="#i-more"></use></svg></button>
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
    toast('Team-Beitrag als Demo veröffentlicht');
  });

  const requestList = document.getElementById('teamRequestList');
  const requestEmpty = document.getElementById('teamRequestEmpty');

  function resolveRequest(message) {
    const item = requestList?.querySelector('.team-request-item');
    if (!item) return;
    item.remove();
    if (requestEmpty) requestEmpty.hidden = false;
    toast(message);
  }

  document.querySelector('.accept-request')?.addEventListener('click', () => resolveRequest('Beitrittsanfrage angenommen'));
  document.querySelector('.reject-request')?.addEventListener('click', () => resolveRequest('Beitrittsanfrage abgelehnt'));

  openTeamPanel(window.location.hash.replace('#', '') || 'overview', false);
})();

(() => {
  const loadScriptOnce = (src, marker) => new Promise((resolve, reject) => {
    const existing = document.querySelector(`script[src*="${src.split('/').pop()}"]`);
    if (existing) {
      resolve();
      return;
    }

    const script = document.createElement('script');
    script.src = `${src}?v=20260716-6`;
    script.async = false;
    script.dataset[marker] = '1';
    script.addEventListener('load', resolve, { once: true });
    script.addEventListener('error', reject, { once: true });
    document.head.appendChild(script);
  });

  loadScriptOnce('/assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js', 'hntTeamDetailHeader')
    .then(() => loadScriptOnce('/assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js', 'hntTeamDetailHeaderLive'))
    .catch((error) => console.error('HNT team detail header runtime failed', error));
})();
