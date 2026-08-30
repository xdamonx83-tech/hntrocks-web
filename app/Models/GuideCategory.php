<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuideCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name_de',
        'name_en',
        'description_de',
        'description_en',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(GuideRevision::class, 'category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name_de');
    }

    public function label(?string $locale = null): string
    {
        return ($locale ?? app()->getLocale()) === 'en' ? $this->name_en : $this->name_de;
    }

    public function description(?string $locale = null): ?string
    {
        return ($locale ?? app()->getLocale()) === 'en' ? $this->description_en : $this->description_de;
    }
}
