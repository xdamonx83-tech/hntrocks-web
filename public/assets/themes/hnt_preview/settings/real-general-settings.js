(() => {
  const forms = new Map([
    ["general", document.getElementById("settingsGeneralForm")],
    ["notifications", document.getElementById("settingsNotificationsForm")],
  ].filter((entry) => entry[1]));
  const saveButton = document.getElementById("settingsSaveTop");
  const discardButton = document.getElementById("settingsDiscardTop");
  const saveFooter = document.querySelector("[data-settings-save-footer]");
  const saveFooterTitle = document.getElementById("settingsSaveFooterTitle");
  const saveFooterHint = document.getElementById("settingsSaveFooterHint");
  const saveBottom = document.getElementById("settingsSaveBottom");
  const discardBottom = document.getElementById("settingsDiscardBottom");
  const saveState = document.getElementById("settingsSaveState");
  const initialSaveState = saveState?.innerHTML || "";
  const notificationMaster = document.getElementById("notificationMaster");
  const notificationToggles = [...document.querySelectorAll(".notification-toggle")];

  if (!saveButton || !discardButton) return;

  const targetButton = (button, form) => {
    if (!button) return;

    if (form) {
      button.setAttribute("form", form.id);
    } else {
      button.removeAttribute("form");
    }
  };

  const syncActions = () => {
    const activePanel = document.querySelector("[data-settings-panel].active");
    const activeForm = forms.get(activePanel?.dataset.settingsPanel);

    saveButton.disabled = !activeForm;
    discardButton.disabled = !activeForm;
    targetButton(saveButton, activeForm);
    targetButton(discardButton, activeForm);
    targetButton(saveBottom, activeForm);
    targetButton(discardBottom, activeForm);

    if (saveFooter) saveFooter.hidden = !activeForm;
    if (saveFooterTitle && activeForm) saveFooterTitle.textContent = activeForm.dataset.saveTitle || "";
    if (saveFooterHint && activeForm) saveFooterHint.textContent = activeForm.dataset.saveHint || "";
  };

  const clearDirtyState = () => {
    if (!saveState) return;
    saveState.classList.remove("dirty");
    saveState.innerHTML = initialSaveState;
  };

  const syncNotificationMaster = () => {
    if (!notificationMaster || notificationToggles.length === 0) return;

    const enabledCount = notificationToggles.filter((toggle) => toggle.checked).length;
    notificationMaster.checked = enabledCount === notificationToggles.length;
    notificationMaster.indeterminate = enabledCount > 0 && enabledCount < notificationToggles.length;
  };

  if (notificationMaster) {
    notificationMaster.addEventListener("change", () => {
      notificationMaster.indeterminate = false;
      notificationToggles.forEach((toggle) => {
        toggle.checked = notificationMaster.checked;
      });
    });

    notificationToggles.forEach((toggle) => {
      toggle.addEventListener("change", syncNotificationMaster);
    });

    syncNotificationMaster();
  }

  document.querySelectorAll("[data-settings-tab]").forEach((tab) => {
    tab.addEventListener("click", () => requestAnimationFrame(syncActions));
  });

  forms.forEach((form, panelName) => {
    form.addEventListener("reset", () => {
      requestAnimationFrame(() => {
        clearDirtyState();
        if (panelName === "notifications") syncNotificationMaster();
      });
    });
  });

  syncActions();
})();
