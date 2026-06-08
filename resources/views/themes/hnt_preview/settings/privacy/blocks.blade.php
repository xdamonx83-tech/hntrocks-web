@extends('themes.hnt_preview.layouts.app')

@section('title', __('ui.account_blocked_users'))
@section('app_window_class', 'profile-window')
@section('main_class', 'profile-edit-main')

@section('content')
    <section class="edit-profile-shell hnt-settings-shell">
        @if (session('status'))
            <div class="profile-edit-status profile-edit-status-success" role="status">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="profile-edit-status profile-edit-status-danger" role="alert">
                {{ __('ui.profile_validation_error') }}
            </div>
        @endif

        @include('themes.hnt_preview.settings.partials.tabs', ['active' => 'blocks'])

        <div class="profile-edit-content-panel hnt-settings-form">
            <div class="profile-edit-form">
                <section class="profile-edit-tab-panel is-active hnt-settings-section">
                    <div class="profile-edit-section-title">
                        <span>{{ __('ui.privacy') }}</span>
                        <h2>{{ __('ui.account_blocked_users') }}</h2>
                        <p>{{ __('ui.blocked_users_privacy_text') }}</p>
                    </div>

                    <form method="POST" action="{{ route('settings.privacy.blocks.store') }}" class="hnt-settings-inline-form">
                        @csrf
                        <div class="form-row compact">
                            <label for="username">{{ __('ui.username') }}</label>
                            <div>
                                <input id="username" type="text" name="username" value="{{ old('username') }}" placeholder="{{ __('ui.block_username_placeholder') }}" required>
                                @error('username')<p class="profile-edit-error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="form-row compact">
                            <label for="reason">{{ __('ui.block_note') }}</label>
                            <div>
                                <input id="reason" type="text" name="reason" value="{{ old('reason') }}" maxlength="120" placeholder="{{ __('ui.block_note_placeholder') }}">
                                @error('reason')<p class="profile-edit-error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="form-actions profile-edit-actions hnt-settings-actions">
                            <a href="{{ route('settings.privacy.edit') }}" class="btn-create">{{ __('ui.privacy') }}</a>
                            <button type="submit" class="btn-create">{{ __('ui.block_user') }}</button>
                        </div>
                    </form>

                    <div class="profile-edit-section-title profile-edit-section-spaced hnt-settings-subtitle">
                        <span>{{ __('ui.account_blocked_users') }}</span>
                        <h2>{{ __('ui.current_blocks') }}</h2>
                        <p>{{ __('ui.account_settings_blocks_text') }}</p>
                    </div>

                    <div class="hnt-settings-list">
                        @forelse ($blocks as $block)
                            <div class="hnt-settings-list-row">
                                <div>
                                    <strong>{{ $block->blockedUser?->name ?? __('ui.deleted_user') }}</strong>
                                    <span>&#64;{{ $block->blockedUser?->username ?? __('ui.unknown') }}</span>
                                    @if ($block->reason)
                                        <small>{{ $block->reason }}</small>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('settings.privacy.blocks.destroy', $block) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn-create" type="submit">{{ __('ui.unblock') }}</button>
                                </form>
                            </div>
                        @empty
                            <p class="hnt-settings-empty">{{ __('ui.no_blocked_users') }}</p>
                        @endforelse
                    </div>

                    <div class="hnt-settings-pagination">
                        {{ $blocks->links() }}
                    </div>
                </section>
            </div>
        </div>
    </section>
@endsection
