<?php

namespace App\Traits\Core;

use App\Enums\Core\SignatureStatus;
use App\Models\Core\Signature;
use App\Services\Core\PdfStamperService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

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
            if ($relativePath && Storage::disk($this->getStampedDisk())->exists($relativePath)) {
                return $this->getStampedUrlForPath($relativePath);
            }
        }

        return null;
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
            if ($relativePath && Storage::disk($this->getStampedDisk())->exists($relativePath)) {
                return Storage::disk($this->getStampedDisk())->path($relativePath);
            }
        }

        return null;
    }

    public function discardStampedSignatureDocument(?Signature $signature = null): void
    {
        $path = $this->getStampedDocumentPath($signature);

        if ($path && file_exists($path)) {
            @unlink($path);
        }
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

        if (! $documentPath || ! is_readable($documentPath)) {
            throw new \RuntimeException('Le document source de la signature est introuvable ou illisible.');
        }

        if ($documentPath) {
            // Load signed signers for multi-signer stamping
            $signers = $signature->signers()
                ->where('status', SignatureStatus::SIGNED)
                ->get()
                ->all();

            $stamper = app(PdfStamperService::class);
            $documentChecksum = null;
            $stampedPdfPath = $stamper->stamp($documentPath, $signature, $signatoryName, $signers ?: null, $documentChecksum);
            if (! $stampedPdfPath || ! is_readable($stampedPdfPath)) {
                throw new \RuntimeException('Le document signé n’a pas pu être généré.');
            }

            if ($documentChecksum) {
                $signature->update(['document_checksum' => $documentChecksum]);
            }

            try {
                // The stamped PDF is stored in a DEDICATED location so that the source
                // document (getSignaturePath / getSignatureDocumentUrl) stays untouched:
                // this avoids re-stamping an already-stamped PDF on a second signature.
                $stampedMediaCollection = $this->getStampedMediaCollection();

                if ($stampedMediaCollection && method_exists($this, 'clearMediaCollection') && method_exists($this, 'addMedia')) {
                    $this->addMedia($stampedPdfPath)->toMediaCollection($stampedMediaCollection);
                } elseif (method_exists($this, 'getStampedPath')) {
                    $relativePath = $this->getStampedPath();
                    if ($relativePath) {
                        $stampedDisk = $this->getStampedDisk();
                        Storage::disk($stampedDisk)->makeDirectory(dirname($relativePath));

                        $fullPath = Storage::disk($stampedDisk)->path($relativePath);
                        if (! File::copy($stampedPdfPath, $fullPath) || ! is_readable($fullPath)) {
                            throw new \RuntimeException('Le document signé n’a pas pu être stocké.');
                        }
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
