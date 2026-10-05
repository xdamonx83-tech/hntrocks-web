/* HNT.ROCKS – isolated Hunt-only statistics tab.
 * Does not rebuild or modify the existing React app or sidebar. */
(() => {
  "use strict";
  const TAB = "hnt-hunt-stats-tab";
  const PANEL = "hnt-hunt-stats-panel";
  const STORAGE = "hnt.next.auth.session";
  const L = {
    de: {
      tab: "Spielstatistiken", title: "Hunt: Showdown 1896 – Spielstatistiken",
      intro: "Deine Hunt-Daten, nach Plattform getrennt.", playtime: "Spielzeit",
      achievements: "Erfolge", unknown: "Nicht verfügbar",
      pending: "Noch nicht verfügbar", noLink: "Kein Konto verbunden",
      noAchievements: "Steam hat für dieses Konto derzeit keine Erfolgsdaten geliefert. Prüfe die Steam-Privatsphäre deiner Spieldetails und aktualisiere die Daten unter „Profil bearbeiten“.",
      last: "Letzter Abgleich", connected: "Verbunden", status: "In Vorbereitung",
    },
    en: {
      tab: "Game statistics", title: "Hunt: Showdown 1896 – Game statistics",
      intro: "Your Hunt data, separated by platform.", playtime: "Playtime",
      achievements: "Achievements", unknown: "Unavailable",
      pending: "Not yet available", noLink: "No account linked",
      noAchievements: "Steam currently did not provide achievements for this account. Check the privacy of your Steam game details and refresh in Edit Profile.",
      last: "Last sync", connected: "Connected", status: "Coming soon",
    },
    es: {
      tab: "Estadísticas", title: "Hunt: Showdown 1896 – Estadísticas",
      intro: "Tus datos de Hunt, separados por plataforma.", playtime: "Tiempo jugado",
      achievements: "Logros", unknown: "No disponible",
      pending: "Aún no disponible", noLink: "Sin cuenta vinculada",
      noAchievements: "Steam no ha proporcionado logros para esta cuenta. Comprueba la privacidad de los detalles del juego en Steam y actualiza desde Editar perfil.",
      last: "Última sincronización", connected: "Conectado", status: "Próximamente",
    },
    ru: {
      tab: "Статистика", title: "Hunt: Showdown 1896 – Статистика",
      intro: "Данные Hunt для каждой платформы.", playtime: "Время в игре",
      achievements: "Достижения", unknown: "Недоступно",
      pending: "Пока недоступно", noLink: "Аккаунт не подключён",
      noAchievements: "Steam пока не предоставил достижения этого аккаунта. Проверьте приватность сведений об играх в Steam и обновите данные в редактировании профиля.",
      last: "Последняя синхронизация", connected: "Подключено", status: "Скоро",
    },
  };
  const platforms = [
    { id: "steam", label: "Steam", icon: "ph-fill ph-steam-logo" },
    { id: "xbox", label: "Xbox", icon: "ph ph-game-controller" },
    { id: "playstation", label: "PlayStation", icon: "ph ph-game-controller" },
  ];
  function lang() {
    const code = (document.documentElement.lang ||
      localStorage.getItem("i18nextLng") || navigator.language || "de")
      .toLowerCase().split(/[-_]/)[0];
    return Object.hasOwn(L, code) ? code : "de";
  }
  function el(tag, className = "", value) {
    const x = document.createElement(tag);
    if (className) x.className = className;
    if (value !== undefined) x.textContent = String(value);
    return x;
  }
  function session() {
    for (const storage of [localStorage, sessionStorage]) {
      try {
        const x = JSON.parse(storage.getItem(STORAGE) || "null");
        if (typeof x?.token_type !== "string" || typeof x?.access_token !== "string") continue;
        if (x.expires_at && Date.parse(x.expires_at) <= Date.now()) continue;
        return x;
      } catch { /* expired or invalid session */ }
    }
    return null;
  }
  function profile() {
    const main = document.querySelector(".profile-main");
    const nav = main?.querySelector(".profile-tabs");
    const overview = main?.querySelector(".profile-overview-grid");
    const hero = main?.querySelector(".profile-handle");
    const username = (hero?.textContent || "").trim().match(/^@([a-z0-9_.-]+)/i)?.[1];
    if (!main || !nav || !overview || !username) return null;
    return { main, nav, overview, username };
  }
  let activeKey = "";
  let lastKey = "";
  let inFlight = "";
  const dataCache = new Map();
  function render(panel, accounts, t) {
    panel.replaceChildren();
    const h = el("div", "hnt-hunt-stats-intro");
    h.append(el("h2", "", t.title), el("p", "", t.intro));
    panel.append(h);
    const grid = el("div", "hnt-hunt-stats-grid");
    for (const p of platforms) {
      const account = accounts.find(a => a?.provider === p.id);
      const card = el("article", "hnt-hunt-platform-card");
      const head = el("div", "hnt-hunt-platform-head");
      const icon = el("i", p.icon);
      icon.setAttribute("aria-hidden", "true");
      const name = el("div", "hnt-hunt-platform-name");
      name.append(el("strong", "", p.label),
        el("span", "", account ? t.connected : p.id === "steam" ? t.noLink : t.status));
      head.append(icon, name);
      card.append(head);

      if (account) {
        const statArea = el("div", "hnt-hunt-platform-metrics");
        const hours = account.hunt?.playtime_minutes;
        const unlocked = account.hunt?.achievements_unlocked;
        const total = account.hunt?.achievements_total;
        const played = hours == null ? t.unknown
          : new Intl.NumberFormat(lang(), { maximumFractionDigits: 1 }).format(hours / 60) + " h";
        for (const [label, value] of [
          [t.playtime, played],
          [t.achievements, unlocked == null || total == null
            ? t.unknown : unlocked + " / " + total],
        ]) {
          const box = el("div", "hnt-hunt-platform-metric");
          box.append(el("span", "", label), el("strong", "", value));
          statArea.append(box);
        }
        card.append(statArea);
        if (total > 0 && unlocked != null) {
          const progress = el("div", "hnt-hunt-achievement-progress");
          const bar = el("div", "hnt-hunt-achievement-progress-value");
          bar.style.width = Math.max(0, Math.min(100, (unlocked / total) * 100)) + "%";
          progress.append(bar);
          progress.setAttribute("role", "progressbar");
          progress.setAttribute("aria-valuenow", String(unlocked));
          progress.setAttribute("aria-valuemin", "0");
          progress.setAttribute("aria-valuemax", String(total));
          card.append(progress);
        } else if (p.id === "steam") {
          card.append(el("p", "hnt-hunt-platform-note", t.noAchievements));
        }
        if (account.last_synced_at) {
          const date = new Date(account.last_synced_at);
          if (!Number.isNaN(date.getTime())) {
            card.append(el("small", "hnt-hunt-platform-updated",
              t.last + ": " + new Intl.DateTimeFormat(lang(),
                { dateStyle: "medium", timeStyle: "short" }).format(date)));
          }
        }
      } else {
        card.append(el("p", "hnt-hunt-platform-note",
          p.id === "steam" ? t.noLink : t.pending));
      }
      grid.append(card);
    }
    panel.append(grid);
  }
  function setTabState(main, button, panel, key) {
    const isOpen = activeKey === key;
    main.classList.toggle("hnt-hunt-stats-active", isOpen);
    button.classList.toggle("active", isOpen);
    button.setAttribute("aria-selected", String(isOpen));
    // The original overview tab retains its React state. Only its visual
    // active style is overridden while our independent tab is selected.
    panel.hidden = !isOpen;
  }
  function mount() {
    const p = profile();
    if (!p) return;
    const key = location.pathname + ":" + p.username;
    if (lastKey && lastKey !== key) activeKey = "";
    lastKey = key;
    // Remove only a leftover legacy stat card. Never modify React cards.
    document.getElementById("hnt-hunt-profile-stats")?.remove();

    let button = p.nav.querySelector("#" + TAB);
    if (!button) {
      button = el("button", "profile-tab", L[lang()].tab);
      button.id = TAB;
      button.type = "button";
      const icon = el("i", "ph ph-chart-bar");
      icon.setAttribute("aria-hidden", "true");
      button.prepend(icon);
      button.addEventListener("click", event => {
        event.preventDefault();
        event.stopPropagation();
        activeKey = key;
        setTabState(p.main, button, panel, key);
      });
      p.nav.append(button);
    }
    let panel = p.main.querySelector("#" + PANEL);
    if (!panel) {
      panel = el("section", "profile-tab-panel hnt-hunt-stats-panel");
      panel.id = PANEL;
      const tabsShell = p.main.querySelector(".profile-tabs-shell");
      tabsShell?.insertAdjacentElement("afterend", panel);
    }
    panel.dataset.username = p.username;
    setTabState(p.main, button, panel, key);
    if (dataCache.has(key)) {
      if (panel.dataset.loaded !== key) {
        render(panel, dataCache.get(key), L[lang()]);
        panel.dataset.loaded = key;
      }
      return;
    }
    if (inFlight === key) return;
    const auth = session();
    if (!auth) return;
    inFlight = key;
    panel.append(el("p", "hnt-hunt-platform-note", "…"));
    fetch("/api/v1/users/" + encodeURIComponent(p.username), {
      method: "GET",
      cache: "no-store",
      credentials: "same-origin",
      headers: { Accept: "application/json",
        Authorization: auth.token_type + " " + auth.access_token },
    }).then(response => {
      if (!response.ok) throw new Error("Profile unavailable");
      return response.json();
    }).then(data => {
      const current = profile();
      if (!current || current.username !== p.username) return;
      const accounts = Array.isArray(data?.hunt_game_accounts) ? data.hunt_game_accounts : [];
      dataCache.set(key, accounts);
      // React may have rerendered the profile while fetching.
      const newPanel = current.main.querySelector("#" + PANEL);
      if (newPanel) {
        render(newPanel, accounts, L[lang()]);
        newPanel.dataset.loaded = key;
      }
    }).catch(() => {
      const current = profile();
      const area = current?.main.querySelector("#" + PANEL);
      if (area) area.replaceChildren(el("p", "hnt-hunt-platform-note", L[lang()].unknown));
    }).finally(() => { if (inFlight === key) inFlight = ""; });
  }
  // Route buttons are controlled by React. Close the statistics panel when
  // navigating to any existing tab, without changing its React state.
  document.addEventListener("click", event => {
    const button = event.target instanceof Element
      ? event.target.closest(".profile-tabs .profile-tab") : null;
    if (button && button.id !== TAB) {
      activeKey = "";
      const p = profile();
      if (p) {
        p.main.classList.remove("hnt-hunt-stats-active");
        p.nav.querySelector("#" + TAB)?.classList.remove("active");
      }
    }
  }, true);
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", mount, { once: true });
  } else mount();
  new MutationObserver(mount).observe(document.documentElement,
    { childList: true, subtree: true });
  window.addEventListener("popstate", () => { activeKey = ""; mount(); });
})();
