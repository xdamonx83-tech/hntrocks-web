<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppRemoteConfig;
use App\Services\AppConfig\AppRemoteConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('admin.app-remote-config.index', [
            'config' => $config,
            'configJson' => json_encode($remoteConfig->previewConfig($config), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'preview' => [
                'message' => 'Remote config loaded.',
                'config' => $remoteConfig->previewConfig($config),
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
            'config_json' => ['required', 'string', 'max:20000'],
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
        $config->fill([
            'is_active' => $request->boolean('is_active'),
            'config_json' => $remoteConfig->normalizeConfig($decoded),
            'published_at' => $request->boolean('publish_now') ? now() : $config->published_at,
            'updated_by' => $request->user()->id,
        ]);

        if (! $config->exists) {
            $config->created_by = $request->user()->id;
        }

        $config->save();

        return redirect()->route('admin.app-remote-config.index')->with('status', 'Remote Config gespeichert.');
    }

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
