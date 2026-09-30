<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EquipmentItem extends Model
{
    protected $guarded = [];
    protected $casts = ['facts' => 'array', 'last_synced_at' => 'datetime', 'source_updated_at' => 'datetime',
        'slot_size' => 'integer', 'price' => 'integer', 'unlock_rank' => 'integer'];

    public function source(): BelongsTo { return $this->belongsTo(EquipmentSource::class); }
    public function family(): BelongsTo { return $this->belongsTo(EquipmentFamily::class); }
    public function translations(): HasMany { return $this->hasMany(EquipmentTranslation::class); }
    public function stats(): HasMany { return $this->hasMany(EquipmentStat::class); }
    public function ammo(): HasMany { return $this->hasMany(EquipmentAmmo::class); }
    public function skins(): HasMany { return $this->hasMany(EquipmentSkin::class); }
    public function patchHistory(): HasMany { return $this->hasMany(EquipmentPatchHistory::class); }
    public function traits(): BelongsToMany { return $this->belongsToMany(EquipmentTrait::class, 'equipment_item_traits'); }
}
