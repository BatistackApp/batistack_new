<?php

namespace App\Models\Tiers;

use App\Enums\Tiers\AddressType;
use App\Observers\Tiers\AddressObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([AddressObserver::class])]
class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'third_party_id',
        'type',
        'street',
        'complement',
        'zip_code',
        'city',
        'country',
        'latitude',
        'longitude',
        'is_default',
    ];

    public function thirdParty(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class);
    }

    protected function casts(): array
    {
        return [
            'type' => AddressType::class,
            'is_default' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function getFullAddressAttribute(): string
    {
        return "{$this->street}, {$this->zip_code} {$this->city}";
    }

    // ============================================
    // SCOPES
    // ============================================


    /**
     * Scope: Récupérer l'adresse par défaut
     */
    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope: Récupérer les adresses de facturation
     */
    public function scopeBilling(Builder $query): Builder
    {
        return $query->where('type', AddressType::BILLING);
    }

    /**
     * Scope: Récupérer les adresses de livraison
     */
    public function scopeDelivery(Builder $query): Builder
    {
        return $query->where('type', AddressType::DELIVERY);
    }

    /**
     * Scope: Récupérer les adresses par type
     */
    public function scopeByType(Builder $query, AddressType $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Rechercher par ville
     */
    public function scopeByCity(Builder $query, string $city): Builder
    {
        return $query->where('city', 'like', "%{$city}%");
    }

    /**
     * Scope: Rechercher par code postal
     */
    public function scopeByZipCode(Builder $query, string $zipCode): Builder
    {
        return $query->where('zip_code', $zipCode);
    }

    /**
     * Scope: Récupérer les adresses géocodées
     */
    public function scopeGeocoded(Builder $query): Builder
    {
        return $query->whereNotNull('latitude')->whereNotNull('longitude');
    }

    /**
     * Scope: Trier par type
     */
    public function scopeOrderByType(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('type', $direction);
    }
}
