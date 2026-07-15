(() => {
  const desiredMode = window.HNT_CUP_CREATE?.verificationMode;
  const form = document.querySelector('#cupCreateForm');
  if (!form || !desiredMode) return;

  let applied = false;

  const applyVerificationMode = () => {
    if (applied) return true;

    const select = form.querySelector('#cupVerificationMode');
    if (!select) return false;

    const optionExists = Array.from(select.options).some((option) => option.value === desiredMode);
    select.value = optionExists ? desiredMode : 'manual';
    select.dispatchEvent(new Event('change', { bubbles: true }));
    applied = true;

    return true;
  };

  if (applyVerificationMode()) return;

  const observer = new MutationObserver(() => {
    if (!applyVerificationMode()) return;
    observer.disconnect();
  });

  observer.observe(form, { childList: true, subtree: true });
  window.setTimeout(() => observer.disconnect(), 5000);
})();
