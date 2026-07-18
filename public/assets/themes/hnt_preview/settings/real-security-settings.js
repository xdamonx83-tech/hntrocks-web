(() => {
  const modal = document.getElementById("settingsDeleteModal");
  const openButton = document.getElementById("openDeleteAccount");
  const closeButton = document.getElementById("closeDeleteAccount");
  const cancelButton = document.getElementById("cancelDeleteAccount");
  const confirmation = document.getElementById("deleteAccountConfirmation");
  const confirmButton = document.getElementById("confirmDeleteAccount");

  if (!modal || !confirmation || !confirmButton) return;

  const syncConfirmation = () => {
    confirmButton.disabled = confirmation.value.trim() !== modal.dataset.username;
  };

  const openModal = () => {
    modal.hidden = false;
    document.body.classList.add("settings-modal-open");
    syncConfirmation();
    requestAnimationFrame(() => confirmation.focus());
  };

  const closeModal = () => {
    modal.hidden = true;
    document.body.classList.remove("settings-modal-open");
  };

  openButton?.addEventListener("click", openModal);
  closeButton?.addEventListener("click", closeModal);
  cancelButton?.addEventListener("click", closeModal);
  confirmation.addEventListener("input", syncConfirmation);

  modal.addEventListener("click", (event) => {
    if (event.target === modal) closeModal();
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && !modal.hidden) closeModal();
  });

  syncConfirmation();

  if (modal.dataset.openOnLoad === "true") {
    openModal();
  }
})();
