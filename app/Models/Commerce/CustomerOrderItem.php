<?php

namespace App\Models\Commerce;

use App\Models\Articles\Item;
use App\Models\Core\VatRate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'selling_price' => 'decimal:4',
        ];
    }
}
