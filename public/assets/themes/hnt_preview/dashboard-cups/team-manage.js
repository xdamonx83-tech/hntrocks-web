(() => {
  const ensureStylesheet = (href, marker) => {
    if (document.querySelector(`link[${marker}]`)) return;
    const style = document.createElement("link");
    style.rel = "stylesheet";
    style.href = href;
    style.setAttribute(marker, "1");
    document.head.appendChild(style);
  };

  ensureStylesheet("/assets/themes/hnt_preview/dashboard-feed/real-feed.css?v=20260714-1", "data-team-manage-real-feed");
  ensureStylesheet("/assets/themes/hnt_preview/dashboard-feed/real-feed-polish.css?v=20260714-1", "data-team-manage-real-feed-polish");
  ensureStylesheet("/assets/themes/hnt_preview/dashboard-cups/cup-community-access.css?v=20260714-1", "data-team-manage-community-access");
  ensureStylesheet("/assets/themes/hnt_preview/dashboard-cups/cups-header-dropdown-reference.css?v=20260715-1", "data-team-manage-header-dropdown-reference");

  const shell = document.querySelector(".team-manage-page-shell");
  if (!shell) return;

  window.HNT_DASHBOARD_HEADER_ENDPOINT = "/feed";

  const loadScript = (src, marker, onload) => {
    const existing = document.querySelector(`script[${marker}]`);
    if (existing) {
      onload?.();
      return;
    }

    const script = document.createElement("script");
    script.src = src;
    script.async = false;
    script.setAttribute(marker, "1");
    if (onload) script.addEventListener("load", onload, { once: true });
    document.body.appendChild(script);
  };

  loadScript(
    "/assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js?v=20260714-1",
    "data-team-manage-header-runtime",
    () => loadScript(
      "/assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js?v=20260714-1",
      "data-team-manage-header-live"
    )
  );

  const cupsNav = shell.querySelector(".nav-cups");
  const cupsTrigger = cupsNav?.querySelector(":scope > .main-nav-trigger");
  cupsNav?.classList.add("is-current");
  cupsTrigger?.classList.add("is-current");
})();

(() => {
  const stage = document.querySelector(".team-manage-stage");
  const scroll = document.getElementById("teamManageScroll");
  const center = document.getElementById("teamManageCenter");
  const left = document.getElementById("teamFixedLeft");
  const right = document.getElementById("teamFixedRight");
  const stickyHead = document.querySelector(".team-center-head");

  let frame = 0;

  function updateFixedColumns() {
    frame = 0;
    if (!stage || !scroll || !center || !left || !right) return;

    if (window.matchMedia("(max-width: 899px)").matches) {
      left.style.removeProperty("top");
      right.style.removeProperty("top");
      stage.classList.remove("team-columns-docked");
      scroll.classList.remove("team-content-docked");
      stickyHead?.classList.remove("is-stuck");
      return;
    }

    const styles = getComputedStyle(stage);
    const gap = Number.parseFloat(styles.getPropertyValue("--team-sticky-gap")) || 12;
    const stageRect = stage.getBoundingClientRect();
    const centerRect = center.getBoundingClientRect();
    const naturalTop = centerRect.top - stageRect.top;
    const top = Math.max(gap, naturalTop);
    const docked = naturalTop <= gap + 1;

    left.style.top = `${top}px`;
    right.style.top = `${top}px`;
    stage.classList.toggle("team-columns-docked", docked);
    scroll.classList.toggle("team-content-docked", docked);

    if (stickyHead) {
      const headRect = stickyHead.getBoundingClientRect();
      const scrollRect = scroll.getBoundingClientRect();
      stickyHead.classList.toggle(
        "is-stuck",
        scroll.scrollTop > 0 && headRect.top <= scrollRect.top + gap + 1
      );
    }
  }

  function requestUpdate() {
    if (frame) return;
    frame = window.requestAnimationFrame(updateFixedColumns);
  }

  scroll?.addEventListener("scroll", requestUpdate, { passive: true });
  window.addEventListener("resize", requestUpdate);
  window.addEventListener("load", requestUpdate);
  requestUpdate();

  const tabs = [...document.querySelectorAll("[data-team-tab]")];
  const panels = [...document.querySelectorAll("[data-team-panel]")];
  const title = document.getElementById("teamPanelTitle");

  function activateTab(name) {
    tabs.forEach((tab) => {
      const active = tab.dataset.teamTab === name;
      tab.classList.toggle("active", active);
      tab.setAttribute("aria-selected", String(active));
    });

    panels.forEach((panel) => {
      const active = panel.dataset.teamPanel === name;
      panel.classList.toggle("active", active);
      panel.hidden = !active;
    });

    const activeTab = tabs.find((tab) => tab.dataset.teamTab === name);
    if (title && activeTab) {
      title.textContent = activeTab.dataset.title || activeTab.textContent.trim();
    }

    if (scroll && window.matchMedia("(max-width: 899px)").matches) {
      center?.scrollIntoView({ block: "start", behavior: "smooth" });
    }

    requestUpdate();
  }

  tabs.forEach((tab) => {
    tab.addEventListener("click", () => activateTab(tab.dataset.teamTab));
  });

  document.querySelectorAll("[data-team-tab-shortcut]").forEach((button) => {
    button.addEventListener("click", () => activateTab(button.dataset.teamTabShortcut));
  });

  document.getElementById("teamNameForm")?.addEventListener("submit", (event) => {
    event.preventDefault();
    if (typeof showToast === "function") showToast("Demo: Teamname gespeichert");
  });

  document.querySelector("[data-copy-invite]")?.addEventListener("click", async () => {
    const input = document.getElementById("teamInviteLink");
    if (!input) return;

    try {
      await navigator.clipboard.writeText(input.value);
    } catch {
      input.select();
      document.execCommand("copy");
    }

    if (typeof showToast === "function") showToast("Einladungslink kopiert");
  });

  document.getElementById("teamChatForm")?.addEventListener("submit", (event) => {
    event.preventDefault();
    const input = document.getElementById("teamChatInput");
    const list = document.getElementById("teamChatList");
    const value = input?.value.trim();
    if (!value || !list) return;

    const message = document.createElement("article");
    message.className = "own";

    const body = document.createElement("div");
    const author = document.createElement("strong");
    const copy = document.createElement("p");
    const time = document.createElement("time");

    author.textContent = "Du";
    copy.textContent = value;
    time.textContent = "jetzt";

    body.append(author, copy, time);
    message.append(body);
    list.append(message);

    input.value = "";
    list.scrollTop = list.scrollHeight;
  });

  const confirm = document.getElementById("teamLeaveConfirm");

  document.querySelector("[data-confirm-team-leave]")?.addEventListener("click", () => {
    if (!confirm) return;
    confirm.hidden = false;
    document.body.classList.add("cup-team-modal-open");
  });

  document.querySelectorAll("[data-close-confirm]").forEach((button) => {
    button.addEventListener("click", () => {
      if (!confirm) return;
      confirm.hidden = true;
      document.body.classList.remove("cup-team-modal-open");
    });
  });

  document.querySelector("[data-confirm-leave]")?.addEventListener("click", () => {
    if (confirm) confirm.hidden = true;
    document.body.classList.remove("cup-team-modal-open");
    if (typeof showToast === "function") {
      showToast("Demo: Team wurde nicht zurückgezogen");
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && confirm && !confirm.hidden) {
      confirm.hidden = true;
      document.body.classList.remove("cup-team-modal-open");
    }
  });

  activateTab("overview");
})();