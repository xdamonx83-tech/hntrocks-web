
    const closeAllDropdowns = () => {
      document.querySelectorAll('.action-menu.is-open').forEach((openMenu) => {
        openMenu.classList.remove('is-open');
        const openToggle = openMenu.querySelector('[data-dropdown-toggle]');
        if (openToggle) openToggle.setAttribute('aria-expanded', 'false');
      });
    };

    document.querySelectorAll('[data-dropdown-toggle]').forEach((toggle) => {
      toggle.addEventListener('click', (event) => {
        event.preventDefault();
        const menu = toggle.closest('.action-menu');
        if (!menu) return;
        const isOpen = menu.classList.contains('is-open');

        closeAllDropdowns();

        if (!isOpen) {
          menu.classList.add('is-open');
          toggle.setAttribute('aria-expanded', 'true');
        }
      });
    });

    document.addEventListener('click', (event) => {
      if (event.target.closest('.action-menu')) return;
      closeAllDropdowns();
    });

    document.addEventListener('keydown', (event) => {
      if (event.key !== 'Escape') return;
      closeAllDropdowns();
    });

    const commentModal = document.querySelector('[data-comment-modal]');
    const commentModalClose = document.querySelector('[data-comment-modal-close]');

    const closeCommentModal = () => {
      if (!commentModal) return;
      commentModal.classList.remove('is-open');
      commentModal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('is-modal-open');
    };

    const openCommentModal = () => {
      if (!commentModal) return;
      closeAllDropdowns();
      commentModal.classList.add('is-open');
      commentModal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('is-modal-open');
      const composerInput = commentModal.querySelector('.modal-composer input');
      if (composerInput) window.setTimeout(() => composerInput.focus(), 120);
    };

    document.querySelectorAll('[data-comment-modal-open]').forEach((trigger) => {
      trigger.addEventListener('click', (event) => {
        event.preventDefault();
        openCommentModal();
      });
    });

    if (commentModalClose) {
      commentModalClose.addEventListener('click', closeCommentModal);
    }

    if (commentModal) {
      commentModal.addEventListener('click', (event) => {
        if (event.target === commentModal) closeCommentModal();
      });
    }

    document.addEventListener('keydown', (event) => {
      if (event.key !== 'Escape') return;
      closeCommentModal();
      closePostComposerModal();
    });

    const postComposerModal = document.querySelector('[data-post-composer-modal]');
    const postComposerCloseButtons = document.querySelectorAll('[data-post-composer-close]');

    const closePostComposerModal = () => {
      if (!postComposerModal) return;
      postComposerModal.classList.remove('is-open');
      postComposerModal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('is-modal-open');
    };

    const openPostComposerModal = () => {
      if (!postComposerModal) return;
      closeAllDropdowns();
      closeCommentModal();
      postComposerModal.classList.add('is-open');
      postComposerModal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('is-modal-open');
      const composerTextarea = postComposerModal.querySelector('textarea');
      if (composerTextarea) window.setTimeout(() => composerTextarea.focus(), 120);
    };

    document.querySelectorAll('[data-post-composer-open]').forEach((trigger) => {
      trigger.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        openPostComposerModal();
      });
      trigger.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' && event.key !== ' ') return;
        event.preventDefault();
        openPostComposerModal();
      });
    });

    postComposerCloseButtons.forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        closePostComposerModal();
      });
    });

    if (postComposerModal) {
      postComposerModal.addEventListener('click', (event) => {
        if (event.target === postComposerModal) closePostComposerModal();
      });
    }



    const membersFilterModal = document.querySelector('[data-members-filter-modal]');
    const membersFilterCloseButtons = document.querySelectorAll('[data-members-filter-close]');

    const closeMembersFilterModal = () => {
      if (!membersFilterModal) return;
      membersFilterModal.classList.remove('is-open');
      membersFilterModal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('is-modal-open');
    };

    const openMembersFilterModal = () => {
      if (!membersFilterModal) return;
      closeAllDropdowns();
      closeCommentModal();
      closePostComposerModal();
      membersFilterModal.classList.add('is-open');
      membersFilterModal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('is-modal-open');
    };

    document.querySelectorAll('[data-members-filter-open]').forEach((trigger) => {
      trigger.addEventListener('click', (event) => {
        event.preventDefault();
        openMembersFilterModal();
      });
    });

    membersFilterCloseButtons.forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        closeMembersFilterModal();
      });
    });

    if (membersFilterModal) {
      membersFilterModal.addEventListener('click', (event) => {
        if (event.target === membersFilterModal) closeMembersFilterModal();
      });
    }

    document.addEventListener('keydown', (event) => {
      if (event.key !== 'Escape') return;
      closeMembersFilterModal();
    });



    const settingsModal = document.querySelector('[data-settings-modal]');
    const settingsCloseButtons = document.querySelectorAll('[data-settings-modal-close]');
    const settingsTabs = document.querySelectorAll('[data-settings-tab]');
    const settingsPanels = document.querySelectorAll('[data-settings-panel]');

    const closeSettingsModal = () => {
      if (!settingsModal) return;
      settingsModal.classList.remove('is-open');
      settingsModal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('is-modal-open');
    };

    const openSettingsModal = () => {
      if (!settingsModal) return;
      closeAllDropdowns();
      if (typeof closeCommentModal === 'function') closeCommentModal();
      if (typeof closePostComposerModal === 'function') closePostComposerModal();
      if (typeof closeProfileEditModal === 'function') closeProfileEditModal();
      if (typeof closeMembersFilterModal === 'function') closeMembersFilterModal();
      settingsModal.classList.add('is-open');
      settingsModal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('is-modal-open');
      const firstControl = settingsModal.querySelector('[data-settings-tab], input, select, button');
      if (firstControl) window.setTimeout(() => firstControl.focus(), 120);
    };

    document.querySelectorAll('[data-settings-modal-open]').forEach((trigger) => {
      trigger.addEventListener('click', (event) => {
        event.preventDefault();
        openSettingsModal();
      });
    });

    settingsCloseButtons.forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        closeSettingsModal();
      });
    });

    settingsTabs.forEach((tab) => {
      tab.addEventListener('click', () => {
        const target = tab.getAttribute('data-settings-tab');
        settingsTabs.forEach((item) => item.classList.toggle('is-active', item === tab));
        settingsPanels.forEach((panel) => panel.classList.toggle('is-active', panel.getAttribute('data-settings-panel') === target));
      });
    });

    if (settingsModal) {
      settingsModal.addEventListener('click', (event) => {
        if (event.target === settingsModal) closeSettingsModal();
      });
    }

    document.addEventListener('keydown', (event) => {
      if (event.key !== 'Escape') return;
      closeSettingsModal();
    });



    const cupDetailTabs = document.querySelectorAll('[data-cup-detail-tab]');
    const cupDetailPanels = document.querySelectorAll('[data-cup-detail-panel]');

    const activateCupDetailTab = (target) => {
      if (!target) return;
      cupDetailTabs.forEach((tab) => {
        tab.classList.toggle('active', tab.getAttribute('data-cup-detail-tab') === target);
      });
      cupDetailPanels.forEach((panel) => {
        panel.classList.toggle('is-active', panel.getAttribute('data-cup-detail-panel') === target);
      });
    };

    cupDetailTabs.forEach((tab) => {
      tab.addEventListener('click', (event) => {
        event.preventDefault();
        activateCupDetailTab(tab.getAttribute('data-cup-detail-tab'));
      });
    });

    const app = document.querySelector('.app');
    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');

    if (app && sidebarToggle) {
      sidebarToggle.addEventListener('click', () => {
        const isExpanded = app.classList.toggle('is-sidebar-expanded');
        sidebarToggle.setAttribute('aria-expanded', String(isExpanded));
        sidebarToggle.setAttribute('aria-label', isExpanded ? 'Sidebar einklappen' : 'Sidebar erweitern');
      });
    }
  