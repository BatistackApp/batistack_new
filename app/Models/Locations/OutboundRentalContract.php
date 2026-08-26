<?php

namespace App\Models\Locations;

use App\Models\Chantiers\Chantier;
use App\Models\Core\Company;
use App\Models\Tiers\ThirdParty;
use App\Observers\Locations\OutboundRentalObserver;
use Database\Factories\Locations\OutboundRentalContractFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(OutboundRentalObserver::class)]
class OutboundRentalContract extends Model
{
    /** @use HasFactory<OutboundRentalContractFactory> */
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
        return $this->belongsTo(ThirdParty::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function chantier()
    {
        return $this->belongsTo(Chantier::class);
    }

    public function lines()
    {
        return $this->hasMany(OutboundRentalLine::class);
    }
}
