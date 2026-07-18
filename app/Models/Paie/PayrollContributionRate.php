<?php

namespace App\Models\Paie;

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
    ];

    protected $casts = [
        'employee_rate' => 'decimal:4',
        'employer_rate' => 'decimal:4',
        'is_deductible' => 'boolean',
        'is_fiscally_reintegrated' => 'boolean',
        'base_formula' => \App\Enums\Paie\ContributionBaseFormula::class,
    ];

    public function profile()
    {
        return $this->belongsTo(PayrollContributionProfile::class, 'payroll_contribution_profile_id');
    }
}
