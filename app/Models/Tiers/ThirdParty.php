<?php

namespace App\Models\Tiers;

use App\Enums\Tiers\LegalStatus;
use App\Enums\Tiers\ThirdPartyType;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerQuote;
use App\Models\Commerce\Payment;
use App\Models\Interventions\ClientEquipment;
use App\Observers\Tiers\ThirdPartyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LaravelIdea\Helper\App\Models\Tiers\_IH_Address_C;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[ObservedBy([ThirdPartyObserver::class])]
class ThirdParty extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name',
        'legal_name',
        'type',
        'siren',
        'siret',
        'vat_number',
        'email',
        'phone',
        'website',
        'is_active',
        'payment_terms_days',
        'delivery_delay_days',
        'credit_limit',
        'last_siren_sync_at',
        'last_legal_sync_at',
        'compliant_status',
        'iban',
        'bic',
        'supplier_score',
        'financial_status',
        'legal_status',
        'financial_data',
        'last_financial_sync_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => ThirdPartyType::class,
            'is_active' => 'boolean',
            'last_siren_sync_at' => 'datetime',
            'last_financial_sync_at' => 'datetime',
            'credit_limit' => 'decimal:2',
            'compliant_status' => 'array',
            'financial_data' => 'array',
            'legal_status' => LegalStatus::class,
            'supplier_score' => 'integer',
            'delivery_delay_days' => 'integer',
        ];
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ThirdPartyDocument::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function primaryContact(): HasOne
    {
        return $this->hasOne(Contact::class)->where('is_primary', true);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_third_party');
    }

    public function chantiers(): BelongsToMany
    {
        return $this->belongsToMany(Chantier::class, 'chantier_subcontractors');
    }

    public function customerQuotes(): HasMany
    {
        return $this->hasMany(CustomerQuote::class, 'client_id');
    }

    public function customerInvoices(): HasMany
    {
        return $this->hasMany(CustomerInvoice::class, 'client_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'third_party_id');
    }

    public function clientEquipments(): HasMany
    {
        return $this->hasMany(ClientEquipment::class);
    }

    public function getComplianceStatusLabelAttribute(): string
    {
        if ($this->compliant_status['compliant']) {
            return 'Conforme';
        } else {
            return 'Alerte';
        }
    }

    // ============================================
    // SCOPES
    // ============================================

    /**
     * Scope: Récupérer seulement les tiers actifs
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Récupérer seulement les tiers inactifs
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope: Récupérer les clients
     */
    public function scopeClients(Builder $query): Builder
    {
        return $query->where('type', ThirdPartyType::CLIENT);
    }

    /**
     * Scope: Récupérer les fournisseurs
     */
    public function scopeSuppliers(Builder $query): Builder
    {
        return $query->where('type', ThirdPartyType::SUPPLIER);
    }

    /**
     * Scope: Récupérer les sous-traitants
     */
    public function scopeSubcontractors(Builder $query): Builder
    {
        return $query->where('type', ThirdPartyType::SUBCONTRACTOR);
    }

    /**
     * Scope: Récupérer par catégorie
     */
    public function scopeByCategory(Builder $query, Category $category): Builder
    {
        return $query->whereHas('categories', function ($q) use ($category) {
            $q->where('category_id', $category->id);
        });
    }

    /**
     * Scope: Récupérer les conformes
     */
    public function scopeCompliant(Builder $query): Builder
    {
        return $query->where('compliant_status->compliant', true);
    }

    /**
     * Scope: Récupérer les non-conformes
     */
    public function scopeNonCompliant(Builder $query): Builder
    {
        return $query->where('compliant_status->compliant', false);
    }

    /**
     * Scope: Rechercher par nom ou email
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('name', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%")
            ->orWhere('legal_name', 'like', "%{$term}%")
            ->orWhere('siret', 'like', "%{$term}%")
            ->orWhere('siren', 'like', "%{$term}%");
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
     * Récupérer l'adresse principale
     */
    public function getMainAddress(): ?Address
    {
        return $this->addresses()
            ->where('is_default', true)
            ->first() ?? $this->addresses()->first();
    }

    /**
     * Récupérer le contact principal
     */
    public function getPrimaryContact(): ?Contact
    {
        return $this->primaryContact ?? $this->contacts()->first();
    }

    /**
     * Récupérer les adresses actives
     */
    public function getActiveAddresses(): Collection|array|_IH_Address_C
    {
        return $this->addresses()->where('is_active', true)->get();
    }

    /**
     * Vérifier si c'est un client
     */
    public function isClient(): bool
    {
        return $this->type === ThirdPartyType::CLIENT;
    }

    /**
     * Vérifier si c'est un fournisseur
     */
    public function isSupplier(): bool
    {
        return $this->type === ThirdPartyType::SUPPLIER;
    }

    /**
     * Vérifier si c'est un sous-traitant
     */
    public function isSubcontractor(): bool
    {
        return $this->type === ThirdPartyType::SUBCONTRACTOR;
    }

    /**
     * Vérifier si conforme
     */
    public function isCompliant(): bool
    {
        return $this->compliant_status['compliant'] ?? false;
    }

    /**
     * Vérifier si a de la conformité
     */
    public function hasCompliance(): bool
    {
        return $this->compliant_status && ! empty($this->compliant_status);
    }

    /**
     * Récupérer le nombre d'adresses
     */
    public function getAddressCount(): int
    {
        return $this->addresses()->count();
    }

    /**
     * Récupérer le nombre de contacts
     */
    public function getContactCount(): int
    {
        return $this->contacts()->count();
    }

    /**
     * Statique: Récupérer par SIRET
     */
    public static function bySiret(string $siret): ?self
    {
        return static::where('siret', $siret)->first();
    }

    /**
     * Statique: Récupérer par SIREN
     */
    public static function bySiren(string $siren): ?self
    {
        return static::where('siren', $siren)->first();
    }
}
