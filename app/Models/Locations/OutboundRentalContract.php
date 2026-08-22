<?php

namespace App\Models\Locations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy(\App\Observers\Locations\OutboundRentalObserver::class)]
class OutboundRentalContract extends Model
{
    /** @use HasFactory<\Database\Factories\Locations\OutboundRentalContractFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'third_party_id',
        'chantier_id',
        'reference',
        'status',
        'billing_period',
        'start_date',
        'expected_end_date',
        'actual_end_date',
        'daily_penalty_rate',
        'last_invoice_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'expected_end_date' => 'date',
        'actual_end_date' => 'date',
        'daily_penalty_rate' => 'decimal:2',
    ];

    public function thirdParty()
    {
        return $this->belongsTo(\App\Models\Tiers\ThirdParty::class);
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Core\Company::class);
    }

    public function chantier()
    {
        return $this->belongsTo(\App\Models\Chantiers\Chantier::class);
    }

    public function lines()
    {
        return $this->hasMany(OutboundRentalLine::class);
    }
}
