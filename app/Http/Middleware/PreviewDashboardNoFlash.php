<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreviewDashboardNoFlash
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('admin.theme-preview.shell')) {
            return $response;
        }

        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));
        if (! str_contains($contentType, 'text/html')) {
            return $response;
        }

        $content = $response->getContent();
        if (! is_string($content) || ! str_contains($content, '</head>')) {
            return $response;
        }

        $head = <<<'HTML'
<script>document.documentElement.classList.add('hnt-preview-hydrating');</script>
<style id="hnt-preview-no-flash-style">
  html.hnt-preview-hydrating .post-list > *,
  html.hnt-preview-hydrating .hnt-agenda-timeline > *,
  html.hnt-preview-hydrating #compositionPanel > *,
  html.hnt-preview-hydrating .personal-progress-table > *,
  html.hnt-preview-hydrating .personal-activity-values > *,
  html.hnt-preview-hydrating .personal-heatmap > *,
  html.hnt-preview-hydrating .personal-activity-summary > *,
  html.hnt-preview-hydrating .overview-progress .bar,
  html.hnt-preview-hydrating .overview-counts strong,
  html.hnt-preview-hydrating .personal-attention-strip button {
    visibility: hidden !important;
  }

  html.hnt-preview-hydrating .post-list,
  html.hnt-preview-hydrating .hnt-agenda-timeline,
  html.hnt-preview-hydrating #compositionPanel,
  html.hnt-preview-hydrating .personal-progress-table {
    position: relative;
  }

  html.hnt-preview-hydrating .post-list::before,
  html.hnt-preview-hydrating .hnt-agenda-timeline::before,
  html.hnt-preview-hydrating #compositionPanel::before,
  html.hnt-preview-hydrating .personal-progress-table::before {
    content: 'Echte Inhalte werden geladen …';
    visibility: visible !important;
    position: absolute;
    inset: 18px;
    z-index: 5;
    display: grid;
    place-items: center;
    min-height: 92px;
    border-radius: 24px;
    color: #9b978d;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: .01em;
    background:
      linear-gradient(100deg, rgba(255,255,255,.5) 20%, rgba(255,255,255,.92) 42%, rgba(255,255,255,.5) 64%)
      rgba(255,255,255,.52);
    background-size: 220% 100%;
    animation: hntPreviewHydration 1.35s linear infinite;
  }

  html.hnt-preview-hydrating #compositionPanel::before {
    min-height: 320px;
  }

  @keyframes hntPreviewHydration {
    to { background-position: -220% 0; }
  }

  @media (prefers-reduced-motion: reduce) {
    html.hnt-preview-hydrating .post-list::before,
    html.hnt-preview-hydrating .hnt-agenda-timeline::before,
    html.hnt-preview-hydrating #compositionPanel::before,
    html.hnt-preview-hydrating .personal-progress-table::before {
      animation: none;
    }
  }
