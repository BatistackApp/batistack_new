<?php

namespace App\Models\RH;

use App\Enums\RH\TimeEntryStatus;
use App\Enums\RH\TimeEntryType;
use App\Models\Chantiers\Chantier;
use App\Models\Gpao\ManufacturingOrder;
use App\Models\User;
use App\Observers\RH\TimeEntryObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([TimeEntryObserver::class])]
class TimeEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'chantier_id', 'date', 'hours', 'type',
        'status', 'refusal_reason', 'approved_by_id', 'approved_at',
        'is_grand_deplacement', 'gd_allowance_amount', 'description',
        'travel_hours', 'manufacturing_order_id', 'started_at', 'ended_at',
        'is_anomaly', 'anomaly_reason', 'anomaly_resolved_at', 'anomaly_resolved_by_id',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function anomalyResolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anomaly_resolved_by_id');
    }

    public function manufacturingOrder(): BelongsTo
    {
        return $this->belongsTo(ManufacturingOrder::class);
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'type' => TimeEntryType::class,
            'status' => TimeEntryStatus::class,
            'hours' => 'decimal:2',
            'is_grand_deplacement' => 'boolean',
            'is_workshop' => 'boolean',
            'gd_allowance_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'travel_hours' => 'decimal:2',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'is_anomaly' => 'boolean',
            'anomaly_resolved_at' => 'datetime',
        ];
    }

    // SCOPES
    public function scopeByEmployee(Builder $query, Employee $employee): Builder
    {
        return $query->where('employee_id', $employee->id);
    }

    public function scopeByDate(Builder $query, $date): Builder
    {
        return $query->whereDate('date', $date);
    }

    public function scopeBetweenDates(Builder $query, $from, $to): Builder
    {
        return $query->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to);
    }

    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereYear('date', now()->year)
            ->whereMonth('date', now()->month);
    }

    public function scopeThisYear(Builder $query): Builder
    {
        return $query->whereYear('date', now()->year);
    }

    public function scopeOrderByDate(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('date', $direction);
    }

    // METHODS
    public function getHours(): float
    {
        return (float) $this->hours;
    }

    public static function totalForEmployee(Employee $employee, $from, $to): float
    {
        return static::byEmployee($employee)
            ->betweenDates($from, $to)
            ->sum('hours') ?? 0;
    }
}
