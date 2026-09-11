<?php

namespace App\Models\Laser;

use App\Enums\Laser\OrderStatus;
use App\Models\Laser\Concerns\RecalculatesLaserTotals;
use App\Models\Tiers\ThirdParty;
use App\Observers\Laser\LaserOrderObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([LaserOrderObserver::class])]
class LaserOrder extends Model
{
    use HasFactory, RecalculatesLaserTotals;

    protected $fillable = [
        'client_id',
        'laser_quote_id',
        'reference',
        'status',
        'total_ht',
        'total_ttc',
        'terms',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'total_ht' => 'decimal:2',
            'total_ttc' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class, 'client_id');
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(LaserQuote::class, 'laser_quote_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(LaserOrderLine::class);
    }

    public function canBeDeleted(): bool
    {
        return $this->status->value === OrderStatus::DRAFT->value;
    }
}
