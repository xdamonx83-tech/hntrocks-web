<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Maps\Imports\MapMarkerImportPreview;
use App\Services\Maps\Imports\MapMarkerImportProviderRegistry;
use App\Services\Maps\Imports\SourceFormatException;
use App\Services\Maps\Imports\SourceUnavailableException;
use App\Support\Maps\MapMarkerRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use InvalidArgumentException;

final class AdminMapMarkerImportController extends Controller
{
    public function index(Request $request, MapMarkerImportProviderRegistry $providers): View
    {
        $this->guardAdmin($request);

        return $this->view($providers, null, null);
    }

    public function preview(Request $request, MapMarkerImportProviderRegistry $providers, MapMarkerImportPreview $preview): View
    {
        $this->guardAdmin($request);

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

    private function view(MapMarkerImportProviderRegistry $providers, ?array $result, ?string $error): View
    {
        return view('admin.maps.import', [
            'providers' => $providers->all(),
            'registry' => MapMarkerRegistry::all(),
            'result' => $result,
            'error' => $error,
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
