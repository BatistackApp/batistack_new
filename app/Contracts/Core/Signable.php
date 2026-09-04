<?php

namespace App\Contracts\Core;

use App\Models\Core\Signature;

interface Signable
{
    /**
     * Get the URL of the document to be signed.
     */
    public function getSignatureDocumentUrl(Signature $signature): ?string;

    /**
     * Get the absolute path to the PDF for stamping.
     */
    public function getSignatureDocumentPath(): ?string;

    /**
     * Get the signatory display name.
     */
    public function getSignatoryName(): ?string;

    /**
     * Handle post-signature logic for this model.
     */
    public function handlePostSignature(Signature $signature): void;
}
