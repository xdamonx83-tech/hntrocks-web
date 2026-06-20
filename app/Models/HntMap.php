<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HntMap extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'width',
        'height',
        'image_path',
        'lines_path',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'width' => 'integer',
            'height' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function markers(): HasMany
    {
        return $this->hasMany(HntMapMarker::class);
    }
}
