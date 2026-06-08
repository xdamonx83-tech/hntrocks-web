<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;

class MediaAsset extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'attachable_type',
        'attachable_id',
        'context',
        'disk',
        'path',
        'thumbnail_path',
        'type',
        'mime_type',
        'original_name',
        'extension',
        'size_bytes',
        'width',
        'height',
        'duration_seconds',
        'visibility',
        'status',
        'alt_text',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'duration_seconds' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function url(): string
    {
        if ($this->isPrivateCupScreenshot()) {
            $submission = CupSubmission::query()->find($this->attachable_id);

            if ($submission && Route::has('cups.submissions.screenshot')) {
                return route('cups.submissions.screenshot', [$submission->cup, $submission]);
            }
        }

        return Storage::disk($this->disk)->url($this->path);
    }

    public function thumbnailUrl(): string
    {
        if ($this->isPrivateCupScreenshot()) {
            return $this->url();
        }

        if ($this->thumbnail_path) {
            return Storage::disk($this->disk)->url($this->thumbnail_path);
        }

        return $this->url();
    }


    public function isPrivateCupScreenshot(): bool
    {
        return $this->visibility === 'private'
            && $this->context === 'cups/screenshots'
            && $this->attachable_type === (new CupSubmission())->getMorphClass()
            && filled($this->attachable_id);
    }

    public function isImage(): bool
    {
        return $this->type === 'image' || str_starts_with((string) $this->mime_type, 'image/');
    }

    public function isVideo(): bool
    {
        return $this->type === 'video' || str_starts_with((string) $this->mime_type, 'video/');
    }

    public function isLinkedToContent(): bool
    {
        return filled($this->attachable_type) && filled($this->attachable_id);
    }

    public function readableSize(): string
    {
        $bytes = max(0, (int) $this->size_bytes);

        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2).' GB';
        }

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }

    public function contextLabel(): string
    {
        return match ($this->context) {
            'feed' => __('ui.feed'),
            'profile_avatar' => __('ui.media_profile_avatar'),
            'profile_cover' => __('ui.media_profile_cover'),
            'team_avatar' => __('ui.media_team_avatar'),
            'team_cover' => __('ui.media_team_cover'),
            'profile' => __('ui.media_profile'),
            'team' => __('ui.visibility_team'),
            'messages' => __('ui.media_messages'),
            'moments' => 'Moments',
            'cups' => 'Cups',
            'loadout_challenges' => __('ui.loadout_nav'),
            default => __('ui.media_library'),
        };
    }
}
