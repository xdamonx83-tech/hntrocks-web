<header class="social-feed-head profile-feed-head">
<div>
<span class="eyebrow">{{ __('hnt_preview.profile.eyebrow') }}</span>
<h2 id="profileTabTitle">{{ __('hnt_preview.profile.posts') }}</h2>
</div>
<div aria-label="{{ __('hnt_preview.profile.profile_sections') }}" class="feed-tabs profile-tabs" role="tablist">
<button aria-controls="profileTabPosts" aria-selected="true" class="active" data-profile-tab="posts" data-title="{{ __('hnt_preview.profile.posts') }}" role="tab" type="button">{{ __('hnt_preview.profile.posts') }}</button>
<button aria-controls="profileTabInfo" aria-selected="false" data-profile-tab="info" data-title="{{ __('hnt_preview.profile.info') }}" role="tab" type="button">{{ __('hnt_preview.profile.info') }}</button>
<button aria-controls="profileTabFriends" aria-selected="false" data-profile-tab="friends" data-title="{{ __('hnt_preview.profile.friends') }}" role="tab" type="button">{{ __('hnt_preview.profile.friends') }}</button>
<button aria-controls="profileTabMoments" aria-selected="false" data-profile-tab="moments" data-title="{{ __('hnt_preview.profile.moments') }}" role="tab" type="button">{{ __('hnt_preview.profile.moments') }}</button>
<button aria-controls="profileTabBadges" aria-selected="false" data-profile-tab="badges" data-title="{{ __('hnt_preview.profile.badges') }}" role="tab" type="button">{{ __('hnt_preview.profile.badges') }}</button>
@if($profileTwitchChannel)
<button aria-controls="profileTabTwitch" aria-selected="false" class="profile-twitch-tab {{ $profileTwitchIsLive ? 'is-live' : '' }}" data-profile-tab="twitch" data-title="Twitch" role="tab" type="button"><span class="profile-social-live-dot"></span>Twitch</button>
@endif
@if($isOwnProfile)
<button aria-label="{{ __('hnt_preview.profile.create_post') }}" class="compose-button" id="openPostComposer" type="button">
<svg><use href="#i-plus"></use></svg><span>{{ __('hnt_preview.profile.post') }}</span>
</button>
@endif
</div>
</header>