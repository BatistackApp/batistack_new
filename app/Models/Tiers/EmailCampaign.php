<?php

namespace App\Models\Tiers;

use App\Enums\Tiers\EmailCampaignStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subject',
        'body',
        'status',
        'scheduled_at',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => EmailCampaignStatus::class,
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(EmailCampaignRecipient::class);
    }
}
