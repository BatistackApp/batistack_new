<?php

namespace App\Models\Flottes;

use App\Enums\Flottes\AssignmentStatus;
use App\Enums\Flottes\VehicleStatus;
use App\Enums\Flottes\VehicleType;
use App\Models\Chantiers\ResourceAllocation;
use App\Models\Immobilisation\FixedAsset;
use App\Observers\Flottes\VehicleObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[ObservedBy([VehicleObserver::class])]
class Vehicle extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'uuid',
        'reference',
        'license_plate',
        'brand',
        'model',
        'type',
        'fuel_type',
        'odometer',
        'status',
        'current_location',
        'daily_rate',
        'km_rate',
        'purchase_date',
        'purchase_price',
        'tco_cache',
        'pollution_control_due_at',
        'usage_unit',
        'crit_air_level',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($vehicle) {
            if (empty($vehicle->uuid)) {
                $vehicle->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'type' => VehicleType::class,
            'status' => VehicleStatus::class,
            'odometer' => 'decimal:2',
            'daily_rate' => 'decimal:2',
            'km_rate' => 'decimal:4',
            'purchase_date' => 'date',
            'purchase_price' => 'decimal:2',
            'tco_cache' => 'decimal:2',
            'pollution_control_due_at' => 'date',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(VehicleAssignment::class);
    }

    public function currentAssignment(): HasOne
    {
        return $this->hasOne(VehicleAssignment::class)->where('status', AssignmentStatus::ACTIVE);
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(VehicleMaintenance::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(VehicleContract::class);
    }

    public function resourceAllocations(): MorphMany
    {
        return $this->morphMany(ResourceAllocation::class, 'allocatable');
    }

    public function fines(): HasMany
    {
        return $this->hasMany(TrafficFine::class);
    }

    public function damages(): HasMany
    {
        return $this->hasMany(Damage::class);
    }

    public function fixedAsset(): HasOne
    {
        return $this->hasOne(FixedAsset::class);
    }

    public function fuelTransactions(): HasMany
    {
        return $this->hasMany(FuelTransaction::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(FleetExpense::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(VehicleInventory::class);
    }

    // ============ SCOPES ============

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', VehicleStatus::AVAILABLE);
    }

    public function scopeAssigned(Builder $query): Builder
    {
        return $query->where('status', VehicleStatus::ASSIGNED);
    }

    public function scopeMaintenance(Builder $query): Builder
    {
        return $query->where('status', VehicleStatus::MAINTENANCE);
    }

    public function scopeBroken(Builder $query): Builder
    {
        return $query->where('status', VehicleStatus::BROKEN);
    }

    public function scopeByType(Builder $query, VehicleType $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus(Builder $query, VehicleStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByReference(Builder $query, string $reference): Builder
    {
        return $query->where('reference', 'ilike', "%{$reference}%");
    }

    public function scopeByLicensePlate(Builder $query, string $plate): Builder
    {
        return $query->where('license_plate', 'ilike', "%{$plate}%");
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where('reference', 'ilike', "%{$search}%")
            ->orWhere('license_plate', 'ilike', "%{$search}%")
            ->orWhere('brand', 'ilike', "%{$search}%")
            ->orWhere('model', 'ilike', "%{$search}%");
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeWithCurrentAssignment(Builder $query): Builder
    {
        return $query->with('currentAssignment');
    }

    public function scopeVUL(Builder $query): Builder
    {
        return $query->where('type', VehicleType::UTILITY);
    }

    public function scopePollutionControlDue(Builder $query): Builder
    {
        return $query->whereNotNull('pollution_control_due_at')
            ->where('pollution_control_due_at', '<=', now());
    }

    // ============ METHODS ============

    public function getActiveAssignment(): ?VehicleAssignment
    {
        return $this->currentAssignment;
    }

    public function isAvailable(): bool
    {
        return $this->status === VehicleStatus::AVAILABLE;
    }

    public function isAssigned(): bool
    {
        return $this->status === VehicleStatus::ASSIGNED;
    }

    public function isInMaintenance(): bool
    {
        return $this->status === VehicleStatus::MAINTENANCE;
    }

    public function isBroken(): bool
    {
        return $this->status === VehicleStatus::BROKEN;
    }

    public function getDisplayName(): string
    {
        return "{$this->brand} {$this->model} ({$this->license_plate})";
    }

    public function getMaintenanceCosts(): float
    {
        return (float) $this->maintenances()
            ->sum('cost_ht');
    }

    public function getMaintenanceCotsWithTax(): float
    {
        return (float) $this->maintenances()
            ->with('vatRate')
            ->get()
            ->sum(function ($m) {
                return $m->cost_ht * (1 + $m->vatRate->rate / 100);
            });
    }

    public function getTotalExpenses(): float
    {
        return $this->getMaintenanceCotsWithTax() + $this->getFuelCosts() + $this->getFleetExpenses();
    }

    public function getFuelCosts(): float
    {
        return (float) $this->fuelTransactions()
            ->sum('cost_ht');
    }

    public function getFleetExpenses(): float
    {
        return (float) $this->expenses()
            ->sum('amount_ttc');
    }

    public function getTotalKilometers(): float
    {
        return (float) $this->assignments()
            ->where('status', AssignmentStatus::COMPLETED)
            ->get()
            ->sum(function ($a) {
                return $a->end_odometer - $a->start_odometer;
            });
    }

    public function getAverageFuelConsumption(): ?float
    {
        $totalKm = $this->getTotalKilometers();
        $totalLiters = (float) $this->fuelTransactions()->sum('liters');

        return $totalKm > 0 ? $totalLiters / $totalKm * 100 : null;
    }

    public function getTCO(): float
    {
        $contractsCosts = (float) $this->contracts()
            ->sum('annual_cost_ht') ?? 0;

        return $this->getTotalExpenses() + $contractsCosts;
    }

    public function getFinesTotalAmount(): float
    {
        return (float) $this->fines()
            ->whereIn('status', ['received', 'transmitted', 'paid'])
            ->sum('amount');
    }

    public function getAssignmentCount(): int
    {
        return $this->assignments()->count();
    }

    public function getLastMaintenanceDate(): ?\DateTime
    {
        return $this->maintenances()
            ->latest('performed_at')
            ->first()?->performed_at;
    }

    public function getDaysSinceLastMaintenance(): ?int
    {
        $lastDate = $this->getLastMaintenanceDate();

        return $lastDate?->diffInDays(now());
    }

    public function needsPollutionControl(): bool
    {
        return $this->isVUL() &&
            $this->pollution_control_due_at &&
            $this->pollution_control_due_at <= now();
    }

    public function getContractStatus(): string
    {
        $active = $this->contracts()
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->exists();

        return $active ? 'Active' : 'Inactive';
    }

    public function getAnnualCost(): float
    {
        return (float) $this->contracts()
            ->sum('annual_cost_ht') ?? 0;
    }

    // ============ MEDIA & HELPERS ============

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('registration_card')->singleFile();
        $this->addMediaCollection('photos');
    }

    public function isVUL(): bool
    {
        return $this->type === VehicleType::UTILITY;
    }
}
