<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CampaignLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminCampaignLinkController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $search = trim($request->string('q')->toString());
        $monthStart = now()->copy()->startOfMonth();
        $weekStart = now()->copy()->subDays(7);

        $links = CampaignLink::query()
            ->withCount([
                'clicks as clicks_this_month_count' => fn ($query) => $query->where('occurred_at', '>=', $monthStart),
                'clicks as clicks_last_7_days_count' => fn ($query) => $query->where('occurred_at', '>=', $weekStart),
            ])
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('label', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%')
                        ->orWhere('target_url', 'like', '%'.$search.'%')
                        ->orWhere('utm_campaign', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('clicks_count')
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.campaign-links.index', [
            'links' => $links,
            'status' => $status,
            'search' => $search,
            'totalClicks' => CampaignLink::query()->sum('clicks_count'),
            'activeLinksCount' => CampaignLink::query()->where('is_active', true)->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $slug = $this->uniqueSlug($data['slug'] ?? $data['label']);

        CampaignLink::create([
            'label' => $data['label'],
            'slug' => $slug,
            'target_url' => $data['target_url'],
            'utm_source' => $data['utm_source'] ?? null,
            'utm_medium' => $data['utm_medium'] ?? null,
            'utm_campaign' => $data['utm_campaign'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.campaign-links.index')
            ->with('status', 'Kampagnenlink wurde erstellt.');
    }

    public function update(Request $request, CampaignLink $campaignLink): RedirectResponse
    {
        $data = $this->validatedData($request, $campaignLink);
        $slug = $this->uniqueSlug($data['slug'] ?? $data['label'], $campaignLink);

        $campaignLink->update([
            'label' => $data['label'],
            'slug' => $slug,
            'target_url' => $data['target_url'],
            'utm_source' => $data['utm_source'] ?? null,
            'utm_medium' => $data['utm_medium'] ?? null,
            'utm_campaign' => $data['utm_campaign'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.campaign-links.index')
            ->with('status', 'Kampagnenlink wurde gespeichert.');
    }

    public function destroy(CampaignLink $campaignLink): RedirectResponse
    {
        $campaignLink->delete();

        return redirect()
            ->route('admin.campaign-links.index')
            ->with('status', 'Kampagnenlink wurde gelöscht.');
    }

    private function validatedData(Request $request, ?CampaignLink $campaignLink = null): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:160'],
            'slug' => [
                'nullable',
                'string',
                'max:140',
                'regex:/^[A-Za-z0-9\-]+$/',
                Rule::unique('campaign_links', 'slug')->ignore($campaignLink?->id),
            ],
            'target_url' => [
                'required',
                'string',
                'max:2048',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $url = trim((string) $value);

                    if ($url === '' || ! Str::startsWith($url, ['/', 'http://', 'https://'])) {
                        $fail('Die Ziel-URL muss mit /, http:// oder https:// beginnen.');
                    }
                },
            ],
            'utm_source' => ['nullable', 'string', 'max:80'],
            'utm_medium' => ['nullable', 'string', 'max:80'],
            'utm_campaign' => ['nullable', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function uniqueSlug(string $value, ?CampaignLink $ignore = null): string
    {
        $base = Str::slug($value);

        if ($base === '') {
            $base = 'link';
        }

        $slug = $base;
        $counter = 2;

        while (CampaignLink::query()
            ->where('slug', $slug)
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
