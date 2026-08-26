<?php

namespace App\Models\Flottes;

use App\Models\Chantiers\Chantier;
use App\Models\RH\Employee;
use Illuminate\Database\Eloquent\Builder;
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
        'chantier_id',
        'co2_emission_kg',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    protected function casts(): array
    {
        return [
            'liters' => 'decimal:2',
            'cost_ht' => 'decimal:2',
            'odometer' => 'decimal:2',
            'purchased_at' => 'datetime',
            'is_suspicious' => 'boolean',
            'co2_emission_kg' => 'decimal:2',
        ];
    }

    // ============ SCOPES ============

    public function scopeByVehicle(Builder $query, int $vehicleId): Builder
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    public function scopeByEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeSuspicious(Builder $query): Builder
    {
        return $query->where('is_suspicious', true);
    }

    public function scopeNotSuspicious(Builder $query): Builder
    {
        return $query->where('is_suspicious', false);
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('purchased_at', '>=', now()->subDays($days))
            ->orderByDesc('purchased_at');
    }

    public function scopeBetweenDates(Builder $query, \DateTime $from, \DateTime $to): Builder
    {
        return $query->whereBetween('purchased_at', [$from, $to]);
    }

    public function scopeExpensive(Builder $query, float $minAmount = 100): Builder
    {
        return $query->where('cost_ht', '>=', $minAmount);
    }

    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereBetween('purchased_at', [now()->startOfMonth(), now()->endOfMonth()]);
    }

    public function scopeByStation(Builder $query, string $station): Builder
    {
        return $query->where('station_name', 'ilike', "%{$station}%");
    }

    // ============ METHODS ============

    public function getPricePerLiter(): float
    {
        return $this->liters > 0 ? (float) $this->cost_ht / (float) $this->liters : 0;
    }

    public function isSuspicious(): bool
    {
        return $this->is_suspicious === true;
    }

    public function markAsSuspicious(string $reason): void
    {
        $this->update([
            'is_suspicious' => true,
            'suspicion_reason' => $reason,
        ]);
    }

    public function markAsNormal(): void
    {
        $this->update([
            'is_suspicious' => false,
            'suspicion_reason' => null,
        ]);
    }

    public function getConsumptionRate(Vehicle $vehicle): ?float
    {
        $lastTransaction = self::byVehicle($vehicle->id)
            ->where('id', '<', $this->id)
            ->orderByDesc('odometer')
            ->first();

        if (! $lastTransaction) {
            return null;
        }

        $km = $this->odometer - $lastTransaction->odometer;
        if ($km <= 0) {
            return null;
        }

        return ($this->liters / $km) * 100; // liters per 100km
    }

    public function getDisplayName(): string
    {
        return "{$this->vehicle->getDisplayName()} - {$this->liters}L @ {$this->station_name}";
    }

    public function getCo2InTons(): float
    {
        return $this->co2_emission_kg ? (float) $this->co2_emission_kg / 1000 : 0.0;
    }
}
