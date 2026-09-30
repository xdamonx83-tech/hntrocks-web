<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EquipmentSyncRun extends Model { protected $guarded = []; protected $casts = ['counts' => 'array', 'dry_run' => 'boolean', 'started_at' => 'datetime', 'finished_at' => 'datetime']; }
