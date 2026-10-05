/* HNT.ROCKS profile overview: Hunt statistics only, no React rebuild. */
(() => {
  "use strict";
  const ID = "hnt-hunt-profile-stats";
  const AUTH_KEY = "hnt.next.auth.session";
  const texts = {
    de: { title: "Hunt-Spielstatistiken", hours: "Spielzeit", achievements: "Erfolge", unavailable: "Nicht verfügbar" },
    en: { title: "Hunt game statistics", hours: "Playtime", achievements: "Achievements", unavailable: "Unavailable" },
    es: { title: "Estadísticas de Hunt", hours: "Tiempo jugado", achievements: "Logros", unavailable: "No disponible" },
    ru: { title: "Статистика Hunt", hours: "Время в игре", achievements: "Достижения", unavailable: "Недоступно" },
  };
  const locale = () => {
    const language = (
      document.documentElement.lang ||
      window.localStorage.getItem("i18nextLng") ||
      navigator.language || "de"
    ).toLowerCase().split(/[-_]/)[0];
    return Object.hasOwn(texts, language) ? language : "de";
  };
  const node = (type, cls, value) => {
    const result = document.createElement(type);
    if (cls) result.className = cls;
    if (value !== undefined) result.textContent = String(value);
    return result;
  };
  const getSession = () => {
    for (const store of [window.localStorage, window.sessionStorage]) {
      try {
        const data = JSON.parse(store.getItem(AUTH_KEY) || "null");
        if (typeof data?.access_token !== "string" || typeof data?.token_type !== "string") continue;
        if (data.expires_at && Date.parse(data.expires_at) <= Date.now()) continue;
        return data;
      } catch { /* no valid session */ }
    }
    return null;
  };

  // The username must come from the visible profile hero (not the viewer's
  // own auth session). This supports both /profile and /u/:username.
  function currentProfile() {
    const overview = document.querySelector(".profile-main .profile-overview-grid");
    const hero = document.querySelector(".profile-main .profile-handle");
    if (!overview || !hero) return null;
    const userMatch = (hero.textContent || "").trim().match(/^@([a-z0-9_.-]+)/i);
    if (!userMatch) return null;
    const panel = overview.closest(".profile-tab-panel");
    if (!panel) return null;
    return { username: userMatch[1], panel, overview };
  }

  let pendingUser = null;
  let lastAttempt = "";
  function mount() {
    const profile = currentProfile();
    if (!profile) {
      // The SPA might be leaving the profile route.
      return;
    }

    const existing = document.getElementById(ID);
    if (existing && existing.dataset.username === profile.username
        && existing.closest(".profile-tab-panel") === profile.panel) return;
    if (existing) existing.remove();

    // Avoid repeating network calls on every mutation if a profile has no
    // linked accounts. Reset automatically on navigation/new profile hero.
    const locationKey = location.pathname + ":" + profile.username;
    if (lastAttempt === locationKey || pendingUser === locationKey) return;
    const session = getSession();
    if (!session) return;
    lastAttempt = locationKey;
    pendingUser = locationKey;

    void fetch("/api/v1/users/" + encodeURIComponent(profile.username), {
      method: "GET",
      cache: "no-store",
      credentials: "same-origin",
      headers: {
        "Accept": "application/json",
        "Authorization": session.token_type + " " + session.access_token,
      },
    }).then(response => {
      if (!response.ok) throw new Error("Profile API: " + response.status);
      return response.json();
    }).then(payload => {
      const now = currentProfile();
      if (!now || now.username !== profile.username || !now.overview.isConnected) return;
      const accounts = Array.isArray(payload?.hunt_game_accounts) ? payload.hunt_game_accounts : [];
      const steam = accounts.find(value => value?.provider === "steam");
      if (!steam) return; // Nothing linked: no empty placeholder.
      const lang = locale();
      const t = texts[lang];
      const card = node("section", "profile-section-card hnt-hunt-profile-stats");
      card.id = ID;
      card.dataset.username = profile.username;
      const heading = node("div", "profile-card-head");
      const h3 = node("h3");
      const icon = node("i", "ph-fill ph-steam-logo");
      icon.setAttribute("aria-hidden", "true");
      h3.append(icon, document.createTextNode(" " + t.title));
      heading.append(h3);
      card.append(heading);
      const stats = node("div", "hnt-hunt-profile-stats-values");
      const minutes = steam.hunt?.playtime_minutes;
      const achieved = steam.hunt?.achievements_unlocked;
      const total = steam.hunt?.achievements_total;
      const display = [
        [t.hours, minutes == null ? t.unavailable : new Intl.NumberFormat(lang, { maximumFractionDigits: 1 }).format(minutes / 60) + " h"],
        [t.achievements, achieved == null || total == null ? t.unavailable : achieved + " / " + total],
      ];
      for (const [label, value] of display) {
        const stat = node("div", "hnt-hunt-profile-stats-value");
        stat.append(node("span", "", label), node("strong", "", value));
        stats.append(stat);
      }
      card.append(stats);
      now.overview.parentNode.insertBefore(card, now.overview);
    }).catch(() => {
      // Do not expose auth errors or noisy placeholders on public profiles.
      // A subsequent profile navigation will allow a new attempt.
    }).finally(() => { if (pendingUser === locationKey) pendingUser = null; });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", mount, { once: true });
  } else {
    mount();
  }
  const observer = new MutationObserver(mount);
  observer.observe(document.documentElement, { childList: true, subtree: true });
  window.addEventListener("popstate", () => { lastAttempt = ""; mount(); });
})();
