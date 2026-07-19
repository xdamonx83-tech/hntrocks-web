(() => {
  'use strict';

  const filterForm = document.querySelector('[data-my-guides-filter]');
  const sortSelect = document.querySelector('[data-my-guides-sort]');
  const deleteDialog = document.querySelector('[data-guide-delete-dialog]');
  const deleteForm = deleteDialog?.querySelector('[data-guide-delete-form]');
  const deleteCopy = deleteDialog?.querySelector('[data-guide-delete-copy]');

  sortSelect?.addEventListener('change', () => filterForm?.submit());

  document.querySelectorAll('[data-guide-delete]').forEach((button) => {
    button.addEventListener('click', () => {
      if (!deleteDialog || !deleteForm || !deleteCopy) return;

      deleteForm.action = button.dataset.guideDeleteUrl || '';
      deleteCopy.textContent = button.dataset.guideDeleteMessage || '';

      if (typeof deleteDialog.showModal === 'function') {
        deleteDialog.showModal();
      }
    });
  });

  deleteDialog?.querySelectorAll('[data-guide-delete-close]').forEach((button) => {
    button.addEventListener('click', () => deleteDialog.close());
  });

  deleteDialog?.addEventListener('click', (event) => {
    if (event.target === deleteDialog) deleteDialog.close();
  });

  deleteDialog?.addEventListener('cancel', (event) => {
    event.preventDefault();
    deleteDialog.close();
  });

  deleteForm?.addEventListener('submit', () => {
    const submit = deleteForm.querySelector('button[type="submit"]');
    if (submit) submit.disabled = true;
  });
})();