</style>
<script>
(() => {
  const neutralHeader = (selector, title, copy) => {
    const host = document.querySelector(selector);
    if (!host) return;
    const hasRealContent = host.querySelector(
      '.header-live-state, [data-real-friend-request], .header-message-item, .header-notification-item'
    );
    if (hasRealContent) return;
    host.innerHTML = `<div class="header-live-state"><strong>${title}</strong>${copy}</div>`;
  };

  const neutralizeFallbacks = () => {
    neutralHeader('.header-request-list', 'Keine offenen Anfragen', 'Neue Anfragen erscheinen automatisch hier.');
    neutralHeader('.header-message-list', 'Noch keine Unterhaltungen', 'Deine privaten Nachrichten erscheinen hier.');
    neutralHeader('.header-notification-list', 'Keine Benachrichtigungen', 'Neue Hinweise erscheinen automatisch hier.');

    const agenda = document.querySelector('.hnt-agenda-panel');
    if (agenda && agenda.dataset.realAgenda !== '1') {
      const items = agenda.querySelector('.hnt-agenda-items');
      const axis = agenda.querySelector('.hnt-agenda-axis');
      if (items) {
        items.innerHTML = '<article class="hnt-agenda-card"><div class="hnt-agenda-copy"><span>HNT.ROCKS</span><h3>Agenda wird geladen</h3><p>Echte Inhalte werden vorbereitet.</p></div></article>';
      }
      if (axis) axis.innerHTML = '<span class="dark">JETZT</span>';
    }

    const community = document.getElementById('compositionPanel');
    if (community && community.dataset.realCommunity !== '1') {
      const title = community.querySelector('.composition-top h2');
      const activity = community.querySelector('#compositionActivity');
      const activityState = community.querySelector('#activityState');
      const trending = community.querySelector('.composition-trending');
      if (title) title.textContent = 'Community';
      if (activity) activity.innerHTML = '<article class="activity-item" data-community-real="1"><img src="/assets/vikinger/img/default-avatar.svg" alt=""><div><strong>Community wird geladen</strong><small>Echte Ereignisse werden vorbereitet.</small></div><span>…</span></article>';
      if (activityState) activityState.textContent = 'Wird geladen';
      if (trending) trending.innerHTML = '<div class="activity-title"><h3>Hashtags</h3><span>Letzte 30 Tage</span></div><div class="trend-tags"><button type="button" disabled>Wird geladen …</button></div>';
    }

    const progress = document.querySelector('.personal-progress-table');
    if (progress && !progress.querySelector('[data-real-dashboard-row]') && progress.dataset.realLoading !== '1') {
      progress.innerHTML = '<div class="personal-progress-labels"><span>Aktivität</span><span>Fortschritt</span><span>Belohnung</span><span>Status</span></div><article class="personal-progress-row"><div class="personal-progress-copy"><strong>Fortschritt wird geladen</strong><small>Echte Auftragsdaten werden vorbereitet.</small></div></article>';
    }

    document.querySelectorAll('.personal-activity-values strong').forEach((node) => { node.textContent = '—'; });
    document.querySelectorAll('.personal-heatmap .y').forEach((node) => node.classList.remove('y'));
  };

  const removeStaticFeedDemo = () => {
    const list = document.querySelector('.post-list');
    if (!list) return;

    list.querySelectorAll(':scope > .social-post:not([data-real-feed-post])').forEach((post) => post.remove());

    const hasRealPost = Boolean(list.querySelector('[data-real-feed-post]'));
    const hasLoader = Boolean(list.querySelector('.real-feed-loader'));
    const hasEmpty = Boolean(list.querySelector('[data-real-feed-empty]'));

    if (!hasRealPost && hasLoader && !hasEmpty && !list.classList.contains('is-loading-real-feed')) {
      const empty = document.createElement('article');
      empty.className = 'social-post';
      empty.dataset.realFeedEmpty = '1';
      empty.innerHTML = '<div class="post-body"><p>Noch keine Beiträge vorhanden.</p></div>';
      list.insertBefore(empty, list.firstChild);
    }
  };

  const moduleReady = () => {
    const agendaReady = Boolean(document.querySelector('.hnt-agenda-panel[data-real-agenda="1"]'));
    const communityReady = Boolean(document.querySelector('#compositionPanel[data-real-community="1"]'));
    const feedList = document.querySelector('.post-list');
    const feedReady = Boolean(feedList?.querySelector('.real-feed-loader'))
      && !feedList.classList.contains('is-loading-real-feed');
    const progressReady = Boolean(document.querySelector('.personal-progress-table[data-real-loading="1"], .personal-progress-table [data-real-dashboard-row]'));
    const headerReady = ['.header-request-list', '.header-message-list', '.header-notification-list'].every((selector) => {
      const host = document.querySelector(selector);
      return Boolean(host?.querySelector('.header-live-state, [data-real-friend-request], .header-message-item, .header-notification-item'));
    });

    return agendaReady && communityReady && feedReady && progressReady && headerReady;
  };

  const release = () => {
    neutralizeFallbacks();
    removeStaticFeedDemo();
    document.documentElement.classList.remove('hnt-preview-hydrating');
  };

  const start = () => {
    const startedAt = performance.now();
    const wait = () => {
      if (moduleReady() || performance.now() - startedAt > 5000) {
        requestAnimationFrame(() => requestAnimationFrame(release));
        return;
      }
      requestAnimationFrame(wait);
    };
    wait();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start, { once: true });
  } else {
    start();
  }
})();
</script>
HTML;

        $response->setContent(str_replace('</head>', $head.'</head>', $content));

        return $response;
    }
}
