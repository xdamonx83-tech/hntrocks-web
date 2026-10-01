<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentFieldProvenance extends Model
{
    protected $table = 'equipment_field_provenance';
    protected $guarded = [];
    protected $casts = [
        'is_manual_override' => 'boolean',
        'verified_at' => 'datetime',
        'metadata' => 'array',
    ];
}
