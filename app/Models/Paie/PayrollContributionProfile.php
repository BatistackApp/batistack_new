<?php

namespace App\Models\Paie;

use Illuminate\Database\Eloquent\Model;

class PayrollContributionProfile extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
    ];

    public function rates()
    {
        return $this->hasMany(PayrollContributionRate::class);
    }
}
