
    const closeAllDropdowns = () => {
      document.querySelectorAll('.action-menu.is-open').forEach((openMenu) => {
        openMenu.classList.remove('is-open');
        const openToggle = openMenu.querySelector('[data-dropdown-toggle]');
        if (openToggle) openToggle.setAttribute('aria-expanded', 'false');
      });
    };

    document.addEventListener('click', (event) => {
      const toggle = event.target.closest('[data-dropdown-toggle]');

      if (toggle) {
        event.preventDefault();
        const menu = toggle.closest('.action-menu');
        if (!menu) return;
        const isOpen = menu.classList.contains('is-open');

        closeAllDropdowns();

        if (!isOpen) {
          menu.classList.add('is-open');
          toggle.setAttribute('aria-expanded', 'true');
        }

        return;
      }

      if (event.target.closest('.action-menu')) return;
      closeAllDropdowns();
    });

    document.addEventListener('keydown', (event) => {
      if (event.key !== 'Escape') return;
      closeAllDropdowns();
    });

    const commentModal = document.querySelector('[data-comment-modal]');
    const commentModalClose = document.querySelector('[data-comment-modal-close]');
    const reactionsModal = document.querySelector('[data-reactions-modal]');
    const reactionsModalClose = document.querySelector('[data-reactions-modal-close]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const reactionLabel = (key, fallback = '') => reactionsModal?.dataset?.[key] || fallback;

    const formatCount = (value) => {
      const number = Number.parseInt(value, 10);
      if (!Number.isFinite(number)) return '0';
      return new Intl.NumberFormat(document.documentElement.lang || undefined).format(number);
    };

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

    const savePostContext = (card, context) => {
      const contextScript = card?.querySelector('[data-rework-post-context]');
      if (!contextScript || !context) return;
      contextScript.textContent = JSON.stringify(context);
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
      const modalBody = commentModal.querySelector('.modal-post-body');
      const modalStats = commentModal.querySelector('.modal-post-stats');
      const modalPostPanel = commentModal.querySelector('.comment-modal-post');
      let modalExtra = commentModal.querySelector('[data-rework-modal-extra]');
      const modalCount = commentModal.querySelector('.comment-modal-head strong');
      const modalThread = commentModal.querySelector('.comment-thread');

      if (modalPostPanel) {
        modalPostPanel.classList.toggle('has-media', Boolean(context.media_url));
        modalPostPanel.classList.toggle('has-no-media', !context.media_url);
        modalPostPanel.classList.toggle('has-body', Boolean(context.body_html));
        modalPostPanel.classList.toggle('has-no-body', !context.body_html);
      }

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

      const renderModalExtras = () => {
        if (!modalPostPanel) return;

        if (!modalExtra) {
          modalExtra = document.createElement('div');
          modalExtra.setAttribute('data-rework-modal-extra', '');
          modalExtra.className = 'rework-modal-extra';
          modalBody?.insertAdjacentElement('afterend', modalExtra);
        }

        modalExtra.innerHTML = '';

        if (context.feeling?.label) {
          const feeling = document.createElement('div');
          feeling.className = 'rework-post-feeling';
          feeling.innerHTML = `<span>${context.feeling.emoji || '✨'}</span><strong>${context.author || 'HNT Hunter'}</strong><em>fühlt sich ${context.feeling.label}</em>`;
          modalExtra.appendChild(feeling);
        }

        if (context.poll && Array.isArray(context.poll.options) && context.poll.options.length) {
          const poll = document.createElement('div');
          poll.className = 'rework-post-poll';

          if (context.poll.question) {
            const question = document.createElement('strong');
            question.className = 'rework-post-poll-question';
            question.textContent = context.poll.question;
            poll.appendChild(question);
          }

          const options = document.createElement('div');
          options.className = 'rework-post-poll-options';

          context.poll.options.forEach((option) => {
            const row = document.createElement('div');
            row.className = 'rework-post-poll-option';
            const percent = Number.parseInt(option.percent, 10) || 0;
            row.innerHTML = `
              <div class="rework-post-poll-option-head"><span></span><em>${percent}%</em></div>
              <div class="rework-post-poll-bar"><span style="width:${percent}%"></span></div>
            `;
            row.querySelector('span').textContent = option.body || '';
            options.appendChild(row);
          });

          poll.appendChild(options);

          const total = document.createElement('span');
          total.className = 'rework-post-poll-total';
          total.textContent = `${formatCount(context.poll.total_votes || 0)} Stimmen`;
          poll.appendChild(total);
          modalExtra.appendChild(poll);
        }

        modalExtra.hidden = modalExtra.childElementCount === 0;
      };

      renderModalExtras();

      if (modalStats) {
        modalStats.innerHTML = `
          <span><i aria-hidden="true" class="ph ph-heart ph-icon"></i>${context.likes_label || formatCount(context.likes || 0)} ${context.labels?.reactions || reactionLabel('labelReactions', 'Reactions')}</span>
          <span><i aria-hidden="true" class="ph ph-chat-circle ph-icon"></i>${context.comments_label || formatCount(context.comments || 0)} ${context.labels?.comments || 'Comments'}</span>
          <span><i aria-hidden="true" class="ph ph-share-network ph-icon"></i>${context.shares_label || formatCount(context.shares || 0)} ${context.labels?.shares || 'Share'}</span>
        `;
      }
      setText(modalCount, `${context.comments_label || formatCount(context.comments || 0)} ${context.labels?.comments || 'Comments'}`);

      if (modalThread) {
        modalThread.innerHTML = '';
        const comments = Array.isArray(context.comments_preview) ? context.comments_preview : [];

        if (!comments.length) {
          const empty = document.createElement('div');
          empty.className = 'comment-empty-state';
          empty.textContent = context.labels?.no_comments || 'No comments yet.';
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
            reply.textContent = context.labels?.reply || 'Reply';

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

    const closeReactionsModal = () => {
      if (!reactionsModal) return;
      reactionsModal.classList.remove('is-open');
      reactionsModal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('is-modal-open');
    };

    const renderReactionsModal = (payload) => {
      if (!reactionsModal) return;
      const total = Number.parseInt(payload?.total, 10) || 0;
      const totalNode = reactionsModal.querySelector('[data-reactions-total]');
      const statsNode = reactionsModal.querySelector('[data-reactions-stats]');
      const listNode = reactionsModal.querySelector('[data-reactions-list]');

      setText(totalNode, `${formatCount(total)} ${total === 1 ? reactionLabel('labelReaction', 'Reaction') : reactionLabel('labelReactions', 'Reactions')}`);

      if (statsNode) {
        statsNode.innerHTML = '';
        const stats = Array.isArray(payload?.stats) ? payload.stats : [];
        stats.forEach((stat) => {
          const pill = document.createElement('span');
          pill.textContent = `${stat.emoji || ''} ${stat.label || stat.type || 'Like'} ${formatCount(stat.count || 0)}`.trim();
          statsNode.appendChild(pill);
        });
        statsNode.hidden = stats.length === 0;
      }

      if (!listNode) return;
      listNode.innerHTML = '';
      const users = Array.isArray(payload?.users) ? payload.users : [];

      if (!users.length) {
        const empty = document.createElement('div');
        empty.className = 'comment-empty-state';
        empty.textContent = reactionLabel('labelNoReactions', 'No reactions yet');
        listNode.appendChild(empty);
        return;
      }

      users.forEach((user) => {
        const item = document.createElement(user.profile_url ? 'a' : 'div');
        const avatar = document.createElement('img');
        const body = document.createElement('div');
        const name = document.createElement('strong');
        const meta = document.createElement('span');
        const reaction = document.createElement('em');

        item.className = 'reaction-user';
        if (user.profile_url) item.href = user.profile_url;
        avatar.src = user.avatar || '';
        avatar.alt = user.name || '';
        name.textContent = user.name || 'HNT Hunter';
        meta.textContent = [user.username, user.reacted_at].filter(Boolean).join(' - ');
        reaction.textContent = user.reaction_emoji || 'Like';

        body.append(name, meta);
        item.append(avatar, body, reaction);
        listNode.appendChild(item);
      });
    };

    const openReactionsModal = async (url) => {
      if (!reactionsModal || !url) return;
      closeAllDropdowns();
      closeCommentModal();
      reactionsModal.classList.add('is-open');
      reactionsModal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('is-modal-open');
      renderReactionsModal({ total: 0, stats: [], users: [] });

      try {
        const response = await fetch(url, {
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
        });
        if (!response.ok) throw new Error(reactionLabel('labelReactionsFailed', 'Reactions could not be loaded.'));
        renderReactionsModal(await response.json());
      } catch (error) {
        const listNode = reactionsModal.querySelector('[data-reactions-list]');
        if (listNode) {
          listNode.innerHTML = '';
          const empty = document.createElement('div');
          empty.className = 'comment-empty-state';
          empty.textContent = reactionLabel('labelReactionsFailed', 'Reactions could not be loaded.');
          listNode.appendChild(empty);
        }
      }
    };

    const updateLikeUi = (card, reacted, count) => {
      if (!card) return;
      const likeButton = card.querySelector('[data-rework-like-toggle]');
      const summary = card.querySelector('[data-rework-like-summary]');
      const likedRow = card.querySelector('[data-rework-reactions-open]');
      const avatars = likedRow?.querySelector('.liked-avatars');
      const context = parsePostContext(card) || {};
      const formattedCount = formatCount(count);

      if (likeButton) {
        likeButton.classList.toggle('is-active', reacted);
        likeButton.setAttribute('aria-pressed', reacted ? 'true' : 'false');
        likeButton.setAttribute('data-reaction-count', String(count));
      }
      if (summary) {
        const viewerName = likedRow?.getAttribute('data-viewer-name') || 'dir';
        summary.textContent = count > 0
          ? (reacted ? `Liked by ${viewerName}${count > 1 ? ` + ${formatCount(count - 1)}` : ''}` : `${formattedCount} ${reactionLabel('labelReactions', 'Reactions')}`)
          : reactionLabel('labelNoReactions', 'No reactions yet');
      }
      if (likedRow) {
        likedRow.classList.toggle('is-empty', count <= 0);
      }
      if (avatars && !reacted) {
        avatars.querySelector('[data-rework-viewer-reaction-avatar]')?.remove();
      }
      if (avatars && reacted && avatars.children.length === 0) {
        const avatar = document.createElement('img');
        avatar.alt = likedRow?.getAttribute('data-viewer-name') || '';
        avatar.src = likedRow?.getAttribute('data-viewer-avatar') || '';
        avatar.setAttribute('data-rework-viewer-reaction-avatar', '1');
        avatars.appendChild(avatar);
      }

      context.likes = count;
      context.likes_label = formattedCount;
      context.reacted = reacted;
      savePostContext(card, context);
    };

    document.addEventListener('click', async (event) => {
      const likeButton = event.target.closest('[data-rework-like-toggle]');
      if (!likeButton) return;
      event.preventDefault();

      const card = likeButton.closest('[data-rework-post-card]');
      const url = likeButton.getAttribute('data-reaction-url');
      const type = likeButton.getAttribute('data-reaction-type') || 'like';
      if (!card || !url || likeButton.disabled) return;

      likeButton.disabled = true;
      try {
        const response = await fetch(url, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
          },
          body: JSON.stringify({ type, mode: 'toggle' }),
        });
        if (!response.ok) throw new Error('Reaction toggle failed');
        const payload = await response.json();
        updateLikeUi(card, Boolean(payload.reacted), Number.parseInt(payload.count, 10) || 0);
      } catch (error) {
        likeButton.classList.add('has-error');
        window.setTimeout(() => likeButton.classList.remove('has-error'), 900);
      } finally {
        likeButton.disabled = false;
      }
    });

    document.addEventListener('click', (event) => {
      const trigger = event.target.closest('[data-rework-reactions-open]');
      if (!trigger) return;
      event.preventDefault();
      openReactionsModal(trigger.getAttribute('data-reactions-url'));
    });

    if (reactionsModalClose) {
      reactionsModalClose.addEventListener('click', closeReactionsModal);
    }

    if (reactionsModal) {
      reactionsModal.addEventListener('click', (event) => {
        if (event.target === reactionsModal) closeReactionsModal();
      });
    }

    const initializeReadMore = () => {
      // Read-more visibility is decided server-side in the Rework post-card.
      // JS only toggles already-rendered buttons. This avoids false positives
      // from font/rendering/DOM height measurements.
    };

    document.addEventListener('click', (event) => {
      const toggle = event.target.closest('[data-rework-read-more]');
      if (!toggle) return;

      event.preventDefault();
      const body = toggle.closest('[data-rework-post-body]');
      if (!body) return;

      const expanded = body.classList.toggle('is-expanded');
      body.classList.toggle('is-collapsed', !expanded);

      toggle.textContent = expanded
        ? toggle.getAttribute('data-less-label') || 'Weniger lesen'
        : toggle.getAttribute('data-more-label') || 'Mehr lesen';
    });

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
          initializeReadMore(postStream);

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
      closeReactionsModal();
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
      closeReactionsModal();
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

    const postComposerForm = postComposerModal?.querySelector('[data-rework-post-composer-form]');
    const postComposerTextarea = postComposerModal?.querySelector('[data-rework-composer-textarea]');
    const postComposerFileInput = postComposerModal?.querySelector('[data-rework-composer-file-input]');
    const postComposerPreview = postComposerModal?.querySelector('[data-rework-composer-media-preview]');
    const postComposerError = postComposerModal?.querySelector('[data-rework-composer-error]');
    const postComposerSubmit = postComposerModal?.querySelector('[data-rework-composer-submit]');
    const postComposerVisibility = postComposerModal?.querySelector('[data-rework-composer-visibility]');
    const postComposerVisibilityLabel = postComposerModal?.querySelector('[data-rework-composer-visibility-label]');
    const postComposerMediaTool = postComposerModal?.querySelector('label[for="reworkComposerMedia"]');
    const postComposerAiInput = postComposerModal?.querySelector('.rework-composer-check input[name="ai_generated"]');
    const postComposerAiTool = postComposerAiInput?.closest('.rework-composer-check');
    const postComposerEmojiButton = postComposerModal?.querySelector('[data-rework-composer-emoji]');
    const postComposerEmojiPicker = postComposerModal?.querySelector('[data-rework-composer-emoji-picker]');

    const updateComposerVisibilityLabel = () => {
      if (!postComposerVisibility || !postComposerVisibilityLabel) return;
      const selected = postComposerVisibility.options[postComposerVisibility.selectedIndex];
      postComposerVisibilityLabel.textContent = selected?.textContent?.trim() || 'Community';
    };

    const insertComposerText = (insert) => {
      if (!postComposerTextarea || !insert) return;
      const start = postComposerTextarea.selectionStart ?? postComposerTextarea.value.length;
      const end = postComposerTextarea.selectionEnd ?? postComposerTextarea.value.length;
      const prefix = start > 0 && !/\s$/.test(postComposerTextarea.value.slice(0, start)) ? ' ' : '';
      const value = `${prefix}${insert}`;
      postComposerTextarea.value = `${postComposerTextarea.value.slice(0, start)}${value}${postComposerTextarea.value.slice(end)}`;
      postComposerTextarea.focus();
      postComposerTextarea.setSelectionRange(start + value.length, start + value.length);
    };

    updateComposerVisibilityLabel();

    postComposerVisibility?.addEventListener('change', updateComposerVisibilityLabel);

    postComposerAiInput?.addEventListener('change', () => {
      postComposerAiTool?.classList.toggle('is-checked', Boolean(postComposerAiInput.checked));
    });

    const showComposerError = (message) => {
      if (!postComposerError) return;
      postComposerError.textContent = message || '';
      postComposerError.hidden = !message;
    };

    const resetComposerPanels = () => {
      postComposerModal?.querySelectorAll('[data-rework-composer-panel]').forEach((panel) => {
        panel.hidden = true;
      });
      postComposerModal?.querySelectorAll('[data-rework-composer-panel-toggle]').forEach((button) => {
        button.classList.remove('is-active');
        button.setAttribute('aria-expanded', 'false');
      });
    };

    const renderComposerMediaPreview = () => {
      if (!postComposerFileInput || !postComposerPreview) return;
      const files = Array.from(postComposerFileInput.files || []);
      postComposerPreview.innerHTML = '';
      postComposerPreview.hidden = files.length === 0;
      postComposerMediaTool?.classList.toggle('has-media', files.length > 0);

      files.slice(0, 6).forEach((file) => {
        const item = document.createElement('span');
        item.className = 'rework-composer-media-item';
        const isVideo = file.type.startsWith('video/');
        if (isVideo) {
          item.textContent = `🎬 ${file.name}`;
        } else {
          const img = document.createElement('img');
          img.alt = file.name;
          img.src = URL.createObjectURL(file);
          img.addEventListener('load', () => URL.revokeObjectURL(img.src), { once: true });
          item.appendChild(img);
        }
        postComposerPreview.appendChild(item);
      });

      if (files.length > 6) {
        const more = document.createElement('span');
        more.className = 'rework-composer-media-item is-more';
        more.textContent = `+${files.length - 6}`;
        postComposerPreview.appendChild(more);
      }
    };

    postComposerFileInput?.addEventListener('change', () => {
      showComposerError('');
      renderComposerMediaPreview();
    });

    postComposerEmojiButton?.addEventListener('click', (event) => {
      event.preventDefault();
      if (!postComposerEmojiPicker) return;
      const shouldOpen = postComposerEmojiPicker.hidden;
      postComposerEmojiPicker.hidden = !shouldOpen;
      postComposerEmojiButton.classList.toggle('is-active', shouldOpen);
      postComposerEmojiButton.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
    });

    postComposerEmojiPicker?.addEventListener('click', (event) => {
      const emojiButton = event.target.closest('[data-rework-composer-emoji-value]');
      if (!emojiButton) return;
      event.preventDefault();
      insertComposerText(emojiButton.getAttribute('data-rework-composer-emoji-value') || '');
      postComposerEmojiPicker.hidden = true;
      postComposerEmojiButton?.classList.remove('is-active');
      postComposerEmojiButton?.setAttribute('aria-expanded', 'false');
    });

    document.addEventListener('click', (event) => {
      if (!postComposerEmojiPicker || postComposerEmojiPicker.hidden) return;
      if (event.target.closest('[data-rework-composer-emoji]') || event.target.closest('[data-rework-composer-emoji-picker]')) return;
      postComposerEmojiPicker.hidden = true;
      postComposerEmojiButton?.classList.remove('is-active');
      postComposerEmojiButton?.setAttribute('aria-expanded', 'false');
    });

    postComposerModal?.querySelectorAll('[data-rework-composer-panel-toggle]').forEach((button) => {
      button.setAttribute('aria-expanded', 'false');
      button.addEventListener('click', () => {
        const target = button.getAttribute('data-rework-composer-panel-toggle');
        const panel = postComposerModal.querySelector(`[data-rework-composer-panel="${target}"]`);
        if (!panel) return;
        const shouldOpen = panel.hidden;
        resetComposerPanels();
        panel.hidden = !shouldOpen;
        button.classList.toggle('is-active', shouldOpen);
        button.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
      });
    });

    const resetPostComposerForm = () => {
      if (!postComposerForm) return;
      postComposerForm.reset();
      if (postComposerPreview) {
        postComposerPreview.innerHTML = '';
        postComposerPreview.hidden = true;
      }
      resetComposerPanels();
      postComposerMediaTool?.classList.remove('has-media');
      postComposerAiTool?.classList.remove('is-checked');
      if (postComposerEmojiPicker) postComposerEmojiPicker.hidden = true;
      postComposerEmojiButton?.classList.remove('is-active');
      postComposerEmojiButton?.setAttribute('aria-expanded', 'false');
      updateComposerVisibilityLabel();
      showComposerError('');
      if (postComposerSubmit) {
        postComposerSubmit.disabled = false;
        postComposerSubmit.textContent = 'Posten';
      }
    };

    postComposerForm?.addEventListener('submit', async (event) => {
      event.preventDefault();
      showComposerError('');

      const formData = new FormData(postComposerForm);
      const body = String(formData.get('body') || '').trim();
      const files = Array.from(postComposerFileInput?.files || []);
      const pollQuestion = String(formData.get('poll_question') || '').trim();
      const pollOptions = Array.from(postComposerForm.querySelectorAll('input[name="poll_options[]"]'))
        .map((input) => input.value.trim())
        .filter(Boolean);

      if (!body && files.length === 0 && !(pollQuestion && pollOptions.length >= 2)) {
        showComposerError('Schreib etwas, wähle Medien aus oder erstelle eine Umfrage mit mindestens zwei Antworten.');
        postComposerTextarea?.focus();
        return;
      }

      if (postComposerSubmit) {
        postComposerSubmit.disabled = true;
        postComposerSubmit.textContent = 'Postet...';
      }

      try {
        const response = await fetch(postComposerForm.action, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
          },
          body: formData,
        });

        const payload = await response.json().catch(() => ({}));

        if (!response.ok || payload.ok === false) {
          const firstError = payload?.errors
            ? Object.values(payload.errors).flat().filter(Boolean)[0]
            : null;
          throw new Error(firstError || payload?.message || 'Post konnte nicht erstellt werden.');
        }

        resetPostComposerForm();
        closePostComposerModal();
        window.location.reload();
      } catch (error) {
        showComposerError(error?.message || 'Post konnte nicht erstellt werden.');
        if (postComposerSubmit) {
          postComposerSubmit.disabled = false;
          postComposerSubmit.textContent = 'Posten';
        }
      }
    });




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
      closeReactionsModal();
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
      if (typeof closeReactionsModal === 'function') closeReactionsModal();
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


