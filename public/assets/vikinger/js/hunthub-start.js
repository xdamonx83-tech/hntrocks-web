document.documentElement.classList.add('hh-js-ready');

const hhI18n = window.HH_I18N || {};

function hhT(key, fallback, replacements = {}) {
  let value = hhI18n[key] || fallback || key;

  Object.entries(replacements).forEach(([name, replacement]) => {
    value = String(value).replaceAll(`:${name}`, replacement).replaceAll(`{${name}}`, replacement);
  });

  return value;
}

function hhPluralLabel(count, singularKey, pluralKey, singularFallback, pluralFallback) {
  return Number(count) === 1 ? hhT(singularKey, singularFallback) : hhT(pluralKey, pluralFallback);
}


const hhReactionLabels = {
  like: 'Like',
  love: 'Love',
  dislike: 'Dislike',
  happy: 'Happy',
  funny: 'Funny',
  wow: 'Wow',
  angry: 'Angry',
  sad: 'Sad'
};

const hhReactionAsset = (type) => `/assets/vikinger/img/reaction/${type || 'like'}.png`;

function hhSetCurrentReactionIcon(postId, type, reacted) {
  document.querySelectorAll(`[data-hh-current-reaction-icon="${postId}"]`).forEach((iconWrap) => {
    if (reacted && type) {
      iconWrap.innerHTML = `<img class="hh-current-reaction-image" src="${hhReactionAsset(type)}" alt="${hhReactionLabels[type] || 'Reaction'}">`;
      return;
    }

    iconWrap.innerHTML = '<svg class="post-option-icon icon-thumbs-up"><use xlink:href="#svg-thumbs-up"></use></svg>';
  });
}

function hhSetCurrentCommentReactionIcon(commentId, type, reacted) {
  document.querySelectorAll(`[data-hh-comment-current-reaction-icon="${commentId}"]`).forEach((iconWrap) => {
    if (reacted && type) {
      iconWrap.innerHTML = `<img class="hh-comment-current-reaction-image" src="${hhReactionAsset(type)}" alt="${hhReactionLabels[type] || 'Reaction'}">`;
      return;
    }

    iconWrap.innerHTML = '';
  });
}

function hhUpdateReactionStats(stats) {
  if (!stats || typeof stats !== 'object') {
    return;
  }

  ['like', 'love', 'happy', 'wow'].forEach((type) => {
    document.querySelectorAll(`[data-hh-reaction-stat="${type}"] .reaction-stat-title`).forEach((node) => {
      node.textContent = String(Number(stats[type] || 0));
    });
  });
}

function hhCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

async function hhPostFormUrlencoded(url, data) {
  const token = hhCsrfToken();

  if (!url || !token) {
    throw new Error('Missing URL or CSRF token');
  }

  const body = new URLSearchParams();
  Object.entries(data).forEach(([key, value]) => body.set(key, value));

  const response = await fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'X-CSRF-TOKEN': token,
      'X-Requested-With': 'XMLHttpRequest',
      'Accept': 'application/json',
      'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
    },
    body
  });

  if (!response.ok) {
    throw new Error('Request failed');
  }

  return response.json();
}

async function hhPostForm(form) {
  const token = hhCsrfToken();
  const url = form.getAttribute('action');

  if (!url || !token) {
    throw new Error('Missing URL or CSRF token');
  }

  const response = await fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'X-CSRF-TOKEN': token,
      'X-Requested-With': 'XMLHttpRequest',
      'Accept': 'application/json'
    },
    body: new FormData(form)
  });

  if (!response.ok) {
    throw new Error('Request failed');
  }

  return response.json();
}

function hhNodeFromHtml(html) {
  const template = document.createElement('template');
  template.innerHTML = String(html || '').trim();
  return template.content.firstElementChild;
}

function hhGetVikingerApp() {
  try {
    if (typeof app !== 'undefined' && app && app.plugins && typeof app.plugins.createHexagon === 'function') {
      return app;
    }
  } catch (error) {
    // Keep media modal interactions alive even if the Vikinger global is not ready.
  }

  try {
    if (window.app && window.app.plugins && typeof window.app.plugins.createHexagon === 'function') {
      return window.app;
    }
  } catch (error) {
    // window.app is optional because Vikinger declares app as a lexical global.
  }

  return null;
}

function hhRenderVikingerHexagon(node, options) {
  if (!node) {
    return;
  }

  const fallbackImage = () => {
    const src = node.getAttribute('data-src');
    if (src && !node.querySelector('canvas')) {
      node.style.backgroundImage = `url("${src}")`;
      node.style.backgroundSize = 'cover';
      node.style.backgroundPosition = 'center';
    }
  };

  const vikingerApp = hhGetVikingerApp();

  if (!vikingerApp) {
    fallbackImage();
    return;
  }

  try {
    node.querySelectorAll?.('canvas, .hh-comment-avatar-fallback')?.forEach((child) => child.remove());
    node.style.backgroundImage = '';
    node.style.backgroundSize = '';
    node.style.backgroundPosition = '';

    vikingerApp.plugins.createHexagon({
      ...options,
      containerElement: node
    });
  } catch (error) {
    fallbackImage();
  }
}

function hhHydrateInsertedVikingerMedia(root) {
  if (!root) {
    return;
  }

  const selectors = [
    '.hexagon-image-30-32',
    '.hexagon-progress-40-44',
    '.hexagon-border-40-44',
    '.hexagon-22-24',
    '.hexagon-dark-16-18'
  ];

  const nodes = [];
  selectors.forEach((selector) => {
    if (root.matches?.(selector)) {
      nodes.push(root);
    }

    root.querySelectorAll?.(selector)?.forEach((node) => nodes.push(node));
  });

  [...new Set(nodes)].forEach((node) => {
    const className = String(node.className || '');

    if (className.includes('hexagon-image-30-32')) {
      hhRenderVikingerHexagon(node, {
        width: 30,
        height: 32,
        roundedCorners: true,
        roundedCornerRadius: 1,
        clip: true
      });
      return;
    }

    if (className.includes('hexagon-progress-40-44')) {
      hhRenderVikingerHexagon(node, {
        width: 40,
        height: 44,
        lineWidth: 3,
        roundedCorners: true,
        roundedCornerRadius: 1,
        gradient: {
          colors: ['#d9ff65', '#40d04f']
        },
        scale: {
          start: 0,
          end: 1,
          stop: .8
        }
      });
      return;
    }

    if (className.includes('hexagon-border-40-44')) {
      hhRenderVikingerHexagon(node, {
        width: 40,
        height: 44,
        lineWidth: 3,
        roundedCorners: true,
        roundedCornerRadius: 1,
        lineColor: '#293249'
      });
      return;
    }

    if (className.includes('hexagon-22-24')) {
      hhRenderVikingerHexagon(node, {
        width: 22,
        height: 24,
        roundedCorners: true,
        roundedCornerRadius: 1,
        lineColor: '#1d2333',
        fill: true
      });
      return;
    }

    if (className.includes('hexagon-dark-16-18')) {
      hhRenderVikingerHexagon(node, {
        width: 16,
        height: 18,
        roundedCorners: true,
        roundedCornerRadius: 1,
        lineColor: '#7750f8',
        fill: true
      });
    }
  });
}


function hhApplyInsertedVikingerMediaFallback(root) {
  if (!root) {
    return;
  }

  const avatarImages = [];

  if (root.matches?.('.hexagon-image-30-32[data-src]')) {
    avatarImages.push(root);
  }

  root.querySelectorAll?.('.hexagon-image-30-32[data-src]')?.forEach((node) => avatarImages.push(node));

  [...new Set(avatarImages)].forEach((node) => {
    const src = node.getAttribute('data-src');

    if (!src) {
      return;
    }

    node.querySelectorAll?.('canvas, .hh-comment-avatar-fallback')?.forEach((child) => child.remove());
    node.style.backgroundImage = `url("${src}")`;
    node.style.backgroundSize = 'cover';
    node.style.backgroundPosition = 'center';
  });
}

function hhUpdatePostCommentCount(postId, label) {
  document.querySelectorAll(`[data-hh-post-comment-count="${postId}"]`).forEach((node) => {
    node.textContent = label;
  });
}

function hhIncrementPostCommentCount(postId, delta = 1) {
  document.querySelectorAll(`[data-hh-post-comment-count="${postId}"]`).forEach((node) => {
    const current = Number(String(node.textContent || '').match(/\d+/)?.[0] || 0);
    const next = Math.max(0, current + Number(delta || 0));
    node.textContent = `${next} ${hhPluralLabel(next, 'comment_singular', 'comment_plural', 'Kommentar', 'Kommentare')}`;
  });
}

function hhResetInlineReply(postId) {
  document.querySelectorAll(`[data-hh-comment-reply][data-post-id="${postId}"]`).forEach((button) => {
    button.classList.remove('is-active');
    button.textContent = hhT('reply', 'Reply');
  });

  document.querySelectorAll('[data-hh-comment-inline-composer]').forEach((node) => {
    node.hidden = true;
  });
}

function hhCommentEscapeHtml(value) {
  return String(value || '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}


function hhBuildCommentNodeFromPayload(payload, fallbackPostId) {
  const comment = payload?.comment;

  if (!comment || !comment.id) {
    return null;
  }

  const postId = String(comment.post_id || fallbackPostId || '');
  const commentId = String(comment.id);
  const rootId = String(comment.root_id || payload.root_id || comment.id);
  const isReply = Boolean(payload.is_reply || comment.is_reply || comment.parent_id);
  const user = comment.user || {};
  const viewer = payload.viewer || {};
  const routes = comment.routes || {};
  const mentionContext = hhCommentEscapeHtml(comment.mention_context || 'feed_comment');
  const mentionTeamId = comment.mention_team_id ? hhCommentEscapeHtml(comment.mention_team_id) : '';
  const mentionTeamAttr = mentionTeamId ? ` data-hh-mention-team-id="${mentionTeamId}"` : '';
  const userName = hhCommentEscapeHtml(user.name || 'User');
  const profileUrl = hhCommentEscapeHtml(user.profile_url || '#');
  const avatarUrl = hhCommentEscapeHtml(user.avatar_url || '/assets/vikinger/img/default-avatar.svg');
  const userLevel = hhCommentEscapeHtml(user.level || '1');
  const viewerAvatarUrl = hhCommentEscapeHtml(viewer.avatar_url || avatarUrl || '/assets/vikinger/img/default-avatar.svg');
  const viewerLevel = hhCommentEscapeHtml(viewer.level || '1');
  const bodyHtml = comment.body_html || hhCommentEscapeHtml(comment.body || '').replace(/\n/g, '<br>');
  const bodyAttr = hhCommentEscapeHtml(comment.body || '');
  const createdAtLabel = hhCommentEscapeHtml(comment.created_at_label || hhT('just_now', 'gerade eben'));
  const reactionUrl = hhCommentEscapeHtml(routes.reaction || '');
  const updateUrl = hhCommentEscapeHtml(routes.update || '');
  const deleteUrl = hhCommentEscapeHtml(routes.delete || '');
  const storeUrl = hhCommentEscapeHtml(routes.store || '');
  const canEdit = Boolean(comment.can_edit && updateUrl);
  const canDelete = Boolean(comment.can_delete && deleteUrl);
  const canManage = canEdit || canDelete;
  const itemClass = isReply ? 'reply-2 hh-vk-comment-reply' : 'hh-vk-comment-root';
  const rootAttr = isReply ? '' : commentId;
  const replyTarget = `post-reply-comment-${rootId}`;
  const translation = comment.translation || {};
  const translationUrl = hhCommentEscapeHtml(translation.url || routes.translation || '');
  const translationLocale = hhCommentEscapeHtml(translation.target_locale || document.documentElement?.lang?.slice(0, 2) || 'de');
  const translationMarkup = (translation.should_offer && translationUrl) ? `
        <div class="hh-feed-translation hh-feed-translation--comment" data-hh-translation-wrap>
          <button class="hh-feed-translation-toggle" type="button" data-hh-translation-trigger data-url="${translationUrl}" data-locale="${translationLocale}" data-label-default="${hhCommentEscapeHtml(translation.show_label || hhT('translation_show', 'Übersetzung anzeigen'))}" data-label-loading="${hhCommentEscapeHtml(translation.loading_label || hhT('translation_loading', 'Wird übersetzt...'))}" data-label-error="${hhCommentEscapeHtml(translation.error_label || hhT('translation_error', 'Übersetzung fehlgeschlagen'))}">
            <i class="hh-ph-action-icon ph ph-translate" aria-hidden="true"></i>
            <span>${hhCommentEscapeHtml(translation.show_label || hhT('translation_show', 'Übersetzung anzeigen'))}</span>
          </button>
          <div class="hh-feed-translation-result" data-hh-translation-result hidden></div>
        </div>` : '';

  const reactionOptions = Object.entries(hhReactionLabels).map(([reaction, label]) => `
                    <button class="reaction-option text-tooltip-tft hh-reaction-option-button hh-comment-reaction-option-button" data-title="${hhCommentEscapeHtml(label)}" type="button" data-hh-comment-reaction-action data-comment-id="${commentId}" data-url="${reactionUrl}" data-type="${hhCommentEscapeHtml(reaction)}" data-mode="set">
                      <img class="reaction-option-image" src="${hhReactionAsset(reaction)}" alt="${hhCommentEscapeHtml(label)}">
                    </button>`).join('');

  const settingsMarkup = canManage ? `
                <div class="meta-line settings hh-comment-settings">
                  <div class="post-settings-wrap hh-comment-settings-wrap">
                    <button class="post-settings hh-comment-settings-toggle" type="button" aria-haspopup="true" aria-expanded="false" data-hh-comment-settings-toggle>
                      <svg class="post-settings-icon icon-more-dots"><use xlink:href="#svg-more-dots"></use></svg>
                    </button>
                    <div class="simple-dropdown hh-comment-settings-dropdown" data-hh-comment-settings-menu>
                      ${canEdit ? `<button class="simple-dropdown-link hh-comment-settings-action" type="button" data-hh-comment-edit data-comment-id="${commentId}" data-url="${updateUrl}" data-current-body="${bodyAttr}">${hhT('edit', 'Bearbeiten')}</button>` : ''}
                      ${canDelete ? `<form method="post" action="${deleteUrl}" data-hh-comment-delete-form data-comment-id="${commentId}" data-post-id="${postId}">
                        <input type="hidden" name="_token" value="${hhCommentEscapeHtml(hhCsrfToken())}">
                        <input type="hidden" name="_method" value="DELETE">
                        <button class="simple-dropdown-link hh-comment-settings-action" type="submit">${hhT('delete', 'Löschen')}</button>
                      </form>` : ''}
                    </div>
                  </div>
                </div>` : '';

  const replyComposerMarkup = !isReply ? `
        <div class="hh-comment-reply-slot" data-hh-comment-reply-slot="${commentId}">
          <div class="post-comment-form hh-vk-reply-first hh-vk-inline-reply-form" data-hh-comment-inline-composer="${commentId}" hidden>
            <div class="user-avatar small no-outline">
              <div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="${viewerAvatarUrl}"></div></div>
              <div class="user-avatar-progress"><div class="hexagon-progress-40-44"></div></div>
              <div class="user-avatar-progress-border"><div class="hexagon-border-40-44"></div></div>
              <div class="user-avatar-badge">
                <div class="user-avatar-badge-border"><div class="hexagon-22-24"></div></div>
                <div class="user-avatar-badge-content"><div class="hexagon-dark-16-18"></div></div>
                <p class="user-avatar-badge-text">${viewerLevel}</p>
              </div>
            </div>

            <form class="form hh-vk-inline-reply-form-inner" method="post" action="${storeUrl}" data-hh-comment-form="${postId}-${commentId}" data-post-id="${postId}" data-root-id="${commentId}">
              <input type="hidden" name="_token" value="${hhCommentEscapeHtml(hhCsrfToken())}">
              <input type="hidden" name="parent_id" value="${commentId}" data-hh-comment-parent="${postId}-${commentId}">
              <p class="hh-comment-reply-context" data-hh-inline-reply-context="${commentId}" hidden>${hhT('reply_to', 'Antwort auf :name', { name: userName })}</p>
              <div class="form-row">
                <div class="form-item">
                  <div class="form-input small">
                    <label for="post-reply-comment-${commentId}">Your Reply</label>
                    <input type="text" id="post-reply-comment-${commentId}" name="body" maxlength="2000" required data-hh-comment-counter="post-reply-comment-count-${commentId}" data-hh-mention-context="${mentionContext}"${mentionTeamAttr}>
                  </div>
                </div>
              </div>
              <div class="hh-vk-inline-reply-meta">
                <span class="hh-vk-inline-reply-count" id="post-reply-comment-count-${commentId}">2000/2000</span>
              </div>
            </form>
          </div>
        </div>

        <div class="hh-vk-comment-replies" data-hh-comment-replies-root="${commentId}"></div>` : '';

  return hhNodeFromHtml(`
    <div id="comment-${commentId}" class="post-comment hh-vk-comment-item-fixed ${itemClass}" data-hh-comment-id="${commentId}" data-hh-comment-root="${rootAttr}" data-hh-mention-context="${mentionContext}"${mentionTeamAttr}>
      <a class="user-avatar small no-outline" href="${profileUrl}">
        <div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="${avatarUrl}"></div></div>
        <div class="user-avatar-progress"><div class="hexagon-progress-40-44"></div></div>
        <div class="user-avatar-progress-border"><div class="hexagon-border-40-44"></div></div>
        <div class="user-avatar-badge">
          <div class="user-avatar-badge-border"><div class="hexagon-22-24"></div></div>
          <div class="user-avatar-badge-content"><div class="hexagon-dark-16-18"></div></div>
          <p class="user-avatar-badge-text">${userLevel}</p>
        </div>
      </a>

      <div class="hh-vk-comment-main">
        <p class="post-comment-text"><a class="post-comment-text-author" href="${profileUrl}">${userName}</a> <span data-hh-comment-body-text="${commentId}">${bodyHtml}</span></p>
        ${translationMarkup}

        <div class="content-actions">
          <div class="content-action hh-comment-action-row">
            <div class="meta-line hh-comment-reaction-hover-zone">
              <button class="meta-line-link light hh-comment-reaction-button" type="button" data-hh-comment-reaction-action data-hh-comment-reaction-main="${commentId}" data-comment-id="${commentId}" data-url="${reactionUrl}" data-type="like" data-mode="toggle" data-current-type="">
                <span class="hh-comment-current-reaction-icon" data-hh-comment-current-reaction-icon="${commentId}"></span>
                <span data-hh-comment-reaction-label="${commentId}">Like</span>
                <span class="hh-comment-reaction-count" data-hh-comment-reaction-count="${commentId}"></span>
              </button>

              <div class="reaction-options reaction-options-dropdown hh-reaction-options-picker hh-comment-reaction-options-picker">
                ${reactionOptions}
              </div>
            </div>

            <div class="meta-line"><button class="meta-line-link light hh-comment-reply-button" type="button" data-hh-comment-reply data-post-id="${postId}" data-parent-id="${rootId}" data-root-id="${rootId}" data-target="${replyTarget}" data-user-name="${userName}">${hhT('reply', 'Reply')}</button></div>
            <div class="meta-line"><p class="meta-line-timestamp">${createdAtLabel}</p></div>
            ${settingsMarkup}
          </div>
        </div>

        ${replyComposerMarkup}
      </div>
    </div>
  `);
}

function hhUiLangStartsWith(prefix) {
  return String(document.documentElement?.lang || '').toLowerCase().startsWith(prefix);
}

function hhCommentRepliesLabel(count) {
  const amount = Math.max(0, Number(count || 0));
  const safeAmount = hhCommentEscapeHtml(amount);
  const countMarkup = `<span class="hh-comment-load-replies-count">${safeAmount}</span>`;
  const label = hhPluralLabel(amount, 'reply_singular', 'reply_plural', 'Antwort', 'Antworten');
  return hhT('load_replies', '{count} {label} laden...', { count: countMarkup, label });
}

function hhCommentRepliesHideLabel(count) {
  const amount = Math.max(0, Number(count || 0));
  const safeAmount = hhCommentEscapeHtml(amount);
  const countMarkup = `<span class="hh-comment-load-replies-count">${safeAmount}</span>`;
  const label = hhPluralLabel(amount, 'reply_singular', 'reply_plural', 'Antwort', 'Antworten');
  return hhT('hide_replies', '{count} {label} ausblenden', { count: countMarkup, label });
}

function hhCommentMoreLabel(count) {
  const amount = Math.max(0, Number(count || 0));
  const safeAmount = hhCommentEscapeHtml(amount);
  const countMarkup = `<span class="hh-comment-load-replies-count">${safeAmount}</span>`;
  const label = hhPluralLabel(amount, 'comment_singular', 'comment_plural', 'Kommentar', 'Kommentare');
  return hhT('load_more_comments', 'Weitere {count} {label} laden...', { count: countMarkup, label });
}

function hhCommentMoreHideLabel(count) {
  const amount = Math.max(0, Number(count || 0));
  const safeAmount = hhCommentEscapeHtml(amount);
  const countMarkup = `<span class="hh-comment-load-replies-count">${safeAmount}</span>`;
  const label = hhPluralLabel(amount, 'comment_singular', 'comment_plural', 'Kommentar', 'Kommentare');
  return hhT('hide_more_comments', '{count} weitere {label} ausblenden', { count: countMarkup, label });
}

function hhDirectReplyCount(repliesWrap) {
  if (!repliesWrap) {
    return 0;
  }

  return Array.from(repliesWrap.children).filter((child) => child.classList?.contains('hh-vk-comment-item-fixed')).length;
}

function hhUpdateCommentReplyLoader(rootId, repliesWrap) {
  if (!rootId || !repliesWrap) {
    return;
  }

  const count = hhDirectReplyCount(repliesWrap);

  if (count < 1) {
    return;
  }

  let button = document.querySelector(`[data-hh-load-comment-replies][data-replies-root="${rootId}"]`);

  if (!button) {
    button = document.createElement('button');
    button.className = 'hh-comment-load-replies';
    button.type = 'button';
    button.setAttribute('data-hh-load-comment-replies', '');
    button.setAttribute('data-replies-root', rootId);
    button.setAttribute('aria-expanded', 'false');
    repliesWrap.parentNode?.insertBefore(button, repliesWrap);
  }

  button.hidden = false;
  button.setAttribute('data-reply-count', String(count));

  if (repliesWrap.classList.contains('is-expanded')) {
    button.setAttribute('aria-expanded', 'true');
    button.innerHTML = hhCommentRepliesHideLabel(count);
    return;
  }

  button.setAttribute('aria-expanded', 'false');
  button.innerHTML = hhCommentRepliesLabel(count);
  repliesWrap.hidden = true;
  repliesWrap.classList.add('is-collapsed');
  repliesWrap.classList.remove('is-expanded');
}

function hhCloseAllCommentSettings() {
  document.querySelectorAll('.hh-comment-settings-wrap.is-open').forEach((node) => {
    node.classList.remove('is-open');
    node.querySelector('[data-hh-comment-settings-toggle]')?.setAttribute('aria-expanded', 'false');
  });
}

function hhCloseInlineCommentEdit(editForm) {
  if (!editForm) {
    return;
  }

  const commentId = editForm.getAttribute('data-comment-id');
  const bodyTarget = commentId ? document.querySelector(`[data-hh-comment-body-text="${commentId}"]`) : null;
  const textWrap = bodyTarget?.closest('.post-comment-text');

  if (bodyTarget) {
    bodyTarget.hidden = false;
  }

  textWrap?.classList.remove('hh-comment-text-is-editing');
  editForm.remove();
}

function hhCloseAllInlineCommentEdits() {
  document.querySelectorAll('[data-hh-comment-inline-edit]').forEach((editForm) => {
    hhCloseInlineCommentEdit(editForm);
  });
}

function hhOpenInlineCommentEdit(button) {
  const commentId = button.getAttribute('data-comment-id');
  const url = button.getAttribute('data-url');
  const currentBody = button.getAttribute('data-current-body') || '';

  hhCloseAllCommentSettings();

  if (!commentId || !url) {
    return;
  }

  const comment = document.querySelector(`[data-hh-comment-id="${commentId}"]`);
  const bodyTarget = document.querySelector(`[data-hh-comment-body-text="${commentId}"]`);

  if (!comment || !bodyTarget) {
    return;
  }

  const mentionContext = comment.getAttribute('data-hh-mention-context') || 'feed_comment';
  const mentionTeamId = comment.getAttribute('data-hh-mention-team-id') || '';
  const mentionTeamAttr = mentionTeamId ? ` data-hh-mention-team-id="${hhCommentEscapeHtml(mentionTeamId)}"` : '';

  const existing = comment.querySelector('[data-hh-comment-inline-edit]');
  if (existing) {
    existing.querySelector('[name="body"]')?.focus();
    return;
  }

  hhCloseAllInlineCommentEdits();

  const textWrap = bodyTarget.closest('.post-comment-text');
  bodyTarget.hidden = true;
  textWrap?.classList.add('hh-comment-text-is-editing');

  const editForm = document.createElement('form');
  editForm.className = 'hh-comment-inline-edit';
  editForm.setAttribute('data-hh-comment-inline-edit', '');
  editForm.setAttribute('data-comment-id', commentId);
  editForm.setAttribute('data-url', url);
  editForm.innerHTML = `
    <div class="hh-comment-inline-edit-box">
      <textarea name="body" maxlength="50000" rows="4" required data-hh-mention-context="${hhCommentEscapeHtml(mentionContext)}"${mentionTeamAttr}></textarea>
    </div>
    <div class="hh-comment-inline-edit-meta">
      <span class="hh-comment-inline-edit-count" data-hh-comment-edit-count>0/50000</span>
      <div class="hh-comment-inline-edit-actions">
        <button class="meta-line-link light hh-comment-inline-edit-cancel" type="button" data-hh-comment-edit-cancel>${hhT('cancel', 'Abbrechen')}</button>
        <button class="meta-line-link light hh-comment-inline-edit-save" type="submit" data-hh-comment-edit-save>${hhT('save', 'Speichern')}</button>
      </div>
    </div>
  `;

  textWrap?.insertAdjacentElement('afterend', editForm);

  const textarea = editForm.querySelector('[name="body"]');
  const counter = editForm.querySelector('[data-hh-comment-edit-count]');
  const updateCounter = () => {
    if (counter && textarea) {
      counter.textContent = `${textarea.value.length}/50000`;
    }
  };

  if (textarea) {
    textarea.value = currentBody;
    updateCounter();
    textarea.addEventListener('input', updateCounter);
    textarea.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        event.preventDefault();
        hhCloseInlineCommentEdit(editForm);
      }

      if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
        event.preventDefault();
        editForm.requestSubmit();
      }
    });
    textarea.focus();
    textarea.setSelectionRange(textarea.value.length, textarea.value.length);
  }
}

