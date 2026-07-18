<?php

namespace App\Models\Paie;

use Illuminate\Database\Eloquent\Model;

class AdvancePayment extends Model
{
    protected $fillable = [
        'employee_id',
        'amount',
        'request_date',
        'payment_date',
        'type',
        'status',
        'payslip_id',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'request_date' => 'date',
        'payment_date' => 'date',
        'status' => \App\Enums\Paie\AdvancePaymentStatus::class,
        'type' => \App\Enums\Paie\AdvancePaymentType::class,
    ];

    public function employee()
    {
        return $this->belongsTo(\App\Models\RH\Employee::class);
    }

    public function payslip()
    {
        return $this->belongsTo(Payslip::class);
    }
}
