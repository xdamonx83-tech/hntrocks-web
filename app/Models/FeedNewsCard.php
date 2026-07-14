<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedNewsCard extends Model
{
    use HasFactory;

    public const PUBLISHER_HUNT_NEWS = 'hunt_news';
    public const PUBLISHER_HNT_ROCKS = 'hnt_rocks';

    protected $fillable = [
        'feed_post_id',
        'created_by_user_id',
        'publisher',
        'badge',
        'kicker',
        'headline',
        'highlights',
    ];

    protected function casts(): array
    {
        return [
            'highlights' => 'array',
        ];
    }

    public function feedPost(): BelongsTo
    {
        return $this->belongsTo(FeedPost::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function publisherName(): string
    {
        return $this->publisher === self::PUBLISHER_HUNT_NEWS
            ? 'HuntNews'
            : 'HNT.ROCKS News';
    }

    public function publisherHandle(): string
    {
        return $this->publisher === self::PUBLISHER_HUNT_NEWS
            ? '@huntnews'
            : '@hntrocks';
    }

    public function publisherKicker(): string
    {
        $kicker = trim((string) $this->kicker);

        if ($kicker !== '') {
            return $kicker;
        }

        return $this->publisher === self::PUBLISHER_HUNT_NEWS
            ? 'HUNTNEWS'
            : 'HNT.ROCKS';
    }

    public static function publishers(): array
    {
        return [
            self::PUBLISHER_HUNT_NEWS => 'HuntNews',
            self::PUBLISHER_HNT_ROCKS => 'HNT.ROCKS News',
        ];
    }
}