let hhPendingCommentDeleteForm = null;
let hhCommentDeleteModal = null;

function hhEnsureCommentDeleteModal() {
  if (hhCommentDeleteModal) {
    return hhCommentDeleteModal;
  }

  hhCommentDeleteModal = document.createElement('div');
  hhCommentDeleteModal.className = 'hh-comment-delete-modal';
  hhCommentDeleteModal.setAttribute('aria-hidden', 'true');
  hhCommentDeleteModal.innerHTML = `
    <div class="hh-comment-delete-modal-backdrop" data-hh-comment-delete-cancel></div>
    <div class="hh-comment-delete-modal-box" role="dialog" aria-modal="true" aria-labelledby="hh-comment-delete-title">
      <button class="hh-comment-delete-modal-close" type="button" aria-label="${hhT('close', 'Schließen')}" data-hh-comment-delete-cancel>
        <svg><use xlink:href="#svg-cross"></use></svg>
      </button>
      <div class="hh-comment-delete-modal-icon">
        <svg><use xlink:href="#svg-cross"></use></svg>
      </div>
      <p class="hh-comment-delete-modal-title" id="hh-comment-delete-title">${hhT('comment_delete_title', 'Kommentar löschen')}</p>
      <p class="hh-comment-delete-modal-text">${hhT('comment_delete_text', 'Möchtest du diesen Kommentar wirklich löschen?')}</p>
      <div class="hh-comment-delete-modal-actions">
        <button class="button secondary hh-comment-delete-modal-cancel" type="button" data-hh-comment-delete-cancel>${hhT('cancel', 'Abbrechen')}</button>
        <button class="button primary hh-comment-delete-modal-accept" type="button" data-hh-comment-delete-accept>${hhT('delete', 'Löschen')}</button>
      </div>
    </div>
  `;

  document.body.appendChild(hhCommentDeleteModal);
  return hhCommentDeleteModal;
}

function hhOpenCommentDeleteModal(form) {
  hhPendingCommentDeleteForm = form;
  hhCloseAllCommentSettings();

  const modal = hhEnsureCommentDeleteModal();
  modal.classList.add('is-visible');
  modal.setAttribute('aria-hidden', 'false');
  document.body.classList.add('hh-comment-delete-modal-open');
}

function hhCloseCommentDeleteModal() {
  if (!hhCommentDeleteModal) {
    return;
  }

  hhCommentDeleteModal.classList.remove('is-visible');
  hhCommentDeleteModal.setAttribute('aria-hidden', 'true');
  document.body.classList.remove('hh-comment-delete-modal-open');
  hhPendingCommentDeleteForm = null;
}

async function hhConfirmCommentDelete() {
  const form = hhPendingCommentDeleteForm;
  const modal = hhEnsureCommentDeleteModal();
  const accept = modal.querySelector('[data-hh-comment-delete-accept]');

  if (!form) {
    hhCloseCommentDeleteModal();
    return;
  }

  accept?.classList.add('is-loading');

  try {
    const payload = await hhPostForm(form);
    const commentId = payload.id || form.getAttribute('data-comment-id');
    const postId = payload.post_id || form.getAttribute('data-post-id');
    const commentNode = commentId ? document.querySelector(`[data-hh-comment-id="${commentId}"]`) : form.closest('[data-hh-comment-id]');

    commentNode?.remove();

    if (postId && payload.comment_count_label) {
      hhUpdatePostCommentCount(postId, payload.comment_count_label);
    } else if (postId && payload.comment_count_delta) {
      hhIncrementPostCommentCount(postId, payload.comment_count_delta);
    }

    hhCloseCommentDeleteModal();
  } catch (error) {
    console.error(error);
    form.submit();
  } finally {
    accept?.classList.remove('is-loading');
  }
}



function hhInitWidgetSliders() {
  document.querySelectorAll('[data-hh-widget-slider]').forEach((slider) => {
    const track = slider.querySelector('[data-hh-slider-track]');
    const slides = Array.from(track?.children || []);
    const prev = slider.querySelector('[data-hh-slider-prev]');
    const next = slider.querySelector('[data-hh-slider-next]');

    if (!track || slides.length === 0) {
      return;
    }

    let index = 0;

    const update = () => {
      track.style.transform = `translateX(${-index * 100}%)`;
      slides.forEach((slide, slideIndex) => slide.classList.toggle('is-active', slideIndex === index));
      const disabled = slides.length <= 1;
      prev?.classList.toggle('is-disabled', disabled);
      next?.classList.toggle('is-disabled', disabled);
      prev?.setAttribute('aria-disabled', disabled ? 'true' : 'false');
      next?.setAttribute('aria-disabled', disabled ? 'true' : 'false');
    };

    prev?.addEventListener('click', (event) => {
      event.preventDefault();
      if (slides.length <= 1) return;
      index = (index - 1 + slides.length) % slides.length;
      update();
    });

    next?.addEventListener('click', (event) => {
      event.preventDefault();
      if (slides.length <= 1) return;
      index = (index + 1) % slides.length;
      update();
    });

    update();
  });
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('a[href="#"]').forEach((link) => {
    link.addEventListener('click', (event) => event.preventDefault());
  });

  hhInitWidgetSliders();
});

document.addEventListener('submit', async (event) => {
  const editForm = event.target.closest('[data-hh-comment-inline-edit]');

  if (!editForm) {
    return;
  }

  event.preventDefault();

  const commentId = editForm.getAttribute('data-comment-id');
  const url = editForm.getAttribute('data-url');
  const textarea = editForm.querySelector('[name="body"]');
  const body = textarea?.value.trim() || '';

  if (!commentId || !url || !textarea || !body) {
    textarea?.focus();
    return;
  }

  editForm.classList.add('is-loading');

  try {
    const payload = await hhPostFormUrlencoded(url, {_method: 'PATCH', body});
    const html = payload.body_html || hhCommentEscapeHtml(body).replaceAll('\n', '<br>');

    document.querySelectorAll(`[data-hh-comment-body-text="${commentId}"]`).forEach((node) => {
      node.innerHTML = html;
      node.hidden = false;
      node.closest('.post-comment-text')?.classList.remove('hh-comment-text-is-editing');
    });

    document.querySelectorAll(`[data-hh-comment-edit][data-comment-id="${commentId}"]`).forEach((node) => {
      node.setAttribute('data-current-body', payload.body || body);
    });

    hhCloseInlineCommentEdit(editForm);
  } catch (error) {
    console.error(error);
  } finally {
    editForm.classList.remove('is-loading');
  }
});

document.addEventListener('submit', (event) => {
  const deleteForm = event.target.closest('[data-hh-comment-delete-form]');

  if (!deleteForm) {
    return;
  }

  event.preventDefault();
  hhOpenCommentDeleteModal(deleteForm);
});

document.addEventListener('submit', async (event) => {
  const commentForm = event.target.closest('[data-hh-comment-form]');

  if (!commentForm) {
    return;
  }

  event.preventDefault();

  const postId = commentForm.getAttribute('data-post-id') || commentForm.getAttribute('data-hh-comment-form')?.split('-')[0] || '';
  const rootId = commentForm.getAttribute('data-root-id') || '';
  const input = commentForm.querySelector('[name="body"]');

  if (!input || !input.value.trim()) {
    input?.focus();
    return;
  }

  if (commentForm.dataset.hhSubmitting === '1') {
    return;
  }

  const originalValue = input.value;

  commentForm.dataset.hhSubmitting = '1';
  commentForm.classList.remove('has-error');
  commentForm.classList.add('is-loading');
  input.setAttribute('aria-busy', 'true');

  try {
    const payload = await hhPostForm(commentForm);
    const node = hhNodeFromHtml(payload.html) || hhBuildCommentNodeFromPayload(payload, postId);

    if (node) {
      if (payload.is_reply && payload.parent_id) {
        let repliesWrap = document.querySelector(`[data-hh-comment-replies-root="${payload.parent_id}"]`);
        if (!repliesWrap) {
          const rootComment = document.querySelector(`[data-hh-comment-root="${payload.parent_id}"] .hh-vk-comment-main`);
          if (rootComment) {
            repliesWrap = document.createElement('div');
            repliesWrap.className = 'hh-vk-comment-replies';
            repliesWrap.setAttribute('data-hh-comment-replies-root', payload.parent_id);
            rootComment.appendChild(repliesWrap);
          }
        }
        if (repliesWrap) {
          repliesWrap.appendChild(node);
          hhUpdateCommentReplyLoader(String(payload.parent_id), repliesWrap);
        }
      } else {
        const list = document.querySelector(`[data-hh-comment-list-post="${postId}"]`);
        const home = list?.querySelector(`[data-hh-comment-home="${postId}"]`) || document.querySelector(`[data-hh-comment-home="${postId}"]`);

        if (list && list.closest('[data-hh-feed-media-modal]') && !list.querySelector(`[data-hh-comment-home="${postId}"]`)) {
          list.insertAdjacentElement('afterbegin', node);
        } else {
          home?.insertAdjacentElement('afterend', node);
        }
      }

      // Avoid the heavy Vikinger canvas hydration directly after comment insertion.
      // On slower hosting this can block the UI for several seconds after the comment is already visible.
      hhApplyInsertedVikingerMediaFallback(node);
    }

    input.value = '';
    hhResetInlineReply(postId || rootId);

    if (postId && payload.comment_count_label) {
      hhUpdatePostCommentCount(postId, payload.comment_count_label);
    } else if (postId && payload.comment_count_delta) {
      hhIncrementPostCommentCount(postId, payload.comment_count_delta);
    }
  } catch (error) {
    console.error(error);
    input.value = originalValue;
    commentForm.classList.add('has-error');
    window.setTimeout(() => commentForm.classList.remove('has-error'), 2400);
    input.focus();
  } finally {
    delete commentForm.dataset.hhSubmitting;
    input.removeAttribute('aria-busy');
    commentForm.classList.remove('is-loading');
  }
});

document.addEventListener('click', async (event) => {
  const openCommentsButton = event.target.closest('[data-hh-open-comments]');

  if (openCommentsButton) {
    event.preventDefault();
    event.stopPropagation();

    const postId = openCommentsButton.getAttribute('data-post-id');
    const list = postId ? document.querySelector(`[data-hh-comment-list-post="${postId}"]`) : null;

    if (!list) {
      return;
    }

    list.classList.remove('is-collapsed');
    list.classList.add('is-open');

    const input = list.querySelector('[data-hh-comment-home] [name="body"]');
    window.requestAnimationFrame(() => {
      try {
        input?.focus({preventScroll: true});
      } catch (error) {
        input?.focus();
      }
    });

    return;
  }

  const loadRepliesButton = event.target.closest('[data-hh-load-comment-replies]');

  if (loadRepliesButton) {
    event.preventDefault();
    event.stopPropagation();

    const rootId = loadRepliesButton.getAttribute('data-replies-root');
    const repliesWrap = rootId ? document.querySelector(`[data-hh-comment-replies-root="${rootId}"]`) : null;

    if (!repliesWrap) {
      return;
    }

    const count = Number(loadRepliesButton.getAttribute('data-reply-count') || hhDirectReplyCount(repliesWrap) || 0);
    const isExpanded = loadRepliesButton.getAttribute('aria-expanded') === 'true' || repliesWrap.classList.contains('is-expanded');

    if (isExpanded) {
      repliesWrap.hidden = true;
      repliesWrap.classList.add('is-collapsed');
      repliesWrap.classList.remove('is-expanded');
      loadRepliesButton.hidden = false;
      loadRepliesButton.setAttribute('aria-expanded', 'false');
      loadRepliesButton.innerHTML = hhCommentRepliesLabel(count);
      return;
    }

    repliesWrap.hidden = false;
    repliesWrap.classList.remove('is-collapsed');
    repliesWrap.classList.add('is-expanded');
    loadRepliesButton.hidden = false;
    loadRepliesButton.setAttribute('aria-expanded', 'true');
    loadRepliesButton.innerHTML = hhCommentRepliesHideLabel(count);

    return;
  }

  const loadMoreCommentsButton = event.target.closest('[data-hh-load-more-comments]');

  if (loadMoreCommentsButton) {
    event.preventDefault();
    event.stopPropagation();

    const postId = loadMoreCommentsButton.getAttribute('data-post-id');
    const hiddenCommentsWrap = postId ? document.querySelector(`[data-hh-hidden-comments="${postId}"]`) : null;

    if (!hiddenCommentsWrap) {
      return;
    }

    const count = Number(loadMoreCommentsButton.getAttribute('data-comment-count') || 0);
    const isExpanded = loadMoreCommentsButton.getAttribute('aria-expanded') === 'true' || !hiddenCommentsWrap.hidden;

    if (isExpanded) {
      hiddenCommentsWrap.hidden = true;
      hiddenCommentsWrap.classList.remove('is-expanded');
      loadMoreCommentsButton.setAttribute('aria-expanded', 'false');
      loadMoreCommentsButton.innerHTML = hhCommentMoreLabel(count);
      return;
    }

    hiddenCommentsWrap.hidden = false;
    hiddenCommentsWrap.classList.add('is-expanded');
    loadMoreCommentsButton.setAttribute('aria-expanded', 'true');
    loadMoreCommentsButton.innerHTML = hhCommentMoreHideLabel(count);

    return;
  }

  const reactionButton = event.target.closest('[data-hh-feed-reaction-action]');

  if (reactionButton) {
    event.preventDefault();
    event.stopPropagation();

    const postId = reactionButton.getAttribute('data-post-id');
    const url = reactionButton.getAttribute('data-url');
    const type = reactionButton.getAttribute('data-type') || 'like';
    const mode = reactionButton.getAttribute('data-mode') || 'toggle';
    const mainButton = document.querySelector(`.hh-post-option-button[data-post-id="${postId}"]`);

    if (!postId || !url) {
      return;
    }

    mainButton?.classList.add('is-loading');

    try {
      const payload = await hhPostFormUrlencoded(url, {type, mode});
      const reacted = Boolean(payload.reacted);
      const count = Number(payload.count || 0);
      const reactionType = reacted ? (payload.type || type || 'like') : '';

      mainButton?.classList.toggle('active', reacted);
      mainButton?.setAttribute('data-current-type', reactionType);
      mainButton?.setAttribute('data-type', reactionType || 'like');

      document.querySelectorAll(`[data-hh-reaction-label="${postId}"]`).forEach((node) => {
        node.textContent = reacted ? (hhReactionLabels[reactionType] || 'Like') : 'Like';
      });

      hhSetCurrentReactionIcon(postId, reactionType, reacted);

      document.querySelectorAll(`[data-hh-reaction-count="${postId}"]`).forEach((node) => {
        node.textContent = String(count);
      });

      document.querySelectorAll(`[data-hh-reaction-icons="${postId}"]`).forEach((node) => {
        if (count <= 0) {
          node.innerHTML = '';
          return;
        }

        const iconType = reactionType || 'like';
        node.innerHTML = `<div class="reaction-item"><img class="reaction-image reaction-item-dropdown-trigger" src="${hhReactionAsset(iconType)}" alt="${hhReactionLabels[iconType] || 'Reaction'}"></div>`;
      });

      hhUpdateReactionStats(payload.reaction_stats);
    } catch (error) {
      console.error(error);
    } finally {
      mainButton?.classList.remove('is-loading');
    }

    return;
  }

  const commentSettingsToggle = event.target.closest('[data-hh-comment-settings-toggle]');

  if (commentSettingsToggle) {
    event.preventDefault();
    event.stopPropagation();

    const wrap = commentSettingsToggle.closest('.hh-comment-settings-wrap');
    const isOpen = wrap?.classList.contains('is-open');

    document.querySelectorAll('.hh-comment-settings-wrap.is-open').forEach((node) => {
      if (node !== wrap) {
        node.classList.remove('is-open');
        node.querySelector('[data-hh-comment-settings-toggle]')?.setAttribute('aria-expanded', 'false');
      }
    });

    wrap?.classList.toggle('is-open', !isOpen);
    commentSettingsToggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
    return;
  }

  const commentEditButton = event.target.closest('[data-hh-comment-edit]');

  if (commentEditButton) {
    event.preventDefault();
    event.stopPropagation();
    hhOpenInlineCommentEdit(commentEditButton);
    return;
  }

  const commentEditCancel = event.target.closest('[data-hh-comment-edit-cancel]');

  if (commentEditCancel) {
    event.preventDefault();
    event.stopPropagation();
    hhCloseInlineCommentEdit(commentEditCancel.closest('[data-hh-comment-inline-edit]'));
    return;
  }

  const commentDeleteCancel = event.target.closest('[data-hh-comment-delete-cancel]');

  if (commentDeleteCancel) {
    event.preventDefault();
    event.stopPropagation();
    hhCloseCommentDeleteModal();
    return;
  }

  const commentDeleteAccept = event.target.closest('[data-hh-comment-delete-accept]');

  if (commentDeleteAccept) {
    event.preventDefault();
    event.stopPropagation();
    hhConfirmCommentDelete();
    return;
  }

  if (!event.target.closest('.hh-comment-settings-wrap')) {
    document.querySelectorAll('.hh-comment-settings-wrap.is-open').forEach((node) => {
      node.classList.remove('is-open');
      node.querySelector('[data-hh-comment-settings-toggle]')?.setAttribute('aria-expanded', 'false');
    });
  }

  const commentReactionButton = event.target.closest('[data-hh-comment-reaction-action]');

  if (commentReactionButton) {
    event.preventDefault();
    event.stopPropagation();

    const commentId = commentReactionButton.getAttribute('data-comment-id');
    const url = commentReactionButton.getAttribute('data-url');
    const type = commentReactionButton.getAttribute('data-type') || 'like';
    const mode = commentReactionButton.getAttribute('data-mode') || 'toggle';
    const mainButton = document.querySelector(`[data-hh-comment-reaction-main="${commentId}"]`);

    if (!commentId || !url) {
      return;
    }

    mainButton?.classList.add('is-loading');

    try {
      const payload = await hhPostFormUrlencoded(url, {type, mode});
      const reacted = Boolean(payload.reacted);
      const count = Number(payload.count || 0);
      const reactionType = reacted ? (payload.type || type || 'like') : '';

      mainButton?.classList.toggle('active', reacted);
      mainButton?.setAttribute('data-type', reactionType || 'like');
      mainButton?.setAttribute('data-current-type', reactionType);

      document.querySelectorAll(`[data-hh-comment-reaction-label="${commentId}"]`).forEach((node) => {
        node.textContent = reacted ? (hhReactionLabels[reactionType] || 'Like') : 'Like';
      });

      document.querySelectorAll(`[data-hh-comment-reaction-count="${commentId}"]`).forEach((node) => {
        node.textContent = count > 0 ? String(count) : '';
      });

      hhSetCurrentCommentReactionIcon(commentId, reactionType, reacted);
    } catch (error) {
      console.error(error);
    } finally {
      mainButton?.classList.remove('is-loading');
    }

    return;
  }

  const replyButton = event.target.closest('[data-hh-comment-reply]');

  if (replyButton) {
    event.preventDefault();

    const postId = replyButton.getAttribute('data-post-id');
    const rootId = replyButton.getAttribute('data-root-id') || replyButton.getAttribute('data-parent-id') || '';
    const targetId = replyButton.getAttribute('data-target');
    const userName = replyButton.getAttribute('data-user-name') || '';
    const composer = rootId ? document.querySelector(`[data-hh-comment-inline-composer="${rootId}"]`) : null;
    const input = targetId ? document.getElementById(targetId) : composer?.querySelector('[name="body"]');
    const context = rootId ? document.querySelector(`[data-hh-inline-reply-context="${rootId}"]`) : null;
    const isActive = replyButton.classList.contains('is-active');

    document.querySelectorAll(`[data-hh-comment-reply][data-post-id="${postId}"]`).forEach((button) => {
      button.classList.remove('is-active');
      button.textContent = hhT('reply', 'Reply');
    });

    document.querySelectorAll(`[data-hh-comment-inline-composer]`).forEach((node) => {
      if (node !== composer) {
        node.hidden = true;
      }
    });

    if (!composer || !input) {
      return;
    }

    if (isActive) {
      composer.hidden = true;
      input.value = '';
      return;
    }

    if (context) {
      context.textContent = userName ? hhT('reply_to', 'Antwort auf :name', { name: userName }) : hhT('reply', 'Antwort');
    }

    composer.hidden = false;
    replyButton.classList.add('is-active');
    replyButton.textContent = 'Cancel';
    input.focus();
  }
});




