<?php

namespace App\Models\RH;

use App\Enums\RH\CertificationSymbol;
use App\Enums\RH\QualificationType;
use App\Observers\RH\QualificationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[ObservedBy([QualificationObserver::class])]
class Qualification extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'employee_id',
        'type',
        'label',
        'reference_number',
        'obtained_at',
        'expires_at',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    protected function casts(): array
    {
        return [
            'type' => QualificationType::class,
            'obtained_at' => 'date',
            'expires_at' => 'date',
            'label' => CertificationSymbol::class,
        ];
    }

    // SCOPES
    public function scopeByEmployee(Builder $query, Employee $employee): Builder
    {
        return $query->where('employee_id', $employee->id);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('expires_at')
            ->orWhere('expires_at', '>=', now())
        );
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<', now());
    }

    public function scopeExpiringsSoon(Builder $query, int $days = 30): Builder
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '>=', now())
            ->where('expires_at', '<=', now()->addDays($days));
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('name', 'like', "%{$term}%");
    }

    // METHODS
    public function isActive(): bool
    {
        return ! $this->expires_at || $this->expires_at >= now();
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at < now();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expires_at
            && $this->expires_at >= now()
            && $this->expires_at <= now()->addDays($days);
    }

    public function getDaysUntilExpiration(): ?int
    {
        if (! $this->expires_at) {
            return null;
        }

        return now()->diffInDays($this->expires_at, false);
    }
}
