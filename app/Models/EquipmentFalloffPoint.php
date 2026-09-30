<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EquipmentFalloffPoint extends Model { public $timestamps = false; protected $guarded = []; protected $casts = ['distance' => 'float', 'damage' => 'float']; }
