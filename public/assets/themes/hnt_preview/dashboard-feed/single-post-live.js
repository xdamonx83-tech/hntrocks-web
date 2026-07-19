/* HNT.ROCKS — Verhalten der Einzelbeitragsseite. */

/* Auf der Einzelansicht fehlt die Fortschrittskarte. Das Community-Panel
   folgt deshalb direkt dem Community-Feed und gleitet beim Scrollen wie im Feed nach oben. */
(() => {
  if (!window.HNT_SINGLE_POST_PAGE) return;

  const feedScroll = document.getElementById('feedScroll');
  const feedShell = document.querySelector('.feed-shell');
  const socialFeedCard = document.querySelector('.social-feed-card');
  const compositionPanel = document.getElementById('compositionPanel');

  if (!feedScroll || !feedShell || !socialFeedCard || !compositionPanel) return;

  const updateCompositionPanel = () => {
    const styles = getComputedStyle(feedShell);
    const stickyGap = Number.parseFloat(styles.getPropertyValue('--sticky-gap')) || 12;
    const startTop = socialFeedCard.offsetTop;
    const expansionDistance = Math.max(260, Math.min(520, feedScroll.clientHeight * 0.68));
    const progress = Math.min(feedScroll.scrollTop / expansionDistance, 1);
    const top = Math.max(stickyGap, startTop * (1 - progress));

    compositionPanel.style.top = `${top}px`;
    compositionPanel.classList.toggle('is-expanded', progress > 0.12);
  };

  feedScroll.addEventListener('scroll', updateCompositionPanel, { passive: true });
  window.addEventListener('resize', updateCompositionPanel);
  window.requestAnimationFrame(updateCompositionPanel);
})();

/* Kommentarlinks öffnen weiterhin exakt dasselbe Feed-Modal. */
(() => {
  if (!window.HNT_SINGLE_POST_PAGE) return;

  const postId = Number(window.HNT_SINGLE_POST_ID || 0);
  const params = new URLSearchParams(window.location.search);
  const shouldOpenComments = window.location.hash.startsWith('#comment-')
    || params.get('comments') === '1'
    || params.get('open_comments') === '1';

  if (!shouldOpenComments || !postId) return;

  let opened = false;
  const openComments = () => {
    if (opened) return true;

    const trigger = document.querySelector(
      `[data-real-feed-post="${postId}"] [data-real-preview-comments]`
    );

    if (!(trigger instanceof HTMLElement)) return false;

    opened = true;
    trigger.click();
    return true;
  };

  if (openComments()) return;

  const list = document.querySelector('.post-list');
  if (!list) return;

  const observer = new MutationObserver(() => {
    if (!openComments()) return;
    observer.disconnect();
  });

  observer.observe(list, { childList: true, subtree: true });
  window.setTimeout(() => observer.disconnect(), 10000);
})();
