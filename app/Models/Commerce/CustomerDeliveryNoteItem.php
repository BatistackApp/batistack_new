<?php

namespace App\Models\Commerce;

use App\Models\Articles\Item;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerDeliveryNoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_delivery_note_id',
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

    protected function casts(): array
    {
        return [
            'quantity_delivered' => 'decimal:4',
        ];
    }
}
