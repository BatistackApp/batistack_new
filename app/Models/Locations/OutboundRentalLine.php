<?php

namespace App\Models\Locations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutboundRentalLine extends Model
{
    /** @use HasFactory<\Database\Factories\Locations\OutboundRentalLineFactory> */
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
        return $this->belongsTo(\App\Models\Immobilisation\FixedAsset::class, 'fixed_asset_id');
    }
}
