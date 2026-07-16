<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamRewardGrant extends Model
{
    protected $fillable = ['team_id', 'team_contract_id', 'user_id', 'crown_transaction_id', 'kind', 'amount', 'granted_at'];

    protected function casts(): array { return ['amount' => 'integer', 'granted_at' => 'datetime']; }
    public function team(): BelongsTo { return $this->belongsTo(Team::class); }
    public function contract(): BelongsTo { return $this->belongsTo(TeamContract::class, 'team_contract_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function crownTransaction(): BelongsTo { return $this->belongsTo(CrownTransaction::class); }
}
