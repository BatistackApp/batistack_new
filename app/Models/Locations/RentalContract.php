<?php

namespace App\Models\Locations;

use App\Enums\Locations\RentalBillingPeriod;
use App\Enums\Locations\RentalStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Tiers\ThirdParty;
use Carbon\Carbon;
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
        'expected_end_date' => 'date',
        'next_billing_date' => 'date',
        'status' => RentalStatus::class,
        'billing_period' => RentalBillingPeriod::class,
        'daily_cost_ht' => 'decimal:2',
        'daily_penalty_rate' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
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

    public function conditionReports(): HasMany
    {
        return $this->hasMany(RentalConditionReport::class);
    }

    /**
     * Calcule la prochaine date de facturation selon la période de facturation.
     */
    public function calculateNextBillingDate(?Carbon $from = null): Carbon
    {
        $from = $from ?? $this->next_billing_date ?? $this->start_date;

        return match ($this->billing_period->value) {
            'daily' => Carbon::parse($from)->addDay(),
            'weekly' => Carbon::parse($from)->addWeek(),
            'monthly' => Carbon::parse($from)->addMonth(),
            'yearly' => Carbon::parse($from)->addYear(),
            default => Carbon::parse($from)->addMonth(),
        };
    }
}
