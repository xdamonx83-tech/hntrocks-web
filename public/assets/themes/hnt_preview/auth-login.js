(() => {
    const passwordToggle = document.querySelector('[data-hnt-password-toggle]');
    const passwordInput = document.getElementById('login-password');

    if (passwordToggle && passwordInput) {
        passwordToggle.addEventListener('click', () => {
            const isVisible = passwordInput.type === 'text';
            const nextVisible = !isVisible;
            const icon = passwordToggle.querySelector('i');

            passwordInput.type = nextVisible ? 'text' : 'password';
            passwordToggle.setAttribute('aria-pressed', String(nextVisible));
            passwordToggle.setAttribute(
                'aria-label',
                nextVisible ? passwordToggle.dataset.hideLabel : passwordToggle.dataset.showLabel,
            );

            if (icon) {
                icon.classList.toggle('ph-eye', !nextVisible);
                icon.classList.toggle('ph-eye-slash', nextVisible);
            }
        });
    }

    const form = document.querySelector('[data-hnt-login-form]');
    const submitButton = document.querySelector('[data-hnt-login-submit]');
    const submitLabel = document.querySelector('[data-hnt-login-submit-label]');

    if (form && submitButton) {
        form.addEventListener('submit', (event) => {
            if (!form.checkValidity()) {
                return;
            }

            if (form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }

            form.dataset.submitting = 'true';
            submitButton.disabled = true;
            submitButton.classList.add('is-loading');

            if (submitLabel && form.dataset.loadingLabel) {
                submitLabel.textContent = form.dataset.loadingLabel;
            }
        });
    }
})();
