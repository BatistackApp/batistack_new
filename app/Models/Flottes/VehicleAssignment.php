<?php

namespace App\Models\Flottes;

use App\Enums\Flottes\AssignmentStatus;
use App\Models\Chantiers\Chantier;
use App\Models\RH\Employee;
use App\Observers\Flottes\VehicleAssignmentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
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

    public function conditionReports(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VehicleConditionReport::class, 'vehicle_assignment_id');
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

    // ============ SCOPES ============

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', AssignmentStatus::ACTIVE);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', AssignmentStatus::COMPLETED);
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', AssignmentStatus::CANCELLED);
    }

    public function scopeByStatus(Builder $query, AssignmentStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByVehicle(Builder $query, int $vehicleId): Builder
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    public function scopeByEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByChantier(Builder $query, int $chantierId): Builder
    {
        return $query->where('chantier_id', $chantierId);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('started_at');
    }

    public function scopeBetweenDates(Builder $query, \DateTime $from, \DateTime $to): Builder
    {
        return $query->whereBetween('started_at', [$from, $to]);
    }

    public function scopeWithPassengers(Builder $query): Builder
    {
        return $query->with('passengers');
    }

    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereBetween('started_at', [now()->startOfMonth(), now()->endOfMonth()]);
    }

    // ============ METHODS ============

    public function getDistance(): float
    {
        if (! $this->end_odometer) {
            return 0;
        }

        return (float) $this->end_odometer - (float) $this->start_odometer;
    }

    public function getDurationInHours(): ?float
    {
        if (! $this->ended_at || ! $this->started_at) {
            return null;
        }

        return (float) $this->started_at->diffInHours($this->ended_at);
    }

    public function getCost(): float
    {
        $distance = $this->getDistance();
        $duration = $this->getDurationInHours() ?? 0;

        return ($this->vehicle->daily_rate * ceil($duration / 24)) +
            ($distance * $this->vehicle->km_rate);
    }

    public function isActive(): bool
    {
        return $this->status === AssignmentStatus::ACTIVE;
    }

    public function isCompleted(): bool
    {
        return $this->status === AssignmentStatus::COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === AssignmentStatus::CANCELLED;
    }

    public function isOverdue(): bool
    {
        if (! $this->ended_at || $this->status !== AssignmentStatus::ACTIVE) {
            return false;
        }

        return now()->gt($this->ended_at->addHours(2));
    }

    public function getPassengerCount(): int
    {
        return $this->passengers()->count();
    }

    public function getDisplayName(): string
    {
        return "{$this->vehicle->getDisplayName()} - {$this->employee->getFullName()}";
    }

    public function calculateEstimatedCost(): float
    {
        return $this->getCost();
    }

    public function addPassenger(Employee $employee): void
    {
        if (! $this->passengers()->where('employee_id', $employee->id)->exists()) {
            $this->passengers()->attach($employee->id);
        }
    }

    public function removePassenger(Employee $employee): void
    {
        $this->passengers()->detach($employee->id);
    }

    public function hasPassenger(Employee $employee): bool
    {
        return $this->passengers()->where('employee_id', $employee->id)->exists();
    }

    /**
     * Récupère les alertes météo du chantier qui chevauchent la durée de l'affectation.
     */
    public function getOverlappingWeatherAlerts()
    {
        if (! $this->chantier_id || ! $this->started_at) {
            return collect();
        }

        $endDate = $this->ended_at ?? now();

        return \App\Models\Chantiers\WeatherAlert::where('chantier_id', $this->chantier_id)
            ->where(function ($query) use ($endDate) {
                $query->whereBetween('alert_date', [$this->started_at->startOfDay(), $endDate->endOfDay()])
                    ->orWhere(function ($q) use ($endDate) {
                        $q->where('started_at', '<=', $endDate)
                          ->where(function ($q2) {
                              $q2->where('ended_at', '>=', $this->started_at)
                                 ->orWhereNull('ended_at');
                          });
                    });
            })
            ->get();
    }
}
