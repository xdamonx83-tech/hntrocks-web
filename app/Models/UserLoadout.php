<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLoadout extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'title', 'primary_weapon', 'secondary_weapon', 'tools',
        'consumables', 'traits', 'note', 'visibility', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tools' => 'array',
            'consumables' => 'array',
            'traits' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
