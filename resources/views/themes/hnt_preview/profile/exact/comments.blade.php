@php
    $commentComposerAvatar = $viewer?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
@endphp
<div aria-hidden="true" class="comments-modal-backdrop" id="commentsModal">
<section aria-labelledby="commentsModalTitle" aria-modal="true" class="comments-modal" role="dialog">
<header class="comments-modal-head">
<div>
<span>HNT.ROCKS</span>
<div class="comments-title-row">
<h2 id="commentsModalTitle">Kommentare</h2>
<small id="commentsModalCount">0 Kommentare</small>
</div>
</div>
<button aria-label="Kommentare schließen" class="comments-close" id="commentsClose" type="button">
<svg><use href="#i-x"></use></svg>
</button>
</header>
<div class="comments-modal-content">
<article class="comments-post-context">
<img alt="{{ $profileDisplayName }}" id="commentsPostAvatar" src="{{ $profileAvatarUrl }}"/>
<div class="comments-post-copy">
<div>
<strong id="commentsPostAuthor">{{ $profileDisplayName }}</strong>
<span id="commentsPostMeta">{{ $profileHandle }}</span>
</div>
<p id="commentsPostExcerpt">Beitrag wird geladen …</p>
</div>
<span class="comments-post-badge" id="commentsPostBadge">Beitrag</span>
</article>
<div class="comments-toolbar">
<strong>Diskussion</strong>
<button id="commentsSort" type="button">Relevant <svg><use href="#i-chevron"></use></svg></button>
</div>
<section aria-live="polite" class="comments-list" id="commentsList"></section>
</div>
<form class="comments-composer" id="commentsComposer">
<img alt="{{ $viewer?->name ?: 'HNT Hunter' }}" src="{{ $commentComposerAvatar }}"/>
<div class="comments-input-shell">
<textarea id="commentsInput" maxlength="500" placeholder="Kommentar schreiben …" rows="1"></textarea>
<div class="comments-compose-actions">
<div>
<button aria-label="Emoji auswählen" data-toast="Emoji-Auswahl" type="button"><svg><use href="#i-smile"></use></svg></button>
<button aria-label="Medien hinzufügen" type="button"><svg><use href="#i-image"></use></svg></button>
</div>
<span id="commentsCounter">0/500</span>
<button aria-label="Kommentar senden" class="comments-send" type="submit">
<svg><use href="#i-send"></use></svg>
</button>
</div>
</div>
</form>
</section>
</div>
