(() => {
  const initSecurityHistory = () => {
    const list = document.getElementById("securityEventsList");
    const loadMoreButton = document.getElementById("securityEventsLoadMore");
    const loadError = document.getElementById("securityEventsLoadError");

    if (!list || !loadMoreButton) return;

    loadMoreButton.addEventListener("click", async () => {
      const cursor = loadMoreButton.dataset.nextCursor;
      if (!cursor || loadMoreButton.disabled) return;

      loadMoreButton.disabled = true;
      loadMoreButton.textContent = loadMoreButton.dataset.labelLoading;
      if (loadError) loadError.hidden = true;

      try {
        const url = new URL(loadMoreButton.dataset.url, window.location.origin);
        url.searchParams.set("before", cursor);

        const response = await fetch(url, {
          credentials: "same-origin",
          headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
          },
        });

        if (!response.ok) throw new Error("Security events could not be loaded.");

        const payload = await response.json();

        if (payload.html) {
          list.insertAdjacentHTML("beforeend", payload.html);
          list.scrollTo({ top: list.scrollHeight, behavior: "smooth" });
        }

        loadMoreButton.dataset.nextCursor = payload.next_cursor || "";
        loadMoreButton.hidden = !payload.has_more || !payload.next_cursor;
      } catch (error) {
        if (loadError) loadError.hidden = false;
      } finally {
        loadMoreButton.disabled = false;
        loadMoreButton.textContent = loadMoreButton.dataset.labelMore;
      }
    });
  };

  const initOtherSessionsForm = () => {
    const form = document.querySelector("[data-security-logout-form]");
    if (!form) return;

    form.addEventListener("submit", (event) => {
      if (!window.confirm(form.dataset.confirm)) {
        event.preventDefault();
        return;
      }

      const button = form.querySelector("button[type='submit']");
      if (!button) return;

      button.disabled = true;
      button.textContent = form.dataset.labelLoading;
    });
  };

  const initDeletionModal = () => {
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
  };

  initSecurityHistory();
  initOtherSessionsForm();
  initDeletionModal();
})();
