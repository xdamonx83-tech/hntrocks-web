<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EquipmentTranslation extends Model { protected $guarded = []; protected $casts = ['description_is_manual' => 'boolean']; }