/* Phase 94: Counter für Inline-Reply-Composer */
(function () {
  function updateCommentCounter(input) {
    const targetId = input.getAttribute('data-hh-comment-counter');
    const target = targetId ? document.getElementById(targetId) : null;
    if (!target) return;

    const max = Number(input.getAttribute('maxlength') || 0);
    const current = input.value.length;
    target.textContent = max ? `${Math.max(max - current, 0)}/${max}` : String(current);
  }

  document.querySelectorAll('[data-hh-comment-counter]').forEach((input) => updateCommentCounter(input));

  document.addEventListener('input', (event) => {
    const input = event.target.closest?.('[data-hh-comment-counter]');
    if (!input) return;
    updateCommentCounter(input);
  });
})();

/* Phase 78: Vikinger-style feed media gallery modal with shared post comments */
(function () {
  let modal = null;
  let activeSources = [];
  let activeIndex = 0;
  let activeComments = null;
  let activeCommentsAnchor = null;
  let activeComposerHome = null;
  let activeCommentsWasCollapsed = false;
  let activeOpenToken = 0;

  function hhEscapeHtml(value) {
    return String(value || '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function ensureModal() {
    if (modal) {
      return modal;
    }

    modal = document.createElement('div');
    modal.className = 'hh-feed-media-modal';
    modal.setAttribute('data-hh-feed-media-modal', '');
    modal.setAttribute('aria-hidden', 'true');
    modal.innerHTML = `
      <div class="hh-feed-media-modal-backdrop" data-hh-feed-media-modal-close></div>
      <div class="hh-feed-media-modal-shell popup-picture" role="dialog" aria-modal="true" aria-label="Feed Medium">
        <button class="hh-feed-media-modal-close popup-close-button" type="button" data-hh-feed-media-modal-close aria-label="${hhT('close', 'Schließen')}">
          <svg class="popup-close-button-icon icon-cross"><use xlink:href="#svg-cross"></use></svg>
        </button>

        <aside class="hh-feed-media-modal-sidebar widget-box no-padding">
          <div class="widget-box-scrollable hh-feed-media-modal-scrollable">
            <div class="widget-box-settings hh-feed-media-modal-settings">
              <div class="post-settings-wrap">
                <div class="post-settings">
                  <svg class="post-settings-icon icon-more-dots"><use xlink:href="#svg-more-dots"></use></svg>
                </div>
              </div>
            </div>

            <div class="widget-box-status">
              <div class="widget-box-status-content">
                <div class="user-status">
                  <a class="user-status-avatar" data-hh-feed-media-modal-profile href="#">
                    <span data-hh-feed-media-modal-avatar-slot></span>
                  </a>
                  <p class="user-status-title medium"><a class="bold" data-hh-feed-media-modal-author-link href="#"><span data-hh-feed-media-modal-author></span></a></p>
                  <p class="user-status-text small" data-hh-feed-media-modal-time></p>
                </div>

                <p class="widget-box-status-text hh-feed-media-modal-text" data-hh-feed-media-modal-text></p>
              </div>
            </div>

            <div class="hh-feed-media-modal-comments" data-hh-feed-media-modal-comments></div>
          </div>

          <div class="hh-feed-media-modal-comment-form-slot" data-hh-feed-media-modal-comment-form></div>
        </aside>

        <main class="hh-feed-media-modal-stage-wrap popup-picture-image-wrap">
          <button class="hh-feed-media-modal-nav hh-feed-media-modal-prev" type="button" data-hh-feed-media-modal-prev aria-label="Vorheriges Medium">
            <svg><use xlink:href="#svg-small-arrow"></use></svg>
          </button>

          <figure class="hh-feed-media-modal-stage popup-picture-image" data-hh-feed-media-modal-stage></figure>

          <button class="hh-feed-media-modal-nav hh-feed-media-modal-next" type="button" data-hh-feed-media-modal-next aria-label="${hhT('next_media', 'Nächstes Medium')}">
            <svg><use xlink:href="#svg-small-arrow"></use></svg>
          </button>

          <p class="hh-feed-media-modal-counter" data-hh-feed-media-modal-counter></p>
        </main>
      </div>
    `;
    document.body.appendChild(modal);
    return modal;
  }

  function collectSources(article, postId) {
    return Array.from(article.querySelectorAll(`[data-hh-feed-media-open][data-post-id="${postId}"][data-media-kind="image"]`))
      .map((node) => ({
        index: Number(node.getAttribute('data-media-index') || 0),
        kind: 'image',
        src: node.getAttribute('data-media-src') || '',
        alt: node.getAttribute('data-media-alt') || 'Feed Bild'
      }))
      .filter((item) => item.src)
      .sort((a, b) => a.index - b.index);
  }

  function returnActiveComments() {
    if (activeComposerHome && activeComments && !activeComments.contains(activeComposerHome)) {
      activeComments.insertAdjacentElement('afterbegin', activeComposerHome);
    }

    if (activeComments && activeCommentsAnchor) {
      activeCommentsAnchor.insertAdjacentElement('afterend', activeComments);
      activeComments.classList.toggle('is-collapsed', activeCommentsWasCollapsed);
      activeComments.classList.toggle('is-open', !activeCommentsWasCollapsed);
    }

    if (modal) {
      const formSlot = modal.querySelector('[data-hh-feed-media-modal-comment-form]');
      if (formSlot) {
        formSlot.innerHTML = '';
      }
    }

    activeComments = null;
    activeCommentsAnchor = null;
    activeComposerHome = null;
    activeCommentsWasCollapsed = false;
  }

  function moveCommentsIntoModal(postId) {
    returnActiveComments();

    const comments = document.querySelector(`[data-hh-comment-list-post="${postId}"]`);
    const anchor = document.querySelector(`[data-hh-media-comments-anchor="${postId}"]`);
    const currentModal = ensureModal();
    const slot = currentModal.querySelector('[data-hh-feed-media-modal-comments]');
    const formSlot = currentModal.querySelector('[data-hh-feed-media-modal-comment-form]');

    if (!comments || !anchor || !slot || !formSlot) {
      return;
    }

    activeComments = comments;
    activeCommentsAnchor = anchor;
    activeComposerHome = comments.querySelector(`[data-hh-comment-home="${postId}"]`);
    activeCommentsWasCollapsed = comments.classList.contains('is-collapsed');

    comments.classList.remove('is-collapsed');
    comments.classList.add('is-open');
    slot.innerHTML = '';
    formSlot.innerHTML = '';

    if (activeComposerHome) {
      formSlot.appendChild(activeComposerHome);
    }

    slot.appendChild(comments);
  }

  function fillSidebar(article) {
    const currentModal = ensureModal();
    const author = article.getAttribute('data-hh-feed-post-author') || '';
    const time = article.getAttribute('data-hh-feed-post-time') || '';
    const avatar = article.getAttribute('data-hh-feed-post-avatar') || '';
    const profile = article.getAttribute('data-hh-feed-post-profile') || '#';
    const level = article.getAttribute('data-hh-feed-post-level') || '';
    const text = article.querySelector('.widget-box-status-text')?.textContent || '';

    currentModal.querySelector('[data-hh-feed-media-modal-author]').textContent = author;
    currentModal.querySelector('[data-hh-feed-media-modal-time]').textContent = time;
    currentModal.querySelector('[data-hh-feed-media-modal-text]').textContent = text;

    currentModal.querySelectorAll('[data-hh-feed-media-modal-profile], [data-hh-feed-media-modal-author-link]').forEach((link) => {
      link.setAttribute('href', profile);
    });

    const avatarSlot = currentModal.querySelector('[data-hh-feed-media-modal-avatar-slot]');
    if (avatarSlot) {
      const safeAvatar = hhEscapeHtml(avatar);
      const safeAuthor = hhEscapeHtml(author || 'User');
      const safeLevel = hhEscapeHtml(level || '1');

      // Do not clone Vikinger's hydrated hexagon node here: cloned hexagon placeholders
      // keep only their data-src attribute and can render as an empty frame in the modal.
      // Build a small reliable avatar instead, with real img fallback and the same badge shell.
      avatarSlot.innerHTML = `
        <div class="user-avatar small no-outline hh-feed-media-modal-author-avatar">
          <div class="user-avatar-content">
            <div class="hexagon-image-30-32" data-src="${safeAvatar}" style="background-image:url('${safeAvatar}')">
              <img class="hh-comment-avatar-fallback" src="${safeAvatar}" alt="${safeAuthor}">
            </div>
          </div>
          <div class="user-avatar-progress"><div class="hexagon-progress-40-44"></div></div>
          <div class="user-avatar-progress-border"><div class="hexagon-border-40-44"></div></div>
          <div class="user-avatar-badge">
            <div class="user-avatar-badge-border"><div class="hexagon-22-24"></div></div>
            <div class="user-avatar-badge-content"><div class="hexagon-dark-16-18"></div></div>
            <p class="user-avatar-badge-text">${safeLevel}</p>
          </div>
        </div>
      `;
    }
  }

  function renderStage() {
    const currentModal = ensureModal();
    const stage = currentModal.querySelector('[data-hh-feed-media-modal-stage]');
    const counter = currentModal.querySelector('[data-hh-feed-media-modal-counter]');
    const prev = currentModal.querySelector('[data-hh-feed-media-modal-prev]');
    const next = currentModal.querySelector('[data-hh-feed-media-modal-next]');
    const item = activeSources[activeIndex];

    if (!stage || !item) {
      return;
    }

    stage.innerHTML = `<img src="${hhEscapeHtml(item.src)}" alt="${hhEscapeHtml(item.alt)}">`;

    if (counter) {
      counter.textContent = activeSources.length > 1 ? `${activeIndex + 1} / ${activeSources.length}` : '';
    }

    const hasMultiple = activeSources.length > 1;
    prev?.toggleAttribute('hidden', !hasMultiple);
    next?.toggleAttribute('hidden', !hasMultiple);
  }

  function openModal(trigger) {
    const postId = trigger.getAttribute('data-post-id');
    const article = trigger.closest('[data-hh-feed-post-card]');
    if (!postId || !article) {
      return;
    }

    activeSources = collectSources(article, postId);
    if (!activeSources.length) {
      return;
    }

    const targetIndex = Number(trigger.getAttribute('data-media-index') || 0);
    activeIndex = Math.max(0, activeSources.findIndex((item) => item.index === targetIndex));
    if (activeIndex < 0) {
      activeIndex = 0;
    }

    const currentModal = ensureModal();
    const token = ++activeOpenToken;

    // Keep the click path light: show image modal first, hydrate sidebar/comments after first paint.
    currentModal.querySelector('[data-hh-feed-media-modal-author]').textContent = '';
    currentModal.querySelector('[data-hh-feed-media-modal-time]').textContent = '';
    currentModal.querySelector('[data-hh-feed-media-modal-text]').textContent = '';
    const avatarSlot = currentModal.querySelector('[data-hh-feed-media-modal-avatar-slot]');
    if (avatarSlot) {
      avatarSlot.innerHTML = '';
    }

    renderStage();

    currentModal.classList.add('is-visible');
    currentModal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('hh-feed-media-modal-open');

    window.requestAnimationFrame(() => {
      if (token !== activeOpenToken || !currentModal.classList.contains('is-visible')) {
        return;
      }

      try {
        fillSidebar(article);
        moveCommentsIntoModal(postId);
      } catch (error) {
        // Do not block the media modal because of avatar/comment move errors.
      }
    });
  }

  function closeModal() {
    if (!modal) {
      return;
    }

    activeOpenToken++;
    modal.classList.remove('is-visible');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('hh-feed-media-modal-open');

    const stage = modal.querySelector('[data-hh-feed-media-modal-stage]');
    if (stage) {
      stage.innerHTML = '';
    }

    returnActiveComments();
  }

  function goTo(delta) {
    if (activeSources.length <= 1) {
      return;
    }

    activeIndex = (activeIndex + delta + activeSources.length) % activeSources.length;
    renderStage();
  }

  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-hh-feed-media-open]');
    if (trigger && !trigger.hidden && trigger.getAttribute('data-media-kind') === 'image') {
      event.preventDefault();
      event.stopPropagation();
      openModal(trigger);
      return;
    }

    if (event.target.closest('[data-hh-feed-media-modal-close]')) {
      event.preventDefault();
      closeModal();
      return;
    }

    if (event.target.closest('[data-hh-feed-media-modal-prev]')) {
      event.preventDefault();
      goTo(-1);
      return;
    }

    if (event.target.closest('[data-hh-feed-media-modal-next]')) {
      event.preventDefault();
      goTo(1);
    }
  });

  document.addEventListener('keydown', (event) => {
    if (!modal?.classList.contains('is-visible')) {
      return;
    }

    if (event.key === 'Escape') {
      closeModal();
    } else if (event.key === 'ArrowLeft') {
      goTo(-1);
    } else if (event.key === 'ArrowRight') {
      goTo(1);
    }
  });
})();

/* Phase 50: Header dropdowns, lightweight AJAX actions and global toast helper */
function hhEnsureToastStack() {
  let stack = document.querySelector('.hh-toast-stack');
  if (!stack) {
    stack = document.createElement('div');
    stack.className = 'hh-toast-stack';
    document.body.appendChild(stack);
  }
  return stack;
}

function hhShowToast(message, type = 'success') {
  if (!message) return;
  const stack = hhEnsureToastStack();
  const toast = document.createElement('div');
  toast.className = `hh-toast hh-toast-${type}`;
  toast.innerHTML = `<svg class="hh-toast-icon"><use xlink:href="#svg-check"></use></svg><span>${message}</span>`;
  stack.appendChild(toast);
  window.setTimeout(() => toast.remove(), 6200);
}

function hhAchievementStack() {
  let stack = document.querySelector('[data-hh-achievement-stack]');
  if (!stack) {
    stack = document.createElement('div');
    stack.className = 'hh-achievement-stack';
    stack.setAttribute('data-hh-achievement-stack', '');
    stack.setAttribute('aria-live', 'polite');
    document.body.appendChild(stack);
  }
  return stack;
}

function hhCloseAchievementToast(toast) {
  if (!toast) return;
  toast.classList.remove('is-visible');
  toast.classList.add('is-leaving');
  window.setTimeout(() => toast.remove(), 360);
}

function hhAchievementIconHtml(icon) {
  if (icon) {
    return `<img src="${String(icon).replace(/"/g, '&quot;')}" alt="">`;
  }

  return '<svg><use xlink:href="#svg-badges"></use></svg>';
}

function hhAchievementToastHtml(payload) {
  const type = payload?.type || 'achievement';
  const eyebrow = payload?.eyebrow || 'Achievement';
  const title = payload?.title || '';
  const body = payload?.body || '';
  const icon = payload?.icon || '';
  const xp = Number(payload?.xp || 0);
  const url = payload?.url || '/gamification';
  const xpText = xp > 0 ? `<em>+${xp} XP</em>` : '';
  const bodyText = body ? `<span>${body}</span>` : '';

  return `
    <a class="hh-achievement-toast-link" href="${url}">
      <span class="hh-achievement-toast-icon" aria-hidden="true">${hhAchievementIconHtml(icon)}</span>
      <span class="hh-achievement-toast-content">
        <span class="hh-achievement-toast-eyebrow">${eyebrow}</span>
        <strong>${title}</strong>
        ${bodyText}
        ${xpText}
      </span>
    </a>
    <button class="hh-achievement-toast-close" type="button" data-hh-achievement-toast-close aria-label="Close">×</button>
  `;
}

function hhShowAchievementToast(payload) {
  if (!payload || !payload.title) return;

  const stack = hhAchievementStack();
  const toast = document.createElement('article');
  toast.className = 'hh-achievement-toast';
  toast.setAttribute('data-hh-achievement-toast', '');
  toast.setAttribute('data-achievement-type', payload.type || 'achievement');
  toast.innerHTML = hhAchievementToastHtml(payload);
  stack.appendChild(toast);

  window.requestAnimationFrame(() => toast.classList.add('is-visible'));
  window.setTimeout(() => hhCloseAchievementToast(toast), 7600);
}

function hhRevealPreloadedAchievementToasts() {
  document.querySelectorAll('[data-hh-achievement-toast].is-preloaded').forEach((toast, index) => {
    window.setTimeout(() => {
      toast.classList.remove('is-preloaded');
      toast.classList.add('is-visible');
      window.setTimeout(() => hhCloseAchievementToast(toast), 7600);
    }, index * 420);
  });
}

async function hhFetchPendingAchievements() {
  const stack = document.querySelector('[data-hh-achievement-stack]');
  const url = stack?.getAttribute('data-pending-url');

  if (!url || !window.hhOriginalFetchForAchievements) {
    return;
  }

  try {
    const response = await window.hhOriginalFetchForAchievements(url, {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    });

    if (!response.ok) return;

    const payload = await response.json();
    (payload.achievements || []).forEach((achievement, index) => {
      window.setTimeout(() => hhShowAchievementToast(achievement), index * 420);
    });
  } catch (error) {
    console.debug('Achievement toast fetch skipped', error);
  }
}

