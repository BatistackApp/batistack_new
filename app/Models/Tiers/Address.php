<?php

namespace App\Models\Tiers;

use App\Enums\Tiers\AddressType;
use App\Observers\Tiers\AddressObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
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
}
