<?php

namespace App\Models\Flottes;

use App\Models\RH\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'employee_id',
        'liters',
        'cost_ht',
        'odometer',
        'purchased_at',
        'station_name',
        'is_suspicious',
        'suspicion_reason',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    protected function casts(): array
    {
        return [
            'liters' => 'decimal:2',
            'cost_ht' => 'decimal:2',
            'odometer' => 'decimal:2',
            'purchased_at' => 'datetime',
            'is_suspicious' => 'boolean',
        ];
    }
}
