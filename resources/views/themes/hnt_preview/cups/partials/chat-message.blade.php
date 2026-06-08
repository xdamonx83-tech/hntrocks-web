@php
    $messageUser = $chatMessage->user;
    $isOwnMessage = (int) ($messageUser?->id ?? 0) === (int) auth()->id();
    $messageProfileUrl = route('members.index');

    if ($messageUser?->username) {
        $messageProfileUrl = $isOwnMessage
            ? route('profile.show')
            : route('profile.public', $messageUser);
    }

    $messageAvatar = $messageUser?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
    $messageName = $messageUser?->username ?: $messageUser?->name ?: __('ui.cup_unknown');

    $renderCupChatBody = static function (?string $body): string {
        $body = (string) $body;
        $pattern = '~(?<![A-Z0-9._@-])((?:https?://)?(?:www\.)?hnt\.rocks(?:/[^\s<>"\']*)?)~iu';
        $html = '';
        $offset = 0;

        if (! preg_match_all($pattern, $body, $matches, PREG_OFFSET_CAPTURE)) {
            return e($body);
        }

        foreach ($matches[1] as [$match, $position]) {
            $html .= e(substr($body, $offset, $position - $offset));

            $linkText = $match;
            $trailing = '';

            while ($linkText !== '' && preg_match('/[.,!?;:\)\]\}]+$/u', $linkText, $punctuationMatch)) {
                $trailing = $punctuationMatch[0].$trailing;
                $linkText = substr($linkText, 0, -strlen($punctuationMatch[0]));
            }

            $href = preg_match('~^https?://~i', $linkText) ? $linkText : 'https://'.$linkText;
            $href = preg_replace('~^http://~i', 'https://', $href);
            $host = strtolower((string) parse_url($href, PHP_URL_HOST));

            if (in_array($host, ['hnt.rocks', 'www.hnt.rocks'], true)) {
                $internalHref = (string) (parse_url($href, PHP_URL_PATH) ?: '/');
                $query = parse_url($href, PHP_URL_QUERY);
                $fragment = parse_url($href, PHP_URL_FRAGMENT);

                if (is_string($query) && $query !== '') {
                    $internalHref .= '?'.$query;
                }

                if (is_string($fragment) && $fragment !== '') {
                    $internalHref .= '#'.$fragment;
                }

                $html .= '<a href="'.e($internalHref).'">'.e($linkText).'</a>';
            } else {
                $html .= e($match);
                $trailing = '';
            }

            $html .= e($trailing);
            $offset = $position + strlen($match);
        }

        return $html.e(substr($body, $offset));
    };

    $messageBody = $renderCupChatBody($chatMessage->body);
@endphp

<article @class(['hnt-cup-chat-message', 'is-own' => $isOwnMessage]) data-cup-chat-message="{{ $chatMessage->id }}">
    <a class="hnt-cup-chat-avatar hnt-avatar-shell" href="{{ $messageProfileUrl }}" aria-label="{{ $messageName }}">
        <img src="{{ $messageAvatar }}" alt="">
    </a>

    <div class="hnt-cup-chat-bubble-wrap">
        <div class="hnt-cup-chat-meta">
            <a href="{{ $messageProfileUrl }}">{{ $messageName }}</a>
            <span>{{ $chatMessage->created_at?->diffForHumans() }}</span>
        </div>
        <p class="hnt-cup-chat-bubble">{!! nl2br($messageBody) !!}</p>
    </div>
</article>
