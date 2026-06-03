<?php

namespace App\Models\Flottes;

use App\Enums\Flottes\FineStatus;
use App\Models\RH\Employee;
use App\Observers\Flottes\TrafficFineObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([TrafficFineObserver::class])]
class TrafficFine extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'employee_id',
        'reference',
        'infraction_at',
        'amount',
        'points_deducted',
        'status',
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
            'infraction_at' => 'datetime',
            'amount' => 'decimal:2',
            'status' => FineStatus::class,
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

    public function scopeByStatus(Builder $query, FineStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeReceived(Builder $query): Builder
    {
        return $query->where('status', FineStatus::RECEIVED);
    }

    public function scopeDisputed(Builder $query): Builder
    {
        return $query->where('status', FineStatus::DISPUTED);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', FineStatus::PAID);
    }

    public function scopeTransmitted(Builder $query): Builder
    {
        return $query->where('status', FineStatus::TRANSMITTED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', [FineStatus::RECEIVED, FineStatus::DISPUTED]);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('infraction_at');
    }

    public function scopeBetweenDates(Builder $query, \DateTime $from, \DateTime $to): Builder
    {
        return $query->whereBetween('infraction_at', [$from, $to]);
    }

    public function scopeExpensive(Builder $query, float $minAmount = 500): Builder
    {
        return $query->where('amount', '>=', $minAmount);
    }

    public function scopeWithPointsDeduction(Builder $query): Builder
    {
        return $query->where('points_deducted', '>', 0);
    }

    public function scopeThisYear(Builder $query): Builder
    {
        return $query->whereYear('infraction_at', now()->year);
    }

    // ============ METHODS ============

    public function isPaid(): bool
    {
        return $this->status === FineStatus::PAID;
    }

    public function isDisputed(): bool
    {
        return $this->status === FineStatus::DISPUTED;
    }

    public function isPending(): bool
    {
        return in_array($this->status, [FineStatus::RECEIVED, FineStatus::DISPUTED]);
    }

    public function isReceived(): bool
    {
        return $this->status === FineStatus::RECEIVED;
    }

    public function isTransmitted(): bool
    {
        return $this->status === FineStatus::TRANSMITTED;
    }

    public function getDaysUntilDue(): int
    {
        // 45 jours pour payer après réception
        return now()->diffInDays($this->infraction_at->addDays(45));
    }

    public function isOverdue(): bool
    {
        return $this->getDaysUntilDue() < 0 && ! $this->isPaid();
    }

    public function getStatusLabel(): string
    {
        return $this->status->getLabel();
    }

    public function getInfractionType(): string
    {
        // Parse from reference number or description
        // Example: "80/50" = speeding, "204" = no documents, etc.
        return $this->reference;
    }

    public function markAsPaid(): void
    {
        $this->update(['status' => FineStatus::PAID]);
    }

    public function markAsDisputed(): void
    {
        $this->update(['status' => FineStatus::DISPUTED]);
    }

    public function markAsTransmitted(): void
    {
        $this->update(['status' => FineStatus::TRANSMITTED]);
    }

    public function getDisplayName(): string
    {
        return "Amende {$this->reference} - {$this->vehicle->getDisplayName()} ({$this->amount}€)";
    }
}
