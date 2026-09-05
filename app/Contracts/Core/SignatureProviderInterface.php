<?php

namespace App\Contracts\Core;

use App\Enums\Core\SignatureType;
use App\Models\Core\Signature;
use App\Models\Core\SignatureSigner;
use Illuminate\Database\Eloquent\Model;

interface SignatureProviderInterface
{
    /**
     * Initiate a signature request (single signer - legacy).
     */
    public function requestSignature(
        Model $model,
        SignatureType $type = SignatureType::AUTOGRAPH,
        ?string $email = null,
        ?string $name = null,
        ?string $documentPath = null
    ): Signature;

    /**
     * Initiate a multi-signature request (parallel workflow).
     *
     * @param  array<array{name: string, email: string, role?: string, user_id?: int}>  $signers
     */
    public function requestMultiSignature(
        Model $model,
        SignatureType $type,
        array $signers,
        ?string $documentPath = null
    ): Signature;

    /**
     * Process an incoming signature from the public portal (signer-specific).
     */
    public function signAsSigner(
        string $token,
        string $signatureData,
        string $ipAddress,
        string $userAgent
    ): SignatureSigner;

    /**
     * Refuse a signature from the public portal (signer-specific).
     */
    public function refuseAsSigner(
        string $token,
        ?string $reason = null
    ): SignatureSigner;

    /**
     * Process an incoming signature (e.g., from local input or from a webhook/callback).
     */
    public function sign(
        Model $model,
        ?string $signatureData,
        SignatureType $type = SignatureType::AUTOGRAPH,
        array $additionalMetadata = []
    ): Signature;

    /**
     * Verify the integrity or status of a signature.
     */
    public function verify(Signature $signature): bool;
}