/* 071 manual rework feed JS hotfix */
(() => {
  const commentModal = document.querySelector('[data-comment-modal]');
  const postStream = document.querySelector('[data-rework-post-stream]');

  const parseContextFromCard = (card) => {
    const node = card?.querySelector('[data-rework-post-context]');
    if (!node) return null;
    try { return JSON.parse(node.textContent || '{}'); } catch (_) { return null; }
  };

  const normalizeMediaItems = (context) => {
    const mediaItems = Array.isArray(context?.media_items) ? context.media_items : [];
    const cleaned = mediaItems.filter((item) => item && item.url);
    if (cleaned.length) return cleaned;
    if (context?.media_url) {
      return [{ url: context.media_url, type: context.media_type || 'image', alt: context.media_alt || '' }];
    }
    return [];
  };

  const renderModalMedia = (context) => {
    if (!commentModal || !context) return;
    const mediaBox = commentModal.querySelector('.modal-post-media');
    if (!mediaBox) return;
    const items = normalizeMediaItems(context);
    mediaBox.innerHTML = '';
    mediaBox.hidden = items.length === 0;
    if (!items.length) return;

    let index = 0;
    const render = () => {
      const item = items[index] || items[0];
      mediaBox.innerHTML = '';
      const stage = document.createElement('div');
      stage.className = 'modal-media-stage';
      const media = document.createElement(item.type === 'video' ? 'video' : 'img');
      media.src = item.url;
      media.alt = item.alt || context.media_alt || '';
      if (item.type === 'video') {
        media.controls = true;
        media.playsInline = true;
        media.preload = 'metadata';
      }
      stage.appendChild(media);

      if (items.length > 1) {
        const prev = document.createElement('button');
        prev.type = 'button';
        prev.className = 'modal-media-nav prev';
        prev.setAttribute('aria-label', 'Vorheriges Medium');
        prev.innerHTML = '&lsaquo;';
        prev.addEventListener('click', (event) => {
          event.preventDefault();
          event.stopPropagation();
          index = (index - 1 + items.length) % items.length;
          render();
        });

        const next = document.createElement('button');
        next.type = 'button';
        next.className = 'modal-media-nav next';
        next.setAttribute('aria-label', 'Nächstes Medium');
        next.innerHTML = '&rsaquo;';
        next.addEventListener('click', (event) => {
          event.preventDefault();
          event.stopPropagation();
          index = (index + 1) % items.length;
          render();
        });

        const counter = document.createElement('span');
        counter.className = 'modal-media-counter';
        counter.textContent = `${index + 1}/${items.length}`;
        stage.append(prev, next, counter);
      }

      mediaBox.appendChild(stage);
    };

    render();
  };

  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-comment-modal-open]');
    if (!trigger) return;
    const card = trigger.closest('[data-rework-post-card]');
    const context = parseContextFromCard(card);
    window.setTimeout(() => renderModalMedia(context), 0);
  });

  const recalibrateReadMore = (root = document) => {
    root.querySelectorAll('[data-rework-post-body]').forEach((body) => {
      const content = body.querySelector('.rework-post-body-content');
      const toggle = body.querySelector('[data-rework-read-more]');
      if (!content || !toggle) return;

      const wasExpanded = body.classList.contains('is-expanded');
      body.classList.add('is-collapsed');
      body.classList.remove('is-expanded', 'can-expand');
      toggle.hidden = true;
      toggle.textContent = toggle.getAttribute('data-more-label') || 'Mehr lesen';

      window.requestAnimationFrame(() => {
        const plainText = (content.textContent || '').replace(/\s+/g, ' ').trim();
        const actuallyOverflowing = content.scrollHeight > content.clientHeight + 12;
        const shouldClamp = plainText.length > 140 && actuallyOverflowing;
        body.classList.toggle('can-expand', shouldClamp);
        toggle.hidden = !shouldClamp;
        if (shouldClamp && wasExpanded) {
          body.classList.add('is-expanded');
          body.classList.remove('is-collapsed');
          toggle.textContent = toggle.getAttribute('data-less-label') || 'Weniger lesen';
        }
      });
    });
  };

  const queueRecalibration = (root = document) => {
    window.requestAnimationFrame(() => recalibrateReadMore(root));
    window.setTimeout(() => recalibrateReadMore(root), 160);
  };

  queueRecalibration();
  window.addEventListener('load', () => queueRecalibration(), { once: true });
  window.addEventListener('resize', () => queueRecalibration());

  if (postStream) {
    const observer = new MutationObserver(() => queueRecalibration(postStream));
    observer.observe(postStream, { childList: true, subtree: false });
  }
})();

/* Rework sidebar friend suggestions */
(() => {
  if (window.__hntReworkFriendSuggestionsReady) return;
  window.__hntReworkFriendSuggestionsReady = true;

  const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  document.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-rework-friend-request-form]');
    if (!form) return;

    event.preventDefault();

    const button = form.querySelector('button[type="submit"]');
    const requestedLabel = form.getAttribute('data-requested-label') || 'Requested';
    const failedLabel = form.getAttribute('data-failed-label') || 'Friend request failed';
    const originalLabel = button?.textContent || '';

    if (!button || button.disabled) return;

    button.disabled = true;
    button.classList.remove('has-error');

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken(),
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: new FormData(form),
      });

      const payload = await response.json().catch(() => ({}));

      if (!response.ok || payload.ok === false) {
        throw new Error(payload?.message || failedLabel);
      }

      button.textContent = requestedLabel;
      button.classList.add('is-requested');
    } catch (error) {
      button.textContent = failedLabel;
      button.classList.add('has-error');
      window.setTimeout(() => {
        button.textContent = originalLabel;
        button.classList.remove('has-error');
        button.disabled = false;
      }, 1800);
    }
  });
})();

