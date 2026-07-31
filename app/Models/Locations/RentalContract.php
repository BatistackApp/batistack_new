<?php

namespace App\Models\Locations;

use App\Enums\Locations\RentalBillingPeriod;
use App\Enums\Locations\RentalStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentalContract extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => RentalStatus::class,
        'billing_period' => RentalBillingPeriod::class,
        'daily_cost_ht' => 'decimal:2',
        'supplier_score' => 'integer',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class, 'supplier_id');
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class, 'chantier_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(RentalContractLine::class);
    }
}
