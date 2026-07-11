/* Interaction polish for the isolated dashboard feed preview.
   Keeps the 1:1 template intact while replacing the temporary floating menu
   and native browser confirmation with HNT-styled UI. */
(() => {
  const feedScroll = document.getElementById('feedScroll');
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

  let lastMenuTrigger = null;
  let activeMenu = null;
  let pendingDelete = null;

  const toast = (message) => {
    if (typeof showToast === 'function') {
      showToast(message);
      return;
    }

    console.info(message);
  };

  const requestJson = async (url, method = 'DELETE') => {
    const response = await fetch(url, {
      method,
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
      },
      body: JSON.stringify({}),
    });

    let payload = null;
    try {
      payload = await response.json();
    } catch (_) {
      payload = null;
    }

    if (!response.ok) {
      throw new Error(payload?.message || `Request failed with ${response.status}`);
    }

    return payload || {};
  };

  const copyText = async (value) => {
    if (!navigator.clipboard) {
      toast('Link konnte nicht kopiert werden');
      return;
    }

    await navigator.clipboard.writeText(value);
    toast('Link kopiert');
  };

  const closeMenu = () => {
    activeMenu?.remove();
    activeMenu = null;
  };

  const positionMenu = (menu, trigger, article) => {
    const triggerRect = trigger.getBoundingClientRect();
    const articleRect = article.getBoundingClientRect();
    const menuWidth = 190;
    const left = Math.max(14, Math.min(articleRect.width - menuWidth - 14, triggerRect.right - articleRect.left - menuWidth));

    menu.style.top = `${Math.max(56, triggerRect.bottom - articleRect.top + 8)}px`;
    menu.style.left = `${left}px`;
  };

  const dialog = document.createElement('div');
  dialog.className = 'real-feed-confirm';
  dialog.hidden = true;
  dialog.setAttribute('aria-hidden', 'true');
  dialog.innerHTML = `
    <div class="real-feed-confirm__backdrop" data-confirm-cancel></div>
    <section class="real-feed-confirm__dialog" role="dialog" aria-modal="true" aria-labelledby="realFeedConfirmTitle" aria-describedby="realFeedConfirmText">
      <button class="real-feed-confirm__close" type="button" aria-label="Schließen" data-confirm-cancel>×</button>
      <span class="real-feed-confirm__kicker">BEITRAG LÖSCHEN</span>
      <h2 id="realFeedConfirmTitle">Diesen Beitrag wirklich löschen?</h2>
      <p id="realFeedConfirmText">Der Beitrag, seine Medien und alle zugehörigen Kommentare werden dauerhaft entfernt. Diese Aktion kann nicht rückgängig gemacht werden.</p>
      <div class="real-feed-confirm__actions">
        <button class="real-feed-confirm__cancel" type="button" data-confirm-cancel>Abbrechen</button>
        <button class="real-feed-confirm__delete" type="button" data-confirm-delete>Beitrag löschen</button>
      </div>
    </section>
  `;
  document.body.appendChild(dialog);

  const closeDeleteDialog = () => {
    dialog.hidden = true;
    dialog.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('real-feed-confirm-open');
    pendingDelete = null;
  };

  const openDeleteDialog = (article, postId) => {
    closeMenu();
    pendingDelete = { article, postId };
    dialog.hidden = false;
    dialog.setAttribute('aria-hidden', 'false');
    document.body.classList.add('real-feed-confirm-open');
    window.setTimeout(() => dialog.querySelector('[data-confirm-delete]')?.focus(), 40);
  };

  dialog.querySelectorAll('[data-confirm-cancel]').forEach((button) => {
    button.addEventListener('click', closeDeleteDialog);
  });

  dialog.querySelector('[data-confirm-delete]')?.addEventListener('click', async (event) => {
    if (!pendingDelete) return;

    const button = event.currentTarget;
    const { article, postId } = pendingDelete;
    button.disabled = true;
    button.textContent = 'Wird gelöscht …';

    try {
      await requestJson(`/feed/${encodeURIComponent(String(postId))}`);
      article.remove();
      closeDeleteDialog();
      toast('Beitrag gelöscht');
    } catch (error) {
      toast(error.message || 'Beitrag konnte nicht gelöscht werden');
    } finally {
      button.disabled = false;
      button.textContent = 'Beitrag löschen';
    }
  });

  const buildAnchoredMenu = (sourceMenu, trigger) => {
    const article = trigger?.closest('[data-real-feed-post]');
    const postId = Number.parseInt(article?.dataset.realFeedPost || '0', 10);
    if (!article || !postId) return;

    const permalink = article.dataset.realPermalink || `/feed/posts/${postId}`;
    const canDelete = Boolean(sourceMenu.querySelector('[data-menu-delete]'));

    sourceMenu.remove();
    closeMenu();

    const menu = document.createElement('div');
    menu.className = 'real-feed-menu real-feed-menu--anchored';
    menu.setAttribute('role', 'menu');
    menu.innerHTML = `
      <button type="button" role="menuitem" data-polish-open>Beitrag öffnen</button>
      <button type="button" role="menuitem" data-polish-copy>Link kopieren</button>
      ${canDelete ? '<button type="button" role="menuitem" class="danger" data-polish-delete>Beitrag löschen</button>' : ''}
    `;

    article.appendChild(menu);
    positionMenu(menu, trigger, article);
    activeMenu = menu;

    menu.querySelector('[data-polish-open]')?.addEventListener('click', () => {
      window.location.href = permalink;
    });

    menu.querySelector('[data-polish-copy]')?.addEventListener('click', async () => {
      await copyText(permalink);
      closeMenu();
    });

    menu.querySelector('[data-polish-delete]')?.addEventListener('click', () => {
      openDeleteDialog(article, postId);
    });

    window.requestAnimationFrame(() => menu.classList.add('is-open'));
  };

  window.addEventListener('pointerdown', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;

    const trigger = target.closest('[data-real-post-more]');
    if (trigger) lastMenuTrigger = trigger;
  }, true);

  const menuObserver = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (!(node instanceof Element)) return;

        const menu = node.matches('.real-feed-menu')
          ? node
          : node.querySelector('.real-feed-menu');

        if (!menu || menu.classList.contains('real-feed-menu--anchored')) return;
        buildAnchoredMenu(menu, lastMenuTrigger);
      });
    });
  });

  menuObserver.observe(document.body, { childList: true });

  document.addEventListener('pointerdown', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;

    if (activeMenu && !activeMenu.contains(target) && !target.closest('[data-real-post-more]')) {
      closeMenu();
    }
  });

  feedScroll?.addEventListener('scroll', closeMenu, { passive: true });
  window.addEventListener('resize', closeMenu);

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;

    if (!dialog.hidden) {
      closeDeleteDialog();
      return;
    }

    closeMenu();
  });
})();