/* Rework feed comment modal parity */
(() => {
  const modal = document.querySelector('[data-comment-modal]');
  if (!modal) return;

  const reportModal = document.querySelector('[data-rework-report-modal]');
  const reportForm = reportModal?.querySelector('[data-rework-report-form]');
  const reportStatus = reportModal?.querySelector('[data-rework-report-status]');
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const emojis = ['😀', '😂', '😍', '🔥', '💪', '🎯', '👏', '🙌', '👍', '❤️', '💯', '👀', '😎', '🤝', '🏆', '✨'];
  let activeCard = null;
  let activeContext = null;
  let replyRootId = null;
  let replyName = '';
  let activeMediaIndex = 0;
  let commentFiles = [];
  let activeReportTarget = null;

  const formatCount = (value) => new Intl.NumberFormat(document.documentElement.lang || undefined)
    .format(Number.parseInt(value, 10) || 0);

  const parseContext = (card) => {
    const script = card?.querySelector('[data-rework-post-context]');
    if (!script) return null;

    try {
      return JSON.parse(script.textContent || '{}');
    } catch (error) {
      return null;
    }
  };

  const saveContext = () => {
    const script = activeCard?.querySelector('[data-rework-post-context]');
    if (script && activeContext) script.textContent = JSON.stringify(activeContext);
  };

  const label = (key, fallback, replacements = {}) => {
    const dataKey = `label${key.replace(/(^|_)([a-z])/g, (_match, _sep, char) => char.toUpperCase())}`;
    let value = activeContext?.labels?.[key] || reportModal?.dataset?.[dataKey] || fallback;
    Object.entries(replacements).forEach(([name, replacement]) => {
      value = value.replace(`__${name}__`, replacement).replace(`:${name}`, replacement);
    });
    return value;
  };

  const setStatus = (message, isError = false) => {
    const status = modal.querySelector('[data-rework-comment-status]');
    if (!status) return;
    status.textContent = message || '';
    status.hidden = !message;
    status.classList.toggle('is-error', isError);
  };

  const mediaItems = () => {
    if (!activeContext) return [];
    if (Array.isArray(activeContext.media_items) && activeContext.media_items.length) return activeContext.media_items;
    return activeContext.media_url ? [{
      url: activeContext.media_url,
      type: activeContext.media_type,
      alt: activeContext.media_alt,
    }] : [];
  };

  const isVideoItem = (item) => item?.type === 'video' || String(item?.mime_type || '').startsWith('video/');

  const setPostCounts = () => {
    if (!activeCard || !activeContext) return;
    const commentMetric = activeCard.querySelector('.metrics .metric:first-child');
    if (commentMetric) {
      commentMetric.innerHTML = `<i aria-hidden="true" class="ph ph-chat-circle ph-icon"></i>${activeContext.comments_label || formatCount(activeContext.comments)} ${label('comments', 'Comments')}`;
    }
    modal.querySelector('[data-rework-modal-comment-count]').textContent = `${activeContext.comments_label || formatCount(activeContext.comments)} ${label('comments', 'Comments')}`;
  };

  const allComments = () => (activeContext?.comments_preview || [])
    .flatMap((thread) => [thread.root].concat(thread.replies || []))
    .filter(Boolean);

  const findComment = (id) => allComments().find((comment) => Number(comment.id) === Number(id));

  const normalizeComment = (comment) => {
    if (!comment) return null;
    const user = comment.user || {};

    return {
      ...comment,
      author: comment.author || user.name || 'HNT Hunter',
      avatar: comment.avatar || user.avatar_url || '',
      profile_url: comment.profile_url || user.profile_url || '#',
      time: comment.time || comment.created_at_label || '',
      root_id: comment.root_id || comment.id,
      viewer_reacted: Boolean(comment.viewer_reacted || comment.viewer_reaction),
      reaction_count: Number(comment.reaction_count) || 0,
      can_report: Boolean(comment.can_report),
      routes: comment.routes || {},
      media: Array.isArray(comment.media) ? comment.media : [],
    };
  };

  const insertTextAtCursor = (input, text) => {
    const start = input.selectionStart ?? input.value.length;
    const end = input.selectionEnd ?? input.value.length;
    input.value = `${input.value.slice(0, start)}${text}${input.value.slice(end)}`;
    input.focus();
    input.setSelectionRange(start + text.length, start + text.length);
  };

  const renderEmojiPicker = () => {
    const picker = modal.querySelector('[data-rework-emoji-picker]');
    if (!picker || picker.childElementCount) return;
    emojis.forEach((emoji) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.textContent = emoji;
      button.setAttribute('data-rework-emoji', emoji);
      picker.appendChild(button);
    });
  };

  const renderMedia = (wrap, mediaItems = []) => {
    if (!mediaItems.length) return;
    const grid = document.createElement('div');
    grid.className = 'rework-comment-media-grid';
    mediaItems.forEach((item) => {
      const link = document.createElement('a');
      link.href = item.url || '#';
      link.target = '_blank';
      link.rel = 'noopener';
      if (isVideoItem(item)) {
        const video = document.createElement('video');
        video.src = item.url || '';
        video.controls = true;
        video.playsInline = true;
        video.preload = 'metadata';
        link.appendChild(video);
      } else if (item.type === 'image') {
        const image = document.createElement('img');
        image.src = item.url || '';
        image.alt = item.alt || '';
        link.appendChild(image);
      } else {
        link.textContent = item.alt || item.url || '';
      }
      grid.appendChild(link);
    });
    wrap.appendChild(grid);
  };

  const renderComment = (comment, isReply = false) => {
    const item = document.createElement('article');
    item.className = `comment-item rework-comment-item ${isReply ? 'is-reply' : 'is-root'}`;
    item.dataset.reworkCommentId = comment.id;
    item.dataset.reworkRootId = comment.root_id || comment.id;

    const avatarLink = document.createElement('a');
    avatarLink.href = comment.profile_url || '#';
    const avatar = document.createElement('img');
    avatar.src = comment.avatar || '';
    avatar.alt = comment.author || '';
    avatarLink.appendChild(avatar);

    const content = document.createElement('div');
    const header = document.createElement('header');
    const author = document.createElement('a');
    const authorName = document.createElement('strong');
    author.href = comment.profile_url || '#';
    authorName.textContent = comment.author || 'HNT Hunter';
    author.appendChild(authorName);
    const time = document.createElement('span');
    time.textContent = comment.time || '';
    header.append(author, time);

    const body = document.createElement('p');
    body.className = 'rework-comment-body';
    body.innerHTML = comment.body_html || '';
    content.append(header, body);
    renderMedia(content, comment.media || []);

    const actions = document.createElement('div');
    actions.className = 'rework-comment-actions';
    actions.innerHTML = `
      <button type="button" class="${comment.viewer_reacted ? 'is-active' : ''}" data-rework-comment-like="${comment.id}" aria-label="${label('like', 'Like')}"><i aria-hidden="true" class="ph ph-heart ph-icon"></i><span>${formatCount(comment.reaction_count || 0)}</span></button>
      <button type="button" data-rework-comment-reply="${comment.id}" aria-label="${label('reply', 'Reply')}"><i aria-hidden="true" class="ph ph-arrow-bend-up-left ph-icon"></i><span>${label('reply', 'Reply')}</span></button>
      ${comment.can_edit ? `<button type="button" data-rework-comment-edit="${comment.id}" aria-label="${label('edit', 'Edit')}"><i aria-hidden="true" class="ph ph-pencil-simple ph-icon"></i><span>${label('edit', 'Edit')}</span></button>` : ''}
      ${comment.can_delete ? `<button type="button" data-rework-comment-delete="${comment.id}" aria-label="${label('delete', 'Delete')}"><i aria-hidden="true" class="ph ph-trash ph-icon"></i><span>${label('delete', 'Delete')}</span></button>` : ''}
      ${comment.can_report ? `<button type="button" data-rework-comment-report="${comment.id}" aria-label="${label('report', 'Report')}"><i aria-hidden="true" class="ph ph-flag ph-icon"></i><span>${label('report', 'Report')}</span></button>` : ''}
    `;
    content.appendChild(actions);
    item.append(avatarLink, content);
    return item;
  };

  const renderThreads = () => {
    const list = modal.querySelector('[data-rework-modal-comments]');
    if (!list || !activeContext) return;
    list.innerHTML = '';

    const threads = Array.isArray(activeContext.comments_preview) ? activeContext.comments_preview : [];
    if (!threads.length) {
      const empty = document.createElement('div');
      empty.className = 'comment-empty-state';
      empty.textContent = label('no_comments', 'No comments yet.');
      list.appendChild(empty);
      return;
    }

    threads.forEach((thread) => {
      const root = thread.root;
      const replies = Array.isArray(thread.replies) ? thread.replies : [];
      const wrap = document.createElement('div');
      wrap.className = 'rework-comment-thread';
      wrap.dataset.reworkThreadId = root.id;
      wrap.appendChild(renderComment(root, false));

      if (replies.length) {
        const toggle = document.createElement('button');
        toggle.className = 'rework-comment-replies-toggle';
        toggle.type = 'button';
        toggle.dataset.reworkRepliesToggle = root.id;
        toggle.setAttribute('aria-expanded', 'true');
        toggle.textContent = `${formatCount(replies.length)} ${label('replies', 'Replies')}`;
        wrap.appendChild(toggle);

        const repliesWrap = document.createElement('div');
        repliesWrap.className = 'rework-comment-replies';
        repliesWrap.dataset.reworkReplies = root.id;
        replies.forEach((reply) => repliesWrap.appendChild(renderComment(reply, true)));
        wrap.appendChild(repliesWrap);
      }

      list.appendChild(wrap);
    });
  };

  const syncLikeUi = (reacted, count) => {
    if (!activeCard || !activeContext) return;
    const formatted = formatCount(count);
    const button = activeCard.querySelector('[data-rework-like-toggle]');
    const summary = activeCard.querySelector('[data-rework-like-summary]');
    const likedRow = activeCard.querySelector('[data-rework-reactions-open]');

    activeContext.likes = count;
    activeContext.likes_label = formatted;
    activeContext.reacted = reacted;
    saveContext();

    button?.classList.toggle('is-active', reacted);
    button?.setAttribute('aria-pressed', reacted ? 'true' : 'false');
    button?.setAttribute('data-reaction-count', String(count));
    likedRow?.classList.toggle('is-empty', count < 1);
    if (summary) summary.textContent = count > 0 ? `${formatted} ${label('reactions', 'Reactions')}` : label('no_reactions', 'No reactions yet');
    renderModalPost();
  };

  const renderModalPost = () => {
    if (!activeContext) return;
    modal.querySelectorAll('[data-rework-modal-author-url]').forEach((link) => {
      link.href = activeContext.author_url || '#';
    });
    const avatar = modal.querySelector('.modal-post-head img');
    if (avatar) {
      avatar.src = activeContext.author_avatar || '';
      avatar.alt = activeContext.author || '';
    }
    modal.querySelector('[data-rework-modal-author]').textContent = activeContext.author || 'HNT Hunter';
    modal.querySelector('[data-rework-modal-meta]').textContent = activeContext.meta || '';

    const media = modal.querySelector('[data-rework-modal-media]');
    if (media) {
      media.innerHTML = '';
      const items = mediaItems();
      activeMediaIndex = Math.min(Math.max(activeMediaIndex, 0), Math.max(items.length - 1, 0));
      const item = items[activeMediaIndex];

      if (item) {
        const stage = document.createElement('div');
        stage.className = 'modal-media-stage';
        const node = document.createElement(isVideoItem(item) ? 'video' : 'img');
        node.src = item.url || '';
        if (isVideoItem(item)) {
          node.controls = true;
          node.playsInline = true;
          node.preload = 'metadata';
        } else {
          node.alt = item.alt || '';
        }
        stage.appendChild(node);

        if (items.length > 1) {
          stage.insertAdjacentHTML('beforeend', `
            <button type="button" class="modal-media-nav prev" data-rework-modal-media-prev aria-label="${label('previous_media', 'Previous media')}"><i aria-hidden="true" class="ph ph-caret-left ph-icon"></i></button>
            <button type="button" class="modal-media-nav next" data-rework-modal-media-next aria-label="${label('next_media', 'Next media')}"><i aria-hidden="true" class="ph ph-caret-right ph-icon"></i></button>
            <span class="modal-media-counter">${activeMediaIndex + 1} / ${items.length}</span>
          `);
        }

        media.appendChild(stage);

        if (items.length > 1) {
          const strip = document.createElement('div');
          strip.className = 'modal-media-strip';
          items.forEach((thumb, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = index === activeMediaIndex ? 'is-active' : '';
            button.setAttribute('data-rework-modal-media-thumb', String(index));
            button.setAttribute('aria-label', `${label('media', 'Media')} ${index + 1}`);
            if (isVideoItem(thumb)) {
              button.innerHTML = `<video src="${thumb.url || ''}" muted playsinline preload="metadata"></video><i aria-hidden="true" class="ph ph-play ph-icon"></i>`;
            } else {
              button.innerHTML = `<img src="${thumb.url || ''}" alt="${thumb.alt || ''}">`;
            }
            strip.appendChild(button);
          });
          media.appendChild(strip);
        }
      }
      media.hidden = items.length === 0;
    }

    const body = modal.querySelector('[data-rework-modal-body]');
    if (body) {
      body.innerHTML = activeContext.body_html || '';
      body.hidden = !activeContext.body_html;
    }

    const stats = modal.querySelector('[data-rework-modal-stats]');
    if (stats) {
      stats.innerHTML = `
        <button type="button" class="${activeContext.reacted ? 'is-active' : ''}" data-rework-modal-like><i aria-hidden="true" class="ph ph-heart ph-icon"></i>${activeContext.likes_label || formatCount(activeContext.likes)} ${label('reactions', 'Reactions')}</button>
        <span><i aria-hidden="true" class="ph ph-chat-circle ph-icon"></i>${activeContext.comments_label || formatCount(activeContext.comments)} ${label('comments', 'Comments')}</span>
        <span><i aria-hidden="true" class="ph ph-share-network ph-icon"></i>${activeContext.shares_label || formatCount(activeContext.shares)} ${label('shares', 'Share')}</span>
        ${activeContext.can_report ? `<button type="button" data-rework-modal-post-report><i aria-hidden="true" class="ph ph-flag ph-icon"></i>${label('post_report', 'Report')}</button>` : ''}
      `;
    }

    const postPanel = modal.querySelector('.comment-modal-post');
    postPanel?.classList.toggle('has-media', Boolean(mediaItems().length));
    postPanel?.classList.toggle('has-no-media', !mediaItems().length);
    postPanel?.classList.toggle('has-body', Boolean(activeContext.body_html));
    postPanel?.classList.toggle('has-no-body', !activeContext.body_html);
    setPostCounts();
  };

  const openModal = (card) => {
    activeCard = card;
    activeContext = parseContext(card);
    if (!activeContext) return;
    replyRootId = null;
    replyName = '';
    activeMediaIndex = 0;
    commentFiles = [];
    renderEmojiPicker();
    renderCommentMediaPreview();
    renderModalPost();
    renderThreads();
    setStatus('');
    const input = modal.querySelector('[data-rework-comment-input]');
    const parent = modal.querySelector('[data-rework-comment-parent]');
    if (input) {
      input.value = '';
      input.placeholder = label('write_comment', 'Write a comment');
    }
    if (parent) parent.value = '';
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('is-modal-open');
    window.setTimeout(() => input?.focus(), 120);
  };

  const closeModal = () => {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('is-modal-open');
  };

  const renderCommentMediaPreview = () => {
    const preview = modal.querySelector('[data-rework-comment-media-preview]');
    if (!preview) return;
    preview.innerHTML = '';
    preview.hidden = commentFiles.length === 0;

    commentFiles.forEach((file, index) => {
      const item = document.createElement('div');
      item.className = 'rework-comment-media-preview-item';
      const url = URL.createObjectURL(file);
      item.innerHTML = file.type.startsWith('video/')
        ? `<video src="${url}" muted playsinline preload="metadata"></video>`
        : `<img src="${url}" alt="${file.name || label('media', 'Media')}">`;

      const remove = document.createElement('button');
      remove.type = 'button';
      remove.setAttribute('data-rework-comment-media-remove', String(index));
      remove.setAttribute('aria-label', label('remove_media', 'Remove media'));
      remove.innerHTML = '<i aria-hidden="true" class="ph ph-x ph-icon"></i>';
      item.appendChild(remove);
      preview.appendChild(item);
    });
  };

  const resetCommentMedia = () => {
    commentFiles = [];
    const input = modal.querySelector('[data-rework-comment-media-input]');
    if (input) input.value = '';
    renderCommentMediaPreview();
  };

  const submitComment = async (event) => {
    event.preventDefault();
    if (!activeContext?.comment_store_url) return;
    const form = event.currentTarget;
    const input = form.querySelector('[data-rework-comment-input]');
    const button = form.querySelector('[data-rework-comment-submit]');
    const body = (input?.value || '').trim();
    if (!body && commentFiles.length === 0) {
      setStatus(label('send_failed', 'Comment could not be sent.'), true);
      return;
    }

    const original = button.textContent;
    button.disabled = true;
    button.textContent = label('sending', 'Sending...');
    setStatus('');

    try {
      const headers = {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest',
      };
      let bodyPayload;

      if (commentFiles.length > 0) {
        bodyPayload = new FormData();
        bodyPayload.set('body', body);
        if (replyRootId) bodyPayload.set('parent_id', replyRootId);
        commentFiles.forEach((file) => bodyPayload.append('media[]', file));
      } else {
        headers['Content-Type'] = 'application/json';
        bodyPayload = JSON.stringify({ body, parent_id: replyRootId || undefined });
      }

      const response = await fetch(activeContext.comment_store_url, {
        method: 'POST',
        headers,
        body: bodyPayload,
      });
      const payload = await response.json().catch(() => ({}));
      if (!response.ok || payload.ok === false) throw new Error(payload.message || label('send_failed', 'Comment could not be sent.'));

      const comment = normalizeComment(payload.comment);
      if (comment) {
        if (comment.is_reply) {
          const rootId = Number(comment.root_id || comment.parent_id || replyRootId);
          let thread = activeContext.comments_preview.find((item) => Number(item.root?.id) === rootId);
          if (!thread) {
            thread = { root: findComment(rootId), replies: [] };
            activeContext.comments_preview.push(thread);
          }
          thread.replies = thread.replies || [];
          thread.replies.push(comment);
        } else {
          activeContext.comments_preview.unshift({ root: comment, replies: [] });
        }
      }

      activeContext.comments = (Number(activeContext.comments) || 0) + 1;
      activeContext.comments_label = formatCount(activeContext.comments);
      saveContext();
      input.value = '';
      replyRootId = null;
      replyName = '';
      input.placeholder = label('write_comment', 'Write a comment');
      form.querySelector('[data-rework-comment-parent]').value = '';
      resetCommentMedia();
      renderThreads();
      renderModalPost();
    } catch (error) {
      setStatus(error.message || label('send_failed', 'Comment could not be sent.'), true);
    } finally {
      button.disabled = false;
      button.textContent = original;
    }
  };

  const updateCommentReaction = async (comment) => {
    if (!comment?.routes?.reaction) return;
    const response = await fetch(comment.routes.reaction, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ type: 'like', mode: 'toggle' }),
    });
    const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(label('reaction_failed', 'Reaction could not be saved.'));
    comment.viewer_reacted = Boolean(payload.reacted);
    comment.reaction_count = Number(payload.count) || 0;
    saveContext();
    renderThreads();
  };

  const openDeletePrompt = (comment) => {
    const item = modal.querySelector(`[data-rework-comment-id="${comment.id}"]`);
    if (!item || item.querySelector('[data-rework-delete-prompt]')) return;
    const prompt = document.createElement('div');
    prompt.className = 'rework-comment-delete-prompt';
    prompt.dataset.reworkDeletePrompt = comment.id;
    prompt.innerHTML = `
      <span>${label('delete_confirm', 'Delete this comment?')}</span>
      <button type="button" data-rework-delete-cancel>${label('cancel', 'Cancel')}</button>
      <button type="button" data-rework-delete-confirm="${comment.id}">${label('delete', 'Delete')}</button>
    `;
    item.querySelector('.rework-comment-actions')?.insertAdjacentElement('afterend', prompt);
  };

  const deleteComment = async (comment) => {
    if (!comment?.routes?.delete) return;
    const response = await fetch(comment.routes.delete, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok || payload.ok === false) throw new Error(payload.message || label('delete_failed', 'Comment could not be deleted.'));

    activeContext.comments_preview = activeContext.comments_preview
      .map((thread) => {
        if (Number(thread.root?.id) === Number(comment.id)) return null;
        thread.replies = (thread.replies || []).filter((reply) => Number(reply.id) !== Number(comment.id));
        return thread;
      })
      .filter(Boolean);
    activeContext.comments = Number(payload.comment_count ?? Math.max(0, (Number(activeContext.comments) || 0) - 1));
    activeContext.comments_label = formatCount(activeContext.comments);
    saveContext();
    renderThreads();
    renderModalPost();
  };

  const openEdit = (comment) => {
    const item = modal.querySelector(`[data-rework-comment-id="${comment.id}"]`);
    const body = item?.querySelector('.rework-comment-body');
    if (!item || !body || item.querySelector('[data-rework-edit-form]')) return;
    body.hidden = true;
    const form = document.createElement('form');
    form.className = 'rework-comment-edit-form';
    form.dataset.reworkEditForm = comment.id;
    form.innerHTML = `
      <input name="body" value="">
      <div>
        <button type="button" data-rework-edit-cancel>${label('cancel', 'Cancel')}</button>
        <button type="submit">${label('save', 'Save')}</button>
      </div>
    `;
    form.querySelector('input').value = comment.body || '';
    body.insertAdjacentElement('afterend', form);
    form.querySelector('input').focus();
  };

  const submitEdit = async (form) => {
    const id = form.dataset.reworkEditForm;
    const comment = findComment(id);
    const body = (form.querySelector('input')?.value || '').trim();
    if (!comment?.routes?.update || !body) return;

    const response = await fetch(comment.routes.update, {
      method: 'PATCH',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ body }),
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok || payload.ok === false) throw new Error(payload.message || label('save_failed', 'Comment could not be saved.'));
    comment.body = payload.body || body;
    comment.body_html = payload.body_html || body;
    saveContext();
    renderThreads();
  };

  const openReportModal = (target) => {
    if (!reportModal || !reportForm || !target?.type || !target?.id) return;
    activeReportTarget = target;
    reportForm.reset();
    reportForm.querySelector('[data-rework-report-type]').value = target.type;
    reportForm.querySelector('[data-rework-report-id]').value = target.id;
    const labelNode = reportModal.querySelector('[data-rework-report-label]');
    if (labelNode) labelNode.textContent = target.label || label('report_default', 'Help us review problematic content faster.');
    if (reportStatus) {
      reportStatus.hidden = true;
      reportStatus.textContent = '';
      reportStatus.classList.remove('is-error');
    }
    reportModal.classList.add('is-open');
    reportModal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('is-modal-open');
    window.setTimeout(() => reportForm.querySelector('select[name="reason"]')?.focus(), 80);
  };

  const closeReportModal = () => {
    if (!reportModal) return;
    reportModal.classList.remove('is-open');
    reportModal.setAttribute('aria-hidden', 'true');
    activeReportTarget = null;
    if (!modal.classList.contains('is-open')) document.body.classList.remove('is-modal-open');
  };

  const markReported = (target) => {
    if (!target) return;

    if (target.type === 'feed_post') {
      if (activeContext) {
        activeContext.can_report = false;
        activeContext.reported = true;
      }
      document.querySelectorAll(`[data-rework-report-open][data-report-type="feed_post"][data-report-id="${target.id}"]`).forEach((button) => {
        button.setAttribute('aria-disabled', 'true');
        button.classList.add('is-reported');
        const text = button.querySelector('strong');
        if (text) text.textContent = label('reported', reportModal?.dataset?.labelReported || 'Reported');
      });
    }

    if (target.type === 'feed_comment') {
      const comment = findComment(target.id);
      if (comment) {
        comment.can_report = false;
        comment.reported = true;
      }
      renderThreads();
    }

    saveContext();
    if (activeContext) renderModalPost();
  };

  document.addEventListener('click', (event) => {
    const reportTrigger = event.target.closest('[data-rework-report-open]');
    if (reportTrigger) {
      event.preventDefault();
      event.stopPropagation();
      openReportModal({
        type: reportTrigger.getAttribute('data-report-type'),
        id: reportTrigger.getAttribute('data-report-id'),
        label: reportTrigger.getAttribute('data-report-label'),
      });
      return;
    }

    const trigger = event.target.closest('[data-comment-modal-open]');
    if (!trigger) return;
    const card = trigger.closest('[data-rework-post-card]');
    if (!card) return;
    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();
    openModal(card);
  }, true);

  modal.querySelector('[data-rework-comment-form]')?.addEventListener('submit', submitComment);

  modal.addEventListener('click', async (event) => {
    if (event.target === modal || event.target.closest('[data-comment-modal-close]')) {
      event.preventDefault();
      closeModal();
      return;
    }

    if (event.target.closest('[data-rework-emoji-toggle]')) {
      event.preventDefault();
      const picker = modal.querySelector('[data-rework-emoji-picker]');
      if (picker) picker.hidden = !picker.hidden;
      return;
    }

    const emoji = event.target.closest('[data-rework-emoji]')?.getAttribute('data-rework-emoji');
    if (emoji) {
      event.preventDefault();
      insertTextAtCursor(modal.querySelector('[data-rework-comment-input]'), emoji);
      modal.querySelector('[data-rework-emoji-picker]').hidden = true;
      return;
    }

    if (event.target.closest('[data-rework-modal-like]')) {
      event.preventDefault();
      const response = await fetch(activeContext.reaction_url, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': token,
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ type: 'like', mode: 'toggle' }),
      });
      const payload = await response.json().catch(() => ({}));
      if (response.ok) syncLikeUi(Boolean(payload.reacted), Number(payload.count) || 0);
      return;
    }

    if (event.target.closest('[data-rework-modal-post-report]')) {
      event.preventDefault();
      openReportModal(activeContext.report);
      return;
    }

    if (event.target.closest('[data-rework-modal-media-prev]')) {
      event.preventDefault();
      activeMediaIndex = (activeMediaIndex - 1 + mediaItems().length) % mediaItems().length;
      renderModalPost();
      return;
    }

    if (event.target.closest('[data-rework-modal-media-next]')) {
      event.preventDefault();
      activeMediaIndex = (activeMediaIndex + 1) % mediaItems().length;
      renderModalPost();
      return;
    }

    const mediaThumb = event.target.closest('[data-rework-modal-media-thumb]');
    if (mediaThumb) {
      event.preventDefault();
      activeMediaIndex = Number(mediaThumb.getAttribute('data-rework-modal-media-thumb')) || 0;
      renderModalPost();
      return;
    }

    if (event.target.closest('[data-rework-comment-media-trigger]')) {
      event.preventDefault();
      modal.querySelector('[data-rework-comment-media-input]')?.click();
      return;
    }

    const removeMedia = event.target.closest('[data-rework-comment-media-remove]');
    if (removeMedia) {
      event.preventDefault();
      const index = Number(removeMedia.getAttribute('data-rework-comment-media-remove'));
      if (Number.isFinite(index)) {
        commentFiles.splice(index, 1);
        renderCommentMediaPreview();
      }
      return;
    }

    const like = event.target.closest('[data-rework-comment-like]');
    const reply = event.target.closest('[data-rework-comment-reply]');
    const edit = event.target.closest('[data-rework-comment-edit]');
    const del = event.target.closest('[data-rework-comment-delete]');
    const report = event.target.closest('[data-rework-comment-report]');
    const confirmDelete = event.target.closest('[data-rework-delete-confirm]');
    const repliesToggle = event.target.closest('[data-rework-replies-toggle]');

    try {
      if (repliesToggle) {
        event.preventDefault();
        const rootId = repliesToggle.getAttribute('data-rework-replies-toggle');
        const replies = modal.querySelector(`[data-rework-replies="${rootId}"]`);
        const expanded = repliesToggle.getAttribute('aria-expanded') !== 'false';
        repliesToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        if (replies) replies.hidden = expanded;
      } else if (like) {
        event.preventDefault();
        await updateCommentReaction(findComment(like.getAttribute('data-rework-comment-like')));
      } else if (reply) {
        event.preventDefault();
        const comment = findComment(reply.getAttribute('data-rework-comment-reply'));
        replyRootId = comment?.root_id || comment?.id || null;
        replyName = comment?.author || '';
        const input = modal.querySelector('[data-rework-comment-input]');
        const parent = modal.querySelector('[data-rework-comment-parent]');
        if (parent) parent.value = replyRootId || '';
        if (input) {
          input.placeholder = label('reply_to', `Reply to ${replyName}`, { name: replyName });
          input.focus();
        }
      } else if (edit) {
        event.preventDefault();
        openEdit(findComment(edit.getAttribute('data-rework-comment-edit')));
      } else if (del) {
        event.preventDefault();
        openDeletePrompt(findComment(del.getAttribute('data-rework-comment-delete')));
      } else if (confirmDelete) {
        event.preventDefault();
        await deleteComment(findComment(confirmDelete.getAttribute('data-rework-delete-confirm')));
      } else if (event.target.closest('[data-rework-delete-cancel]')) {
        event.preventDefault();
        event.target.closest('[data-rework-delete-prompt]')?.remove();
      } else if (report) {
        event.preventDefault();
        const comment = findComment(report.getAttribute('data-rework-comment-report'));
        if (comment) {
          openReportModal({
            type: 'feed_comment',
            id: comment.id,
            label: comment.author ? label('comment_report_label', 'Report comment by :name', { name: comment.author }) : label('report', 'Report'),
          });
        }
      }
    } catch (error) {
      setStatus(error.message || label('action_failed', 'Action could not be completed.'), true);
    }
  });

  modal.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-rework-edit-form]');
    if (!form) return;
    event.preventDefault();
    try {
      await submitEdit(form);
    } catch (error) {
      setStatus(error.message || label('save_failed', 'Comment could not be saved.'), true);
    }
  });

  modal.addEventListener('click', (event) => {
    if (!event.target.closest('[data-rework-edit-cancel]')) return;
    event.preventDefault();
    renderThreads();
  });

  modal.addEventListener('change', (event) => {
    const input = event.target.closest('[data-rework-comment-media-input]');
    if (!input) return;
    commentFiles = commentFiles.concat(Array.from(input.files || []));
    input.value = '';
    setStatus('');
    renderCommentMediaPreview();
  });

  document.addEventListener('click', (event) => {
    if (event.target.closest('[data-rework-report-close]') || event.target === reportModal) {
      event.preventDefault();
      closeReportModal();
    }
  });

  reportForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const submit = reportForm.querySelector('[data-rework-report-submit]');
    const original = submit?.textContent || '';
    if (submit) {
      submit.disabled = true;
      submit.textContent = label('sending', 'Sending...');
    }
    if (reportStatus) {
      reportStatus.hidden = true;
      reportStatus.textContent = '';
      reportStatus.classList.remove('is-error');
    }

    try {
      const response = await fetch(reportForm.action, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': token,
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: new FormData(reportForm),
      });
      const payload = await response.json().catch(() => ({}));
      if (!response.ok) throw new Error(payload.message || label('report_failed', 'Report could not be sent.'));
      markReported(payload.report || activeReportTarget);
      if (reportStatus) {
        reportStatus.textContent = payload.message || label('report_success', 'Report sent.');
        reportStatus.hidden = false;
      }
      window.setTimeout(closeReportModal, 450);
    } catch (error) {
      if (reportStatus) {
        reportStatus.textContent = error.message || label('report_failed', 'Report could not be sent.');
        reportStatus.classList.add('is-error');
        reportStatus.hidden = false;
      }
    } finally {
      if (submit) {
        submit.disabled = false;
        submit.textContent = original;
      }
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    if (reportModal?.classList.contains('is-open')) {
      closeReportModal();
      return;
    }
    if (modal.classList.contains('is-open')) closeModal();
  });
})();


