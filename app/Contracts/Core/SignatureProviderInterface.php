<?php

namespace App\Contracts\Core;

use App\Enums\Core\SignatureType;
use App\Models\Core\Signature;
use Illuminate\Database\Eloquent\Model;

interface SignatureProviderInterface
{
    /**
     * Initiate a signature request.
     */
    public function requestSignature(
        Model $model,
        SignatureType $type = SignatureType::AUTOGRAPH,
        ?string $email = null,
        ?string $name = null,
        ?string $documentPath = null
    ): Signature;

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
