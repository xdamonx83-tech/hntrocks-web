<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HntMapMarker extends Model
{
    protected $fillable = [
        'hnt_map_id',
        'legacy_key',
        'source_id',
        'source_provider',
        'source_key',
        'source_category',
        'type',
        'subtype',
        'source_payload_hash',
        'last_synced_at',
        'x',
        'y',
        'label_de',
        'label_en',
        'source_image',
        'status',
        'sort_order',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'source_id' => 'integer',
            'last_synced_at' => 'datetime',
            'x' => 'float',
            'y' => 'float',
            'sort_order' => 'integer',
            'meta' => 'array',
        ];
    }

    public function map(): BelongsTo
    {
        return $this->belongsTo(HntMap::class, 'hnt_map_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(HntMapMarkerVote::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(HntMapMarkerComment::class);
    }
}
