<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamSessionResponse extends Model
{
    public const RESPONSE_GOING = 'going';
    public const RESPONSE_MAYBE = 'maybe';
    public const RESPONSE_DECLINED = 'declined';

    protected $fillable = ['team_session_id', 'user_id', 'response', 'attendance_confirmed', 'attendance_confirmed_by', 'attendance_confirmed_at'];

    protected function casts(): array { return ['attendance_confirmed' => 'boolean', 'attendance_confirmed_at' => 'datetime']; }
    public function session(): BelongsTo { return $this->belongsTo(TeamSession::class, 'team_session_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function attendanceConfirmer(): BelongsTo { return $this->belongsTo(User::class, 'attendance_confirmed_by'); }
}
