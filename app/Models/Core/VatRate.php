<?php

namespace App\Models\Core;

use App\Observers\Core\VatRateObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy([VatRateObserver::class])]
class VatRate extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'rate', 'is_default', 'is_active'];

    protected $casts = [
        'rate' => 'decimal:4',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Scope: Récupérer seulement les TVA actives
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Récupérer la TVA par défaut
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope: Récupérer les TVA par taux
     */
    public function scopeByRate(Builder $query, float $rate): Builder
    {
        return $query->where('rate', $rate);
    }

    /**
     * Scope: Récupérer les TVA dans une plage de taux
     */
    public function scopeRateBetween(Builder $query, float $min, float $max): Builder
    {
        return $query->whereBetween('rate', [$min, $max]);
    }

    /**
     * Scope: Trier par taux croissant
     */
    public function scopeOrderByRate(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('rate', $direction);
    }

    /**
     * Statique: Récupérer la TVA par défaut
     */
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first();
    }

    /**
     * Statique: Récupérer la TVA standard (20% en France)
     */
    public static function getStandard(): ?self
    {
        return static::where('rate', 20)->first();
    }
}
