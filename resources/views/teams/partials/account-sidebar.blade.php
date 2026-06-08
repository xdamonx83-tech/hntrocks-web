@php
    $activeSection = $activeSection ?? 'teams';
    $activeLink = $activeLink ?? 'manage';
    $accountSectionOpen = $activeSection === 'account';
@endphp

<div class="account-hub-sidebar">
    <div class="sidebar-box no-padding">
        <div class="sidebar-menu" data-hh-account-hub-accordion>
            <div class="sidebar-menu-item">
                <div class="sidebar-menu-header accordion-trigger-linked">
                    <i class="sidebar-menu-header-icon hh-ph-action-icon ph ph-user" aria-hidden="true"></i>
                    <div class="sidebar-menu-header-control-icon">
                        <svg class="sidebar-menu-header-control-icon-open icon-minus-small"><use xlink:href="#svg-minus-small"></use></svg>
                        <i class="sidebar-menu-header-control-icon-closed hh-ph-action-icon ph ph-plus" aria-hidden="true"></i>
                    </div>
                    <p class="sidebar-menu-header-title">{{ __('ui.my_profile') }}</p>
                    <p class="sidebar-menu-header-text">{{ __('ui.profile_hub_text') }}</p>
                </div>

                <div class="sidebar-menu-body accordion-content-linked {{ $activeSection === 'profile' ? 'accordion-open' : '' }}">
                    <a class="sidebar-menu-link {{ $activeSection === 'profile' && $activeLink === 'edit' ? 'active' : '' }}" href="{{ route('profile.edit') }}">{{ __('ui.profile_info') }}</a>
                    <a class="sidebar-menu-link {{ $activeSection === 'profile' && $activeLink === 'show' ? 'active' : '' }}" href="{{ route('profile.show') }}">{{ __('ui.view_profile') }}</a>
                    <a class="sidebar-menu-link {{ $activeSection === 'profile' && $activeLink === 'notifications' ? 'active' : '' }}" href="{{ route('notifications.index') }}">{{ __('ui.notifications') }}</a>
                    <a class="sidebar-menu-link {{ $activeSection === 'profile' && $activeLink === 'messages' ? 'active' : '' }}" href="{{ route('messages.index') }}">{{ __('ui.messages') }}</a>
                    <a class="sidebar-menu-link {{ $activeSection === 'profile' && $activeLink === 'friend_requests' ? 'active' : '' }}" href="{{ route('members.index', ['relationship' => 'pending']) }}">{{ __('ui.friend_requests') }}</a>
                </div>
            </div>

            <div class="sidebar-menu-item">
                <div class="sidebar-menu-header accordion-trigger-linked">
                    <i class="sidebar-menu-header-icon hh-ph-action-icon ph ph-gear-six" aria-hidden="true"></i>
                    <div class="sidebar-menu-header-control-icon">
                        <svg class="sidebar-menu-header-control-icon-open icon-minus-small"><use xlink:href="#svg-minus-small"></use></svg>
                        <i class="sidebar-menu-header-control-icon-closed hh-ph-action-icon ph ph-plus" aria-hidden="true"></i>
                    </div>
                    <p class="sidebar-menu-header-title">{{ __('ui.account') }}</p>
                    <p class="sidebar-menu-header-text">{{ __('ui.account_hub_text') }}</p>
                </div>

                <div class="sidebar-menu-body accordion-content-linked {{ $accountSectionOpen ? 'accordion-open' : '' }}">
                    <a class="sidebar-menu-link {{ $activeSection === 'account' && $activeLink === 'info' ? 'active' : '' }}" href="{{ route('account.index') }}">{{ __('ui.account_info') }}</a>
                    <a class="sidebar-menu-link {{ $activeSection === 'account' && $activeLink === 'settings' ? 'active' : '' }}" href="{{ route('account.settings.edit') }}">{{ __('ui.account_settings') }}</a>
                    <a class="sidebar-menu-link {{ $activeSection === 'account' && $activeLink === 'privacy' ? 'active' : '' }}" href="{{ route('settings.privacy.edit') }}">{{ __('ui.privacy') }}</a>
                    <a class="sidebar-menu-link {{ $activeSection === 'account' && $activeLink === 'security' ? 'active' : '' }}" href="{{ route('settings.security.index') }}">{{ __('ui.security') }}</a>
                </div>
            </div>

            <div class="sidebar-menu-item">
                <div class="sidebar-menu-header accordion-trigger-linked">
                    <i class="sidebar-menu-header-icon hh-ph-action-icon ph ph-users-three" aria-hidden="true"></i>
                    <div class="sidebar-menu-header-control-icon">
                        <svg class="sidebar-menu-header-control-icon-open icon-minus-small"><use xlink:href="#svg-minus-small"></use></svg>
                        <i class="sidebar-menu-header-control-icon-closed hh-ph-action-icon ph ph-plus" aria-hidden="true"></i>
                    </div>
                    <p class="sidebar-menu-header-title">{{ __('ui.teams') }}</p>
                    <p class="sidebar-menu-header-text">{{ __('ui.teams_hub_text') }}</p>
                </div>

                <div class="sidebar-menu-body accordion-content-linked {{ $activeSection === 'teams' ? 'accordion-open' : '' }}">
                    <a class="sidebar-menu-link {{ $activeSection === 'teams' && $activeLink === 'manage' ? 'active' : '' }}" href="{{ route('teams.manage') }}">{{ __('ui.manage_teams') }}</a>
                    <a class="sidebar-menu-link {{ $activeSection === 'teams' && $activeLink === 'invitations' ? 'active' : '' }}" href="{{ route('teams.invitations') }}">{{ __('ui.invitations') }}</a>
                    <a class="sidebar-menu-link {{ $activeSection === 'teams' && $activeLink === 'create' ? 'active' : '' }}" href="{{ route('teams.create') }}">{{ __('ui.team_create_menu') }}</a>
                    @if ($activeSection === 'teams' && $activeLink === 'edit' && !empty($teamEditHref))
                        <a class="sidebar-menu-link active" href="{{ $teamEditHref }}">{{ __('ui.team_edit_menu') }}</a>
                    @endif
                </div>
            </div>

            <div class="sidebar-menu-item">
                <div class="sidebar-menu-header accordion-trigger-linked">
                    <i class="sidebar-menu-header-icon hh-ph-action-icon ph ph-storefront" aria-hidden="true"></i>
                    <div class="sidebar-menu-header-control-icon">
                        <svg class="sidebar-menu-header-control-icon-open icon-minus-small"><use xlink:href="#svg-minus-small"></use></svg>
                        <i class="sidebar-menu-header-control-icon-closed hh-ph-action-icon ph ph-plus" aria-hidden="true"></i>
                    </div>
                    <p class="sidebar-menu-header-title">{{ __('ui.lfg_index_title') }}</p>
                    <p class="sidebar-menu-header-text">{{ __('ui.lfg_hub_text') }}</p>
                </div>

                <div class="sidebar-menu-body accordion-content-linked {{ $activeSection === 'lfg' ? 'accordion-open' : '' }}">
                    <a class="sidebar-menu-link {{ $activeSection === 'lfg' && $activeLink === 'overview' ? 'active' : '' }}" href="{{ route('lfg.index') }}">{{ __('ui.lfg_overview') }}</a>
                    <a class="sidebar-menu-link {{ $activeSection === 'lfg' && $activeLink === 'mine' ? 'active' : '' }}" href="{{ route('lfg.index', ['mine' => 1]) }}">{{ __('ui.lfg_my_posts') }}</a>
                    <a class="sidebar-menu-link {{ $activeSection === 'lfg' && $activeLink === 'create' ? 'active' : '' }}" href="{{ route('lfg.create') }}">{{ __('ui.lfg_create') }}</a>
                    @if ($activeSection === 'lfg' && $activeLink === 'edit' && !empty($lfgEditHref))
                        <a class="sidebar-menu-link active" href="{{ $lfgEditHref }}">{{ __('ui.lfg_edit_title') }}</a>
                    @endif
                </div>
            </div>

            <div class="sidebar-menu-item">
                <div class="sidebar-menu-header accordion-trigger-linked">
                    <i class="sidebar-menu-header-icon hh-ph-action-icon ph ph-medal" aria-hidden="true"></i>
                    <div class="sidebar-menu-header-control-icon">
                        <svg class="sidebar-menu-header-control-icon-open icon-minus-small"><use xlink:href="#svg-minus-small"></use></svg>
                        <i class="sidebar-menu-header-control-icon-closed hh-ph-action-icon ph ph-plus" aria-hidden="true"></i>
                    </div>
                    <p class="sidebar-menu-header-title">{{ __('ui.gamification') }}</p>
                    <p class="sidebar-menu-header-text">{{ __('ui.gamification_page_text') }}</p>
                </div>

                <div class="sidebar-menu-body accordion-content-linked {{ $activeSection === 'gamification' ? 'accordion-open' : '' }}">
                    <a class="sidebar-menu-link" href="{{ route('gamification.index') }}">{{ __('ui.gamification_badges') }}</a>
                    <a class="sidebar-menu-link" href="{{ route('gamification.index') }}#quests">{{ __('ui.gamification_quests') }}</a>
                </div>
            </div>
        </div>

            @if (!empty($teamFormId))
                <div class="sidebar-box-footer">
                    <button class="button primary" type="submit" form="{{ $teamFormId }}" @if (!empty($profileSubmitButton)) data-hh-profile-submit data-default-label="{{ __('ui.save_changes') }}" data-saving-label="{{ __('ui.saving') }}" @endif>{{ $teamPrimaryLabel ?? __('ui.save_changes') }}</button>
                    <a class="button white small-space" href="{{ $teamSecondaryHref ?? route('teams.manage') }}">{{ $teamSecondaryLabel ?? __('ui.discard_all') }}</a>
                </div>
            @endif
    </div>
</div>
