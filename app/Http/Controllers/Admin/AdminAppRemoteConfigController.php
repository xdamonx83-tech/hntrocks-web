<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppRemoteConfig;
use App\Services\AppConfig\AppRemoteConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use JsonException;

class AdminAppRemoteConfigController extends Controller
{
    public function index(Request $request, AppRemoteConfigService $remoteConfig): View
    {
        $this->guardAdmin($request);

        $config = AppRemoteConfig::query()->firstOrCreate(
            ['key' => AppRemoteConfigService::DEFAULT_KEY],
            [
                'is_active' => true,
                'config_json' => $remoteConfig->defaults(),
                'published_at' => now(),
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]
        );

        $normalizedConfig = $remoteConfig->previewConfig($config);

        return view('admin.app-remote-config.index', [
            'config' => $config,
            'configJson' => json_encode($normalizedConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'themePalette' => $normalizedConfig['theme']['palette'],
            'defaultThemePalette' => $remoteConfig->defaults()['theme']['palette'],
            'branding' => $normalizedConfig['branding'],
            'appearance' => $normalizedConfig['appearance'],
            'preview' => [
                'message' => 'Remote config loaded.',
                'config' => $normalizedConfig,
                'feed_cards' => [],
                'server_time' => now()->toIso8601String(),
            ],
        ]);
    }

    public function update(Request $request, AppRemoteConfigService $remoteConfig): RedirectResponse
    {
        $this->guardAdmin($request);

        $data = $request->validate([
            'is_active' => ['nullable', 'boolean'],
            'publish_now' => ['nullable', 'boolean'],
            'config_json' => ['required', 'string', 'max:30000'],
            'theme_palette' => ['nullable', 'array'],
            'theme_palette.*' => ['nullable', 'string', 'max:20'],
            'palette_enabled' => ['nullable', 'boolean'],
            'logo_enabled' => ['nullable', 'boolean'],
            'logo_file' => ['nullable', 'file', 'mimes:svg,png,webp', 'max:1024'],
            'logo_dark_file' => ['nullable', 'file', 'mimes:svg,png,webp', 'max:1024'],
            'remote_appearance_form' => ['nullable', 'boolean'],
            'auth_background_file' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'feed_background_file' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'auth_background_clear' => ['nullable', 'boolean'],
            'feed_background_clear' => ['nullable', 'boolean'],
        ]);

        try {
            $decoded = json_decode($data['config_json'], true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return back()->withInput()->withErrors(['config_json' => 'Das JSON ist ungültig.']);
        }

        if (! is_array($decoded)) {
            return back()->withInput()->withErrors(['config_json' => 'Die Config muss ein JSON-Objekt sein.']);
        }

        $config = AppRemoteConfig::query()->firstOrNew(['key' => AppRemoteConfigService::DEFAULT_KEY]);
        $previousConfig = $remoteConfig->normalizeConfig($config->config_json ?? []);

        if ($this->hasBrandingFormFields($request)) {
            $decoded = $this->mergeBrandingFields($decoded, $request, $remoteConfig);
        }

        if ($this->hasAppearanceFormFields($request)) {
            $decoded = $this->mergeAppearanceFields($decoded, $request);
        }

        $normalizedConfig = $remoteConfig->withAppearanceRevisions($previousConfig, $decoded);

        $config->fill([
            'is_active' => $request->boolean('is_active'),
            'config_json' => $normalizedConfig,
            'published_at' => $request->boolean('publish_now') ? now() : $config->published_at,
            'updated_by' => $request->user()->id,
        ]);

        if (! $config->exists) {
            $config->created_by = $request->user()->id;
        }

        $config->save();
        $remoteConfig->invalidateActiveConfigCache();

        return redirect()->route('admin.app-remote-config.index')->with('status', 'Remote Config gespeichert.');
    }

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }

    private function hasBrandingFormFields(Request $request): bool
    {
        return $request->boolean('remote_branding_form')
            || $request->hasFile('logo_file')
            || $request->hasFile('logo_dark_file');
    }

    private function hasAppearanceFormFields(Request $request): bool
    {
        return $request->boolean('remote_appearance_form')
            || $request->hasFile('auth_background_file')
            || $request->hasFile('feed_background_file')
            || $request->boolean('auth_background_clear')
            || $request->boolean('feed_background_clear');
    }

    private function mergeBrandingFields(array $decoded, Request $request, AppRemoteConfigService $remoteConfig): array
    {
        $defaults = $remoteConfig->defaults();
        $palette = [];

        foreach (array_keys($defaults['theme']['palette']) as $key) {
            $palette[$key] = $request->input('theme_palette.'.$key);
        }

        data_set($decoded, 'theme.palette_enabled', $request->boolean('palette_enabled'));
        data_set($decoded, 'theme.palette', $palette);
        data_set($decoded, 'branding.logo_enabled', $request->boolean('logo_enabled'));
        data_set($decoded, 'branding.logo_url', $this->uploadedLogoUrl($request, 'logo_file') ?? data_get($decoded, 'branding.logo_url'));
        data_set($decoded, 'branding.logo_dark_url', $this->uploadedLogoUrl($request, 'logo_dark_file') ?? data_get($decoded, 'branding.logo_dark_url'));

        if ($request->hasFile('logo_file') || $request->hasFile('logo_dark_file')) {
            data_set($decoded, 'branding.logo_updated_at', now()->toIso8601String());
        }

        return $decoded;
    }

    private function mergeAppearanceFields(array $decoded, Request $request): array
    {
        foreach ([
            'auth_background' => 'auth_background_file',
            'feed_background' => 'feed_background_file',
        ] as $configKey => $requestKey) {
            if ($request->boolean(str_replace('_file', '_clear', $requestKey))) {
                data_set($decoded, 'appearance.'.$configKey.'.url', null);
                continue;
            }

            $uploadedUrl = $this->uploadedBackgroundUrl($request, $requestKey);

            if ($uploadedUrl !== null) {
                data_set($decoded, 'appearance.'.$configKey.'.url', $uploadedUrl);
            }
        }

        return $decoded;
    }

    private function uploadedLogoUrl(Request $request, string $key): ?string
    {
        if (! $request->hasFile($key)) {
            return null;
        }

        $file = $request->file($key);
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $prefix = $key === 'logo_dark_file' ? 'logo-dark' : 'logo-main';
        $filename = $prefix.'-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(8)).'.'.$extension;
        $path = $file->storeAs('app-branding', $filename, 'public');

        return Storage::disk('public')->url($path);
    }

    private function uploadedBackgroundUrl(Request $request, string $key): ?string
    {
        if (! $request->hasFile($key)) {
            return null;
        }

        $file = $request->file($key);
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $prefix = $key === 'auth_background_file' ? 'auth-background' : 'feed-background';
        $filename = $prefix.'-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(8)).'.'.$extension;
        $path = $file->storeAs('app-backgrounds', $filename, 'public');

        return Storage::disk('public')->url($path);
    }
}