/* 082: Rework composer addon functionality */
(() => {
  const backdrop = document.querySelector('[data-post-composer-modal]');
  const form = backdrop?.querySelector('[data-rework-post-composer-form]');
  if (!backdrop || !form) return;
  if (backdrop.dataset.reworkComposerAddonReady === '1') return;
  backdrop.dataset.reworkComposerAddonReady = '1';

  const textarea = backdrop.querySelector('[data-rework-composer-textarea]');
  const fileInput = form.querySelector('[data-rework-composer-file-input]');
  const mediaTrigger = backdrop.querySelector('[data-rework-composer-media-trigger]');
  const mediaPreview = backdrop.querySelector('[data-rework-composer-media-preview]');
  const submitTrigger = backdrop.querySelector('[data-rework-composer-submit]');
  const aiTrigger = backdrop.querySelector('[data-rework-composer-ai-toggle]');
  const aiInput = form.querySelector('[data-rework-composer-ai-input]');
  const audienceTrigger = backdrop.querySelector('[data-rework-composer-audience]');
  const audienceMenu = backdrop.querySelector('[data-rework-composer-audience-menu]');
  const visibilityInput = form.querySelector('[data-rework-composer-visibility-input]');
  const feelingTrigger = backdrop.querySelector('[data-rework-composer-feeling]');
  const feelingPanel = backdrop.querySelector('[data-rework-composer-feeling-panel]');
  const feelingInput = form.querySelector('[data-rework-composer-feeling-input]');
  const pollTrigger = backdrop.querySelector('[data-rework-composer-poll]');
  const pollPanel = backdrop.querySelector('[data-rework-composer-poll-panel]');
  const emojiTrigger = backdrop.querySelector('[data-rework-composer-emoji]');
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    || form.querySelector('input[name="_token"]')?.value
    || '';

  let selectedFiles = [];

  const closeFloatingPanels = (except = null) => {
    if (except !== 'audience' && audienceMenu) audienceMenu.hidden = true;
    if (except !== 'feeling' && feelingPanel) feelingPanel.hidden = true;
    if (except !== 'poll' && pollPanel) pollPanel.hidden = true;
    if (except !== 'emoji') backdrop.querySelector('[data-rework-inline-emoji-picker]')?.remove();
  };

  const setSubmitState = (isSubmitting) => {
    if (!submitTrigger) return;
    submitTrigger.setAttribute('aria-disabled', isSubmitting ? 'true' : 'false');
    submitTrigger.textContent = isSubmitting ? 'Postet...' : 'Posten';
  };

  const syncFileInput = () => {
    if (!fileInput || typeof DataTransfer === 'undefined') return;
    const transfer = new DataTransfer();
    selectedFiles.forEach((file) => transfer.items.add(file));
    fileInput.files = transfer.files;
  };

  const renderMediaPreview = () => {
    if (!mediaPreview) return;
    mediaPreview.innerHTML = '';
    mediaPreview.hidden = selectedFiles.length === 0;

    selectedFiles.forEach((file, index) => {
      const tile = document.createElement('div');
      tile.className = 'rework-composer-media-tile';

      if (file.type.startsWith('image/')) {
        const img = document.createElement('img');
        img.alt = file.name;
        img.src = URL.createObjectURL(file);
        img.addEventListener('load', () => URL.revokeObjectURL(img.src), { once: true });
        tile.appendChild(img);
      } else if (file.type.startsWith('video/')) {
        const video = document.createElement('video');
        video.src = URL.createObjectURL(file);
        video.muted = true;
        video.playsInline = true;
        video.addEventListener('loadeddata', () => URL.revokeObjectURL(video.src), { once: true });
        tile.appendChild(video);
      } else {
        tile.classList.add('is-file');
        tile.textContent = file.name;
      }

      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'rework-composer-media-remove';
      remove.setAttribute('aria-label', `${file.name} entfernen`);
      remove.innerHTML = '<i aria-hidden="true" class="ph ph-x ph-icon"></i>';
      remove.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        selectedFiles.splice(index, 1);
        syncFileInput();
        renderMediaPreview();
      });
      tile.appendChild(remove);
      mediaPreview.appendChild(tile);
    });
  };

  mediaTrigger?.addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();
    fileInput?.click();
  });

  fileInput?.addEventListener('change', () => {
    selectedFiles = Array.from(fileInput.files || []);
    renderMediaPreview();
  });

  audienceTrigger?.addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();
    if (!audienceMenu) return;
    const willOpen = audienceMenu.hidden;
    closeFloatingPanels(willOpen ? 'audience' : null);
    audienceMenu.hidden = !willOpen;
  });

  audienceMenu?.addEventListener('click', (event) => {
    const option = event.target.closest('[data-rework-composer-audience-option]');
    if (!option || !visibilityInput || !audienceTrigger) return;
    event.preventDefault();

    const value = option.getAttribute('data-rework-composer-audience-option') || 'public';
    const label = option.querySelector('strong')?.textContent?.trim() || 'Community';
    visibilityInput.value = value;

    const textNode = Array.from(audienceTrigger.childNodes).find((node) => node.nodeType === Node.TEXT_NODE);
    if (textNode) textNode.textContent = `${label} `;

    audienceMenu.querySelectorAll('[data-rework-composer-audience-option]').forEach((button) => {
      button.classList.toggle('is-active', button === option);
    });

    audienceMenu.hidden = true;
  });

  feelingTrigger?.addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();
    if (!feelingPanel) return;
    const willOpen = feelingPanel.hidden;
    closeFloatingPanels(willOpen ? 'feeling' : null);
    feelingPanel.hidden = !willOpen;
  });

  feelingPanel?.addEventListener('click', (event) => {
    const option = event.target.closest('[data-rework-composer-feeling-option]');
    if (!option || !feelingInput) return;
    event.preventDefault();

    const isActive = option.classList.contains('is-active');
    feelingPanel.querySelectorAll('[data-rework-composer-feeling-option]').forEach((button) => button.classList.remove('is-active'));
    option.classList.toggle('is-active', !isActive);
    feelingInput.value = isActive ? 'none' : (option.getAttribute('data-rework-composer-feeling-option') || 'none');
    feelingPanel.hidden = true;
  });

  pollTrigger?.addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();
    if (!pollPanel) return;
    const willOpen = pollPanel.hidden;
    closeFloatingPanels(willOpen ? 'poll' : null);
    pollPanel.hidden = !willOpen;
  });

  aiTrigger?.addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();
    if (!aiInput) return;
    aiInput.value = aiInput.value === '1' ? '0' : '1';
    const icon = aiTrigger.querySelector('.composer-check i');
    if (icon) {
      icon.classList.toggle('ph-square', aiInput.value !== '1');
      icon.classList.toggle('ph-check-square', aiInput.value === '1');
    }
    aiTrigger.setAttribute('aria-pressed', aiInput.value === '1' ? 'true' : 'false');
  });

  const insertTextAtCursor = (insert) => {
    if (!textarea || !insert) return;
    const start = textarea.selectionStart ?? textarea.value.length;
    const end = textarea.selectionEnd ?? textarea.value.length;
    const prefix = start > 0 && !/\s$/.test(textarea.value.slice(0, start)) ? ' ' : '';
    const value = `${prefix}${insert}`;
    textarea.value = `${textarea.value.slice(0, start)}${value}${textarea.value.slice(end)}`;
    textarea.focus();
    textarea.setSelectionRange(start + value.length, start + value.length);
  };

  emojiTrigger?.addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();

    const existing = backdrop.querySelector('[data-rework-inline-emoji-picker]');
    if (existing) {
      existing.remove();
      return;
    }

    closeFloatingPanels('emoji');

    const picker = document.createElement('div');
    picker.setAttribute('data-rework-inline-emoji-picker', '');
    picker.setAttribute('role', 'menu');
    picker.style.position = 'absolute';
    picker.style.zIndex = '9999';
    picker.style.display = 'grid';
    picker.style.gridTemplateColumns = 'repeat(8, 32px)';
    picker.style.gap = '6px';
    picker.style.padding = '10px';
    picker.style.borderRadius = '12px';
    picker.style.background = '#151514';
    picker.style.border = '1px solid rgba(255,255,255,.08)';
    picker.style.boxShadow = '0 18px 50px rgba(0,0,0,.45)';

    const rect = emojiTrigger.getBoundingClientRect();
    const backdropRect = backdrop.getBoundingClientRect();
    picker.style.left = `${Math.max(12, rect.left - backdropRect.left - 235)}px`;
    picker.style.top = `${Math.max(12, rect.top - backdropRect.top - 82)}px`;

    ['😄','😂','😍','🔥','🎯','💀','🤠','😎','😭','😡','👏','🙏','👀','🏆','🎮','🧂'].forEach((emoji) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.textContent = emoji;
      button.setAttribute('aria-label', `Emoji ${emoji} einfügen`);
      button.style.width = '32px';
      button.style.height = '32px';
      button.style.border = '0';
      button.style.borderRadius = '9px';
      button.style.background = 'rgba(255,255,255,.05)';
      button.style.cursor = 'pointer';
      button.style.fontSize = '18px';
      button.addEventListener('click', (emojiEvent) => {
        emojiEvent.preventDefault();
        emojiEvent.stopPropagation();
        insertTextAtCursor(emoji);
        picker.remove();
      });
      picker.appendChild(button);
    });

    backdrop.appendChild(picker);
  });

  document.addEventListener('click', (event) => {
    if (
      event.target.closest('[data-rework-composer-audience]') ||
      event.target.closest('[data-rework-composer-audience-menu]') ||
      event.target.closest('[data-rework-composer-feeling]') ||
      event.target.closest('[data-rework-composer-feeling-panel]') ||
      event.target.closest('[data-rework-composer-poll]') ||
      event.target.closest('[data-rework-composer-poll-panel]') ||
      event.target.closest('[data-rework-composer-emoji]') ||
      event.target.closest('[data-rework-inline-emoji-picker]')
    ) {
      return;
    }

    closeFloatingPanels();
  });

  submitTrigger?.addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();

    if (submitTrigger.getAttribute('aria-disabled') === 'true') return;

    if (typeof form.requestSubmit === 'function') {
      form.requestSubmit();
    } else {
      form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    }
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    event.stopImmediatePropagation();

    if (submitTrigger?.getAttribute('aria-disabled') === 'true') return;

    const formData = new FormData(form);
    formData.set('body', textarea?.value || '');
    formData.set('visibility', visibilityInput?.value || 'public');
    formData.set('ai_generated', aiInput?.value === '1' ? '1' : '0');
    formData.set('feeling_key', feelingInput?.value || 'none');

    const body = String(formData.get('body') || '').trim();
    const files = Array.from(fileInput?.files || []);
    const pollQuestion = String(formData.get('poll_question') || '').trim();
    const pollOptions = formData.getAll('poll_options[]')
      .map((value) => String(value || '').trim())
      .filter(Boolean);

    if (!body && files.length === 0 && !(pollQuestion && pollOptions.length >= 2)) {
      textarea?.focus();
      window.alert('Schreib etwas, wähle Medien aus oder erstelle eine Umfrage mit mindestens zwei Antworten.');
      return;
    }

    setSubmitState(true);

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrfToken,
        },
        credentials: 'same-origin',
        body: formData,
      });

      const payload = await response.json().catch(() => ({}));

      if (!response.ok || payload.ok === false) {
        const firstError = payload?.errors
          ? Object.values(payload.errors).flat().filter(Boolean)[0]
          : null;
        throw new Error(firstError || payload?.message || 'Post konnte nicht erstellt werden.');
      }

      window.location.reload();
    } catch (error) {
      setSubmitState(false);
      window.alert(error?.message || 'Post konnte nicht erstellt werden.');
    }
  }, true);
})();


