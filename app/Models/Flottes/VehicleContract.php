<?php

namespace App\Models\Flottes;

use App\Models\Tiers\ThirdParty;
use App\Observers\Flottes\VehicleContractObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([VehicleContractObserver::class])]
class VehicleContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'supplier_id',
        'type',
        'policy_number',
        'start_date',
        'end_date',
        'annual_cost_ht',
        'max_mileage',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class, 'supplier_id');
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'annual_cost_ht' => 'decimal:2',
        ];
    }

    // ============ SCOPES ============

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('end_date', '<', now());
    }

    public function scopeExpiringsSoon(Builder $query, int $daysThreshold = 30): Builder
    {
        return $query->where('end_date', '>=', now())
            ->where('end_date', '<=', now()->addDays($daysThreshold));
    }

    public function scopeByVehicle(Builder $query, int $vehicleId): Builder
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', 'ilike', "%{$type}%");
    }

    public function scopeBySupplier(Builder $query, int $supplierId): Builder
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeExpensive(Builder $query, float $minAmount = 2000): Builder
    {
        return $query->where('annual_cost_ht', '>=', $minAmount);
    }

    // ============ METHODS ============

    public function isActive(): bool
    {
        return $this->start_date <= now() && $this->end_date >= now();
    }

    public function isExpired(): bool
    {
        return $this->end_date < now();
    }

    public function isExpiringsSoon(int $daysThreshold = 30): bool
    {
        return $this->end_date >= now() &&
            $this->end_date <= now()->addDays($daysThreshold);
    }

    public function getDaysUntilExpiration(): ?int
    {
        if ($this->isExpired()) {
            return null;
        }

        return now()->diffInDays($this->end_date);
    }

    public function getDaysUntilRenewal(): ?int
    {
        return $this->getDaysUntilExpiration();
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'insurance' => 'Assurance',
            'maintenance' => 'Maintenance',
            'tracking' => 'Suivi GPS',
            'leasing' => 'Location financière',
            default => $this->type,
        };
    }

    public function getMonthlyRetenue(): float
    {
        return (float) $this->annual_cost_ht / 12;
    }

    public function getRemainingCost(): float
    {
        if ($this->isExpired()) {
            return 0;
        }
        $daysRemaining = $this->getDaysUntilExpiration() ?? 0;

        return ($this->annual_cost_ht / 365) * $daysRemaining;
    }

    public function sendRenewalReminder(): void
    {
        if ($this->isExpiringsSoon(15)) {
            // TODO: Implement notification
        }
    }
}
