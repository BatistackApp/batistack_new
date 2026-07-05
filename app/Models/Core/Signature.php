<?php

namespace App\Models\Core;

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
}
