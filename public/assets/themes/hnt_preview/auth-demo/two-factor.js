(() => {
  const form = document.querySelector('[data-two-factor-form]');
  if (!form) return;

  const digitGroup = form.querySelector('[data-two-factor-digits]');
  const digits = Array.from(form.querySelectorAll('[data-two-factor-digit]'));
  const recoveryField = form.querySelector('[data-two-factor-recovery-field]');
  const recoveryInput = form.querySelector('[data-two-factor-recovery]');
  const hiddenCode = form.querySelector('[data-two-factor-code]');
  const toggle = form.querySelector('[data-two-factor-toggle]');
  const toggleLabel = form.querySelector('[data-two-factor-toggle-label]');
  const error = form.querySelector('[data-two-factor-error]');
  const submit = form.querySelector('[data-two-factor-submit]');
  const submitLabel = form.querySelector('[data-two-factor-submit-label]');

  if (!digitGroup || digits.length !== 6 || !hiddenCode || !toggle || !recoveryField || !recoveryInput || !submit) {
    return;
  }

  hiddenCode.name = 'code';
  let mode = form.dataset.initialMode === 'recovery' ? 'recovery' : 'authenticator';

  const setError = (message = '') => {
    if (error) error.textContent = message;
    digitGroup.classList.toggle('is-invalid', Boolean(message) && mode === 'authenticator');
    recoveryField.querySelector('div')?.classList.toggle('is-invalid', Boolean(message) && mode === 'recovery');
  };

  const authenticatorCode = () => digits.map((input) => input.value.replace(/\D/g, '')).join('');

  const syncHiddenCode = () => {
    hiddenCode.value = mode === 'recovery'
      ? recoveryInput.value.trim()
      : authenticatorCode();
  };

  const applyMode = ({ focus = true, clearError = true } = {}) => {
    const useRecovery = mode === 'recovery';

    digitGroup.hidden = useRecovery;
    recoveryField.hidden = !useRecovery;
    digits.forEach((input) => {
      input.required = !useRecovery;
    });
    recoveryInput.required = useRecovery;
    hiddenCode.value = '';

    if (clearError) setError();

    if (toggleLabel) {
      toggleLabel.textContent = useRecovery
        ? toggle.dataset.authenticatorLabel
        : toggle.dataset.recoveryLabel;
    }

    toggle.setAttribute('aria-expanded', String(useRecovery));

    if (focus) {
      (useRecovery ? recoveryInput : digits[0]).focus();
    }
  };

  const fillDigits = (value, startIndex = 0) => {
    const numbers = value.replace(/\D/g, '').slice(0, digits.length - startIndex).split('');
    if (!numbers.length) return false;

    numbers.forEach((number, offset) => {
      digits[startIndex + offset].value = number;
    });

    const nextIndex = Math.min(startIndex + numbers.length, digits.length - 1);
    digits[nextIndex].focus();
    digits[nextIndex].select();
    syncHiddenCode();
    setError();
    return true;
  };

  digits.forEach((input, index) => {
    input.addEventListener('input', () => {
      const rawValue = input.value;
      if (rawValue.length > 1 && fillDigits(rawValue, index)) return;

      input.value = rawValue.replace(/\D/g, '').slice(-1);
      syncHiddenCode();
      setError();

      if (input.value && index < digits.length - 1) {
        digits[index + 1].focus();
        digits[index + 1].select();
      }
    });

    input.addEventListener('keydown', (event) => {
      if (event.key === 'Backspace' && !input.value && index > 0) {
        digits[index - 1].focus();
        digits[index - 1].value = '';
        syncHiddenCode();
        return;
      }

      if (event.key === 'ArrowLeft' && index > 0) {
        event.preventDefault();
        digits[index - 1].focus();
      }

      if (event.key === 'ArrowRight' && index < digits.length - 1) {
        event.preventDefault();
        digits[index + 1].focus();
      }
    });
  });

  digitGroup.addEventListener('paste', (event) => {
    const pasted = event.clipboardData?.getData('text') || '';
    if (!/\d/.test(pasted)) return;
    event.preventDefault();
    fillDigits(pasted, 0);
  });

  recoveryInput.addEventListener('input', () => {
    syncHiddenCode();
    setError();
  });

  toggle.addEventListener('click', () => {
    mode = mode === 'authenticator' ? 'recovery' : 'authenticator';
    applyMode();
  });

  form.addEventListener('submit', (event) => {
    if (form.dataset.submitting === 'true') {
      event.preventDefault();
      return;
    }

    syncHiddenCode();

    if (mode === 'authenticator' && hiddenCode.value.length !== 6) {
      event.preventDefault();
      setError(form.dataset.codeIncomplete || '');
      const firstEmpty = digits.find((input) => !input.value) || digits[0];
      firstEmpty.focus();
      return;
    }

    if (mode === 'recovery' && hiddenCode.value === '') {
      event.preventDefault();
      setError(form.dataset.recoveryRequired || '');
      recoveryInput.focus();
      return;
    }

    form.dataset.submitting = 'true';
    submit.disabled = true;
    submit.classList.add('is-loading');

    if (submitLabel && form.dataset.loadingLabel) {
      submitLabel.textContent = form.dataset.loadingLabel;
    }
  });

  applyMode({ focus: false, clearError: false });
})();
