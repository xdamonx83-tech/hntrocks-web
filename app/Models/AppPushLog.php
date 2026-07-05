<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppPushLog extends Model
{
    protected $fillable = [
        'admin_user_id',
        'target_user_id',
        'title',
        'body',
        'action_url',
        'data_json',
        'result_json',
        'sent_count',
        'failed_count',
    ];

    protected function casts(): array
    {
        return [
            'data_json' => 'array',
            'result_json' => 'array',
            'sent_count' => 'integer',
            'failed_count' => 'integer',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
