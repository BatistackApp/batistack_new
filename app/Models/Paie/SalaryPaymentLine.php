<?php

namespace App\Models\Paie;

use App\Enums\Paie\SalaryPaymentStatus;
use App\Models\RH\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class SalaryPaymentLine extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'run_id',
        'payslip_id',
        'employee_id',
        'amount',
        'status',
        'bridge_payment_request_id',
        'consent_url',
        'bank_reference',
        'end_to_end_id',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => SalaryPaymentStatus::class,
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(SalaryPaymentRun::class, 'run_id');
    }

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['amount', 'status', 'bridge_payment_request_id', 'consent_url', 'bank_reference', 'error'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
