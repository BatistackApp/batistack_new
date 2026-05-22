<?php

namespace App\Models\Commerce;

use App\Models\Articles\Item;
use App\Models\Core\VatRate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerQuoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_quote_id',
        'item_id',
        'lot_label',
        'name',
        'quantity',
        'purchase_price',
        'selling_price',
        'vat_rate_id',
        'total_ht',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(CustomerQuote::class, 'customer_quote_id');
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
            'purchase_price' => 'decimal:4',
            'selling_price' => 'decimal:4',
            'total_ht'  => 'decimal:4',
        ];
    }
}
