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

  const submissionModal = document.getElementById("teamSubmissionModal");
  const submissionForm = document.getElementById("teamSubmissionModalForm");
  const submissionFile = submissionForm?.querySelector("[data-team-submission-file]");
  const submissionDrop = submissionForm?.querySelector("[data-team-submission-drop]");
  const submissionEmpty = submissionForm?.querySelector("[data-team-submission-empty]");
  const submissionPreview = submissionForm?.querySelector("[data-team-submission-preview]");
  const submissionPreviewImage = submissionForm?.querySelector("[data-team-submission-preview-image]");
  const submissionFileName = submissionForm?.querySelector("[data-team-submission-file-name]");
  const submissionFileSize = submissionForm?.querySelector("[data-team-submission-file-size]");
  const submissionError = submissionForm?.querySelector("[data-team-submission-error]");
  const submissionProgress = submissionForm?.querySelector("[data-team-submission-progress]");
  const submissionProgressBar = submissionForm?.querySelector("[data-team-submission-progress-bar]");
  const submissionProgressText = submissionForm?.querySelector("[data-team-submission-progress-text]");
  const submissionButton = submissionForm?.querySelector("[data-team-submission-submit]");
  const submissionCloseButtons = submissionModal?.querySelectorAll("[data-close-team-submission-modal]") || [];
  let submissionPreviewUrl = null;

  const formatBytes = (bytes) => {
    if (!Number.isFinite(bytes) || bytes <= 0) return "0 KB";
    const units = ["B", "KB", "MB", "GB"];
    const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    const value = bytes / (1024 ** index);
    return `${value >= 10 || index === 0 ? value.toFixed(0) : value.toFixed(1)} ${units[index]}`;
  };

  const clearSubmissionError = () => {
    if (!submissionError) return;
    submissionError.hidden = true;
    submissionError.textContent = "";
  };

  const showSubmissionError = (message) => {
    if (!submissionError) return;
    submissionError.textContent = message || submissionForm?.dataset.error || "Upload fehlgeschlagen";
    submissionError.hidden = false;
  };

  const setSubmissionBusy = (busy) => {
    if (!submissionForm) return;
    submissionForm.setAttribute("aria-busy", String(busy));
    submissionButton?.toggleAttribute("disabled", busy);
    submissionCloseButtons.forEach((button) => button.toggleAttribute("disabled", busy));
  };

  const setSubmissionProgress = (percent, text, analyzing = false) => {
    if (!submissionProgress || !submissionProgressBar || !submissionProgressText) return;
    submissionProgress.hidden = false;
    submissionProgress.classList.toggle("is-analyzing", analyzing);
    submissionProgressBar.style.width = `${Math.max(0, Math.min(100, percent))}%`;
    submissionProgressText.textContent = text || "";
  };

  const resetSubmissionModal = () => {
    if (!submissionForm) return;
    submissionForm.reset();
    clearSubmissionError();
    setSubmissionBusy(false);
    submissionProgress?.classList.remove("is-analyzing");
    if (submissionProgress) submissionProgress.hidden = true;
    if (submissionProgressBar) submissionProgressBar.style.width = "0%";
    if (submissionProgressText) submissionProgressText.textContent = "";
    if (submissionPreviewUrl) URL.revokeObjectURL(submissionPreviewUrl);
    submissionPreviewUrl = null;
    if (submissionPreviewImage) submissionPreviewImage.removeAttribute("src");
    if (submissionPreview) submissionPreview.hidden = true;
    if (submissionEmpty) submissionEmpty.hidden = false;
    submissionDrop?.classList.remove("is-dragging");
  };

  const openSubmissionModal = () => {
    if (!submissionModal) return;
    submissionModal.hidden = false;
    document.body.classList.add("team-submission-modal-open");
    submissionModal.querySelector(".team-submission-modal__close")?.focus();
  };

  const closeSubmissionModal = () => {
    if (!submissionModal || submissionForm?.getAttribute("aria-busy") === "true") return;
    submissionModal.hidden = true;
    document.body.classList.remove("team-submission-modal-open");
    resetSubmissionModal();
  };

  const validateSubmissionFile = (file) => {
    if (!file) {
      showSubmissionError(submissionForm?.dataset.fileRequired);
      return false;
    }

    if (!file.type.startsWith("image/")) {
      showSubmissionError(submissionForm?.dataset.fileInvalid);
      return false;
    }

    const maxBytes = Number.parseInt(submissionForm?.dataset.maxBytes || "0", 10);
    if (maxBytes > 0 && file.size > maxBytes) {
      showSubmissionError(submissionForm?.dataset.fileTooLarge);
      return false;
    }

    return true;
  };

  const showSubmissionPreview = (file) => {
    clearSubmissionError();
    if (!validateSubmissionFile(file)) {
      if (submissionFile) submissionFile.value = "";
      return;
    }

    if (submissionPreviewUrl) URL.revokeObjectURL(submissionPreviewUrl);
    submissionPreviewUrl = URL.createObjectURL(file);
    if (submissionPreviewImage) submissionPreviewImage.src = submissionPreviewUrl;
    if (submissionFileName) submissionFileName.textContent = file.name;
    if (submissionFileSize) submissionFileSize.textContent = formatBytes(file.size);
    if (submissionEmpty) submissionEmpty.hidden = true;
    if (submissionPreview) submissionPreview.hidden = false;
  };

  document.querySelectorAll("[data-open-team-submission-modal]").forEach((button) => {
    button.addEventListener("click", openSubmissionModal);
  });

  submissionCloseButtons.forEach((button) => button.addEventListener("click", closeSubmissionModal));

  submissionFile?.addEventListener("change", () => {
    showSubmissionPreview(submissionFile.files?.[0]);
  });

  ["dragenter", "dragover"].forEach((eventName) => {
    submissionDrop?.addEventListener(eventName, (event) => {
      event.preventDefault();
      submissionDrop.classList.add("is-dragging");
    });
  });

  ["dragleave", "drop"].forEach((eventName) => {
    submissionDrop?.addEventListener(eventName, (event) => {
      event.preventDefault();
      submissionDrop.classList.remove("is-dragging");
    });
  });

  submissionDrop?.addEventListener("drop", (event) => {
    const file = event.dataTransfer?.files?.[0];
    if (!file || !submissionFile) return;

    const transfer = new DataTransfer();
    transfer.items.add(file);
    submissionFile.files = transfer.files;
    showSubmissionPreview(file);
  });

  submissionForm?.addEventListener("submit", (event) => {
    event.preventDefault();
    if (submissionForm.getAttribute("aria-busy") === "true") return;

    const file = submissionFile?.files?.[0];
    clearSubmissionError();
    if (!validateSubmissionFile(file)) return;

    const formData = new FormData(submissionForm);
    setSubmissionBusy(true);
    setSubmissionProgress(4, submissionForm.dataset.uploading);

    const request = new XMLHttpRequest();
    request.open("POST", submissionForm.action, true);
    request.setRequestHeader("Accept", "application/json");
    request.setRequestHeader("X-Requested-With", "XMLHttpRequest");

    request.upload.addEventListener("progress", (progressEvent) => {
      if (!progressEvent.lengthComputable) return;
      const uploadPercent = Math.round((progressEvent.loaded / progressEvent.total) * 62) + 4;
      setSubmissionProgress(uploadPercent, submissionForm.dataset.uploading);
    });

    request.upload.addEventListener("load", () => {
      setSubmissionProgress(72, submissionForm.dataset.analyzing, true);
    });

    request.addEventListener("load", () => {
      let response = null;
      try {
        response = JSON.parse(request.responseText || "{}");
      } catch {
        response = null;
      }

      if (request.status >= 200 && request.status < 300 && response?.ok) {
        setSubmissionProgress(100, response.message || submissionForm.dataset.success, false);
        showToast(response.message || submissionForm.dataset.success);
        window.sessionStorage.setItem("teamManageInitialTab", "submissions");
        window.setTimeout(() => window.location.reload(), 900);
        return;
      }

      const validationMessage = response?.errors
        ? Object.values(response.errors).flat().find(Boolean)
        : null;
      showSubmissionError(validationMessage || response?.message || submissionForm.dataset.error);
      if (submissionProgress) submissionProgress.hidden = true;
      setSubmissionBusy(false);
    });

    request.addEventListener("error", () => {
      showSubmissionError(submissionForm.dataset.error);
      if (submissionProgress) submissionProgress.hidden = true;
      setSubmissionBusy(false);
    });

    request.send(formData);
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && submissionModal && !submissionModal.hidden) {
      closeSubmissionModal();
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

  const storedInitialTab = window.sessionStorage.getItem("teamManageInitialTab");
  if (storedInitialTab) window.sessionStorage.removeItem("teamManageInitialTab");
  activateTab(tabs.some((tab) => tab.dataset.teamTab === storedInitialTab) ? storedInitialTab : "overview");
})();
