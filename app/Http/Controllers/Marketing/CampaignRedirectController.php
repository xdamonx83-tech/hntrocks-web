<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\CampaignLink;
use App\Models\CampaignLinkClick;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CampaignRedirectController extends Controller
{
    public function __invoke(Request $request, string $slug): RedirectResponse
    {
        if (! Schema::hasTable('campaign_links')) {
            return redirect()->to($this->fallbackUrl($slug));
        }

        $campaignLink = CampaignLink::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $now = now();

        DB::transaction(function () use ($campaignLink, $now): void {
            CampaignLink::query()
                ->whereKey($campaignLink->id)
                ->update([
                    'clicks_count' => DB::raw('clicks_count + 1'),
                    'last_clicked_at' => $now,
                    'updated_at' => $now,
                ]);

            CampaignLinkClick::create([
                'campaign_link_id' => $campaignLink->id,
                'occurred_at' => $now,
            ]);
        });

        return redirect()->away($this->targetUrl($campaignLink));
    }

    private function targetUrl(CampaignLink $campaignLink): string
    {
        $targetUrl = trim((string) $campaignLink->target_url);

        if ($targetUrl === '') {
            $targetUrl = '/';
        }

        if (! Str::startsWith($targetUrl, ['http://', 'https://'])) {
            $targetUrl = url('/' . ltrim($targetUrl, '/'));
        }

        $query = array_filter([
            'utm_source' => $campaignLink->utm_source,
            'utm_medium' => $campaignLink->utm_medium,
            'utm_campaign' => $campaignLink->utm_campaign,
        ], static fn ($value): bool => $value !== null && $value !== '');

        if ($query === []) {
            return $targetUrl;
        }

        return $targetUrl . (str_contains($targetUrl, '?') ? '&' : '?') . http_build_query($query);
    }

    private function fallbackUrl(string $slug): string
    {
        return match ($slug) {
            'bayou-blood-cup' => url('/cups/bayou-blood-cup?utm_source=facebook&utm_medium=social&utm_campaign=bayou_blood_cup_launch'),
            default => url('/'),
        };
    }
}
