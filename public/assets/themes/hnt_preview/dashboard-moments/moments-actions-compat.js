(() => {
  'use strict';

  if (document.body?.dataset.page !== 'moments') return;

  const isEnglish = (document.documentElement.lang || '').toLowerCase().startsWith('en');
  const titleField = document.querySelector('[data-manage-edit] .hnt-moment-manage-field:first-child');

  if (titleField) {
    titleField.style.display = 'none';
  }

  document.addEventListener('click', (event) => {
    if (!event.target.closest('[data-moment-option="edit"]')) return;

    window.setTimeout(() => {
      const heading = document.getElementById('hntMomentManageTitle');
      if (heading) heading.textContent = isEnglish ? 'Edit description' : 'Beschreibung bearbeiten';
    }, 0);
  });
})();
