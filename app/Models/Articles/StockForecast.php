<?php

namespace App\Models\Articles;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockForecast extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'warehouse_id',
        'forecasted_at',
        'days_until_rupture',
        'daily_burn',
        'seasonality_coeff',
        'planned_needs',
        'available_stock',
        'suggested_qty',
        'suggested_order_date',
        'confidence',
    ];

    protected $casts = [
        'forecasted_at' => 'datetime',
        'suggested_order_date' => 'date',
        'daily_burn' => 'decimal:4',
        'seasonality_coeff' => 'decimal:4',
        'planned_needs' => 'decimal:4',
        'available_stock' => 'decimal:4',
        'suggested_qty' => 'decimal:4',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function scopeUrgent($query, int $days = 14)
    {
        return $query->whereNotNull('days_until_rupture')
            ->where('days_until_rupture', '<=', $days)
            ->where('suggested_qty', '>', 0);
    }
}
