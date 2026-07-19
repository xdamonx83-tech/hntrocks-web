/* HNT.ROCKS — Verhalten der Einzelbeitragsseite. */
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
