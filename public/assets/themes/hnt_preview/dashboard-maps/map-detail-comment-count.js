(() => {
  'use strict';

  const isEnglish = (document.documentElement.lang || '').toLowerCase().startsWith('en');
  const buttonLabel = isEnglish ? 'View comments' : 'Kommentare ansehen';
  const numberFormatter = new Intl.NumberFormat(document.documentElement.lang || 'de-DE');
  const observedCounters = new WeakSet();

  const bindCommentCount = () => {
    const detailModal = document.querySelector('.map-cash-detail-demo');
    const commentsButton = detailModal?.querySelector('.map-cash-detail-demo-actions button:first-child');
    const sourceCounter = document.querySelector('.hnt-map-cash-comments-count');

    if (!(commentsButton instanceof HTMLButtonElement) || !(sourceCounter instanceof HTMLElement)) {
      return false;
    }

    const sync = () => {
      const parsed = Number.parseInt(sourceCounter.textContent || '0', 10);
      const count = Number.isFinite(parsed) ? Math.max(0, parsed) : 0;
      const formatted = numberFormatter.format(count);

      commentsButton.textContent = `${buttonLabel} (${formatted})`;
      commentsButton.dataset.commentCount = String(count);
      commentsButton.setAttribute('aria-label', `${buttonLabel}: ${formatted}`);
    };

    sync();

    if (!observedCounters.has(sourceCounter)) {
      observedCounters.add(sourceCounter);
      new MutationObserver(sync).observe(sourceCounter, {
        childList: true,
        characterData: true,
        subtree: true,
      });
    }

    return true;
  };

  const scheduleBind = () => {
    bindCommentCount();
    window.requestAnimationFrame(bindCommentCount);
  };

  new MutationObserver(scheduleBind).observe(document.body, {
    childList: true,
    subtree: true,
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', scheduleBind, { once: true });
  } else {
    scheduleBind();
  }
})();
