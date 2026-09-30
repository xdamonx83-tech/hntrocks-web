<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class EquipmentAmmo extends Model { protected $table = 'equipment_ammo'; public $timestamps = false; protected $guarded = []; protected $casts = ['damage' => 'float', 'velocity' => 'float', 'loaded' => 'integer', 'reserve' => 'integer', 'facts' => 'array']; public function falloffPoints(): HasMany { return $this->hasMany(EquipmentFalloffPoint::class); } }
