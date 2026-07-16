<aside aria-label="{{ $isEnglish ? 'Cup image and cup data' : 'Cup-Bild und Cup-Daten' }}" class="cup-fixed-column cup-fixed-left" id="cupFixedLeft"><article class="cup-cover-card">
<img alt="{{ $cup->title }}" src="{{ $cup->coverUrl() }}"/>
<div class="cup-cover-copy">
<div>
<span>COMMUNITY CUP</span>
<strong>{{ $cup->title }}</strong>
<small>{{ $cup->modeLabel() }} · {{ $platformLabel }}</small>
</div>
<button aria-label="{{ $isEnglish ? 'Share cup' : 'Cup teilen' }}" data-toast="{{ $isEnglish ? 'Cup link copied' : 'Cup-Link kopiert' }}" type="button">
<svg><use href="#i-share"></use></svg>
</button>
</div>
</article><section aria-label="{{ $isEnglish ? 'Cup data, internally scrollable' : 'Cup-Daten, intern scrollbar' }}" class="cup-data-stack cup-white-card" tabindex="0">
<details open="">
<summary>{{ $isEnglish ? 'Cup data' : 'Cup-Daten' }} <svg><use href="#i-chevron"></use></svg></summary>
<dl>
<div><dt>Status</dt><dd>{{ $registrationOpen ? ($isEnglish ? 'Registration open' : 'Anmeldung offen') : $cup->statusLabel() }}</dd></div>
<div><dt>{{ $isEnglish ? 'Mode' : 'Modus' }}</dt><dd>{{ $cup->modeLabel() }}</dd></div>
<div><dt>{{ $soloCup ? ($isEnglish ? 'Players' : 'Spieler') : ($isEnglish ? 'Team size' : 'Teamgröße') }}</dt><dd>{{ $teamSize }} {{ $isEnglish ? ($teamSize === 1 ? 'Hunter' : 'Hunters') : 'Hunter' }}</dd></div>
<div><dt>{{ $soloCup ? ($isEnglish ? 'Maximum' : 'Maximum') : ($isEnglish ? 'Max. teams' : 'Max. Teams') }}</dt><dd>{{ $teamLimit ?: ($isEnglish ? 'Open' : 'Offen') }}</dd></div>
<div><dt>{{ $isEnglish ? 'Language' : 'Sprache' }}</dt><dd>{{ $languageLabel }}</dd></div>
<div><dt>{{ $isEnglish ? 'Region' : 'Region' }}</dt><dd>{{ $regionLabel }}</dd></div>
</dl>
</details>
<details>
<summary>{{ $isEnglish ? 'Submissions' : 'Einreichungen' }} <svg><use href="#i-chevron"></use></svg></summary>
<dl>
<div><dt>Uploads</dt><dd>{{ $maxUploadsPerParticipant ? ($isEnglish ? 'Max. '.$maxUploadsPerParticipant.' per player' : 'Max. '.$maxUploadsPerParticipant.' pro Spieler') : ($isEnglish ? 'Open' : 'Offen') }}</dd></div>
<div><dt>Cooldown</dt><dd>{{ $cooldownMinutes > 0 ? $cooldownMinutes.' '.($isEnglish ? 'minutes' : 'Minuten') : ($isEnglish ? 'None' : 'Keiner') }}</dd></div>
<div><dt>{{ $isEnglish ? 'Proof' : 'Nachweis' }}</dt><dd>Screenshot</dd></div>
<div><dt>{{ $isEnglish ? 'Review' : 'Prüfung' }}</dt><dd>{{ $verificationLabel }}</dd></div>
<div><dt>{{ $isEnglish ? 'Open reviews' : 'Offene Prüfungen' }}</dt><dd>{{ $pendingSubmissionCount }}</dd></div>
</dl>
</details>
<details>
<summary>{{ $isEnglish ? 'Organizer' : 'Veranstalter' }} <svg><use href="#i-chevron"></use></svg></summary>
<div class="cup-organizer">
<img alt="{{ $cup->owner?->name ?: 'HNT.ROCKS' }}" src="{{ $cup->owner?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"/>
<div><strong>{{ $cup->owner?->name ?: 'HNT.ROCKS Team' }}</strong><span>{{ $cup->owner?->username ? '@'.$cup->owner->username.' · ' : '' }}{{ $isEnglish ? 'Cup organizer' : 'Cup-Leitung' }}</span></div>
<button data-toast="{{ $isEnglish ? 'Organizer profile opened' : 'Profil der Cup-Leitung geöffnet' }}" type="button">
<svg><use href="#i-comment"></use></svg>
</button>
</div>
</details>
<details>
<summary>{{ $isEnglish ? 'Participation requirements' : 'Teilnahmebedingungen' }} <svg><use href="#i-chevron"></use></svg></summary>
<p class="cup-data-note">{{ $participationRequirements !== [] ? implode(' · ', $participationRequirements) : ($isEnglish ? 'No additional participation requirements.' : 'Keine zusätzlichen Teilnahmebedingungen.') }}</p>
</details>
</section></aside>
