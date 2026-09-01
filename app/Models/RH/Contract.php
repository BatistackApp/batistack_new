<?php

namespace App\Models\RH;

use App\Enums\Core\SignatureStatus;
use App\Enums\RH\ContractType;
use App\Enums\RH\EmployeeCategory;
use App\Enums\RH\TerminationType;
use App\Models\Core\Signature;
use App\Models\Paie\PayrollContributionProfile;
use App\Observers\RH\ContractObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[ObservedBy([ContractObserver::class])]
class Contract extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'employee_id',
        'type',
        'category',
        'start_date',
        'end_date',
        'job_title',
        'hourly_rate',
        'weekly_hours',
        'trial_end_date',
        'docuseal_template_id',
        'docuseal_submission_id',
        'signature_status',
        'payroll_contribution_profile_id',
        'termination_type',
        'termination_reason',
        'terminated_at',
        'notice_end_date',
        'termination_amount',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payrollContributionProfile(): BelongsTo
    {
        return $this->belongsTo(PayrollContributionProfile::class);
    }

    public function signatures()
    {
        return $this->morphMany(Signature::class, 'signable');
    }

    protected function casts(): array
    {
        return [
            'type' => ContractType::class,
            'category' => EmployeeCategory::class,
            'termination_type' => TerminationType::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'hourly_rate' => 'decimal:4',
            'trial_end_date' => 'date',
            'terminated_at' => 'date',
            'notice_end_date' => 'date',
            'termination_amount' => 'decimal:2',
            'signature_status' => SignatureStatus::class,
        ];
    }

    // SCOPES
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('start_date', '<=', now())
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()))
            ->whereNull('terminated_at');
    }

    public function scopeTerminated(Builder $query): Builder
    {
        return $query->whereNotNull('terminated_at');
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('end_date')->where('end_date', '<', now());
    }

    public function scopeByType(Builder $query, ContractType $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeByEmployee(Builder $query, Employee $employee): Builder
    {
        return $query->where('employee_id', $employee->id);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->latest('start_date');
    }

    public function scopeOrderByStartDate(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('start_date', $direction);
    }

    // METHODS
    public function isActive(): bool
    {
        return $this->start_date <= now()
            && (! $this->end_date || $this->end_date >= now())
            && is_null($this->terminated_at);
    }

    public function isExpired(): bool
    {
        return $this->end_date && $this->end_date < now();
    }

    public function isTerminated(): bool
    {
        return ! is_null($this->terminated_at);
    }

    public function getDuration(): ?int
    {
        if (! $this->end_date) {
            return null;
        }

        return $this->start_date->diffInMonths($this->end_date);
    }

    public function getSalary(): float
    {
        return round(($this->hourly_rate * $this->weekly_hours) * 4, 2);
    }

    public function getHourlyRate(): float
    {
        return (float) $this->hourly_rate;
    }

    public function getWeeklyHours(): float
    {
        return (float) $this->weekly_hours;
    }
}
