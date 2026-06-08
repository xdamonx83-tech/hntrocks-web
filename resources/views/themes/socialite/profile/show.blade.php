@extends('themes.socialite.layouts.app')

@php
    $profile = $profileUser->profile;
    $completion = \App\Support\ProfileCompletion::score($profileUser);
    $isDesignProfile = request()->routeIs('design.socialite.profile*');
    $profileUrl = $isOwnProfile
        ? ($isDesignProfile ? route('design.socialite.profile') : route('profile.show'))
        : ($isDesignProfile ? route('design.socialite.profile.public', $profileUser) : route('profile.public', $profileUser));
    $realProfileUrl = $isOwnProfile
        ? route('profile.show', ['classic_profile' => 1])
        : route('profile.public', ['user' => $profileUser, 'classic_profile' => 1]);
    $editUrl = route('profile.edit');
    $mediaUpdateUrl = route('profile.media.update');
    $feedUrl = route('feed.index');
    $level = (int) ($profileUser->level ?? 1);
    $xpTotal = (int) ($profileUser->xp_total ?? 0);
    $nextLevelXp = max(250, $level * 250);
    $levelProgress = min(100, (int) round(($xpTotal % $nextLevelXp) / $nextLevelXp * 100));
    $coverUrl = $profileUser->coverUrl();
    $avatarUrl = $profileUser->avatarUrl();
    $viewer = auth()->user();
    $socialLinks = collect([
        'Discord' => $profile?->discord_name,
        'Steam' => $profile?->steam_url,
        'Twitch' => $profile?->twitch_url,
        'YouTube' => $profile?->youtube_url,
    ])->filter();
    $activeSection = $activeSection ?? 'timeline';
    $profileSectionUrl = function (string $section) use ($isOwnProfile, $profileUser, $isDesignProfile): string {
        $designRoutes = [
            'timeline' => ['own' => 'design.socialite.profile', 'public' => 'design.socialite.profile.public'],
            'about' => ['own' => 'design.socialite.profile.about', 'public' => 'design.socialite.profile.public.about'],
            'friends' => ['own' => 'design.socialite.profile.friends', 'public' => 'design.socialite.profile.public.friends'],
            'badges' => ['own' => 'design.socialite.profile.badges', 'public' => 'design.socialite.profile.public.badges'],
            'trophies' => ['own' => 'design.socialite.profile.trophies', 'public' => 'design.socialite.profile.public.trophies'],
            'teams' => ['own' => 'design.socialite.profile.teams', 'public' => 'design.socialite.profile.public.teams'],
            'contact' => ['own' => 'design.socialite.profile.contact', 'public' => 'design.socialite.profile.public.contact'],
        ];
        $liveRoutes = [
            'timeline' => ['own' => 'profile.show', 'public' => 'profile.public'],
            'about' => ['own' => 'profile.about', 'public' => 'profile.about.public'],
            'friends' => ['own' => 'profile.friends', 'public' => 'profile.friends.public'],
            'badges' => ['own' => 'profile.badges', 'public' => 'profile.badges.public'],
            'trophies' => ['own' => 'profile.trophies', 'public' => 'profile.trophies.public'],
            'teams' => ['own' => 'profile.teams', 'public' => 'profile.teams.public'],
            'contact' => ['own' => 'profile.contact', 'public' => 'profile.contact.public'],
        ];

        $sectionRoutes = $isDesignProfile ? $designRoutes : $liveRoutes;
        $routeName = $sectionRoutes[$section][$isOwnProfile ? 'own' : 'public'] ?? $sectionRoutes['timeline'][$isOwnProfile ? 'own' : 'public'];

        return $isOwnProfile ? route($routeName) : route($routeName, $profileUser);
    };
    $profileTabClasses = fn (string $section): string => 'inline-block py-3 leading-8 px-3.5 border-b-2 ' . ($activeSection === $section
        ? 'border-blue-600 text-blue-600'
        : 'border-transparent hover:text-blue-600');

    $profilePublicRouteMap = [
        'timeline' => 'profile.public',
        'about' => 'profile.about.public',
        'badges' => 'profile.badges.public',
        'trophies' => 'profile.trophies.public',
        'teams' => 'profile.teams.public',
        'friends' => 'profile.friends.public',
        'contact' => 'profile.contact.public',
    ];
    $profilePublicCanonicalRoute = $profilePublicRouteMap[$activeSection] ?? 'profile.public';
    $profilePublicCanonicalUrl = route($profilePublicCanonicalRoute, $profileUser);
    $profilePublicBaseUrl = route('profile.public', $profileUser);
    $profileCanUsePublicCanonical = ($profile?->profile_visibility ?? 'public') !== 'private';
    $profileCanonicalUrl = $profileCanUsePublicCanonical ? $profilePublicCanonicalUrl : request()->url();
    $profileBaseCanonicalUrl = $profileCanUsePublicCanonical ? $profilePublicBaseUrl : request()->url();
    $isPublicProfileRoute = request()->routeIs('profile.public', 'profile.*.public');
    $profileIndexableSections = ['timeline', 'about', 'badges', 'trophies', 'teams'];
    $profileRobots = (! $isDesignProfile && $isPublicProfileRoute && ($profile?->profile_visibility ?? 'public') === 'public' && in_array($activeSection, $profileIndexableSections, true))
        ? 'index,follow'
        : 'noindex,follow';
    $profileDisplayName = trim((string) ($profileUser->name ?: $profileUser->username));
    $profileUsername = trim((string) $profileUser->username);
    $profileSeoTitle = trim($profileDisplayName . ($profileUsername !== '' && $profileUsername !== $profileDisplayName ? ' (@' . $profileUsername . ')' : '')) . ' · ' . __('ui.profile_seo_title_suffix') . ' · HNT.rocks';
    $profileHeadline = trim(strip_tags((string) ($profile?->headline ?? '')));
    $profileBio = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($profile?->bio ?? ''))));
    $profileStatSummary = collect([
        __('ui.profile_seo_level_stat', ['level' => $level]),
        trans_choice('ui.profile_seo_badge_stat', (int) ($profileUser->badges_count ?? 0), ['count' => (int) ($profileUser->badges_count ?? 0)]),
        trans_choice('ui.profile_seo_moment_stat', (int) ($profileUser->moments_count ?? 0), ['count' => (int) ($profileUser->moments_count ?? 0)]),
        trans_choice('ui.profile_seo_team_stat', (int) ($profileUser->active_teams_count ?? 0), ['count' => (int) ($profileUser->active_teams_count ?? 0)]),
    ])->filter()->implode(' · ');
    $profileSeoDescriptionSource = $profileHeadline !== '' ? $profileHeadline : ($profileBio !== '' ? $profileBio : __('ui.profile_seo_fallback_description', ['name' => $profileDisplayName, 'stats' => $profileStatSummary]));
    $profileSeoDescription = \Illuminate\Support\Str::limit($profileSeoDescriptionSource, 155, '');
    $profileSeoImage = $profileUser->cover_path ? $coverUrl : ($profileUser->avatar_path ? $avatarUrl : asset('assets/socialite/images/seo/hnt-og-default.png'));
    $profileSeoImage = \Illuminate\Support\Str::startsWith($profileSeoImage, ['http://', 'https://']) ? $profileSeoImage : url($profileSeoImage);
    $profileSeoAvatarImage = \Illuminate\Support\Str::startsWith($avatarUrl, ['http://', 'https://']) ? $avatarUrl : url($avatarUrl);
    $profileSeoImageAlt = __('ui.profile_seo_image_alt', ['name' => $profileDisplayName]);
    $profileStructuredData = [
        '@context' => 'https://schema.org',
        '@type' => 'ProfilePage',
        '@id' => $profileBaseCanonicalUrl . '#profile',
        'url' => $profileBaseCanonicalUrl,
        'name' => $profileSeoTitle,
        'description' => $profileSeoDescription,
        'inLanguage' => str_replace('_', '-', app()->getLocale()),
        'isPartOf' => [
            '@type' => 'WebSite',
            '@id' => rtrim((string) config('app.url', 'https://hnt.rocks'), '/') . '/#website',
        ],
        'mainEntity' => [
            '@type' => 'Person',
            '@id' => $profileBaseCanonicalUrl . '#person',
            'name' => $profileDisplayName,
            'alternateName' => $profileUsername !== '' ? $profileUsername : null,
            'url' => $profileBaseCanonicalUrl,
            'image' => $profileSeoAvatarImage,
            'description' => $profileBio !== '' ? \Illuminate\Support\Str::limit($profileBio, 300, '') : null,
            'memberOf' => [
                '@type' => 'Organization',
                '@id' => rtrim((string) config('app.url', 'https://hnt.rocks'), '/') . '/#organization',
                'name' => 'HNT.rocks',
            ],
            'interactionStatistic' => [
                [
                    '@type' => 'InteractionCounter',
                    'interactionType' => ['@type' => 'CreateAction'],
                    'name' => __('ui.profile_seo_posts_stat_name'),
                    'userInteractionCount' => (int) ($profileUser->visible_feed_posts_count ?? 0),
                ],
                [
                    '@type' => 'InteractionCounter',
                    'interactionType' => ['@type' => 'CreateAction'],
                    'name' => __('ui.profile_seo_moments_stat_name'),
                    'userInteractionCount' => (int) ($profileUser->moments_count ?? 0),
                ],
            ],
        ],
    ];
    $profileStructuredData['mainEntity'] = array_filter($profileStructuredData['mainEntity'], static fn ($value) => $value !== null && $value !== '');
    $profileCosmetics = $profileCosmetics ?? \App\Support\CrownCosmetics::forUser($profileUser);
    $avatarFrameClass = trim((string) ($profileCosmetics['avatar_frame_class'] ?? ''));
    $usernameEffectClass = trim((string) ($profileCosmetics['username_effect_class'] ?? ''));
    $profileBannerClass = trim((string) ($profileCosmetics['profile_banner_class'] ?? ''));
    $profileTitleLabel = trim((string) ($profileCosmetics['profile_title_label'] ?? ''));
