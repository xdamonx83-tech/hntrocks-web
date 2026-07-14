<?php

namespace App\Http\Controllers\Cups;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardCupsLiveController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $viewer = $request->user();

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', ''),
            'platform' => (string) $request->query('platform', ''),
            'mine' => $viewer !== null && $request->boolean('mine'),
        ];

        $cups = Cup::query()
            ->withCount(['activeTeams', 'pendingSubmissions'])
            ->with('owner:id,name,username,avatar_path')
            ->when($filters['q'] !== '', function ($query) use ($filters): void {
                $query->where(function ($subQuery) use ($filters): void {
                    $subQuery->where('title', 'like', '%'.$filters['q'].'%')
                        ->orWhere('summary', 'like', '%'.$filters['q'].'%')
                        ->orWhere('rules', 'like', '%'.$filters['q'].'%');
                });
            })
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['platform'] !== '', function ($query) use ($filters): void {
                $platform = strtolower(str_replace([' ', '-', '_', '/'], '', $filters['platform']));
                $aliases = match ($platform) {
                    'pc', 'steam', 'windows' => ['PC', 'pc', 'Steam', 'Windows'],
                    'ps', 'ps4', 'ps5', 'playstation', 'playstation4', 'playstation5' => ['PlayStation', 'PS5', 'PS4', 'ps5', 'ps4', 'PlayStation / Xbox', 'PS5/Xbox', 'PS5 / Xbox', 'Konsole', 'Console'],
                    'xbox', 'xboxseries', 'xboxseriesx', 'xboxseriess', 'xboxseriesxs' => ['Xbox', 'xbox', 'PlayStation / Xbox', 'PS5/Xbox', 'PS5 / Xbox', 'Konsole', 'Console'],
                    'konsole', 'console' => ['Konsole', 'Console', 'PlayStation', 'PS5', 'PS4', 'Xbox', 'PlayStation / Xbox', 'PS5/Xbox', 'PS5 / Xbox'],
                    default => [$filters['platform']],
                };

                $jsonPlatforms = match ($platform) {
                    'pc', 'steam', 'windows' => ['PC'],
                    'ps', 'ps4', 'ps5', 'playstation', 'playstation4', 'playstation5' => ['PlayStation'],
                    'xbox', 'xboxseries', 'xboxseriesx', 'xboxseriess', 'xboxseriesxs' => ['Xbox'],
                    'konsole', 'console' => ['PlayStation', 'Xbox'],
                    default => [$filters['platform']],
                };

                $query->where(function ($platformQuery) use ($aliases, $jsonPlatforms): void {
                    foreach (array_values(array_unique($aliases)) as $alias) {
                        $platformQuery->orWhere('platform', 'like', '%'.$alias.'%');
                    }

                    foreach (array_values(array_unique($jsonPlatforms)) as $jsonPlatform) {
                        $platformQuery->orWhereJsonContains('settings->platform_gate->allowed_platforms', $jsonPlatform);
                    }
                });
            })
            ->when($filters['mine'] && $viewer !== null, fn ($query) => $query->where('owner_id', $viewer->id))
            ->when(! $viewer?->isAdmin(), function ($query) use ($viewer): void {
                $query->where(function ($visibilityQuery) use ($viewer): void {
                    $visibilityQuery->where('visibility', 'public');

                    if ($viewer !== null) {
                        $visibilityQuery->orWhere('owner_id', $viewer->id);
                    }
                });
            })
            ->latest('starts_at')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return response()->view('themes.hnt_preview.cups.index', [
            'cups' => $cups,
            'filters' => $filters,
        ]);
    }
}
