(() => {
  'use strict';

  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const toastNode = document.querySelector('[data-guide-toast]');
  let toastTimer;

  const toast = (message, error = false) => {
    if (!toastNode || !message) return;
    toastNode.textContent = message;
    toastNode.classList.toggle('error', error);
    toastNode.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toastNode.classList.remove('show'), 3000);
  };

  const jsonRequest = async (url, options = {}) => {
    const response = await fetch(url, {
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrf,
        ...(options.body instanceof FormData ? {} : {'Content-Type': 'application/json'}),
        ...(options.headers || {}),
      },
      ...options,
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
      const errors = payload.errors
        ? Object.values(payload.errors).flat().join('\n')
        : (payload.message || `HTTP ${response.status}`);
      throw new Error(errors);
    }
    return payload;
  };

  const guideToggleButtons = [...document.querySelectorAll('[data-guide-toggle]')];

  const syncGuideToggle = (button, active, count) => {
    button.classList.toggle('active', active);
    const label = button.querySelector('span');
    if (label) label.textContent = active ? button.dataset.activeLabel : button.dataset.inactiveLabel;
    const countNode = button.querySelector('[data-count]');
    if (countNode && Number.isFinite(Number(count))) {
      countNode.textContent = new Intl.NumberFormat(document.documentElement.lang).format(Number(count));
    }
  };

  guideToggleButtons.forEach((button) => {
    button.addEventListener('click', async () => {
      if (button.disabled) return;
      const group = button.dataset.guideToggleGroup;
      const targets = group
        ? guideToggleButtons.filter((candidate) => candidate.dataset.guideToggleGroup === group)
        : [button];
      targets.forEach((target) => { target.disabled = true; });
      try {
        const payload = await jsonRequest(button.dataset.url, {
          method: 'POST',
          body: JSON.stringify({}),
        });
        const active = payload.helpful ?? payload.saved ?? false;
        targets.forEach((target) => syncGuideToggle(target, active, payload.count));

        if (group === 'helpful' && Number.isFinite(Number(payload.count))) {
          document.querySelectorAll('[data-guide-helpful-total]').forEach((node) => {
            node.textContent = new Intl.NumberFormat(document.documentElement.lang).format(Number(payload.count));
          });
        }

        toast(payload.message);
      } catch (error) {
        toast(error.message, true);
      } finally {
        targets.forEach((target) => { target.disabled = false; });
      }
    });
  });

  document.querySelectorAll('[data-guide-share]').forEach((button) => {
    button.addEventListener('click', async () => {
      const data = {title: button.dataset.title || document.title, url: button.dataset.url || location.href};
      try {
        if (navigator.share) {
          await navigator.share(data);
        } else {
          await navigator.clipboard.writeText(data.url);
          toast(document.documentElement.lang.startsWith('de') ? 'Guide-Link wurde kopiert.' : 'Guide link copied.');
        }
      } catch (error) {
        if (error?.name !== 'AbortError') toast(error.message, true);
      }
    });
  });

  const commentsDialog = document.querySelector('[data-guide-comments-dialog]');

  document.querySelectorAll('[data-guide-comments-open]').forEach((button) => {
    button.addEventListener('click', () => {
      if (commentsDialog?.showModal) {
        commentsDialog.showModal();
      }
    });
  });

  document.querySelectorAll('[data-guide-comments-close]').forEach((button) => {
    button.addEventListener('click', () => commentsDialog?.close());
  });

  commentsDialog?.addEventListener('click', (event) => {
    if (event.target === commentsDialog) commentsDialog.close();
  });

  if (commentsDialog && location.hash.startsWith('#comment-')) {
    commentsDialog.showModal();
  }

  const commentForm = document.querySelector('[data-guide-comment-form]');
  if (commentForm) {
    let replyInput = null;
    const commentList = document.querySelector('[data-guide-comment-list]');
    const commentsHeadingCounts = document.querySelectorAll('[data-guide-comment-count], #guide-comments h2 b');
    const commentInput = commentForm.querySelector('[data-guide-comment-input]');
    const commentLength = document.querySelector('[data-guide-comment-length]');

    const updateCommentLength = () => {
      if (commentLength) commentLength.textContent = String(commentInput?.value.length || 0);
    };

    commentInput?.addEventListener('input', updateCommentLength);
    updateCommentLength();
    const emptyComments = document.querySelector('[data-guide-comments-empty]');
    const commentLabels = {
      author: commentForm.dataset.labelAuthor || '',
      reply: commentForm.dataset.labelReply || '',
      edit: commentForm.dataset.labelEdit || '',
      delete: commentForm.dataset.labelDelete || '',
      report: commentForm.dataset.labelReport || '',
      reportDetails: commentForm.dataset.labelReportDetails || '',
      reportSend: commentForm.dataset.labelReportSend || '',
    };

    commentList?.addEventListener('click', (event) => {
      const button = event.target.closest('[data-comment-reply]');
      if (button) {
        replyInput ||= (() => {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'parent_id';
          commentForm.append(input);
          return input;
        })();
        replyInput.value = button.dataset.commentReply;
        const textarea = commentForm.querySelector('textarea');
        textarea?.focus();
        if (textarea && !textarea.value.trim() && button.dataset.commentAuthor) {
          textarea.value = `@${button.dataset.commentAuthor} `;
        }
      }
    });

    const hiddenInput = (name, value) => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = name;
      input.value = value;
      return input;
    };

    const actionForm = (url, method) => {
      const form = document.createElement('form');
      form.action = url;
      form.method = 'post';
      form.append(hiddenInput('_token', csrf));
      if (method !== 'POST') form.append(hiddenInput('_method', method));
      return form;
    };

    const commentElement = (comment) => {
      const article = document.createElement('article');
      article.className = `guide-comment${comment.parent_id ? ' reply' : ''}`;
      article.id = `comment-${comment.id}`;

      const avatar = document.createElement('img');
      avatar.src = comment.author.avatar || '';
      avatar.alt = '';
      article.append(avatar);

      const body = document.createElement('div');
      const header = document.createElement('header');
      const name = document.createElement('strong');
      name.textContent = comment.author.name || comment.author.handle || '';
      header.append(name);
      if (comment.is_guide_author) {
        const badge = document.createElement('em');
        badge.textContent = commentLabels.author;
        header.append(badge);
      }
      const time = document.createElement('span');
      time.textContent = comment.created_at || '';
      header.append(time);
      body.append(header);

      const text = document.createElement('p');
      text.textContent = comment.body;
      body.append(text);

      const actions = comment.actions || {};
      if (actions.can_reply || actions.can_edit || actions.can_delete || actions.can_report) {
        const footer = document.createElement('footer');

        if (actions.can_reply) {
          const reply = document.createElement('button');
          reply.type = 'button';
          reply.dataset.commentReply = String(comment.id);
          reply.dataset.commentAuthor = (comment.author.handle || '').replace(/^@/, '');
          reply.textContent = commentLabels.reply;
          footer.append(reply);
        }

        if (actions.can_edit) {
          const details = document.createElement('details');
          const summary = document.createElement('summary');
          summary.textContent = commentLabels.edit;
          const form = actionForm(actions.update_url, 'PATCH');
          const textarea = document.createElement('textarea');
          textarea.name = 'body';
          textarea.maxLength = 2000;
          textarea.required = true;
          textarea.value = comment.body;
          const submit = document.createElement('button');
          submit.type = 'submit';
          submit.textContent = commentLabels.edit;
          form.append(textarea, submit);
          details.append(summary, form);
          footer.append(details);
        }

        if (actions.can_delete) {
          const form = actionForm(actions.delete_url, 'DELETE');
          const submit = document.createElement('button');
          submit.type = 'submit';
          submit.textContent = commentLabels.delete;
          form.append(submit);
          footer.append(form);
        }

        if (actions.can_report) {
          const details = document.createElement('details');
          const summary = document.createElement('summary');
          summary.textContent = commentLabels.report;
          const form = actionForm(actions.report_url, 'POST');
          form.append(
            hiddenInput('type', 'guide_comment'),
            hiddenInput('id', String(comment.id)),
            hiddenInput('reason', 'other')
          );
          const textarea = document.createElement('textarea');
          textarea.name = 'body';
          textarea.maxLength = 2000;
          textarea.placeholder = commentLabels.reportDetails;
          const submit = document.createElement('button');
          submit.type = 'submit';
          submit.textContent = commentLabels.reportSend;
          form.append(textarea, submit);
          details.append(summary, form);
          footer.append(details);
        }

        body.append(footer);
      }

      article.append(body);
      return article;
    };

    commentForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      const submit = commentForm.querySelector('button[type="submit"]');
      const formData = new FormData(commentForm);
      submit.disabled = true;
      try {
        const payload = await jsonRequest(commentForm.action, {
          method: 'POST',
          body: JSON.stringify(Object.fromEntries(formData.entries())),
        });
        const element = commentElement(payload.comment);
        if (payload.comment.parent_id) {
          document.querySelector(`#comment-${payload.comment.parent_id} > div`)?.append(element);
        } else {
          commentList?.append(element);
        }
        emptyComments?.remove();
        commentsHeadingCounts.forEach((node) => {
          node.textContent = new Intl.NumberFormat(document.documentElement.lang).format(Number(payload.count));
        });
        commentForm.reset();
        updateCommentLength();
        if (replyInput) replyInput.value = '';
        location.hash = `comment-${payload.comment.id}`;
        element.scrollIntoView({behavior: 'smooth', block: 'center'});
        toast(payload.message);
      } catch (error) {
        toast(error.message, true);
      } finally {
        submit.disabled = false;
      }
    });

    const moreButton = document.querySelector('[data-guide-comments-more]');
    moreButton?.addEventListener('click', async () => {
      if (!moreButton.dataset.nextUrl || moreButton.disabled) return;
      moreButton.disabled = true;
      try {
        const response = await fetch(moreButton.dataset.nextUrl, {
          credentials: 'same-origin',
          headers: {Accept: 'text/html'},
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const documentFragment = new DOMParser().parseFromString(await response.text(), 'text/html');
        documentFragment.querySelectorAll('[data-guide-comment-list] > .guide-comment').forEach((comment) => {
          commentList?.append(document.importNode(comment, true));
        });
        const next = documentFragment.querySelector('[data-guide-comments-more]');
        if (next?.dataset.nextUrl) {
          moreButton.dataset.nextUrl = next.dataset.nextUrl;
          moreButton.disabled = false;
        } else {
          moreButton.closest('.guides-pagination')?.remove();
        }
      } catch (error) {
        toast(error.message, true);
        moreButton.disabled = false;
      }
    });
  }

  const guideTocLinks = [...document.querySelectorAll('.guide-detail-toc nav a[href^="#"]')];
  const guideScroll = document.getElementById('guidesScroll');

  if (guideTocLinks.length && guideScroll) {
    const guideSections = guideTocLinks
      .map((link) => {
        const id = decodeURIComponent(link.hash.slice(1));
        const section = document.getElementById(id);
        return section ? {link, section} : null;
      })
      .filter(Boolean);
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    const setActiveGuideSection = (activeSection) => {
      guideSections.forEach(({link, section}) => {
        const active = section === activeSection;
        link.classList.toggle('active', active);
        if (active) {
          link.setAttribute('aria-current', 'location');
        } else {
          link.removeAttribute('aria-current');
        }
      });
    };

    guideSections.forEach(({link, section}) => {
      link.addEventListener('click', (event) => {
        event.preventDefault();
        setActiveGuideSection(section);

        const scrollRect = guideScroll.getBoundingClientRect();
        const targetTop = section.getBoundingClientRect().top
          - scrollRect.top
          + guideScroll.scrollTop
          - 84;

        guideScroll.scrollTo({
          top: Math.max(0, targetTop),
          behavior: reducedMotion.matches ? 'auto' : 'smooth',
        });

        history.replaceState(null, '', `#${encodeURIComponent(section.id)}`);
      });
    });

    let tocFrame = 0;
    const updateActiveGuideSection = () => {
      tocFrame = 0;
      if (!guideSections.length) return;

      const rootTop = guideScroll.getBoundingClientRect().top;
      const activationLine = rootTop + Math.min(150, guideScroll.clientHeight * .2);
      let activeSection = guideSections[0].section;

      guideSections.forEach(({section}) => {
        if (section.getBoundingClientRect().top <= activationLine) {
          activeSection = section;
        }
      });

      const reachedBottom = guideScroll.scrollTop + guideScroll.clientHeight
        >= guideScroll.scrollHeight - 4;
      if (reachedBottom) {
        activeSection = guideSections[guideSections.length - 1].section;
      }

      setActiveGuideSection(activeSection);
    };

    const requestTocUpdate = () => {
      if (tocFrame) return;
      tocFrame = requestAnimationFrame(updateActiveGuideSection);
    };

    guideScroll.addEventListener('scroll', requestTocUpdate, {passive: true});
    window.addEventListener('resize', requestTocUpdate);
    updateActiveGuideSection();
  }

  const editor = document.querySelector('[data-guide-editor]');
    if (!editor) return;
  
    const dataNode = document.getElementById('guideEditorData');
    const editorData = JSON.parse(dataNode?.textContent || '{}');
    const labels = editorData.labels || {};
    let blocks = Array.isArray(editorData.blocks) ? editorData.blocks : [];
    const blocksRoot = editor.querySelector('[data-editor-blocks]');
    const emptyState = editor.querySelector('[data-editor-empty]');
    const blockCounts = [...editor.querySelectorAll('[data-block-count]')];
    const autosaveState = document.querySelector('[data-autosave-state]');
    const autosaveTime = document.querySelector('[data-autosave-time]');
    const coverInput = editor.querySelector('[data-cover-input]');
    const coverIdInput = editor.querySelector('[name="cover_media_id"]');
    const coverPreview = editor.querySelector('[data-cover-preview]');
    const coverUploadButton = editor.querySelector('[data-cover-upload]');
    const contentInput = editor.querySelector('[data-content-blocks-input]');
    const titleInput = editor.querySelector('[data-editor-title]');
    const summaryInput = editor.querySelector('[data-editor-summary]');
    const categoryInput = editor.querySelector('[data-editor-category]');
    const platformInput = editor.querySelector('[name="platform"]');
    const titleCount = editor.querySelector('[data-title-count]');
    const summaryCount = editor.querySelector('[data-summary-count]');
    const completionNode = editor.querySelector('[data-editor-completion]');
    const progressNode = editor.querySelector('[data-editor-progress]');
    const miniCover = editor.querySelector('[data-mini-cover]');
    const miniCategory = editor.querySelector('[data-mini-category]');
    const miniTitle = editor.querySelector('[data-mini-title]');
    const miniSummary = editor.querySelector('[data-mini-summary]');
    const tagsHidden = editor.querySelector('[name="tags_text"]');
    const tagList = editor.querySelector('[data-tag-list]');
    const tagEntry = editor.querySelector('[data-tag-entry]');
    const undoButton = editor.querySelector('[data-editor-undo]');
    const redoButton = editor.querySelector('[data-editor-redo]');
    const submitDialog = document.querySelector('[data-guide-submit-dialog]');
    const submitButton = editor.querySelector('[data-submit-guide]');
    const submitConfirm = submitDialog?.querySelector('[data-guide-submit-confirm]');
    let tags = String(tagsHidden?.value || '')
      .split(',')
      .map((tag) => tag.trim())
      .filter(Boolean)
      .slice(0, 8);
    let dirty = false;
    let autosaveTimer;
    let saveChain = Promise.resolve();
    let pendingSaves = 0;
    let undoStack = [];
    let redoStack = [];
    let editSnapshot = null;
  
    const escapeHtml = (value) => String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  
    const uid = () => `block_${Date.now().toString(36)}_${Math.random().toString(36).slice(2, 8)}`;
  
    const normalizeBlock = (block) => ({
      id: String(block?.id || uid()),
      type: String(block?.type || 'paragraph'),
      ...(block || {}),
    });
  
    blocks = blocks.map(normalizeBlock);
  
    const snapshotBlocks = () => JSON.stringify(blocks);
  
    const updateHistoryButtons = () => {
      if (undoButton) undoButton.disabled = undoStack.length === 0;
      if (redoButton) redoButton.disabled = redoStack.length === 0;
    };
  
    const rememberBlocks = () => {
      const snapshot = snapshotBlocks();
      if (undoStack[undoStack.length - 1] !== snapshot) {
        undoStack.push(snapshot);
        if (undoStack.length > 30) undoStack.shift();
      }
      redoStack = [];
      updateHistoryButtons();
    };
  
    const blockFields = (block) => {
      const text = escapeHtml(block.text || '');
  
      if (block.type === 'heading') {
        return `<label>${escapeHtml(labels.heading_level)}<select data-field="level"><option value="2"${Number(block.level || 2) === 2 ? ' selected' : ''}>H2</option><option value="3"${Number(block.level) === 3 ? ' selected' : ''}>H3</option><option value="4"${Number(block.level) === 4 ? ' selected' : ''}>H4</option></select></label><label>${escapeHtml(labels.text)}<input data-field="text" maxlength="180" value="${text}"></label>`;
      }
  
      if (block.type === 'paragraph') {
        return `<label>${escapeHtml(labels.text)}<textarea data-field="text" maxlength="5000">${text}</textarea></label>`;
      }
  
      if (block.type === 'steps' || block.type === 'list') {
        return `<label>${escapeHtml(labels.items)}<small>Ein Eintrag pro Zeile</small><textarea data-field="items" maxlength="12000">${escapeHtml((block.items || []).join('\n'))}</textarea></label>`;
      }
  
      if (block.type === 'image') {
        const preview = block.media_id
          ? `<img class="guide-editor-image-preview" src="/guides/media/${Number(block.media_id)}" alt="">`
          : '';
        return `${preview}<label>${escapeHtml(labels.image_upload)}<input type="file" accept="image/jpeg,image/png,image/webp" data-image-input></label><label>${escapeHtml(labels.caption)}<input data-field="caption" maxlength="240" value="${escapeHtml(block.caption || '')}"></label>`;
      }
  
      return `<label>${escapeHtml(labels.box_title)}<input data-field="title" maxlength="120" value="${escapeHtml(block.title || '')}"></label><label>${escapeHtml(labels.text)}<textarea data-field="text" maxlength="1500">${text}</textarea></label>`;
    };
  
    const updateEditorState = () => {
      const title = String(titleInput?.value || '');
      const summary = String(summaryInput?.value || '');
      const hasBasics = title.trim() !== '' && summary.trim() !== '';
      const hasClassification = String(categoryInput?.value || '') !== '' && String(platformInput?.value || '') !== '';
      const hasCover = Number(coverIdInput?.value || 0) > 0;
      const hasBlocks = blocks.length >= 3;
      const completed = [hasBasics, hasClassification, hasCover, hasBlocks].filter(Boolean).length;
      const completion = Math.round((completed / 4) * 100);
  
      if (titleCount) titleCount.textContent = String(title.length);
      if (summaryCount) summaryCount.textContent = String(summary.length);
      if (completionNode) completionNode.textContent = `${completion}%`;
      if (progressNode) progressNode.style.width = `${completion}%`;
  
      editor.querySelector('[data-nav-basics]')?.replaceChildren(document.createTextNode(hasBasics ? '✓' : '–'));
      editor.querySelector('[data-nav-cover]')?.replaceChildren(document.createTextNode(hasCover ? '✓' : '–'));
      blockCounts.forEach((node) => { node.textContent = String(blocks.length); });
  
      const states = [
        ['[data-check-basics]', hasBasics],
        ['[data-check-classification]', hasClassification],
        ['[data-check-cover]', hasCover],
        ['[data-check-blocks]', hasBlocks],
      ];
      states.forEach(([selector, state]) => editor.querySelector(selector)?.classList.toggle('done', state));
  
      if (miniTitle) miniTitle.textContent = title.trim() || 'Dein Guide-Titel';
      if (miniSummary) miniSummary.textContent = summary.trim() || 'Deine Kurzbeschreibung erscheint hier.';
      if (miniCategory) {
        miniCategory.textContent = categoryInput?.selectedOptions?.[0]?.textContent?.trim() || 'Keine Kategorie';
      }
    };
  
    const renderTags = () => {
      if (!tagList || !tagsHidden) return;
      tagList.innerHTML = tags.map((tag) => `
        <span data-tag="${escapeHtml(tag)}">
          ${escapeHtml(tag)}
          <button type="button" data-remove-tag aria-label="${escapeHtml(tag)} entfernen">×</button>
        </span>
      `).join('');
      tagsHidden.value = tags.join(', ');
    };
  
    const renderBlocks = () => {
      blocksRoot.querySelectorAll('[data-editor-block]').forEach((node) => node.remove());
      emptyState.hidden = blocks.length > 0;
  
      blocks.forEach((block, index) => {
        const node = document.createElement('article');
        const stateClass = block.type === 'steps'
          ? 'step'
          : (block.type === 'notice' || block.type === 'warning' ? block.type : '');
        node.className = `guide-editor-block ${stateClass} type-${block.type}`.trim();
        node.dataset.editorBlock = block.id;
        node.innerHTML = `
          <aside>
            <span class="guide-editor-block-grip" aria-hidden="true"><i class="ph ph-dots-six-vertical"></i></span>
            <span>${escapeHtml(labels[block.type] || block.type)}</span>
          </aside>
          <div class="guide-editor-block-fields">${blockFields(block)}</div>
          <nav class="guide-editor-block-actions">
            <button type="button" data-move-up title="${escapeHtml(labels.move_up)}" ${index === 0 ? 'disabled' : ''}><i class="ph ph-arrow-up"></i></button>
            <button type="button" data-move-down title="${escapeHtml(labels.move_down)}" ${index === blocks.length - 1 ? 'disabled' : ''}><i class="ph ph-arrow-down"></i></button>
            <button type="button" data-remove-block title="${escapeHtml(labels.remove)}"><i class="ph ph-trash"></i></button>
          </nav>`;
        blocksRoot.append(node);
      });
  
      contentInput.value = JSON.stringify(blocks);
      updateEditorState();
      updateHistoryButtons();
    };
  
    const collectBlock = (node) => {
      const block = blocks.find((candidate) => candidate.id === node.dataset.editorBlock);
      if (!block) return;
  
      node.querySelectorAll('[data-field]').forEach((input) => {
        const field = input.dataset.field;
        block[field] = field === 'level'
          ? Number(input.value)
          : field === 'items'
            ? input.value.split(/\r?\n/).map((item) => item.trim()).filter(Boolean)
            : input.value;
      });
  
      contentInput.value = JSON.stringify(blocks);
    };
  
    const formPayload = () => {
      blocksRoot.querySelectorAll('[data-editor-block]').forEach(collectBlock);
      return {
        title: titleInput?.value || '',
        summary: summaryInput?.value || '',
        category_id: categoryInput?.value || null,
        cover_media_id: coverIdInput.value || null,
        tags,
        language: editor.querySelector('[name="language"]')?.value || 'de',
        difficulty: editor.querySelector('[name="difficulty"]')?.value || 'beginner',
        platform: platformInput?.value || 'all',
        content_blocks: blocks,
      };
    };
  
    const setSaveState = (text, error = false) => {
      if (!autosaveState) return;
      autosaveState.textContent = text;
      autosaveState.style.color = error ? '#a9453c' : '';
    };
  
    const enqueueSave = (showToast = false) => {
      clearTimeout(autosaveTimer);
      const snapshot = formPayload();
      dirty = false;
      pendingSaves += 1;
      setSaveState(labels.saving);
  
      saveChain = saveChain
        .catch(() => undefined)
        .then(() => jsonRequest(editor.action, {
          method: 'PUT',
          body: JSON.stringify(snapshot),
        }))
        .then((payload) => {
          pendingSaves -= 1;
          if (pendingSaves === 0) {
            setSaveState(labels.saved);
            if (autosaveTime) {
              autosaveTime.textContent = new Intl.DateTimeFormat(
                document.documentElement.lang,
                {hour: '2-digit', minute: '2-digit'},
              ).format(new Date(payload.saved_at || Date.now()));
            }
          }
          if (showToast) toast(payload.message);
          return payload;
        })
        .catch((error) => {
          pendingSaves = Math.max(0, pendingSaves - 1);
          dirty = true;
          setSaveState(labels.save_failed, true);
          toast(error.message, true);
          throw error;
        });
  
      return saveChain;
    };
  
    const changed = () => {
      dirty = true;
      setSaveState(labels.saving);
      clearTimeout(autosaveTimer);
      autosaveTimer = setTimeout(() => enqueueSave(false), 900);
      updateEditorState();
    };
  
    const addTag = (value) => {
      const tag = String(value || '').trim().replace(/^#/, '').slice(0, 30);
      if (!tag || tags.some((item) => item.toLocaleLowerCase() === tag.toLocaleLowerCase())) return;
      if (tags.length >= 8) {
        toast('Maximal 8 Tags sind erlaubt.', true);
        return;
      }
      tags.push(tag);
      renderTags();
      if (tagEntry) tagEntry.value = '';
      changed();
    };
  
    editor.addEventListener('input', (event) => {
      const node = event.target.closest('[data-editor-block]');
      if (node) collectBlock(node);
      changed();
    });
  
    editor.addEventListener('change', (event) => {
      if (event.target.matches('[data-image-input], [data-cover-input]')) return;
      const node = event.target.closest('[data-editor-block]');
      if (node) collectBlock(node);
      changed();
    });
  
    blocksRoot.addEventListener('focusin', (event) => {
      if (!event.target.matches('[data-field]') || editSnapshot !== null) return;
      editSnapshot = snapshotBlocks();
    });
  
    blocksRoot.addEventListener('focusout', (event) => {
      const node = event.target.closest('[data-editor-block]');
      if (node) collectBlock(node);
      if (editSnapshot !== null && editSnapshot !== snapshotBlocks()) {
        undoStack.push(editSnapshot);
        if (undoStack.length > 30) undoStack.shift();
        redoStack = [];
        updateHistoryButtons();
      }
      editSnapshot = null;
    });
  
    editor.querySelectorAll('[data-add-block]').forEach((button) => {
      button.addEventListener('click', () => {
        rememberBlocks();
        const type = button.dataset.addBlock;
        blocks.push(normalizeBlock({
          type,
          ...(type === 'heading' ? {level: 2, text: ''} : {}),
          ...(type === 'paragraph' ? {text: ''} : {}),
          ...(['steps', 'list'].includes(type) ? {items: []} : {}),
          ...(type === 'image' ? {media_id: 0, caption: ''} : {}),
          ...(['notice', 'warning'].includes(type) ? {title: '', text: ''} : {}),
        }));
        renderBlocks();
        changed();
        blocksRoot.lastElementChild?.scrollIntoView({
          behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
          block: 'center',
        });
      });
    });
  
    blocksRoot.addEventListener('click', (event) => {
      const button = event.target.closest('button');
      const node = button?.closest('[data-editor-block]');
      if (!button || !node) return;
      const index = blocks.findIndex((block) => block.id === node.dataset.editorBlock);
      if (index < 0) return;
  
      collectBlock(node);
      rememberBlocks();
  
      if (button.hasAttribute('data-remove-block')) blocks.splice(index, 1);
      if (button.hasAttribute('data-move-up') && index > 0) {
        [blocks[index - 1], blocks[index]] = [blocks[index], blocks[index - 1]];
      }
      if (button.hasAttribute('data-move-down') && index < blocks.length - 1) {
        [blocks[index + 1], blocks[index]] = [blocks[index], blocks[index + 1]];
      }
  
      renderBlocks();
      changed();
    });
  
    undoButton?.addEventListener('click', () => {
      if (!undoStack.length) return;
      redoStack.push(snapshotBlocks());
      blocks = JSON.parse(undoStack.pop()).map(normalizeBlock);
      renderBlocks();
      changed();
    });
  
    redoButton?.addEventListener('click', () => {
      if (!redoStack.length) return;
      undoStack.push(snapshotBlocks());
      blocks = JSON.parse(redoStack.pop()).map(normalizeBlock);
      renderBlocks();
      changed();
    });
  
    tagEntry?.addEventListener('keydown', (event) => {
      if (event.key !== 'Enter' && event.key !== ',') return;
      event.preventDefault();
      addTag(tagEntry.value);
    });
  
    tagEntry?.addEventListener('blur', () => addTag(tagEntry.value));
  
    tagList?.addEventListener('click', (event) => {
      const button = event.target.closest('[data-remove-tag]');
      const tagNode = button?.closest('[data-tag]');
      if (!button || !tagNode) return;
      tags = tags.filter((tag) => tag !== tagNode.dataset.tag);
      renderTags();
      changed();
    });
  
    const uploadImage = async (file, kind) => {
      const body = new FormData();
      body.append('kind', kind);
      body.append('image', file);
      return jsonRequest(editor.dataset.mediaUrl, {method: 'POST', body});
    };
  
    blocksRoot.addEventListener('change', async (event) => {
      const input = event.target.closest('[data-image-input]');
      if (!input?.files?.[0]) return;
      const node = input.closest('[data-editor-block]');
      const block = blocks.find((candidate) => candidate.id === node?.dataset.editorBlock);
      if (!block) return;
  
      input.disabled = true;
      try {
        const payload = await uploadImage(input.files[0], 'content');
        rememberBlocks();
        block.media_id = payload.media.id;
        renderBlocks();
        changed();
      } catch (error) {
        toast(error.message, true);
        input.disabled = false;
      }
    });
  
    coverUploadButton?.addEventListener('click', () => coverInput?.click());
  
    coverInput?.addEventListener('change', async () => {
      if (!coverInput.files?.[0]) return;
      coverUploadButton.disabled = true;
  
      try {
        const payload = await uploadImage(coverInput.files[0], 'cover');
        coverIdInput.value = payload.media.id;
        coverPreview.innerHTML = `<img src="${escapeHtml(payload.media.url)}" alt="">`;
        if (miniCover) miniCover.innerHTML = `<img src="${escapeHtml(payload.media.url)}" alt="">`;
        coverUploadButton.lastChild.textContent = ' Bild ersetzen';
        changed();
      } catch (error) {
        toast(error.message, true);
      } finally {
        coverUploadButton.disabled = false;
        coverInput.value = '';
      }
    });
  
    const editorNavLinks = [...editor.querySelectorAll('[data-editor-nav] a[href^="#"]')];
    const editorScroll = document.getElementById('guidesScroll');
  
    if (editorNavLinks.length && editorScroll) {
      const sections = editorNavLinks
        .map((link) => ({link, section: document.getElementById(link.hash.slice(1))}))
        .filter(({section}) => section);
  
      const setActive = (activeSection) => {
        sections.forEach(({link, section}) => {
          const active = section === activeSection;
          link.classList.toggle('active', active);
          if (active) link.setAttribute('aria-current', 'location');
          else link.removeAttribute('aria-current');
        });
      };
  
      editorNavLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
          const section = document.getElementById(link.hash.slice(1));
          if (!section) return;
          event.preventDefault();
          setActive(section);
          const rootRect = editorScroll.getBoundingClientRect();
          const top = section.getBoundingClientRect().top - rootRect.top + editorScroll.scrollTop - 12;
          editorScroll.scrollTo({
            top: Math.max(0, top),
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
          });
          history.replaceState(null, '', link.hash);
        });
      });
  
      let frame = 0;
      const updateActive = () => {
        frame = 0;
        const line = editorScroll.getBoundingClientRect().top + 110;
        let activeSection = sections[0].section;
        sections.forEach(({section}) => {
          if (section.getBoundingClientRect().top <= line) activeSection = section;
        });
        setActive(activeSection);
      };
      editorScroll.addEventListener('scroll', () => {
        if (!frame) frame = requestAnimationFrame(updateActive);
      }, {passive: true});
      updateActive();
    }
  
    editor.addEventListener('submit', async (event) => {
      event.preventDefault();
      const button = editor.querySelector('[data-save-guide]');
      button.disabled = true;
      try {
        await enqueueSave(true);
      } finally {
        button.disabled = false;
      }
    });
  
    const submitGuide = async () => {
      if (!submitConfirm) return;
      submitConfirm.disabled = true;
  
      try {
        await enqueueSave(false);
        const payload = await jsonRequest(editor.dataset.submitUrl, {
          method: 'POST',
          body: JSON.stringify({}),
        });
        dirty = false;
        submitDialog?.close();
        toast(payload.message);
        setTimeout(() => { location.href = '/guides/mine'; }, 550);
      } catch (error) {
        toast(error.message, true);
        submitConfirm.disabled = false;
      }
    };
  
    submitButton?.addEventListener('click', () => {
      if (submitDialog?.showModal) {
        submitDialog.showModal();
        return;
      }
      if (confirm(labels.submit_confirm)) submitGuide();
    });
  
    submitConfirm?.addEventListener('click', submitGuide);
    submitDialog?.querySelector('[data-guide-dialog-close]')?.addEventListener('click', () => submitDialog.close());
    submitDialog?.addEventListener('click', (event) => {
      if (event.target === submitDialog) submitDialog.close();
    });
  
    window.addEventListener('beforeunload', (event) => {
      if (!dirty && pendingSaves === 0) return;
      event.preventDefault();
      event.returnValue = labels.unsaved_warning;
    });
  
    renderTags();
    renderBlocks();
    updateEditorState();
})();
