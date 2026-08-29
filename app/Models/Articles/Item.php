<?php

namespace App\Models\Articles;

use App\Enums\Articles\GhsPictogram;
use App\Enums\Articles\HazardCategory;
use App\Enums\Articles\ItemType;
use App\Models\Core\Unit;
use App\Models\Core\VatRate;
use App\Models\Tiers\ThirdParty;
use App\Observers\Articles\BarcodeObserver;
use App\Observers\Articles\ItemObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[ObservedBy([ItemObserver::class])]
#[ObservedBy([BarcodeObserver::class])]
class Item extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'reference',
        'barcode',
        'name',
        'description',
        'type',
        'purchase_price',
        'selling_price',
        'is_active',
        'is_sensitive',
        'hazard_category',
        'ghs_pictograms',
        'h_phrases',
        'p_phrases',
        'fds_updated_at',
        'unit_id',
        'vat_rate_id',
        'min_stock',
        'parent_id',
        'supplier_id',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function vatRate(): BelongsTo
    {
        return $this->belongsTo(VatRate::class);
    }

    /**
     * Pour les Ouvrages : Récupère les composants (Matériels + MO).
     */
    public function components(): HasMany
    {
        return $this->hasMany(ItemComposition::class, 'parent_item_id');
    }

    /**
     * Stock par dépôt.
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Item::class, 'parent_id');
    }

    public function stockMouvements(): HasManyThrough
    {
        return $this->hasManyThrough(StockMouvement::class, Stock::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class, 'supplier_id');
    }

    protected function casts(): array
    {
        return [
            'type' => ItemType::class,
            'hazard_category' => HazardCategory::class,
            'ghs_pictograms' => 'array',
            'h_phrases' => 'array',
            'p_phrases' => 'array',
            'fds_updated_at' => 'datetime',
            'purchase_price' => 'decimal:4',
            'selling_price' => 'decimal:4',
            'is_active' => 'boolean',
            'is_sensitive' => 'boolean',
        ];
    }

    // ============================================
    // SCOPES
    // ============================================

    /**
     * Scope: Récupérer articles actifs
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Récupérer articles inactifs
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope: Récupérer par type
     */
    public function scopeByType(Builder $query, ItemType $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Récupérer articles matériels
     */
    public function scopeMaterials(Builder $query): Builder
    {
        return $query->where('type', ItemType::STOCKABLE)->orWhere('type', ItemType::CONSUMABLE);
    }

    /**
     * Scope: Récupérer services
     */
    public function scopeServices(Builder $query): Builder
    {
        return $query->where('type', ItemType::LABOR);
    }

    /**
     * Scope: Récupérer ouvrages
     */
    public function scopeWorks(Builder $query): Builder
    {
        return $query->where('type', ItemType::WORK);
    }

    /**
     * Scope: Rechercher par référence ou nom
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('reference', 'like', "%{$term}%")
            ->orWhere('barcode', 'like', "%{$term}%")
            ->orWhere('name', 'like', "%{$term}%");
    }

    /**
     * Scope: Récupérer par unité
     */
    public function scopeByUnit(Builder $query, Unit $unit): Builder
    {
        return $query->where('unit_id', $unit->id);
    }

    /**
     * Scope: Récupérer par TVA
     */
    public function scopeByVatRate(Builder $query, VatRate $vatRate): Builder
    {
        return $query->where('vat_rate_id', $vatRate->id);
    }

    /**
     * Scope: Articles chers (> seuil)
     */
    public function scopeExpensive(Builder $query, float $threshold = 1000.00): Builder
    {
        return $query->where('selling_price', '>', $threshold);
    }

    /**
     * Scope: Articles pas chers
     */
    public function scopeCheap(Builder $query, float $threshold = 100.00): Builder
    {
        return $query->where('selling_price', '<', $threshold);
    }

    /**
     * Scope: Articles composés (ouvrages/kits)
     */
    public function scopeComposed(Builder $query): Builder
    {
        return $query->whereHas('components');
    }

    /**
     * Scope: Articles simples (sans composants)
     */
    public function scopeSimple(Builder $query): Builder
    {
        return $query->doesntHave('components');
    }

    /**
     * Scope: Trier par nom
     */
    public function scopeOrderByName(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('name', $direction);
    }

    /**
     * Scope: Trier par prix
     */
    public function scopeOrderByPrice(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('selling_price', $direction);
    }

    // ============================================
    // METHODS MÉTIER
    // ============================================

    /**
     * Vérifier si l'article est composé
     */
    public function isComposed(): bool
    {
        return $this->components()->exists();
    }

    /**
     * Vérifier si c'est une variante
     */
    public function isVariant(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Vérifier si c'est un ouvrage
     */
    public function isWork(): bool
    {
        return $this->type === ItemType::WORK;
    }

    /**
     * Vérifier si c'est un service
     */
    public function isService(): bool
    {
        return $this->type === ItemType::LABOR;
    }

    /**
     * Vérifier si c'est un matériel
     */
    public function isConsommable(): bool
    {
        return $this->type === ItemType::CONSUMABLE;
    }

    /**
     * Vérifier si c'est un matériel
     */
    public function isStockable(): bool
    {
        return $this->type === ItemType::STOCKABLE;
    }

    /**
     * Vérifier si l'article possède une fiche de sécurité / présente un danger.
     */
    public function isHazardous(): bool
    {
        return $this->hazard_category !== null
            || ! empty($this->ghs_pictograms)
            || ! empty($this->h_phrases);
    }

    /**
     * Liste des pictogrammes CLP résolus en énum.
     */
    public function pictograms(): array
    {
        return collect($this->ghs_pictograms ?? [])
            ->map(fn (string $value) => GhsPictogram::tryFrom($value))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Récupérer le stock total disponible
     */
    public function getTotalStock(): float
    {
        return $this->stocks()->sum('quantity');
    }

    /**
     * Récupérer le stock disponible dans un entrepôt (somme de tous les emplacements)
     */
    public function getStockInWarehouse(Warehouse $warehouse): float
    {
        return $this->stocks()
            ->where('warehouse_id', $warehouse->id)
            ->sum('quantity');
    }

    /**
     * Vérifier si le stock est bas
     */
    public function isLowStock(): bool
    {
        return $this->stocks()->get()->sum('quantity') <= $this->min_stock;
    }

    /**
     * Récupérer la marge bénéficiaire
     */
    public function getMargin(): float
    {
        if ($this->purchase_price == 0) {
            return 0;
        }

        return (($this->selling_price - $this->purchase_price) / $this->purchase_price) * 100;
    }

    /**
     * Récupérer le coût TTC
     */
    public function getPriceTTC(): float
    {
        $vatRate = $this->vatRate ? $this->vatRate->rate / 100 : 0;

        return $this->selling_price * (1 + $vatRate);
    }

    /**
     * Statique: Récupérer par référence
     */
    public static function byReference(string $reference): ?self
    {
        return static::where('reference', $reference)->first();
    }

    /**
     * Statique: Vérifier si référence existe
     */
    public static function referenceExists(string $reference): bool
    {
        return static::where('reference', $reference)->exists();
    }
}
