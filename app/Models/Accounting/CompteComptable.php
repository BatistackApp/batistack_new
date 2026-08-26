<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompteComptable extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero',
        'libelle',
        'classe',
        'is_balance',
        'parent_id',
    ];

    protected $casts = [
        'is_balance' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function ecritures(): HasMany
    {
        return $this->hasMany(EcritureComptable::class, 'compte_numero', 'numero');
    }

    public function getClasseLabelAttribute(): string
    {
        return match ($this->classe) {
            1 => 'Comptes de ressources durables',
            2 => 'Immobilisations',
            3 => 'Stocks',
            4 => 'Tiers',
            5 => 'Financier',
            6 => 'Charges',
            7 => 'Produits',
            8 => 'Résultat',
            default => 'Inconnu',
        };
    }

    public function getFullLabelAttribute(): string
    {
        return $this->numero.' - '.$this->libelle;
    }

    public function scopeDeClasse($query, int $classe)
    {
        return $query->where('classe', $classe);
    }

    public function scopeBalances($query)
    {
        return $query->where('is_balance', true);
    }
}
