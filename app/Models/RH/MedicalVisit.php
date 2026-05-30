<?php

namespace App\Models\RH;

use App\Enums\RH\MedicalAptitude;
use App\Enums\RH\MedicalVisiteType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class MedicalVisit extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'employee_id',
        'type',
        'visit_date',
        'next_due_date',
        'aptitude',
        'practitioner_name',
        'notes',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    protected function casts(): array
    {
        return [
            'type' => MedicalVisiteType::class,
            'aptitude' => MedicalAptitude::class,
            'visit_date' => 'date',
            'next_due_date' => 'date',
        ];
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeByEmployee(Builder $query, Employee $employee): Builder
    {
        return $query->where('employee_id', $employee->id);
    }

    public function scopeByType(Builder $query, MedicalVisiteType $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeApte(Builder $query): Builder
    {
        return $query->where('aptitude', MedicalAptitude::FIT);
    }

    public function scopeInapte(Builder $query): Builder
    {
        return $query->where('aptitude', MedicalAptitude::UNFIT);
    }

    public function scopeApteAvecReserves(Builder $query): Builder
    {
        return $query->where('aptitude', MedicalAptitude::FIT_RESTRICTED);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('next_due_date', '<', today());
    }

    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->where('next_due_date', '>=', today())
                ->orWhereNull('next_due_date');
        });
    }

    public function scopeExpiringsSoon(Builder $query, int $days = 30): Builder
    {
        return $query->where('next_due_date', '>=', today())
            ->where('next_due_date', '<=', now()->addDays($days));
    }

    public function scopeRecent(Builder $query, int $days = 365): Builder
    {
        return $query->where('visit_date', '>=', now()->subDays($days));
    }

    public function scopeOrderByDate(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('visit_date', $direction);
    }

    public function scopeOrderByNextDue(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('next_due_date', $direction);
    }

    // ============================================
    // METHODS
    // ============================================

    public function isExpired(): bool
    {
        return $this->next_due_date && $this->next_due_date->isPast();
    }

    public function isExpiringsSoon(int $days = 30): bool
    {
        return $this->next_due_date
            && $this->next_due_date >= now()
            && $this->next_due_date <= now()->addDays($days);
    }

    public function isApte(): bool
    {
        return $this->aptitude === MedicalAptitude::FIT;
    }

    public function isInapte(): bool
    {
        return $this->aptitude === MedicalAptitude::UNFIT;
    }

    public function isApteAvecReserves(): bool
    {
        return $this->aptitude === MedicalAptitude::FIT_RESTRICTED;
    }

    public function getDaysUntilDue(): ?int
    {
        if (! $this->next_due_date) {
            return null;
        }

        $days = now()->diffInDays($this->next_due_date, false);

        return $days >= 0 ? $days : null;
    }

    public function getMonthsSinceVisit(): int
    {
        return $this->visit_date->diffInMonths(now());
    }

    public static function lastVisitForEmployee(Employee $employee): ?self
    {
        return static::byEmployee($employee)
            ->orderByDate()
            ->first();
    }
}
