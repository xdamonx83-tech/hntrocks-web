<?php

namespace App\Support;

use App\Models\Cup;
use App\Models\FeedPost;
use App\Models\ApprovedOutboundLink;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FeedTextRenderer
{
    private const URL_PATTERN = '~((?:(?:https?:)?//[^\s<>"\']+)|(?<![\pL\pN_@])(?:www\.)?hnt\.rocks/[^\s<>"\']+|(?<![\pL\pN_@])/[A-Za-z0-9][^\s<>"\']*)~iu';

    public static function render(?string $body): string
    {
        $body = (string) $body;

        if ($body === '') {
            return '';
        }

        $users = self::usersForMentions($body);
        $html = '';
        $offset = 0;

        preg_match_all(self::URL_PATTERN, $body, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] ?? [] as $match) {
            [$candidate, $position] = $match;

            if ($position > $offset) {
                $html .= self::renderPlainSegment(substr($body, $offset, $position - $offset), $users);
            }

            $html .= self::renderUrlSegment($candidate);
            $offset = $position + strlen($candidate);
        }

        if ($offset < strlen($body)) {
            $html .= self::renderPlainSegment(substr($body, $offset), $users);
        }

        return $html;
    }

    public static function internalLinkPreviews(?string $body, int $limit = 1): array
    {
        $body = (string) $body;
        $limit = max(1, min(3, $limit));
        $previews = [];
        $seen = [];

        preg_match_all(self::URL_PATTERN, $body, $matches);

        foreach ($matches[0] ?? [] as $candidate) {
            [$url] = self::splitTrailingPunctuation((string) $candidate);
            $href = self::normalizeInternalHref($url);

            if ($href === null || isset($seen[$href])) {
                continue;
            }

            $seen[$href] = true;
            $previews[] = self::previewForHref($href);

            if (count($previews) >= $limit) {
                break;
            }
        }

        return $previews;
    }

    private static function renderUrlSegment(string $candidate): string
    {
        [$url, $trailing] = self::splitTrailingPunctuation($candidate);
        $href = self::normalizeInternalHref($url);

        if ($href === null) {
            return e($candidate);
        }

        $label = self::displayLabelForHref($href);

        return '<a class="font-semibold text-blue-500 hover:underline" href="'.e($href).'">'.e($label).'</a>'.e($trailing);
    }

    private static function renderPlainSegment(string $segment, $users): string
    {
        $escaped = e($segment);
        $linked = self::renderEmoticons($escaped);

        if (! $users->isEmpty()) {
            $linked = preg_replace_callback('/(?<![\pL\pN_@])@([A-Za-z0-9_.-]{1,32})\b/u', function (array $match) use ($users): string {
                $lookup = Str::lower($match[1]);
                $user = $users->get($lookup);

                if (! $user) {
                    return $match[0];
                }

                $label = '@'.e($user->username);
                $title = e($user->name);
                $url = e(route('profile.public', $user));

                return '<a class="hh-mention-link hnt-mention-link font-semibold text-blue-500 hover:underline" href="'.$url.'" title="'.$title.'">'.$label.'</a>';
            }, $linked) ?? $linked;
        }

        // The text is already HTML-escaped at this point. Escaped apostrophes like
        // &#039; contain a # character. Do not interpret that numeric entity as
        // a hashtag, otherwise posts render Devil&#039;s as Devil + linked #039.
        $linked = preg_replace_callback('/(?<![\pL\pN_&])#([\pL\pN_][\pL\pN_\-]{0,49})/u', function (array $match): string {
            $tag = Hashtag::normalize($match[1]);

            if (! $tag) {
                return $match[0];
            }

            return '<a class="hnt-hashtag-link" href="'.e(route('hashtags.show', $tag)).'">'.e($match[0]).'</a>';
        }, $linked) ?? $linked;

        return nl2br($linked);
    }


    private static function renderEmoticons(string $escaped): string
    {
        if ($escaped === '') {
            return '';
        }

        $map = [
            ':-D' => '😆',
            ':D' => '😆',
            ':-d' => '😆',
            ':d' => '😆',
            'xD' => '😆',
            'XD' => '😆',
            ':-)' => '😊',
            ':)' => '😊',
            '=)' => '😊',
            ';-)' => '😉',
            ';)' => '😉',
            ':-(' => '🙁',
            ':(' => '🙁',
            ':-P' => '😛',
            ':P' => '😛',
            ':-p' => '😛',
            ':p' => '😛',
            ':-O' => '😮',
            ':O' => '😮',
            ':-o' => '😮',
            ':o' => '😮',
            ':-/' => '😕',
            ':/' => '😕',
            '&lt;3' => '❤️',
        ];

        $escaped = preg_replace_callback('/(?:&lt;3|:-D|:-d|:D|:d|:-\)|:\)|=\)|;-\)|;\)|:-\(|:\(|:-P|:-p|:P|:p|:-O|:-o|:O|:o|:-\/|:\/)(?![\pL\pN_])/u', function (array $match) use ($map): string {
            return $map[$match[0]] ?? $match[0];
        }, $escaped) ?? $escaped;

        return preg_replace_callback('/(?<![\pL\pN_])(?:xD|XD)(?![\pL\pN_])/u', function (array $match) use ($map): string {
            return $map[$match[0]] ?? $match[0];
        }, $escaped) ?? $escaped;
    }

    private static function usersForMentions(string $body)
    {
        $usernames = MentionRenderer::extractUsernames($body);

        if ($usernames === []) {
            return collect();
        }

        return User::query()
            ->whereIn(DB::raw('LOWER(username)'), $usernames)
            ->get(['id', 'name', 'username'])
            ->keyBy(fn (User $user): string => Str::lower($user->username));
    }

    private static function splitTrailingPunctuation(string $candidate): array
    {
        $clean = rtrim($candidate, '.,!?;:');

        if ($clean === '') {
            return [$candidate, ''];
        }

        return [$clean, substr($candidate, strlen($clean)) ?: ''];
    }

    private static function normalizeInternalHref(string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return self::cleanRelativeHref($url);
        }

        if (Str::startsWith(Str::lower($url), ['hnt.rocks/', 'www.hnt.rocks/'])) {
            $url = 'https://'.$url;
        }

        if (str_starts_with($url, '//')) {
            $url = 'https:'.$url;
        }

        $parts = parse_url($url);

        if (! is_array($parts)) {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true) || ! self::isAllowedInternalHost($host)) {
            return null;
        }

        $href = (string) ($parts['path'] ?? '/');
        $href = $href !== '' ? $href : '/';

        if (isset($parts['query']) && $parts['query'] !== '') {
            $href .= '?'.$parts['query'];
        }

        if (isset($parts['fragment']) && $parts['fragment'] !== '') {
            $href .= '#'.$parts['fragment'];
        }

        return self::cleanRelativeHref($href);
    }

    private static function cleanRelativeHref(string $href): ?string
    {
        if ($href === '' || ! str_starts_with($href, '/') || str_starts_with($href, '//')) {
            return null;
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $href)) {
            return null;
        }

        return $href;
    }

    private static function isAllowedInternalHost(string $host): bool
    {
        $allowed = ['hnt.rocks', 'www.hnt.rocks'];
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (is_string($appHost) && $appHost !== '') {
            $allowed[] = strtolower($appHost);
        }

        try {
            $requestHost = request()->getHost();
            if (is_string($requestHost) && $requestHost !== '') {
                $allowed[] = strtolower($requestHost);
            }
        } catch (\Throwable) {
            // no request context, for example during CLI cache builds
        }

        return in_array($host, array_unique($allowed), true);
    }

    private static function displayLabelForHref(string $href): string
    {
        $path = parse_url($href, PHP_URL_PATH) ?: '/';
        $query = parse_url($href, PHP_URL_QUERY);
        $fragment = parse_url($href, PHP_URL_FRAGMENT);
        $label = 'hnt.rocks'.$path;

        if ($query) {
            $label .= '?'.$query;
        }

        if ($fragment) {
            $label .= '#'.$fragment;
        }

        return $label;
    }

    private static function previewForHref(string $href): array
    {
        $path = parse_url($href, PHP_URL_PATH) ?: '/';
        $segments = collect(explode('/', trim($path, '/')))->filter()->values();
        $label = __('ui.feed_internal_link_preview_label');
        $title = __('ui.feed_internal_link_preview_title');
        $description = __('ui.feed_internal_link_preview_description');
        $icon = 'link-outline';

        if ($path === '/cup-feedback') {
            $title = __('ui.feed_internal_link_preview_cup_feedback_title');
            $description = __('ui.feed_internal_link_preview_cup_feedback_description');
            $icon = 'chatbubbles-outline';
        } elseif ($path === '/feed') {
            $title = __('ui.feed_internal_link_preview_feed_title');
            $description = __('ui.feed_internal_link_preview_feed_description');
            $icon = 'newspaper-outline';
        } elseif ($segments->get(0) === 'feed' && $segments->get(1) === 'posts' && $segments->get(2)) {
            $post = FeedPost::query()->with('user')->find((int) $segments->get(2));
            $title = $post?->user?->name ? __('ui.feed_internal_link_preview_post_by', ['name' => $post->user->name]) : __('ui.feed_internal_link_preview_post_title');
            $description = Str::limit(trim(strip_tags((string) ($post?->body ?? ''))) ?: __('ui.feed_internal_link_preview_post_description'), 120);
            $icon = 'newspaper-outline';
        } elseif ($segments->get(0) === 'cups') {
            $slug = (string) ($segments->get(1) ?? '');
            $cup = $slug !== '' ? Cup::query()->where('slug', $slug)->first(['id', 'title', 'slug', 'summary']) : null;
            $title = $cup?->title ?: __('ui.feed_internal_link_preview_cups_title');
            $description = Str::limit(trim((string) ($cup?->summary ?? '')) ?: __('ui.feed_internal_link_preview_cups_description'), 120);
            $icon = 'trophy-outline';
        } elseif ($segments->get(0) === 'teams') {
            $slug = (string) ($segments->get(1) ?? '');
            $team = $slug !== '' ? Team::query()->where('slug', $slug)->first(['id', 'name', 'tagline']) : null;
            $title = $team?->name ?: __('ui.feed_internal_link_preview_teams_title');
            $description = Str::limit(trim((string) ($team?->tagline ?? '')) ?: __('ui.feed_internal_link_preview_teams_description'), 120);
            $icon = 'users-three-outline';
        } elseif ($segments->get(0) === 'u' && $segments->get(1)) {
            $user = User::query()->where('username', (string) $segments->get(1))->first(['id', 'name', 'username']);
            $title = $user?->name ?: '@'.$segments->get(1);
            $description = __('ui.feed_internal_link_preview_profile_description');
            $icon = 'user-circle-outline';
        } elseif ($segments->get(0) === 'moments') {
            $title = __('ui.feed_internal_link_preview_moments_title');
            $description = __('ui.feed_internal_link_preview_moments_description');
            $icon = 'play-circle-outline';
        } elseif ($segments->get(0) === 'members') {
            $title = __('ui.feed_internal_link_preview_members_title');
            $description = __('ui.feed_internal_link_preview_members_description');
            $icon = 'users-outline';
        } elseif ($segments->get(0) === 'out' && $segments->get(1)) {
            $outbound = ApprovedOutboundLink::query()
                ->where('slug', (string) $segments->get(1))
                ->where('is_active', true)
                ->first(['id', 'title', 'description', 'target_domain']);
            $title = $outbound?->title ?: __('ui.feed_internal_link_preview_outbound_title');
            $description = $outbound?->description
                ? Str::limit(trim((string) $outbound->description), 120)
                : __('ui.feed_internal_link_preview_outbound_description');
            $icon = 'arrow-square-out-outline';
        } elseif ($segments->get(0) === 'app-beta') {
            $title = __('ui.feed_internal_link_preview_app_title');
            $description = __('ui.feed_internal_link_preview_app_description');
            $icon = 'device-mobile-outline';
        } elseif ($segments->get(0) === 'gamification') {
            $title = __('ui.feed_internal_link_preview_badges_title');
            $description = __('ui.feed_internal_link_preview_badges_description');
            $icon = 'medal-outline';
        }

        return [
            'url' => $href,
            'display_url' => self::displayLabelForHref($href),
            'label' => $label,
            'title' => $title,
            'description' => $description,
            'icon' => $icon,
        ];
    }
}
