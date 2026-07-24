<?php

namespace App\Models\Interventions;

use App\Models\RH\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterventionWorker extends Model
{
    use HasFactory;

    protected $fillable = [
        'intervention_id',
        'employee_id',
        'hours_worked',
        'hourly_cost',
    ];

    protected $casts = [
        'hours_worked' => 'decimal:2',
        'hourly_cost' => 'decimal:2',
    ];

    public function intervention(): BelongsTo
    {
        return $this->belongsTo(Intervention::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
