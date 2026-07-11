<header class="social-feed-head profile-feed-head">
<div>
<span class="eyebrow">HNT.ROCKS PROFIL</span>
<h2 id="profileTabTitle">Posts</h2>
</div>
<div aria-label="Profilbereiche" class="feed-tabs profile-tabs" role="tablist">
<button aria-controls="profileTabPosts" aria-selected="true" class="active" data-profile-tab="posts" data-title="Posts" role="tab" type="button">Posts</button>
<button aria-controls="profileTabInfo" aria-selected="false" data-profile-tab="info" data-title="Info" role="tab" type="button">Info</button>
<button aria-controls="profileTabFriends" aria-selected="false" data-profile-tab="friends" data-title="Freunde" role="tab" type="button">Freunde</button>
<button aria-controls="profileTabMoments" aria-selected="false" data-profile-tab="moments" data-title="Moments" role="tab" type="button">Moments</button>
<button aria-controls="profileTabBadges" aria-selected="false" data-profile-tab="badges" data-title="Badges" role="tab" type="button">Badges</button>
@if($isOwnProfile)
<button aria-label="Post erstellen" class="compose-button" id="openPostComposer" type="button">
<svg><use href="#i-plus"></use></svg><span>Post</span>
</button>
@endif
</div>
</header>
