(function () {
  const landing = document.querySelector('.hh-auth-landing');

  if (!landing || typeof app === 'undefined' || typeof XM_Tab === 'undefined') {
    return;
  }

  const startMode = landing.getAttribute('data-hh-auth-start') === 'register' ? 'register' : 'login';

  app.plugins.createTab({
    triggers: '.login-register-form-trigger',
    elements: '.login-register-form-element',
    startOpen: startMode === 'register' ? 2 : 1,
    animation: {
      type: 'slide-in-right'
    },
    onTabChange: function (activeTab) {
      const firstInput = activeTab.querySelector('input:not([type="hidden"]):not([type="checkbox"])');

      if (firstInput) {
        firstInput.focus();
      }
    }
  });
}());