window.hhShowAchievementToast = hhShowAchievementToast;
window.hhFetchPendingAchievements = hhFetchPendingAchievements;
window.hhOriginalFetchForAchievements = window.fetch.bind(window);
window.fetch = function hhAchievementAwareFetch(input, init = {}) {
  const responsePromise = window.hhOriginalFetchForAchievements(input, init);

  responsePromise.then(() => {
    const method = String(init?.method || input?.method || 'GET').toUpperCase();
    const url = typeof input === 'string' ? input : String(input?.url || '');
    const isPendingUrl = url.includes('/gamification/achievements/pending');
    const isMutation = !['GET', 'HEAD', 'OPTIONS'].includes(method);
    const isSameOrigin = !url || url.startsWith('/') || url.startsWith(window.location.origin);

    if (isMutation && isSameOrigin && !isPendingUrl) {
      window.setTimeout(hhFetchPendingAchievements, 250);
    }
  }).catch(() => {});

  return responsePromise;
};

document.addEventListener('DOMContentLoaded', hhRevealPreloadedAchievementToasts);
document.addEventListener('click', (event) => {
  const close = event.target.closest('[data-hh-achievement-toast-close]');
  if (!close) return;
  event.preventDefault();
  hhCloseAchievementToast(close.closest('[data-hh-achievement-toast]'));
});

function hhSetHeaderBadge(selector, value) {
  const count = Number(value || 0);
  document.querySelectorAll(selector).forEach((badge) => {
    if (count > 0) {
      badge.textContent = String(count);
      badge.hidden = false;
    } else {
      badge.remove();
    }
  });
}

function hhUpdateHeaderBackdrop() {
  const mobileSheetOpen = Boolean(document.querySelector('.hh-mobile-header-sheet.is-open'));
  document.querySelectorAll('[data-hh-header-dropdown-backdrop]').forEach((backdrop) => {
    backdrop.classList.toggle('is-open', mobileSheetOpen);
  });
  document.documentElement.classList.toggle('hh-mobile-sheet-open', mobileSheetOpen);
  document.body?.classList.toggle('hh-mobile-sheet-open', mobileSheetOpen);
}

function hhCloseHeaderDropdowns(except = null) {
  document.querySelectorAll('[data-hh-header-dropdown]').forEach((dropdown) => {
    if (except && dropdown === except) return;
    dropdown.classList.remove('is-open');
  });
  document.querySelectorAll('[data-hh-header-dropdown-trigger]').forEach((trigger) => {
    const target = trigger.getAttribute('data-hh-header-dropdown-trigger');
    if (except && except.getAttribute('data-hh-header-dropdown') === target) return;
    trigger.classList.remove('is-open');
    trigger.setAttribute('aria-expanded', 'false');
  });
  hhUpdateHeaderBackdrop();
}

document.addEventListener('click', (event) => {
  if (event.target.closest('[data-hh-header-dropdown-close]') || event.target.closest('[data-hh-header-dropdown-backdrop]')) {
    event.preventDefault();
    hhCloseHeaderDropdowns();
    return;
  }

  const trigger = event.target.closest('[data-hh-header-dropdown-trigger]');

  if (trigger) {
    event.preventDefault();
    event.stopPropagation();
    const key = trigger.getAttribute('data-hh-header-dropdown-trigger');
    const dropdown = key ? document.querySelector(`[data-hh-header-dropdown="${key}"]`) : null;

    if (!dropdown) return;

    const willOpen = !dropdown.classList.contains('is-open');
    hhCloseHeaderDropdowns(dropdown);
    dropdown.classList.toggle('is-open', willOpen);
    trigger.classList.toggle('is-open', willOpen);
    trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    hhUpdateHeaderBackdrop();
    return;
  }

  if (!event.target.closest('[data-hh-header-dropdown]')) {
    hhCloseHeaderDropdowns();
  }
});

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') {
    hhCloseHeaderDropdowns();
  }
});

window.addEventListener('resize', () => {
  if (window.matchMedia('(min-width: 961px)').matches) {
    hhCloseHeaderDropdowns();
  }
});

function hhHeaderEmptyMarkup(list, message) {
  const safeMessage = hhEscapeHtml(message || hhT('no_new_entries', 'Keine neuen Einträge.'));
  if (list && list.closest('.hh-mobile-header-sheet')) {
    return `<div class="hh-mobile-header-sheet-empty">${safeMessage}</div>`;
  }
  return `<div class="dropdown-box-list-item hh-header-dropdown-empty">${safeMessage}</div>`;
}

document.addEventListener('submit', async (event) => {
  const form = event.target.closest('[data-hh-header-ajax-form]');
  if (!form) return;

  event.preventDefault();
  form.classList.add('is-loading');

  try {
    const payload = await hhPostForm(form);
    const removeSelector = form.getAttribute('data-remove-on-success');
    const clearDropdown = form.getAttribute('data-clear-dropdown');

    if (removeSelector) {
      document.querySelectorAll(removeSelector).forEach((node) => node.remove());
    }

    const emptyListKey = form.getAttribute('data-empty-list');
    if (emptyListKey) {
      const list = document.querySelector(`[data-hh-dropdown-list="${emptyListKey}"]`);
      if (list && !list.querySelector('[data-hh-dropdown-item]')) {
        const emptyMessage = form.getAttribute('data-empty-message') || hhT('no_new_entries', 'Keine neuen Einträge.');
        const moreMessage = form.getAttribute('data-more-message') || emptyMessage;
        const friendRequestCount = Object.prototype.hasOwnProperty.call(payload, 'friend_request_count') ? Number(payload.friend_request_count || 0) : 0;
        list.innerHTML = hhHeaderEmptyMarkup(list, friendRequestCount > 0 ? moreMessage : emptyMessage);
      }
    }

    if (clearDropdown) {
      const list = document.querySelector(`[data-hh-dropdown-list="${clearDropdown}"]`);
      const emptyMessage = form.getAttribute('data-empty-message') || hhT('no_new_entries', 'Keine neuen Einträge.');
      if (list) {
        list.innerHTML = hhHeaderEmptyMarkup(list, emptyMessage);
      }
    }

    if (Object.prototype.hasOwnProperty.call(payload, 'friend_request_count')) {
      hhSetHeaderBadge('[data-hh-friend-request-count]', payload.friend_request_count);
    }

    if (Object.prototype.hasOwnProperty.call(payload, 'unread_count')) {
      hhSetHeaderBadge('[data-hh-notification-count]', payload.unread_count);
    }

    if (payload.message) {
      hhShowToast(payload.message);
    }

    if (form.hasAttribute('data-redirect-on-success') && payload.action_url) {
      window.location.href = payload.action_url;
    }
  } catch (error) {
    console.error(error);
    form.submit();
  } finally {
    form.classList.remove('is-loading');
  }
});

/* Phase 58b: Right chat dock closer to the Vikinger widget behavior */
function hhEscapeHtml(value) {
  return String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function hhGetChatDock() {
  return document.querySelector('[data-hh-chat-dock]');
}

function hhOpenChatPanel(key) {
  const dock = hhGetChatDock();
  if (!dock) return;

  const listPanel = dock.querySelector('[data-hh-chat-list-panel]');
  const targetKey = key || 'list';

  if (typeof hhCloseHeaderDropdowns === 'function') {
    hhCloseHeaderDropdowns();
  }

  dock.classList.add('is-open');

  if (targetKey === 'list') {
    listPanel?.classList.add('is-open');
    listPanel?.classList.remove('is-closed', 'is-hidden');
    listPanel?.setAttribute('aria-hidden', 'false');

    dock.querySelectorAll('[data-hh-chat-panel]').forEach((node) => {
      if (node !== listPanel) {
        node.classList.remove('is-active');
        node.classList.add('is-hidden');
        node.setAttribute('aria-hidden', 'true');
      }
    });
    return;
  }

  const panel = dock.querySelector(`[data-hh-chat-panel="${targetKey}"]`);
  if (!panel) return;

  listPanel?.classList.add('is-open');
  listPanel?.classList.remove('is-closed', 'is-hidden');
  listPanel?.setAttribute('aria-hidden', 'false');

  dock.querySelectorAll('[data-hh-chat-panel]').forEach((node) => {
    const active = node === panel;
    if (node !== listPanel) {
      node.classList.toggle('is-active', active);
      node.classList.toggle('is-hidden', !active);
      node.setAttribute('aria-hidden', active ? 'false' : 'true');
    }
  });

  const messageList = panel.querySelector('[data-hh-chat-messages]');
  if (messageList) {
    window.requestAnimationFrame(() => {
      messageList.scrollTop = messageList.scrollHeight;
    });
  }
}

function hhCloseChatDock() {
  const dock = hhGetChatDock();
  if (!dock) return;

  const listPanel = dock.querySelector('[data-hh-chat-list-panel]');
  dock.classList.remove('is-open');
  listPanel?.classList.remove('is-open', 'is-hidden');
  listPanel?.classList.add('is-closed');
  listPanel?.setAttribute('aria-hidden', 'false');

  dock.querySelectorAll('[data-hh-chat-panel]').forEach((node) => {
    if (node !== listPanel) {
      node.classList.remove('is-active');
      node.classList.add('is-hidden');
      node.setAttribute('aria-hidden', 'true');
    }
  });
}

async function hhMarkChatConversationRead(trigger) {
  const url = trigger?.getAttribute('data-read-url');
  const conversationId = trigger?.getAttribute('data-hh-chat-conversation-trigger');
  if (!url || !conversationId) return;

  document.querySelectorAll(`[data-hh-chat-unread="${conversationId}"]`).forEach((node) => node.remove());
  document.querySelectorAll(`[data-hh-chat-conversation-trigger="${conversationId}"]`).forEach((node) => {
    node.classList.remove('is-unread');
    node.querySelector('.hh-chat-avatar-wrap')?.classList.remove('is-unread');
    node.querySelector('.hh-chat-avatar-wrap')?.classList.add('is-online');
  });

  try {
    const payload = await hhPostFormUrlencoded(url, {});
    if (Object.prototype.hasOwnProperty.call(payload, 'unread_messages')) {
      hhSetHeaderBadge('[data-hh-message-count]', payload.unread_messages);
    }
  } catch (error) {
    console.error(error);
  }
}

function hhAppendOwnChatMessage(conversationId, body, timeLabel) {
  const list = document.querySelector(`[data-hh-chat-messages="${conversationId}"]`);
  if (!list) return;

  const row = document.createElement('div');
  row.className = 'hh-chat-speaker right';
  row.innerHTML = `<p class="hh-chat-speaker-message">${hhEscapeHtml(body)}</p><p class="hh-chat-speaker-timestamp">${hhEscapeHtml(timeLabel || '')}</p>`;
  list.appendChild(row);
  list.scrollTop = list.scrollHeight;
}

function hhCanUseDesktopChatDrawer() {
  return Boolean(hhGetChatDock()) && window.matchMedia('(min-width: 961px)').matches;
}

function hhShowProfileMessageLoadingPanel(link) {
  const dock = hhGetChatDock();
  if (!dock) return null;

  dock.querySelector('[data-hh-chat-panel="profile-message-loading"]')?.remove();

  const recipient = link?.getAttribute('data-recipient-username') || link?.textContent?.trim() || hhT('messages', 'Nachrichten');
  const panel = hhNodeFromHtml(`
    <section class="hh-chat-widget hh-chat-conversation-panel hh-chat-loading-panel is-hidden" data-hh-chat-panel="profile-message-loading" aria-hidden="true">
      <div class="hh-chat-widget-header">
        <button class="hh-chat-back" type="button" data-hh-chat-back aria-label="${hhEscapeHtml(hhT('back_to_messages', 'Zurück'))}">
          <svg><use xlink:href="#svg-back-arrow"></use></svg>
        </button>
        <div class="hh-chat-header-user">
          <span class="hh-chat-header-user-meta">
            <strong>${hhEscapeHtml(recipient)}</strong>
            <em>${hhEscapeHtml(hhT('message_drawer_loading', 'Chat wird geladen...'))}</em>
          </span>
        </div>
      </div>
      <div class="hh-chat-widget-conversation">
        <div class="hh-chat-speaker left hh-chat-empty-thread-note">
          <p class="hh-chat-speaker-message">${hhEscapeHtml(hhT('message_drawer_loading_hint', 'Die Unterhaltung wird geöffnet. Du kannst gleich direkt schreiben.'))}</p>
        </div>
      </div>
      <button class="hh-chat-widget-button" type="button" data-hh-chat-back aria-label="${hhEscapeHtml(hhT('back_to_messages', 'Zurück'))}">
        <span class="hh-chat-widget-button-icon" aria-hidden="true">
          <span class="burger-icon inverted"><span class="burger-icon-bar"></span><span class="burger-icon-bar"></span><span class="burger-icon-bar"></span></span>
        </span>
        <span class="hh-chat-widget-button-text">${hhEscapeHtml(hhT('messages_chat', 'Chat'))}</span>
      </button>
    </section>
  `);

  if (!panel) return null;
  dock.appendChild(panel);
  hhOpenChatPanel('profile-message-loading');

  return panel;
}

function hhRemoveProfileMessageLoadingPanel() {
  const panel = document.querySelector('[data-hh-chat-panel="profile-message-loading"]');
  if (panel) {
    panel.remove();
  }
}

function hhUpsertChatDockNode(selector, html, container, mode) {
  const node = hhNodeFromHtml(html);
  if (!node || !container) return null;

  const existing = document.querySelector(selector);
  if (existing) {
    existing.replaceWith(node);
  } else if (mode === 'prepend') {
    container.prepend(node);
  } else {
    container.appendChild(node);
  }

  hhHydrateInsertedVikingerMedia(node);
  hhApplyInsertedVikingerMediaFallback(node);

  return node;
}

async function hhOpenProfileMessageDrawer(link) {
  if (!link?.href || !hhCanUseDesktopChatDrawer()) {
    return false;
  }

  link.classList.add('is-loading');
  const loadingPanel = hhShowProfileMessageLoadingPanel(link);

  try {
    const response = await fetch(link.href, {
      method: 'GET',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin'
    });

    if (!response.ok) {
      throw new Error('Request failed');
    }

    const payload = await response.json();
    const conversationId = payload.conversation_id;
    if (!conversationId) {
      throw new Error('Missing conversation id');
    }

    const dock = hhGetChatDock();
    const list = dock?.querySelector('[data-hh-chat-list]');

    if (payload.trigger_html && list) {
      list.querySelectorAll('.hh-chat-empty-link').forEach((node) => node.remove());
      hhUpsertChatDockNode(`[data-hh-chat-conversation-trigger="${conversationId}"]`, payload.trigger_html, list, 'prepend');
    }

    if (payload.panel_html && dock) {
      hhUpsertChatDockNode(`[data-hh-chat-panel="conversation-${conversationId}"]`, payload.panel_html, dock, 'append');
    }

    document.querySelectorAll(`[data-hh-chat-unread="${conversationId}"]`).forEach((node) => node.remove());

    if (Object.prototype.hasOwnProperty.call(payload, 'unread_messages')) {
      hhSetHeaderBadge('[data-hh-message-count]', payload.unread_messages);
    }

    hhOpenChatPanel(payload.panel_key || `conversation-${conversationId}`);
    hhRemoveProfileMessageLoadingPanel();

    window.requestAnimationFrame(() => {
      const panel = dock?.querySelector(`[data-hh-chat-panel="conversation-${conversationId}"]`);
      const input = panel?.querySelector('input[name="body"]');
      input?.focus();
    });

    return true;
  } catch (error) {
    console.error(error);

    if (loadingPanel) {
      const note = loadingPanel.querySelector('.hh-chat-speaker-message');
      if (note) {
        note.innerHTML = `${hhEscapeHtml(hhT('message_drawer_error', 'Der Chat konnte nicht direkt geöffnet werden.'))}<br><a href="${hhEscapeHtml(link.href)}">${hhEscapeHtml(hhT('open_messages', 'Nachrichten öffnen'))}</a>`;
      }
    } else {
      window.location.href = link.href;
    }

    return false;
  } finally {
    link.classList.remove('is-loading');
  }
}

document.addEventListener('click', (event) => {
  const profileMessageLink = event.target.closest('[data-hh-profile-message-drawer]');
  if (profileMessageLink && hhCanUseDesktopChatDrawer() && !event.metaKey && !event.ctrlKey && !event.shiftKey && event.button !== 1) {
    event.preventDefault();
    event.stopPropagation();
    hhOpenProfileMessageDrawer(profileMessageLink);
    return;
  }

  const openChat = event.target.closest('[data-hh-chat-dock-open]');
  if (openChat) {
    event.preventDefault();
    event.stopPropagation();

    const dock = hhGetChatDock();
    const listPanel = dock?.querySelector('[data-hh-chat-list-panel]');
    const hasConversationOpen = Boolean(dock?.querySelector('.hh-chat-conversation-panel.is-active'));

    if (dock?.classList.contains('is-open') && listPanel?.classList.contains('is-open') && !hasConversationOpen) {
      hhCloseChatDock();
    } else {
      hhOpenChatPanel(openChat.getAttribute('data-hh-chat-dock-open') || 'list');
    }
    return;
  }

  const conversationTrigger = event.target.closest('[data-hh-chat-conversation-trigger]');
  if (conversationTrigger) {
    event.preventDefault();
    event.stopPropagation();
    const conversationId = conversationTrigger.getAttribute('data-hh-chat-conversation-trigger');
    hhOpenChatPanel(`conversation-${conversationId}`);
    hhMarkChatConversationRead(conversationTrigger);
    return;
  }

  const back = event.target.closest('[data-hh-chat-back]');
  if (back) {
    event.preventDefault();
    event.stopPropagation();
    hhOpenChatPanel('list');
    return;
  }

  const dock = hhGetChatDock();
  if (dock?.classList.contains('is-open') && !event.target.closest('[data-hh-chat-dock]')) {
    hhCloseChatDock();
  }
});

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') {
    hhCloseChatDock();
  }
});

document.addEventListener('input', (event) => {
  const search = event.target.closest('[data-hh-chat-search]');
  if (!search) return;

  const term = search.value.trim().toLowerCase();
  document.querySelectorAll('[data-hh-chat-search-item]').forEach((item) => {
    item.hidden = Boolean(term) && !String(item.getAttribute('data-hh-chat-search-item') || '').includes(term);
  });
});

document.addEventListener('submit', async (event) => {
  const form = event.target.closest('[data-hh-chat-send-form]');
  if (!form) return;

  event.preventDefault();
  const input = form.querySelector('[name="body"]');
  const body = input?.value.trim() || '';
  if (!body) {
    input?.focus();
    return;
  }

  form.classList.add('is-loading');

  try {
    const payload = await hhPostForm(form);
    hhAppendOwnChatMessage(form.getAttribute('data-conversation-id'), payload.body || body, payload.created_at_label || '');
    input.value = '';
    if (Object.prototype.hasOwnProperty.call(payload, 'unread_messages')) {
      hhSetHeaderBadge('[data-hh-message-count]', payload.unread_messages);
    }
  } catch (error) {
    console.error(error);
    form.submit();
  } finally {
    form.classList.remove('is-loading');
  }
});

/* Phase 153: Profile messages AJAX reply + scroll to latest message */
function hhProfileMessageSetError(form, message) {
  if (!form) return;
  const errorNode = form.querySelector('[data-hh-profile-message-error]');
  form.classList.toggle('has-error', Boolean(message));
  if (errorNode) {
    errorNode.textContent = message || '';
    errorNode.hidden = !message;
  }
}

function hhProfileMessageGetSimpleBarInstance(node) {
  try {
    if (window.SimpleBar?.instances?.get) {
      return window.SimpleBar.instances.get(node) || null;
    }
  } catch (error) {
    return null;
  }

  return null;
}

function hhProfileMessageGetContentElement(thread) {
  if (!thread) return null;
  const simpleBar = hhProfileMessageGetSimpleBarInstance(thread);
  if (simpleBar && typeof simpleBar.getContentElement === 'function') {
    return simpleBar.getContentElement();
  }

  return thread.querySelector('.simplebar-content') || thread;
}

function hhProfileMessageGetScrollElement(thread) {
  if (!thread) return null;
  const simpleBar = hhProfileMessageGetSimpleBarInstance(thread);
  if (simpleBar && typeof simpleBar.getScrollElement === 'function') {
    return simpleBar.getScrollElement();
  }

  return thread.querySelector('.simplebar-content-wrapper') || thread;
}

function hhProfileMessageScrollToBottom(thread, behavior = 'auto') {
  const scrollElement = hhProfileMessageGetScrollElement(thread);
  if (!scrollElement) return;

  const scrollToBottom = () => {
    const target = scrollElement.scrollHeight || 0;
    if (typeof scrollElement.scrollTo === 'function') {
      scrollElement.scrollTo({top: target, behavior});
    } else {
      scrollElement.scrollTop = target;
    }
  };

  window.requestAnimationFrame(scrollToBottom);
  window.setTimeout(scrollToBottom, 120);
}

function hhProfileMessageAppendOwn(form, body, timeLabel) {
  const widget = form.closest('.hh-profile-message-thread-widget');
  const thread = widget?.querySelector('[data-hh-profile-message-thread]');
  const content = hhProfileMessageGetContentElement(thread);
  if (!content) return;

  content.querySelectorAll('.hh-profile-message-thread-empty').forEach((node) => node.remove());

  const row = document.createElement('div');
  row.className = 'chat-widget-speaker right hh-profile-message-new';
  row.innerHTML = `<p class="chat-widget-speaker-message">${hhEscapeHtml(body)}</p><p class="chat-widget-speaker-timestamp">${hhEscapeHtml(timeLabel || '')}</p>`;
  content.appendChild(row);
  hhProfileMessageScrollToBottom(thread, 'smooth');
}

