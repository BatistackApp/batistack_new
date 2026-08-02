<?php

namespace App\Models\RH;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'status' => \App\Enums\RH\ExpenseAdvanceStatus::class,
    ];

    public function employee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function expenseReport(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ExpenseReport::class);
    }
}
