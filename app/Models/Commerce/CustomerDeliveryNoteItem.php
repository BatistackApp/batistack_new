<?php

namespace App\Models\Commerce;

use App\Enums\Commerce\DeliveryStatus;
use App\Models\Articles\Item;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CustomerDeliveryNoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_delivery_note_id',
        'customer_order_item_id',
        'item_id',
        'quantity_delivered',
    ];

    public function customerDeliveryNote(): BelongsTo
    {
        return $this->belongsTo(CustomerDeliveryNote::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(CustomerOrderItem::class, 'customer_order_item_id');
    }

    public function getQuantityOrderedAttribute(): float
    {
        return $this->orderItem->quantity;
    }

    public function getQuantityInStockAttribute(): float|string
    {
        return $this->orderItem->item->stocks()?->first()->quantity ?? 'N/A';
    }


    protected function casts(): array
    {
        return [
            'quantity_delivered' => 'decimal:4',
        ];
    }
}
