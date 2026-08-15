<?php

namespace App\Models\RH;

use App\Enums\RH\EquipementStatus;
use App\Enums\RH\EquipementType;
use App\Models\Articles\Item;
use App\Models\Immobilisation\AssetMaintenanceTicket;
use App\Observers\RH\EquipementObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[ObservedBy([EquipementObserver::class])]
class Equipement extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'type',
        'label',
        'brand',
        'model_name',
        'serial_number',
        'barcode',
        'qr_token',
        'assigned_at',
        'expires_at',
        'last_check_at',
        'notes',
        'item_id',
        'status',
        'daily_cost',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(EquipementAssignment::class);
    }

    public function currentAssignment(): HasOne
    {
        return $this->hasOne(EquipementAssignment::class)->whereNull('returned_at');
    }

    public function maintenanceTickets(): MorphMany
    {
        return $this->morphMany(AssetMaintenanceTicket::class, 'asset');
    }

    protected function casts(): array
    {
        return [
            'assigned_at' => 'date',
            'expires_at' => 'date',
            'last_check_at' => 'date',
            'type' => EquipementType::class,
            'status' => EquipementStatus::class,
            'daily_cost' => 'decimal:2',
        ];
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeByEmployee(Builder $query, Employee $employee): Builder
    {
        return $query->where('employee_id', $employee->id);
    }

    public function scopeByType(Builder $query, EquipementType $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '>=', now())
            ->orWhereNull('expires_at');
    }

    public function scopeExpiringsSoon(Builder $query, int $days = 30): Builder
    {
        return $query->where('expires_at', '>=', now())
            ->where('expires_at', '<=', now()->addDays($days));
    }

    public function scopeNeedsCheck(Builder $query, int $days = 365): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('last_check_at')
            ->orWhere('last_check_at', '<', now()->subDays($days))
        );
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('label', 'like', "%{$term}%")
            ->orWhere('serial_number', 'like', "%{$term}%")
            ->orWhere('barcode', 'like', "%{$term}%")
            ->orWhere('brand', 'like', "%{$term}%");
    }

    public function scopeOrderByExpiresAt(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('expires_at', $direction);
    }

    public function scopeOrderByType(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('type', $direction);
    }

    // ============================================
    // METHODS
    // ============================================

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isExpiringsSoon(int $days = 30): bool
    {
        return $this->expires_at
            && $this->expires_at >= now()
            && $this->expires_at <= now()->addDays($days);
    }

    public function needsCheck(int $days = 365): bool
    {
        if (! $this->last_check_at) {
            return true;
        }

        return $this->last_check_at < now()->subDays($days);
    }

    public function getDaysUntilExpiration(): ?int
    {
        if (! $this->expires_at) {
            return null;
        }

        $days = now()->diffInDays($this->expires_at, false);

        return $days >= 0 ? $days : null;
    }

    public function getLabel(): string
    {
        return "{$this->brand} {$this->model_name} ({$this->label})";
    }

    public static function bySerialNumber(string $serial): ?self
    {
        return static::where('serial_number', $serial)->first();
    }
}