/* Load the live composer as an isolated optional enhancement. */
(() => {
  const base = '/assets/themes/hnt_preview/dashboard-feed/';
  if (!document.querySelector('link[data-real-feed-compose]')) {
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = `${base}real-feed-compose.css?v=20260711-2`;
    link.dataset.realFeedCompose = '1';
    document.head.appendChild(link);
  }
  if (!document.querySelector('script[data-real-feed-compose]')) {
    const script = document.createElement('script');
    script.src = `${base}real-feed-compose.js?v=20260711-2`;
    script.dataset.realFeedCompose = '1';
    script.defer = true;
    document.body.appendChild(script);
  }
})();

/* Load the fullscreen media + comments viewer without touching the base demo. */
(() => {
  const base = '/assets/themes/hnt_preview/dashboard-feed/';

  if (!document.querySelector('link[data-real-media-viewer]')) {
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = `${base}real-feed-media-viewer.css?v=20260711-1`;
    link.dataset.realMediaViewer = '1';
    document.head.appendChild(link);
  }

  if (!document.querySelector('script[data-real-media-viewer]')) {
    const script = document.createElement('script');
    script.src = `${base}real-feed-media-viewer.js?v=20260711-1`;
    script.dataset.realMediaViewer = '1';
    script.defer = true;
    document.body.appendChild(script);
  }
})();

/* Load existing comment media and the real comment image uploader. */
(() => {
  const base = '/assets/themes/hnt_preview/dashboard-feed/';

  if (!document.querySelector('script[data-real-comment-media]')) {
    const script = document.createElement('script');
    script.src = `${base}real-feed-comment-media.js?v=20260711-3`;
    script.dataset.realCommentMedia = '1';
    script.defer = true;
    document.body.appendChild(script);
  }
})();