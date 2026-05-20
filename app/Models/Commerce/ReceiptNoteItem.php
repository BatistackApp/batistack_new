<?php

namespace App\Models\Commerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptNoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_note_id',
        'purchase_order_item_id',
        'quantity_received',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(ReceiptNote::class, 'receipt_note_id');
    }

    public function items(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'purchase_order_item_id');
    }

    protected function casts(): array
    {
        return [
            'quantity_received' => 'decimal:4',
        ];
    }
}
