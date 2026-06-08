@extends('themes.hnt_preview.layouts.app')

@section('main_class', 'trophy-prop-lab-main')
@section('app_window_class', 'trophy-room-window trophy-prop-lab-window')
@section('title', __('ui.trophy_prop_lab_page_title'))

@section('right_sidebar')
    <aside class="trophy-prop-lab-side" aria-label="{{ __('ui.trophy_prop_lab_side_aria') }}">
        <div class="trophy-prop-lab-card">
            <span class="trophy-prop-lab-kicker">{{ __('ui.trophy_prop_lab_side_kicker') }}</span>
            <h2>{{ __('ui.trophy_prop_lab_side_title') }}</h2>
            <p>{{ __('ui.trophy_prop_lab_side_text') }}</p>
        </div>

        <div class="trophy-prop-lab-card compact trophy-prop-lab-upload-card">
            <span class="trophy-prop-lab-kicker">{{ __('ui.trophy_prop_lab_upload_kicker') }}</span>
            <h2>{{ __('ui.trophy_prop_lab_upload_title') }}</h2>
            <p>{{ __('ui.trophy_prop_lab_upload_text', ['size' => $maxUploadMegabytes . ' MB']) }}</p>
            <form class="trophy-prop-lab-upload-form" action="{{ route('trophy-room.prop-lab.models.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <label>
                    <span>{{ __('ui.trophy_prop_lab_upload_label') }}</span>
                    <input type="file" name="glb_model" accept=".glb,model/gltf-binary" required>
                </label>
                @error('glb_model')
                    <div class="trophy-prop-lab-alert error">{{ $message }}</div>
                @enderror
                <button class="btn-create" type="submit">
                    <i class="ph ph-upload-simple" aria-hidden="true"></i>
                    {{ __('ui.trophy_prop_lab_upload_button') }}
                </button>
            </form>
        </div>

        <div class="trophy-prop-lab-card compact">
            <span class="trophy-prop-lab-kicker">{{ __('ui.trophy_prop_lab_json_kicker') }}</span>
            <textarea class="trophy-prop-lab-json" data-prop-output readonly></textarea>
            <button class="btn-create" type="button" data-prop-copy>
                <i class="ph ph-copy" aria-hidden="true"></i>
                {{ __('ui.trophy_prop_lab_copy') }}
            </button>
        </div>
    </aside>
@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('assets/themes/hnt_preview/trophy-prop-lab.css') }}?v=760">
    <script type="importmap">
        {
            "imports": {
                "three": "https://cdn.jsdelivr.net/npm/three@0.164.1/build/three.module.js",
                "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.164.1/examples/jsm/"
            }
        }
    </script>
@endpush

@section('content')
<div
    class="trophy-prop-lab-shell"
    data-prop-lab
    data-models='@json($models)'
    data-empty-label="{{ __('ui.trophy_prop_lab_empty') }}"
    data-load-error="{{ __('ui.trophy_prop_lab_load_error') }}"
    data-copied-label="{{ __('ui.trophy_prop_lab_copied') }}"
    data-loading-label="{{ __('ui.trophy_prop_lab_loading') }}"
>
    <section class="trophy-prop-lab-hero">
        <div>
            <span class="trophy-prop-lab-kicker"><i class="ph ph-cube-focus" aria-hidden="true"></i>{{ __('ui.trophy_prop_lab_kicker') }}</span>
            <h1>{{ __('ui.trophy_prop_lab_title') }}</h1>
            <p>{{ __('ui.trophy_prop_lab_intro') }}</p>
        </div>
        <div class="trophy-prop-lab-actions">
            <a class="trophy-room-soft-link" href="{{ route('trophy-room.index') }}">
                <i class="ph ph-arrow-left" aria-hidden="true"></i>
                {{ __('ui.trophy_prop_lab_back') }}
            </a>
        </div>
    </section>

    @if (session('trophy_prop_lab_success'))
        <div class="trophy-prop-lab-alert success">
            <i class="ph ph-check-circle" aria-hidden="true"></i>
            {{ session('trophy_prop_lab_success') }}
        </div>
    @endif

    <section class="trophy-prop-lab-workbench">
        <div class="trophy-prop-lab-preview-card">
            <div class="trophy-prop-lab-preview-head">
                <div>
                    <span>{{ __('ui.trophy_prop_lab_preview_kicker') }}</span>
                    <h2 data-prop-current-name>{{ __('ui.trophy_prop_lab_preview_title') }}</h2>
                </div>
                <div class="trophy-prop-lab-status" data-prop-status>
                    <i class="ph ph-spinner-gap" aria-hidden="true"></i>
                    {{ __('ui.trophy_prop_lab_status_ready') }}
                </div>
            </div>
            <div class="trophy-prop-lab-canvas-wrap">
                <canvas data-prop-canvas></canvas>
                <div class="trophy-prop-lab-fallback" data-prop-fallback hidden>
                    <i class="ph ph-warning-circle" aria-hidden="true"></i>
                    <strong>{{ __('ui.trophy_prop_lab_no_models_title') }}</strong>
                    <span>{{ __('ui.trophy_prop_lab_no_models_text') }}</span>
                </div>
            </div>
        </div>

        <form class="trophy-prop-lab-controls" data-prop-form>
            <label>
                <span>{{ __('ui.trophy_prop_lab_model') }}</span>
                <select data-prop-model>
                    @forelse ($models as $model)
                        <option value="{{ $model['file'] }}">{{ $model['group'] }} · {{ $model['label'] }} · {{ $model['size_label'] }}</option>
                    @empty
                        <option value="">{{ __('ui.trophy_prop_lab_empty') }}</option>
                    @endforelse
                </select>
            </label>

            <div class="trophy-prop-lab-grid three">
                <label><span>X</span><input type="number" step="0.1" value="0" data-prop-x></label>
                <label><span>Y</span><input type="number" step="0.1" value="0" data-prop-y></label>
                <label><span>Z</span><input type="number" step="0.1" value="0" data-prop-z></label>
            </div>

            <div class="trophy-prop-lab-grid two">
                <label>
                    <span>{{ __('ui.trophy_prop_lab_rotation_y') }}</span>
                    <input type="number" step="1" value="0" data-prop-rotation-y>
                </label>
                <label>
                    <span>{{ __('ui.trophy_prop_lab_scale') }}</span>
                    <input type="number" min="0.05" step="0.05" value="1" data-prop-scale>
                </label>
            </div>

            <div class="trophy-prop-lab-toggle-row">
                <label class="trophy-prop-lab-check">
                    <input type="checkbox" data-prop-grid checked>
                    <span>{{ __('ui.trophy_prop_lab_grid') }}</span>
                </label>
                <label class="trophy-prop-lab-check">
                    <input type="checkbox" data-prop-auto-fit checked>
                    <span>{{ __('ui.trophy_prop_lab_auto_fit') }}</span>
                </label>
            </div>

            <div class="trophy-prop-lab-button-row">
                <button class="btn-create" type="button" data-prop-reset>
                    <i class="ph ph-arrows-clockwise" aria-hidden="true"></i>
                    {{ __('ui.trophy_prop_lab_reset') }}
                </button>
                <button class="trophy-room-soft-link" type="button" data-prop-copy-inline>
                    <i class="ph ph-copy" aria-hidden="true"></i>
                    {{ __('ui.trophy_prop_lab_copy_short') }}
                </button>
            </div>
        </form>
    </section>
</div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('assets/themes/hnt_preview/trophy-prop-lab.js') }}?v=760"></script>
@endpush
