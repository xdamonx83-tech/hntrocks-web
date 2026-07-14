(() => {
  document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      const input = document.getElementById(button.dataset.passwordToggle);
      const use = button.querySelector('use');

      if (!input) return;

      const willShow = input.type === 'password';
      input.type = willShow ? 'text' : 'password';
      button.setAttribute('aria-pressed', String(willShow));
      button.setAttribute('aria-label', willShow ? button.dataset.hideLabel : button.dataset.showLabel);

      if (use) {
        use.setAttribute('href', willShow ? '#i-eye-off' : '#i-eye');
      }
    });
  });

  const form = document.querySelector('[data-auth-login-form]');
  const submit = document.querySelector('[data-auth-submit]');
  const submitLabel = document.querySelector('[data-auth-submit-label]');

  if (!form || !submit) return;

  form.addEventListener('submit', (event) => {
    if (!form.checkValidity()) return;

    if (form.dataset.submitting === 'true') {
      event.preventDefault();
      return;
    }

    form.dataset.submitting = 'true';
    submit.disabled = true;
    submit.classList.add('is-loading');

    if (submitLabel && form.dataset.loadingLabel) {
      submitLabel.textContent = form.dataset.loadingLabel;
    }
  });
})();
