<?php

namespace App\Models\Articles;

use App\Enums\Articles\StockMouvementSource;
use App\Enums\Articles\StockMouvementType;
use App\Models\User;
use App\Observers\Articles\StockMouvementObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
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

    // ============================================
    // SCOPES
    // ============================================

    /**
     * Scope: Récupérer entrées (IN)
     */
    public function scopeIncoming(Builder $query): Builder
    {
        return $query->where('type', StockMouvementType::IN);
    }

    /**
     * Scope: Récupérer sorties (OUT)
     */
    public function scopeOutgoing(Builder $query): Builder
    {
        return $query->where('type', StockMouvementType::OUT);
    }

    /**
     * Scope: Récupérer par utilisateur
     */
    public function scopeByUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Scope: Mouvements récents (derniers N jours)
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Récupérer par stock
     */
    public function scopeByStock(Builder $query, Stock $stock): Builder
    {
        return $query->where('stock_id', $stock->id);
    }

    /**
     * Scope: Récupérer par source
     */
    public function scopeBySource(Builder $query, StockMouvementSource $source): Builder
    {
        return $query->where('reference_type', $source);
    }

    /**
     * Scope: Trier par date décroissante
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope: Trier par date croissante
     */
    public function scopeOldest(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'asc');
    }

    // ============================================
    // METHODS MÉTIER
    // ============================================

    /**
     * Vérifier si c'est une entrée
     */
    public function isIncoming(): bool
    {
        return $this->type === StockMouvementType::IN;
    }

    /**
     * Vérifier si c'est une sortie
     */
    public function isOutgoing(): bool
    {
        return $this->type === StockMouvementType::OUT;
    }

    /**
     * Obtenir le type en français
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            StockMouvementType::IN => 'Entrée',
            StockMouvementType::OUT => 'Sortie',
            default => 'Inconnu',
        };
    }

    /**
     * Obtenir la source en français
     */
    public function getSourceLabel(): string
    {
        return $this->reference_type?->name ?? 'Manuelle';
    }

    /**
     * Statique: Récupérer mouvements d'un article
     */
    public static function forItem(Item $item, int $limit = 50): StockMouvement|array
    {
        return static::whereHas('stock', fn ($q) => $q->where('item_id', $item->id))
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Statique: Total entrées d'un stock
     */
    public static function totalIncomingForStock(Stock $stock): float
    {
        return static::where('stock_id', $stock->id)
            ->incoming()
            ->sum('quantity_delta');
    }

    /**
     * Statique: Total sorties d'un stock
     */
    public static function totalOutgoingForStock(Stock $stock): float
    {
        return static::where('stock_id', $stock->id)
            ->outgoing()
            ->sum('quantity_delta');
    }
}
