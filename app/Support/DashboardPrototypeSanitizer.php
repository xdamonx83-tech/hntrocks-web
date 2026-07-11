<?php

namespace App\Support;

use App\Models\User;

class DashboardPrototypeSanitizer
{
    public function sanitize(
        string $html,
        User $viewer,
        array $header,
        array $progress,
        array $streak
    ): string {
        $profile = is_array($header['profile'] ?? null) ? $header['profile'] : [];
        $counts = is_array($header['counts'] ?? null) ? $header['counts'] : [];

        $html = $this->stripPrototypeTokens($html, $viewer);
        $html = $this->replaceHeaderLists($html, $header);
        $html = $this->replaceHeaderBadges($html, $counts);
        $html = $this->replaceHeaderProfile($html, $profile);
        $html = $this->replaceProfilePanel($html, $profile, $progress);
        $html = $this->replaceAgenda($html);
        $html = $this->replaceCommunity($html);
        $html = $this->replaceOverview($html, $viewer, $profile, $counts, $progress, $streak);
        $html = $this->replacePersonalDashboard($html, $profile, $counts, $progress, $streak);
        $html = $this->replaceStaticPosts($html);
        $html = $this->replaceComposerIdentity($html, $viewer);
        $html = $this->replaceCommentPlaceholders($html, $viewer);

        return $html;
    }

