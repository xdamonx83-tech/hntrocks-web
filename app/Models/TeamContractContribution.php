<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamContractContribution extends Model
{
    protected $fillable = ['team_contract_id', 'user_id', 'event_type', 'source_key', 'amount', 'metadata'];

    protected function casts(): array { return ['amount' => 'integer', 'metadata' => 'array']; }
    public function contract(): BelongsTo { return $this->belongsTo(TeamContract::class, 'team_contract_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
