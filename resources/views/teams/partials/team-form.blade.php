@php
    $isEdit = $mode === 'edit' && $team;
    $useOldInput = $useOldInput ?? true;
    $fieldValue = fn ($field, $default = null) => $useOldInput ? old($field, $default) : $default;
    $modalId = $modalId ?? ($isEdit ? 'hh-team-edit-modal-'.$team->id : 'hh-team-create-modal');
    $formId = $formId ?? $modalId.'-form';
    $isEmbedded = $isEmbedded ?? false;
    $isModalOpen = $open ?? true;
    $modalModeToken = $modalModeToken ?? ($isEdit ? 'edit:'.$team->slug : 'create');
    $teamName = $fieldValue('name', $team?->name ?? __('ui.create_new_team'));
    $teamTagline = $fieldValue('tagline', $team?->tagline ?? __('ui.create_new_team_text'));
    $teamAvatarUrl = $team?->avatarUrl() ?? asset('assets/vikinger/img/default-avatar.svg');
    $teamCoverUrl = $team?->coverUrl() ?? asset('assets/vikinger/img/default-cover.svg');
    $platform = $fieldValue('platform', $team?->platform);
    $playstyle = $fieldValue('playstyle', $team?->playstyle);
    $region = $fieldValue('region', $team?->region);
    $language = $fieldValue('language', $team?->language);
    $visibility = $fieldValue('visibility', $team?->visibility ?? 'public');
    $recruitmentStatus = $fieldValue('recruitment_status', $team?->recruitment_status ?? 'open');
    $modalTitle = $isEdit ? __('ui.team_edit_title') : __('ui.team_create_title');
    $modalSubtitle = $isEdit ? __('ui.team_edit_banner_text') : __('ui.team_create_banner_text');
@endphp

