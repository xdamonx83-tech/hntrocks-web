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
      stickyHead.classList.toggle("is-stuck", scroll.scrollTop > 0 && headRect.top <= scrollRect.top + gap + 1);
    }
  }

  function requestUpdate() {
    if (frame) return;
    frame = requestAnimationFrame(updateFixedColumns);
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
    if (title && activeTab) title.textContent = activeTab.dataset.title || activeTab.textContent.trim();
  }

  tabs.forEach((tab) => tab.addEventListener("click", () => activateTab(tab.dataset.teamTab)));
  document.querySelectorAll("[data-team-tab-shortcut]").forEach((button) => {
    button.addEventListener("click", () => activateTab(button.dataset.teamTabShortcut));
  });

  document.getElementById("teamNameForm")?.addEventListener("submit", (event) => {
    event.preventDefault();
    showToast("Teamname gespeichert");
  });

  const qrTarget = document.getElementById("teamInviteQr");
  const qrValue = qrTarget?.dataset.qrValue?.trim();
  if (qrTarget && qrValue && window.QRCode) {
    qrTarget.replaceChildren();
    new QRCode(qrTarget, {
      text: qrValue,
      width: 136,
      height: 136,
      colorDark: "#2b2c29",
      colorLight: "#f3f3f0",
      correctLevel: QRCode.CorrectLevel.M,
    });
  }

  document.querySelector("[data-copy-invite]")?.addEventListener("click", async (event) => {
    const button = event.currentTarget;
    const input = document.getElementById("teamInviteLink");
    if (!input) return;

    try {
      await navigator.clipboard.writeText(input.value);
      showToast(button.dataset.copySuccess || "Einladungslink kopiert");
    } catch {
      input.select();
      document.execCommand("copy");
      showToast(button.dataset.copySuccess || "Einladungslink kopiert");
    }
  });

  document.getElementById("teamChatForm")?.addEventListener("submit", (event) => {
    event.preventDefault();
    const input = document.getElementById("teamChatInput");
    const list = document.getElementById("teamChatList");
    const value = input.value.trim();
    if (!value || !list) return;

    const message = document.createElement("article");
    message.className = "own";
    message.innerHTML = `<div><strong>Du</strong><p>${value.replace(/[<>]/g, "")}</p><time>jetzt</time></div>`;
    list.appendChild(message);
    input.value = "";
    list.scrollTop = list.scrollHeight;
  });

  const confirm = document.getElementById("teamLeaveConfirm");
  document.querySelector("[data-confirm-team-leave]")?.addEventListener("click", () => {
    confirm.hidden = false;
  });
  document.querySelectorAll("[data-close-confirm]").forEach((button) => {
    button.addEventListener("click", () => confirm.hidden = true);
  });
  document.querySelector("[data-confirm-leave]")?.addEventListener("click", () => {
    confirm.hidden = true;
    showToast("Demo: Team wurde nicht wirklich zurückgezogen");
  });

  activateTab("overview");
})();