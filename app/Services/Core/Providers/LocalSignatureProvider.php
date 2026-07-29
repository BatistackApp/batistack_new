<?php

namespace App\Services\Core\Providers;

use App\Contracts\Core\SignatureProviderInterface;
use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Models\Core\Signature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\Core\SignatureRequestedMail;

/**
 * Service de gestion de la signature numérique "maison".
 * Sécurise l'intégrité des documents par checksum et gère les états via Enums.
 */
class LocalSignatureProvider implements SignatureProviderInterface
{
    /**
     * Enregistre une signature finalisée pour un document donné.
     *
     * * @param Model $model Le document à signer (Devis, Facture, etc.)
     * @param  string|null  $signatureData  Données de signature (Base64 pour autographe, ou log)
     * @param  SignatureType  $type  Le mode de signature utilisé
     * @param  array  $additionalMetadata  Métadonnées supplémentaires
     */
    public function sign(
        Model $model,
        ?string $signatureData,
        SignatureType $type = SignatureType::AUTOGRAPH,
        array $additionalMetadata = []
    ): Signature {
        return Signature::create([
            'token' => Str::uuid()->toString(),
            'signable_type' => $model->getMorphClass(),
            'signable_id' => $model->id,
            'user_id' => Auth::id(),
            'status' => SignatureStatus::SIGNED,
            'type' => $type,
            'signature_data' => $signatureData,
            'checksum' => $this->generateChecksum($model),
            'ip_address' => request()->ip(),
            'signed_at' => now(),
            'metadata' => array_merge([
                'user_agent' => request()->userAgent(),
                'source' => 'internal_erp',
            ], $additionalMetadata),
        ]);
    }

    /**
     * Crée une demande de signature en attente.
     */
    public function requestSignature(
        Model $model, 
        SignatureType $type = SignatureType::AUTOGRAPH,
        ?string $email = null,
        ?string $name = null,
        ?string $documentPath = null
    ): Signature {
        $signature = Signature::create([
            'token' => Str::uuid()->toString(),
            'signable_type' => $model->getMorphClass(),
            'signable_id' => $model->id,
            'user_id' => Auth::id(),
            'status' => SignatureStatus::PENDING,
            'type' => $type,
            'checksum' => $this->generateChecksum($model),
            'metadata' => [
                'requested_at' => now()->toDateTimeString(),
            ],
        ]);

        if ($email && $name) {
            Mail::to($email)->send(new SignatureRequestedMail($signature, $name, $documentPath));
        }

        return $signature;
    }

    /**
     * Vérifie si la signature est toujours valide par rapport à l'état actuel du document.
     * Si le document a été modifié depuis la signature, le checksum ne correspondra plus.
     */
    public function verify(Signature $signature): bool
    {
        if ($signature->status !== SignatureStatus::SIGNED) {
            return false;
        }

        $currentChecksum = $this->generateChecksum($signature->signable);

        return hash_equals($signature->checksum, $currentChecksum);
    }

    /**
     * Génère une empreinte unique (SHA-256) basée sur les attributs du modèle.
     */
    protected function generateChecksum(Model $model): string
    {
        // On sérialise les données du modèle pour détecter toute modification ultérieure
        // Note : On pourrait exclure certains champs comme 'updated_at' si nécessaire
        return hash('sha256', json_encode($model->toArray()));
    }
}