function hhProfileMessageUpdateActivePreview(form, body) {
  const active = document.querySelector('.hh-profile-conversation-link.active');
  if (!active) return;

  const preview = active.querySelector('.user-status-text');
  if (preview) {
    preview.textContent = body.length > 76 ? `${body.slice(0, 73)}...` : body;
  }
}

function hhProfileMessageInitScroll() {
  document.querySelectorAll('[data-hh-profile-message-thread]').forEach((thread) => {
    if (!thread.querySelector('.chat-widget-speaker')) return;
    hhProfileMessageScrollToBottom(thread);
    window.setTimeout(() => hhProfileMessageScrollToBottom(thread), 420);
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', hhProfileMessageInitScroll, {once: true});
} else {
  hhProfileMessageInitScroll();
}

window.addEventListener('load', hhProfileMessageInitScroll, {once: true});

document.addEventListener('input', (event) => {
  const input = event.target.closest('[data-hh-profile-message-input]');
  if (!input) return;

  const form = input.closest('[data-hh-profile-message-reply-form]');
  if (form && input.value.trim()) {
    hhProfileMessageSetError(form, '');
  }
});

document.addEventListener('submit', async (event) => {
  const form = event.target.closest('[data-hh-profile-message-reply-form]');
  if (!form) return;

  event.preventDefault();
  hhProfileMessageSetError(form, '');

  const input = form.querySelector('[data-hh-profile-message-input], [name="body"]');
  const submit = form.querySelector('[type="submit"]');
  const body = String(input?.value || '').trim();

  if (!body) {
    hhProfileMessageSetError(form, form.getAttribute('data-empty-message') || hhT('message_empty', 'Bitte schreibe zuerst eine Nachricht.'));
    input?.focus();
    return;
  }

  if (input) {
    input.value = body;
  }

  form.classList.add('is-loading');
  if (submit) {
    submit.disabled = true;
  }

  try {
    const payload = await hhPostForm(form);
    const sentBody = String(payload.body || body);
    hhProfileMessageAppendOwn(form, sentBody, payload.created_at_label || '');
    hhProfileMessageUpdateActivePreview(form, sentBody);
    if (Object.prototype.hasOwnProperty.call(payload, 'unread_messages')) {
      hhSetHeaderBadge('[data-hh-message-count]', payload.unread_messages);
    }
    if (input) {
      input.value = '';
      input.focus();
    }
  } catch (error) {
    console.error(error);
    hhProfileMessageSetError(form, form.getAttribute('data-send-error') || 'Nachricht konnte nicht gesendet werden.');
  } finally {
    form.classList.remove('is-loading');
    if (submit) {
      submit.disabled = false;
    }
  }
});

/* Phase 65b: Profile edit AJAX + Vikinger account hub form helpers */

function hhAccountHubSetOpen(root, targetBody) {
  if (!root || !targetBody) return;

  root.querySelectorAll('.sidebar-menu-body.accordion-content-linked').forEach((body) => {
    const isTarget = body === targetBody;
    body.classList.toggle('accordion-open', isTarget);
    body.hidden = !isTarget;
    const item = body.closest('.sidebar-menu-item');
    item?.querySelector('.sidebar-menu-header')?.classList.toggle('selected', isTarget);
  });
}

function hhAccountHubInitAccordions() {
  document.querySelectorAll('[data-hh-account-hub-accordion]').forEach((root) => {
    const firstOpen = root.querySelector('.sidebar-menu-body.accordion-open') || root.querySelector('.sidebar-menu-body.accordion-content-linked');
    if (firstOpen) {
      hhAccountHubSetOpen(root, firstOpen);
    }
  });
}

hhAccountHubInitAccordions();

document.addEventListener('click', (event) => {
  const header = event.target.closest('[data-hh-account-hub-accordion] .accordion-trigger-linked');
  if (!header) return;

  event.preventDefault();
  event.stopPropagation();

  const root = header.closest('[data-hh-account-hub-accordion]');
  const body = header.closest('.sidebar-menu-item')?.querySelector('.sidebar-menu-body.accordion-content-linked');
  if (root && body) {
    hhAccountHubSetOpen(root, body);
  }
}, true);

function hhProfileClearErrors(form) {
  form.querySelectorAll('[data-hh-profile-error]').forEach((node) => {
    node.textContent = '';
  });
  form.querySelectorAll('.has-error').forEach((node) => node.classList.remove('has-error'));
}

function hhProfileSetErrors(form, errors) {
  Object.entries(errors || {}).forEach(([field, messages]) => {
    const errorNode = form.querySelector(`[data-hh-profile-error="${CSS.escape(field)}"]`);
    if (!errorNode) return;

    errorNode.textContent = Array.isArray(messages) ? messages.join(' ') : String(messages || '');
    const formItem = errorNode.closest('.form-item') || errorNode.closest('.upload-box');
    formItem?.classList.add('has-error');
    formItem?.querySelector('.form-input, .form-select')?.classList.add('has-error');
  });
}

function hhProfileToggleInputState(input) {
  const wrap = input.closest('.form-input');
  if (!wrap) return;
  wrap.classList.toggle('active', Boolean(input.value));
}

function hhProfileSetImagePreview(selector, url) {
  if (!url) return;
  document.querySelectorAll(selector).forEach((node) => {
    if (node.tagName === 'IMG') {
      node.src = url;
      return;
    }
    node.setAttribute('data-src', url);
    node.style.backgroundImage = `url("${url}")`;

    const previewImage = node.closest('.user-avatar-content')?.querySelector('[data-hh-profile-avatar-preview-img]');
    if (previewImage) {
      previewImage.src = url;
    }
  });
}

function hhProfilePreviewSelectedFile(input) {
  const file = input.files?.[0];
  const type = input.getAttribute('data-hh-profile-file');
  if (!file || !type || !file.type.startsWith('image/')) return;

  const url = URL.createObjectURL(file);
  if (type === 'avatar') {
    hhProfileSetImagePreview('[data-hh-profile-avatar-preview]', url);
    hhProfileSetImagePreview('[data-hh-profile-avatar-preview-img]', url);
    return;
  }
  if (type === 'cover') {
    hhProfileSetImagePreview('[data-hh-profile-cover-preview]', url);
  }
}

document.addEventListener('input', (event) => {
  const liveInput = event.target.closest('[data-hh-profile-live]');
  if (liveInput) {
    hhProfileToggleInputState(liveInput);
    const target = liveInput.getAttribute('data-hh-profile-live');
    if (target === 'name') {
      document.querySelectorAll('[data-hh-profile-preview-name]').forEach((node) => {
        node.textContent = liveInput.value.trim() || liveInput.defaultValue || '';
      });
    }
    if (target === 'headline') {
      document.querySelectorAll('[data-hh-profile-preview-headline]').forEach((node) => {
        node.textContent = liveInput.value.trim() || node.getAttribute('data-empty-text') || hhT('no_headline', 'Noch keine Kurzbeschreibung.');
      });
    }
  }

  const field = event.target.closest('.hh-profile-edit-form input, .hh-profile-edit-form textarea');
  if (field) {
    hhProfileToggleInputState(field);
  }
});

document.addEventListener('change', (event) => {
  const fileInput = event.target.closest('[data-hh-profile-file]');
  if (!fileInput) return;
  hhProfilePreviewSelectedFile(fileInput);
});

document.addEventListener('submit', async (event) => {
  const form = event.target.closest('[data-hh-profile-edit-form]');
  if (!form) return;

  event.preventDefault();
  hhProfileClearErrors(form);

  const submit = document.querySelector('[data-hh-profile-submit]');
  const defaultLabel = submit?.getAttribute('data-default-label') || submit?.textContent || 'Save Changes!';
  const savingLabel = submit?.getAttribute('data-saving-label') || 'Saving…';

  if (submit) {
    submit.disabled = true;
    submit.textContent = savingLabel;
  }
  form.classList.add('is-saving');

  try {
    const response = await fetch(form.action, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'X-CSRF-TOKEN': hhCsrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      body: new FormData(form)
    });

    const payload = await response.json().catch(() => ({}));

    if (response.status === 422) {
      hhProfileSetErrors(form, payload.errors || {});
      hhShowToast(payload.message || form.getAttribute('data-error-message') || hhT('validation_error', 'Bitte prüfe die markierten Angaben.'), 'error');
      return;
    }

    if (!response.ok) {
      throw new Error(payload.message || 'Profile save failed');
    }

    const profile = payload.profile || {};
    if (profile.avatar_url) {
      hhProfileSetImagePreview('[data-hh-profile-avatar-preview]', profile.avatar_url);
    }
    if (profile.cover_url) {
      hhProfileSetImagePreview('[data-hh-profile-cover-preview]', profile.cover_url);
    }
    if (Object.prototype.hasOwnProperty.call(profile, 'completion')) {
      document.querySelectorAll('[data-hh-profile-preview-completion]').forEach((node) => {
        node.textContent = `${Number(profile.completion || 0)}%`;
      });
    }

    hhShowToast(payload.message || form.getAttribute('data-success-message') || 'Profil wurde gespeichert.', 'success');
  } catch (error) {
    console.error(error);
    hhShowToast(form.getAttribute('data-error-message') || 'Profil konnte nicht gespeichert werden.', 'error');
  } finally {
    if (submit) {
      submit.disabled = false;
      submit.textContent = defaultLabel;
    }
    form.classList.remove('is-saving');
  }
});


/* Phase 70: Real Team create/edit modals on manage page, with normal Laravel submit */
function hhTeamModalRoot(node) {
  return node?.closest?.('[data-hh-team-modal-shell]') || document;
}

function hhTeamSetImagePreview(root, selector, url) {
  if (!url) return;
  const scope = root || document;
  scope.querySelectorAll(selector).forEach((node) => {
    if (node.tagName === 'IMG') {
      node.src = url;
      return;
    }
    node.setAttribute('data-src', url);
    node.style.backgroundImage = `url("${url}")`;

    const previewImage = node.closest('.user-avatar-content')?.querySelector('[data-hh-team-avatar-preview-img]');
    if (previewImage) {
      previewImage.src = url;
    }
  });
}

function hhTeamPreviewSelectedFile(input) {
  const file = input.files?.[0];
  const type = input.getAttribute('data-hh-team-file');
  if (!file || !type || !file.type.startsWith('image/')) return;

  const url = URL.createObjectURL(file);
  const root = hhTeamModalRoot(input);

  if (type === 'avatar') {
    hhTeamSetImagePreview(root, '[data-hh-team-avatar-preview]', url);
    hhTeamSetImagePreview(root, '[data-hh-team-avatar-preview-img]', url);
  }
  if (type === 'cover') {
    hhTeamSetImagePreview(root, '[data-hh-team-cover-preview]', url);
  }
}

function hhTeamVisibleModalShells() {
  return Array.from(document.querySelectorAll('[data-hh-team-modal-shell].is-open:not([hidden])'));
}

function hhTeamUpdateBodyLock() {
  document.body.classList.toggle('hh-has-team-modal', hhTeamVisibleModalShells().length > 0);
}

function hhTeamOpenModal(shell) {
  if (!shell) return;
  document.querySelectorAll('[data-hh-team-modal-shell].is-open').forEach((openShell) => {
    if (openShell !== shell && openShell.getAttribute('data-hh-team-modal-embedded') === 'true') {
      openShell.classList.remove('is-open');
      openShell.hidden = true;
    }
  });

  shell.hidden = false;
  shell.classList.add('is-open');
  hhTeamUpdateBodyLock();

  const modal = shell.querySelector('.hh-team-modal');
  if (modal) {
    modal.setAttribute('tabindex', '-1');
    window.setTimeout(() => {
      try {
        modal.focus({ preventScroll: true });
      } catch (error) {
        modal.focus();
      }
    }, 0);
  }
}

function hhTeamCloseModal(shell) {
  if (!shell) return;

  const isEmbedded = shell.getAttribute('data-hh-team-modal-embedded') === 'true';
  const modal = shell.querySelector('.hh-team-modal[data-hh-team-modal-close-url]');
  const closeUrl = modal?.getAttribute('data-hh-team-modal-close-url');

  if (isEmbedded) {
    shell.classList.remove('is-open');
    shell.hidden = true;
    hhTeamUpdateBodyLock();
    return;
  }

  if (closeUrl) {
    window.location.href = closeUrl;
  }
}

document.addEventListener('input', (event) => {
  const liveInput = event.target.closest('[data-hh-team-live]');
  if (!liveInput) return;

  hhProfileToggleInputState(liveInput);
  const root = hhTeamModalRoot(liveInput);
  const target = liveInput.getAttribute('data-hh-team-live');
  if (target === 'name') {
    root.querySelectorAll('[data-hh-team-preview-name]').forEach((node) => {
      node.textContent = liveInput.value.trim() || node.getAttribute('data-empty-text') || '';
    });
  }
  if (target === 'tagline') {
    root.querySelectorAll('[data-hh-team-preview-tagline]').forEach((node) => {
      node.textContent = liveInput.value.trim() || node.getAttribute('data-empty-text') || 'hnt.rocks Team';
    });
  }
});

document.addEventListener('change', (event) => {
  const fileInput = event.target.closest('[data-hh-team-file]');
  if (!fileInput) return;
  hhTeamPreviewSelectedFile(fileInput);
});

document.addEventListener('click', (event) => {
  const trigger = event.target.closest('[data-hh-team-modal-trigger]');
  if (trigger) {
    const targetId = trigger.getAttribute('data-hh-team-modal-trigger');
    const shell = targetId ? document.getElementById(targetId) : null;
    if (shell) {
      event.preventDefault();
      hhTeamOpenModal(shell);
      return;
    }
  }

  const tab = event.target.closest('[data-hh-team-modal-tab]');
  if (tab) {
    const shell = hhTeamModalRoot(tab);
    const target = tab.getAttribute('data-hh-team-modal-tab');
    if (!target) return;

    shell.querySelectorAll('[data-hh-team-modal-tab]').forEach((item) => {
      item.classList.toggle('active', item === tab);
    });

    shell.querySelectorAll('[data-hh-team-modal-panel]').forEach((panel) => {
      panel.classList.toggle('is-active', panel.getAttribute('data-hh-team-modal-panel') === target);
    });
    return;
  }

  const closer = event.target.closest('[data-hh-team-modal-close]');
  if (closer) {
    const shell = hhTeamModalRoot(closer);
    if (shell && shell !== document) {
      event.preventDefault();
      hhTeamCloseModal(shell);
    }
  }
});

document.addEventListener('keydown', (event) => {
  if (event.key !== 'Escape') return;

  const shells = hhTeamVisibleModalShells();
  const shell = shells[shells.length - 1];
  if (!shell) return;

  const activeElement = document.activeElement;
  if (activeElement && ['INPUT', 'TEXTAREA', 'SELECT'].includes(activeElement.tagName)) {
    activeElement.blur();
    return;
  }

  event.preventDefault();
  hhTeamCloseModal(shell);
});

window.addEventListener('DOMContentLoaded', () => {
  hhTeamVisibleModalShells().forEach((shell) => {
    const modal = shell.querySelector('.hh-team-modal');
    modal?.setAttribute('tabindex', '-1');
  });
  hhTeamUpdateBodyLock();
});

/* Phase 170: LFG create uses the real Vikinger popup-manage-item pattern.
   The modal card is a direct .popup-box.mid.popup-manage-item in the DOM; no shell is required. */
(function () {
  const modalSelector = '[data-hh-lfg-create-modal]';
  const backdropSelector = '[data-hh-lfg-create-modal-backdrop], .hh-lfg-modal-backdrop';

  function getModal(trigger) {
    const targetId = trigger?.getAttribute?.('data-hh-lfg-create-modal-trigger') || 'hh-lfg-create-modal';
    return targetId ? document.getElementById(targetId) : document.querySelector(modalSelector);
  }

  function getBackdrop(modal) {
    return document.querySelector(backdropSelector) || modal?.previousElementSibling;
  }

  function setVisible(node, visible) {
    if (!node) return;
    node.hidden = !visible;
    node.classList.toggle('is-open', visible);
    node.classList.toggle('active', visible);
    node.setAttribute('aria-hidden', visible ? 'false' : 'true');
  }

  function openModal(trigger) {
    const modal = getModal(trigger);
    if (!modal) return false;

    const backdrop = getBackdrop(modal);
    setVisible(backdrop, true);
    setVisible(modal, true);
    document.body.classList.add('hh-has-lfg-create-modal', 'hh-has-team-modal');

    modal.setAttribute('tabindex', '-1');
    window.setTimeout(() => {
      try {
        modal.focus({ preventScroll: true });
      } catch (error) {
        modal.focus();
      }
    }, 0);

    return true;
  }

  function closeModal(source) {
    const modal = source?.closest?.(modalSelector) || document.querySelector(`${modalSelector}.is-open`);
    if (!modal) return false;

    const embedded = modal.getAttribute('data-hh-lfg-create-modal-embedded') === 'true';
    const closeUrl = modal.getAttribute('data-hh-lfg-create-modal-close-url');

    if (!embedded && closeUrl) {
      window.location.href = closeUrl;
      return true;
    }

    const backdrop = getBackdrop(modal);
    setVisible(modal, false);
    setVisible(backdrop, false);
    document.body.classList.remove('hh-has-lfg-create-modal');
    if (!document.querySelector('[data-hh-team-modal-shell].is-open:not([hidden]), [data-hh-lfg-create-modal].is-open:not([hidden])')) {
      document.body.classList.remove('hh-has-team-modal');
    }
    return true;
  }

  function activateCreateTab(source, targetName) {
    const modal = source?.closest?.(modalSelector);
    if (!modal || !targetName) return false;

    const tabs = modal.querySelectorAll('[data-hh-lfg-create-tab]');
    const panels = modal.querySelectorAll('[data-hh-lfg-create-panel]');
    let activeTitle = '';

    tabs.forEach((tab) => {
      const isActive = tab.getAttribute('data-hh-lfg-create-tab') === targetName;
      tab.classList.toggle('active', isActive);
      tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
      tab.setAttribute('tabindex', isActive ? '0' : '-1');

      if (isActive) {
        activeTitle = tab.getAttribute('data-hh-lfg-create-tab-title') || tab.textContent.trim();
      }
    });

    panels.forEach((panel) => {
      const isActive = panel.getAttribute('data-hh-lfg-create-panel') === targetName;
      panel.classList.toggle('is-active', isActive);
      panel.hidden = !isActive;
    });

    const title = modal.querySelector('[data-hh-lfg-create-panel-title]');
    if (title && activeTitle) {
      title.textContent = activeTitle;
    }

    const content = modal.querySelector('.popup-box-content.limited');
    if (content) {
      content.scrollTop = 0;
      const simplebarContent = content.querySelector('.simplebar-content-wrapper');
      if (simplebarContent) {
        simplebarContent.scrollTop = 0;
      }
    }

    return true;
  }

  function activateTabForField(field) {
    const panel = field?.closest?.('[data-hh-lfg-create-panel]');
    const targetName = panel?.getAttribute?.('data-hh-lfg-create-panel');
    if (!panel || !targetName) return false;

    return activateCreateTab(field, targetName);
  }

  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-hh-lfg-create-modal-trigger], .hh-lfg-create-button.popup-manage-item-trigger');
    if (trigger) {
      event.preventDefault();
      event.stopPropagation();
      openModal(trigger);
      return;
    }

    const tab = event.target.closest('[data-hh-lfg-create-tab]');
    if (tab && activateCreateTab(tab, tab.getAttribute('data-hh-lfg-create-tab'))) {
      event.preventDefault();
      event.stopPropagation();
      return;
    }

    const closer = event.target.closest('[data-hh-lfg-create-modal-close], .hh-lfg-modal-backdrop.is-open');
    if (closer && closeModal(closer)) {
      event.preventDefault();
      event.stopPropagation();
    }
  }, true);

  document.addEventListener('keydown', (event) => {
    const tab = event.target.closest?.('[data-hh-lfg-create-tab]');
    if (tab && (event.key === 'Enter' || event.key === ' ')) {
      if (activateCreateTab(tab, tab.getAttribute('data-hh-lfg-create-tab'))) {
        event.preventDefault();
        event.stopPropagation();
      }
      return;
    }

    if (event.key !== 'Escape') return;
    const openModalNode = document.querySelector('[data-hh-lfg-create-modal].is-open:not([hidden])');
    if (!openModalNode) return;

    const activeElement = document.activeElement;
    if (activeElement && ['INPUT', 'TEXTAREA', 'SELECT'].includes(activeElement.tagName)) {
      activeElement.blur();
      return;
    }

    event.preventDefault();
    closeModal(openModalNode);
  }, true);

  document.addEventListener('invalid', (event) => {
    const field = event.target;
    if (!field?.closest?.(modalSelector)) return;

    activateTabForField(field);
  }, true);

  window.addEventListener('DOMContentLoaded', () => {
    const modal = document.querySelector(modalSelector);
    if (modal?.classList.contains('is-open') && !modal.hidden) {
      const backdrop = getBackdrop(modal);
      setVisible(backdrop, true);
      setVisible(modal, true);
      document.body.classList.add('hh-has-lfg-create-modal', 'hh-has-team-modal');
    }
  });
})();

