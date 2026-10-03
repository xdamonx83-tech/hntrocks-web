@extends('admin.layouts.app')

@section('title', 'Marker importieren · HNT Maps Admin')
@section('admin_heading', 'HNT Maps')

@section('content')
    @php
        $selectedProviderId = $result['provider'] ?? request('provider', array_key_first($providers));
        $selectedProviderId = array_key_exists($selectedProviderId, $providers) ? $selectedProviderId : array_key_first($providers);
        $selectedProvider = $providers[$selectedProviderId];
        $selectedMaps = request()->input('maps', array_keys($selectedProvider->maps()));
        $selectedCategories = request()->input('categories', array_merge(array_keys($selectedProvider->categories()), ['tower', 'wild_target:rotjaw', 'wild_target:hellborn']));
        $categoryGroups = collect($selectedProvider->categories())->groupBy('type', true);
    @endphp

    <section class="hh-page-header">
        <div>
            <p class="hh-kicker"><a href="{{ route('admin.maps.index') }}">← Zurück zu Admin Maps</a></p>
            <h1>Marker importieren</h1>
            <p>Strukturierte Marker von einer externen Quelle prüfen und nach gesonderter Bestätigung importieren.</p>
        </div>
    </section>

    @if($error)
        <div class="hh-alert hh-alert-danger hh-section-space" role="alert">{{ $error }}</div>
    @endif

    <form class="hh-card hh-section-space" method="post" action="{{ route('admin.maps.import.preview') }}">
        @csrf
        <div class="hh-card-title-row"><h2>Quelle und Auswahl</h2></div>
        <div class="hh-section-space">
            <label for="marker-import-provider"><strong>Quelle</strong></label><br>
            <select id="marker-import-provider" name="provider" style="min-width:260px">
                @foreach($providers as $providerId => $provider)
                    <option value="{{ $providerId }}" @selected($providerId === $selectedProviderId)>{{ $provider->name() }}</option>
                @endforeach
            </select>
            <p class="hh-muted">Die Daten stammen von der genannten externen Quelle. Bilder und Beschreibungen werden nicht übernommen.</p>
        </div>

        <fieldset class="hh-section-space" data-import-select-group>
            <legend><strong>Maps</strong></legend>
            <div class="hh-admin-map-import-grid">
                @foreach($selectedProvider->maps() as $slug => $map)
                    <label><input type="checkbox" name="maps[]" value="{{ $slug }}" @checked(in_array($slug, $selectedMaps, true))> {{ $map['name'] }}</label>
                @endforeach
            </div>
            <button type="button" class="hh-secondary-button" data-import-select-all>Alle auswählen</button>
        </fieldset>

        <fieldset class="hh-section-space" data-import-select-group>
            <legend><strong>Marker-Kategorien</strong></legend>
            <div class="hh-admin-map-import-grid">
                @foreach($categoryGroups as $type => $entries)
                    @php $definition = $registry[$type]; @endphp
                    <div class="hh-admin-map-import-category">
                        <label><input type="checkbox" name="categories[]" value="{{ $type }}" @if(in_array($type, ['wild_target', 'tower'], true)) data-import-parent="{{ $type }}" @endif @checked(in_array($type, $selectedCategories, true))> <strong>{{ $definition['label_de'] }}</strong></label>
                        @if($type === 'wild_target')
                            <div class="hh-admin-map-import-subtypes">
                                @foreach($definition['subtypes'] as $subtype => $labels)
                                    <label><input type="checkbox" name="categories[]" value="{{ $type.':'.$subtype }}" data-import-child="{{ $type }}" @checked(in_array($type.':'.$subtype, $selectedCategories, true))> {{ $labels['de'] }}</label>
                                @endforeach
                            </div>
                            <small class="hh-muted">Einträge ohne eindeutigen Typ erscheinen separat als „nicht klassifiziert“.</small>
                        @elseif($type === 'tower')
                            <div class="hh-admin-map-import-subtypes">
                                @foreach($entries as $entryKey => $entry)
                                    <label><input type="checkbox" name="categories[]" value="{{ $entryKey }}" data-import-child="{{ $type }}" @checked(in_array($entryKey, $selectedCategories, true))> {{ $definition['subtypes'][$entry['subtype']]['de'] }}</label>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            <button type="button" class="hh-secondary-button" data-import-select-all>Alle auswählen</button>
        </fieldset>

        <fieldset class="hh-section-space">
            <legend><strong>Importmodus</strong></legend>
            <label><input type="radio" name="mode" value="sync" @checked(request('mode', 'sync') === 'sync')> Synchronisieren</label><br>
            <label><input type="radio" name="mode" value="add_only" @checked(request('mode') === 'add_only')> Nur neue Marker hinzufügen</label>
            <p class="hh-muted">Die Vorschau verändert keine Marker. Ein Import erfordert anschließend eine eigene Bestätigung.</p>
            <p class="hh-muted">„Nur neue Marker hinzufügen“ würde bestehende Marker auch dann auslassen, wenn die Vorschau sie als geändert erkennt.</p>
        </fieldset>

        @if($errors->any())
            <div class="hh-alert hh-alert-danger" role="alert">Bitte Quelle, mindestens eine Map und mindestens eine Kategorie auswählen.</div>
        @endif
        <button class="hh-primary-button hh-section-space" type="submit">Vorschau erstellen</button>
    </form>

    @if($result)
        <section class="hh-card hh-section-space" aria-labelledby="import-preview-title">
            <div class="hh-card-title-row"><div><h2 id="import-preview-title">Import-Vorschau</h2><p class="hh-muted">{{ $result['provider_name'] }} · {{ $result['mode'] === 'sync' ? 'Synchronisieren' : 'Nur neue Marker hinzufügen' }}</p></div></div>
            @unless($result['identity_columns_ready'])
                <div class="hh-alert">Die vorbereitende Migration ist in dieser Datenbank noch nicht angewendet. Vorhandene externe Marker können daher noch nicht anhand ihrer Source-ID abgeglichen werden.</div>
            @endunless
            @foreach($result['maps'] as $slug => $mapResult)
                <div class="hh-section-space">
                    <h3>{{ $mapResult['name'] }}</h3>
                    <div class="hh-admin-map-import-table-wrap">
                        <table class="hh-admin-map-import-table">
                            <thead><tr><th>Kategorie</th><th>Neu</th><th>Geändert</th><th>Unverändert</th><th>Extern entfernt</th><th>Geschützt</th><th>Nicht klassifiziert</th><th>Außerhalb Karte</th></tr></thead>
                            <tbody>
                                @foreach($mapResult['categories'] as $category => $counts)
                                    @php
                                        [$type, $subtype] = array_pad(explode(':', $category, 2), 2, null);
                                        $categoryLabel = $registry[$type]['label_de'] ?? $type;
                                        $subtypeLabel = $subtype === 'unclassified' ? 'Nicht klassifiziert' : ($registry[$type]['subtypes'][$subtype]['de'] ?? $subtype);
                                    @endphp
                                    <tr><th>{{ $categoryLabel }}{{ $subtype ? ' · '.$subtypeLabel : '' }}</th><td>+ {{ $counts['new'] }}</td><td>~ {{ $counts['changed'] }}</td><td>= {{ $counts['unchanged'] }}</td><td>- {{ $counts['removed_external'] }}</td><td>{{ $counts['protected'] }}</td><td>{{ $counts['unclassified'] ? '! '.$counts['unclassified'] : '0' }}</td><td>{{ $counts['out_of_bounds'] ? '! '.$counts['out_of_bounds'] : '0' }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="hh-muted">Bei einem späteren Replace wären folgende bestehende Legacy-HNT-Marker betroffen: tower {{ $mapResult['legacy']['tower'] }}, bugs {{ $mapResult['legacy']['bugs'] }}, wild {{ $mapResult['legacy']['wild'] }}. Hier erfolgt kein Replace.</p>
                    @if(! empty($mapResult['examples']))
                        <details><summary>Beispielpositionen (HNT X/Y)</summary>
                            <ul>
                                @foreach($mapResult['examples'] as $example)
                                    <li>{{ $example['category'] }} · {{ $example['source_key'] }} · X {{ $example['x'] }} / Y {{ $example['y'] }}</li>
                                @endforeach
                            </ul>
                        </details>
                    @endif
                </div>
            @endforeach
            <h3>Gesamt</h3>
            <p>Neu: {{ $result['total']['new'] }} · Geändert: {{ $result['total']['changed'] }} · Unverändert: {{ $result['total']['unchanged'] }} · Extern nicht mehr vorhanden: {{ $result['total']['removed_external'] }} · Geschützt: {{ $result['total']['protected'] }} · Nicht eindeutig klassifiziert: {{ $result['total']['unclassified'] }} · Außerhalb der Karte übersprungen: {{ $result['total']['out_of_bounds'] }}</p>
        </section>
        <section class="hh-card hh-section-space" aria-labelledby="import-prepare-title">
            <h2 id="import-prepare-title">Import vorbereiten</h2>
            <p><strong>Quelle:</strong> {{ $result['provider_name'] }}<br>
                <strong>Maps:</strong> {{ implode(', ', array_map(fn ($slug) => $result['maps'][$slug]['name'], array_keys($result['maps']))) }}<br>
                <strong>Kategorien:</strong> {{ implode(', ', request('categories', [])) }}<br>
                <strong>Modus:</strong> {{ $result['mode'] === 'sync' ? 'Synchronisieren' : 'Nur neue Marker hinzufügen' }}</p>
            <p>Neu: {{ $result['total']['new'] }} · Geändert: {{ $result['total']['changed'] }} · Nicht klassifiziert: {{ $result['total']['unclassified'] }} · Außerhalb Karte: {{ $result['total']['out_of_bounds'] }}</p>
            @if($result['identity_columns_ready'])
                <form method="post" action="{{ route('admin.maps.import.execute') }}">
                    @csrf
                    <p><label><input type="checkbox" name="reviewed" value="1" required> Ich habe die Import-Vorschau geprüft.</label></p>
                    <p><label for="import-confirmation">Zur Bestätigung exakt <strong>IMPORT</strong> eingeben:</label><br>
                        <input id="import-confirmation" type="text" name="confirmation" required autocomplete="off"></p>
                    <fieldset class="hh-section-space">
                        <legend><strong>Legacy-Marker ersetzen (separate, optionale Freigabe)</strong></legend>
                        <p class="hh-muted">Standardmäßig aus. Nur Legacy-Marker der gewählten Maps und Klassen ohne Source-Provider werden nach erfolgreichem Import entfernt.</p>
                        @foreach(['tower' => 'Alte HNT-Türme', 'bugs' => 'Alte HNT-Käfer', 'wild' => 'Alte HNT-Wildziele'] as $legacyType => $legacyLabel)
                            @php
                                $targetType = ['tower' => 'tower', 'bugs' => 'beetle', 'wild' => 'wild_target'][$legacyType];
                                $newCount = collect($result['maps'])->sum(fn ($map) => collect($map['categories'])->filter(fn ($counts, $key) => $key === $targetType || str_starts_with($key, $targetType.':'))->sum('new'));
                                $oldCount = collect($result['maps'])->sum(fn ($map) => $map['legacy'][$legacyType]);
                            @endphp
                            <label class="hh-admin-map-import-replace"><input type="checkbox" name="replace_legacy[]" value="{{ $legacyType }}"> {{ $legacyLabel }} ersetzen · bestehend: {{ $oldCount }} · neu: {{ $newCount }}</label>
                        @endforeach
                        <p><label><input type="checkbox" name="replace_reviewed" value="1"> Ich bestätige das gesonderte Legacy-Replacement.</label></p>
                        <p><label for="replace-confirmation">Für Legacy-Replacement exakt <strong>ERSETZEN</strong> eingeben:</label><br>
                            <input id="replace-confirmation" type="text" name="replace_confirmation" autocomplete="off"></p>
                    </fieldset>
                    <button class="hh-primary-button" type="submit">Marker importieren</button>
                </form>
            @else
                <p class="hh-alert">Import gesperrt: Die vorbereitende Migration ist in dieser Datenbank noch nicht angewendet.</p>
            @endif
        </section>
    @endif
    @if($executed)
        <section class="hh-card hh-section-space" role="status">
            <h2>Markerimport abgeschlossen</h2>
            <p>Neu: {{ $executed['total']['created'] }} · Aktualisiert: {{ $executed['total']['updated'] }} · Unverändert: {{ $executed['total']['unchanged'] }} · Nur-hinzufügen übersprungen: {{ $executed['total']['skipped_add_only'] }} · Extern nicht mehr vorhanden: {{ $executed['total']['external_missing'] }} · Geschützt: {{ $executed['total']['protected'] }} · Nicht klassifiziert: {{ $executed['total']['unclassified'] }} · Außerhalb Karte: {{ $executed['total']['out_of_bounds'] }}</p>
            @foreach($executed['maps'] as $slug => $map)
                <p><strong>{{ $map['name'] }}:</strong> @foreach($map['legacy_deleted'] as $type => $count) {{ $type }}: {{ $count }} Legacy-Marker ersetzt. @endforeach</p>
            @endforeach
        </section>
    @endif
@endsection

@push('head')
    <style>
        .hh-admin-map-import-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin:14px 0}
        .hh-admin-map-import-category{padding:14px;border:1px solid rgba(255,255,255,.12);border-radius:10px}
        .hh-admin-map-import-subtypes{display:grid;gap:8px;margin:10px 0 0 20px}
        .hh-admin-map-import-table-wrap{overflow-x:auto}
        .hh-admin-map-import-table{width:100%;border-collapse:collapse;text-align:left}
        .hh-admin-map-import-table th,.hh-admin-map-import-table td{padding:9px 12px;border-bottom:1px solid rgba(255,255,255,.12);white-space:nowrap}
        .hh-admin-map-import-replace{display:block;margin:10px 0}
    </style>
@endpush

@push('scripts')
    <script>
        document.querySelectorAll('[data-import-select-all]').forEach(function (button) {
            button.addEventListener('click', function () {
                button.closest('[data-import-select-group]').querySelectorAll('input[type="checkbox"]').forEach(function (checkbox) { checkbox.checked = true; });
            });
        });
        document.querySelectorAll('[data-import-parent]').forEach(function (parent) {
            var children = Array.from(document.querySelectorAll('[data-import-child="' + parent.dataset.importParent + '"]'));
            parent.addEventListener('change', function () { children.forEach(function (child) { child.checked = parent.checked; }); });
            children.forEach(function (child) {
                child.addEventListener('change', function () { parent.checked = children.every(function (item) { return item.checked; }); });
            });
        });
        document.getElementById('marker-import-provider')?.addEventListener('change', function (event) {
            window.location.assign('{{ route('admin.maps.import.index') }}?provider=' + encodeURIComponent(event.target.value));
        });
    </script>
@endpush
