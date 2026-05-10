<?php

namespace App\Models\Tiers;

use App\Enums\Tiers\ThirdPartyType;
use App\Observers\Tiers\ThirdPartyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    ];

    protected function casts(): array
    {
        return [
            'type' => ThirdPartyType::class,
            'is_active' => 'boolean',
            'last_siren_sync_at' => 'datetime',
            'credit_limit' => 'decimal:2',
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

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_third_party');
    }
}