/* Phase 72b: Team-Feed Composer Counter wie im normalen Feed */
(function () {
  function updateTeamFeedCounter(textarea) {
    const targetId = textarea.getAttribute('data-hh-team-feed-counter');
    const target = targetId ? document.getElementById(targetId) : null;
    if (!target) return;

    const max = Number(textarea.getAttribute('maxlength') || 0);
    const current = textarea.value.length;
    if (!max) {
      target.textContent = String(current);
      return;
    }

    target.textContent = `${Math.max(max - current, 0)}/${max}`;
  }

  document.querySelectorAll('[data-hh-team-feed-counter]').forEach((textarea) => {
    updateTeamFeedCounter(textarea);
  });

  document.addEventListener('input', (event) => {
    const textarea = event.target.closest?.('[data-hh-team-feed-counter]');
    if (!textarea) return;
    updateTeamFeedCounter(textarea);
  });

  document.addEventListener('reset', (event) => {
    const form = event.target.closest?.('.hh-team-feed-form');
    if (!form) return;

    window.setTimeout(() => {
      form.querySelectorAll('[data-hh-team-feed-counter]').forEach((textarea) => {
        updateTeamFeedCounter(textarea);
      });
    }, 0);
  });
})();

/* Phase 72c: Team-Feed Medienvorschau wie im normalen Feed-Composer */
(function () {
  const filesByInput = new WeakMap();
  const urlsByInput = new WeakMap();

  function revokePreviewUrls(input) {
    (urlsByInput.get(input) || []).forEach((url) => URL.revokeObjectURL(url));
    urlsByInput.set(input, []);
  }

  function uniqueFiles(files) {
    const seen = new Set();
    return files.filter((file) => {
      const key = `${file.name}-${file.size}-${file.lastModified}`;
      if (seen.has(key)) return false;
      seen.add(key);
      return true;
    });
  }

  function syncInputFiles(input, files) {
    filesByInput.set(input, files);

    if (typeof DataTransfer === 'undefined') {
      return;
    }

    const transfer = new DataTransfer();
    files.forEach((file) => transfer.items.add(file));
    input.files = transfer.files;
  }

  function createPreviewButton(type, label) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = type;
    button.setAttribute('aria-label', label);
    return button;
  }

  function mediaLimits(input) {
    const maxFiles = Number.parseInt(input.getAttribute('data-hh-media-max-files') || '', 10);
    const maxMb = Number.parseInt(input.getAttribute('data-hh-media-max-mb') || '', 10);

    return {
      maxFiles: Number.isFinite(maxFiles) && maxFiles > 0 ? maxFiles : 0,
      maxMb: Number.isFinite(maxMb) && maxMb > 0 ? maxMb : 0,
      maxBytes: Number.isFinite(maxMb) && maxMb > 0 ? maxMb * 1024 * 1024 : 0,
    };
  }

  function validateSelectedFiles(input, files) {
    const limits = mediaLimits(input);
    let nextFiles = Array.from(files || []);

    if (limits.maxBytes > 0) {
      const oversized = nextFiles.find((file) => Number(file.size || 0) > limits.maxBytes);
      nextFiles = nextFiles.filter((file) => Number(file.size || 0) <= limits.maxBytes);

      if (oversized) {
        hhShowToast(hhT('feed_upload_file_too_large', '“:name” is too large. Maximum allowed size is :limit MB per file.', {
          name: oversized.name || hhT('media_preview_alt', 'Upload preview'),
          limit: String(limits.maxMb),
        }), 'error');
      }
    }

    if (limits.maxFiles > 0 && nextFiles.length > limits.maxFiles) {
      nextFiles = nextFiles.slice(0, limits.maxFiles);
      hhShowToast(hhT('feed_upload_too_many_files', 'You can upload a maximum of :count files per post.', {
        count: String(limits.maxFiles),
      }), 'error');
    }

    return nextFiles;
  }

  function syncAiDisclosure(form, hasFiles) {
    const wrap = form?.querySelector('[data-hh-team-feed-ai-disclosure]');
    const input = wrap?.querySelector('[data-hh-team-feed-ai-disclosure-input]');
    if (!wrap) return;

    wrap.hidden = !hasFiles;
    wrap.classList.toggle('is-visible', hasFiles);

    if (!hasFiles && input) {
      input.checked = false;
    }
  }

  function renderPreview(input) {
    const form = input.closest('.hh-team-feed-form');
    const preview = form?.querySelector('[data-hh-team-feed-preview]');
    const list = form?.querySelector('[data-hh-team-feed-preview-list]');
    const files = filesByInput.get(input) || Array.from(input.files || []);

    if (!preview || !list) return;

    revokePreviewUrls(input);
    list.innerHTML = '';

    if (!files.length) {
      preview.hidden = true;
      syncAiDisclosure(form, false);
      return;
    }

    preview.hidden = false;
    syncAiDisclosure(form, true);
    const createdUrls = [];
    const hasVideo = files.some((file) => file.type.startsWith('video/'));
    list.classList.toggle('is-video-mode', hasVideo);
    list.classList.toggle('is-image-mode', !hasVideo);

    files.forEach((file, index) => {
      const url = URL.createObjectURL(file);
      createdUrls.push(url);

      const item = document.createElement('div');
      item.className = file.type.startsWith('video/') ? 'hh-team-feed-preview-video' : 'hh-team-feed-preview-item';

      if (file.type.startsWith('video/')) {
        const video = document.createElement('video');
        video.src = url;
        video.controls = true;
        video.preload = 'metadata';
        video.playsInline = true;
        item.appendChild(video);
      } else {
        const img = document.createElement('img');
        img.src = url;
        img.alt = file.name || hhT('media_preview_alt', 'Upload preview');
        item.appendChild(img);
      }

      const remove = createPreviewButton('hh-team-feed-preview-remove', hhT('remove_media', 'Remove media'));
      remove.textContent = '×';
      remove.addEventListener('click', () => {
        const nextFiles = (filesByInput.get(input) || []).filter((_, fileIndex) => fileIndex !== index);
        syncInputFiles(input, nextFiles);
        renderPreview(input);
      });
      item.appendChild(remove);
      list.appendChild(item);
    });

    if (!hasVideo) {
      const add = createPreviewButton('hh-team-feed-preview-add', hhT('add_more_media', 'Weiteres Medium hinzufügen'));
      add.innerHTML = '<span>+</span>';
      add.addEventListener('click', () => input.click());
      list.appendChild(add);
    }

    urlsByInput.set(input, createdUrls);
  }

  document.addEventListener('change', (event) => {
    const input = event.target.closest?.('[data-hh-team-feed-media-input]');
    if (!input) return;

    const currentFiles = filesByInput.get(input) || [];
    const incomingFiles = Array.from(input.files || []);
    const nextFiles = validateSelectedFiles(input, uniqueFiles([...currentFiles, ...incomingFiles]));
    syncInputFiles(input, nextFiles);
    renderPreview(input);
  });

  document.addEventListener('reset', (event) => {
    const form = event.target.closest?.('.hh-team-feed-form');
    if (!form) return;

    window.setTimeout(() => {
      const input = form.querySelector('[data-hh-team-feed-media-input]');
      if (!input) return;

      revokePreviewUrls(input);
      syncInputFiles(input, []);
      renderPreview(input);
    }, 0);
  });

  window.addEventListener('beforeunload', () => {
    document.querySelectorAll('[data-hh-team-feed-media-input]').forEach((input) => revokePreviewUrls(input));
  });
})();

/* Phase 72d: Normaler Feed Medienvorschau + Counter wie im Team-Feed */
(function () {
  function updateFeedCounter(textarea) {
    const targetId = textarea.getAttribute('data-hh-feed-counter');
    const target = targetId ? document.getElementById(targetId) : null;
    if (!target) return;

    const max = Number(textarea.getAttribute('maxlength') || 0);
    const current = textarea.value.length;
    target.textContent = max ? `${Math.max(max - current, 0)}/${max}` : String(current);
  }

  document.querySelectorAll('[data-hh-feed-counter]').forEach((textarea) => {
    updateFeedCounter(textarea);
  });

  document.addEventListener('input', (event) => {
    const textarea = event.target.closest?.('[data-hh-feed-counter]');
    if (!textarea) return;
    updateFeedCounter(textarea);
  });

  document.addEventListener('reset', (event) => {
    const form = event.target.closest?.('.hh-feed-form');
    if (!form) return;

    window.setTimeout(() => {
      form.querySelectorAll('[data-hh-feed-counter]').forEach((textarea) => updateFeedCounter(textarea));
    }, 0);
  });
})();

(function () {
  const filesByInput = new WeakMap();
  const urlsByInput = new WeakMap();

  function revokePreviewUrls(input) {
    (urlsByInput.get(input) || []).forEach((url) => URL.revokeObjectURL(url));
    urlsByInput.set(input, []);
  }

  function uniqueFiles(files) {
    const seen = new Set();
    return files.filter((file) => {
      const key = `${file.name}-${file.size}-${file.lastModified}`;
      if (seen.has(key)) return false;
      seen.add(key);
      return true;
    });
  }

  function syncInputFiles(input, files) {
    filesByInput.set(input, files);

    if (typeof DataTransfer === 'undefined') {
      return;
    }

    const transfer = new DataTransfer();
    files.forEach((file) => transfer.items.add(file));
    input.files = transfer.files;
  }

  function createPreviewButton(type, label) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = type;
    button.setAttribute('aria-label', label);
    return button;
  }

  function mediaLimits(input) {
    const maxFiles = Number.parseInt(input.getAttribute('data-hh-media-max-files') || '', 10);
    const maxMb = Number.parseInt(input.getAttribute('data-hh-media-max-mb') || '', 10);

    return {
      maxFiles: Number.isFinite(maxFiles) && maxFiles > 0 ? maxFiles : 0,
      maxMb: Number.isFinite(maxMb) && maxMb > 0 ? maxMb : 0,
      maxBytes: Number.isFinite(maxMb) && maxMb > 0 ? maxMb * 1024 * 1024 : 0,
    };
  }

  function validateSelectedFiles(input, files) {
    const limits = mediaLimits(input);
    let nextFiles = Array.from(files || []);

    if (limits.maxBytes > 0) {
      const oversized = nextFiles.find((file) => Number(file.size || 0) > limits.maxBytes);
      nextFiles = nextFiles.filter((file) => Number(file.size || 0) <= limits.maxBytes);

      if (oversized) {
        hhShowToast(hhT('feed_upload_file_too_large', '“:name” is too large. Maximum allowed size is :limit MB per file.', {
          name: oversized.name || hhT('media_preview_alt', 'Upload preview'),
          limit: String(limits.maxMb),
        }), 'error');
      }
    }

    if (limits.maxFiles > 0 && nextFiles.length > limits.maxFiles) {
      nextFiles = nextFiles.slice(0, limits.maxFiles);
      hhShowToast(hhT('feed_upload_too_many_files', 'You can upload a maximum of :count files per post.', {
        count: String(limits.maxFiles),
      }), 'error');
    }

    return nextFiles;
  }

  function syncAiDisclosure(form, hasFiles) {
    const wrap = form?.querySelector('[data-hh-feed-ai-disclosure]');
    const input = wrap?.querySelector('[data-hh-feed-ai-disclosure-input]');
    if (!wrap) return;

    wrap.hidden = !hasFiles;
    wrap.classList.toggle('is-visible', hasFiles);

    if (!hasFiles && input) {
      input.checked = false;
    }
  }

  function renderPreview(input) {
    const form = input.closest('.hh-feed-form');
    const preview = form?.querySelector('[data-hh-feed-preview]');
    const list = form?.querySelector('[data-hh-feed-preview-list]');
    const files = filesByInput.get(input) || Array.from(input.files || []);

    if (!preview || !list) return;

    revokePreviewUrls(input);
    list.innerHTML = '';

    if (!files.length) {
      preview.hidden = true;
      syncAiDisclosure(form, false);
      return;
    }

    preview.hidden = false;
    syncAiDisclosure(form, true);
    const createdUrls = [];
    const hasVideo = files.some((file) => file.type.startsWith('video/'));
    list.classList.toggle('is-video-mode', hasVideo);
    list.classList.toggle('is-image-mode', !hasVideo);

    files.forEach((file, index) => {
      const url = URL.createObjectURL(file);
      createdUrls.push(url);

      const item = document.createElement('div');
      item.className = file.type.startsWith('video/') ? 'hh-feed-preview-video' : 'hh-feed-preview-item';

      if (file.type.startsWith('video/')) {
        const video = document.createElement('video');
        video.src = url;
        video.controls = true;
        video.preload = 'metadata';
        video.playsInline = true;
        item.appendChild(video);
      } else {
        const img = document.createElement('img');
        img.src = url;
        img.alt = file.name || hhT('media_preview_alt', 'Upload preview');
        item.appendChild(img);
      }

      const remove = createPreviewButton('hh-feed-preview-remove', hhT('remove_media', 'Remove media'));
      remove.textContent = '×';
      remove.addEventListener('click', () => {
        const nextFiles = (filesByInput.get(input) || []).filter((_, fileIndex) => fileIndex !== index);
        syncInputFiles(input, nextFiles);
        renderPreview(input);
      });
      item.appendChild(remove);
      list.appendChild(item);
    });

    if (!hasVideo) {
      const add = createPreviewButton('hh-feed-preview-add', hhT('add_more_media', 'Weiteres Medium hinzufügen'));
      add.innerHTML = '<span>+</span>';
      add.addEventListener('click', () => input.click());
      list.appendChild(add);
    }

    urlsByInput.set(input, createdUrls);
  }

  document.addEventListener('change', (event) => {
    const input = event.target.closest?.('[data-hh-feed-media-input]');
    if (!input) return;

    const currentFiles = filesByInput.get(input) || [];
    const incomingFiles = Array.from(input.files || []);
    const nextFiles = validateSelectedFiles(input, uniqueFiles([...currentFiles, ...incomingFiles]));
    syncInputFiles(input, nextFiles);
    renderPreview(input);
  });

  document.addEventListener('reset', (event) => {
    const form = event.target.closest?.('.hh-feed-form');
    if (!form) return;

    window.setTimeout(() => {
      const input = form.querySelector('[data-hh-feed-media-input]');
      if (!input) return;

      revokePreviewUrls(input);
      syncInputFiles(input, []);
      renderPreview(input);
    }, 0);
  });

  window.addEventListener('beforeunload', () => {
    document.querySelectorAll('[data-hh-feed-media-input]').forEach((input) => revokePreviewUrls(input));
  });

})();

/* Phase 21: sichtbarer Upload-Fortschritt für Feed- und Team-Feed-Medien */
(function () {
  function hhUploadSelectedFiles(form) {
    return Array.from(form.querySelectorAll('input[type="file"]'))
      .flatMap((input) => Array.from(input.files || []));
  }

  function hhFormatBytes(bytes) {
    const size = Number(bytes || 0);
    if (!size) return '0 MB';
    const mb = size / 1024 / 1024;
    if (mb >= 1) return `${mb.toFixed(mb >= 10 ? 0 : 1)} MB`;
    return `${Math.max(1, Math.round(size / 1024))} KB`;
  }

  function hhEnsureUploadProgress(form) {
    let progress = form.querySelector('[data-hh-feed-upload-progress]');
    if (progress) return progress;

    progress = document.createElement('div');
    progress.className = 'hh-feed-upload-progress';
    progress.setAttribute('data-hh-feed-upload-progress', '');
    progress.hidden = true;
    progress.innerHTML = `
      <div class="hh-feed-upload-progress-head">
        <span class="hh-feed-upload-progress-kicker">${hhT('upload_running', 'Upload läuft')}</span>
        <strong data-hh-feed-upload-progress-percent>0%</strong>
      </div>
      <div class="hh-feed-upload-progress-bar" aria-hidden="true"><span data-hh-feed-upload-progress-bar></span></div>
      <div class="hh-feed-upload-progress-meta">
        <span data-hh-feed-upload-progress-status>${hhT('upload_processing', 'Video/Bilder werden hochgeladen …')}</span>
        <span data-hh-feed-upload-progress-bytes></span>
      </div>
    `;

    const footer = form.querySelector('.quick-post-footer');
    if (footer) {
      form.insertBefore(progress, footer);
    } else {
      form.appendChild(progress);
    }

    return progress;
  }

  function hhSetUploadProgress(progress, percent, status, bytesText) {
    const clamped = Math.max(0, Math.min(100, Number(percent || 0)));
    const rounded = Math.round(clamped);
    const bar = progress.querySelector('[data-hh-feed-upload-progress-bar]');
    const percentNode = progress.querySelector('[data-hh-feed-upload-progress-percent]');
    const statusNode = progress.querySelector('[data-hh-feed-upload-progress-status]');
    const bytesNode = progress.querySelector('[data-hh-feed-upload-progress-bytes]');

    progress.hidden = false;
    progress.classList.toggle('is-indeterminate', !Number.isFinite(clamped) || rounded <= 0);

    if (bar) bar.style.width = `${rounded}%`;
    if (percentNode) percentNode.textContent = `${rounded}%`;
    if (statusNode && status) statusNode.textContent = status;
    if (bytesNode) bytesNode.textContent = bytesText || '';
  }

  function hhSetUploadDisabled(form, disabled) {
    form.querySelectorAll('button, input, select, textarea, label[for]').forEach((node) => {
      if (node.matches('label[for]')) {
        node.classList.toggle('is-disabled', disabled);
        return;
      }

      if (disabled) {
        if (!node.hasAttribute('data-hh-upload-was-disabled')) {
          node.setAttribute('data-hh-upload-was-disabled', node.disabled ? '1' : '0');
        }
        node.disabled = true;
      } else if (node.getAttribute('data-hh-upload-was-disabled') === '0') {
        node.disabled = false;
        node.removeAttribute('data-hh-upload-was-disabled');
      } else {
        node.removeAttribute('data-hh-upload-was-disabled');
      }
    });
  }

  function hhUploadFormWithProgress(form, progress, files, formData) {
    return new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      const token = hhCsrfToken();
      const totalBytes = files.reduce((sum, file) => sum + Number(file.size || 0), 0);
      const payload = formData instanceof FormData ? formData : new FormData(form);

      xhr.open('POST', form.getAttribute('action') || window.location.href, true);
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
      xhr.setRequestHeader('Accept', 'application/json');
      if (token) xhr.setRequestHeader('X-CSRF-TOKEN', token);

      xhr.upload.addEventListener('loadstart', () => {
        hhSetUploadProgress(progress, 0, hhT('upload_preparing', 'Preparing upload …'), totalBytes ? `0 / ${hhFormatBytes(totalBytes)}` : '');
      });

      xhr.upload.addEventListener('progress', (event) => {
        if (!event.lengthComputable) {
          hhSetUploadProgress(progress, 12, hhT('upload_running_dots', 'Upload läuft …'), totalBytes ? `0 / ${hhFormatBytes(totalBytes)}` : '');
          return;
        }

        const percent = event.total > 0 ? (event.loaded / event.total) * 100 : 0;
        hhSetUploadProgress(
          progress,
          percent,
          percent >= 100 ? hhT('upload_done_processing', 'Upload abgeschlossen, Beitrag wird verarbeitet …') : hhT('upload_processing', 'Video/Bilder werden hochgeladen …'),
          `${hhFormatBytes(event.loaded)} / ${hhFormatBytes(event.total)}`
        );
      });

      xhr.addEventListener('load', () => {
        let payload = {};
        try {
          payload = xhr.responseText ? JSON.parse(xhr.responseText) : {};
        } catch (error) {
          payload = {};
        }

        if (xhr.status >= 200 && xhr.status < 300) {
          resolve(payload);
          return;
        }

        const message = payload.message || hhT('upload_failed', 'Upload could not be completed.');
        reject(new Error(message));
      });

      xhr.addEventListener('error', () => reject(new Error(hhT('upload_failed', 'Upload could not be completed.'))));
      xhr.addEventListener('abort', () => reject(new Error(hhT('upload_aborted', 'Upload aborted.'))));

      xhr.send(payload);
    });
  }

  document.addEventListener('submit', async (event) => {
    const form = event.target.closest?.('.hh-feed-form, .hh-team-feed-form');
    if (!form) return;

    const files = hhUploadSelectedFiles(form);
    if (!files.length) return;

    event.preventDefault();
    event.stopImmediatePropagation();

    if (form.dataset.hhUploadSubmitting === '1') return;
    form.dataset.hhUploadSubmitting = '1';
    form.classList.add('is-uploading');

    const progress = hhEnsureUploadProgress(form);
    const submit = form.querySelector('[type="submit"]');
    const defaultSubmitText = submit?.textContent || '';

    // Wichtig: FormData muss vor dem Deaktivieren der Felder erstellt werden.
    // Disabled inputs/selects werden von FormData sonst nicht übertragen
    // (z. B. visibility bei Medien-Posts).
    const formData = new FormData(form);

    hhSetUploadDisabled(form, true);
    if (submit) submit.textContent = hhT('upload_running_dots', 'Upload läuft …');
    hhSetUploadProgress(progress, 0, hhT('upload_preparing', 'Preparing upload …'), '');

    try {
      const payload = await hhUploadFormWithProgress(form, progress, files, formData);
      hhSetUploadProgress(progress, 100, hhT('done_open_post', 'Fertig. Beitrag wird geöffnet …'), '');
      hhShowToast(payload.message || hhT('post_published', 'Beitrag wurde veröffentlicht.'), 'success');
      window.setTimeout(() => {
        window.location.href = payload.redirect_url || window.location.href;
      }, 450);
    } catch (error) {
      console.error(error);
      hhShowToast(error.message || hhT('upload_failed', 'Upload could not be completed.'), 'error');
      progress.hidden = true;
      if (submit) submit.textContent = defaultSubmitText;
      hhSetUploadDisabled(form, false);
      form.classList.remove('is-uploading');
      form.dataset.hhUploadSubmitting = '0';
    }
  }, true);
})();