/* 083: Rework composer selected feeling label */
(() => {
  const backdrop = document.querySelector('[data-post-composer-modal]');
  if (!backdrop) return;

  const feelingPanel = backdrop.querySelector('[data-rework-composer-feeling-panel]');
  const selected = backdrop.querySelector('[data-rework-composer-feeling-selected]');
  const feelingInput = backdrop.querySelector('[data-rework-composer-feeling-input]');

  if (!feelingPanel || !selected || !feelingInput) return;
  if (backdrop.dataset.reworkFeelingLabelReady === '1') return;
  backdrop.dataset.reworkFeelingLabelReady = '1';

  const labels = {
    happy: '😄 Happy',
    excited: '🔥 Hype',
    focused: '🎯 Fokus',
    chill: '😎 Chill',
    tired: '💀 Müde',
    salty: '🧂 Salty',
  };

  const syncSelectedFeeling = () => {
    const value = feelingInput.value || 'none';
    if (!value || value === 'none') {
      selected.hidden = true;
      selected.textContent = '';
      return;
    }

    selected.hidden = false;
    selected.textContent = `Gefühl: ${labels[value] || value}`;
  };

  feelingPanel.addEventListener('click', () => {
    window.setTimeout(syncSelectedFeeling, 0);
  });

  document.querySelectorAll('[data-post-composer-close]').forEach((close) => {
    close.addEventListener('click', () => {
      window.setTimeout(() => {
        feelingInput.value = 'none';
        syncSelectedFeeling();
      }, 0);
    });
  });

  syncSelectedFeeling();
})();


