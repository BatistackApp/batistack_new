<?php

namespace App\Models\Commerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerSituationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_situation_id',
        'customer_order_item_id',
        'progress_percentage', // Correction de la faute de frappe
        'amount_ht',
    ];

    public function situation(): BelongsTo
    {
        return $this->belongsTo(CustomerSituation::class, 'customer_situation_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(CustomerOrderItem::class, 'customer_order_item_id');
    }

    protected function casts(): array
    {
        return [
            'progress_percentage' => 'integer',
            'amount_ht' => 'decimal:2',
        ];
    }
}
