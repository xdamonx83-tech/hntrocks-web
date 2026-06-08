@extends('admin.layouts.app')

@section('title', 'Navigation · Admin')

@section('admin_heading', 'Navigation')

@section('content')
@php($mobileItems = $mobileItems ?? [])
<section class="hh-page-header">
    <div>
        <p class="hh-kicker">Admin · Navigation</p>
        <h1>Navigation</h1>
        <p>Aktiviere, deaktiviere und sortiere die Menüpunkte der linken hnt.rocks-Sidebar, des Desktop-Headers und der mobilen Bottom-Bar. Eigene Links können als interne Pfade oder externe URLs ergänzt werden.</p>
    </div>
    <div class="hh-page-header-meta">
        <strong>{{ count($items) + count($mobileItems) }}</strong>
        <span>Menüpunkte</span>
    </div>
</section>

@if(session('status'))
    <div class="hh-alert hh-alert-success">{{ session('status') }}</div>
@endif

@if($errors->any())
    <div class="hh-alert hh-alert-danger">
        {{ $errors->first() }}
    </div>
@endif

<section class="hh-card hh-card-compact">
    <div class="hh-card-title-row">
        <div>
            <h2>Linke Sidebar & Desktop-Header</h2>
            <p class="hh-muted">Systempunkte nutzen echte Laravel-Routen. Die wichtigsten Hauptmenüpunkte steuern zusätzlich die Desktop-Header-Navigation.</p>
        </div>
        <button class="hh-primary-button" type="submit" form="hh-admin-navigation-form">Speichern</button>
    </div>

    <form id="hh-admin-navigation-form" method="post" action="{{ route('admin.navigation.update') }}">
        @csrf
        <div class="hh-admin-table hh-admin-navigation-table">
            <table>
                <thead>
                    <tr>
                        <th>Aktiv</th>
                        <th>Menüpunkt</th>
                        <th>Icon</th>
                        <th>Reihenfolge</th>
                        <th>Bereich</th>
                        <th>Admin</th>
                        <th>Quelle</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td>
                                <label class="hh-admin-menu-check">
                                    <input type="checkbox" name="enabled[]" value="{{ $item->id }}" @checked($item->is_enabled)>
                                    <span>aktiv</span>
                                </label>
                            </td>
                            <td>
                                @if($item->is_custom)
                                    <input class="hh-admin-menu-input" type="text" name="items[{{ $item->id }}][label]" value="{{ $item->label }}" maxlength="80" required>
                                    <input class="hh-admin-menu-input hh-admin-menu-input-url" type="text" name="items[{{ $item->id }}][url]" value="{{ $item->url }}" maxlength="2048" required>
                                @else
                                    <strong>{{ $item->displayLabel() }}</strong>
                                    <span>{{ $item->route_name }}</span>
                                @endif
                            </td>
                            <td>
                                <input class="hh-admin-menu-input hh-admin-menu-icon-input" type="text" name="items[{{ $item->id }}][phosphor_icon]" value="{{ $item->phosphor_icon }}" maxlength="80">
                                <span><i class="ph ph-{{ $item->phosphor_icon ?: 'circle' }}" aria-hidden="true"></i> Phosphor</span>
                            </td>
                            <td>
                                <input class="hh-admin-menu-input hh-admin-menu-order-input" type="number" name="items[{{ $item->id }}][sort_order]" value="{{ $item->sort_order }}" min="0" max="9999">
                            </td>
                            <td>
                                <select class="hh-admin-menu-input" name="items[{{ $item->id }}][section]">
                                    <option value="main" @selected($item->section === 'main')>Hauptmenü</option>
                                    <option value="account" @selected($item->section === 'account')>Sekundär</option>
                                </select>
                            </td>
                            <td>
                                <label class="hh-admin-menu-check">
                                    <input type="checkbox" name="admin_only[]" value="{{ $item->id }}" @checked($item->admin_only)>
                                    <span>nur Admin</span>
                                </label>
                            </td>
                            <td>
                                @if($item->is_custom)
                                    <label class="hh-admin-menu-check hh-admin-menu-delete">
                                        <input type="checkbox" name="items[{{ $item->id }}][delete]" value="1">
                                        <span>löschen</span>
                                    </label>
                                @else
                                    <strong>System</strong>
                                    <span>{{ $item->menu_key }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </form>
</section>

<section class="hh-card hh-card-compact hh-section-space">
    <div class="hh-card-title-row">
        <div>
            <h2>Eigenen Sidebar-Menüpunkt hinzufügen</h2>
            <p class="hh-muted">Für interne Seiten reicht ein Pfad wie <code>/cups</code>. Externe Links müssen mit <code>https://</code> beginnen.</p>
        </div>
    </div>

    <form class="hh-admin-menu-create-form" method="post" action="{{ route('admin.navigation.store') }}">
        @csrf
        <div class="hh-admin-menu-field">
            <label for="new-menu-label">Label</label>
            <input id="new-menu-label" type="text" name="label" maxlength="80" required placeholder="z. B. Wiki">
        </div>
        <div class="hh-admin-menu-field">
            <label for="new-menu-url">URL / Pfad</label>
            <input id="new-menu-url" type="text" name="url" maxlength="2048" required placeholder="/wiki oder https://example.com">
        </div>
        <div class="hh-admin-menu-field">
            <label for="new-menu-icon">Phosphor Icon</label>
            <input id="new-menu-icon" type="text" name="phosphor_icon" maxlength="80" placeholder="link">
        </div>
        <div class="hh-admin-menu-field">
            <label for="new-menu-order">Reihenfolge</label>
            <input id="new-menu-order" type="number" name="sort_order" min="0" max="9999" value="900">
        </div>
        <div class="hh-admin-menu-field">
            <label for="new-menu-section">Bereich</label>
            <select id="new-menu-section" name="section">
                <option value="main">Hauptmenü</option>
                <option value="account">Sekundär</option>
            </select>
        </div>
        <label class="hh-admin-menu-check hh-admin-menu-create-check">
            <input type="checkbox" name="is_enabled" value="1" checked>
            <span>aktiv</span>
        </label>
        <label class="hh-admin-menu-check hh-admin-menu-create-check">
            <input type="checkbox" name="admin_only" value="1">
            <span>nur Admin</span>
        </label>
        <button class="hh-primary-button" type="submit">Hinzufügen</button>
    </form>
</section>

<section class="hh-card hh-card-compact hh-section-space">
    <div class="hh-card-title-row">
        <div>
            <h2>Mobile Bottom-Bar</h2>
            <p class="hh-muted">Konfiguration für die feste mobile Leiste. Der Punkt „Profil“ ist ein Spezialpunkt und öffnet das mobile Profil-Sheet statt eine Seite direkt aufzurufen.</p>
        </div>
        <button class="hh-primary-button" type="submit" form="hh-admin-mobile-navigation-form">Speichern</button>
    </div>

    <form id="hh-admin-mobile-navigation-form" method="post" action="{{ route('admin.navigation.mobile.update') }}">
        @csrf
        <div class="hh-admin-table hh-admin-navigation-table hh-admin-mobile-navigation-table">
            <table>
                <thead>
                    <tr>
                        <th>Aktiv</th>
                        <th>Menüpunkt</th>
                        <th>Icon</th>
                        <th>Reihenfolge</th>
                        <th>Admin</th>
                        <th>Quelle</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mobileItems as $item)
                        <tr>
                            <td>
                                <label class="hh-admin-menu-check">
                                    <input type="checkbox" name="mobile_enabled[]" value="{{ $item->id }}" @checked($item->is_enabled)>
                                    <span>aktiv</span>
                                </label>
                            </td>
                            <td>
                                @if($item->is_custom)
                                    <input class="hh-admin-menu-input" type="text" name="mobile_items[{{ $item->id }}][label]" value="{{ $item->label }}" maxlength="80" required>
                                    <input class="hh-admin-menu-input hh-admin-menu-input-url" type="text" name="mobile_items[{{ $item->id }}][url]" value="{{ $item->url }}" maxlength="2048" required>
                                @else
                                    <strong>{{ $item->displayLabel() }}</strong>
                                    <span>{{ $item->action === \App\Services\Navigation\MobileNavService::PROFILE_SHEET_ACTION ? 'Profil-Sheet' : $item->route_name }}</span>
                                @endif
                            </td>
                            <td>
                                <input class="hh-admin-menu-input hh-admin-menu-icon-input" type="text" name="mobile_items[{{ $item->id }}][phosphor_icon]" value="{{ $item->phosphor_icon }}" maxlength="80">
                                <span><i class="ph ph-{{ $item->phosphor_icon ?: 'circle' }}" aria-hidden="true"></i> Phosphor</span>
                            </td>
                            <td>
                                <input class="hh-admin-menu-input hh-admin-menu-order-input" type="number" name="mobile_items[{{ $item->id }}][sort_order]" value="{{ $item->sort_order }}" min="0" max="9999">
                            </td>
                            <td>
                                <label class="hh-admin-menu-check">
                                    <input type="checkbox" name="mobile_admin_only[]" value="{{ $item->id }}" @checked($item->admin_only)>
                                    <span>nur Admin</span>
                                </label>
                            </td>
                            <td>
                                @if($item->is_custom)
                                    <label class="hh-admin-menu-check hh-admin-menu-delete">
                                        <input type="checkbox" name="mobile_items[{{ $item->id }}][delete]" value="1">
                                        <span>löschen</span>
                                    </label>
                                @else
                                    <strong>{{ $item->action === \App\Services\Navigation\MobileNavService::PROFILE_SHEET_ACTION ? 'Spezial' : 'System' }}</strong>
                                    <span>{{ $item->menu_key }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <strong>Mobile Navigation noch nicht initialisiert.</strong>
                                <span>Bitte Migration ausführen. Bis dahin nutzt die Website automatisch Feed, LFG, Teams und Profil als Fallback.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>
</section>

<section class="hh-card hh-card-compact hh-section-space">
    <div class="hh-card-title-row">
        <div>
            <h2>Eigenen Mobile-Menüpunkt hinzufügen</h2>
            <p class="hh-muted">Für die mobile Leiste bitte sparsam nutzen. Mehr als vier bis fünf Punkte werden auf kleinen Displays schnell eng.</p>
        </div>
    </div>

    <form class="hh-admin-menu-create-form hh-admin-mobile-menu-create-form" method="post" action="{{ route('admin.navigation.mobile.store') }}">
        @csrf
        <div class="hh-admin-menu-field">
            <label for="new-mobile-menu-label">Label</label>
            <input id="new-mobile-menu-label" type="text" name="label" maxlength="80" required placeholder="z. B. Cups">
        </div>
        <div class="hh-admin-menu-field">
            <label for="new-mobile-menu-url">URL / Pfad</label>
            <input id="new-mobile-menu-url" type="text" name="url" maxlength="2048" required placeholder="/cups oder https://example.com">
        </div>
        <div class="hh-admin-menu-field">
            <label for="new-mobile-menu-icon">Phosphor Icon</label>
            <input id="new-mobile-menu-icon" type="text" name="phosphor_icon" maxlength="80" placeholder="trophy">
        </div>
        <div class="hh-admin-menu-field">
            <label for="new-mobile-menu-order">Reihenfolge</label>
            <input id="new-mobile-menu-order" type="number" name="sort_order" min="0" max="9999" value="900">
        </div>
        <label class="hh-admin-menu-check hh-admin-menu-create-check">
            <input type="checkbox" name="is_enabled" value="1" checked>
            <span>aktiv</span>
        </label>
        <label class="hh-admin-menu-check hh-admin-menu-create-check">
            <input type="checkbox" name="admin_only" value="1">
            <span>nur Admin</span>
        </label>
        <button class="hh-primary-button" type="submit">Hinzufügen</button>
    </form>
</section>
@endsection
