<?php

namespace App\Models\Flottes;

use App\Enums\Flottes\FleetExpenseType;
use App\Models\Chantiers\Chantier;
use App\Models\Core\VatRate;
use App\Models\RH\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FleetExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'employee_id',
        'chantier_id',
        'type',
        'reference',
        'amount_ht',
        'vat_rate_id',
        'amount_ttc',
        'merchant_name',
        'description',
        'spent_at',
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

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    public function vatRate(): BelongsTo
    {
        return $this->belongsTo(VatRate::class);
    }

    protected function casts(): array
    {
        return [
            'spent_at' => 'datetime',
            'amount_ht' => 'decimal:4',
            'amount_ttc' => 'decimal:2',
            'is_suspicious' => 'boolean',
            'type' => FleetExpenseType::class,
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

    public function scopeByChantier(Builder $query, int $chantierId): Builder
    {
        return $query->where('chantier_id', $chantierId);
    }

    public function scopeByType(Builder $query, FleetExpenseType $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeSuspicious(Builder $query): Builder
    {
        return $query->where('is_suspicious', true);
    }

    public function scopeNotSuspicious(Builder $query): Builder
    {
        return $query->where('is_suspicious', false);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('spent_at');
    }

    public function scopeBetweenDates(Builder $query, \DateTime $from, \DateTime $to): Builder
    {
        return $query->whereBetween('spent_at', [$from, $to]);
    }

    public function scopeExpensive(Builder $query, float $minAmount = 100): Builder
    {
        return $query->where('amount_ttc', '>=', $minAmount);
    }

    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereBetween('spent_at', [now()->startOfMonth(), now()->endOfMonth()]);
    }

    public function scopeByMerchant(Builder $query, string $merchant): Builder
    {
        return $query->where('merchant_name', 'ilike', "%{$merchant}%");
    }

    // ============ METHODS ============

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            FleetExpenseType::PEAGE => 'Péage autoroutier',
            FleetExpenseType::PARKING => 'stationnement',
            default => $this->type->value,
        };
    }

    public function getVatAmount(): float
    {
        return (float) $this->amount_ttc - (float) $this->amount_ht;
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

    public function getDisplayName(): string
    {
        return "{$this->getTypeLabel()} - {$this->amount_ttc}€ ({$this->merchant_name})";
    }

    public function allocateToChantier(Chantier $chantier): void
    {
        $this->update(['chantier_id' => $chantier->id]);
    }
}
