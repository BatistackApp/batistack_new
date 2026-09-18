<?php

namespace App\Models\Laser;

use App\Enums\Laser\DeliveryStatus;
use App\Models\Tiers\ThirdParty;
use App\Observers\Laser\LaserDeliveryNoteObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([LaserDeliveryNoteObserver::class])]
class LaserDeliveryNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'laser_order_id',
        'reference',
        'status',
        'delivery_date',
    ];

    protected function casts(): array
    {
        return [
            'status' => DeliveryStatus::class,
            'delivery_date' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class, 'client_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(LaserOrder::class, 'laser_order_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(LaserDeliveryNoteLine::class);
    }

    public function canBeDeleted(): bool
    {
        return $this->status->value === DeliveryStatus::DRAFT->value;
    }
}
