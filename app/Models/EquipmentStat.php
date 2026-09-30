<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class EquipmentStat extends Model { public $timestamps = false; protected $guarded = []; protected $casts = ['value' => 'float']; public function definition(): BelongsTo { return $this->belongsTo(EquipmentStatDefinition::class, 'stat_definition_id'); } }
