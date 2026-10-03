<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Maps\Imports\MapMarkerImportPreview;
use App\Services\Maps\Imports\MapMarkerImportExecutor;
use App\Services\Maps\Imports\MapMarkerImportProviderRegistry;
use App\Services\Maps\Imports\StaleMapMarkerImportPreviewException;
use App\Services\Maps\Imports\StaleMapMarkerImportDatabaseException;
use App\Services\Maps\Imports\SourceFormatException;
use App\Services\Maps\Imports\SourceUnavailableException;
use App\Support\Maps\MapMarkerRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use InvalidArgumentException;
use Illuminate\Validation\Rule;
use Throwable;

final class AdminMapMarkerImportController extends Controller
{
    public function index(Request $request, MapMarkerImportProviderRegistry $providers): View
    {
        $this->guardAdmin($request);
        $request->session()->forget('map_marker_import_plan');

        return $this->view($providers, null, null);
    }

    public function preview(Request $request, MapMarkerImportProviderRegistry $providers, MapMarkerImportPreview $preview): View
    {
        $this->guardAdmin($request);
        $request->session()->forget('map_marker_import_plan');

        $validated = $request->validate([
            'provider' => ['required', 'string', 'max:64'],
            'maps' => ['required', 'array', 'min:1'],
            'maps.*' => ['required', 'string', 'max:80'],
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => ['required', 'string', 'max:80'],
            'mode' => ['required', 'in:sync,add_only'],
        ]);

        try {
            $provider = $providers->get($validated['provider']);
            $result = $preview->create($provider, $validated['maps'], $validated['categories'], $validated['mode']);
            $request->session()->put('map_marker_import_plan', [
                'user_id' => $request->user()->id,
                'provider' => $result['provider'], 'maps' => array_keys($result['maps']),
                'categories' => $validated['categories'], 'mode' => $result['mode'],
                'fingerprint' => $result['fingerprint'],
                'database_fingerprint' => $result['database_fingerprint'],
                'legacy' => array_map(fn ($map) => $map['legacy'], $result['maps']),
                'created_at' => now()->timestamp,
            ]);

            return $this->view($providers, $result, null);
        } catch (SourceUnavailableException $exception) {
            $this->logPreviewFailure($validated, $exception);
            return $this->view($providers, null, 'Die externe Markerquelle konnte nicht geladen werden.');
        } catch (SourceFormatException $exception) {
            $this->logPreviewFailure($validated, $exception);
            return $this->view($providers, null, 'Das Datenformat der Quelle hat sich möglicherweise geändert.');
        } catch (InvalidArgumentException $exception) {
            $this->logPreviewFailure($validated, $exception);
            return $this->view($providers, null, 'Die Auswahl enthält eine unbekannte Map oder Kategorie.');
        }
    }

    public function execute(Request $request, MapMarkerImportProviderRegistry $providers, MapMarkerImportExecutor $executor): View
    {
        $this->guardAdmin($request);
        $validated = $request->validate([
            'reviewed' => ['required', 'accepted'],
            'confirmation' => ['required', 'string', Rule::in(['IMPORT'])],
            'replace_legacy' => ['sometimes', 'array', 'min:1'],
            'replace_legacy.*' => ['required', 'distinct', Rule::in(['tower', 'bugs', 'wild'])],
            'replace_reviewed' => $request->has('replace_legacy') ? ['required', 'accepted'] : ['sometimes', 'accepted'],
            'replace_confirmation' => $request->has('replace_legacy') ? ['required', Rule::in(['ERSETZEN'])] : ['sometimes', 'nullable'],
        ]);
        $plan = $request->session()->get('map_marker_import_plan');
        if (! is_array($plan) || ($plan['user_id'] ?? null) !== $request->user()->id
            || now()->timestamp - ($plan['created_at'] ?? 0) > 600) {
            return $this->view($providers, null, 'Die Import-Vorschau ist abgelaufen. Bitte erstelle eine neue Vorschau.');
        }
        $replace = $validated['replace_legacy'] ?? [];
        try {
            $result = $executor->execute(
                $providers->get($plan['provider']), $plan['maps'], $plan['categories'],
                $plan['mode'], $plan['fingerprint'], $plan['database_fingerprint'], $replace, $plan['legacy'],
            );
            $request->session()->forget('map_marker_import_plan');
            return $this->view($providers, null, null, $result);
        } catch (StaleMapMarkerImportPreviewException $exception) {
            $request->session()->forget('map_marker_import_plan');
            $this->logPreviewFailure($plan, $exception);
            return $this->view($providers, null, 'Die Quelldaten haben sich seit der Vorschau geändert. Bitte erstelle eine neue Vorschau.');
        } catch (StaleMapMarkerImportDatabaseException $exception) {
            $request->session()->forget('map_marker_import_plan');
            $this->logPreviewFailure($plan, $exception);
            return $this->view($providers, null, 'Die betroffenen Kartendaten haben sich seit der Vorschau geändert. Bitte erstelle eine neue Vorschau.');
        } catch (SourceUnavailableException $exception) {
            $this->logPreviewFailure($plan, $exception);
            return $this->view($providers, null, 'Die externe Markerquelle konnte nicht geladen werden.');
        } catch (SourceFormatException $exception) {
            $this->logPreviewFailure($plan, $exception);
            return $this->view($providers, null, 'Das Datenformat der Quelle hat sich möglicherweise geändert.');
        } catch (Throwable $exception) {
            $this->logPreviewFailure($plan, $exception);
            return $this->view($providers, null, 'Der Markerimport wurde abgebrochen; es wurden keine Teiländerungen übernommen.');
        }
    }

    private function view(MapMarkerImportProviderRegistry $providers, ?array $result, ?string $error, ?array $executed = null): View
    {
        return view('admin.maps.import', [
            'providers' => $providers->all(),
            'registry' => MapMarkerRegistry::all(),
            'result' => $result,
            'error' => $error,
            'executed' => $executed,
        ]);
    }

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }

    private function logPreviewFailure(array $selection, \Throwable $exception): void
    {
        Log::warning('Map marker import preview failed', [
            'provider' => $selection['provider'],
            'maps' => $selection['maps'],
            'categories' => $selection['categories'],
            'error' => $exception::class,
            'reason' => $exception->getMessage(),
        ]);
    }
}
