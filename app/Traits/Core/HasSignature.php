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
     * Get the URL of the final stamped/signed document.
     * Models can override via getStampedDocumentUrl() method (stamped copy).
     * Defaults to the source document URL when the model does not store a distinct stamped copy.
     */
    public function getStampedDocumentUrl(Signature $signature): ?string
    {
        $stampedMediaCollection = $this->getStampedMediaCollection();

        if ($stampedMediaCollection && method_exists($this, 'getMedia')) {
            $media = $this->getMedia($stampedMediaCollection)->first();
            if ($media) {
                return $media->getUrl();
            }
        }

        if (method_exists($this, 'getStampedPath')) {
            $relativePath = $this->getStampedPath();
            if ($relativePath && \Illuminate\Support\Facades\Storage::disk($this->getStampedDisk())->exists($relativePath)) {
                return $this->getStampedUrlForPath($relativePath);
            }
        }

        return $this->getSignatureDocumentUrl($signature);
    }

    /**
     * Get the absolute path of the final stamped/signed document.
     * Models can override via getStampedPath() method (file-based storage).
     */
    public function getStampedDocumentPath(?Signature $signature = null): ?string
    {
        $stampedMediaCollection = $this->getStampedMediaCollection();

        if ($stampedMediaCollection && method_exists($this, 'getMedia')) {
            $media = $this->getMedia($stampedMediaCollection)->first();
            if ($media) {
                return $media->getPath();
            }
        }

        if (method_exists($this, 'getStampedPath')) {
            $relativePath = $this->getStampedPath();
            if ($relativePath && \Illuminate\Support\Facades\Storage::disk($this->getStampedDisk())->exists($relativePath)) {
                return \Illuminate\Support\Facades\Storage::disk($this->getStampedDisk())->path($relativePath);
            }
        }

        return $this->getSignatureDocumentPath();
    }

    /**
     * Resolve the public URL for a stamped file-based document.
     * Models can override when the stamped copy lives on a specific disk.
     */
    protected function getStampedUrlForPath(string $stampedPath): ?string
    {
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
            // Load signed signers for multi-signer stamping
            $signers = $signature->signers()
                ->where('status', \App\Enums\Core\SignatureStatus::SIGNED)
                ->get()
                ->all();

            $stamper = app(PdfStamperService::class);
            $documentChecksum = null;
            $stampedPdfPath = $stamper->stamp($documentPath, $signature, $signatoryName, $signers ?: null, $documentChecksum);

            if ($documentChecksum) {
                $signature->update(['document_checksum' => $documentChecksum]);
            }

            try {
                // The stamped PDF is stored in a DEDICATED location so that the source
                // document (getSignaturePath / getSignatureDocumentUrl) stays untouched:
                // this avoids re-stamping an already-stamped PDF on a second signature.
                $stampedMediaCollection = $this->getStampedMediaCollection();

                if ($stampedMediaCollection && method_exists($this, 'clearMediaCollection') && method_exists($this, 'addMedia')) {
                    $this->clearMediaCollection($stampedMediaCollection);
                    $this->addMedia($stampedPdfPath)->toMediaCollection($stampedMediaCollection);
                } elseif (method_exists($this, 'getStampedPath')) {
                    $relativePath = $this->getStampedPath();
                    if ($relativePath) {
                        $stampedDisk = $this->getStampedDisk();
                        \Illuminate\Support\Facades\Storage::disk($stampedDisk)->makeDirectory(dirname($relativePath));

                        $fullPath = \Illuminate\Support\Facades\Storage::disk($stampedDisk)->path($relativePath);
                        File::copy($stampedPdfPath, $fullPath);
                    }
                } else {
                    // Legacy fallback: overwrite the source in place (models without
                    // dedicated stamped storage).
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
     * Get the Spatie Media collection name used for the SOURCE document.
     * Override in models that use Spatie Media Library.
     */
    protected function getSignatureMediaCollection(): ?string
    {
        return null;
    }

    /**
     * Get the Spatie Media collection name used for the STAMPED (signed) document.
     * Must differ from the source collection so that re-signing never re-stamps
     * an already-stamped document.
     */
    protected function getStampedMediaCollection(): ?string
    {
        return null;
    }

    /**
     * Relative path (on getStampedDisk()) of the STAMPED document for file-based models.
     */
    protected function getStampedPath(): ?string
    {
        return null;
    }

    /**
     * Disk used for the stamped file-based document.
     */
    public function getStampedDisk(): string
    {
        return 'public';
    }
}
