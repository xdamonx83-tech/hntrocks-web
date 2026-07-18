<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationSetting extends Model
{
    use HasFactory;

    public const FIELDS = [
        'feed_comments',
        'feed_reactions',
        'friends',
        'teams',
        'lfg',
        'gamification',
        'moments',
        'cups',
        'guides',
        'referrals',
    ];

    protected $fillable = [
        'user_id',
        'feed_comments',
        'feed_reactions',
        'friends',
        'teams',
        'lfg',
        'gamification',
        'moments',
        'cups',
        'guides',
        'referrals',
    ];

    protected function casts(): array
    {
        return array_fill_keys(self::FIELDS, 'boolean');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function allows(string $notificationType): bool
    {
        $field = self::fieldForType($notificationType);

        if (! $field) {
            return true;
        }

        return (bool) $this->{$field};
    }

    public static function fieldForType(string $notificationType): ?string
    {
        $type = strtolower($notificationType);

        if (str_starts_with($type, 'team_lfg_') || str_starts_with($type, 'team_')) {
            return 'teams';
        }

        if (str_starts_with($type, 'lfg_')) {
            return 'lfg';
        }

        if ($type === 'feed_comment_reaction' || str_starts_with($type, 'feed_like') || str_starts_with($type, 'feed_reaction')) {
            return 'feed_reactions';
        }

        if ($type === 'feed_mention' || $type === 'feed_comment_mention' || str_starts_with($type, 'feed_comment')) {
            return 'feed_comments';
        }

        if (str_starts_with($type, 'friend_')) {
            return 'friends';
        }

        if (str_starts_with($type, 'badge_') || str_starts_with($type, 'quest_')) {
            return 'gamification';
        }

        if (str_starts_with($type, 'moment_')) {
            return 'moments';
        }

        if (str_starts_with($type, 'cup_')) {
            return 'cups';
        }

        if (str_starts_with($type, 'guide_')) {
            return 'guides';
        }

        if (str_starts_with($type, 'referral_')) {
            return 'referrals';
        }

        return null;
    }
}
