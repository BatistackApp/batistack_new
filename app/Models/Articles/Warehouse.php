<?php

namespace App\Models\Articles;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'is_active',
        'vehicle_id',
        'chantier_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function vehicle(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Flottes\Vehicle::class);
    }

    public function chantier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Chantiers\Chantier::class);
    }

    public function isVirtualChantier(): bool
    {
        return $this->chantier_id !== null;
    }
    // ============================================
    // SCOPES
    // ============================================

    /**
     * Scope: Entrepôts actifs
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Entrepôts inactifs
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope: Rechercher par nom
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('name', 'like', "%{$term}%")
            ->orWhere('location', 'like', "%{$term}%");
    }

    /**
     * Scope: Trier par nom
     */
    public function scopeOrderByName(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('name', $direction);
    }

    // ============================================
    // METHODS MÉTIER
    // ============================================

    /**
     * Récupérer le stock total de l'entrepôt
     */
    public function getTotalStock(): float
    {
        return $this->stocks()->sum('quantity');
    }

    /**
     * Récupérer le stock d'un article
     */
    public function getStockForItem(Item $item): float
    {
        return $this->stocks()
            ->where('item_id', $item->id)
            ->first()?->quantity ?? 0;
    }

    /**
     * Récupérer les stocks bas
     */
    public function getLowStocks()
    {
        return $this->stocks()
            ->lowStock()
            ->with('item')
            ->get();
    }

    /**
     * Récupérer les stocks critiques
     */
    public function getCriticalStocks()
    {
        return $this->stocks()
            ->critical()
            ->with('item')
            ->get();
    }

    /**
     * Nombre de références en stock
     */
    public function getItemCount(): int
    {
        return $this->stocks()->count();
    }

    /**
     * Valeur totale du stock (au prix d'achat)
     */
    public function getTotalValue(): float
    {
        return $this->stocks()
            ->with('item')
            ->get()
            ->sum(fn ($s) => $s->quantity * ($s->item->purchase_price ?? 0));
    }

    /**
     * Statique: Récupérer par nom
     */
    public static function byName(string $name): ?self
    {
        return static::where('name', $name)->first();
    }
}
