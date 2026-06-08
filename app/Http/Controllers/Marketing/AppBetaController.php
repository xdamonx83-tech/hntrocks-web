<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Support\HntTheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppBetaController extends Controller
{
    public function index(): View
    {
        return view(HntTheme::resolve('app-beta.index'));
    }

    public function store(Request $request): RedirectResponse
    {
        if (filled($request->input('website'))) {
            return redirect()
                ->route('app-beta.index')
                ->with('status', __('ui.app_beta_request_received'));
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:80'],
            'google_email' => ['required', 'email:rfc', 'max:190'],
            'discord' => ['nullable', 'string', 'max:80'],
            'consent' => ['accepted'],
        ]);

        $email = strtolower(trim($validated['google_email']));
        $path = storage_path('app/app-beta-requests.csv');
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if ($this->emailAlreadyRegistered($path, $email)) {
            return redirect()
                ->route('app-beta.index')
                ->with('status', __('ui.app_beta_already_registered'));
        }

        $isNewFile = ! file_exists($path) || filesize($path) === 0;
        $handle = fopen($path, 'ab');

        if ($handle === false) {
            return back()
                ->withInput()
                ->withErrors(['google_email' => __('ui.app_beta_save_failed')]);
        }

        try {
            flock($handle, LOCK_EX);

            if ($isNewFile) {
                fputcsv($handle, [
                    'created_at',
                    'name',
                    'google_email',
                    'discord',
                    'locale',
                    'early_access_badge',
                ]);
            }

            fputcsv($handle, [
                now()->toIso8601String(),
                trim((string) ($validated['name'] ?? '')),
                $email,
                trim((string) ($validated['discord'] ?? '')),
                app()->getLocale(),
                'yes',
            ]);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        return redirect()
            ->route('app-beta.index')
            ->with('status', __('ui.app_beta_request_received'));
    }

    private function emailAlreadyRegistered(string $path, string $email): bool
    {
        if (! file_exists($path)) {
            return false;
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        try {
            while (($row = fgetcsv($handle)) !== false) {
                if (($row[2] ?? null) === $email) {
                    return true;
                }
            }
        } finally {
            fclose($handle);
        }

        return false;
    }
}
