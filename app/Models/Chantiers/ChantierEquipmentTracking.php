<?php

namespace App\Models\Chantiers;

use App\Models\RH\Equipement;
use App\Models\Immobilisation\FixedAsset;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ChantierEquipmentTracking extends Model
{
    use HasFactory;

    protected $fillable = [
        'chantier_id',
        'trackable_type',
        'trackable_id',
        'scanned_by',
        'check_in_at',
        'check_out_at',
        'qr_token',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
        ];
    }

    // -- Relationships --

    public function trackable(): MorphTo
    {
        return $this->morphTo();
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    public function scannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }

    // -- Scopes --

    public function scopeCurrentlyOnSite(Builder $query): Builder
    {
        return $query->whereNull('check_out_at');
    }

    public function scopeForChantier(Builder $query, int $chantierId): Builder
    {
        return $query->where('chantier_id', $chantierId);
    }

    public function scopeCheckedInToday(Builder $query): Builder
    {
        return $query->whereDate('check_in_at', today());
    }

    // -- Cost Calculation --

    public function getDurationInDays(): int
    {
        if (! $this->check_in_at) {
            return 0;
        }

        $end = $this->check_out_at ?? now();
        $hours = $this->check_in_at->diffInHours($end);

        return max(1, (int) ceil($hours / 24));
    }

    public function getDailyRate(): float
    {
        $trackable = $this->trackable;

        if ($trackable instanceof FixedAsset) {
            return (float) ($trackable->daily_rate ?? 0);
        }

        if ($trackable instanceof Equipement) {
            return (float) ($trackable->daily_cost ?? 0);
        }

        return 0;
    }

    public function getImmobilizationCost(): float
    {
        return $this->getDurationInDays() * $this->getDailyRate();
    }

    public function isCurrentlyOnSite(): bool
    {
        return $this->check_out_at === null;
    }

    // -- Helpers --

    public function getTrackableLabel(): string
    {
        $trackable = $this->trackable;

        if ($trackable instanceof FixedAsset) {
            return $trackable->name ?? $trackable->reference ?? 'Actif #'.$trackable->id;
        }

        if ($trackable instanceof Equipement) {
            return $trackable->getLabel();
        }

        return 'Inconnu';
    }

    public function getTrackableTypeLabel(): string
    {
        return match ($this->trackable_type) {
            FixedAsset::class => 'Gros matériel',
            Equipement::class => 'Outillage',
            default => 'Autre',
        };
    }
}