/* Phase 158: Notifications page uses server-side manual pagination only. */

/* p224: Mobile Bottom Profil-Sheet */
(function () {
  const sheet = document.getElementById('hh-mobile-profile-sheet');
  const backdrop = document.querySelector('[data-hh-mobile-profile-close].hh-mobile-profile-backdrop');
  const triggers = document.querySelectorAll('[data-hh-mobile-profile-open]');

  if (!sheet || !triggers.length) return;

  function setOpenState(isOpen) {
    sheet.hidden = !isOpen;
    sheet.setAttribute('aria-hidden', isOpen ? 'false' : 'true');

    if (backdrop) {
      backdrop.hidden = !isOpen;
    }

    document.documentElement.classList.toggle('hh-mobile-profile-open', isOpen);
    triggers.forEach((trigger) => trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false'));

    if (isOpen) {
      const closeButton = sheet.querySelector('[data-hh-mobile-profile-close]');
      window.setTimeout(() => closeButton?.focus({ preventScroll: true }), 30);
    }
  }

  triggers.forEach((trigger) => {
    trigger.addEventListener('click', () => {
      setOpenState(sheet.getAttribute('aria-hidden') !== 'false');
    });
  });

  document.addEventListener('click', (event) => {
    const close = event.target.closest?.('[data-hh-mobile-profile-close]');
    if (!close) return;
    setOpenState(false);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    if (sheet.getAttribute('aria-hidden') === 'false') {
      setOpenState(false);
    }
  });
})();

/* Phase 24: Frontend report modal */
(function () {
  const modal = document.querySelector('[data-hh-report-modal]');
  const form = document.querySelector('[data-hh-report-form]');

  if (!modal || !form) {
    return;
  }

  const typeInput = form.querySelector('[data-hh-report-type]');
  const idInput = form.querySelector('[data-hh-report-id]');
  const reasonInputs = Array.from(form.querySelectorAll('input[name="reason"]'));
  const bodyInput = form.querySelector('textarea[name="body"]');
  const submitButton = form.querySelector('[data-hh-report-submit]');
  const targetTitle = modal.querySelector('[data-hh-report-title]');
  const targetSubtitle = modal.querySelector('[data-hh-report-subtitle]');
  const defaultSubmitHtml = submitButton?.innerHTML || 'Meldung senden';

  function hhReportTypeLabel(type) {
    const labels = {
      user: hhT('report_user', 'Profil'),
      feed_post: hhT('report_feed_post', 'Feed-Beitrag'),
      feed_comment: hhT('report_feed_comment', 'Kommentar'),
      team: 'Team',
      lfg: 'LFG',
      team_lfg: 'Team-LFG',
      media: 'Medium',
      moment: 'Moment',
      moment_comment: hhT('report_moment_comment', 'Moment-Kommentar'),
      cup: 'Cup',
      cup_submission: 'Cup-Einreichung'
    };

    return labels[type] || 'Inhalt';
  }

  function hhReportTargetFromLabel(label, type) {
    const cleanLabel = (label || '').trim();
    const fallbackType = hhReportTypeLabel(type);

    if (!cleanLabel) {
      return { title: hhT('selected_content', 'Ausgewählter Inhalt'), subtitle: fallbackType };
    }

    const colonIndex = cleanLabel.indexOf(':');
    if (colonIndex > -1) {
      const prefix = cleanLabel.slice(0, colonIndex).trim();
      const title = cleanLabel.slice(colonIndex + 1).trim();

      return {
        title: title || cleanLabel,
        subtitle: prefix || fallbackType
      };
    }

    const byMatch = cleanLabel.match(/^(.+?)\s+von\s+(.+)$/i);
    if (byMatch) {
      return {
        title: byMatch[1].trim(),
        subtitle: `von ${byMatch[2].trim()}`
      };
    }

    return { title: cleanLabel, subtitle: fallbackType };
  }

  function updateReasonState() {
    reasonInputs.forEach((input) => {
      const item = input.closest('.hh-report-reason');
      if (item) {
        item.classList.toggle('is-checked', input.checked);
      }
    });
  }

  function reportSelectorValue(value) {
    const raw = String(value || '');

    if (window.CSS && typeof window.CSS.escape === 'function') {
      return window.CSS.escape(raw);
    }

    return raw.replace(/(["\\])/g, '\\$1');
  }

  function isReportedTrigger(trigger) {
    return trigger?.getAttribute('data-hh-report-reported') === '1';
  }

  function syncReportedTrigger(trigger) {
    if (!trigger) {
      return;
    }

    trigger.classList.add('is-reported');
    trigger.setAttribute('data-hh-report-reported', '1');
    trigger.setAttribute('aria-disabled', 'true');
    trigger.setAttribute('title', 'Bereits gemeldet');
    trigger.setAttribute('data-title', 'Bereits gemeldet');
    trigger.setAttribute('aria-label', 'Bereits gemeldet');

    if (trigger.classList.contains('hh-report-menu-link')) {
      const original = trigger.textContent || '';
      trigger.textContent = original.toLowerCase().includes('beitrag') ? hhT('post_reported', 'Beitrag gemeldet') : hhT('reported', 'Gemeldet');
      return;
    }

    const label = trigger.querySelector('.hh-report-button-label') || trigger.querySelector('span');
    if (label) {
      label.textContent = hhT('reported', 'Gemeldet');
    }
  }

  function markReportTarget(type, id) {
    if (!type || !id) {
      return;
    }

    const selector = `[data-hh-report-open][data-hh-report-type="${reportSelectorValue(type)}"][data-hh-report-id="${reportSelectorValue(id)}"]`;
    document.querySelectorAll(selector).forEach(syncReportedTrigger);
  }

  document.querySelectorAll('[data-hh-report-open][data-hh-report-reported="1"]').forEach(syncReportedTrigger);

  function setOpen(isOpen) {
    modal.hidden = !isOpen;
    modal.classList.toggle('is-open', isOpen);
    modal.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
    document.documentElement.classList.toggle('hh-report-modal-is-open', isOpen);

    if (isOpen) {
      updateReasonState();
      window.setTimeout(() => reasonInputs[0]?.focus({ preventScroll: true }), 40);
    }
  }

  function openReportModal(trigger) {
    const type = trigger.getAttribute('data-hh-report-type') || '';
    const id = trigger.getAttribute('data-hh-report-id') || '';
    const label = trigger.getAttribute('data-hh-report-label') || hhT('selected_content', 'Ausgewählter Inhalt');
    const customTitle = trigger.getAttribute('data-hh-report-title');
    const customSubtitle = trigger.getAttribute('data-hh-report-subtitle');

    if (!type || !id) {
      hhShowToast(hhT('report_open_failed', 'Diese Meldung kann nicht geöffnet werden.'), 'error');
      return;
    }

    const target = hhReportTargetFromLabel(label, type);

    form.reset();
    if (typeInput) typeInput.value = type;
    if (idInput) idInput.value = id;
    if (targetTitle) targetTitle.textContent = customTitle || target.title;
    if (targetSubtitle) targetSubtitle.textContent = customSubtitle || target.subtitle;
    if (bodyInput) bodyInput.value = '';
    updateReasonState();
    setOpen(true);
  }

  function closeReportModal() {
    setOpen(false);
  }

  reasonInputs.forEach((input) => {
    input.addEventListener('change', updateReasonState);
  });

  document.addEventListener('click', (event) => {
    const trigger = event.target.closest?.('[data-hh-report-open]');

    if (trigger) {
      event.preventDefault();

      if (isReportedTrigger(trigger)) {
        hhShowToast('Diese Meldung liegt bereits bei der Moderation.', 'success');
        return;
      }

      openReportModal(trigger);
      return;
    }

    if (event.target.closest?.('[data-hh-report-close]')) {
      event.preventDefault();
      closeReportModal();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && modal.classList.contains('is-open')) {
      closeReportModal();
    }
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const checkedReason = form.querySelector('input[name="reason"]:checked');

    if (!form.action || !typeInput?.value || !idInput?.value || !checkedReason?.value) {
      hhShowToast(form.getAttribute('data-error-message') || 'Die Meldung konnte nicht gespeichert werden.', 'error');
      return;
    }

    if (submitButton) {
      submitButton.disabled = true;
      submitButton.innerHTML = `<span>${hhT('sending', 'Senden…')}</span>`;
    }

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'X-CSRF-TOKEN': hhCsrfToken(),
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        body: new FormData(form)
      });

      const payload = await response.json().catch(() => ({}));

      if (!response.ok) {
        const validationMessage = payload.errors ? Object.values(payload.errors).flat().shift() : null;
        throw new Error(validationMessage || payload.message || form.getAttribute('data-error-message') || 'Die Meldung konnte nicht gespeichert werden.');
      }

      const reportType = payload.report?.type || typeInput.value;
      const reportId = payload.report?.id || idInput.value;

      closeReportModal();
      markReportTarget(reportType, reportId);
      if (bodyInput) bodyInput.value = '';
      hhShowToast(payload.message || form.getAttribute('data-success-message') || hhT('report_success', 'Danke, die Meldung wurde an die Moderation übergeben.'), 'success');
    } catch (error) {
      hhShowToast(error.message || form.getAttribute('data-error-message') || 'Die Meldung konnte nicht gespeichert werden.', 'error');
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.innerHTML = defaultSubmitHtml;
      }
    }
  });
})();

/* Phase 17: Feed post inline edit like Vikinger in-text editor */
(function () {
  function updateCounter(form) {
    const textarea = form?.querySelector('[data-hh-post-edit-body]');
    const counter = form?.querySelector('[data-hh-post-edit-count]');

    if (!textarea || !counter) {
      return;
    }

    const max = Number(textarea.getAttribute('maxlength') || 5000);
    counter.textContent = `${textarea.value.length}/${max}`;
  }

  function closeInlineEdit(form, restore = true) {
    if (!form) {
      return;
    }

    const postId = form.getAttribute('data-post-id');
    const textarea = form.querySelector('[data-hh-post-edit-body]');
    const bodyText = postId ? document.querySelector(`[data-hh-post-body-text="${postId}"]`) : null;
    const article = postId ? document.querySelector(`[data-hh-feed-post-card="${postId}"]`) : form.closest('[data-hh-feed-post-card]');

    if (restore && textarea) {
      textarea.value = form.getAttribute('data-hh-post-original') || '';
      updateCounter(form);
    }

    form.hidden = true;
    article?.classList.remove('hh-post-is-inline-editing');

    if (bodyText) {
      bodyText.hidden = !bodyText.innerHTML.trim();
    }
  }

  function closeAllInlineEdits(exceptForm = null) {
    document.querySelectorAll('[data-hh-post-inline-edit]').forEach((form) => {
      if (form !== exceptForm && !form.hidden) {
        closeInlineEdit(form, true);
      }
    });
  }

  function openInlineEdit(trigger) {
    const postId = trigger.getAttribute('data-post-id');
    const form = postId ? document.querySelector(`[data-hh-post-inline-edit="${postId}"]`) : null;
    const bodyText = postId ? document.querySelector(`[data-hh-post-body-text="${postId}"]`) : null;
    const article = postId ? document.querySelector(`[data-hh-feed-post-card="${postId}"]`) : null;
    const textarea = form?.querySelector('[data-hh-post-edit-body]');

    trigger.closest('details[open]')?.removeAttribute('open');

    if (!form || !textarea) {
      return;
    }

    closeAllInlineEdits(form);

    form.setAttribute('data-hh-post-original', textarea.value || '');
    bodyText && (bodyText.hidden = true);
    form.hidden = false;
    article?.classList.add('hh-post-is-inline-editing');
    updateCounter(form);

    if (textarea.dataset.hhPostEditBound !== '1') {
      textarea.dataset.hhPostEditBound = '1';
      textarea.addEventListener('input', () => updateCounter(form));
      textarea.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
          event.preventDefault();
          closeInlineEdit(form, true);
        }

        if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
          event.preventDefault();
          form.requestSubmit();
        }
      });
    }

    window.setTimeout(() => {
      textarea.focus({ preventScroll: true });
      textarea.setSelectionRange(textarea.value.length, textarea.value.length);
    }, 30);
  }

  document.addEventListener('click', (event) => {
    const editTrigger = event.target.closest?.('[data-hh-post-edit-open]');

    if (editTrigger) {
      event.preventDefault();
      event.stopPropagation();
      openInlineEdit(editTrigger);
      return;
    }

    const cancelTrigger = event.target.closest?.('[data-hh-post-edit-cancel]');

    if (cancelTrigger) {
      event.preventDefault();
      event.stopPropagation();
      closeInlineEdit(cancelTrigger.closest('[data-hh-post-inline-edit]'), true);
    }
  });

  document.addEventListener('submit', async (event) => {
    const form = event.target.closest?.('[data-hh-post-inline-edit]');

    if (!form) {
      return;
    }

    event.preventDefault();

    const postId = form.getAttribute('data-post-id');
    const url = form.getAttribute('action');
    const textarea = form.querySelector('[data-hh-post-edit-body]');
    const visibility = form.querySelector('[name="visibility"]')?.value || 'public';
    const backgroundStyle = form.querySelector('[name="background_style"]')?.value || '';
    const feelingKey = form.querySelector('[name="feeling_key"]')?.value || '';
    const body = textarea?.value.trim() || '';

    if (!url || !textarea || !body) {
      textarea?.focus();
      hhShowToast(hhT('feed_body_empty', 'Bitte gib einen Beitragstext ein.'), 'error');
      return;
    }

    form.classList.add('is-saving');

    try {
      const payload = await hhPostFormUrlencoded(url, {
        _method: 'PUT',
        body,
        visibility,
        background_style: backgroundStyle,
        feeling_key: feelingKey
      });

      const bodyText = postId ? document.querySelector(`[data-hh-post-body-text="${postId}"]`) : null;
      const html = payload.body_html || hhCommentEscapeHtml(body).replaceAll('\n', '<br>');

      if (bodyText) {
        bodyText.innerHTML = bodyText.classList.contains('hh-feed-text-background-card') ? `<p>${html}</p>` : html;
        bodyText.hidden = false;
        window.hhRefreshFeedPostTextCollapse?.(bodyText);
      }

      textarea.value = payload.body || body;
      form.setAttribute('data-hh-post-original', textarea.value);
      closeInlineEdit(form, false);
      updateCounter(form);
      hhShowToast(hhT('post_updated', 'Beitrag wurde aktualisiert.'), 'success');
    } catch (error) {
      console.error(error);
      hhShowToast(hhT('post_update_failed', 'Der Beitrag konnte nicht gespeichert werden.'), 'error');
    } finally {
      form.classList.remove('is-saving');
    }
  });

})();

/* Phase 32: Collapse very long feed post texts */
(function () {
  const selector = '[data-hh-post-body-text]';
  const maxHeight = 156;

  function ensureToggle(body) {
    let toggle = body.nextElementSibling?.matches?.('[data-hh-post-text-toggle]') ? body.nextElementSibling : null;

    if (!toggle) {
      toggle = document.createElement('button');
      toggle.type = 'button';
      toggle.className = 'hh-feed-post-text-toggle';
      toggle.setAttribute('data-hh-post-text-toggle', '');
      body.insertAdjacentElement('afterend', toggle);
    }

    return toggle;
  }

  function removeToggle(body) {
    const toggle = body.nextElementSibling?.matches?.('[data-hh-post-text-toggle]') ? body.nextElementSibling : null;

    if (toggle) {
      toggle.remove();
    }
  }

  function setToggleLabel(body, toggle) {
    const expanded = body.classList.contains('is-expanded');
    toggle.textContent = expanded ? 'Weniger anzeigen' : 'Mehr anzeigen';
    toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
  }

  function refresh(body) {
    if (!body || body.hidden) {
      return;
    }

    const wasExpanded = body.classList.contains('is-expanded');

    body.classList.remove('hh-feed-post-body-collapsible', 'is-expanded');
    removeToggle(body);

    window.requestAnimationFrame(() => {
      if (!body.isConnected || body.hidden) {
        return;
      }

      if (body.scrollHeight <= maxHeight + 8) {
        return;
      }

      body.classList.add('hh-feed-post-body-collapsible');

      if (wasExpanded) {
        body.classList.add('is-expanded');
      }

      if (!body.id) {
        const postId = body.getAttribute('data-hh-post-body-text') || Math.random().toString(36).slice(2);
        body.id = `hh-feed-post-body-${postId}`;
      }

      const toggle = ensureToggle(body);
      toggle.setAttribute('aria-controls', body.id);
      setToggleLabel(body, toggle);
    });
  }

  function refreshAll() {
    document.querySelectorAll(selector).forEach(refresh);
  }

  document.addEventListener('click', (event) => {
    const toggle = event.target.closest?.('[data-hh-post-text-toggle]');

    if (!toggle) {
      return;
    }

    const body = toggle.previousElementSibling?.matches?.(selector) ? toggle.previousElementSibling : null;

    if (!body) {
      return;
    }

    event.preventDefault();
    body.classList.toggle('is-expanded');
    setToggleLabel(body, toggle);
  });

  window.hhRefreshFeedPostTextCollapse = refresh;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', refreshAll, { once: true });
  } else {
    refreshAll();
  }

  window.addEventListener('load', refreshAll, { once: true });
  window.addEventListener('resize', () => window.requestAnimationFrame(refreshAll));
})();


/* Phase 27: Friends-only mentions autocomplete for feed posts/comments */
(function () {
  const selector = '[data-hh-mention-context]';
  let activeField = null;
  let activeRange = null;
  let activeItems = [];
  let activeIndex = 0;
  let requestTimer = null;
  let requestController = null;

  const menu = document.createElement('div');
  menu.className = 'hh-mention-menu';
  menu.hidden = true;
  document.body.appendChild(menu);

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function tokenBeforeCaret(field) {
    const caret = Number(field.selectionStart || 0);
    const before = String(field.value || '').slice(0, caret);
    const match = before.match(/(^|\s)@([A-Za-z0-9_.-]{0,32})$/);

    if (!match) {
      return null;
    }

    return {
      start: caret - match[2].length - 1,
      end: caret,
      query: match[2] || ''
    };
  }

  function positionMenu(field) {
    const rect = field.getBoundingClientRect();
    const maxWidth = Math.min(340, window.innerWidth - 24);
    const width = Math.min(Math.max(rect.width, 260), maxWidth);
    const left = Math.min(Math.max(12, rect.left), window.innerWidth - width - 12);
    const top = Math.min(rect.bottom + 8, window.innerHeight - 260);

    menu.style.width = `${width}px`;
    menu.style.left = `${left}px`;
    menu.style.top = `${Math.max(12, top)}px`;
  }

  function closeMenu() {
    menu.hidden = true;
    menu.innerHTML = '';
    activeField = null;
    activeRange = null;
    activeItems = [];
    activeIndex = 0;

    if (requestController) {
      requestController.abort();
      requestController = null;
    }
  }

  function renderMenu(users) {
    activeItems = Array.isArray(users) ? users : [];
    activeIndex = 0;

    if (!activeField || !activeRange || activeItems.length === 0) {
      closeMenu();
      return;
    }

    menu.innerHTML = activeItems.map((user, index) => `
      <button class="hh-mention-menu-item ${index === activeIndex ? 'is-active' : ''}" type="button" data-hh-mention-pick="${index}">
        <span class="hh-mention-menu-avatar"><img src="${escapeHtml(user.avatar_url)}" alt=""></span>
        <span class="hh-mention-menu-copy">
          <strong>${escapeHtml(user.name || user.username || 'User')}</strong>
          <small>@${escapeHtml(user.username || '')}</small>
        </span>
      </button>
    `).join('');

    positionMenu(activeField);
    menu.hidden = false;
  }

  function updateActiveItem() {
    menu.querySelectorAll('[data-hh-mention-pick]').forEach((node, index) => {
      node.classList.toggle('is-active', index === activeIndex);
    });
  }

  function pick(index) {
    const user = activeItems[index];

    if (!activeField || !activeRange || !user?.username) {
      closeMenu();
      return;
    }

    const value = String(activeField.value || '');
    const mention = `@${user.username} `;
    activeField.value = value.slice(0, activeRange.start) + mention + value.slice(activeRange.end);
    const caret = activeRange.start + mention.length;

    activeField.focus();
    activeField.setSelectionRange(caret, caret);
    activeField.dispatchEvent(new Event('input', {bubbles: true}));
    closeMenu();
  }

  function scheduleSearch(field) {
    const range = tokenBeforeCaret(field);

    if (!range) {
      closeMenu();
      return;
    }

    activeField = field;
    activeRange = range;
    positionMenu(field);

    window.clearTimeout(requestTimer);
    requestTimer = window.setTimeout(async () => {
      try {
        if (requestController) {
          requestController.abort();
        }

        requestController = new AbortController();
        const context = field.getAttribute('data-hh-mention-context') || 'feed';
        let teamId = field.getAttribute('data-hh-mention-team-id') || '';
        const teamFieldSelector = field.getAttribute('data-hh-mention-team-field') || '';

        if (!teamId && teamFieldSelector) {
          const form = field.closest('form');
          const teamField = (form && form.querySelector(teamFieldSelector)) || document.querySelector(teamFieldSelector);

          if (teamField) {
            teamId = teamField.value || '';
          }
        }

        const params = new URLSearchParams({
          q: range.query,
          context
        });

        if (teamId) {
          params.set('team_id', teamId);
        }

        const response = await fetch(`/mentions/search?${params.toString()}`, {
          credentials: 'same-origin',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          signal: requestController.signal
        });

        if (!response.ok) {
          throw new Error('mention search failed');
        }

        const payload = await response.json();
        renderMenu(payload.users || []);
      } catch (error) {
        if (error.name !== 'AbortError') {
          closeMenu();
        }
      }
    }, 130);
  }

  document.addEventListener('input', (event) => {
    const field = event.target.closest?.(selector);

    if (!field) {
      return;
    }

    scheduleSearch(field);
  });

  document.addEventListener('keydown', (event) => {
    const field = event.target.closest?.(selector);

    if (!field || menu.hidden) {
      return;
    }

    if (event.key === 'ArrowDown') {
      event.preventDefault();
      activeIndex = Math.min(activeItems.length - 1, activeIndex + 1);
      updateActiveItem();
      return;
    }

    if (event.key === 'ArrowUp') {
      event.preventDefault();
      activeIndex = Math.max(0, activeIndex - 1);
      updateActiveItem();
      return;
    }

    if (event.key === 'Enter' || event.key === 'Tab') {
      event.preventDefault();
      pick(activeIndex);
      return;
    }

    if (event.key === 'Escape') {
      event.preventDefault();
      closeMenu();
    }
  });

  document.addEventListener('mousedown', (event) => {
    const pickButton = event.target.closest?.('[data-hh-mention-pick]');

    if (pickButton) {
      event.preventDefault();
      pick(Number(pickButton.getAttribute('data-hh-mention-pick') || 0));
      return;
    }

    if (!event.target.closest?.('.hh-mention-menu') && !event.target.closest?.(selector)) {
      closeMenu();
    }
  });

  window.addEventListener('resize', () => {
    if (!menu.hidden && activeField) {
      positionMenu(activeField);
    }
  });

  window.addEventListener('scroll', () => {
    if (!menu.hidden && activeField) {
      positionMenu(activeField);
    }
  }, true);
})();


