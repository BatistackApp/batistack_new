<?php

namespace App\Models\Paie;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayslipLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'payslip_id',
        'category',
        'label',
        'base',
        'employee_rate',
        'employer_rate',
        'employee_amount',
        'employer_amount',
    ];

    protected $casts = [
        'base' => 'decimal:2',
        'employee_rate' => 'decimal:4',
        'employer_rate' => 'decimal:4',
        'employee_amount' => 'decimal:2',
        'employer_amount' => 'decimal:2',
    ];

    public function payslip()
    {
        return $this->belongsTo(Payslip::class);
    }
}
