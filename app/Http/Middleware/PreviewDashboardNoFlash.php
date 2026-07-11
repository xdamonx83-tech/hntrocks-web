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
  html.hnt-preview-hydrating .profile-panel > *,
  html.hnt-preview-hydrating .hnt-agenda-panel > *,
  html.hnt-preview-hydrating #compositionPanel > *,
  html.hnt-preview-hydrating .feed-overview > *,
  html.hnt-preview-hydrating .salary-attendance-card > *,
  html.hnt-preview-hydrating .post-list > *,
  html.hnt-preview-hydrating .header-actions > * {
    filter: blur(7px) !important;
    opacity: .16 !important;
    pointer-events: none !important;
    user-select: none !important;
    transition: filter .22s ease, opacity .22s ease;
  }

  html.hnt-preview-hydrating .profile-panel,
  html.hnt-preview-hydrating .hnt-agenda-panel,
  html.hnt-preview-hydrating #compositionPanel,
  html.hnt-preview-hydrating .feed-overview,
  html.hnt-preview-hydrating .salary-attendance-card,
  html.hnt-preview-hydrating .post-list,
  html.hnt-preview-hydrating .header-actions {
    position: relative;
  }

  html.hnt-preview-hydrating .profile-panel::before,
  html.hnt-preview-hydrating .hnt-agenda-panel::before,
  html.hnt-preview-hydrating #compositionPanel::before,
  html.hnt-preview-hydrating .feed-overview::before,
  html.hnt-preview-hydrating .salary-attendance-card::before,
  html.hnt-preview-hydrating .post-list::before,
  html.hnt-preview-hydrating .header-actions::before {
    content: 'Echte Inhalte werden geladen …';
    position: absolute;
    inset: 10px;
    z-index: 80;
    display: grid;
    place-items: center;
    border-radius: 22px;
    color: #8f8b80;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .02em;
    pointer-events: none;
    background:
      linear-gradient(100deg, rgba(255,255,255,.58) 18%, rgba(255,255,255,.96) 42%, rgba(255,255,255,.58) 66%)
      rgba(249,247,238,.88);
    background-size: 220% 100%;
    box-shadow: inset 0 0 0 1px rgba(47,47,44,.035);
    animation: hntPreviewHydration 1.25s linear infinite;
  }

  html.hnt-preview-hydrating .header-actions::before {
    inset: 0;
    border-radius: 999px;
    font-size: 10px;
  }

  html.hnt-preview-hydrating .post-list::before {
    min-height: 180px;
  }

  html.hnt-preview-hydrating #compositionPanel::before,
  html.hnt-preview-hydrating .salary-attendance-card::before {
    min-height: 280px;
  }

  @keyframes hntPreviewHydration {
    to { background-position: -220% 0; }
  }

  @media (prefers-reduced-motion: reduce) {
    html.hnt-preview-hydrating .profile-panel::before,
    html.hnt-preview-hydrating .hnt-agenda-panel::before,
    html.hnt-preview-hydrating #compositionPanel::before,
    html.hnt-preview-hydrating .feed-overview::before,
    html.hnt-preview-hydrating .salary-attendance-card::before,
    html.hnt-preview-hydrating .post-list::before,
    html.hnt-preview-hydrating .header-actions::before {
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
    const agendaItems = agenda?.querySelector('.hnt-agenda-items');
    if (agendaItems?.textContent?.includes('Agenda wird geladen')) {
      agendaItems.innerHTML = '<article class="hnt-agenda-card"><div class="hnt-agenda-copy"><span>HNT.ROCKS</span><h3>Agenda nicht verfügbar</h3><p>Die Daten konnten gerade nicht geladen werden.</p></div></article>';
    }

    const community = document.getElementById('compositionPanel');
    const note = community?.querySelector('.community-note');
    if (note?.textContent?.includes('Community wird geladen')) {
      note.innerHTML = '<span>HNT.ROCKS</span><strong>Community-Daten nicht verfügbar</strong><small>Die Werte konnten gerade nicht geladen werden.</small>';
    }

    const progress = document.querySelector('.personal-progress-table');
    if (progress && !progress.querySelector('[data-real-dashboard-row]')) {
      progress.innerHTML = '<article class="personal-progress-row" data-real-dashboard-row><div class="personal-progress-copy"><strong>Fortschritt nicht verfügbar</strong><small>Die echten Werte konnten gerade nicht geladen werden.</small></div></article>';
    }

    document.querySelectorAll('.personal-activity-values strong').forEach((node) => {
      if (!node.textContent?.trim() || /^(12|47)$/.test(node.textContent.trim())) node.textContent = '—';
    });
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
    const agenda = document.querySelector('.hnt-agenda-panel[data-real-agenda="1"]');
    const agendaText = agenda?.querySelector('.hnt-agenda-items')?.textContent || '';
    const agendaReady = Boolean(agenda) && !agendaText.includes('Agenda wird geladen');

    const community = document.querySelector('#compositionPanel[data-real-community="1"]');
    const communityLive = community?.querySelector('.composition-live')?.textContent || '';
    const communityNote = community?.querySelector('.community-note')?.textContent || '';
    const communityReady = Boolean(community)
      && !communityLive.includes('Live-Daten')
      && !communityNote.includes('Community wird geladen');

    const progress = document.querySelector('.personal-progress-table');
    const progressReady = Boolean(progress?.querySelector('[data-real-dashboard-row]'))
      && progress?.dataset.realLoading !== '1';

    const activityReady = Boolean(document.querySelector('.personal-heatmap[data-real-activity="1"]'));

    const feedList = document.querySelector('.post-list');
    const feedReady = Boolean(feedList?.querySelector('.real-feed-loader'))
      && !feedList.classList.contains('is-loading-real-feed');

    const headerReady = ['.header-request-list', '.header-message-list', '.header-notification-list'].every((selector) => {
      const host = document.querySelector(selector);
      return Boolean(host?.querySelector('.header-live-state, [data-real-friend-request], .header-message-item, .header-notification-item'));
    });

    return agendaReady && communityReady && progressReady && activityReady && feedReady && headerReady;
  };

  const recalculateResponsivePositions = () => {
    const feedScroll = document.getElementById('feedScroll');
    window.dispatchEvent(new Event('resize'));
    feedScroll?.dispatchEvent(new Event('scroll'));
  };

  const release = () => {
    neutralizeFallbacks();
    removeStaticFeedDemo();
    document.documentElement.classList.remove('hnt-preview-hydrating');

    requestAnimationFrame(() => {
      requestAnimationFrame(recalculateResponsivePositions);
    });
  };

  const start = () => {
    const startedAt = performance.now();
    const minimumMs = 520;
    const maximumMs = 8000;

    const wait = () => {
      const elapsed = performance.now() - startedAt;
      const ready = elapsed >= minimumMs && moduleReady();

      if (ready || elapsed > maximumMs) {
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
