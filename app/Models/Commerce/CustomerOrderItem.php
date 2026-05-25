<?php

namespace App\Models\Commerce;

use App\Enums\Commerce\DeliveryStatus;
use App\Models\Articles\Item;
use App\Models\Core\VatRate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CustomerOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_order_id',
        'item_id',
        'name',
        'quantity',
        'selling_price',
        'vat_rate_id',
        'total_ht',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'customer_order_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function vatRate(): BelongsTo
    {
        return $this->belongsTo(VatRate::class);
    }

    /**
     * Un article de commande peut avoir plusieurs lignes de livraison.
     */
    public function deliveryNoteItems(): hasMany
    {
        return $this->hasMany(CustomerDeliveryNoteItem::class);
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'selling_price' => 'decimal:4',
            'total_ht' => 'decimal:4',
        ];
    }

    public function getQuantityUndeliveredAttribute(): float
    {
        $orderedQuantity = $this->quantity;

        // On charge uniquement les lignes de livraison dont le BL parent est "livré".
        $deliveredQuantity = $this->deliveryNoteItems()
            ->whereHas('customerDeliveryNote', function ($query) {
                $query->where('status', DeliveryStatus::DELIVERED);
            })
            ->sum('quantity_delivered');

        return (float) max(0, $orderedQuantity - $deliveredQuantity);
    }
}
