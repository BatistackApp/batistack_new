<?php

namespace App\Models\Articles;

use App\Enums\Articles\InventoryCycleLineStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryCycleLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_cycle_id',
        'item_id',
        'theoretical_quantity',
        'counted_quantity',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => InventoryCycleLineStatus::class,
            'theoretical_quantity' => 'decimal:4',
            'counted_quantity' => 'decimal:4',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(InventoryCycle::class, 'inventory_cycle_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
