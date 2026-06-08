<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SidebarMenuItem extends Model
{
    protected $fillable = [
        'menu_key',
        'label',
        'label_key',
        'route_name',
        'url',
        'match_pattern',
        'phosphor_icon',
        'section',
        'sort_order',
        'is_enabled',
        'admin_only',
        'is_custom',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'admin_only' => 'boolean',
        'is_custom' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function displayLabel(): string
    {
        if ($this->label) {
            return $this->label;
        }

        if ($this->label_key) {
            return __($this->label_key);
        }

        return $this->menu_key;
    }
}
