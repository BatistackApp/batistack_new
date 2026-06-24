<?php

namespace App\Models\Flottes;

use App\Enums\Flottes\ConditionReportType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class VehicleConditionReport extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'vehicle_assignment_id',
        'type',
        'odometer',
        'fuel_level',
        'signature_checksum',
        'signed_at',
        'comment',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(VehicleAssignment::class, 'vehicle_assignment_id');
    }

    protected function casts(): array
    {
        return [
            'odometer' => 'decimal:2',
            'fuel_level' => 'integer',
            'signed_at' => 'datetime',
            'type' => ConditionReportType::class,
        ];
    }

    /**
     * Configuration des collections d'images Spatie MediaLibrary.
     * On isole chaque face et le tableau de bord pour un contrôle précis.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo_front')->singleFile();
        $this->addMediaCollection('photo_back')->singleFile();
        $this->addMediaCollection('photo_left')->singleFile();
        $this->addMediaCollection('photo_right')->singleFile();
        $this->addMediaCollection('photo_dashboard')->singleFile(); // Odomètre & Jauge carburant
    }

    // ============ SCOPES ============

    public function scopeByAssignment(Builder $query, int $assignmentId): Builder
    {
        return $query->where('vehicle_assignment_id', $assignmentId);
    }

    public function scopeByType(Builder $query, ConditionReportType $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeCheckIn(Builder $query): Builder
    {
        return $query->where('type', ConditionReportType::CHECK_IN);
    }

    public function scopeCheckOut(Builder $query): Builder
    {
        return $query->where('type', ConditionReportType::CHECK_OUT);
    }

    public function scopeInventory(Builder $query): Builder
    {
        return $query->where('type', ConditionReportType::INVENTORY);
    }

    public function scopeSigned(Builder $query): Builder
    {
        return $query->whereNotNull('signed_at');
    }

    public function scopeUnsigned(Builder $query): Builder
    {
        return $query->whereNull('signed_at');
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeBetweenDates(Builder $query, \DateTime $from, \DateTime $to): Builder
    {
        return $query->whereBetween('signed_at', [$from, $to]);
    }

    public function scopeWithPhotos(Builder $query): Builder
    {
        return $query->whereHas('media');
    }

    public function scopeWithoutPhotos(Builder $query): Builder
    {
        return $query->whereDoesntHave('media');
    }

    // ============ METHODS ============

    public function getTypeLabel(): string
    {
        return $this->type->getLabel();
    }

    public function getTypeDescription(): string
    {
        return $this->type->getDescription() ?? '';
    }

    public function isSigned(): bool
    {
        return $this->signed_at !== null;
    }

    public function isUnsigned(): bool
    {
        return ! $this->isSigned();
    }

    public function needsSignature(): bool
    {
        return $this->isUnsigned();
    }

    public function isCheckIn(): bool
    {
        return $this->type === ConditionReportType::CHECK_IN;
    }

    public function isCheckOut(): bool
    {
        return $this->type === ConditionReportType::CHECK_OUT;
    }

    public function isInventory(): bool
    {
        return $this->type === ConditionReportType::INVENTORY;
    }

    public function getFuelLevelLabel(): string
    {
        if (! $this->fuel_level) {
            return 'Non spécifié';
        }

        return "{$this->fuel_level}%";
    }

    public function hasAllPhotos(): bool
    {
        $requiredCollections = ['photo_front', 'photo_back', 'photo_left', 'photo_right', 'photo_dashboard'];
        foreach ($requiredCollections as $collection) {
            if (! $this->hasMedia($collection)) {
                return false;
            }
        }

        return true;
    }

    public function getMissingPhotos(): array
    {
        $requiredCollections = ['photo_front', 'photo_back', 'photo_left', 'photo_right', 'photo_dashboard'];
        $missing = [];

        foreach ($requiredCollections as $collection) {
            if (! $this->hasMedia($collection)) {
                $missing[] = $collection;
            }
        }

        return $missing;
    }

    public function getPhotoCount(): int
    {
        return $this->media()->count();
    }

    public function getDisplayName(): string
    {
        return "{$this->getTypeLabel()} - {$this->created_at->format('d/m/Y H:i')}";
    }

    public function sign(string $signature): void
    {
        $this->update([
            'signed_at' => now(),
            'signature_checksum' => hash('sha256', $signature),
        ]);
    }

    /**
     * Vérifie si le checksum fourni correspond à celui stocké.
     */
    public function validateChecksum(string $checksum): bool
    {
        return hash_equals($this->signature_checksum, $checksum);
    }
}
