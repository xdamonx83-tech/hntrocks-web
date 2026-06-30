@forelse($members as $member)
        @php
            $profile = $member->profile;
            $friendship = $friendshipMap->get($member->id);
            $isOwnCard = auth()->id() === $member->id;
            $memberUrl = $isOwnCard ? route('profile.show') : route('profile.public', $member);
            $memberFriendCount = $friendCounts[$member->id] ?? 0;
            $memberLevel = app(\App\Services\GamificationService::class)->levelForXp((int) ($member->xp_total ?? 0));
            $memberHeadline = $profile?->headline ?: __('ui.profile_no_headline');
            $memberCover = $member->coverUrl();
            $memberAvatar = $member->avatarUrl();
            $memberPlatform = $profile?->platform ?: __('ui.members_platform_open');
            $memberPlaystyle = $profile?->playstyle ?: __('ui.members_playstyle_open');
            $memberRegion = $profile?->region ?: __('ui.members_region_open');
            $memberLfg = $profile?->is_lfg_available ? __('ui.members_lfg_open') : __('ui.members_lfg_not_marked');
        @endphp

        <article class="member-card {{ $loop->first ? 'member-card-featured' : '' }}">
            <a class="member-cover" href="{{ $memberUrl }}">
                <img alt="{{ __('ui.profile_cover_alt', ['name' => $member->username]) }}" src="{{ $memberCover }}"/>
            </a>
            <div class="member-card-body">
                <div class="member-card-topline">
                    <a class="member-avatar-wrap" href="{{ $memberUrl }}">
                        <img alt="{{ $member->name }}" src="{{ $memberAvatar }}"/>
                        <span><img alt="" src="{{ \App\Support\HntTheme::asset('images/bounty-mark.png', 'rework') }}"/></span>
                    </a>
                    <div>
                        <strong><a href="{{ $memberUrl }}">{{ $member->name }}</a></strong>
                        <small>{{ __('ui.gamification_level') }} {{ $memberLevel }}</small>
                    </div>
                </div>

                <p>{{ $memberHeadline }}</p>

                <div class="member-mini-stats">
                    <span><b>{{ $member->visible_feed_posts_count ?? 0 }}</b><small>{{ __('ui.profile_posts_stat') }}</small></span>
                    <span><b>{{ $memberFriendCount }}</b><small>{{ __('ui.friends') }}</small></span>
                    <span><b>{{ $member->visible_moments_count ?? 0 }}</b><small>{{ __('ui.moments') }}</small></span>
                </div>

                <div class="member-meta-grid">
                    <span>{{ $memberPlatform }}</span>
                    <span>{{ $memberPlaystyle }}</span>
                    <span>{{ $memberRegion }}</span>
                    <span class="{{ $profile?->is_lfg_available ? 'is-active' : '' }}">{{ $memberLfg }}</span>
                </div>

                <div class="member-actions">
                    @if ($isOwnCard)
                        <a class="btn" href="{{ route('profile.show') }}">{{ __('ui.members_my_profile') }}</a>
                        <a class="btn light" href="{{ route('profile.edit') }}">{{ __('ui.members_edit') }}</a>
                    @elseif (! $friendship || $friendship->isDeclined())
                        <form method="post" action="{{ route('friends.store', $member) }}">
                            @csrf
                            <button class="btn" type="submit">{{ __('ui.profile_add_friend') }}</button>
                        </form>
                        <a class="btn light" href="{{ route('messages.index') }}">{{ __('ui.profile_message_singular') }}</a>
                    @elseif ($friendship->isPending() && $friendship->isRequester(auth()->user()))
                        <form method="post" action="{{ route('friends.destroy', $friendship) }}">
                            @csrf
                            @method('DELETE')
                            <button class="btn" type="submit">{{ __('ui.members_friend_request_sent') }}</button>
                        </form>
                        <a class="btn light" href="{{ route('messages.index') }}">{{ __('ui.profile_message_singular') }}</a>
                    @elseif ($friendship->isPending() && $friendship->isRecipient(auth()->user()))
                        <form method="post" action="{{ route('friends.accept', $friendship) }}">
                            @csrf
                            <button class="btn" type="submit">{{ __('ui.profile_accept') }}</button>
                        </form>
                        <form method="post" action="{{ route('friends.decline', $friendship) }}">
                            @csrf
                            <button class="btn light" type="submit">{{ __('ui.profile_decline') }}</button>
                        </form>
                    @elseif ($friendship->isAccepted())
                        <form method="post" action="{{ route('friends.destroy', $friendship) }}" onsubmit="return confirm('{{ __('ui.profile_friend_remove_confirm') }}');">
                            @csrf
                            @method('DELETE')
                            <button class="btn" type="submit">{{ __('ui.members_friends_active') }}</button>
                        </form>
                        <a class="btn light" href="{{ route('messages.index') }}">{{ __('ui.profile_message_singular') }}</a>
                    @endif
                </div>
            </div>
        </article>
    @empty
        <article class="member-card members-empty-card">
            <div class="member-card-body">
                <div class="member-card-topline"><div><strong>{{ __('ui.members_empty_title') }}</strong><small>{{ __('ui.rework_members_title') }}</small></div></div>
                <p>{{ __('ui.members_empty_text') }}</p>
            </div>
        </article>
    @endforelse
