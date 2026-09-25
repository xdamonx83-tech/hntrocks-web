<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppRemoteConfig;
use App\Services\AppConfig\WebAppearanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminWebAppearanceController extends Controller
{
    public function index(Request $request, WebAppearanceService $appearance): View
    {
        $this->guardAdmin($request);

        $record = AppRemoteConfig::query()->where('key', WebAppearanceService::KEY)->first();

        return view('admin.appearance.index', [
            'backgrounds' => $appearance->normalize($record?->config_json ?? [])['backgrounds'],
            'slots' => [
                'auth' => ['Auth Background', 'Login, Registrierung, Passwort und 2FA'],
                'landing' => ['Landing Background', 'Öffentliche Landingpage'],
                'app' => ['App Background', 'Community und App-Bereiche'],
                'topbar' => ['Topbar Background', 'Obere Navigationsleiste'],
                'sidebar' => ['Sidebar Background', 'Seitliche Navigation'],
            ],
        ]);
    }

    public function update(Request $request, WebAppearanceService $appearance): RedirectResponse
    {
        $this->guardAdmin($request);

        $rules = ['backgrounds' => ['required', 'array:auth,landing,app,topbar,sidebar']];
        foreach (WebAppearanceService::SLOTS as $slot) {
            $rules['backgrounds.'.$slot] = ['sometimes', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'];
        }
        $request->validate($rules);

        $files = [];
        foreach (WebAppearanceService::SLOTS as $slot) {
            if ($request->hasFile('backgrounds.'.$slot)) {
                $files[$slot] = $request->file('backgrounds.'.$slot);
            }
        }
        if ($files === []) {
            throw ValidationException::withMessages(['backgrounds' => 'Bitte mindestens ein Bild auswählen.']);
        }

        DB::transaction(function () use ($request, $appearance, $files): void {
            $record = AppRemoteConfig::query()->where('key', WebAppearanceService::KEY)->lockForUpdate()->first();
            $record ??= new AppRemoteConfig(['key' => WebAppearanceService::KEY]);
            $config = $appearance->normalize($record->config_json ?? []);

            foreach ($files as $slot => $file) {
                $extension = $file->extension();
                $filename = 'web-'.$slot.'-background-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(8)).'.'.$extension;
                $path = $file->storeAs('app-backgrounds', $filename, 'public');
                if (! is_string($path) || $path === '') {
                    throw ValidationException::withMessages(['backgrounds.'.$slot => 'Das Bild konnte nicht gespeichert werden.']);
                }
                $config['backgrounds'][$slot]['url'] = '/storage/'.$path;
                $config['backgrounds'][$slot]['version']++;
            }

            $record->fill([
                'is_active' => true,
                'config_json' => $config,
                'published_at' => now(),
                'updated_by' => $request->user()->id,
            ]);
            if (! $record->exists) {
                $record->created_by = $request->user()->id;
            }
            $record->save();
        });

        $appearance->invalidate();

        return redirect()->route('admin.appearance.index')->with('status', 'Website-Hintergründe gespeichert.');
    }

    public function reset(Request $request, string $slot, WebAppearanceService $appearance): RedirectResponse
    {
        $this->guardAdmin($request);
        abort_unless(in_array($slot, WebAppearanceService::SLOTS, true), 404);

        DB::transaction(function () use ($request, $slot, $appearance): void {
            $record = AppRemoteConfig::query()->where('key', WebAppearanceService::KEY)->lockForUpdate()->first();
            if ($record === null) {
                return;
            }
            $config = $appearance->normalize($record->config_json ?? []);
            if ($config['backgrounds'][$slot]['url'] === null) {
                return;
            }
            $config['backgrounds'][$slot]['url'] = null;
            $config['backgrounds'][$slot]['version']++;
            $record->config_json = $config;
            $record->updated_by = $request->user()->id;
            $record->published_at = now();
            $record->save();
        });

        $appearance->invalidate();

        return redirect()->route('admin.appearance.index')->with('status', 'Hintergrund entfernt. Lokaler Fallback aktiv.');
    }

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
