<?php

namespace App\Models\RH;

use App\Enums\RH\ContractType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        ];
    }
}
