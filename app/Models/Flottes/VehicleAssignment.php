<?php

namespace App\Models\Flottes;

use App\Enums\Flottes\AssignmentStatus;
use App\Models\Chantiers\Chantier;
use App\Models\RH\Employee;
use App\Observers\Flottes\VehicleAssignmentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[ObservedBy([VehicleAssignmentObserver::class])]
class VehicleAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'chantier_id',
        'employee_id',
        'started_at',
        'ended_at',
        'start_odometer',
        'end_odometer',
        'status',
        'purpose',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function passengers(): BelongsToMany
    {
        return $this->belongsToMany(
            Employee::class,
            'vehicle_assignment_passengers',
            'vehicle_assignment_id',
            'employee_id'
        )->withTimestamps();
    }

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'start_odometer' => 'decimal:2',
            'end_odometer' => 'decimal:2',
            'status' => AssignmentStatus::class,
        ];
    }
}
