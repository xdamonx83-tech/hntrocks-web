<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentSourceSnapshot extends Model
{
    protected $guarded = [];
    protected $casts = [
        'normalized_payload' => 'array',
        'source_revision_timestamp' => 'datetime',
        'fetched_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Source snapshots are immutable.'));
        static::deleting(fn () => throw new \LogicException('Source snapshots are immutable.'));
    }
}