/* 087: Rework own post edit/delete actions */
(() => {
  if (window.__hntReworkPostManageReady) return;
  window.__hntReworkPostManageReady = true;

  const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  const closeReworkDropdowns = () => {
    document.querySelectorAll('.action-menu.is-open').forEach((menu) => {
      menu.classList.remove('is-open');
      menu.querySelector('[data-dropdown-toggle]')?.setAttribute('aria-expanded', 'false');
    });
  };

  const submitPostUpdate = async (trigger, nextBody) => {
    const url = trigger.getAttribute('data-update-url');
    if (!url) throw new Error('Update-URL fehlt.');

    const params = new URLSearchParams();
    params.set('_method', 'PUT');
    params.set('body', nextBody);
    params.set('visibility', trigger.getAttribute('data-post-visibility') || 'public');
    params.set('background_style', trigger.getAttribute('data-post-background-style') || 'none');
    params.set('feeling_key', trigger.getAttribute('data-post-feeling-key') || 'none');

    const response = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
        'X-CSRF-TOKEN': csrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: params,
    });

    if (!response.ok) {
      throw new Error('Post konnte nicht gespeichert werden.');
    }
  };

  document.addEventListener('click', async (event) => {
    const editTrigger = event.target.closest('[data-rework-post-edit-open]');

    if (editTrigger) {
      event.preventDefault();
      event.stopPropagation();
      closeReworkDropdowns();

      const currentBody = editTrigger.getAttribute('data-post-body') || '';
      const nextBody = window.prompt('Post bearbeiten', currentBody);

      if (nextBody === null) return;

      const trimmedBody = nextBody.trim();

      if (!trimmedBody) {
        window.alert('Der Post darf nicht leer sein.');
        return;
      }

      try {
        await submitPostUpdate(editTrigger, trimmedBody);
        window.location.reload();
      } catch (error) {
        window.alert(error?.message || 'Post konnte nicht gespeichert werden.');
      }

      return;
    }

    const deleteTrigger = event.target.closest('[data-rework-post-delete-trigger]');

    if (deleteTrigger) {
      event.preventDefault();
      event.stopPropagation();
      closeReworkDropdowns();

      const formId = deleteTrigger.getAttribute('data-delete-form');
      const form = formId ? document.getElementById(formId) : null;

      if (!form) return;
      if (!window.confirm('Diesen Post wirklich löschen?')) return;

      if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
      } else {
        form.submit();
      }
    }
  });
})();

