/* HNT.ROCKS — minimal Steam profile-edit bridge.
 * Independent of the React entrypoint: keep the newer live sidebar/assets. */
(() => {
  "use strict";
  const WIDGET_ID = "hnt-steam-profile-bridge";
  const API = "/api/v1/me/game-accounts";
  const authKey = "hnt.next.auth.session";
  const translations = {
    de: {
      title: "Spielkonten verbinden",
      intro: "Verbinde deine Spielkonten, um ausschließlich deine Hunt: Showdown 1896-Statistiken im HNT-Profil anzuzeigen.",
      connected: "Verbunden", unavailable: "Noch nicht verfügbar", disconnected: "Nicht verbunden",
      preparing: "Steam-Verknüpfung wird vorbereitet.", wait: "Lade Spielkonten …",
      notAvailable: "Noch keine freigegebene Schnittstelle für Hunt-Statistiken.",
      hours: "Spielstunden", achievements: "Erfolge", last: "Zuletzt aktualisiert",
      connect: "Mit Steam verbinden", refresh: "Aktualisieren", disconnect: "Trennen",
      failed: "Aktion fehlgeschlagen.", error: "Verbindungen konnten nicht geladen werden.",
      linked: "Steam-Konto erfolgreich verbunden.", cancelled: "Steam-Verknüpfung fehlgeschlagen oder abgebrochen.",
      confirm: "Steam-Verknüpfung wirklich trennen?", unknown: "Nicht verfügbar",
    },
    en: {
      title: "Connect game accounts", intro: "Connect your accounts to display only Hunt: Showdown 1896 statistics in your HNT profile.",
      connected: "Connected", unavailable: "Not yet available", disconnected: "Not connected",
      preparing: "Steam linking is being prepared.", wait: "Loading game accounts …",
      notAvailable: "Hunt statistics API access is not yet available.",
      hours: "Hours played", achievements: "Achievements", last: "Last updated",
      connect: "Connect with Steam", refresh: "Refresh", disconnect: "Disconnect",
      failed: "Action failed.", error: "Could not load game accounts.",
      linked: "Steam account connected.", cancelled: "Steam connection cancelled or failed.",
      confirm: "Disconnect Steam account?", unknown: "Unavailable",
    },
    es: {
      title: "Conectar cuentas de juego", intro: "Conecta tus cuentas para mostrar únicamente las estadísticas de Hunt: Showdown 1896 en HNT.",
      connected: "Conectado", unavailable: "Aún no disponible", disconnected: "Sin conectar",
      preparing: "Se está preparando la conexión con Steam.", wait: "Cargando cuentas …",
      notAvailable: "La API de estadísticas de Hunt aún no está disponible.",
      hours: "Horas jugadas", achievements: "Logros", last: "Última actualización",
      connect: "Conectar con Steam", refresh: "Actualizar", disconnect: "Desconectar",
      failed: "Acción fallida.", error: "No se pudieron cargar las cuentas.",
      linked: "Cuenta de Steam conectada.", cancelled: "La conexión de Steam falló o se canceló.",
      confirm: "¿Desconectar Steam?", unknown: "No disponible",
    },
    ru: {
      title: "Подключить игровые аккаунты", intro: "Подключите аккаунты, чтобы показывать только статистику Hunt: Showdown 1896 в профиле HNT.",
      connected: "Подключено", unavailable: "Пока недоступно", disconnected: "Не подключено",
      preparing: "Подключение Steam готовится.", wait: "Загрузка аккаунтов …",
      notAvailable: "API статистики Hunt пока недоступен.",
      hours: "Часов в игре", achievements: "Достижения", last: "Последнее обновление",
      connect: "Подключить Steam", refresh: "Обновить", disconnect: "Отключить",
      failed: "Ошибка операции.", error: "Не удалось загрузить аккаунты.",
      linked: "Steam подключён.", cancelled: "Подключение Steam не удалось или отменено.",
      confirm: "Отключить Steam?", unknown: "Недоступно",
    },
  };
  const supported = ["de", "en", "es", "ru"];
  function language() {
    const saved = window.localStorage.getItem("i18nextLng") || "";
    const value = (document.documentElement.lang || saved || navigator.language || "de").toLowerCase().split(/[-_]/)[0];
    return supported.includes(value) ? value : "de";
  }
  function session() {
    for (const storage of [window.localStorage, window.sessionStorage]) {
      let value = null;
      try { value = JSON.parse(storage.getItem(authKey) || "null"); } catch { /* ignore */ }
      if (!value || typeof value.access_token !== "string" || typeof value.token_type !== "string") continue;
      if (value.expires_at && Date.parse(value.expires_at) <= Date.now()) continue;
      return value;
    }
    return null;
  }
  async function request(path, method = "GET") {
    const current = session();
    if (!current) throw new Error("session");
    const res = await fetch(path, {
      method,
      credentials: "same-origin",
      cache: "no-store",
      headers: {
        Accept: "application/json",
        "Authorization": current.token_type + " " + current.access_token,
      },
    });
    if (!res.ok) throw new Error("http:" + res.status);
    return res.json();
  }
  function elem(tag, className, text) {
    const n = document.createElement(tag);
    if (className) n.className = className;
    if (text !== undefined) n.textContent = String(text);
    return n;
  }
  function button(text, action) {
    const b = elem("button", "hnt-steam-bridge-button", text);
    b.type = "button";
    b.addEventListener("click", action);
    return b;
  }
  function create() {
    const target = document.querySelector(".profile-edit-status-card");
    if (!target || document.getElementById(WIDGET_ID) || !session()) return;

    const t = translations[language()];
    const root = elem("section", "profile-edit-card profile-edit-section-card hnt-steam-bridge");
    root.id = WIDGET_ID;
    const section = elem("div", "profile-edit-section");
    const copy = elem("div", "profile-edit-section-copy");
    copy.append(elem("h2", "", t.title), elem("p", "", t.intro));
    const area = elem("div", "hnt-steam-bridge-list");
    section.append(copy, area);
    root.append(section);
    target.parentNode.insertBefore(root, target);

    let busy = false;
    function notice(value) {
      const n = elem("div", "hnt-steam-bridge-notice", value);
      n.setAttribute("role", "status");
      area.prepend(n);
    }
    async function load() {
      if (!root.isConnected) return;
      area.replaceChildren(elem("p", "hnt-steam-bridge-hint", t.wait));
      try {
        const response = await request(API);
        if (!root.isConnected) return;
        paint(response);
      } catch {
        if (!root.isConnected) return;
        area.replaceChildren(elem("p", "hnt-steam-bridge-hint", t.error));
        area.append(button("↻", () => void load()));
      }
    }
    async function perform(fn) {
      if (busy) return;
      busy = true;
      try { await fn(); } catch {
        if (root.isConnected) notice(t.failed);
      } finally { busy = false; }
    }
    function paint(response) {
      area.replaceChildren();
      const connected = Array.isArray(response.data) ? response.data.find(x => x.provider === "steam") : null;
      for (const id of ["steam", "xbox", "playstation"]) {
        const isSteam = id === "steam";
        const label = isSteam ? "Steam" : id === "xbox" ? "Xbox" : "PlayStation";
        const active = isSteam && response.providers?.steam?.enabled === true;
        const item = elem("div", "hnt-steam-bridge-row");
        const head = elem("div", "hnt-steam-bridge-head");
        const icon = elem("i", isSteam ? "ph-fill ph-steam-logo" : "ph ph-game-controller");
        icon.setAttribute("aria-hidden", "true");
        const texts = elem("div", "hnt-steam-bridge-label");
        texts.append(elem("strong", "", label), elem("small", "", isSteam && connected ? t.connected : active ? t.disconnected : t.unavailable));
        head.append(icon, texts);
        if (isSteam && connected) {
          head.append(button(t.refresh, () => void perform(async () => {
            await request(API + "/steam/sync", "POST"); await load();
          })));
          head.append(button(t.disconnect, () => {
            if (!window.confirm(t.confirm)) return;
            void perform(async () => {
              await request(API + "/steam", "DELETE"); await load();
            });
          }));
        } else if (isSteam && active) {
          head.append(button(t.connect, () => void perform(async () => {
            const result = await request(API + "/steam/start", "POST");
            const url = new URL(result.authorize_url);
            if (url.protocol !== "https:" || url.hostname !== "steamcommunity.com" || url.pathname !== "/openid/login") {
              throw new Error("Invalid Steam endpoint");
            }
            window.location.assign(url.href);
          })));
        }
        item.append(head);
        if (isSteam && connected) {
          const stats = elem("div", "hnt-steam-bridge-stats");
          const minutes = connected.hunt?.playtime_minutes;
          const total = connected.hunt?.achievements_total;
          const completed = connected.hunt?.achievements_unlocked;
          const hours = minutes == null ? t.unknown : new Intl.NumberFormat(language(), { maximumFractionDigits: 1 }).format(minutes / 60) + " h";
          const ach = total == null || completed == null ? t.unknown : completed + " / " + total;
          for (const [name, value] of [[t.hours, hours], [t.achievements, ach]]) {
            const box = elem("div", "hnt-steam-bridge-stat");
            box.append(elem("span", "", name), elem("strong", "", value));
            stats.append(box);
          }
          item.append(stats);
          let date = t.unknown;
          if (connected.last_synced_at) {
            const d = new Date(connected.last_synced_at);
            if (!Number.isNaN(d.getTime())) date = new Intl.DateTimeFormat(language(), { dateStyle: "medium", timeStyle: "short" }).format(d);
          }
          item.append(elem("small", "hnt-steam-bridge-hint", t.last + ": " + date));
        } else {
          item.append(elem("p", "hnt-steam-bridge-hint",
            isSteam ? t.preparing : t.notAvailable));
        }
        area.append(item);
      }
      const url = new URL(window.location.href);
      const result = url.searchParams.get("steam_connection");
      if (result) {
        notice(result === "connected" ? t.linked : t.cancelled);
        url.searchParams.delete("steam_connection");
        window.history.replaceState(window.history.state, "", url.pathname + url.search + url.hash);
      }
    }
    void load();
  }
  function mount() {
    if (!document.getElementById(WIDGET_ID)) create();
  }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", mount, { once: true });
  else mount();
  const observer = new MutationObserver(mount);
  observer.observe(document.documentElement, { childList: true, subtree: true });
})();
