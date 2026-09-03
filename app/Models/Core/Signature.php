<?php

namespace App\Models\Core;

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Signature extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'signable_type',
        'signable_id',
        'user_id',
        'status',
        'type',
        'signature_data', // Image en base64 ou log de validation
        'checksum',       // Hash du document au moment de la signature
        'ip_address',
        'signed_at',
        'metadata',       // Informations supplémentaires (User-Agent, etc.)
    ];

    protected $casts = [
        'status' => SignatureStatus::class,
        'type' => SignatureType::class,
        'signed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Relation polymorphique (Devis, Facture, Contrat, etc.)
     */
    public function signable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * L'utilisateur qui a apposé la signature.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Les signataires configurés pour cette demande de signature.
     */
    public function signers(): HasMany
    {
        return $this->hasMany(SignatureSigner::class);
    }

    /**
     * Vérifie si c'est un workflow multi-signataires.
     * Uses withCount('signers') if eager-loaded, otherwise falls back to query.
     */
    public function getIsMultiSignatoryAttribute(): bool
    {
        if ($this->relationLoaded('signers')) {
            return $this->signers->count() > 0;
        }

        return (int) ($this->attributes['signers_count'] ?? $this->signers()->count()) > 0;
    }

    /**
     * Nombre de signataires ayant signé.
     */
    public function getSignedCountAttribute(): int
    {
        if ($this->relationLoaded('signers')) {
            return $this->signers->where('status', SignatureStatus::SIGNED)->count();
        }

        return (int) ($this->attributes['signed_signers_count'] ?? $this->signers()->where('status', SignatureStatus::SIGNED)->count());
    }

    /**
     * Nombre total de signataires.
     */
    public function getTotalSignersAttribute(): int
    {
        if ($this->relationLoaded('signers')) {
            return $this->signers->count();
        }

        return (int) ($this->attributes['signers_count'] ?? $this->signers()->count());
    }

    /**
     * Scope to eager-load signer counts (avoids N+1).
     */
    public function scopeWithSignerCounts($query)
    {
        return $query->withCount([
            'signers',
            'signers as signed_signers_count' => fn ($q) => $q->where('status', SignatureStatus::SIGNED),
        ]);
    }

    /**
     * Determine if the signature is valid.
     */
    public function getIsValidAttribute(): bool
    {
        return $this->status === SignatureStatus::SIGNED;
    }
}
