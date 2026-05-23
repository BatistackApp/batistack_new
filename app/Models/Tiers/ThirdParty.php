<?php

namespace App\Models\Tiers;

use App\Enums\Tiers\ThirdPartyType;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerQuote;
use App\Observers\Tiers\ThirdPartyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'credit_limit',
        'last_siren_sync_at',
        'compliant_status',
        'iban',
        'bic',
    ];

    protected function casts(): array
    {
        return [
            'type' => ThirdPartyType::class,
            'is_active' => 'boolean',
            'last_siren_sync_at' => 'datetime',
            'credit_limit' => 'decimal:2',
            'compliant_status' => 'array',
        ];
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
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
        return $this->hasMany(CustomerQuote::class, 'id', 'client_id');
    }

    public function getComplianceStatusLabelAttribute(): string
    {
        if ($this->compliant_status['compliant']) {
            return 'Conforme';
        } else {
            return 'Alerte';
        }
    }
}
