// /next is an isolated, read-only migration stage.
// Server-render the first page; progressively append subsequent pages without
// showing temporary/demo posts or replacing the Laravel authorization rules.
const feedList = document.getElementById('nextFeedList');
let loadMore = document.getElementById('nextLoadMore');
let fetching = false;
const existingIds = new Set(
  Array.from(feedList?.querySelectorAll('[data-next-feed-post]') ?? [], (p) => p.dataset.nextFeedPost)
);

async function appendPage() {
  if (!loadMore || fetching) return;
  fetching = true;
  const link = loadMore;
  link.setAttribute('aria-busy', 'true');
  const originalText = link.textContent;
  link.textContent = 'Weitere Beiträge laden …';

  try {
    const response = await fetch(link.href, {
      credentials: 'same-origin',
      headers: { Accept: 'text/html' },
      redirect: 'follow',
    });
    if (!response.ok || !response.url.startsWith(window.location.origin + '/next/')) {
      throw new Error('Nicht verfügbar');
    }
    const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
    const nodes = doc.querySelectorAll('[data-next-feed-post]');

    let appended = 0;
    for (const node of nodes) {
      const id = node.dataset.nextFeedPost;
      if (!id || existingIds.has(id)) continue;
      existingIds.add(id);
      feedList.appendChild(document.importNode(node, true));
      appended++;
    }

    const next = doc.getElementById('nextLoadMore');
    if (next && appended > 0) {
      link.href = next.href;
      link.textContent = originalText;
    } else {
      link.remove();
      loadMore = null;
      observer?.disconnect();
    }
  } catch {
    link.textContent = 'Laden fehlgeschlagen – erneut versuchen';
  } finally {
    link.removeAttribute('aria-busy');
    fetching = false;
  }
}

let observer;
if (feedList && loadMore && 'IntersectionObserver' in window) {
  observer = new IntersectionObserver(
    (items) => {
      if (items.some(({ isIntersecting }) => isIntersecting)) void appendPage();
    },
    { rootMargin: '0px 0px 320px 0px' }
  );
  observer.observe(loadMore);
}
loadMore?.addEventListener('click', (event) => {
  if (!('fetch' in window)) return;
  event.preventDefault();
  void appendPage();
});
