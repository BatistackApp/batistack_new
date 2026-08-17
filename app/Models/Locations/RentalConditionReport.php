<?php

namespace App\Models\Locations;

use App\Enums\Locations\RentalConditionReportType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * État des lieux d'un contrat de location entrante (protection contre les litiges fournisseurs).
 * Photos horodatées + commentaire + GPS + signature, à la réception et à la restitution.
 */
class RentalConditionReport extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'rental_contract_id',
        'type',
        'comment',
        'latitude',
        'longitude',
        'signature_checksum',
        'signed_at',
        'captured_at',
        'client_key',
    ];

    protected function casts(): array
    {
        return [
            'type' => RentalConditionReportType::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'signed_at' => 'datetime',
            'captured_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos');
        $this->addMediaCollection('signature')->singleFile();
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(RentalContract::class, 'rental_contract_id');
    }

    // ============ SCOPES ============

    public function scopeReception(Builder $query): Builder
    {
        return $query->where('type', RentalConditionReportType::RECEPTION);
    }

    public function scopeRestitution(Builder $query): Builder
    {
        return $query->where('type', RentalConditionReportType::RESTITUTION);
    }

    public function scopeSigned(Builder $query): Builder
    {
        return $query->whereNotNull('signed_at');
    }

    public function scopeByContract(Builder $query, int $contractId): Builder
    {
        return $query->where('rental_contract_id', $contractId);
    }

    public function scopeWithPhotos(Builder $query): Builder
    {
        return $query->whereHas('media', fn ($q) => $q->where('collection_name', 'photos'));
    }

    // ============ METHODS ============

    public function isSigned(): bool
    {
        return $this->signed_at !== null;
    }

    public function isReception(): bool
    {
        return $this->type === RentalConditionReportType::RECEPTION;
    }

    public function isRestitution(): bool
    {
        return $this->type === RentalConditionReportType::RESTITUTION;
    }

    public function getTypeLabel(): string
    {
        return $this->type->getLabel();
    }

    public function sign(string $signature): void
    {
        $this->update([
            'signed_at' => now(),
            'signature_checksum' => hash('sha256', $signature),
        ]);
    }

    public function getPhotoCount(): int
    {
        return $this->getMedia('photos')->count();
    }

    public function getDisplayName(): string
    {
        return "{$this->getTypeLabel()} - ".($this->captured_at?->format('d/m/Y H:i') ?? $this->created_at->format('d/m/Y H:i'));
    }
}
