(() => {
  const modals = [...document.querySelectorAll('.cup-submission-moderation')];
  if (!modals.length) return;

  if (!document.querySelector('link[data-cup-submission-moderation-style]')) {
    const style = document.createElement('link');
    style.rel = 'stylesheet';
    style.href = '/assets/themes/hnt_preview/dashboard-cups/cup-submission-moderation.css?v=20260717-1';
    style.setAttribute('data-cup-submission-moderation-style', '1');
    document.head.appendChild(style);
  }

  let activeModal = null;

  const closeModal = () => {
    if (!activeModal) return;
    activeModal.hidden = true;
    activeModal = null;
    document.body.classList.remove('cup-submission-moderation-open');
  };

  document.querySelectorAll('[data-open-cup-submission-moderation]').forEach((button) => {
    button.addEventListener('click', () => {
      const modal = document.getElementById(`cupSubmissionModeration${button.dataset.openCupSubmissionModeration}`);
      if (!modal) return;
      if (activeModal && activeModal !== modal) activeModal.hidden = true;
      activeModal = modal;
      modal.hidden = false;
      document.body.classList.add('cup-submission-moderation-open');
      window.setTimeout(() => modal.querySelector('input, textarea, button')?.focus(), 30);
    });
  });

  modals.forEach((modal) => {
    modal.querySelectorAll('[data-close-cup-submission-moderation]').forEach((button) => {
      button.addEventListener('click', closeModal);
    });
  });

  document.querySelectorAll('[data-sync-review-note]').forEach((form) => {
    form.addEventListener('submit', () => {
      const source = document.querySelector(`[data-cup-submission-review-note="${form.dataset.syncReviewNote}"]`);
      const target = form.querySelector('input[name="review_note"]');
      if (source && target) target.value = source.value;
    });
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && activeModal) closeModal();
  });
})();
