<?php

namespace App\Models\Articles;

use App\Enums\Articles\StockMouvementSource;
use App\Enums\Articles\StockMouvementType;
use App\Models\User;
use App\Observers\Articles\StockMouvementObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([StockMouvementObserver::class])]
class StockMouvement extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_id',
        'user_id',
        'type',
        'quantity_before',
        'quantity_delta',
        'quantity_after',
        'description',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'type' => StockMouvementType::class,
        'reference_type' => StockMouvementSource::class,
        'quantity_before' => 'decimal:4',
        'quantity_delta' => 'decimal:4',
        'quantity_after' => 'decimal:4',
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
