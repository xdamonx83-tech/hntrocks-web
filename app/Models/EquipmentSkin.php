<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class EquipmentSkin extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public function imageUrl(): ?string
    {
        if (! $this->local_asset_path || ! Storage::disk('public')->exists($this->local_asset_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->local_asset_path);
    }
}
