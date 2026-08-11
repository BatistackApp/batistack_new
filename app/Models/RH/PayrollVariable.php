<?php

namespace App\Models\RH;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollVariable extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_export_id',
        'employee_id',
        'base_hours',
        'worked_hours',
        'overtime_hours',
        'absence_days',
        'travel_allowances',
        'expense_reports_total',
        'estimated_gross_salary',
        'satd_deduction',
    ];

    protected function casts(): array
    {
        return [
            'base_hours' => 'decimal:2',
            'worked_hours' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'absence_days' => 'decimal:2',
            'travel_allowances' => 'decimal:2',
            'expense_reports_total' => 'decimal:2',
            'estimated_gross_salary' => 'decimal:2',
        ];
    }

    public function payrollExport(): BelongsTo
    {
        return $this->belongsTo(PayrollExport::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
