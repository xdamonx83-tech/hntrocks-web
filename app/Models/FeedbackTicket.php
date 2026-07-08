<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackTicket extends Model
{
    use HasFactory;

    public const TYPE_BUG = 'bug';
    public const TYPE_IDEA = 'idea';
    public const TYPE_ACCOUNT = 'account';
    public const TYPE_CUP = 'cup';
    public const TYPE_OTHER = 'other';

    public const STATUS_OPEN = 'open';
    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'user_id',
        'type',
        'subject',
        'message',
        'status',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_BUG => 'Bug',
            self::TYPE_IDEA => 'Idee',
            self::TYPE_ACCOUNT => 'Account',
            self::TYPE_CUP => 'Cup',
            self::TYPE_OTHER => 'Sonstiges',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_OPEN => 'Offen',
            self::STATUS_IN_REVIEW => 'In Prüfung',
            self::STATUS_CLOSED => 'Geschlossen',
        ];
    }

    public function typeLabel(): string
    {
        return self::typeOptions()[$this->type] ?? $this->type;
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? $this->status;
    }

    public function userLabel(): string
    {
        if (! $this->user) {
            return 'Gelöschter Nutzer';
        }

        return $this->user->username ? '@'.$this->user->username : ($this->user->name ?: 'Nutzer #'.$this->user->id);
    }
}
