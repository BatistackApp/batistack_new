<?php

namespace App\Traits\Core;

use App\Models\Core\Signature;
use App\Services\Core\PdfStamperService;
use Illuminate\Support\Facades\File;

trait HasSignature
{
    /**
     * Get the URL of the document to be signed.
     * Models can override via getSignatureUrl() method.
     */
    public function getSignatureDocumentUrl(Signature $signature): ?string
    {
        if (method_exists($this, 'getSignatureUrl')) {
            return $this->getSignatureUrl($signature);
        }

        return null;
    }

    /**
     * Get the absolute path to the PDF for stamping.
     * Models can override via getSignaturePath() method.
     */
    public function getSignatureDocumentPath(): ?string
    {
        if (method_exists($this, 'getSignaturePath')) {
            return $this->getSignaturePath();
        }

        return null;
    }

    /**
     * Get the signatory display name.
     * Models can override via getSignatoryDisplayName() method.
     */
    public function getSignatoryName(): ?string
    {
        if (method_exists($this, 'getSignatoryDisplayName')) {
            return $this->getSignatoryDisplayName();
        }

        return null;
    }

    /**
     * Handle post-signature logic for this model.
     * Models can override via onPostSignature() method.
     */
    public function handlePostSignature(Signature $signature): void
    {
        if (method_exists($this, 'onPostSignature')) {
            $this->onPostSignature($signature);
        }
    }

    /**
     * Stamp the document PDF with the signature certificate.
     * Models can override via onStampSignature() method.
     */
    public function stampSignatureDocument(Signature $signature): void
    {
        if (method_exists($this, 'onStampSignature')) {
            $this->onStampSignature($signature);

            return;
        }

        // Default: stamp using PdfStamperService
        $documentPath = $this->getSignatureDocumentPath();
        $signatoryName = $this->getSignatoryName();

        if ($documentPath && file_exists($documentPath)) {
            $stamper = app(PdfStamperService::class);
            $stampedPdfPath = $stamper->stamp($documentPath, $signature, $signatoryName);

            try {
                // Use Spatie Media for models that support it
                if (method_exists($this, 'clearMediaCollection') && method_exists($this, 'addMedia')) {
                    $mediaCollection = $this->getSignatureMediaCollection();
                    if ($mediaCollection) {
                        $this->clearMediaCollection($mediaCollection);
                        $this->addMedia($stampedPdfPath)->toMediaCollection($mediaCollection);
                    }
                } else {
                    File::copy($stampedPdfPath, $documentPath);
                }
            } finally {
                if (file_exists($stampedPdfPath)) {
                    @unlink($stampedPdfPath);
                }
            }
        }
    }

    /**
     * Get the Spatie Media collection name for signed documents.
     * Override in models that use Spatie Media Library.
     */
    protected function getSignatureMediaCollection(): ?string
    {
        return null;
    }
}
