<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignLinkClick extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_link_id',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    public function campaignLink(): BelongsTo
    {
        return $this->belongsTo(CampaignLink::class);
    }
}
