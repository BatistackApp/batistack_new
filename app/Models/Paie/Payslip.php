<?php

namespace App\Models\Paie;

use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    protected $fillable = [
        'employee_id',
        'period',
        'base_hours',
        'overtime_hours',
        'hourly_rate',
        'gross_salary',
        'net_social',
        'taxable_net',
        'pas_rate',
        'pas_amount',
        'net_payable',
        'net_paid',
        'employer_cost',
        'status',
        'pdf_path',
        'payment_date',
    ];

    protected $casts = [
        'base_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'hourly_rate' => 'decimal:4',
        'gross_salary' => 'decimal:2',
        'net_social' => 'decimal:2',
        'taxable_net' => 'decimal:2',
        'pas_rate' => 'decimal:4',
        'pas_amount' => 'decimal:2',
        'net_payable' => 'decimal:2',
        'net_paid' => 'decimal:2',
        'employer_cost' => 'decimal:2',
        'payment_date' => 'date',
        'status' => \App\Enums\Paie\PayslipStatus::class,
    ];

    public function employee()
    {
        return $this->belongsTo(\App\Models\RH\Employee::class);
    }

    public function lines()
    {
        return $this->hasMany(PayslipLine::class);
    }

    public function advances()
    {
        return $this->hasMany(AdvancePayment::class);
    }
}
