<?php

namespace App\Models\RH;

use App\Enums\RH\ContractType;
use App\Observers\RH\ContractObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([ContractObserver::class])]
class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'type',
        'start_date',
        'end_date',
        'job_title',
        'hourly_rate',
        'weekly_hours',
        'trial_end_date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    protected function casts(): array
    {
        return [
            'type' => ContractType::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'hourly_rate' => 'decimal:4',
            'trial_end_date' => 'date',
        ];
    }
}
