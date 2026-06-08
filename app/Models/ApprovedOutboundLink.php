<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApprovedOutboundLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'target_url',
        'target_domain',
        'clicks_count',
        'is_active',
        'last_clicked_at',
        'created_by',
        'admin_note',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function publicUrl(): string
    {
        return route('outbound.show', $this);
    }

    public function continueUrl(): string
    {
        return route('outbound.go', $this);
    }

    public function safeDomain(): string
    {
        if ($this->target_domain) {
            return $this->target_domain;
        }

        $host = parse_url((string) $this->target_url, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? Str::lower($host) : __('ui.external_website');
    }
}
