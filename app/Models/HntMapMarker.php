<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HntMapMarker extends Model
{
    protected $fillable = [
        'hnt_map_id',
        'legacy_key',
        'source_id',
        'type',
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
}
