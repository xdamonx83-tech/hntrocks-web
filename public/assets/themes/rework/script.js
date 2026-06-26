
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
          <span><i aria-hidden="true" class="ph ph-heart ph-icon"></i>${context.likes_label || formatCount(context.likes || 0)} Reaktionen</span>
          <span><i aria-hidden="true" class="ph ph-chat-circle ph-icon"></i>${context.comments_label || formatCount(context.comments || 0)} Kommentare</span>
          <span><i aria-hidden="true" class="ph ph-share-network ph-icon"></i>${context.shares_label || formatCount(context.shares || 0)} Shares</span>
        `;
      }
      setText(modalCount, `${context.comments_label || formatCount(context.comments || 0)} Antworten`);

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

      setText(totalNode, `${formatCount(total)} ${total === 1 ? 'Reaktion' : 'Reaktionen'}`);

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
        empty.textContent = 'Noch keine Reaktionen.';
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
        if (!response.ok) throw new Error('Reactions failed');
        renderReactionsModal(await response.json());
      } catch (error) {
        const listNode = reactionsModal.querySelector('[data-reactions-list]');
        if (listNode) {
          listNode.innerHTML = '';
          const empty = document.createElement('div');
          empty.className = 'comment-empty-state';
          empty.textContent = 'Reaktionen konnten nicht geladen werden.';
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
          ? (reacted ? `Liked by ${viewerName}${count > 1 ? ` und ${formatCount(count - 1)} andere` : ''}` : `${formattedCount} Reaktionen`)
          : 'Noch keine Reaktionen';
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