    private function stripPrototypeTokens(string $html, User $viewer): string
    {
        $name = $this->escape($viewer->name ?: ($viewer->username ?: 'HNT Hunter'));
        $handle = $this->escape($viewer->username ? '@'.$viewer->username : '@hunter');
        $avatar = $this->escape($viewer->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg'));

        $html = str_replace(['Valentina', '@valentina'], [$name, $handle], $html);

        $html = str_replace(
            [
                'Katy Fuller', 'Jonathan Kelly', 'Erica Wyatt', 'Sarah Page',
                'Noah Brandt', 'Mara Voss', 'Lena Hart',
                '@katy', '@jonathan', '@erica', '@sarah', '@noah', '@mara', '@lenahart',
            ],
            [
                'HNT Hunter', 'HNT Hunter', 'HNT Hunter', 'HNT Hunter',
                'HNT Hunter', 'HNT Hunter', 'HNT Hunter',
                '@hunter', '@hunter', '@hunter', '@hunter', '@hunter', '@hunter', '@hunter',
            ],
            $html
        );

        $pattern = '~(?:https?:)?//[^"\']*/assets/themes/hnt_preview/dashboard-feed/assets/[^"\']+\.(?:jpg|jpeg|png|webp)|/assets/themes/hnt_preview/dashboard-feed/assets/[^"\']+\.(?:jpg|jpeg|png|webp)~i';
        $result = preg_replace($pattern, $avatar, $html);

        return is_string($result) ? $result : $html;
    }

    private function replaceHeaderLists(string $html, array $header): string
    {
        $friends = $this->friendRequestsMarkup($header['friend_requests'] ?? []);
        $messages = $this->messagesMarkup($header['messages'] ?? []);
        $notifications = $this->notificationsMarkup($header['notifications'] ?? []);

        $html = $this->replaceOne(
            '~<div class="header-request-list">.*?(?=<button class="header-dropdown-footer")~s',
            "<div class=\"header-request-list\">{$friends}</div>\n",
            $html
        );
        $html = $this->replaceOne(
            '~<div class="header-message-list">.*?(?=<button class="header-dropdown-footer")~s',
            "<div class=\"header-message-list\">{$messages}</div>\n",
            $html
        );
        $html = $this->replaceOne(
            '~<div class="header-notification-list">.*?(?=<button class="header-dropdown-footer")~s',
            "<div class=\"header-notification-list\">{$notifications}</div>\n",
            $html
        );

        return $html;
    }

    private function replaceHeaderBadges(string $html, array $counts): string
    {
        foreach (['friends', 'messages', 'notifications'] as $type) {
            $count = max(0, (int) ($counts[$type] ?? 0));
            $html = $this->replaceOne(
                '~(<span class="header-action-badge" data-header-badge="'.preg_quote($type, '~').'">).*?(</span>)~s',
                '$1'.$count.'$2',
                $html,
                false
            );

            $suffix = $type === 'friends' ? 'offen' : 'ungelesen';
            $html = $this->replaceOne(
                '~(<small data-dropdown-count="'.preg_quote($type, '~').'">).*?(</small>)~s',
                '$1'.$count.' '.$suffix.'$2',
                $html,
                false
            );
        }

        return $html;
    }

    private function replaceHeaderProfile(string $html, array $profile): string
    {
        $name = $this->escape($profile['name'] ?? 'HNT Hunter');
        $handle = $this->escape($profile['handle'] ?? '@hunter');
        $avatar = $this->escape($profile['avatar'] ?? asset('assets/vikinger/img/default-avatar.svg'));
        $level = max(1, (int) ($profile['level'] ?? 1));
        $rocks = max(0, (int) ($profile['rocks'] ?? 0));
        $friends = max(0, (int) ($profile['friends'] ?? 0));
        $posts = max(0, (int) ($profile['posts'] ?? 0));

        $markup = <<<HTML
<div class="header-profile-summary">
<div class="header-profile-avatar"><img alt="{$name}" src="{$avatar}"/><i></i></div>
<div><strong>{$name}</strong><span>{$handle} · Level {$level}</span></div>
</div>
<div class="header-profile-stats">
<span><strong>{$rocks}</strong><small>Rocks</small></span>
<span><strong>{$friends}</strong><small>Freunde</small></span>
<span><strong>{$posts}</strong><small>Posts</small></span>
</div>
HTML;

        return $this->replaceOne(
            '~<div class="header-profile-summary">.*?(?=<div class="header-menu-list profile-menu-list">)~s',
            $markup,
            $html
        );
    }

    private function replaceProfilePanel(string $html, array $profile, array $progress): string
    {
        $name = $this->escape($profile['name'] ?? 'HNT Hunter');
        $handle = $this->escape($profile['handle'] ?? '@hunter');
        $avatar = $this->escape($profile['avatar'] ?? asset('assets/vikinger/img/default-avatar.svg'));
        $level = max(1, (int) ($profile['level'] ?? 1));
        $nextLevel = $level + 1;
        $levelProgress = max(0, min(100, (int) ($progress['level_progress'] ?? 0)));
        $rocks = max(0, (int) ($profile['rocks'] ?? 0));
        $friends = max(0, (int) ($profile['friends'] ?? 0));
        $posts = max(0, (int) ($profile['posts'] ?? 0));

        $markup = <<<HTML
<aside aria-label="Mein Bereich" class="profile-panel fixed-profile" id="profilePanel">
<div class="profile-cover"><span>MEIN BEREICH</span><button aria-label="Einstellungen" class="profile-settings"><svg><use href="#i-settings"></use></svg></button></div>
<div class="profile-card-head">
<div class="profile-avatar-wrap"><img alt="{$name}" class="profile-avatar" src="{$avatar}"/><i aria-label="Online"></i></div>
<div class="profile-meta"><strong>{$name}</strong><span>{$handle} · Online</span></div>
<button class="edit-profile-button">Bearbeiten</button>
</div>
<div class="profile-stats">
<article><strong>{$rocks}</strong><span>Rocks</span></article>
<article><strong>{$friends}</strong><span>Freunde</span></article>
<article><strong>{$posts}</strong><span>Posts</span></article>
</div>
<div class="profile-level">
<div class="level-row"><span>Level {$level}</span><strong>{$levelProgress}%</strong></div>
<div class="level-bar"><i style="width:{$levelProgress}%"></i></div>
<small>Fortschritt zu Level {$nextLevel}</small>
</div>
<div class="profile-links">
<button><svg><use href="#i-user"></use></svg><span>Profil</span></button>
<button><svg><use href="#i-comment"></use></svg><span>Nachrichten</span></button>
<button><svg><use href="#i-folder"></use></svg><span>Inventar</span></button>
</div>
</aside>
HTML;

        return $this->replaceOne(
            '~<aside aria-label="Mein Bereich" class="profile-panel fixed-profile" id="profilePanel">.*?</aside>~s',
            $markup,
            $html
        );
    }

    private function replaceAgenda(string $html): string
    {
        $markup = <<<'HTML'
<aside class="schedule-panel fixed-schedule hnt-agenda-panel">
<div class="section-head"><div><span class="hnt-section-kicker">HNT.ROCKS</span><h2>Heute &amp; als Nächstes</h2></div><button class="circle-button"><svg><use href="#i-arrow"></use></svg></button></div>
<div aria-label="Zeitraum" class="week-row hnt-agenda-row">
<button class="active"><b>Jetzt</b><small>0</small></button>
<button><b>Heute</b><small>0</small></button>
<button><b>Morgen</b><small>0</small></button>
<button><b>Diese Woche</b><small>0</small></button>
</div>
<div class="hnt-agenda-timeline">
<div aria-hidden="true" class="hnt-agenda-axis"><span class="dark">JETZT</span></div>
<div class="hnt-agenda-items"><article class="hnt-agenda-card"><div class="hnt-agenda-copy"><span>HNT.ROCKS</span><h3>Agenda wird geladen</h3><p>Echte Inhalte werden vorbereitet.</p></div></article></div>
</div>
</aside>
HTML;

        return $this->replaceOne(
            '~<aside class="schedule-panel fixed-schedule hnt-agenda-panel">.*?</aside>~s',
            $markup,
            $html
        );
    }

    private function replaceCommunity(string $html): string
    {
        $markup = <<<'HTML'
<aside class="composition-panel fixed-composition" id="compositionPanel">
<div class="composition-top"><h2>Community</h2><span class="composition-live"><i></i> Wird geladen</span></div>
<div class="composition-ring"><div><strong>—</strong><span>Hunter</span></div></div>
<div class="composition-values">
<span><i class="yellow"></i><strong>—</strong><small>heute aktiv</small></span>
<span><i class="dark"></i><strong>—</strong><small>gerade online</small></span>
</div>
<div class="community-note"><span>HNT.ROCKS</span><strong>Community wird geladen</strong><small>Echte Werte werden vorbereitet.</small></div>
<div class="composition-extra">
<div class="composition-stat-grid">
<article><span>Heute aktiv</span><strong>—</strong><small>Hunter</small></article>
<article><span>Offene LFGs</span><strong>—</strong><small>aktuell</small></article>
<article><span>Moments</span><strong>—</strong><small>heute</small></article>
<article><span>Cup-Teams</span><strong>—</strong><small>aktiv</small></article>
</div>
<section class="composition-activity"><div class="activity-title"><h3>Live-Aktivität</h3><span id="activityState">Wird geladen</span></div><div class="activity-list" id="compositionActivity"><div class="activity-skeleton"></div><div class="activity-skeleton short"></div><div class="activity-skeleton"></div></div></section>
<section class="composition-trending"><div class="activity-title"><h3>Hashtags</h3><span>Letzte 30 Tage</span></div><div class="trend-tags"><button type="button" disabled>Wird geladen …</button></div></section>
</div>
</aside>
HTML;

        return $this->replaceOne(
            '~<aside class="composition-panel fixed-composition" id="compositionPanel">.*?</aside>~s',
            $markup,
            $html
        );
    }

    private function replaceOverview(
        string $html,
        User $viewer,
        array $profile,
        array $counts,
        array $progress,
        array $streak
    ): string {
        $name = $this->escape($viewer->name ?: ($viewer->username ?: 'HNT Hunter'));
        $completed = max(0, (int) ($progress['completed'] ?? 0));
        $total = max(0, (int) ($progress['total'] ?? 0));
        $levelProgress = max(0, min(100, (int) ($progress['level_progress'] ?? 0)));
        $streakCurrent = max(0, (int) ($streak['current_streak'] ?? 0));
        $streakMax = max(1, (int) ($streak['max_streak_days'] ?? 7));
        $rocks = max(0, (int) ($profile['rocks'] ?? 0));
        $messages = max(0, (int) ($counts['messages'] ?? 0));
        $notifications = max(0, (int) ($counts['notifications'] ?? 0));
        $friends = max(0, (int) ($counts['friends'] ?? 0));

        $markup = <<<HTML
<section class="feed-overview">
<div class="overview-left">
<h1>Hello {$name}</h1>
<div class="overview-progress">
<div class="overview-metric wide"><span>Wochenauftrag</span><div class="bar dark">{$completed} / {$total}</div></div>
<div class="overview-metric"><span>Login-Serie</span><div class="bar yellow">{$streakCurrent} / {$streakMax}</div></div>
<div class="overview-metric project"><span>Level-Fortschritt</span><div class="bar striped">{$levelProgress}%</div></div>
<div class="overview-metric output"><span>Rocks</span><div class="bar outline">{$rocks}</div></div>
</div>
</div>
<div aria-label="Persönliche Übersicht" class="overview-counts">
<article><strong>{$messages}</strong><span>Nachrichten</span></article>
<article><strong>{$notifications}</strong><span>Hinweise</span></article>
<article><strong>{$friends}</strong><span>Anfragen</span></article>
</div>
</section>
HTML;

        return $this->replaceOne('~<section class="feed-overview">.*?</section>~s', $markup, $html);
    }

    private function replacePersonalDashboard(
        string $html,
        array $profile,
        array $counts,
        array $progress,
        array $streak
    ): string {
        $total = max(0, (int) ($progress['total'] ?? 0));
        $completed = max(0, (int) ($progress['completed'] ?? 0));
        $open = max(0, (int) ($progress['open'] ?? 0));
        $percent = max(0, min(100, (int) ($progress['completion_percent'] ?? 0)));
        $availableXp = max(0, (int) ($progress['available_xp'] ?? 0));
        $level = max(1, (int) ($profile['level'] ?? 1));
        $levelProgress = max(0, min(100, (int) ($progress['level_progress'] ?? 0)));
        $next = is_array($progress['next_open'] ?? null) ? $progress['next_open'] : null;
        $messages = max(0, (int) ($counts['messages'] ?? 0));
        $notifications = max(0, (int) ($counts['notifications'] ?? 0));
        $friends = max(0, (int) ($counts['friends'] ?? 0));
        $streakCurrent = max(0, (int) ($streak['current_streak'] ?? 0));

        $aggregateStatus = $total === 0 ? 'Keine' : ($open === 0 ? 'Erledigt' : 'Aktiv');
        $aggregateClass = $total === 0 ? 'review-status' : ($open === 0 ? 'open-status' : 'active-status');

        $rows = $this->progressRow(
            'W',
            'contract',
            'Wochenaufträge',
            $total > 0 ? $open.' noch offen' : 'Aktuell keine aktiven Aufträge',
            $completed.' / '.$total,
            $percent,
            $availableXp.' XP',
            $aggregateStatus,
            $aggregateClass
        );

        if ($next) {
            $rows .= $this->progressRow(
                'N',
                'challenge',
                (string) ($next['name'] ?? 'Wochenauftrag'),
                (string) ($next['action'] ?? 'Wochenauftrag'),
                max(0, (int) ($next['current'] ?? 0)).' / '.max(0, (int) ($next['target'] ?? 0)),
                max(0, min(100, (int) ($next['percent'] ?? 0))),
                (string) ($next['reward'] ?? '+0 XP'),
                'Offen',
                'open-status'
            );
        } else {
            $rows .= $this->progressRow(
                '✓',
                'challenge',
                $total > 0 ? 'Alle Aufträge erledigt' : 'Keine Wochenaufträge',
                $total > 0 ? 'Starker Wochenfortschritt' : 'Aktuell ist nichts offen',
                $total > 0 ? '100%' : '—',
                $total > 0 ? 100 : 0,
                max(0, (int) ($progress['claimed_xp'] ?? 0)).' XP',
                $total > 0 ? 'Fertig' : 'Keine',
                $total > 0 ? 'open-status' : 'review-status'
            );
        }

        $rows .= $this->progressRow(
            'L',
            'profile',
            'Level '.$level,
            'Fortschritt zu Level '.($level + 1),
            $levelProgress.'%',
            $levelProgress,
            'Level '.($level + 1),
            'Läuft',
            'active-status'
        );

        $heatmap = str_repeat('<span></span>', 55);

        $markup = <<<HTML
<section class="salary-attendance-card personal-dashboard-card">
<div class="salary-section personal-progress-section">
<div class="salary-head"><div><span class="hnt-section-kicker">DEIN BEREICH</span><h2>Mein Fortschritt</h2></div><div class="personal-progress-tools"><button class="active">Aktiv</button><button>Verlauf</button><button class="circle-button"><svg><use href="#i-arrow"></use></svg></button></div></div>
<div class="personal-progress-table">
<div class="personal-progress-labels"><span>Aktivität</span><span>Fortschritt</span><span>Belohnung</span><span>Status</span></div>
{$rows}
</div>
<div class="personal-attention-strip"><span>Benötigt deine Aufmerksamkeit</span><div><button><b>{$messages}</b> Nachrichten</button><button><b>{$notifications}</b> Hinweise</button><button><b>{$friends}</b> Anfragen</button></div></div>
</div>
<aside class="attendance-panel personal-activity-panel">
<div class="attendance-head"><div><span class="personal-activity-kicker">LETZTE 30 TAGE</span><h2>Meine Aktivität</h2></div><button class="circle-button"><svg><use href="#i-arrow"></use></svg></button></div>
<div class="attendance-values personal-activity-values"><div><strong>—</strong><span>Tage aktiv</span></div><div><strong>—</strong><span>Aktionen</span></div></div>
<div aria-label="Aktivitäts-Heatmap der letzten 30 Tage" class="dot-matrix personal-heatmap">{$heatmap}</div>
<div class="personal-activity-summary"><article><strong>{$streakCurrent}</strong><span>Login-Serie</span></article><article><strong>—</strong><span>XP diese Woche</span></article><article><strong>—</strong><span>Rocks verdient</span></article></div>
</aside>
</section>
HTML;

        return $this->replaceOne(
            '~<section class="salary-attendance-card personal-dashboard-card">.*?</section>(?=\s*<section class="social-feed-card">)~s',
            $markup,
            $html
        );
    }

    private function replaceStaticPosts(string $html): string
    {
        return $this->replaceOne(
            '~<div class="post-list">.*?</div>\s*</section>\s*</div>\s*</section>(?=\s*<div aria-hidden="true" class="mobile-stats-panel")~s',
            "<div class=\"post-list\"></div>\n</section>\n</div>\n</section>",
            $html
        );
    }

    private function replaceComposerIdentity(string $html, User $viewer): string
    {
        $name = $this->escape($viewer->name ?: ($viewer->username ?: 'HNT Hunter'));
        $handle = $this->escape($viewer->username ? '@'.$viewer->username : '@hunter');
        $avatar = $this->escape($viewer->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg'));

        $markup = <<<HTML
<div class="composer-identity">
<div class="composer-avatar"><img alt="{$name}" src="{$avatar}"/><i></i></div>
<div><strong>{$name}</strong><span>{$handle}</span></div>
</div>
HTML;

        return $this->replaceOne('~<div class="composer-identity">.*?</div>\s*</div>~s', $markup, $html);
    }

    private function replaceCommentPlaceholders(string $html, User $viewer): string
    {
        $name = $this->escape($viewer->name ?: ($viewer->username ?: 'HNT Hunter'));
        $avatar = $this->escape($viewer->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg'));
        $defaultAvatar = $this->escape(asset('assets/vikinger/img/default-avatar.svg'));

        $context = <<<HTML
<article class="comments-post-context">
<img alt="" id="commentsPostAvatar" src="{$defaultAvatar}"/>
<div class="comments-post-copy"><div><strong id="commentsPostAuthor">HNT Hunter</strong><span id="commentsPostMeta">@hunter · gerade eben</span></div><p id="commentsPostExcerpt">Beitrag wird geladen …</p></div>
<span class="comments-post-badge" id="commentsPostBadge">Beitrag</span>
</article>
HTML;

        $html = $this->replaceOne('~<article class="comments-post-context">.*?</article>~s', $context, $html);
        $composerMarkup = '<form class="comments-composer" id="commentsComposer">'
            .'<img alt="'.$name.'" src="'.$avatar.'"/>';
        $html = $this->replaceOne(
            '~<form class="comments-composer" id="commentsComposer">\s*<img[^>]*>~s',
            $composerMarkup,
            $html
        );

        return $html;
    }

    private function friendRequestsMarkup(mixed $items): string
    {
        if (! is_array($items) || $items === []) {
            return '<div class="header-live-state"><strong>Keine offenen Anfragen</strong>Neue Anfragen erscheinen automatisch hier.</div>';
        }

        return collect($items)->map(function (array $item): string {
            $id = max(0, (int) ($item['id'] ?? 0));
            $name = $this->escape($item['name'] ?? 'HNT Hunter');
            $handle = $this->escape($item['handle'] ?? '@hunter');
            $time = $this->escape($item['time'] ?? '—');
            $avatar = $this->escape($item['avatar'] ?? asset('assets/vikinger/img/default-avatar.svg'));
            $profileUrl = $this->escape($item['profile_url'] ?? '#');
            $acceptUrl = $this->escape($item['accept_url'] ?? '#');
            $declineUrl = $this->escape($item['decline_url'] ?? '#');

            return <<<HTML
<article class="header-request-item" data-real-friend-request="{$id}">
<a href="{$profileUrl}" aria-label="{$name}"><img alt="{$name}" src="{$avatar}"></a>
<div><strong>{$name}</strong><small>{$handle} · {$time}</small></div>
<div class="header-request-actions"><button aria-label="{$name} annehmen" class="friend-accept" data-friend-action="accept" data-url="{$acceptUrl}"><svg><use href="#i-check"></use></svg></button><button aria-label="{$name} ablehnen" class="friend-decline" data-friend-action="decline" data-url="{$declineUrl}"><svg><use href="#i-x"></use></svg></button></div>
</article>
HTML;
        })->implode('');
    }

    private function messagesMarkup(mixed $items): string
    {
        if (! is_array($items) || $items === []) {
            return '<div class="header-live-state"><strong>Noch keine Unterhaltungen</strong>Deine privaten Nachrichten erscheinen hier.</div>';
        }

        return collect($items)->map(function (array $item): string {
            $title = $this->escape($item['title'] ?? 'Unterhaltung');
            $preview = $this->escape($item['preview'] ?? 'Noch keine Nachricht');
            $avatar = $this->escape($item['avatar'] ?? asset('assets/vikinger/img/default-avatar.svg'));
            $time = $this->escape($item['time'] ?? '—');
            $url = $this->escape($item['url'] ?? '#');
            $unread = max(0, (int) ($item['unread'] ?? 0));
            $class = $unread > 0 ? ' unread' : '';
            $badge = $unread > 0 ? ' <em>'.$unread.'</em>' : '';

            return '<a class="header-message-item'.$class.'" href="'.$url.'" role="menuitem"><img alt="'.$title.'" src="'.$avatar.'"><span><strong>'.$title.$badge.'</strong><small>'.$preview.'</small></span><time>'.$time.'</time></a>';
        })->implode('');
    }

    private function notificationsMarkup(mixed $items): string
    {
        if (! is_array($items) || $items === []) {
            return '<div class="header-live-state"><strong>Keine Benachrichtigungen</strong>Neue Hinweise erscheinen automatisch hier.</div>';
        }

        return collect($items)->map(function (array $item): string {
            $title = $this->escape($item['title'] ?? 'Benachrichtigung');
            $body = $this->escape($item['body'] ?? ($item['actor'] ?? ''));
            $time = $this->escape($item['time'] ?? '—');
            $readUrl = $this->escape($item['read_url'] ?? '#');
            $actionUrl = $this->escape($item['action_url'] ?? '');
            $unread = (bool) ($item['unread'] ?? false);
            $class = $unread ? ' unread' : '';
            $iconClass = $unread ? ' yellow' : '';

            return '<button class="header-notification-item'.$class.'" type="button" role="menuitem" data-notification-read-url="'.$readUrl.'" data-notification-action-url="'.$actionUrl.'"><span class="header-notification-icon'.$iconClass.'"><svg><use href="#i-bell"></use></svg></span><span><strong>'.$title.'</strong><small>'.$body.'</small></span><time>'.$time.'</time></button>';
        })->implode('');
    }

    private function progressRow(
        string $icon,
        string $iconClass,
        string $title,
        string $subtitle,
        string $value,
        int $percent,
        string $reward,
        string $status,
        string $statusClass
    ): string {
        $icon = $this->escape($icon);
        $iconClass = $this->escape($iconClass);
        $title = $this->escape($title);
        $subtitle = $this->escape($subtitle);
        $value = $this->escape($value);
        $percent = max(0, min(100, $percent));
        $reward = $this->escape($reward);
        $status = $this->escape($status);
        $statusClass = $this->escape($statusClass);

        return <<<HTML
<article class="personal-progress-row" data-real-dashboard-row>
<span class="personal-progress-icon {$iconClass}">{$icon}</span>
<div class="personal-progress-copy"><strong>{$title}</strong><small>{$subtitle}</small></div>
<div class="personal-progress-value"><strong>{$value}</strong><div><i style="width:{$percent}%"></i></div></div>
<span class="personal-reward">{$reward}</span>
<span class="status {$statusClass}"><i></i>{$status}</span>
</article>
HTML;
    }

    private function replaceOne(
        string $pattern,
        string $replacement,
        string $html,
        bool $literal = true
    ): string {
        $result = $literal
            ? preg_replace_callback($pattern, static fn (): string => $replacement, $html, 1)
            : preg_replace($pattern, $replacement, $html, 1);

        return is_string($result) ? $result : $html;
    }

    private function escape(mixed $value): string
    {
        return e((string) $value);
    }
}
