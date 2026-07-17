(() => {
  const form = document.getElementById("settingsGeneralForm");
  const saveButton = document.getElementById("settingsSaveTop");
  const discardButton = document.getElementById("settingsDiscardTop");
  const saveFooter = document.querySelector("[data-settings-save-footer]");
  const saveState = document.getElementById("settingsSaveState");
  const initialSaveState = saveState?.innerHTML || "";

  if (!form || !saveButton || !discardButton) return;

  const syncActions = () => {
    const activePanel = document.querySelector("[data-settings-panel].active");
    const generalIsActive = activePanel?.dataset.settingsPanel === "general";

    saveButton.disabled = !generalIsActive;
    discardButton.disabled = !generalIsActive;

    if (saveFooter) saveFooter.hidden = !generalIsActive;
  };

  document.querySelectorAll("[data-settings-tab]").forEach((tab) => {
    tab.addEventListener("click", () => requestAnimationFrame(syncActions));
  });

  form.addEventListener("reset", () => {
    requestAnimationFrame(() => {
      if (!saveState) return;
      saveState.classList.remove("dirty");
      saveState.innerHTML = initialSaveState;
    });
  });

  syncActions();
})();
