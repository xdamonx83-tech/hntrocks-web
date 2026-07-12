<section class="profile-page-heading">
<div>
<span>HNT.ROCKS</span>
<h1>Profil</h1>
</div>
<div class="profile-page-tools">
@if($isOwnProfile)
<a aria-label="Profil bearbeiten" class="profile-edit-main" href="{{ route('profile.edit') }}">
<svg><use href="#i-user"></use></svg>
<span>Profil bearbeiten</span>
</a>
@elseif($profileMessageUrl)
<a aria-label="Nachricht an {{ $profileDisplayName }}" class="profile-edit-main" href="{{ $profileMessageUrl }}">
<svg><use href="#i-comment"></use></svg>
<span>Nachricht</span>
</a>
@endif
<button aria-label="Profil teilen" class="circle-button" data-profile-share type="button">
<svg><use href="#i-share"></use></svg>
</button>
<button aria-label="Weitere Optionen" class="circle-button" data-toast="Weitere Profiloptionen" type="button">
<svg><use href="#i-more"></use></svg>
</button>
</div>
</section>
