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
    ];

    public function customerQuote(): BelongsTo
    {
        return $this->belongsTo(CustomerQuote::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function vatRate(): BelongsTo
    {
        return $this->belongsTo(VatRate::class);
    }
}
