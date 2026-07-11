/* Keep the compact post-type badge consistent across feed and profile. */
(() => {
  const feedList = document.querySelector('.post-list');
  if (!feedList) return;

  const classify = (article) => {
    if (!(article instanceof Element) || !article.matches('[data-real-feed-post]')) return;

    const badge = article.querySelector('.post-badge');
    if (!badge) return;

    const hasPoll = Boolean(article.querySelector('.real-feed-poll, .profile-real-poll'));
    const hasVideo = Boolean(article.querySelector('.real-post-video-item, video'));
    const hasMedia = Boolean(article.querySelector('.real-post-media-grid, .profile-real-media-grid'));

    let label = 'Beitrag';
    let variant = 'discussion';

    if (hasPoll) {
      label = 'Umfrage';
      variant = 'cup';
    } else if (hasVideo) {
      label = 'Video';
      variant = 'moment';
    } else if (hasMedia) {
      label = 'Bild';
      variant = '';
    }

    badge.textContent = label;
    badge.classList.remove('moment', 'cup', 'rocks', 'discussion');
    if (variant) badge.classList.add(variant);
  };

  const classifyWithin = (root) => {
    if (!(root instanceof Element)) return;
    if (root.matches('[data-real-feed-post]')) classify(root);
    root.querySelectorAll('[data-real-feed-post]').forEach(classify);
  };

  classifyWithin(feedList);

  const observer = new MutationObserver((records) => {
    records.forEach((record) => {
      record.addedNodes.forEach((node) => {
        if (node instanceof Element) classifyWithin(node);
      });
    });
  });

  observer.observe(feedList, { childList: true, subtree: true });
})();
