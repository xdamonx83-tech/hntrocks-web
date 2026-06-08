<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CampaignLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'label',
        'target_url',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'clicks_count',
        'is_active',
        'last_clicked_at',
    ];

    protected function casts(): array
    {
        return [
            'clicks_count' => 'integer',
            'is_active' => 'boolean',
            'last_clicked_at' => 'datetime',
        ];
    }


    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function publicUrl(): string
    {
        return route('campaign.redirect', ['slug' => $this->slug]);
    }

    public function targetUrlWithUtm(): string
    {
        $targetUrl = trim((string) $this->target_url);

        if ($targetUrl === '') {
            $targetUrl = '/';
        }

        if (! Str::startsWith($targetUrl, ['http://', 'https://'])) {
            $targetUrl = url('/'.ltrim($targetUrl, '/'));
        }

        $query = array_filter([
            'utm_source' => $this->utm_source,
            'utm_medium' => $this->utm_medium,
            'utm_campaign' => $this->utm_campaign,
        ], static fn ($value): bool => $value !== null && $value !== '');

        if ($query === []) {
            return $targetUrl;
        }

        return $targetUrl.(str_contains($targetUrl, '?') ? '&' : '?').http_build_query($query);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(CampaignLinkClick::class);
    }
}
