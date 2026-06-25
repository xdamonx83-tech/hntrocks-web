
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

    const parsePostContext = (card) => {
      if (!card) return null;
      const contextScript = card.querySelector('[data-rework-post-context]');
      if (!contextScript) return null;

      try {
        return JSON.parse(contextScript.textContent || '{}');
      } catch (error) {
        return null;
      }
    };

    const setHtml = (element, html) => {
      if (!element) return;
      element.innerHTML = html || '';
      element.hidden = !html;
    };

    const setText = (element, text) => {
      if (!element) return;
      element.textContent = text || '';
      element.hidden = !text;
    };

    const updateCommentModal = (context) => {
      if (!commentModal || !context) return;

      const modalAuthorAvatar = commentModal.querySelector('.modal-post-head img');
      const modalAuthorName = commentModal.querySelector('.modal-post-head strong');
      const modalAuthorMeta = commentModal.querySelector('.modal-post-head span');
      const modalMedia = commentModal.querySelector('.modal-post-media');
      const modalBody = commentModal.querySelector('.comment-modal-post > p');
      const modalStats = commentModal.querySelector('.modal-post-stats');
      const modalCount = commentModal.querySelector('.comment-modal-head strong');
      const modalThread = commentModal.querySelector('.comment-thread');

      if (modalAuthorAvatar) {
        modalAuthorAvatar.src = context.author_avatar || '';
        modalAuthorAvatar.alt = context.author || '';
      }
      setText(modalAuthorName, context.author || 'HNT Hunter');
      setText(modalAuthorMeta, context.meta || 'Feed Post');

      if (modalMedia) {
        modalMedia.innerHTML = '';
        if (context.media_url) {
          const media = document.createElement(context.media_type === 'video' ? 'video' : 'img');
          if (context.media_type === 'video') {
            media.controls = true;
            media.playsInline = true;
            media.preload = 'metadata';
          }
          media.src = context.media_url;
          media.alt = context.media_alt || '';
          modalMedia.appendChild(media);
          modalMedia.hidden = false;
        } else {
          modalMedia.hidden = true;
        }
      }

      setHtml(modalBody, context.body_html || '');

      if (modalStats) {
        modalStats.innerHTML = `
          <span><i aria-hidden="true" class="ph ph-heart ph-icon"></i>${context.likes || '0'} Likes</span>
          <span><i aria-hidden="true" class="ph ph-chat-circle ph-icon"></i>${context.comments || '0'} Kommentare</span>
          <span><i aria-hidden="true" class="ph ph-share-network ph-icon"></i>${context.shares || '0'} Shares</span>
        `;
      }
      setText(modalCount, `${context.comments || '0'} Antworten`);

      if (modalThread) {
        modalThread.innerHTML = '';
        const comments = Array.isArray(context.comments_preview) ? context.comments_preview : [];

        if (!comments.length) {
          const empty = document.createElement('div');
          empty.className = 'comment-empty-state';
          empty.textContent = 'Noch keine Kommentare.';
          modalThread.appendChild(empty);
        } else {
          comments.forEach((comment) => {
            const item = document.createElement('article');
            item.className = 'comment-item';
            const avatar = document.createElement('img');
            const content = document.createElement('div');
            const header = document.createElement('header');
            const author = document.createElement('strong');
            const time = document.createElement('span');
            const body = document.createElement('p');
            const reply = document.createElement('a');

            avatar.alt = comment.author || '';
            avatar.src = comment.avatar || '';
            reply.href = '#';
            reply.textContent = 'Antworten';

            setText(author, comment.author || 'HNT Hunter');
            setText(time, comment.time || '');
            setHtml(body, comment.body_html || '');

            header.append(author, time);
            content.append(header, body, reply);
            item.append(avatar, content);
            modalThread.appendChild(item);
          });
        }
      }
    };

    const closeCommentModal = () => {
      if (!commentModal) return;
      commentModal.classList.remove('is-open');
      commentModal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('is-modal-open');
    };

    const openCommentModal = (context) => {
      if (!commentModal) return;
      closeAllDropdowns();
      updateCommentModal(context);
      commentModal.classList.add('is-open');
      commentModal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('is-modal-open');
      const composerInput = commentModal.querySelector('.modal-composer input');
      if (composerInput) window.setTimeout(() => composerInput.focus(), 120);
    };

    document.addEventListener('click', (event) => {
      const trigger = event.target.closest('[data-comment-modal-open]');
      if (!trigger) return;
      if (!commentModal) return;

      event.preventDefault();
      const card = trigger.closest('[data-rework-post-card]');
      openCommentModal(parsePostContext(card));
    });

    document.querySelectorAll('[data-comment-modal-open]').forEach((trigger) => {
      trigger.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' && event.key !== ' ') return;
        event.preventDefault();
        const card = trigger.closest('[data-rework-post-card]');
        openCommentModal(parsePostContext(card));
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

    const loadMoreButton = document.querySelector('[data-rework-load-more]');
    const postStream = document.querySelector('[data-rework-post-stream]');

    if (loadMoreButton && postStream) {
      loadMoreButton.addEventListener('click', async () => {
        const nextUrl = loadMoreButton.getAttribute('data-next-url');
        if (!nextUrl || loadMoreButton.disabled) return;

        const label = loadMoreButton.querySelector('[data-rework-load-more-label]');
        const loadingLabel = loadMoreButton.getAttribute('data-loading-label') || 'Loading...';
        const readyLabel = loadMoreButton.getAttribute('data-ready-label') || 'Load more';
        const errorLabel = loadMoreButton.getAttribute('data-error-label') || 'Try again';

        loadMoreButton.disabled = true;
        if (label) label.textContent = loadingLabel;

        try {
          const url = new URL(nextUrl, window.location.href);
          url.searchParams.set('fragment', '1');
          const response = await fetch(url.toString(), {
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
            },
          });

          if (!response.ok) throw new Error('Load more failed');

          const payload = await response.json();
          const template = document.createElement('template');
          template.innerHTML = payload.html || '';
          postStream.append(...template.content.childNodes);

          if (payload.hasMorePages && payload.nextPageUrl) {
            loadMoreButton.setAttribute('data-next-url', payload.nextPageUrl);
            loadMoreButton.disabled = false;
            if (label) label.textContent = readyLabel;
          } else {
            loadMoreButton.closest('[data-rework-load-more-wrap]')?.remove();
          }
        } catch (error) {
          loadMoreButton.disabled = false;
          if (label) label.textContent = errorLabel;
        }
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
