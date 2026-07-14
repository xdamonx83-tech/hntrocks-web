(() => {
  function notify(message) {
    if (typeof showToast === "function") {
      showToast(message);
      return;
    }
    const toast = document.getElementById("toast");
    if (!toast) return;
    toast.textContent = message;
    toast.classList.add("show");
    clearTimeout(notify.timer);
    notify.timer = setTimeout(() => toast.classList.remove("show"), 1700);
  }

  document.querySelectorAll("[data-auth-toast]").forEach((node) => {
    node.addEventListener("click", (event) => {
      if (node.getAttribute("href") === "#") event.preventDefault();
      notify(node.dataset.authToast);
    });
  });

  document.querySelectorAll("[data-password-toggle]").forEach((button) => {
    button.addEventListener("click", () => {
      const input = document.getElementById(button.dataset.passwordToggle);
      if (!input) return;
      const visible = input.type === "text";
      input.type = visible ? "password" : "text";
      button.innerHTML = `<svg><use href="#${visible ? "i-eye" : "i-eye-off"}"></use></svg>`;
    });
  });

  function setError(id, message) {
    const node = document.querySelector(`[data-error-for="${id}"]`);
    if (node) node.textContent = message || "";
  }

  const loginForm = document.getElementById("loginForm");
  if (loginForm) {
    loginForm.addEventListener("submit", (event) => {
      event.preventDefault();
      const identity = document.getElementById("loginIdentity").value.trim();
      const password = document.getElementById("loginPassword").value;
      setError("loginIdentity", identity ? "" : "Bitte Zugang eingeben.");
      setError("loginPassword", password ? "" : "Bitte Passwort eingeben.");
      if (!identity || !password) return;
      notify("Anmeldung geprüft – 2FA wird geöffnet");
      setTimeout(() => notify("2FA wird später angebunden"), 550);
    });
  }
})();
