@php
    $isEnglish = app()->getLocale() === 'en';
    $firstPrize = trim((string) $cup->localizedContentSetting('prizes.first', ''));
    $secondPrize = trim((string) $cup->localizedContentSetting('prizes.second', ''));
    $thirdPrize = trim((string) $cup->localizedContentSetting('prizes.third', ''));
    $prizeNote = trim((string) $cup->localizedContentSetting('prize_note', ''));
    $cashoutNote = trim((string) $cup->localizedContentSetting('cashout_note', ''));
    $hallOfFameNote = trim((string) $cup->localizedContentSetting('hall_of_fame_note', ''));
    $hasAnyPrize = $firstPrize !== '' || $secondPrize !== '' || $thirdPrize !== '' || $prizeNote !== '' || $hallOfFameNote !== '';
@endphp

<section class="cup-tab-panel" data-cup-panel="prizes" hidden tabindex="0">
    @if($hasAnyPrize)
        <div class="cup-prizes-layout">
            <article class="cup-prize-feature">
                <div class="cup-prize-feature-top">
                    <span class="cup-prize-rank">01</span>
                    <span class="cup-prize-label">{{ $isEnglish ? 'WINNER' : 'GEWINNER' }}</span>
                </div>
                <div class="cup-prize-feature-copy">
                    <span>{{ $isEnglish ? 'FIRST PRIZE' : 'HAUPTPREIS' }}</span>
                    <strong>{{ $firstPrize !== '' ? $firstPrize : ($isEnglish ? 'Not specified yet' : 'Noch nicht festgelegt') }}</strong>
                    <p>{{ $prizeNote !== '' ? $prizeNote : ($isEnglish ? 'The confirmed first place receives this prize.' : 'Der bestätigte erste Platz erhält diesen Preis.') }}</p>
                </div>
                <div class="cup-prize-feature-bottom">
                    <span>{{ $cup->modeLabel() }}</span>
                    <span>{{ $cup->statusLabel() }}</span>
                </div>
            </article>

            <div class="cup-prize-secondary-grid">
                <article class="cup-prize-secondary rocks">
                    <div class="cup-prize-medal">2</div>
                    <div>
                        <span>{{ $isEnglish ? 'SECOND PLACE' : 'ZWEITER PLATZ' }}</span>
                        <strong>{{ $secondPrize !== '' ? $secondPrize : ($isEnglish ? 'No prize specified' : 'Kein Preis hinterlegt') }}</strong>
                        <p>{{ $isEnglish ? 'Awarded after the final review.' : 'Vergabe nach der abschließenden Prüfung.' }}</p>
                    </div>
                </article>

                <article class="cup-prize-secondary badge">
                    <div class="cup-prize-medal">3</div>
                    <div>
                        <span>{{ $isEnglish ? 'THIRD PLACE' : 'DRITTER PLATZ' }}</span>
                        <strong>{{ $thirdPrize !== '' ? $thirdPrize : ($isEnglish ? 'No prize specified' : 'Kein Preis hinterlegt') }}</strong>
                        <p>{{ $isEnglish ? 'Only valid scored submissions count.' : 'Es zählen nur gültige gewertete Einreichungen.' }}</p>
                    </div>
                </article>

                <article class="cup-prize-secondary random">
                    <div class="cup-prize-medal">i</div>
                    <div>
                        <span>{{ $isEnglish ? 'PRIZE NOTE' : 'PREISHINWEIS' }}</span>
                        <strong>{{ $prizeNote !== '' ? $prizeNote : ($isEnglish ? 'No additional note' : 'Kein zusätzlicher Hinweis') }}</strong>
                        <p>{{ $isEnglish ? 'Details published by the cup organizer.' : 'Angaben des Cup-Veranstalters.' }}</p>
                    </div>
                </article>

                <article class="cup-prize-secondary fame">
                    <div class="cup-prize-medal">H</div>
                    <div>
                        <span>COMMUNITY</span>
                        <strong>Hall of Fame</strong>
                        <p>{{ $hallOfFameNote !== '' ? $hallOfFameNote : ($isEnglish ? 'Finished cups may be archived in the Hall of Fame.' : 'Abgeschlossene Cups können in der Hall of Fame verewigt werden.') }}</p>
                    </div>
                </article>
            </div>
        </div>

        <div class="cup-prize-footer">
            <span>{{ $cashoutNote !== '' ? $cashoutNote : ($isEnglish ? 'Distribution after final confirmation' : 'Vergabe nach abschließender Bestätigung') }}</span>
            <span>{{ $isEnglish ? 'Only confirmed and valid submissions count' : 'Nur bestätigte und gültige Einreichungen zählen' }}</span>
        </div>
    @else
        <div class="cup-prize-empty">
            <strong>{{ $isEnglish ? 'No prizes have been published yet.' : 'Für diesen Cup wurden noch keine Preise veröffentlicht.' }}</strong>
        </div>
    @endif
</section>
