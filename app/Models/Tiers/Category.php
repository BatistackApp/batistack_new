<?php

namespace App\Models\Tiers;

use App\Observers\Tiers\CategoryObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy([CategoryObserver::class])]
class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
        'icon',
    ];

    /**
     * Scope: Rechercher par nom
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('name', 'like', "%{$term}%");
    }

    /**
     * Scope: Trier alphabétiquement
     */
    public function scopeOrderByName(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('name', $direction);
    }

    /**
     * Statique: Récupérer une catégorie par nom
     */
    public static function byName(string $name): ?self
    {
        return static::where('name', $name)->first();
    }

    /**
     * Statique: Vérifier si une catégorie existe par nom
     */
    public static function nameExists(string $name): bool
    {
        return static::where('name', $name)->exists();
    }
}
