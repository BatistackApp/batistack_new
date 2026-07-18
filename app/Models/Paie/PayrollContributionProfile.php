<?php

namespace App\Models\Paie;

use Illuminate\Database\Eloquent\Model;

class PayrollContributionProfile extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'meal_allowance_amount',
    ];

    public function rates()
    {
        return $this->hasMany(PayrollContributionRate::class);
    }
}
