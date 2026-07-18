<?php

namespace App\Models\RH;

use App\Models\Chantiers\Chantier;
use App\Models\User;
use App\Observers\RH\EmployeeObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[ObservedBy([EmployeeObserver::class])]
class Employee extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, Notifiable;

    protected $fillable = [
        'registration_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'birth_date',
        'social_security_number',
        'is_active',
        'uuid',
        'address',
        'postal_code',
        'city',
        'pin_hash',
        'biometric_consent',
        'face_descriptor',
        'onboarding_completed',
        'pas_rate',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_active' => 'boolean',
            'biometric_consent' => 'boolean',
            'face_descriptor' => 'array',
            'onboarding_completed' => 'boolean',
            'pas_rate' => 'decimal:4',
        ];
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function currentContract(): HasOne
    {
        return $this->hasOne(Contract::class)->latestOfMany();
    }

    public function qualifications(): HasMany
    {
        return $this->hasMany(Qualification::class);
    }

    public function medicalVisits(): HasMany
    {
        return $this->hasMany(MedicalVisit::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function expenseReports(): HasMany
    {
        return $this->hasMany(ExpenseReport::class);
    }

    public function absences(): HasMany
    {
        return $this->hasMany(Abscence::class);
    }

    public function equipements(): HasMany
    {
        return $this->hasMany(Equipement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chantiers(): BelongsToMany
    {
        return $this->belongsToMany(Chantier::class, 'chantier_members');
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('first_name', 'like', "%{$term}%")
            ->orWhere('last_name', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%")
            ->orWhere('registration_number', 'like', "%{$term}%");
    }

    public function scopeByRegistrationNumber(Builder $query, string $number): Builder
    {
        return $query->where('registration_number', $number);
    }

    public function scopeByEmail(Builder $query, string $email): Builder
    {
        return $query->where('email', $email);
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeOrderByName(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('last_name', $direction)
            ->orderBy('first_name', $direction);
    }

    public function scopeWithContract(Builder $query): Builder
    {
        return $query->whereHas('currentContract');
    }

    public function scopeWithoutContract(Builder $query): Builder
    {
        return $query->whereDoesntHave('currentContract');
    }

    // ============================================
    // METHODS MÉTIER
    // ============================================

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isInactive(): bool
    {
        return !$this->is_active;
    }

    public function getFullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getFullAddress(): string
    {
        return "{$this->address} {$this->postal_code} {$this->city}";
    }

    public function getAge(): ?int
    {
        if (!$this->birth_date) {
            return null;
        }

        return $this->birth_date->age;
    }

    public function hasCurrentContract(): bool
    {
        return $this->currentContract()->exists();
    }

    public function getCurrentContractType(): ?string
    {
        return $this->currentContract?->contract_type;
    }

    public function getHoursWorkedToday(): float
    {
        return $this->timeEntries()
            ->whereDate('date', today())
            ->sum('hours') ?? 0;
    }

    public function getHoursWorkedThisMonth(): float
    {
        return $this->timeEntries()
            ->where('status', \App\Enums\RH\TimeEntryStatus::APPROVED)
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->sum('hours') ?? 0;
    }

    public function getAbsencesThisMonth(): int
    {
        return $this->absences()
            ->whereYear('start_date', now()->year)
            ->whereMonth('start_date', now()->month)
            ->count();
    }

    public function hasQualifications(): bool
    {
        return $this->qualifications()->exists();
    }

    public function getQualificationCount(): int
    {
        return $this->qualifications()->count();
    }

    public function needsMedicalVisit(): bool
    {
        // Check if last medical visit is older than 1 year
        $lastVisit = $this->medicalVisits()->latest()->first();
        return !$lastVisit || $lastVisit->created_at < now()->subYear();
    }

    public function getEquipementCount(): int
    {
        return $this->equipements()->count();
    }

    public static function byRegistration(string $number): ?self
    {
        return static::where('registration_number', $number)->first();
    }

    public static function byEmail(string $email): ?self
    {
        return static::where('email', $email)->first();
    }

    // ============================================
    // ATTRIBUTES
    // ============================================

    public function getFullNameAttribute(): string
    {
        return $this->getFullName();
    }

    public function getFullAddressAttribute(): string
    {
        return $this->getFullAddress();
    }
}
