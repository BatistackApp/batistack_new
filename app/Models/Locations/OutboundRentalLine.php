<?php

namespace App\Models\Locations;

use App\Models\Immobilisation\FixedAsset;
use App\Observers\Locations\OutboundRentalLineObserver;
use Database\Factories\Locations\OutboundRentalLineFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(OutboundRentalLineObserver::class)]
class OutboundRentalLine extends Model
{
    /** @use HasFactory<OutboundRentalLineFactory> */
    use HasFactory;

    protected $fillable = [
        'outbound_rental_contract_id',
        'fixed_asset_id',
        'daily_rate',
    ];

    protected $casts = [
        'daily_rate' => 'decimal:2',
    ];

    public function contract()
    {
        return $this->belongsTo(OutboundRentalContract::class, 'outbound_rental_contract_id');
    }

    public function fixedAsset()
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id');
    }
}
