<?php

namespace App\Services\Core;

use App\Enums\Core\SignatureType;
use App\Models\Core\Signature;
use App\Models\Core\SignatureSigner;
use App\Services\Core\Providers\DocusealProvider;
use App\Services\Core\Providers\LocalSignatureProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Manager;

class SignatureService extends Manager
{
    /**
     * Get the default driver name.
     */
    public function getDefaultDriver()
    {
        return $this->config->get('signature.default', 'local');
    }

    /**
     * Create an instance of the "local" signature driver.
     */
    protected function createLocalDriver(): LocalSignatureProvider
    {
        return new LocalSignatureProvider;
    }

    /**
     * Create an instance of the "docuseal" signature driver.
     */
    protected function createDocusealDriver(): DocusealProvider
    {
        return new DocusealProvider;
    }

    /**
     * Convenience: request a multi-signature workflow.
     *
     * @param  array<array{name: string, email: string, role?: string, user_id?: int}>  $signers
     */
    public function requestMultiSignature(
        Model $model,
        SignatureType $type,
        array $signers,
        ?string $documentPath = null
    ): Signature {
        return $this->driver()->requestMultiSignature($model, $type, $signers, $documentPath);
    }

    /**
     * Convenience: sign as a specific signer via public portal.
     */
    public function signAsSigner(
        string $token,
        string $signatureData,
        string $ipAddress,
        string $userAgent
    ): SignatureSigner {
        return $this->driver()->signAsSigner($token, $signatureData, $ipAddress, $userAgent);
    }

    /**
     * Convenience: refuse as a specific signer via public portal.
     */
    public function refuseAsSigner(
        string $token,
        ?string $reason = null
    ): SignatureSigner {
        return $this->driver()->refuseAsSigner($token, $reason);
    }
}
