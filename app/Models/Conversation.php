<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'title', 'context_type', 'context_id', 'context_label', 'context_url', 'created_by'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot('last_read_at', 'cleared_at')
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function scopeForUser($query, User $user)
    {
        return $query->whereHas('users', fn ($users) => $users->where('users.id', $user->id));
    }

    public function isParticipant(User $user): bool
    {
        if (! $this->relationLoaded('users')) {
            return $this->users()->where('users.id', $user->id)->exists();
        }

        return $this->users->contains('id', $user->id);
    }

    public function otherParticipant(User $user): ?User
    {
        $this->loadMissing('users');

        return $this->users->firstWhere('id', '!=', $user->id);
    }

    public function displayTitleFor(User $user): string
    {
        if ($this->context_label) {
            return $this->context_label;
        }

        if ($this->title) {
            return $this->title;
        }

        return $this->otherParticipant($user)?->name ?: 'Konversation';
    }

    public function isLfgConversation(): bool
    {
        return in_array($this->type, ['lfg', 'team_lfg'], true);
    }

    public function contextBadgeLabel(): ?string
    {
        return match ($this->type) {
            'lfg' => 'LFG',
            'team_lfg' => 'Team-LFG',
            default => null,
        };
    }

    public function markReadFor(User $user): void
    {
        $this->users()->updateExistingPivot($user->id, ['last_read_at' => now()]);
    }

    public function unreadCountFor(User $user): int
    {
        $participant = $this->users()->where('users.id', $user->id)->first()?->pivot;

        return $this->messages()
            ->where('user_id', '!=', $user->id)
            ->when($participant?->last_read_at, fn ($query, $lastReadAt) => $query->where('created_at', '>', $lastReadAt))
            ->when($participant?->cleared_at, fn ($query, $clearedAt) => $query->where('created_at', '>', $clearedAt))
            ->count();
    }
}
