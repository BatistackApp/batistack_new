<?php

namespace App\Models\Flottes;

use App\Models\Core\VatRate;
use App\Models\Tiers\ThirdParty;
use App\Observers\Flottes\VehicleMaintenanceObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([VehicleMaintenanceObserver::class])]
class VehicleMaintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'supplier_id',
        'vat_rate_id',
        'type',
        'description',
        'cost_ht',
        'odometer_at_maintenance',
        'performed_at',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class, 'supplier_id');
    }

    public function vatRate(): BelongsTo
    {
        return $this->belongsTo(VatRate::class);
    }

    protected function casts(): array
    {
        return [
            'cost_ht' => 'decimal:4',
            'odometer_at_maintenance' => 'decimal:2',
            'performed_at' => 'date',
        ];
    }

    // ============ SCOPES ============

    public function scopeByVehicle(Builder $query, int $vehicleId): Builder
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    public function scopeBySupplier(Builder $query, int $supplierId): Builder
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', 'ilike', "%{$type}%");
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('performed_at', '>=', now()->subDays($days))
            ->orderByDesc('performed_at');
    }

    public function scopeExpensive(Builder $query, float $minAmount = 1000): Builder
    {
        return $query->where('cost_ht', '>=', $minAmount);
    }

    public function scopeBetweenDates(Builder $query, \DateTime $from, \DateTime $to): Builder
    {
        return $query->whereBetween('performed_at', [$from, $to]);
    }

    public function scopeThisYear(Builder $query): Builder
    {
        return $query->whereYear('performed_at', now()->year);
    }

    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereMonth('performed_at', now()->month)
            ->whereYear('performed_at', now()->year);
    }

    public function scopeWithSupplier(Builder $query): Builder
    {
        return $query->with('supplier');
    }

    public function scopeCritical(Builder $query): Builder
    {
        return $query->whereIn('type', ['panne', 'accident', 'grosse réparation', 'carrosserie', 'moteur']);
    }

    // ============ METHODS ============

    public function getCostTTC(): float
    {
        $vat = $this->vatRate->rate ?? 0;

        return (float) $this->cost_ht * (1 + $vat / 100);
    }

    public function getVatAmount(): float
    {
        return $this->getCostTTC() - (float) $this->cost_ht;
    }

    public function isRecent(int $daysThreshold = 30): bool
    {
        return $this->performed_at->diffInDays(now()) <= $daysThreshold;
    }

    public function getDaysSinceMaintenance(): int
    {
        return $this->performed_at->diffInDays(now());
    }

    public function getKilometersSinceMaintenance(): ?float
    {
        $latestAssignment = $this->vehicle->assignments()
            ->where('status', 'completed')
            ->orderByDesc('ended_at')
            ->first();

        if (! $latestAssignment) {
            return null;
        }

        return $latestAssignment->end_odometer - $this->odometer_at_maintenance;
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'revision' => 'Révision',
            'repair' => 'Réparation',
            'inspection' => 'Contrôle technique',
            'oil_change' => 'Vidange',
            'tires' => 'Pneus',
            default => $this->type,
        };
    }

    public function isPreventiveMaintenance(): bool
    {
        return in_array($this->type, ['revision', 'inspection', 'oil_change']);
    }

    public function isEmergencyRepair(): bool
    {
        return $this->type == 'repair';
    }
}
