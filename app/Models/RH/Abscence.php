<?php

namespace App\Models\RH;

use App\Enums\RH\AbsenceType;
use App\Observers\RH\AbscenceObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[ObservedBy([AbscenceObserver::class])]
class Abscence extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'employee_id',
        'type',
        'start_date',
        'end_date',
        'reason',
        'is_paid',
        'cibtp_declared_at',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'is_paid' => 'boolean',
            'type' => AbsenceType::class,
            'cibtp_declared_at' => 'datetime',
        ];
    }

    // SCOPES
    public function scopeByEmployee(Builder $query, Employee $employee): Builder
    {
        return $query->where('employee_id', $employee->id);
    }

    public function scopeByType(Builder $query, AbsenceType $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereYear('start_date', now()->year)
            ->whereMonth('start_date', now()->month);
    }

    public function scopeOrderByDate(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('start_date', $direction);
    }

    // METHODS
    public function getType(): AbsenceType
    {
        return $this->type;
    }
}
