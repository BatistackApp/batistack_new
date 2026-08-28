<?php

namespace App\Models\Paie;

use App\Enums\Paie\ContributionBaseFormula;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class PayrollContributionRate extends Model
{
    protected $fillable = [
        'payroll_contribution_profile_id',
        'category',
        'label',
        'employee_rate',
        'employer_rate',
        'base_formula',
        'is_deductible',
        'is_fiscally_reintegrated',
        'valid_from',
        'valid_to',
    ];

    protected $casts = [
        'employee_rate' => 'decimal:4',
        'employer_rate' => 'decimal:4',
        'is_deductible' => 'boolean',
        'is_fiscally_reintegrated' => 'boolean',
        'base_formula' => ContributionBaseFormula::class,
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    public function scopeValidAt($query, CarbonInterface $date)
    {
        return $query->where(function ($q) use ($date) {
            $q->whereNull('valid_from')
                ->orWhere('valid_from', '<=', $date);
        })->where(function ($q) use ($date) {
            $q->whereNull('valid_to')
                ->orWhere('valid_to', '>=', $date);
        });
    }

    public function profile()
    {
        return $this->belongsTo(PayrollContributionProfile::class, 'payroll_contribution_profile_id');
    }
}
