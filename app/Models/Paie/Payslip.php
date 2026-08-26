<?php

namespace App\Models\Paie;

use App\Enums\Paie\PayslipStatus;
use App\Models\RH\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    use HasFactory;

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
        'overtime_amount',
        'gd_allowance_amount',
        'expense_reports_amount',
        'custom_bonuses',
        'meal_allowance_amount',
        'digiposte_document_id',
        'digiposte_status',
        'dsn_status',
        'dsn_submitted_at',
        'dsn_exported_at',
        'dsn_error_message',
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
        'overtime_amount' => 'decimal:2',
        'gd_allowance_amount' => 'decimal:2',
        'expense_reports_amount' => 'decimal:2',
        'meal_allowance_amount' => 'decimal:2',
        'custom_bonuses' => 'array',
        'status' => PayslipStatus::class,
        'dsn_submitted_at' => 'datetime',
        'dsn_exported_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function lines()
    {
        return $this->hasMany(PayslipLine::class);
    }

    public function advances()
    {
        return $this->hasMany(AdvancePayment::class);
    }

    public function dsnSubmissions()
    {
        return $this->belongsToMany(DsnSubmission::class, 'dsn_submission_lines')
            ->withPivot('status', 'error_message')
            ->withTimestamps();
    }
}