/* 089: Rework post edit/delete using existing composer/settings modal design */
(() => {
  if (window.__hntReworkPostManageDesignerReady) return;
  window.__hntReworkPostManageDesignerReady = true;

  const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  let activeEditTrigger = null;
  let activeDeleteForm = null;
  let selectedFiles = [];

  const closeDropdowns = () => {
    document.querySelectorAll('.action-menu.is-open').forEach((menu) => {
      menu.classList.remove('is-open');
      menu.querySelector('[data-dropdown-toggle]')?.setAttribute('aria-expanded', 'false');
    });
  };

  const escapeHtml = (value) => String(value || '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

  const parsePostContext = (card) => {
    const node = card?.querySelector('[data-rework-post-context]');
    if (!node) return {};
    try {
      return JSON.parse(node.textContent || '{}') || {};
    } catch (_) {
      return {};
    }
  };

  const mediaItemsFromContext = (context) => {
    const items = Array.isArray(context?.media_items) ? context.media_items : [];
    const cleaned = items.filter((item) => item && item.url);
    if (cleaned.length) return cleaned;

    if (context?.media_url) {
      return [{
        id: '',
        url: context.media_url,
        type: context.media_type || 'image',
        alt: context.media_alt || 'Post Medium',
      }];
    }

    return [];
  };

  const viewerFromPage = () => {
    const profileCard = document.querySelector('.profile-card');
    const avatar = document.querySelector('.profile-card img, .header-avatar, .post-composer-author img');
    return {
      name: profileCard?.querySelector('.profile-top strong')?.textContent?.trim()
        || document.querySelector('.post-composer-author strong')?.textContent?.trim()
        || 'HNT Hunter',
      avatar: avatar?.getAttribute('src') || '',
    };
  };

  const ensureEditModal = () => {
    let backdrop = document.querySelector('[data-rework-post-edit-modal]');
    if (backdrop) return backdrop;

    const viewer = viewerFromPage();

    backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop post-composer-backdrop rework-post-edit-backdrop';
    backdrop.setAttribute('data-rework-post-edit-modal', '');
    backdrop.setAttribute('aria-hidden', 'true');
    backdrop.innerHTML = `
      <section aria-labelledby="rework-post-edit-title" aria-modal="true" class="post-composer-modal rework-post-edit-composer-modal" role="dialog">
        <div aria-hidden="true" class="post-composer-grip"></div>
        <header class="post-composer-header">
          <div class="post-composer-titleblock">
            <span class="composer-eyebrow"><span aria-hidden="true" class="composer-dot"></span>HNT FEED</span>
            <h2 id="rework-post-edit-title">Post bearbeiten</h2>
            <p>Bearbeite Text und Medien deines Feed-Posts.</p>
          </div>
          <button aria-label="Post bearbeiten schließen" class="post-composer-close" data-rework-post-edit-close type="button"><i aria-hidden="true" class="ph ph-x ph-icon"></i></button>
        </header>

        <div class="post-composer-body">
          <div class="post-composer-author-row">
            <div class="post-composer-author">
              <img alt="${escapeHtml(viewer.name)}" src="${escapeHtml(viewer.avatar)}">
              <div>
                <strong>${escapeHtml(viewer.name)}</strong>
                <span data-rework-post-edit-audience>Community · HNT Feed</span>
              </div>
            </div>
          </div>

          <div class="post-composer-textbox">
            <textarea data-rework-post-edit-body maxlength="5000" name="body" placeholder="Was gibt es Neues im Bayou?"></textarea>
            <div class="composer-textbox-footer">
              <div aria-hidden="true" class="composer-ghost-actions"><span></span><span></span><span></span></div>
              <span class="rework-post-edit-counter" data-rework-post-edit-count>0/5000</span>
            </div>
          </div>

          <div class="post-composer-tools rework-post-edit-tools">
            <a data-rework-post-edit-add-media href="#"><span><i aria-hidden="true" class="ph ph-plus ph-icon"></i></span>Medien hinzufügen</a>
            <input accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime" data-rework-post-edit-file-input multiple type="file" hidden>
          </div>

          <div class="rework-composer-addons rework-post-edit-addons">
            <div class="rework-composer-media-preview rework-post-edit-media-preview" data-rework-post-edit-media-preview></div>
            <span class="rework-post-edit-error" data-rework-post-edit-error hidden></span>
          </div>
        </div>

        <footer class="post-composer-footer">
          <a class="composer-cancel" data-rework-post-edit-close href="#">Abbrechen</a>
          <a class="composer-submit" data-rework-post-edit-save href="#">Speichern</a>
        </footer>
      </section>
    `;

    document.body.appendChild(backdrop);
    return backdrop;
  };

  const ensureDeleteModal = () => {
    let backdrop = document.querySelector('[data-rework-post-delete-modal]');
    if (backdrop) return backdrop;

    backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop post-composer-backdrop rework-post-delete-backdrop';
    backdrop.setAttribute('data-rework-post-delete-modal', '');
    backdrop.setAttribute('aria-hidden', 'true');
    backdrop.innerHTML = `
      <section aria-labelledby="rework-post-delete-title" aria-modal="true" class="post-composer-modal rework-post-delete-composer-modal" role="dialog">
        <div aria-hidden="true" class="post-composer-grip"></div>
        <header class="post-composer-header">
          <div class="post-composer-titleblock">
            <span class="composer-eyebrow"><span aria-hidden="true" class="composer-dot"></span>BESTÄTIGUNG</span>
            <h2 id="rework-post-delete-title">Post löschen?</h2>
            <p>Der Post wird dauerhaft entfernt. Diese Aktion kann nicht rückgängig gemacht werden.</p>
          </div>
          <button aria-label="Löschen schließen" class="post-composer-close" data-rework-post-delete-close type="button"><i aria-hidden="true" class="ph ph-x ph-icon"></i></button>
        </header>

        <div class="post-composer-body rework-post-delete-body">
          <div class="settings-section-head">
            <span>HNT Feed</span>
            <h3>Diesen Beitrag wirklich löschen?</h3>
            <p>Alle zugehörigen Inhalte dieses Posts werden aus dem Feed entfernt.</p>
          </div>
        </div>

        <footer class="post-composer-footer">
          <a class="composer-cancel" data-rework-post-delete-close href="#">Nein, behalten</a>
          <a class="composer-submit rework-post-delete-confirm" data-rework-post-delete-confirm href="#">Ja, löschen</a>
        </footer>
      </section>
    `;

    document.body.appendChild(backdrop);
    return backdrop;
  };

  const setBackdropOpen = (backdrop, open) => {
    if (!backdrop) return;
    backdrop.classList.toggle('is-open', open);
    backdrop.setAttribute('aria-hidden', open ? 'false' : 'true');
    document.body.classList.toggle('is-modal-open', open);
  };

  const closeEditModal = () => {
    setBackdropOpen(document.querySelector('[data-rework-post-edit-modal]'), false);
    activeEditTrigger = null;
    selectedFiles = [];
  };

  const closeDeleteModal = () => {
    setBackdropOpen(document.querySelector('[data-rework-post-delete-modal]'), false);
    activeDeleteForm = null;
  };

  const renderEditMedia = (context) => {
    const backdrop = ensureEditModal();
    const preview = backdrop.querySelector('[data-rework-post-edit-media-preview]');
    if (!preview) return;

    const existing = mediaItemsFromContext(context);
    preview.innerHTML = '';

    if (!existing.length && !selectedFiles.length) {
      preview.innerHTML = '<div class="rework-post-edit-empty-media">Keine Medien an diesem Post.</div>';
      return;
    }

    existing.forEach((item, index) => {
      const id = item.id ? String(item.id) : '';
      const tile = document.createElement('div');
      tile.className = 'rework-composer-media-item rework-post-edit-media-item';
      tile.setAttribute('data-rework-existing-media', id);
      tile.setAttribute('data-rework-existing-media-url', item.url || '');
      tile.setAttribute('data-removed', '0');
      tile.innerHTML = item.type === 'video'
        ? `<video src="${escapeHtml(item.url)}" muted playsinline preload="metadata"></video>${id ? '<button type="button" aria-label="Medium entfernen" data-rework-existing-media-remove><i aria-hidden="true" class="ph ph-x ph-icon"></i></button>' : ''}<em>Video ${index + 1}</em>`
        : `<img src="${escapeHtml(item.url)}" alt="${escapeHtml(item.alt || 'Post Medium')}">${id ? '<button type="button" aria-label="Medium entfernen" data-rework-existing-media-remove><i aria-hidden="true" class="ph ph-x ph-icon"></i></button>' : ''}<em>Bild ${index + 1}</em>`;
      /* 091: hide remove button when existing media id is missing */
      preview.appendChild(tile);
    });

    selectedFiles.forEach((file, index) => {
      const tile = document.createElement('div');
      tile.className = 'rework-composer-media-item rework-post-edit-media-item is-new';
      const url = URL.createObjectURL(file);

      if (file.type.startsWith('video/')) {
        tile.innerHTML = `<video src="${escapeHtml(url)}" muted playsinline preload="metadata"></video><button type="button" aria-label="Neues Medium entfernen" data-rework-new-media-remove="${index}"><i aria-hidden="true" class="ph ph-x ph-icon"></i></button><em>Neu ${index + 1}</em>`;
      } else {
        tile.innerHTML = `<img src="${escapeHtml(url)}" alt="${escapeHtml(file.name)}"><button type="button" aria-label="Neues Medium entfernen" data-rework-new-media-remove="${index}"><i aria-hidden="true" class="ph ph-x ph-icon"></i></button><em>Neu ${index + 1}</em>`;
      }

      preview.appendChild(tile);
    });
  };

  const updateCounter = () => {
    const backdrop = ensureEditModal();
    const textarea = backdrop.querySelector('[data-rework-post-edit-body]');
    const counter = backdrop.querySelector('[data-rework-post-edit-count]');
    if (!textarea || !counter) return;
    counter.textContent = `${textarea.value.length}/5000`;
  };

  const showEditError = (message) => {
    const backdrop = ensureEditModal();
    const error = backdrop.querySelector('[data-rework-post-edit-error]');
    if (!error) return;
    error.textContent = message || '';
    error.hidden = !message;
  };

  const openEditModal = (trigger) => {
    activeEditTrigger = trigger;
    activeDeleteForm = null;
    selectedFiles = [];
    closeDropdowns();

    const card = trigger.closest('[data-rework-post-card]');
    const context = parsePostContext(card);
    const backdrop = ensureEditModal();
    const textarea = backdrop.querySelector('[data-rework-post-edit-body]');
    const audience = backdrop.querySelector('[data-rework-post-edit-audience]');
    const save = backdrop.querySelector('[data-rework-post-edit-save]');

    if (textarea) {
      textarea.value = trigger.getAttribute('data-post-body') || '';
    }

    if (audience) {
      audience.textContent = `${trigger.getAttribute('data-post-visibility') || 'Community'} · HNT Feed`;
    }

    if (save) {
      save.textContent = 'Speichern';
      save.setAttribute('aria-disabled', 'false');
    }

    const fileInput = backdrop.querySelector('[data-rework-post-edit-file-input]');
    if (fileInput) fileInput.value = '';

    showEditError('');
    updateCounter();
    renderEditMedia(context);
    setBackdropOpen(backdrop, true);

    window.setTimeout(() => {
      textarea?.focus();
      textarea?.setSelectionRange(textarea.value.length, textarea.value.length);
    }, 90);
  };

  const openDeleteModal = (trigger) => {
    const formId = trigger.getAttribute('data-delete-form');
    activeDeleteForm = formId ? document.getElementById(formId) : null;
    activeEditTrigger = null;

    if (!activeDeleteForm) return;

    closeDropdowns();

    const backdrop = ensureDeleteModal();
    const confirm = backdrop.querySelector('[data-rework-post-delete-confirm]');
    if (confirm) {
      confirm.textContent = 'Ja, löschen';
      confirm.disabled = false;
    }
    setBackdropOpen(backdrop, true);
  };

  const currentContext = () => parsePostContext(activeEditTrigger?.closest('[data-rework-post-card]'));

  const visibleExistingMediaCount = () => {
    const backdrop = ensureEditModal();
    return backdrop.querySelectorAll('[data-rework-existing-media][data-removed="0"]').length;
  };

  const submitEdit = async () => {
    const backdrop = ensureEditModal();
    const textarea = backdrop.querySelector('[data-rework-post-edit-body]');
    const save = backdrop.querySelector('[data-rework-post-edit-save]');
    const trigger = activeEditTrigger;

    if (!trigger || !textarea || !save) return;

    const body = textarea.value.trim();
    const context = currentContext();
    const hasPoll = Boolean(context.poll);
    const mediaCount = visibleExistingMediaCount() + selectedFiles.length;

    if (!body && mediaCount < 1 && !hasPoll) {
      showEditError('Schreib etwas oder füge mindestens ein Medium hinzu.');
      textarea.focus();
      return;
    }

    const url = trigger.getAttribute('data-update-url');
    if (!url) {
      showEditError('Update-URL fehlt.');
      return;
    }

    const formData = new FormData();
    formData.set('_method', 'PUT');
    formData.set('body', body);
    formData.set('visibility', trigger.getAttribute('data-post-visibility') || 'public');
    formData.set('background_style', trigger.getAttribute('data-post-background-style') || 'none');
    formData.set('feeling_key', trigger.getAttribute('data-post-feeling-key') || 'none');

    formData.set('media_keep_mode', '1');

    backdrop.querySelectorAll('[data-rework-existing-media]').forEach((tile) => {
      const id = tile.getAttribute('data-rework-existing-media');
      const url = tile.getAttribute('data-rework-existing-media-url')
        || tile.querySelector('img, video')?.getAttribute('src')
        || '';

      if (tile.getAttribute('data-removed') === '1') {
        if (id) formData.append('media_remove[]', id);
        if (url) formData.append('media_remove_urls[]', url);
        return;
      }

      if (url) formData.append('media_keep_urls[]', url);
    });

    selectedFiles.forEach((file) => formData.append('media[]', file));

    save.textContent = 'Speichert...';
    save.setAttribute('aria-disabled', 'true');
    showEditError('');

    try {
      const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken(),
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: formData,
      });

      const payload = await response.json().catch(() => ({}));

      if (!response.ok || payload.ok === false) {
        const firstError = payload?.errors ? Object.values(payload.errors).flat().filter(Boolean)[0] : null;
        throw new Error(firstError || payload?.message || 'Post konnte nicht gespeichert werden.');
      }

      window.location.reload();
    } catch (error) {
      showEditError(error?.message || 'Post konnte nicht gespeichert werden.');
      save.textContent = 'Speichern';
      save.setAttribute('aria-disabled', 'false');
    }
  };

  const confirmDelete = () => {
    if (!activeDeleteForm) return;

    const backdrop = ensureDeleteModal();
    const confirm = backdrop.querySelector('[data-rework-post-delete-confirm]');
    if (confirm) {
      confirm.disabled = true;
      confirm.textContent = 'Löscht...';
    }

    if (typeof activeDeleteForm.requestSubmit === 'function') {
      activeDeleteForm.requestSubmit();
    } else {
      activeDeleteForm.submit();
    }
  };

  document.addEventListener('input', (event) => {
    if (event.target.closest('[data-rework-post-edit-body]')) {
      updateCounter();
      showEditError('');
    }
  }, true);

  document.addEventListener('change', (event) => {
    const input = event.target.closest('[data-rework-post-edit-file-input]');
    if (!input) return;

    selectedFiles = selectedFiles.concat(Array.from(input.files || []));
    input.value = '';
    showEditError('');
    renderEditMedia(currentContext());
  }, true);

  document.addEventListener('click', (event) => {
    const editTrigger = event.target.closest('[data-rework-post-edit-open]');
    const deleteTrigger = event.target.closest('[data-rework-post-delete-trigger]');

    if (editTrigger) {
      event.preventDefault();
      event.stopImmediatePropagation();
      openEditModal(editTrigger);
      return;
    }

    if (deleteTrigger) {
      event.preventDefault();
      event.stopImmediatePropagation();
      openDeleteModal(deleteTrigger);
      return;
    }

    if (event.target.closest('[data-rework-post-edit-close]')) {
      event.preventDefault();
      event.stopImmediatePropagation();
      closeEditModal();
      return;
    }

    if (event.target.closest('[data-rework-post-delete-close]') || event.target.matches?.('[data-rework-post-delete-modal]')) {
      event.preventDefault();
      event.stopImmediatePropagation();
      closeDeleteModal();
      return;
    }

    const addMedia = event.target.closest('[data-rework-post-edit-add-media]');
    if (addMedia) {
      event.preventDefault();
      event.stopImmediatePropagation();
      ensureEditModal().querySelector('[data-rework-post-edit-file-input]')?.click();
      return;
    }

    const removeExisting = event.target.closest('[data-rework-existing-media-remove]');
    if (removeExisting) {
      event.preventDefault();
      event.stopImmediatePropagation();
      const tile = removeExisting.closest('[data-rework-existing-media]');
      if (tile) {
        const willRemove = tile.getAttribute('data-removed') !== '1';
        tile.setAttribute('data-removed', willRemove ? '1' : '0');
        tile.classList.toggle('is-removed', willRemove);
        removeExisting.setAttribute('aria-label', willRemove ? 'Medium behalten' : 'Medium entfernen');
        removeExisting.innerHTML = willRemove
          ? '<i aria-hidden="true" class="ph ph-arrow-counter-clockwise ph-icon"></i>'
          : '<i aria-hidden="true" class="ph ph-x ph-icon"></i>';
      }
      showEditError('');
      return;
    }

    const removeNew = event.target.closest('[data-rework-new-media-remove]');
    if (removeNew) {
      event.preventDefault();
      event.stopImmediatePropagation();
      const index = Number(removeNew.getAttribute('data-rework-new-media-remove'));
      if (Number.isFinite(index)) {
        selectedFiles.splice(index, 1);
        renderEditMedia(currentContext());
      }
      return;
    }

    if (event.target.closest('[data-rework-post-edit-save]')) {
      event.preventDefault();
      event.stopImmediatePropagation();
      if (event.target.closest('[data-rework-post-edit-save]').getAttribute('aria-disabled') === 'true') return;
      submitEdit();
      return;
    }

    if (event.target.closest('[data-rework-post-delete-confirm]')) {
      event.preventDefault();
      event.stopImmediatePropagation();
      confirmDelete();
    }
  }, true);

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;

    if (document.querySelector('[data-rework-post-edit-modal].is-open')) {
      event.preventDefault();
      closeEditModal();
    }

    if (document.querySelector('[data-rework-post-delete-modal].is-open')) {
      event.preventDefault();
      closeDeleteModal();
    }
  }, true);
})();

/* 095: Rework edit media strip navigation */
(() => {
  if (window.__hntReworkEditMediaStripNavReady) return;
  window.__hntReworkEditMediaStripNavReady = true;

  const previewSelector = '[data-rework-post-edit-media-preview]';

  const tiles = (preview) => Array.from(preview?.querySelectorAll('.rework-post-edit-media-item') || []);

  const isOverflowing = (preview) => {
    if (!preview) return false;
    return preview.scrollWidth > preview.clientWidth + 8;
  };

  const tileStep = (preview) => {
    const first = tiles(preview)[0];
    if (!first) return Math.max(180, Math.floor(preview.clientWidth * 0.8));

    const styles = window.getComputedStyle(preview);
    const gap = Number.parseFloat(styles.columnGap || styles.gap || '14') || 14;
    return first.getBoundingClientRect().width + gap;
  };

  const navFor = (preview) => preview?.parentElement?.querySelector(`[data-rework-edit-media-nav="${preview.dataset.reworkMediaNavId || ''}"]`);

  const updateNav = (preview) => {
    if (!preview) return;

    const nav = navFor(preview);
    if (!nav) return;

    const allTiles = tiles(preview);
    const total = allTiles.length;
    const overflow = isOverflowing(preview);

    nav.hidden = !overflow || total < 2;

    if (nav.hidden) return;

    const step = Math.max(1, tileStep(preview));
    const firstIndex = Math.min(total - 1, Math.max(0, Math.round(preview.scrollLeft / step)));
    const visibleCount = Math.max(1, Math.floor((preview.clientWidth + 8) / step));
    const lastIndex = Math.min(total, firstIndex + visibleCount);

    nav.querySelector('[data-rework-edit-media-range]').textContent = `${firstIndex + 1}-${lastIndex} von ${total}`;
    nav.querySelector('[data-rework-edit-media-prev]').disabled = preview.scrollLeft <= 4;
    nav.querySelector('[data-rework-edit-media-next]').disabled = preview.scrollLeft + preview.clientWidth >= preview.scrollWidth - 4;
  };

  const ensureNav = (preview) => {
    if (!preview || preview.dataset.reworkMediaNavEnhanced === '1') {
      updateNav(preview);
      return;
    }

    preview.dataset.reworkMediaNavEnhanced = '1';
    preview.dataset.reworkMediaNavId = preview.dataset.reworkMediaNavId || `media-strip-${Math.random().toString(36).slice(2)}`;

    const nav = document.createElement('div');
    nav.className = 'rework-post-edit-media-nav';
    nav.setAttribute('data-rework-edit-media-nav', preview.dataset.reworkMediaNavId);
    nav.innerHTML = `
      <button type="button" data-rework-edit-media-prev aria-label="Vorherige Medien">
        <i aria-hidden="true" class="ph ph-caret-left ph-icon"></i>
      </button>
      <span data-rework-edit-media-range></span>
      <button type="button" data-rework-edit-media-next aria-label="Weitere Medien">
        <i aria-hidden="true" class="ph ph-caret-right ph-icon"></i>
      </button>
    `;

    preview.insertAdjacentElement('afterend', nav);

    preview.addEventListener('scroll', () => updateNav(preview), { passive: true });

    preview.addEventListener('wheel', (event) => {
      if (!isOverflowing(preview)) return;
      if (Math.abs(event.deltaY) <= Math.abs(event.deltaX)) return;

      event.preventDefault();
      preview.scrollLeft += event.deltaY;
    }, { passive: false });

    updateNav(preview);
  };

  const scan = () => {
    document.querySelectorAll(previewSelector).forEach((preview) => {
      ensureNav(preview);
      updateNav(preview);
    });
  };

  document.addEventListener('click', (event) => {
    const prev = event.target.closest('[data-rework-edit-media-prev]');
    const next = event.target.closest('[data-rework-edit-media-next]');

    if (!prev && !next) return;

    event.preventDefault();
    event.stopPropagation();

    const nav = event.target.closest('[data-rework-edit-media-nav]');
    const id = nav?.getAttribute('data-rework-edit-media-nav');
    const preview = id ? document.querySelector(`${previewSelector}[data-rework-media-nav-id="${id}"]`) : null;

    if (!preview) return;

    const distance = Math.max(tileStep(preview), Math.floor(preview.clientWidth * 0.86));
    preview.scrollBy({
      left: prev ? -distance : distance,
      behavior: 'smooth',
    });

    window.setTimeout(() => updateNav(preview), 240);
  }, true);

  const observer = new MutationObserver(() => {
    window.requestAnimationFrame(scan);
  });

  observer.observe(document.documentElement, {
    childList: true,
    subtree: true,
  });

  window.addEventListener('resize', scan);
  document.addEventListener('DOMContentLoaded', scan);
  window.setTimeout(scan, 0);
  window.setTimeout(scan, 300);
})();

