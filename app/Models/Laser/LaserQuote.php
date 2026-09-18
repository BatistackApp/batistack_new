<?php

namespace App\Models\Laser;

use App\Enums\Laser\QuoteStatus;
use App\Models\Laser\Concerns\RecalculatesLaserTotals;
use App\Models\Tiers\ThirdParty;
use App\Observers\Laser\LaserQuoteObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy([LaserQuoteObserver::class])]
class LaserQuote extends Model
{
    use HasFactory, RecalculatesLaserTotals;

    protected $fillable = [
        'client_id',
        'reference',
        'status',
        'total_ht',
        'total_ttc',
        'terms',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'total_ht' => 'decimal:2',
            'total_ttc' => 'decimal:2',
            'expires_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class, 'client_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(LaserQuoteLine::class);
    }

    public function order(): HasOne
    {
        return $this->hasOne(LaserOrder::class);
    }

    public function canBeDeleted(): bool
    {
        return $this->status->value === QuoteStatus::DRAFT->value;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
