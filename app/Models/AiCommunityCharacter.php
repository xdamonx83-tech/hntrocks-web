<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiCommunityCharacter extends Model
{
    protected $fillable = ['name', 'language', 'persona_voice', 'topics', 'prompt_settings', 'enabled', 'user_id', 'generated_count', 'published_count'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function drafts(): HasMany { return $this->hasMany(AiCommunityDraft::class, 'character_id'); }

    public function disclosure(): string
    {
        return $this->language === 'de'
            ? 'Automatisierter HNT.ROCKS Community-Charakter. Kein echter Spieler.'
            : 'Automated HNT.ROCKS community character. Not a real player.';
    }
}
