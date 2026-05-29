<?php

namespace App\Models\Articles;

use App\Observers\Articles\StockObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([StockObserver::class])]
class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'warehouse_id',
        'quantity',
        'min_threshold',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Mouvements de stock pour ce stock
     */
    public function mouvements(): HasMany
    {
        return $this->hasMany(StockMouvement::class);
    }

    // ============================================
    // SCOPES
    // ============================================

    /**
     * Scope: Stock bas (sous seuil)
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('quantity', '<=', 'min_threshold');
    }

    /**
     * Scope: Stock critique (à 0 ou négatif)
     */
    public function scopeCritical(Builder $query): Builder
    {
        return $query->where('quantity', '<=', 0);
    }

    /**
     * Scope: Stock sain (au-dessus du seuil)
     */
    public function scopeHealthy(Builder $query): Builder
    {
        return $query->whereColumn('quantity', '>', 'min_threshold');
    }

    /**
     * Scope: Récupérer par entrepôt
     */
    public function scopeByWarehouse(Builder $query, Warehouse $warehouse): Builder
    {
        return $query->where('warehouse_id', $warehouse->id);
    }

    /**
     * Scope: Récupérer par article
     */
    public function scopeByItem(Builder $query, Item $item): Builder
    {
        return $query->where('item_id', $item->id);
    }

    /**
     * Scope: Nécessite réapprovisionnement
     */
    public function scopeNeedsReorder(Builder $query): Builder
    {
        return $query->whereColumn('quantity', '<=', 'min_threshold');
    }

    /**
     * Scope: Trier par quantity
     */
    public function scopeOrderByQuantity(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('quantity', $direction);
    }

    // ============================================
    // METHODS MÉTIER
    // ============================================

    /**
     * Vérifier si le stock est bas
     */
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->min_threshold;
    }

    /**
     * Vérifier si le stock est critique
     */
    public function isCritical(): bool
    {
        return $this->quantity <= 0;
    }

    /**
     * Vérifier si le stock est sain
     */
    public function isHealthy(): bool
    {
        return $this->quantity > $this->min_threshold;
    }

    /**
     * Augmenter le stock
     */
    public function increase(float $quantity, string $description = ''): StockMouvement
    {
        $before = $this->quantity;
        $this->quantity += $quantity;
        $this->save();

        return StockMouvement::create([
            'stock_id' => $this->id,
            'user_id' => auth()->id(),
            'type' => 'in',
            'quantity_before' => $before,
            'quantity_delta' => $quantity,
            'quantity_after' => $this->quantity,
            'description' => $description ?: 'Augmentation manuelle du stock',
        ]);
    }

    /**
     * Diminuer le stock
     */
    public function decrease(float $quantity, string $description = ''): StockMouvement
    {
        $before = $this->quantity;
        $this->quantity -= $quantity;
        $this->save();

        return StockMouvement::create([
            'stock_id' => $this->id,
            'user_id' => auth()->id(),
            'type' => 'out',
            'quantity_before' => $before,
            'quantity_delta' => -$quantity,
            'quantity_after' => $this->quantity,
            'description' => $description ?: 'Diminution manuelle du stock',
        ]);
    }

    /**
     * Récupérer les mouvements récents
     */
    public function getRecentMovements(int $limit = 10): Collection
    {
        return $this->mouvements()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Récupérer le dernier mouvement
     */
    public function getLastMovement(): ?StockMouvement
    {
        return $this->mouvements()
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
