<?php

namespace App\Models\Commerce;

use App\Enums\Commerce\DeliveryStatus;
use App\Models\Articles\Warehouse;
use App\Observers\Commerce\ReceiptNoteObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([ReceiptNoteObserver::class])]
class ReceiptNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'warehouse_id',
        'reference',
        'status',
        'received_at',
        'quality_rating',
        'has_litigation',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReceiptNoteItem::class);
    }

    protected function casts(): array
    {
        return [
            'status' => DeliveryStatus::class,
            'received_at' => 'datetime',
            'quality_rating' => 'integer',
            'has_litigation' => 'boolean',
        ];
    }
}
