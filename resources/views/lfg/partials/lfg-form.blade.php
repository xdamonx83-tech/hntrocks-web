<div>
    <label for="title">Titel</label>
    <input id="title" name="title" type="text" value="{{ old('title', $post?->title) }}" maxlength="120" required placeholder="z. B. Suche Duo für Bounty Hunt">
</div>

<label for="body">Beschreibung</label>
<textarea id="body" name="body" rows="6" maxlength="2800" data-hh-mention-context="lfg" placeholder="Wann spielst du? Was suchst du? Wie ernst soll die Runde sein? Nutze @, um Freunde zu erwähnen.">{{ old('body', $post?->body) }}</textarea>

<div class="hh-form-grid">
    <div>
        <label for="platform">Plattform</label>
        <select id="platform" name="platform">
            <option value="">Keine Angabe</option>
            @foreach (['PC', 'PlayStation', 'Xbox', 'Crossplay'] as $option)
                <option value="{{ $option }}" @selected(old('platform', $post?->platform) === $option)>{{ $option }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="playstyle">Spielstil</label>
        <select id="playstyle" name="playstyle">
            <option value="">Keine Angabe</option>
            @foreach (['Entspannt', 'Taktisch', 'Aggressiv', 'Competitive', 'Einsteigerfreundlich'] as $option)
                <option value="{{ $option }}" @selected(old('playstyle', $post?->playstyle) === $option)>{{ $option }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="region">Region</label>
        <select id="region" name="region">
            <option value="">Keine Angabe</option>
            @foreach (['EU', 'US East', 'US West', 'Asia', 'Oceania'] as $option)
                <option value="{{ $option }}" @selected(old('region', $post?->region) === $option)>{{ $option }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="language">Sprache</label>
        <select id="language" name="language">
            <option value="">Keine Angabe</option>
            @foreach (['Deutsch', 'Englisch', 'Deutsch / Englisch', 'Mehrsprachig'] as $option)
                <option value="{{ $option }}" @selected(old('language', $post?->language) === $option)>{{ $option }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="preferred_time">Spielzeit</label>
        <select id="preferred_time" name="preferred_time">
            <option value="">Keine Angabe</option>
            @foreach (['Morgens', 'Mittags', 'Abends', 'Nachts', 'Wochenende', 'Flexibel'] as $option)
                <option value="{{ $option }}" @selected(old('preferred_time', $post?->preferred_time) === $option)>{{ $option }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="experience_level">Erfahrung</label>
        <select id="experience_level" name="experience_level">
            <option value="">Keine Angabe</option>
            @foreach (['Einsteiger', 'Fortgeschritten', 'Erfahren', 'Competitive', 'Egal'] as $option)
                <option value="{{ $option }}" @selected(old('experience_level', $post?->experience_level) === $option)>{{ $option }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="slots_total">Teamgröße</label>
        <select id="slots_total" name="slots_total" required>
            @foreach ([2, 3, 4] as $option)
                <option value="{{ $option }}" @selected((int) old('slots_total', $post?->slots_total ?? 2) === $option)>{{ $option }} Spieler</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="visibility">Sichtbarkeit</label>
        <select id="visibility" name="visibility" required>
            <option value="public" @selected(old('visibility', $post?->visibility ?? 'public') === 'public')>Öffentlich</option>
            <option value="private" @selected(old('visibility', $post?->visibility) === 'private')>Privat</option>
        </select>
    </div>
    @if ($isEdit)
        <div>
            <label for="slots_filled">Besetzte Slots</label>
            <input id="slots_filled" name="slots_filled" type="number" min="1" max="4" value="{{ old('slots_filled', $post?->slots_filled ?? 1) }}" required>
        </div>
        <div>
            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="open" @selected(old('status', $post?->status) === 'open')>Offen</option>
                <option value="full" @selected(old('status', $post?->status) === 'full')>Voll</option>
                <option value="closed" @selected(old('status', $post?->status) === 'closed')>Geschlossen</option>
            </select>
        </div>
    @endif
</div>

<label class="hh-checkline hh-checkline-card">
    <input type="checkbox" name="voice_required" value="1" @checked(old('voice_required', $post?->voice_required ?? false))>
    Voice ist gewünscht oder erforderlich
</label>