<div id="{{ $modalId }}" class="hh-team-modal-shell {{ $isModalOpen ? 'is-open' : '' }}" data-hh-team-modal-shell data-hh-team-modal-embedded="{{ $isEmbedded ? 'true' : 'false' }}" @unless($isModalOpen) hidden @endunless>
    <div class="hh-team-modal-backdrop" aria-hidden="true" data-hh-team-modal-close="{{ $closeHref }}"></div>

    <div class="popup-box mid popup-manage-group hh-team-modal" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title" data-hh-team-modal-close-url="{{ $closeHref }}">
        <a class="popup-close-button hh-team-modal-close" href="{{ $closeHref }}" aria-label="{{ __('ui.discard_all') }}" data-hh-team-modal-close="{{ $closeHref }}">
            <i class="popup-close-button-icon hh-ph-action-icon ph ph-x" aria-hidden="true"></i>
        </a>

        <div class="popup-box-body">
            <aside class="popup-box-sidebar hh-team-modal-sidebar">
                <div class="user-preview small hh-team-modal-preview-card">
                    <figure class="user-preview-cover liquid hh-team-cover-preview">
                        <img src="{{ $teamCoverUrl }}" alt="{{ $teamName }}" data-hh-team-cover-preview>
                    </figure>

                    <div class="user-preview-info">
                        <div class="user-short-description small">
                            <div class="user-short-description-avatar user-avatar no-stats">
                                <div class="user-avatar-border"><div class="hexagon-100-108"></div></div>
                                <div class="user-avatar-content hh-team-avatar-preview-frame">
                                    <div class="hexagon-image-84-92" data-src="{{ $teamAvatarUrl }}" style="background-image:url('{{ $teamAvatarUrl }}');" data-hh-team-avatar-preview></div>
                                    <img class="hh-team-avatar-preview-img" src="{{ $teamAvatarUrl }}" alt="{{ $teamName }}" data-hh-team-avatar-preview-img>
                                </div>
                            </div>

                            <p class="user-short-description-title small" data-hh-team-preview-name data-empty-text="{{ __('ui.create_new_team') }}">{{ $teamName }}</p>
                            <p class="user-short-description-text regular" data-hh-team-preview-tagline data-empty-text="{{ __('ui.team_default_tagline') }}">{{ $teamTagline }}</p>
                        </div>
                    </div>
                </div>

                <div class="sidebar-menu-item hh-team-modal-tabs">
                    <div class="sidebar-menu-body secondary">
                        <button class="sidebar-menu-link active" type="button" data-hh-team-modal-tab="info">{{ __('ui.team_info') }}</button>
                        <button class="sidebar-menu-link" type="button" data-hh-team-modal-tab="media">{{ __('ui.avatar_and_cover') }}</button>
                        <button class="sidebar-menu-link" type="button" data-hh-team-modal-tab="settings">{{ __('ui.settings') }}</button>
                        <button class="sidebar-menu-link" type="button" data-hh-team-modal-tab="members">{{ __('ui.members') }}</button>
                        @if ($isEdit && $team->isOwner(auth()->user()))
                            <button class="sidebar-menu-link" type="button" data-hh-team-modal-tab="delete">{{ __('ui.delete_team') }}</button>
                        @endif
                    </div>
                </div>

                <div class="popup-box-sidebar-footer hh-team-modal-footer">
                    <button class="button secondary full" type="submit" form="{{ $formId }}">{{ $primaryLabel }}</button>
                    <a class="button white full" href="{{ $closeHref }}" data-hh-team-modal-close="{{ $closeHref }}">{{ $secondaryLabel }}</a>
                </div>
            </aside>

            <main class="popup-box-content hh-team-modal-content">
                <div class="hh-team-modal-heading">
                    <p class="section-pretitle">{{ __('ui.teams') }}</p>
                    <h2 id="{{ $modalId }}-title">{{ $modalTitle }}</h2>
                    <p>{{ $modalSubtitle }}</p>
                </div>

                @if ($useOldInput && $errors->any())
                    <div class="hh-alert hh-alert-danger">{{ __('ui.profile_validation_error') }}</div>
                @endif

                <form id="{{ $formId }}" class="form hh-team-edit-form hh-team-modal-form" method="post" action="{{ $formAction }}" enctype="multipart/form-data">
                    @csrf
                    @if (($formMethod ?? 'POST') !== 'POST')
                        @method($formMethod)
                    @endif
                    <input type="hidden" name="hh_team_modal" value="{{ $modalModeToken }}">

                    <section class="widget-box hh-team-modal-panel is-active" data-hh-team-modal-panel="info">
                        <p class="widget-box-title">{{ __('ui.team_info') }}</p>
                        <div class="widget-box-content">
                            <div class="form-row">
                                <div class="form-item">
                                    <div class="form-input small active">
                                        <label for="{{ $modalId }}-name">{{ __('ui.team_name') }}</label>
                                        <input id="{{ $modalId }}-name" name="name" type="text" value="{{ $fieldValue('name', $team?->name) }}" maxlength="80" required data-hh-team-live="name">
                                    </div>
                                    @if($useOldInput) @error('name')<span class="hh-form-error">{{ $message }}</span>@enderror @endif
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-item">
                                    <div class="form-input small {{ $fieldValue('tagline', $team?->tagline) ? 'active' : '' }}">
                                        <label for="{{ $modalId }}-tagline">{{ __('ui.team_tagline') }}</label>
                                        <input id="{{ $modalId }}-tagline" name="tagline" type="text" value="{{ $fieldValue('tagline', $team?->tagline) }}" maxlength="140" data-hh-team-live="tagline">
                                    </div>
                                    @if($useOldInput) @error('tagline')<span class="hh-form-error">{{ $message }}</span>@enderror @endif
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-item">
                                    <div class="form-input small mid-textarea {{ $fieldValue('description', $team?->description) ? 'active' : '' }}">
                                        <label for="{{ $modalId }}-description">{{ __('ui.team_description') }}</label>
                                        <textarea id="{{ $modalId }}-description" name="description" maxlength="2500" placeholder="{{ __('ui.team_description_placeholder') }}">{{ $fieldValue('description', $team?->description) }}</textarea>
                                    </div>
                                    @if($useOldInput) @error('description')<span class="hh-form-error">{{ $message }}</span>@enderror @endif
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="widget-box hh-team-modal-panel" data-hh-team-modal-panel="media">
                        <p class="widget-box-title">{{ __('ui.avatar_and_cover') }}</p>
                        <div class="widget-box-content">
                            <div class="grid grid-6-6 centered-on-mobile hh-team-modal-upload-grid">
                                <label class="upload-box hh-team-upload-box" for="{{ $modalId }}-avatar">
                                    <i class="upload-box-icon hh-ph-action-icon ph ph-users" aria-hidden="true"></i>
                                    <p class="upload-box-title">{{ __('ui.team_avatar') }}</p>
                                    <p class="upload-box-text">{{ __('ui.team_avatar_hint') }}</p>
                                    <input class="hh-team-file-input" type="file" id="{{ $modalId }}-avatar" name="avatar" accept="image/jpeg,image/png,image/webp" data-hh-team-file="avatar">
                                    @if($useOldInput) @error('avatar')<span class="hh-form-error">{{ $message }}</span>@enderror @endif
                                </label>

                                <label class="upload-box hh-team-upload-box" for="{{ $modalId }}-cover">
                                    <i class="upload-box-icon hh-ph-action-icon ph ph-images" aria-hidden="true"></i>
                                    <p class="upload-box-title">{{ __('ui.team_cover') }}</p>
                                    <p class="upload-box-text">{{ __('ui.team_cover_hint') }}</p>
                                    <input class="hh-team-file-input" type="file" id="{{ $modalId }}-cover" name="cover" accept="image/jpeg,image/png,image/webp" data-hh-team-file="cover">
                                    @if($useOldInput) @error('cover')<span class="hh-form-error">{{ $message }}</span>@enderror @endif
                                </label>
                            </div>
                        </div>
                    </section>

                    <section class="widget-box hh-team-modal-panel" data-hh-team-modal-panel="settings">
                        <p class="widget-box-title">{{ __('ui.team_setup') }}</p>
                        <div class="widget-box-content">
                            <div class="form-row split">
                                <div class="form-item">
                                    <div class="form-select">
                                        <label for="{{ $modalId }}-visibility">{{ __('ui.team_visibility') }}</label>
                                        <select id="{{ $modalId }}-visibility" name="visibility" required>
                                            <option value="public" @selected($visibility === 'public')>{{ __('ui.visibility_public') }}</option>
                                            <option value="private" @selected($visibility === 'private')>{{ __('ui.visibility_private') }}</option>
                                        </select>
                                        <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                                    </div>
                                    @if($useOldInput) @error('visibility')<span class="hh-form-error">{{ $message }}</span>@enderror @endif
                                </div>

                                <div class="form-item">
                                    <div class="form-select">
                                        <label for="{{ $modalId }}-recruitment-status">{{ __('ui.team_recruiting') }}</label>
                                        <select id="{{ $modalId }}-recruitment-status" name="recruitment_status" required>
                                            <option value="open" @selected($recruitmentStatus === 'open')>{{ __('ui.recruiting_open') }}</option>
                                            <option value="closed" @selected($recruitmentStatus === 'closed')>{{ __('ui.recruiting_closed') }}</option>
                                        </select>
                                        <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                                    </div>
                                    @if($useOldInput) @error('recruitment_status')<span class="hh-form-error">{{ $message }}</span>@enderror @endif
                                </div>
                            </div>

                            <div class="form-row split">
                                <div class="form-item">
                                    <div class="form-select">
                                        <label for="{{ $modalId }}-platform">{{ __('ui.platform') }}</label>
                                        <select id="{{ $modalId }}-platform" name="platform">
                                            <option value="">{{ __('ui.select_option') }}</option>
                                            @foreach (['PC', 'PlayStation', 'Xbox', 'Crossplay'] as $option)
                                                <option value="{{ $option }}" @selected($platform === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                        <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                                    </div>
                                    @if($useOldInput) @error('platform')<span class="hh-form-error">{{ $message }}</span>@enderror @endif
                                </div>

                                <div class="form-item">
                                    <div class="form-select">
                                        <label for="{{ $modalId }}-playstyle">{{ __('ui.playstyle') }}</label>
                                        <select id="{{ $modalId }}-playstyle" name="playstyle">
                                            <option value="">{{ __('ui.select_option') }}</option>
                                            @foreach (['Entspannt', 'Taktisch', 'Aggressiv', 'Competitive', 'Einsteigerfreundlich'] as $option)
                                                <option value="{{ $option }}" @selected($playstyle === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                        <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                                    </div>
                                    @if($useOldInput) @error('playstyle')<span class="hh-form-error">{{ $message }}</span>@enderror @endif
                                </div>
                            </div>

                            <div class="form-row split">
                                <div class="form-item">
                                    <div class="form-select">
                                        <label for="{{ $modalId }}-region">{{ __('ui.region') }}</label>
                                        <select id="{{ $modalId }}-region" name="region">
                                            <option value="">{{ __('ui.select_option') }}</option>
                                            @foreach (['EU', 'US East', 'US West', 'Asia', 'Oceania'] as $option)
                                                <option value="{{ $option }}" @selected($region === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                        <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                                    </div>
                                    @if($useOldInput) @error('region')<span class="hh-form-error">{{ $message }}</span>@enderror @endif
                                </div>

                                <div class="form-item">
                                    <div class="form-select">
                                        <label for="{{ $modalId }}-language">{{ __('ui.language') }}</label>
                                        <select id="{{ $modalId }}-language" name="language">
                                            <option value="">{{ __('ui.select_option') }}</option>
                                            @foreach (['Deutsch', 'Englisch', 'Deutsch / Englisch', 'Mehrsprachig'] as $option)
                                                <option value="{{ $option }}" @selected($language === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                        <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                                    </div>
                                    @if($useOldInput) @error('language')<span class="hh-form-error">{{ $message }}</span>@enderror @endif
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="widget-box hh-team-modal-panel" data-hh-team-modal-panel="members">
                        <p class="widget-box-title">{{ __('ui.members') }}</p>
                        <div class="widget-box-content">
                            <p class="hh-team-modal-muted">{{ __('ui.team_members_modal_text') }}</p>
                            <div class="hh-team-modal-actions">
                                <a class="button secondary" href="{{ $isEdit ? route('teams.show', $team) : route('teams.manage') }}">{{ __('ui.view_team') }}</a>
                                <a class="button white" href="{{ route('teams.invitations') }}">{{ __('ui.invitations') }}</a>
                            </div>
                        </div>
                    </section>
                </form>

                @if ($isEdit && $team->isOwner(auth()->user()))
                    <section class="widget-box hh-team-modal-panel" data-hh-team-modal-panel="delete">
                        <p class="widget-box-title">{{ __('ui.delete_team') }}</p>
                        <div class="widget-box-content">
                            <p class="hh-team-danger-text">{{ __('ui.archive_team_text') }}</p>
                            <form method="post" action="{{ route('teams.destroy', $team) }}" onsubmit="return confirm('{{ __('ui.archive_team_confirm') }}')">
                                @csrf
                                @method('DELETE')
                                <button class="button white" type="submit">{{ __('ui.archive_team') }}</button>
                            </form>
                        </div>
                    </section>
                @endif
            </main>
        </div>
    </div>
</div>
