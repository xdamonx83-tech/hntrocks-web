@php
    $ruleCards = collect();

    if ($requiresExtraction) {
        $ruleCards->push([
            'title' => $isEnglish ? 'Successful extraction' : 'Erfolgreiche Extraktion',
            'text' => $isEnglish ? 'Only runs with a successful extraction can receive points.' : 'Nur Runden mit erfolgreicher Extraktion können gewertet werden.',
        ]);
    }

    if ($pointsPerKill > 0) {
        $ruleCards->push([
            'title' => $isEnglish ? 'Hunter kills' : 'Hunter-Kills',
            'text' => $isEnglish ? 'Each confirmed Hunter kill is worth '.$pointsPerKill.' point(s).' : 'Jeder bestätigte Hunter-Kill bringt '.$pointsPerKill.' Punkt(e).',
        ]);
    }

    if ($extractBonus > 0) {
        $ruleCards->push([
            'title' => $isEnglish ? 'Extraction bonus' : 'Extraktionsbonus',
            'text' => $isEnglish ? 'A valid trophy extraction adds '.$extractBonus.' bonus points.' : 'Eine gültige Trophäen-Extraktion bringt '.$extractBonus.' Bonuspunkte.',
        ]);
    }

    $ruleCards->push([
        'title' => $soloCup ? ($isEnglish ? 'Participant submits' : 'Teilnehmer reicht ein') : ($isEnglish ? 'Captain submits' : 'Captain reicht ein'),
        'text' => $soloCup
            ? ($isEnglish ? 'Registered participants submit their own screenshots.' : 'Angemeldete Teilnehmer reichen ihre eigenen Screenshots ein.')
            : ($isEnglish ? 'Only the captain of a complete team can submit screenshots.' : 'Nur der Captain eines vollständigen Teams kann Screenshots einreichen.'),
    ]);

    if ($maxUploadsPerParticipant) {
        $ruleCards->push([
            'title' => $isEnglish ? 'Upload limit' : 'Upload-Limit',
            'text' => $isEnglish ? 'Each participant may upload up to '.$maxUploadsPerParticipant.' screenshots.' : 'Pro Teilnehmer sind maximal '.$maxUploadsPerParticipant.' Screenshot-Uploads erlaubt.',
        ]);
    }

    if ($maxScoredPerParticipant) {
        $ruleCards->push([
            'title' => $isEnglish ? 'Scored runs' : 'Gewertete Runs',
            'text' => $isEnglish ? 'Only the best '.$maxScoredPerParticipant.' valid runs per participant count.' : 'Pro Teilnehmer zählen nur die besten '.$maxScoredPerParticipant.' gültigen Runs.',
        ]);
    }

    if ($platforms !== []) {
        $ruleCards->push([
            'title' => $isEnglish ? 'Allowed platforms' : 'Erlaubte Plattformen',
            'text' => implode(' / ', $platforms),
        ]);
    }

    foreach ($participationRequirements as $requirement) {
        $ruleCards->push([
            'title' => $isEnglish ? 'Participation requirement' : 'Teilnahmebedingung',
            'text' => $requirement,
        ]);
    }

    $ruleCards->push([
        'title' => $isEnglish ? 'Screenshot proof' : 'Screenshot-Nachweis',
        'text' => $isEnglish ? 'The relevant result and match information must be complete and readable.' : 'Das relevante Ergebnis und die Matchdaten müssen vollständig und lesbar sein.',
    ]);

    $ruleCards->push([
        'title' => $isEnglish ? 'Verification' : 'Prüfung',
        'text' => $verificationMode === \App\Support\CupOrganizerAccess::VERIFICATION_AI
            ? ($isEnglish ? 'Submissions are checked automatically and can be reviewed manually.' : 'Einreichungen werden automatisch geprüft und können manuell kontrolliert werden.')
            : ($isEnglish ? 'Every submission is reviewed manually before it reaches the leaderboard.' : 'Jede Einreichung wird vor der Leaderboard-Wertung manuell geprüft.'),
    ]);

    $customRules = trim((string) $cup->displayRules());
    if ($customRules !== '') {
        $ruleCards->push([
            'title' => $isEnglish ? 'Additional cup rules' : 'Zusätzliche Cup-Regeln',
            'text' => $customRules,
        ]);
    }

    $ruleCards = $ruleCards->take(12)->values();
@endphp
<section class="cup-tab-panel" data-cup-panel="rules" hidden="" tabindex="0">
<div class="cup-rule-grid">
@foreach ($ruleCards as $index => $rule)
<article><span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span><div><strong>{{ $rule['title'] }}</strong><p>{!! nl2br(e($rule['text'])) !!}</p></div></article>
@endforeach
</div>
</section>