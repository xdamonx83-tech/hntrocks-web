<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Friendship extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';

    protected $fillable = [
        'user_one_id',
        'user_two_id',
        'requester_id',
        'recipient_id',
        'status',
        'accepted_at',
        'declined_at',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'declined_at' => 'datetime',
        ];
    }

    public static function pairIds(User|int $first, User|int $second): array
    {
        $firstId = $first instanceof User ? (int) $first->id : (int) $first;
        $secondId = $second instanceof User ? (int) $second->id : (int) $second;

        return [min($firstId, $secondId), max($firstId, $secondId)];
    }

    public function scopeBetween(Builder $query, User|int $first, User|int $second): Builder
    {
        [$one, $two] = self::pairIds($first, $second);

        return $query->where('user_one_id', $one)->where('user_two_id', $two);
    }

    public function scopeForUser(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? (int) $user->id : (int) $user;

        return $query->where(function (Builder $inner) use ($userId): void {
            $inner->where('user_one_id', $userId)->orWhere('user_two_id', $userId);
        });
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function otherUser(User $user): ?User
    {
        if ((int) $this->user_one_id === (int) $user->id) {
            return $this->relationLoaded('userTwo') ? $this->userTwo : User::find($this->user_two_id);
        }

        if ((int) $this->user_two_id === (int) $user->id) {
            return $this->relationLoaded('userOne') ? $this->userOne : User::find($this->user_one_id);
        }

        return null;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isDeclined(): bool
    {
        return $this->status === self::STATUS_DECLINED;
    }

    public function isParticipant(User $user): bool
    {
        return in_array((int) $user->id, [(int) $this->user_one_id, (int) $this->user_two_id], true);
    }

    public function isRequester(User $user): bool
    {
        return (int) $this->requester_id === (int) $user->id;
    }

    public function isRecipient(User $user): bool
    {
        return (int) $this->recipient_id === (int) $user->id;
    }
}