@endphp

@section('title', $profileSeoTitle)
@section('meta_description', $isDesignProfile ? 'Socialite theme profile preview with HNT.rocks profile data.' : $profileSeoDescription)
@section('canonical_url', $isDesignProfile ? $profileBaseCanonicalUrl : $profileCanonicalUrl)
@section('robots', $profileRobots)
@section('og_type', 'profile')
@section('og_image', $profileSeoImage)

@push('head')
    <meta property="og:image:alt" content="{{ $profileSeoImageAlt }}">
    <meta property="profile:username" content="{{ $profileUsername }}">
    <meta name="twitter:image:alt" content="{{ $profileSeoImageAlt }}">
    <script type="application/ld+json">{!! json_encode($profileStructuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
@endpush

@section('content')
            <div class="max-w-[1065px] mx-auto max-lg:-m-2.5">

                <!-- cover -->
                <div class="bg-white shadow lg:rounded-b-2xl lg:-mt-10 dark:bg-dark2">

                    <div class="relative overflow-hidden w-full lg:h-72 h-48 {{ $profileBannerClass }}">
                        <img src="{{ $coverUrl }}" alt="{{ $profileUser->username }}" class="h-full w-full object-cover inset-0" data-profile-cover-img>
                        <div class="w-full bottom-0 absolute left-0 bg-gradient-to-t from-black/60 pt-20 z-10"></div>

                        <div class="absolute bottom-0 right-0 m-4 z-20">
                            <div class="flex items-center gap-3">
                                @if($isOwnProfile)
                                    <a href="{{ $editUrl }}#cover" class="button bg-black/10 text-white flex items-center gap-2 backdrop-blur-small" data-profile-media-edit-trigger="cover" aria-label="{{ __('ui.edit_cover_image') }}">
                                        <ion-icon name="image-outline" class="text-lg"></ion-icon>
                                        <span>{{ __('ui.edit_cover') }}</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- user info -->
                    <div class="p-3">
                        <div class="flex flex-col justify-center md:items-center lg:-mt-48 -mt-28">

                            <div class="relative lg:h-48 lg:w-48 h-28 w-28 aspect-square mb-5 z-10 {{ $avatarFrameClass ? 'hh-crowns-avatar-wrap ' . $avatarFrameClass : '' }}">
                                <div class="relative h-full w-full overflow-hidden rounded-full md:border-[6px] border-4 border-gray-100 shrink-0 dark:border-slate-900 shadow-xl bg-white dark:bg-dark3">
                                    <img src="{{ $avatarUrl }}" alt="{{ $profileUser->username }}" class="block h-full w-full object-cover object-center" data-profile-avatar-img>
                                </div>
                                @if($isOwnProfile)
                                    <a href="{{ $editUrl }}#avatar" class="absolute -top-3 left-1/2 z-20 grid h-10 w-10 -translate-x-1/2 place-items-center rounded-full bg-white text-slate-700 shadow-lg ring-2 ring-white transition hover:scale-105 hover:bg-blue-600 hover:text-white dark:bg-dark3 dark:text-white dark:ring-slate-900" data-profile-media-edit-trigger="avatar" aria-label="{{ __('ui.edit_avatar') }}" title="{{ __('ui.edit_avatar') }}">
                                        <ion-icon name="camera-outline" class="text-xl"></ion-icon>
                                    </a>
                                @endif
                                <div class="absolute -bottom-3 left-1/2 -translate-x-1/2 bg-white shadow px-3 py-1.5 rounded-full sm:flex hidden items-center gap-1 text-sm font-bold dark:bg-dark3 dark:text-white">
                                    <ion-icon name="flash-outline" class="text-lg"></ion-icon>
                                    <span>Lv. {{ $level }}</span>
                                </div>
                            </div>

                            <h3 class="md:text-4xl text-xl leading-tight font-bold text-black text-center dark:text-white {{ $usernameEffectClass }}">{{ $profileUser->name }}</h3>

                            @if($profileTitleLabel !== '')
                                <p class="hh-crowns-profile-title">{{ $profileTitleLabel }}</p>
                            @endif

                            <p class="mt-2 text-center text-gray-500 dark:text-white/80">
                                {{ '@' . $profileUser->username }}
                                @if($profile?->headline)
                                    <span class="mx-1.5">·</span>{{ $profile->headline }}
                                @endif
                                @if($isOwnProfile)
                                    <a href="{{ $editUrl }}" class="text-blue-500 ml-3 inline-block">{{ __('ui.edit') }}</a>
                                @endif
                            </p>

                            @if($profile?->bio)
                                <p class="mt-2 max-w-2xl text-sm md:font-normal font-light text-center text-gray-600 dark:text-white/80">{{ $profile->bio }}</p>
                            @endif

                            <div class="mt-3 flex flex-wrap justify-center gap-2 text-xs font-semibold text-gray-600 dark:text-white/80">
                                <span class="px-2.5 py-1 rounded-full bg-slate-100 dark:bg-dark3">{{ $profile?->platform ?: __('ui.platform_open') }}</span>
                                <span class="px-2.5 py-1 rounded-full bg-slate-100 dark:bg-dark3">{{ $profile?->playstyle ?: __('ui.playstyle_open') }}</span>
                                <span class="px-2.5 py-1 rounded-full bg-slate-100 dark:bg-dark3">{{ $profile?->region ?: __('ui.region_open') }}</span>
                                @if($profile?->is_lfg_available)
                                    <span class="px-2.5 py-1 rounded-full bg-blue-100 text-blue-600 dark:bg-white/10 dark:text-white">{{ __('ui.lfg_open') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- navigations -->
                    <div class="flex items-center justify-between mt-2 border-t border-gray-100 px-2 max-lg:flex-col dark:border-slate-700"
                         uk-sticky="offset:50; cls-active: bg-white/80 shadow rounded-b-2xl z-50 backdrop-blur-xl dark:!bg-slate-700/80; animation:uk-animation-slide-top ; media: 992">

                        <div class="flex items-center gap-2 text-sm py-2 pr-1 max-md:w-full lg:order-2">
                            @if($isOwnProfile)
                                <a href="{{ $editUrl }}" class="button bg-primary flex items-center gap-2 text-white py-2 px-3.5 max-md:flex-1">
                                    <ion-icon name="create-outline" class="text-xl"></ion-icon>
                                    <span class="text-sm">{{ __('ui.profile_edit') }}</span>
                                </a>
                            @else
                                @if($viewer)
                                    <div class="flex items-center gap-2 max-md:flex-1">
                                        @if (! $friendship || $friendship->isDeclined())
                                            <form method="post" action="{{ route('friends.store', $profileUser) }}" class="max-md:flex-1">
                                                @csrf
                                                <button class="button bg-primary flex items-center justify-center gap-2 text-white py-2 px-3.5 w-full" type="submit">
                                                    <ion-icon name="person-add-outline" class="text-xl"></ion-icon>
                                                    <span class="text-sm">{{ __('ui.profile_add_friend_clean') }}</span>
                                                </button>
                                            </form>
                                        @elseif ($friendship->isPending() && $friendship->isRequester($viewer))
                                            <form method="post" action="{{ route('friends.destroy', $friendship) }}" class="max-md:flex-1">
                                                @csrf
                                                @method('DELETE')
                                                <button class="button bg-secondery flex items-center justify-center gap-2 py-2 px-3.5 w-full dark:bg-dark3" type="submit">
                                                    <ion-icon name="time-outline" class="text-xl"></ion-icon>
                                                    <span class="text-sm">{{ __('ui.profile_request_sent') }}</span>
                                                </button>
                                            </form>
                                        @elseif ($friendship->isPending() && $friendship->isRecipient($viewer))
                                            <form method="post" action="{{ route('friends.accept', $friendship) }}">
                                                @csrf
                                                <button class="rounded-lg bg-primary text-white flex px-2.5 py-2" type="submit" aria-label="{{ __('ui.accept_friend_request') }}"><ion-icon name="checkmark-outline" class="text-xl"></ion-icon></button>
                                            </form>
                                            <form method="post" action="{{ route('friends.decline', $friendship) }}">
                                                @csrf
                                                <button class="rounded-lg bg-secondery flex px-2.5 py-2 dark:bg-dark3" type="submit" aria-label="{{ __('ui.decline_friend_request') }}"><ion-icon name="close-outline" class="text-xl"></ion-icon></button>
                                            </form>
                                        @elseif ($friendship->isAccepted())
                                            <form method="post" action="{{ route('friends.destroy', $friendship) }}" class="max-md:flex-1">
                                                @csrf
                                                @method('DELETE')
                                                <button class="button bg-secondery flex items-center justify-center gap-2 py-2 px-3.5 w-full dark:bg-dark3" type="submit">
                                                    <ion-icon name="checkmark-circle-outline" class="text-xl"></ion-icon>
                                                    <span class="text-sm">{{ __('ui.friends') }}</span>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                    <a href="{{ route('messages.with-user', $profileUser) }}" class="rounded-lg bg-secondery flex px-2.5 py-2 dark:bg-dark3" aria-label="{{ __('ui.messages') }}">
                                        <ion-icon name="chatbubble-ellipses-outline" class="text-xl"></ion-icon>
                                    </a>
                                @else
                                    <a href="{{ route('login') }}" class="button bg-primary flex items-center gap-2 text-white py-2 px-3.5 max-md:flex-1">
                                        <ion-icon name="log-in-outline" class="text-xl"></ion-icon>
                                        <span class="text-sm">{{ __('ui.login') }}</span>
                                    </a>
                                @endif
                            @endif

                            <button type="button" class="rounded-lg bg-secondery flex px-2.5 py-2 dark:bg-dark3" data-socialite-share-button data-share-url="{{ $profileUrl }}" data-share-title="{{ e($profileUser->name . ' · HNT.rocks') }}" aria-label="{{ __('ui.share_profile') }}">
                                <ion-icon name="share-social-outline" class="text-xl"></ion-icon>
                            </button>
                        </div>

                        <nav class="flex gap-0.5 rounded-xl -mb-px text-gray-600 font-medium text-[15px] dark:text-white max-md:w-full max-md:overflow-x-auto">
                            <a href="{{ $profileSectionUrl('timeline') }}" class="{{ $profileTabClasses('timeline') }}">{{ __('ui.profile_section_timeline') }}</a>
                            <a href="{{ $profileSectionUrl('about') }}" class="{{ $profileTabClasses('about') }}">{{ __('ui.profile_about_title_suffix') }}</a>
                            <a href="{{ $profileSectionUrl('friends') }}" class="{{ $profileTabClasses('friends') }}">{{ __('ui.friends') }} <span class="text-xs pl-2 font-normal lg:inline-block hidden">{{ $profileFriendsCount ?? 0 }}</span></a>
                            <a href="{{ $profileSectionUrl('badges') }}" class="{{ $profileTabClasses('badges') }}">{{ __('ui.gamification_badges') }} <span class="text-xs pl-2 font-normal lg:inline-block hidden">{{ $profileUser->badges_count ?? 0 }}</span></a>
                            <a href="{{ $profileSectionUrl('trophies') }}" class="{{ $profileTabClasses('trophies') }}">{{ __('ui.profile_trophies') }}</a>
                            <a href="{{ $profileSectionUrl('teams') }}" class="{{ $profileTabClasses('teams') }}">{{ __('ui.teams') }} <span class="text-xs pl-2 font-normal lg:inline-block hidden">{{ $profileUser->active_teams_count ?? 0 }}</span></a>
                            <a href="{{ $profileSectionUrl('contact') }}" class="{{ $profileTabClasses('contact') }}">{{ __('ui.profile_section_contact') }}</a>
                        </nav>
                    </div>
                </div>

                <div class="flex items-start gap-6 mt-8 max-lg:flex-col" id="js-oversized">

                    <!-- profile main content -->
                    <div class="flex-1 min-w-0 xl:space-y-6 space-y-3">
                        @if($activeSection === 'timeline')
                            @if($isOwnProfile)
                                <div class="bg-white rounded-xl shadow-sm p-4 space-y-4 text-sm font-medium border1 dark:bg-dark2">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 bg-slate-100 hover:bg-opacity-80 transition-all rounded-lg cursor-pointer dark:bg-dark3" uk-toggle="target: #create-status">
                                            <div class="py-2.5 text-center dark:text-white">{{ __('ui.profile_share_prompt') }}</div>
                                        </div>
                                        <div class="cursor-pointer hover:bg-opacity-80 p-1 px-1.5 rounded-lg transition-all bg-pink-100/60 hover:bg-pink-100" uk-toggle="target: #create-status">
                                            <ion-icon name="images-outline" class="text-3xl text-pink-600"></ion-icon>
                                        </div>
                                        <div class="cursor-pointer hover:bg-opacity-80 p-1 px-1.5 rounded-lg transition-all bg-sky-100/60 hover:bg-sky-100" uk-toggle="target: #create-status">
                                            <ion-icon name="videocam-outline" class="text-3xl text-sky-600"></ion-icon>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @forelse($profilePosts as $post)
                                <div id="profile-post-{{ $post->id }}">
                                    @include('themes.socialite.feed.partials.post-card', ['post' => $post])
                                </div>
                            @empty
                                <div class="bg-white rounded-xl shadow-sm p-6 border1 dark:bg-dark2">
                                    <h3 class="font-bold text-base text-black dark:text-white">{{ __('ui.profile_no_timeline_title') }}</h3>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-white/70">
                                        {{ $isOwnProfile ? __('ui.profile_timeline_empty_own') : __('ui.profile_timeline_empty_user', ['name' => $profileUser->username]) }}
                                    </p>
                                    @if($isOwnProfile)
                                        <a href="{{ $feedUrl }}" class="button bg-primary text-white mt-4 inline-flex">{{ __('ui.create_first_post') }}</a>
                                    @endif
                                </div>
                            @endforelse

                            @if (($profilePostsTotal ?? 0) > ($profilePostsShown ?? 0))
                                @php
                                    $profilePostQueryParams = array_merge(request()->query(), ['profile_posts_page' => ($profilePostPage ?? 1) + 1]);
                                    $profileMoreUrl = $profileUrl . '?' . http_build_query($profilePostQueryParams);
                                @endphp
                                <div class="bg-white rounded-xl shadow-sm p-4 border1 text-center dark:bg-dark2">
                                    <p class="text-sm text-gray-500 dark:text-white/70">{{ __('ui.profile_posts_shown', ['shown' => $profilePostsShown, 'total' => $profilePostsTotal]) }}</p>
                                    <a class="button bg-secondery text-black mt-3 dark:bg-dark3 dark:text-white" href="{{ $profileMoreUrl }}">{{ __('ui.load_more') }}</a>
                                </div>
                            @endif
                        @elseif($activeSection === 'about')
                            <div class="space-y-5">
                                <div class="bg-white rounded-xl shadow-sm p-6 border1 dark:bg-dark2">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-wide text-blue-500">{{ __('ui.profile_info') }}</p>
                                            <h3 class="mt-1 font-bold text-xl text-black dark:text-white">{{ __('ui.profile_about_of', ['name' => $profileUser->name]) }}</h3>
                                        </div>
                                        @if($isOwnProfile)
                                            <a href="{{ $editUrl }}" class="button bg-secondery py-2 px-3 text-sm dark:bg-dark3">{{ __('ui.edit') }}</a>
                                        @endif
                                    </div>
                                    <p class="mt-4 text-sm leading-6 text-gray-600 dark:text-white/80">{{ $profile?->bio ?: __('ui.profile_no_bio') }}</p>
                                </div>

                                <div class="grid md:grid-cols-2 gap-4">
                                    <div class="bg-white rounded-xl shadow-sm p-5 border1 dark:bg-dark2">
                                        <h4 class="font-bold text-base text-black dark:text-white">Account</h4>
                                        <div class="mt-4 space-y-3 text-sm">
                                            <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-100 p-3 dark:bg-dark3"><span class="text-gray-500">{{ __('ui.profile_joined') }}</span><strong class="text-black dark:text-white">{{ $profileUser->created_at?->format('d.m.Y') ?: '—' }}</strong></div>
                                            <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-100 p-3 dark:bg-dark3"><span class="text-gray-500">Level</span><strong class="text-black dark:text-white">{{ $level }}</strong></div>
                                            <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-100 p-3 dark:bg-dark3"><span class="text-gray-500">XP</span><strong class="text-black dark:text-white">{{ number_format($xpTotal, 0, ',', '.') }}</strong></div>
                                        </div>
                                    </div>

                                    <div class="bg-white rounded-xl shadow-sm p-5 border1 dark:bg-dark2">
                                        <h4 class="font-bold text-base text-black dark:text-white">{{ __('ui.profile_hunt_profile') }}</h4>
                                        <div class="mt-4 space-y-3 text-sm">
                                            <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-100 p-3 dark:bg-dark3"><span class="text-gray-500">{{ __('ui.profile_role') }}</span><strong class="text-black dark:text-white">{{ $profile?->hunt_role ?: '—' }}</strong></div>
                                            <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-100 p-3 dark:bg-dark3"><span class="text-gray-500">{{ __('ui.platform') }}</span><strong class="text-black dark:text-white">{{ $profile?->platform ?: '—' }}</strong></div>
                                            <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-100 p-3 dark:bg-dark3"><span class="text-gray-500">{{ __('ui.region') }}</span><strong class="text-black dark:text-white">{{ $profile?->region ?: '—' }}</strong></div>
                                            <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-100 p-3 dark:bg-dark3"><span class="text-gray-500">{{ __('ui.profile_playstyle') }}</span><strong class="text-black dark:text-white">{{ $profile?->playstyle ?: '—' }}</strong></div>
                                            <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-100 p-3 dark:bg-dark3"><span class="text-gray-500">{{ __('ui.language') }}</span><strong class="text-black dark:text-white">{{ $profile?->language ?: '—' }}</strong></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @elseif($activeSection === 'friends')
                            <div class="bg-white rounded-xl shadow-sm p-6 border1 dark:bg-dark2">
                                <div class="flex items-baseline justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-500">{{ __('ui.network') }}</p>
                                        <h3 class="mt-1 font-bold text-xl text-black dark:text-white">{{ __('ui.friends') }} <span class="text-gray-400">{{ $profileFriendsCount ?? 0 }}</span></h3>
                                    </div>
                                </div>

                                <div class="grid lg:grid-cols-2 grid-cols-1 gap-3 mt-5 text-sm font-medium dark:text-white">
                                    @forelse($profileFriendsPreview as $friend)
                                        @php
                                            $friendUrl = $isDesignProfile ? route('design.socialite.profile.public', $friend) : route('profile.public', $friend);
                                        @endphp
                                        <a href="{{ $friendUrl }}" class="group flex items-center gap-3 rounded-2xl bg-slate-50 p-3 transition hover:bg-slate-100 dark:bg-dark3 dark:hover:bg-white/10">
                                            <div class="relative h-16 w-16 shrink-0 overflow-hidden rounded-2xl bg-slate-100 shadow-sm dark:bg-dark4">
                                                <img src="{{ $friend->avatarUrl() }}" alt="{{ $friend->name }}" class="object-cover w-full h-full transition duration-300 group-hover:scale-105">
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="line-clamp-1 font-bold text-black dark:text-white">{{ $friend->name }}</div>
                                                @if($friend->username)
                                                    <div class="line-clamp-1 text-xs text-gray-500">{{ '@' . $friend->username }}</div>
                                                @endif
                                                <div class="mt-1 text-xs text-gray-500">{{ (int) ($friend->common_friends_count ?? 0) }} mutual friends</div>
                                            </div>
                                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-white text-blue-500 shadow-sm dark:bg-dark2">
                                                <ion-icon name="person-outline" class="text-lg"></ion-icon>
                                            </span>
                                        </a>
                                    @empty
                                        <div class="col-span-full rounded-xl bg-slate-50 p-5 text-sm text-gray-500 dark:bg-dark3 dark:text-white/70">{{ __('ui.profile_no_friends_visible') }}</div>
                                    @endforelse
                                </div>
                            </div>
                        @elseif($activeSection === 'badges')
                            <div class="bg-white rounded-xl shadow-sm p-6 border1 dark:bg-dark2">
                                <div class="flex items-baseline justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-500">Achievements</p>
                                        <h3 class="mt-1 font-bold text-xl text-black dark:text-white">{{ __('ui.gamification_badges') }} <span class="text-gray-400">{{ $profileUser->badges_count ?? 0 }}</span></h3>
                                    </div>
                                </div>

                                <div class="grid xl:grid-cols-2 grid-cols-1 gap-3 mt-5">
                                    @forelse($latestBadges as $badge)
                                        @php
                                            $badgeDescription = (string) ($badge->description ?? $badge->summary ?? $badge->hint ?? 'Badge unlocked.');
                                        @endphp
                                        <a href="{{ $profileSectionUrl('badges') }}" class="group flex items-center gap-3 rounded-2xl bg-slate-50 p-3 transition hover:bg-slate-100 dark:bg-dark3 dark:hover:bg-white/10" title="{{ $badge->name }}">
                                            <div class="relative h-14 w-14 shrink-0 rounded-2xl overflow-hidden bg-white grid place-items-center p-2 shadow-sm transition group-hover:-translate-y-0.5 group-hover:shadow-md dark:bg-dark2">
                                                @if($badge->iconUrl())
                                                    <img src="{{ $badge->iconUrl() }}" alt="{{ $badge->name }}" class="object-contain w-full h-full">
                                                @else
                                                    <span class="font-bold text-xl text-black dark:text-white">{{ $badge->icon ?: '◆' }}</span>
                                                @endif
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="line-clamp-1 text-sm font-bold text-black dark:text-white">{{ $badge->name }}</div>
                                                <p class="mt-1 line-clamp-2 text-xs leading-5 text-gray-500 dark:text-white/70">{{ \Illuminate\Support\Str::limit($badgeDescription, 105) }}</p>
                                            </div>
                                        </a>
                                    @empty
                                        <div class="col-span-full rounded-xl bg-slate-50 p-5 text-sm text-gray-500 dark:bg-dark3 dark:text-white/70">{{ __('ui.profile_no_badges_unlocked') }}</div>
                                    @endforelse
                                </div>
                            </div>
                        @elseif($activeSection === 'trophies')
                            @include('themes.socialite.profile.partials.trophy-cabinet')
                        @elseif($activeSection === 'teams')
                            <div class="bg-white rounded-xl shadow-sm p-6 border1 dark:bg-dark2">
                                <div class="flex items-baseline justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-500">Squads</p>
                                        <h3 class="mt-1 font-bold text-xl text-black dark:text-white">{{ __('ui.teams') }} <span class="text-gray-400">{{ $profileUser->active_teams_count ?? 0 }}</span></h3>
                                    </div>
                                </div>

                                <div class="space-y-3 mt-5">
                                    @forelse($profileTeams as $team)
                                        <div class="flex items-center gap-4 rounded-2xl bg-slate-50 p-3 transition hover:bg-slate-100 dark:bg-dark3 dark:hover:bg-white/10">
                                            <a href="{{ route('teams.show', $team) }}" class="shrink-0">
                                                <img src="{{ $team->avatarUrl() }}" alt="{{ $team->name }}" class="w-16 h-16 rounded-2xl object-cover shadow-sm">
                                            </a>
                                            <div class="flex-1 min-w-0">
                                                <a href="{{ route('teams.show', $team) }}" class="font-bold text-black dark:text-white line-clamp-1">{{ $team->name }}</a>
                                                @if($team->tagline || $team->description)
                                                    <p class="mt-1 line-clamp-2 text-xs text-gray-500 dark:text-white/70">{{ \Illuminate\Support\Str::limit((string) ($team->tagline ?: $team->description), 90) }}</p>
                                                @endif
                                                <div class="mt-2 flex flex-wrap gap-2 text-xs text-gray-500">
                                                    <span class="rounded-full bg-white px-2 py-1 dark:bg-dark2">{{ trans_choice('ui.hunter_count', (int) ($team->active_members_count ?? 0), ['count' => (int) ($team->active_members_count ?? 0)]) }}</span>
                                                    @if(isset($team->posts_count))<span class="rounded-full bg-white px-2 py-1 dark:bg-dark2">{{ (int) $team->posts_count }} posts</span>@endif
                                                    @if(isset($team->open_lfg_posts_count) && (int) $team->open_lfg_posts_count > 0)<span class="rounded-full bg-blue-100 px-2 py-1 text-blue-600 dark:bg-white/10 dark:text-white">{{ (int) $team->open_lfg_posts_count }} LFG</span>@endif
                                                </div>
                                            </div>
                                            <a href="{{ route('teams.show', $team) }}" class="button bg-primary text-white shrink-0">{{ __('ui.view') }}</a>
                                        </div>
                                    @empty
                                        <div class="rounded-xl bg-slate-50 p-5 text-sm text-gray-500 dark:bg-dark3 dark:text-white/70">{{ __('ui.profile_no_active_teams_visible') }}</div>
                                    @endforelse
                                </div>
                            </div>
                        @elseif($activeSection === 'contact')
                            <div class="bg-white rounded-xl shadow-sm p-6 border1 dark:bg-dark2">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-500">{{ __('ui.profile_social_links') }}</p>
                                        <h3 class="mt-1 font-bold text-xl text-black dark:text-white">{{ __('ui.profile_section_contact') }}</h3>
                                    </div>
                                    @if($isOwnProfile)
                                        <a href="{{ $editUrl }}" class="button bg-secondery py-2 px-3 text-sm dark:bg-dark3">{{ __('ui.edit') }}</a>
                                    @endif
                                </div>

                                <div class="space-y-3 mt-5 text-sm">
                                    @forelse($socialLinks as $label => $value)
                                        <div class="flex items-center justify-between gap-3 rounded-2xl bg-slate-100 p-4 dark:bg-dark3">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-white text-blue-500 dark:bg-dark2">
                                                    <ion-icon name="link-outline" class="text-xl"></ion-icon>
                                                </span>
                                                <span class="font-semibold text-black dark:text-white">{{ $label }}</span>
                                            </div>
                                            @if(str_starts_with((string) $value, 'http'))
                                                <a href="{{ $value }}" rel="nofollow noopener" target="_blank" class="font-semibold text-blue-500">{{ __('ui.open') }}</a>
                                            @else
                                                <span class="font-semibold text-black dark:text-white truncate">{{ $value }}</span>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="rounded-xl bg-slate-50 p-5 text-sm text-gray-500 dark:bg-dark3 dark:text-white/70">{{ __('ui.profile_no_contact_links') }}</div>
                                    @endforelse
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- sidebar -->
                    <div class="w-full lg:w-80 shrink-0">
                        <div class="lg:space-y-6 space-y-4 lg:pb-8">

                            @include('themes.socialite.profile.partials.hunter-card')

                            <div id="profile-about" class="bg-white rounded-xl shadow p-5 px-6 border1 dark:bg-dark2">
                                <div class="flex items-baseline justify-between text-black dark:text-white">
                                    <h3 class="font-bold text-base">{{ __('ui.profile_about_title_suffix') }}</h3>
                                    @if($isOwnProfile)
                                        <a href="{{ $editUrl }}" class="text-sm text-blue-500">{{ __('ui.edit') }}</a>
                                    @endif
                                </div>
                                <div class="mt-4 space-y-3 text-sm text-gray-600 dark:text-white/80">
                                    <p>{{ $profile?->bio ?: __('ui.profile_no_bio') }}</p>
                                    <div class="grid grid-cols-2 gap-2 text-xs font-semibold">
                                        <div class="rounded-xl bg-slate-100 p-3 dark:bg-dark3"><div class="text-gray-500">{{ __('ui.profile_joined') }}</div><div class="mt-1 text-black dark:text-white">{{ $profileUser->created_at?->format('d.m.Y') ?: '—' }}</div></div>
                                        <div class="rounded-xl bg-slate-100 p-3 dark:bg-dark3"><div class="text-gray-500">XP</div><div class="mt-1 text-black dark:text-white">{{ number_format($xpTotal, 0, ',', '.') }}</div></div>
                                        <div class="rounded-xl bg-slate-100 p-3 dark:bg-dark3"><div class="text-gray-500">{{ __('ui.profile_role') }}</div><div class="mt-1 text-black dark:text-white">{{ $profile?->hunt_role ?: '—' }}</div></div>
                                        <div class="rounded-xl bg-slate-100 p-3 dark:bg-dark3"><div class="text-gray-500">{{ __('ui.language') }}</div><div class="mt-1 text-black dark:text-white">{{ $profile?->language ?: '—' }}</div></div>
                                    </div>
                                    <div>
                                        <div class="flex items-center justify-between text-xs font-semibold text-gray-500">
                                            <span>Level {{ $level }}</span>
                                            <span>{{ $levelProgress }}%</span>
                                        </div>
                                        <div class="h-2 mt-2 rounded-full bg-slate-100 overflow-hidden dark:bg-dark3">
                                            <div class="h-full bg-blue-600" style="width: {{ $levelProgress }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @include('themes.socialite.profile.partials.trophy-preview')

                            <div id="profile-badges" class="bg-white rounded-xl shadow p-5 px-6 border1 dark:bg-dark2">
                                <div class="flex items-baseline justify-between text-black dark:text-white">
                                    <h3 class="font-bold text-base">{{ __('ui.badges') }}</h3>
                                    <a href="{{ $profileSectionUrl('badges') }}" class="text-sm text-blue-500">{{ __('ui.see_all') }}</a>
                                </div>
                                <div class="grid grid-cols-5 gap-3 mt-4">
                                    @forelse($latestBadges->take(10) as $badge)
                                        <a href="{{ $isOwnProfile ? route('profile.badges') : route('profile.badges.public', $profileUser) }}" class="text-center" title="{{ $badge->name }}">
                                            <div class="relative w-full aspect-square rounded-xl overflow-hidden bg-slate-100 grid place-items-center dark:bg-dark3">
                                                @if($badge->iconUrl())
                                                    <img src="{{ $badge->iconUrl() }}" alt="{{ $badge->name }}" class="object-cover w-full h-full inset-0">
                                                @else
                                                    <span class="font-bold text-lg">{{ $badge->icon ?: '◆' }}</span>
                                                @endif
                                            </div>
                                        </a>
                                    @empty
                                        <div class="col-span-5 text-sm text-gray-500 dark:text-white/70">{{ __('ui.profile_no_badges_unlocked') }}</div>
                                    @endforelse
                                </div>
                            </div>

                            <div id="profile-friends" class="bg-white rounded-xl shadow p-5 px-6 border1 dark:bg-dark2">
                                <div class="flex items-baseline justify-between text-black dark:text-white">
                                    <h3 class="font-bold text-base">{{ __('ui.friends') }} <span class="text-gray-400">{{ $profileFriendsCount ?? 0 }}</span></h3>
                                    <a href="{{ $profileSectionUrl('friends') }}" class="text-sm text-blue-500">{{ __('ui.see_all') }}</a>
                                </div>
                                <div class="grid grid-cols-3 gap-3 mt-4 text-sm text-center font-medium dark:text-white">
                                    @forelse($profileFriendsPreview->take(6) as $friend)
                                        <a href="{{ ($isDesignProfile ? route('design.socialite.profile.public', $friend) : route('profile.public', $friend)) }}">
                                            <div class="relative w-full aspect-square rounded-lg overflow-hidden">
                                                <img src="{{ $friend->avatarUrl() }}" alt="{{ $friend->name }}" class="object-cover w-full h-full inset-0">
                                            </div>
                                            <div class="mt-2 line-clamp-1">{{ $friend->name }}</div>
                                        </a>
                                    @empty
                                        <div class="col-span-3 text-sm text-gray-500 dark:text-white/70">{{ __('ui.profile_no_friends_visible') }}</div>
                                    @endforelse
                                </div>
                            </div>

                            <div id="profile-teams" class="bg-white rounded-xl shadow p-5 px-6 border1 dark:bg-dark2">
                                <div class="flex items-baseline justify-between text-black dark:text-white">
                                    <h3 class="font-bold text-base">Teams</h3>
                                    <a href="{{ $profileSectionUrl('teams') }}" class="text-sm text-blue-500">{{ __('ui.see_all') }}</a>
                                </div>
                                <div class="side-list mt-4">
                                    @forelse($profileTeams as $team)
                                        <div class="side-list-item">
                                            <a href="{{ route('teams.show', $team) }}">
                                                <img src="{{ $team->avatarUrl() }}" alt="{{ $team->name }}" class="side-list-image rounded-md">
                                            </a>
                                            <div class="flex-1 min-w-0">
                                                <a href="{{ route('teams.show', $team) }}"><h4 class="side-list-title truncate">{{ $team->name }}</h4></a>
                                                <div class="side-list-info">{{ trans_choice('ui.hunter_count', (int) ($team->active_members_count ?? 0), ['count' => (int) ($team->active_members_count ?? 0)]) }}</div>
                                            </div>
                                            <a href="{{ route('teams.show', $team) }}" class="button bg-primary text-white">{{ __('ui.view') }}</a>
                                        </div>
                                    @empty
                                        <div class="text-sm text-gray-500 dark:text-white/70">{{ __('ui.profile_no_active_teams_visible') }}</div>
                                    @endforelse
                                </div>
                            </div>

                            <div id="profile-contact" class="bg-white rounded-xl shadow p-5 px-6 border1 dark:bg-dark2">
                                <div class="flex items-baseline justify-between text-black dark:text-white">
                                    <h3 class="font-bold text-base">{{ __('ui.profile_section_contact') }}</h3>
                                </div>
                                <div class="space-y-3 mt-4 text-sm">
                                    @forelse($socialLinks as $label => $value)
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-gray-500 dark:text-white/70">{{ $label }}</span>
                                            @if(str_starts_with((string) $value, 'http'))
                                                <a href="{{ $value }}" rel="nofollow noopener" target="_blank" class="font-semibold text-blue-500">{{ __('ui.open') }}</a>
                                            @else
                                                <span class="font-semibold text-black dark:text-white truncate">{{ $value }}</span>
                                            @endif
                                        </div>
                                    @empty
                                        <p class="text-gray-500 dark:text-white/70">{{ __('ui.profile_no_contact_links') }}</p>
                                    @endforelse
                                </div>
                            </div>

                            <div class="bg-white rounded-xl shadow p-5 px-6 border1 dark:bg-dark2">
                                <div class="flex items-baseline justify-between text-black dark:text-white">
                                    <h3 class="font-bold text-base">{{ __('ui.profile_progress') }}</h3>
                                    <span class="text-sm text-blue-500">{{ $completion }}%</span>
                                </div>
                                <div class="h-2 mt-4 rounded-full bg-slate-100 overflow-hidden dark:bg-dark3">
                                    <div class="h-full bg-blue-600" style="width: {{ $completion }}%"></div>
                                </div>
                                <p class="mt-3 text-sm text-gray-500 dark:text-white/70">{{ __('ui.profile_progress_help') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

@if($isOwnProfile)
    <input id="profile-media-crop-input" type="file" accept="image/jpeg,image/png,image/webp" class="hidden">
    <div id="profile-media-crop-toast" class="hidden fixed right-5 top-24 max-w-sm rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white shadow-2xl" style="z-index: 100001;"></div>

    <div id="profile-media-crop-modal" class="hidden fixed inset-0 flex items-start justify-center bg-black/75 px-3 py-8 md:items-center md:px-5" style="z-index: 100000;" aria-hidden="true">
        <div class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-dark2" style="max-height: calc(100vh - 64px);">
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 dark:border-slate-700">
                <div class="min-w-0">
                    <h3 id="profile-media-crop-title" class="text-lg font-bold text-black dark:text-white">{{ __('ui.crop_image') }}</h3>
                    <p id="profile-media-crop-help" class="mt-1 text-sm text-gray-500 dark:text-white/70">{{ __('ui.crop_image_help') }}</p>
                </div>
                <button type="button" class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-dark3 dark:text-white" data-profile-media-crop-close aria-label="{{ __('ui.close_crop_dialog') }}">
                    <ion-icon name="close-outline" class="text-xl"></ion-icon>
                </button>
            </div>

            <div class="overflow-y-auto p-5" style="max-height: calc(100vh - 150px);">
                <div id="profile-media-crop-stage" class="flex items-center justify-center overflow-hidden rounded-2xl bg-slate-100 p-3 dark:bg-dark3" style="max-height: 48vh;">
                    <canvas id="profile-media-crop-canvas" class="block max-w-full cursor-move rounded-xl bg-black shadow-sm"></canvas>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-[1fr_auto] md:items-end">
                    <label class="text-sm font-semibold text-black dark:text-white">
                        Zoom
                        <input id="profile-media-crop-zoom" type="range" min="1" max="3" step="0.01" value="1" class="mt-2 w-full accent-blue-600">
                    </label>

                    <div class="flex flex-wrap gap-2 md:justify-end">
                        <button type="button" id="profile-media-crop-rechoose" class="button bg-secondery px-4 py-2 dark:bg-dark3">{{ __('ui.choose_another') }}</button>
                        <button type="button" data-profile-media-crop-close class="button bg-secondery px-4 py-2 dark:bg-dark3">{{ __('ui.cancel') }}</button>
                        <button type="button" id="profile-media-crop-save" class="button bg-primary px-4 py-2 text-white">{{ __('ui.save_image') }}</button>
                    </div>
                </div>

                <p id="profile-media-crop-error" class="mt-4 hidden rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-600 dark:bg-red-500/10 dark:text-red-300"></p>
                <p id="profile-media-crop-status" class="mt-4 hidden rounded-xl bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-700 dark:bg-blue-500/10 dark:text-blue-200"></p>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const updateUrl = @json($mediaUpdateUrl);
            const csrfToken = @json(csrf_token());
            const triggers = document.querySelectorAll('[data-profile-media-edit-trigger]');
            const input = document.getElementById('profile-media-crop-input');
            const modal = document.getElementById('profile-media-crop-modal');
            const canvas = document.getElementById('profile-media-crop-canvas');
            const cropStage = document.getElementById('profile-media-crop-stage');
            const zoomInput = document.getElementById('profile-media-crop-zoom');
            const saveButton = document.getElementById('profile-media-crop-save');
            const rechooseButton = document.getElementById('profile-media-crop-rechoose');
            const title = document.getElementById('profile-media-crop-title');
            const help = document.getElementById('profile-media-crop-help');
            const errorBox = document.getElementById('profile-media-crop-error');
            const statusBox = document.getElementById('profile-media-crop-status');
            const toastBox = document.getElementById('profile-media-crop-toast');
            const closeButtons = document.querySelectorAll('[data-profile-media-crop-close]');
            const ctx = canvas?.getContext('2d');

            if (!input || !modal || !canvas || !cropStage || !ctx || !zoomInput || !saveButton) {
                return;
            }

            const presets = {
                avatar: {
                    label: 'Crop avatar',
                    help: 'Square crop for the round profile avatar.',
                    outputWidth: 900,
                    outputHeight: 900,
                    fileName: 'hnt-avatar.jpg',
                    fieldLabel: 'avatar',
                },
                cover: {
                    label: 'Crop cover image',
                    help: 'Wide crop for the profile title image.',
                    outputWidth: 1800,
                    outputHeight: 600,
                    fileName: 'hnt-cover.jpg',
                    fieldLabel: 'cover',
                },
            };

            const state = {
                type: 'avatar',
                image: null,
                objectUrl: null,
                zoom: 1,
                offsetX: 0,
                offsetY: 0,
                drag: null,
                busy: false,
            };

            const activePreset = () => presets[state.type] || presets.avatar;

            const setError = (message = '') => {
                if (!errorBox) return;
                errorBox.textContent = message;
                errorBox.classList.toggle('hidden', !message);
                if (message && statusBox) {
                    statusBox.textContent = '';
                    statusBox.classList.add('hidden');
                }
            };

            const setStatus = (message = '') => {
                if (!statusBox) return;
                statusBox.textContent = message;
                statusBox.classList.toggle('hidden', !message);
                if (message && errorBox) {
                    errorBox.textContent = '';
                    errorBox.classList.add('hidden');
                }
            };

            const showToast = (message = '') => {
                if (!toastBox || !message) return;
                toastBox.textContent = message;
                toastBox.classList.remove('hidden');
                window.clearTimeout(showToast.timeoutId);
                showToast.timeoutId = window.setTimeout(() => {
                    toastBox.classList.add('hidden');
                }, 2400);
            };

            const setBusy = (isBusy) => {
                state.busy = isBusy;
                saveButton.disabled = isBusy;
                saveButton.classList.toggle('opacity-70', isBusy);
                saveButton.classList.toggle('cursor-not-allowed', isBusy);
                saveButton.setAttribute('aria-busy', isBusy ? 'true' : 'false');
                if (rechooseButton) {
                    rechooseButton.disabled = isBusy;
                    rechooseButton.classList.toggle('opacity-70', isBusy);
                    rechooseButton.classList.toggle('cursor-not-allowed', isBusy);
                }
                closeButtons.forEach((button) => {
                    button.disabled = isBusy;
                    button.classList.toggle('opacity-60', isBusy);
                    button.classList.toggle('cursor-not-allowed', isBusy);
                });
            };

            const bustUrl = (url) => {
                if (!url) return url;
                const glue = url.includes('?') ? '&' : '?';
                return `${url}${glue}v=${Date.now()}`;
            };

            const configureCropPreview = () => {
                const isAvatar = state.type === 'avatar';

                canvas.classList.toggle('rounded-full', isAvatar);
                canvas.classList.toggle('rounded-xl', !isAvatar);
                canvas.style.width = isAvatar ? '420px' : '100%';
                canvas.style.maxWidth = '100%';
                canvas.style.height = 'auto';
                canvas.style.maxHeight = isAvatar ? '420px' : '340px';

                cropStage.style.maxHeight = isAvatar ? '48vh' : '42vh';
                cropStage.classList.toggle('p-3', isAvatar);
                cropStage.classList.toggle('p-2', !isAvatar);
            };

            const openModal = () => {
                const preset = activePreset();
                title.textContent = preset.label;
                help.textContent = preset.help;
                configureCropPreview();
                setStatus('');
                modal.classList.remove('hidden');
                modal.setAttribute('aria-hidden', 'false');
                modal.scrollTop = 0;
                document.documentElement.classList.add('overflow-hidden');
                document.body.classList.add('overflow-hidden');
            };

            const closeModal = () => {
                if (state.busy) return;
                modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
                document.documentElement.classList.remove('overflow-hidden');
                document.body.classList.remove('overflow-hidden');
                setError('');
                setStatus('');
            };

            const resetImageState = () => {
                state.zoom = 1;
                state.offsetX = 0;
                state.offsetY = 0;
                zoomInput.value = '1';
                if (state.objectUrl) {
                    URL.revokeObjectURL(state.objectUrl);
                    state.objectUrl = null;
                }
                state.image = null;
                state.drag = null;
            };

            const render = () => {
                if (!state.image) return;

                const preset = activePreset();
                canvas.width = preset.outputWidth;
                canvas.height = preset.outputHeight;
                configureCropPreview();

                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.fillStyle = '#000000';
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                const baseScale = Math.max(canvas.width / state.image.naturalWidth, canvas.height / state.image.naturalHeight);
                const scale = baseScale * state.zoom;
                const drawWidth = state.image.naturalWidth * scale;
                const drawHeight = state.image.naturalHeight * scale;
                let x = (canvas.width - drawWidth) / 2 + state.offsetX;
                let y = (canvas.height - drawHeight) / 2 + state.offsetY;

                const minX = canvas.width - drawWidth;
                const minY = canvas.height - drawHeight;
                x = Math.min(0, Math.max(minX, x));
                y = Math.min(0, Math.max(minY, y));
                state.offsetX = x - (canvas.width - drawWidth) / 2;
                state.offsetY = y - (canvas.height - drawHeight) / 2;

                ctx.drawImage(state.image, x, y, drawWidth, drawHeight);

            };

            const chooseFile = (type) => {
                if (state.busy) return;
                state.type = type === 'cover' ? 'cover' : 'avatar';
                input.value = '';
                input.click();
            };

            triggers.forEach((trigger) => {
                trigger.addEventListener('click', (event) => {
                    event.preventDefault();
                    chooseFile(trigger.dataset.profileMediaEditTrigger);
                });
            });

            input.addEventListener('change', () => {
                const file = input.files?.[0];
                if (!file) return;

                if (!file.type.startsWith('image/')) {
                    setError('Please choose an image file.');
                    return;
                }

                resetImageState();
                state.objectUrl = URL.createObjectURL(file);
                state.image = new Image();
                state.image.onload = () => {
                    openModal();
                    render();
                };
                state.image.onerror = () => setError('The selected image could not be loaded.');
                state.image.src = state.objectUrl;
            });

            zoomInput.addEventListener('input', () => {
                state.zoom = Number.parseFloat(zoomInput.value) || 1;
                render();
            });

            const pointerPosition = (event) => {
                const rect = canvas.getBoundingClientRect();
                return {
                    x: (event.clientX - rect.left) * (canvas.width / rect.width),
                    y: (event.clientY - rect.top) * (canvas.height / rect.height),
                };
            };

            canvas.addEventListener('pointerdown', (event) => {
                if (!state.image) return;
                canvas.setPointerCapture(event.pointerId);
                const position = pointerPosition(event);
                state.drag = {
                    x: position.x,
                    y: position.y,
                    offsetX: state.offsetX,
                    offsetY: state.offsetY,
                };
            });

            canvas.addEventListener('pointermove', (event) => {
                if (!state.drag) return;
                const position = pointerPosition(event);
                state.offsetX = state.drag.offsetX + (position.x - state.drag.x);
                state.offsetY = state.drag.offsetY + (position.y - state.drag.y);
                render();
            });

            const stopDragging = () => {
                state.drag = null;
            };

            canvas.addEventListener('pointerup', stopDragging);
            canvas.addEventListener('pointercancel', stopDragging);
            canvas.addEventListener('pointerleave', stopDragging);

            closeButtons.forEach((button) => {
                button.addEventListener('click', closeModal);
            });

            rechooseButton?.addEventListener('click', () => chooseFile(state.type));

            saveButton.addEventListener('click', async () => {
                if (!state.image || state.busy) return;

                setError('');
                setStatus(@json(__('ui.preparing_image')));
                setBusy(true);
                const originalLabel = saveButton.textContent;
                saveButton.textContent = @json(__('ui.saving'));

                try {
                    const preset = activePreset();
                    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.9));

                    if (!blob) {
                        throw new Error(@json(__('ui.crop_export_failed')));
                    }

                    const formData = new FormData();
                    formData.append('type', state.type);
                    formData.append('image', blob, preset.fileName);

                    setStatus(@json(__('ui.uploading_image')));

                    const response = await fetch(updateUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: formData,
                    });

                    const contentType = response.headers.get('content-type') || '';
                    const payload = contentType.includes('application/json')
                        ? await response.json().catch(() => ({}))
                        : {};

                    if (!response.ok) {
                        const validationMessage = payload?.errors
                            ? Object.values(payload.errors).flat().join(' ')
                            : null;
                        throw new Error(validationMessage || payload?.message || @json(__('ui.image_save_failed')));
                    }

                    const avatarImg = document.querySelector('[data-profile-avatar-img]');
                    const coverImg = document.querySelector('[data-profile-cover-img]');

                    if (payload.avatar_url && avatarImg) {
                        avatarImg.src = bustUrl(payload.avatar_url);
                    }

                    if (payload.cover_url && coverImg) {
                        coverImg.src = bustUrl(payload.cover_url);
                    }

                    setStatus('Saved. Updating preview...');
                    setBusy(false);
                    showToast(payload?.message || 'Profile image updated.');
                    closeModal();
                    resetImageState();
                } catch (error) {
                    setError(error?.message || 'The image could not be saved.');
                } finally {
                    setBusy(false);
                    saveButton.textContent = originalLabel;
                }
            });
        })();
    </script>
@endif

@endsection
