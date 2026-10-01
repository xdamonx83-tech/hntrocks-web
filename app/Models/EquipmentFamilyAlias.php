<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EquipmentFamilyAlias extends Model
{
    protected $guarded = [];

    public static function keyFor(string $alias): string
    {
        return Str::slug($alias);
    }
}