/* HH Phase 85: Optional DE/EN feed translations */
(() => {
  document.addEventListener('click', async (event) => {
    const button = event.target.closest?.('[data-hh-translation-trigger]');

    if (!button) {
      return;
    }

    event.preventDefault();

    const wrap = button.closest('[data-hh-translation-wrap]');
    const result = wrap?.querySelector('[data-hh-translation-result]');
    const label = button.querySelector('span') || button;
    const url = button.getAttribute('data-url') || '';
    const locale = button.getAttribute('data-locale') || document.documentElement?.lang?.slice(0, 2) || 'de';
    const defaultLabel = button.getAttribute('data-label-default') || hhT('translation_show', 'Übersetzung anzeigen');
    const loadingLabel = button.getAttribute('data-label-loading') || hhT('translation_loading', 'Wird übersetzt...');
    const errorLabel = button.getAttribute('data-label-error') || hhT('translation_error', 'Übersetzung fehlgeschlagen');

    if (!url || !result || button.disabled) {
      return;
    }

    if (result.dataset.loaded === '1') {
      const isHidden = result.hidden;
      result.hidden = !isHidden;
      label.textContent = isHidden ? hhT('translation_hide', 'Übersetzung ausblenden') : defaultLabel;
      return;
    }

    button.disabled = true;
    label.textContent = loadingLabel;

    try {
      const payload = await hhPostFormUrlencoded(url, { locale });

      if (!payload?.ok || !payload.translated_html) {
        throw new Error('translation failed');
      }

      const meta = payload.meta_label ? `<small>${hhCommentEscapeHtml(payload.meta_label)} · ${hhCommentEscapeHtml(payload.provider_label || hhT('translation_provider_ai', 'KI-Übersetzung'))}</small>` : '';
      result.innerHTML = `${meta}<p>${payload.translated_html}</p>`;
      result.dataset.loaded = '1';
      result.hidden = false;
      button.classList.add('is-loaded');
      label.textContent = hhT('translation_hide', 'Übersetzung ausblenden');
    } catch (error) {
      result.innerHTML = `<small>${hhCommentEscapeHtml(errorLabel)}</small>`;
      result.hidden = false;
      label.textContent = defaultLabel;
    } finally {
      button.disabled = false;
    }
  });
})();

/* HH Phase 74: Moments reel video playback fix */
(() => {
  const reels = document.querySelectorAll('[data-hh-moment-reel]');

  if (!reels.length) {
    return;
  }

  reels.forEach((reel) => {
    const video = reel.querySelector('[data-hh-moment-reel-video]');
    const playButton = reel.querySelector('[data-hh-moment-reel-play]');

    if (!video || !playButton) {
      return;
    }

    const syncButton = () => {
      playButton.classList.toggle('is-hidden', !video.paused && !video.ended);
    };

    playButton.addEventListener('click', async (event) => {
      event.preventDefault();
      event.stopPropagation();

      try {
        await video.play();
      } catch (error) {
        video.controls = true;
      }

      syncButton();
    });

    video.addEventListener('play', syncButton);
    video.addEventListener('playing', syncButton);
    video.addEventListener('pause', syncButton);
    video.addEventListener('ended', syncButton);
    video.addEventListener('loadedmetadata', syncButton);
    video.addEventListener('error', () => {
      playButton.classList.remove('is-hidden');
    });

    syncButton();
  });
})();

/* HH Phase 87: lightweight hnt.rocks feed video player */
(() => {
  const playerSelector = '[data-hh-feed-video-player]';
  const initialized = new WeakSet();
  const players = new Set();

  const formatTime = (seconds) => {
    const value = Number.isFinite(seconds) ? Math.max(0, Math.floor(seconds)) : 0;
    const minutes = Math.floor(value / 60);
    const rest = value % 60;
    return `${minutes}:${String(rest).padStart(2, '0')}`;
  };

  const getVideo = (player) => player?.querySelector?.('[data-hh-feed-video]') || null;

  const pauseOtherVideos = (currentVideo) => {
    document.querySelectorAll('[data-hh-feed-video]').forEach((video) => {
      if (video !== currentVideo && !video.paused) {
        video.pause();
      }
    });
  };

  const syncPlayer = (player) => {
    const video = getVideo(player);

    if (!player || !video) {
      return;
    }

    const isPlaying = !video.paused && !video.ended;
    const isMuted = video.muted || Number(video.volume) === 0;
    const duration = Number.isFinite(video.duration) && video.duration > 0 ? video.duration : 0;
    const progress = duration ? Math.min(100, Math.max(0, (video.currentTime / duration) * 100)) : 0;
    const playLabel = isPlaying ? hhT('feed_video_pause', 'Video pausieren') : hhT('feed_video_play', 'Video abspielen');
    const muteLabel = isMuted ? hhT('feed_video_unmute', 'Ton an') : hhT('feed_video_mute', 'Ton aus');

    player.classList.toggle('is-playing', isPlaying);
    player.classList.toggle('is-paused', !isPlaying);
    player.classList.toggle('is-muted', isMuted);
    player.classList.toggle('has-duration', duration > 0);

    player.querySelectorAll('[data-hh-feed-video-toggle]').forEach((button) => {
      button.setAttribute('aria-label', playLabel);
      button.setAttribute('title', playLabel);
    });

    const playIcon = player.querySelector('[data-hh-feed-video-play-icon]');
    if (playIcon) {
      playIcon.className = `hh-ph-action-icon ph ${isPlaying ? 'ph-pause' : 'ph-play'}`;
    }

    const muteButton = player.querySelector('[data-hh-feed-video-mute]');
    if (muteButton) {
      muteButton.setAttribute('aria-label', muteLabel);
      muteButton.setAttribute('title', muteLabel);
    }

    const muteIcon = player.querySelector('[data-hh-feed-video-mute-icon]');
    if (muteIcon) {
      muteIcon.className = `hh-ph-action-icon ph ${isMuted ? 'ph-speaker-slash' : 'ph-speaker-high'}`;
    }

    const fill = player.querySelector('[data-hh-feed-video-progress-fill]');
    if (fill) {
      fill.style.width = `${progress}%`;
    }

    const time = player.querySelector('[data-hh-feed-video-time]');
    if (time) {
      time.textContent = duration ? `${formatTime(video.currentTime)} / ${formatTime(duration)}` : formatTime(video.currentTime);
    }
  };

  const toggleVideo = async (player) => {
    const video = getVideo(player);

    if (!video) {
      return;
    }

    if (!video.paused && !video.ended) {
      video.pause();
      syncPlayer(player);
      return;
    }

    pauseOtherVideos(video);

    try {
      await video.play();
    } catch (error) {
      video.controls = true;
    }

    syncPlayer(player);
  };

  const seekVideo = (player, event) => {
    const video = getVideo(player);
    const button = event.currentTarget;

    if (!video || !button || !Number.isFinite(video.duration) || video.duration <= 0) {
      return;
    }

    const rect = button.getBoundingClientRect();
    const ratio = Math.min(1, Math.max(0, (event.clientX - rect.left) / rect.width));
    video.currentTime = ratio * video.duration;
    syncPlayer(player);
  };

  const toggleMute = (player) => {
    const video = getVideo(player);

    if (!video) {
      return;
    }

    video.muted = !video.muted;
    syncPlayer(player);
  };

  const requestFullscreen = (player) => {
    const video = getVideo(player);
    const target = player || video;

    try {
      if (document.fullscreenElement) {
        document.exitFullscreen?.();
        return;
      }

      if (target?.requestFullscreen) {
        target.requestFullscreen();
        return;
      }

      if (video?.webkitEnterFullscreen) {
        video.webkitEnterFullscreen();
      }
    } catch (error) {
      // Keep playback usable even if fullscreen is blocked by the browser.
    }
  };

  const initPlayer = (player) => {
    if (!player || initialized.has(player)) {
      return;
    }

    const video = getVideo(player);

    if (!video) {
      return;
    }

    initialized.add(player);
    players.add(player);
    video.controls = false;

    ['loadedmetadata', 'timeupdate', 'play', 'playing', 'pause', 'ended', 'volumechange', 'seeking', 'seeked'].forEach((eventName) => {
      video.addEventListener(eventName, () => syncPlayer(player));
    });

    video.addEventListener('play', () => pauseOtherVideos(video));
    video.addEventListener('click', (event) => {
      event.preventDefault();
      toggleVideo(player);
    });

    player.querySelectorAll('[data-hh-feed-video-toggle]').forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        toggleVideo(player);
      });
    });

    const muteButton = player.querySelector('[data-hh-feed-video-mute]');
    muteButton?.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      toggleMute(player);
    });

    const progress = player.querySelector('[data-hh-feed-video-progress]');
    progress?.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      seekVideo(player, event);
    });

    const fullscreenButton = player.querySelector('[data-hh-feed-video-fullscreen]');
    fullscreenButton?.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      requestFullscreen(player);
    });

    syncPlayer(player);
  };

  const initPlayers = (root = document) => {
    if (root.matches?.(playerSelector)) {
      initPlayer(root);
    }

    root.querySelectorAll?.(playerSelector)?.forEach(initPlayer);
  };

  initPlayers();

  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.intersectionRatio < 0.22) {
          const video = getVideo(entry.target);
          if (video && !video.paused) {
            video.pause();
          }
        }
      });
    }, { threshold: [0, 0.22, 0.5, 1] });

    const observePlayer = (player) => {
      if (player && !player.dataset.hhFeedVideoObserved) {
        player.dataset.hhFeedVideoObserved = '1';
        observer.observe(player);
      }
    };

    document.querySelectorAll(playerSelector).forEach(observePlayer);

    const mutationObserver = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
          if (!(node instanceof Element)) {
            return;
          }

          initPlayers(node);
          if (node.matches?.(playerSelector)) {
            observePlayer(node);
          }
          node.querySelectorAll?.(playerSelector)?.forEach(observePlayer);
        });
      });
    });

    mutationObserver.observe(document.documentElement, { childList: true, subtree: true });
  } else if ('MutationObserver' in window) {
    const mutationObserver = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
          if (node instanceof Element) {
            initPlayers(node);
          }
        });
      });
    });

    mutationObserver.observe(document.documentElement, { childList: true, subtree: true });
  }
})();

/* p90: Cup summary screenshot example modal */
(() => {
  const modalSelector = '[data-hh-cup-summary-example-modal]';
  const openSelector = '[data-hh-cup-summary-example-open]';
  const closeSelector = '[data-hh-cup-summary-example-close]';
  const openClass = 'hh-cup-summary-example-open';

  const closeModal = (modal) => {
    if (!modal) return;
    modal.hidden = true;
    document.documentElement.classList.remove(openClass);
  };

  const openModal = (modal) => {
    if (!modal) return;

    // Move the modal to <body> before opening so it is not trapped by
    // feed/cup layout stacking contexts or hidden below the fixed header.
    if (modal.parentElement !== document.body) {
      document.body.appendChild(modal);
    }

    modal.hidden = false;
    document.documentElement.classList.add(openClass);
    const closeButton = modal.querySelector(closeSelector);
    closeButton?.focus?.({ preventScroll: true });
  };

  document.addEventListener('click', (event) => {
    const openButton = event.target.closest?.(openSelector);
    if (openButton) {
      event.preventDefault();
      const scope = openButton.closest('form') || document;
      openModal(scope.querySelector(modalSelector) || document.querySelector(modalSelector));
      return;
    }

    const closeButton = event.target.closest?.(closeSelector);
    if (closeButton) {
      event.preventDefault();
      closeModal(closeButton.closest(modalSelector));
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    const openModalElement = document.querySelector(`${modalSelector}:not([hidden])`);
    if (openModalElement) {
      closeModal(openModalElement);
    }
  });
})();

/* Phase 347d: Feed composer panels collapsed until selected */
(function () {
  function findComposerForm(trigger) {
    const composer = trigger.closest?.('.hh-feed-composer');
    return composer?.querySelector?.('.hh-feed-form') || trigger.closest?.('.hh-feed-form') || null;
  }

  function setComposerPanel(form, panel) {
    if (!form) return;

    const composer = form.closest('.hh-feed-composer') || form;
    const current = composer.getAttribute('data-hh-feed-active-panel') || '';
    const next = current === panel ? '' : (panel || '');

    composer.setAttribute('data-hh-feed-active-panel', next);
    form.classList.toggle('is-panel-feeling', next === 'feeling');
    form.classList.toggle('is-panel-background', next === 'background');
    form.classList.toggle('is-panel-poll', next === 'poll');

    composer.querySelectorAll('[data-hh-feed-composer-panel]').forEach((panelNode) => {
      const isActive = panelNode.getAttribute('data-hh-feed-composer-panel') === next;
      panelNode.hidden = !isActive;
      panelNode.classList.toggle('is-active', isActive);
    });

    composer.querySelectorAll('[data-hh-feed-composer-panel-toggle]').forEach((toggle) => {
      toggle.classList.toggle('active', toggle.getAttribute('data-hh-feed-composer-panel-toggle') === next);
    });

    composer.querySelectorAll('[data-hh-feed-composer-status-tab]').forEach((tab) => {
      tab.classList.toggle('active', !next);
    });
  }

  document.addEventListener('click', (event) => {
    const statusTab = event.target.closest?.('[data-hh-feed-composer-status-tab]');
    if (statusTab) {
      event.preventDefault();
      setComposerPanel(findComposerForm(statusTab), '');
      return;
    }

    const toggle = event.target.closest?.('[data-hh-feed-composer-panel-toggle]');
    if (!toggle) return;

    event.preventDefault();
    setComposerPanel(findComposerForm(toggle), toggle.getAttribute('data-hh-feed-composer-panel-toggle') || '');
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter' && event.key !== ' ') return;

    const trigger = event.target.closest?.('[data-hh-feed-composer-status-tab], [data-hh-feed-composer-panel-toggle]');
    if (!trigger) return;

    event.preventDefault();
    trigger.click();
  });

  document.addEventListener('reset', (event) => {
    const form = event.target.closest?.('.hh-feed-form');
    if (!form) return;

    window.setTimeout(() => setComposerPanel(form, ''), 0);
  });

  document.querySelectorAll('.hh-feed-form').forEach((form) => setComposerPanel(form, ''));
})();

/* Patch 347h: Feed GIF picker */
(function () {
  function composerFrom(node) {
    return node?.closest?.('.hh-feed-composer') || node?.closest?.('.hh-feed-form') || null;
  }

  function field(composer, selector) {
    return composer?.querySelector?.(selector) || null;
  }

  function setSelectedGif(composer, item) {
    const provider = item?.provider || '';
    const gifUrl = item?.gif_url || '';
    const previewUrl = item?.preview_url || gifUrl;
    const selected = field(composer, '[data-hh-feed-gif-selected]');
    const image = field(composer, '[data-hh-feed-gif-selected-image]');

    const values = {
      '[data-hh-feed-gif-provider]': provider,
      '[data-hh-feed-gif-id]': item?.id || '',
      '[data-hh-feed-gif-url]': gifUrl,
      '[data-hh-feed-gif-preview-url]': previewUrl,
      '[data-hh-feed-gif-title]': item?.title || '',
      '[data-hh-feed-gif-source-url]': item?.source_url || ''
    };

    Object.entries(values).forEach(([selector, value]) => {
      const input = field(composer, selector);
      if (input) input.value = value || '';
    });

    if (selected && image) {
      selected.hidden = !gifUrl;
      image.src = previewUrl || '';
      image.alt = item?.title || 'GIF';
    }
  }

  function clearSelectedGif(composer) {
    setSelectedGif(composer, null);
  }

  function renderResults(composer, results, message) {
    const resultsNode = field(composer, '[data-hh-feed-gif-results]');
    if (!resultsNode) return;

    resultsNode.innerHTML = '';

    if (message) {
      const node = document.createElement('p');
      node.className = 'widget-box-text';
      node.textContent = message;
      resultsNode.appendChild(node);
      return;
    }

    (results || []).forEach((item) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'hh-feed-gif-result';
      button.innerHTML = `<img src="${String(item.preview_url || item.gif_url || '').replace(/"/g, '&quot;')}" alt=""><span>${String(item.title || 'GIF').replace(/[<>&]/g, '')}</span>`;
      button.addEventListener('click', () => setSelectedGif(composer, item));
      resultsNode.appendChild(button);
    });
  }

  async function loadGifs(composer, mode) {
    const wrap = field(composer, '[data-hh-feed-gif-composer]');
    if (!wrap || wrap.dataset.loading === '1') return;

    const query = field(composer, '[data-hh-feed-gif-query]')?.value?.trim() || '';
    const url = mode === 'search' && query
      ? `${wrap.dataset.searchUrl}?q=${encodeURIComponent(query)}&limit=24`
      : `${wrap.dataset.trendingUrl}?limit=24`;

    wrap.dataset.loading = '1';
    renderResults(composer, [], 'GIFs werden geladen …');

    try {
      const response = await fetch(url, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
      });
      const payload = await response.json();
      renderResults(composer, payload.results || [], payload.message || '');
    } catch (error) {
      renderResults(composer, [], 'GIFs konnten gerade nicht geladen werden.');
    } finally {
      wrap.dataset.loading = '0';
    }
  }

  document.addEventListener('click', (event) => {
    const gifToggle = event.target.closest?.('[data-hh-feed-composer-panel-toggle="gif"]');
    if (gifToggle) {
      const composer = composerFrom(gifToggle);
      window.setTimeout(() => loadGifs(composer, 'trending'), 80);
      return;
    }

    const search = event.target.closest?.('[data-hh-feed-gif-search]');
    if (search) {
      event.preventDefault();
      loadGifs(composerFrom(search), 'search');
      return;
    }

    const clear = event.target.closest?.('[data-hh-feed-gif-clear]');
    if (clear) {
      event.preventDefault();
      clearSelectedGif(composerFrom(clear));
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter') return;
    const query = event.target.closest?.('[data-hh-feed-gif-query]');
    if (!query) return;
    event.preventDefault();
    loadGifs(composerFrom(query), 'search');
  });
})();

/* HH Phase 463: true fullscreen Moments shell + comments drawer */
(() => {
  const page = document.querySelector('[data-hh-moment-fullscreen]');

  if (!page) {
    return;
  }

  document.body.classList.add('hh-moment-fullscreen-active');

  const video = page.querySelector('[data-hh-moment-reel-video]');
  const commentsToggle = page.querySelector('[data-hh-moment-comments-toggle]');
  const commentsClose = page.querySelector('[data-hh-moment-comments-close]');
  const commentsPanel = page.querySelector('[data-hh-moment-comments-panel]');

  const openComments = () => {
    document.body.classList.add('hh-moment-comments-open');
  };

  const closeComments = () => {
    document.body.classList.remove('hh-moment-comments-open');
  };

  if (commentsToggle && commentsPanel) {
    commentsToggle.addEventListener('click', (event) => {
      event.preventDefault();
      openComments();
    });
  }

  if (commentsClose) {
    commentsClose.addEventListener('click', (event) => {
      event.preventDefault();
      closeComments();
    });
  }

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeComments();
    }
  });

  document.addEventListener('visibilitychange', () => {
    if (document.hidden && video && !video.paused) {
      video.pause();
    }
  });
})();
