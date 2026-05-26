<?php

namespace App\Models\Commerce;

use App\Models\Articles\Item;
use App\Models\Core\VatRate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_invoice_id',
        'item_id',
        'name',
        'quantity',
        'price_unit',
        'vat_rate_id',
        'total_ht',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(CustomerInvoice::class, 'customer_invoice_id');
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
            'price_unit' => 'decimal:4',
            'total_ht' => 'decimal:4',
        ];
    }
}
