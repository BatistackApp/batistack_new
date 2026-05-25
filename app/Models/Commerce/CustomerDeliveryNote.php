<?php

namespace App\Models\Commerce;

use App\Enums\Commerce\DeliveryStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Observers\Commerce\CustomerDeliveryNoteObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([CustomerDeliveryNoteObserver::class])]
class CustomerDeliveryNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'chantier_id',
        'customer_order_id',
        'reference',
        'status',
        'delivery_date',
        'responsable_id',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class, 'client_id');
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'customer_order_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerDeliveryNoteItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    protected function casts(): array
    {
        return [
            'status' => DeliveryStatus::class,
            'delivery_date' => 'date',
        ];
    }
}
