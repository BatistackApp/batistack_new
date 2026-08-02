<?php

namespace App\Models\RH;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'month',
        'year',
        'status',
        'total_amount',
        'advance_deducted',
    ];

    protected $casts = [
        'status' => \App\Enums\RH\ExpenseReportStatus::class,
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExpenseItem::class);
    }

    public function advances(): HasMany
    {
        return $this->hasMany(ExpenseAdvance::class);
    }

    public function getAmountToPayAttribute(): float
    {
        return max(0, $this->total_amount - $this->advance_deducted);
    }
}
