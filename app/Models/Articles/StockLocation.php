<?php

namespace App\Models\Articles;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_id',
        'location_code',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function scopeByStock($query, Stock $stock)
    {
        return $query->where('stock_id', $stock->id);
    }

    public function scopeHasQuantity($query)
    {
        return $query->where('quantity', '>', 0);
    }
}