/* 098: Rework comment media viewer */
(() => {
  if (window.__hntReworkCommentMediaViewerReady) return;
  window.__hntReworkCommentMediaViewerReady = true;

  let activeItems = [];
  let activeIndex = 0;

  const isVideoUrl = (url = '') => /\.(mp4|webm|mov)(\?|#|$)/i.test(url);

  const ensureViewer = () => {
    let viewer = document.querySelector('[data-rework-comment-media-viewer]');
    if (viewer) return viewer;

    viewer = document.createElement('div');
    viewer.className = 'modal-backdrop rework-comment-media-viewer-backdrop';
    viewer.setAttribute('data-rework-comment-media-viewer', '');
    viewer.setAttribute('aria-hidden', 'true');
    viewer.innerHTML = `
      <section class="post-composer-modal rework-comment-media-viewer-modal" role="dialog" aria-modal="true" aria-labelledby="rework-comment-media-viewer-title">
        <div aria-hidden="true" class="post-composer-grip"></div>
        <header class="post-composer-header">
          <div class="post-composer-titleblock">
            <span class="composer-eyebrow"><span aria-hidden="true" class="composer-dot"></span>HNT MEDIA</span>
            <h2 id="rework-comment-media-viewer-title">Kommentar-Medium</h2>
            <p data-rework-comment-media-viewer-counter></p>
          </div>
          <button aria-label="Medienansicht schließen" class="post-composer-close" data-rework-comment-media-viewer-close type="button">
            <i aria-hidden="true" class="ph ph-x ph-icon"></i>
          </button>
        </header>
        <div class="rework-comment-media-viewer-stage" data-rework-comment-media-viewer-stage></div>
      </section>
    `;

    document.body.appendChild(viewer);
    return viewer;
  };

  const setOpen = (open) => {
    const viewer = ensureViewer();
    viewer.classList.toggle('is-open', open);
    viewer.setAttribute('aria-hidden', open ? 'false' : 'true');
    document.body.classList.toggle('is-modal-open', open);
  };

  const render = () => {
    const viewer = ensureViewer();
    const stage = viewer.querySelector('[data-rework-comment-media-viewer-stage]');
    const counter = viewer.querySelector('[data-rework-comment-media-viewer-counter]');

    if (!stage) return;

    const item = activeItems[activeIndex];
    stage.innerHTML = '';

    if (!item) {
      setOpen(false);
      return;
    }

    const frame = document.createElement('div');
    frame.className = 'rework-comment-media-viewer-frame';

    const mediaNode = item.type === 'video' || isVideoUrl(item.url)
      ? document.createElement('video')
      : document.createElement('img');

    mediaNode.src = item.url || '';

    if (mediaNode.tagName === 'VIDEO') {
      mediaNode.controls = true;
      mediaNode.playsInline = true;
      mediaNode.preload = 'metadata';
    } else {
      mediaNode.alt = item.alt || '';
    }

    frame.appendChild(mediaNode);

    if (activeItems.length > 1) {
      frame.insertAdjacentHTML('beforeend', `
        <button type="button" class="rework-comment-media-viewer-nav is-prev" data-rework-comment-media-viewer-prev aria-label="Vorheriges Medium">
          <i aria-hidden="true" class="ph ph-caret-left ph-icon"></i>
        </button>
        <button type="button" class="rework-comment-media-viewer-nav is-next" data-rework-comment-media-viewer-next aria-label="Nächstes Medium">
          <i aria-hidden="true" class="ph ph-caret-right ph-icon"></i>
        </button>
      `);
    }

    stage.appendChild(frame);

    if (counter) {
      counter.textContent = activeItems.length > 1 ? `${activeIndex + 1} / ${activeItems.length}` : '';
    }
  };

  const itemsFromGrid = (grid) => Array.from(grid.querySelectorAll('a'))
    .map((link) => {
      const media = link.querySelector('img, video');
      const url = link.getAttribute('href') || media?.getAttribute('src') || '';
      if (!url || url === '#') return null;

      return {
        url,
        alt: media?.getAttribute('alt') || '',
        type: media?.tagName === 'VIDEO' || isVideoUrl(url) ? 'video' : 'image',
      };
    })
    .filter(Boolean);

  const openFromLink = (link) => {
    const grid = link.closest('.rework-comment-media-grid');
    if (!grid) return;

    activeItems = itemsFromGrid(grid);
    const url = link.getAttribute('href') || link.querySelector('img, video')?.getAttribute('src') || '';
    activeIndex = Math.max(0, activeItems.findIndex((item) => item.url === url));

    render();
    setOpen(true);
  };

  document.addEventListener('click', (event) => {
    const mediaLink = event.target.closest('.rework-comment-media-grid a');

    if (mediaLink) {
      event.preventDefault();
      event.stopPropagation();
      openFromLink(mediaLink);
      return;
    }

    const viewer = event.target.closest('[data-rework-comment-media-viewer]');

    if (event.target.matches?.('[data-rework-comment-media-viewer]') || event.target.closest('[data-rework-comment-media-viewer-close]')) {
      event.preventDefault();
      setOpen(false);
      return;
    }

    if (!viewer) return;

    if (event.target.closest('[data-rework-comment-media-viewer-prev]')) {
      event.preventDefault();
      activeIndex = (activeIndex - 1 + activeItems.length) % activeItems.length;
      render();
      return;
    }

    if (event.target.closest('[data-rework-comment-media-viewer-next]')) {
      event.preventDefault();
      activeIndex = (activeIndex + 1) % activeItems.length;
      render();
    }
  }, true);

  document.addEventListener('keydown', (event) => {
    const viewer = document.querySelector('[data-rework-comment-media-viewer].is-open');
    if (!viewer) return;

    if (event.key === 'Escape') {
      event.preventDefault();
      setOpen(false);
      return;
    }

    if (event.key === 'ArrowLeft' && activeItems.length > 1) {
      event.preventDefault();
      activeIndex = (activeIndex - 1 + activeItems.length) % activeItems.length;
      render();
      return;
    }

    if (event.key === 'ArrowRight' && activeItems.length > 1) {
      event.preventDefault();
      activeIndex = (activeIndex + 1) % activeItems.length;
      render();
    }
  });
})();

/* 102: Rework sidebar late sticky profile */
(() => {
  if (window.__hntReworkSidebarLateStickyReady) return;
  window.__hntReworkSidebarLateStickyReady = true;

  const isDesktopMagicViewport = () => window.matchMedia('(min-width: 1101px)').matches;

  const killMobileClone = () => {
    if (isDesktopMagicViewport()) return;
    document.querySelectorAll('.rework-profile-late-sticky-clone').forEach((node) => node.remove());
  };

  killMobileClone();

  let clone = null;

  const ensureClone = (profile) => {
    if (!isDesktopMagicViewport()) return null;
    if (clone) return clone;

    clone = profile.cloneNode(true);
    clone.classList.add('rework-profile-late-sticky-clone');
    clone.classList.remove('is-late-sticky');
    clone.setAttribute('aria-hidden', 'true');
    clone.querySelectorAll('[id]').forEach((node) => node.removeAttribute('id'));
    document.body.appendChild(clone);

    return clone;
  };

  const setup = () => {
    if (!isDesktopMagicViewport()) {
      killMobileClone();
      window.addEventListener('resize', killMobileClone, { passive: true });
      return;
    }

    const rightCol = document.querySelector('.right-col');
    const profile = rightCol?.querySelector(':scope > .profile-card');

    if (!rightCol || !profile) return;

    const update = () => {
      if (window.matchMedia('(max-width: 1100px)').matches) {
        if (clone) {
          clone.remove();
          clone = null;
        }

        return;
      }

      const stickyClone = ensureClone(profile);
      if (!stickyClone) return;

      const columnRect = rightCol.getBoundingClientRect();
      const profileRect = profile.getBoundingClientRect();
      const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
      const originalGone = profileRect.bottom <= -8;
      const stickyHeight = stickyClone.offsetHeight || profile.offsetHeight || 0;
      const lateTriggerLine = Math.max(160, stickyHeight + 36);
      const reachedWidgetEnd = columnRect.bottom <= lateTriggerLine;
      const shouldShow = originalGone && reachedWidgetEnd;

      if (shouldShow) {
        stickyClone.style.setProperty('--rework-profile-left', `${columnRect.left}px`);
        stickyClone.style.setProperty('--rework-profile-width', `${columnRect.width}px`);
        stickyClone.classList.add('is-active');
      } else {
        stickyClone.classList.remove('is-active');
      }
    };

    update();
    window.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
    window.setTimeout(update, 120);
    window.setTimeout(update, 420);
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setup);
  } else {
    setup();
  }
})();

/* 104: Late sticky profile quick actions */
(() => {
  if (window.__hntReworkLateStickyProfileActionsReady) return;
  window.__hntReworkLateStickyProfileActionsReady = true;

  const labels = () => {
    const lang = (document.documentElement.getAttribute('lang') || 'de').toLowerCase();

    if (lang.startsWith('en')) {
      return {
        open: 'Open profile',
        edit: 'Edit profile',
        settings: 'Settings',
        logout: 'Logout',
      };
    }

    return {
      open: 'Profil öffnen',
      edit: 'Profil bearbeiten',
      settings: 'Einstellungen',
      logout: 'Logout',
    };
  };

  const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  const attachActions = () => {
    const clone = document.querySelector('.rework-profile-late-sticky-clone');
    if (!clone || clone.querySelector('[data-rework-late-profile-actions]')) return;

    const copy = labels();
    const actions = document.createElement('div');
    actions.className = 'profile-late-actions';
    actions.setAttribute('data-rework-late-profile-actions', '');
    actions.innerHTML = `
      <a href="/profile">
        <i aria-hidden="true" class="ph ph-user-circle ph-icon"></i>
        <span>${copy.open}</span>
      </a>
      <a href="/profile/edit">
        <i aria-hidden="true" class="ph ph-pencil-simple ph-icon"></i>
        <span>${copy.edit}</span>
      </a>
      <button type="button" data-rework-late-settings>
        <i aria-hidden="true" class="ph ph-gear-six ph-icon"></i>
        <span>${copy.settings}</span>
      </button>
      <form method="POST" action="/logout">
        <input type="hidden" name="_token" value="${csrfToken()}">
        <button type="submit">
          <i aria-hidden="true" class="ph ph-sign-out ph-icon"></i>
          <span>${copy.logout}</span>
        </button>
      </form>
    `;

    clone.appendChild(actions);
  };

  const observe = () => {
    if (!window.matchMedia('(min-width: 1101px)').matches) {
      document.querySelectorAll('.rework-profile-late-sticky-clone').forEach((node) => node.remove());
      return;
    }

    attachActions();

    const observer = new MutationObserver(attachActions);
    observer.observe(document.body, { childList: true, subtree: true });

    window.addEventListener('scroll', attachActions, { passive: true });
    window.addEventListener('resize', attachActions);
    window.setTimeout(attachActions, 120);
    window.setTimeout(attachActions, 420);
  };

  document.addEventListener('click', (event) => {
    const settingsButton = event.target.closest('[data-rework-late-settings]');
    if (!settingsButton) return;

    event.preventDefault();
    const opener = document.querySelector('[data-settings-modal-open]');
    if (opener) opener.click();
  }, true);

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', observe);
  } else {
    observe();
  }
})();

/* 107: Mobile sidebar flicker kill switch */
(() => {
  if (window.__hntReworkMobileSidebarFlickerKillReady) return;
  window.__hntReworkMobileSidebarFlickerKillReady = true;

  const isMobileSidebar = () => window.matchMedia('(max-width: 1100px)').matches;

  const kill = () => {
    if (!isMobileSidebar()) return;

    document.querySelectorAll('.rework-profile-late-sticky-clone').forEach((node) => node.remove());

    const rightCol = document.querySelector('.right-col');
    if (rightCol) {
      rightCol.style.setProperty('display', 'none', 'important');
      rightCol.style.setProperty('position', 'static', 'important');
      rightCol.style.setProperty('transform', 'none', 'important');
      rightCol.style.setProperty('animation', 'none', 'important');
      rightCol.style.setProperty('transition', 'none', 'important');
    }
  };

  kill();
  document.addEventListener('DOMContentLoaded', kill);
  window.addEventListener('resize', kill, { passive: true });
  window.addEventListener('orientationchange', kill, { passive: true });
  window.setTimeout(kill, 80);
  window.setTimeout(kill, 350);
  window.setTimeout(kill, 900);
})();

/* 110: Touch/mobile hard kill for right widgets and magic clone */
(() => {
  if (window.__hntReworkTouchWidgetKillReady) return;
  window.__hntReworkTouchWidgetKillReady = true;

  const isTouchDevice = () => window.matchMedia('(hover: none) and (pointer: coarse)').matches
    || window.matchMedia('(max-width: 1100px)').matches;

  const kill = () => {
    if (!isTouchDevice()) return;

    document.querySelectorAll('.rework-profile-late-sticky-clone').forEach((node) => node.remove());

    const rightCol = document.querySelector('.right-col');
    if (!rightCol) return;

    rightCol.style.setProperty('display', 'none', 'important');
    rightCol.style.setProperty('visibility', 'hidden', 'important');
    rightCol.style.setProperty('opacity', '0', 'important');
    rightCol.style.setProperty('height', '0', 'important');
    rightCol.style.setProperty('max-height', '0', 'important');
    rightCol.style.setProperty('overflow', 'hidden', 'important');
    rightCol.style.setProperty('pointer-events', 'none', 'important');
  };

  kill();
  document.addEventListener('DOMContentLoaded', kill);
  window.addEventListener('resize', kill, { passive: true });
  window.addEventListener('orientationchange', kill, { passive: true });
  window.setTimeout(kill, 80);
  window.setTimeout(kill, 350);
  window.setTimeout(kill, 900);
  window.setTimeout(kill, 1800);
})();
