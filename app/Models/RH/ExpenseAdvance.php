<?php

namespace App\Models\RH;

use App\Enums\RH\ExpenseAdvanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseAdvance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'amount',
        'request_date',
        'reason',
        'status',
        'expense_report_id',
        'payment_date',
    ];

    protected $casts = [
        'request_date' => 'date',
        'payment_date' => 'datetime',
        'status' => ExpenseAdvanceStatus::class,
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function expenseReport(): BelongsTo
    {
        return $this->belongsTo(ExpenseReport::class);
    }
}
