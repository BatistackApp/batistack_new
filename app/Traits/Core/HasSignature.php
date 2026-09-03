<?php

namespace App\Traits\Core;

use App\Enums\Commerce\QuoteStatus;
use App\Enums\Core\SignatureStatus;
use App\Enums\Tiers\ThirdPartyDocumentStatus;
use App\Models\Commerce\CustomerQuote;
use App\Models\Core\Signature;
use App\Models\RH\Contract;
use App\Models\RH\Employee;
use App\Models\Tiers\ThirdPartyDocument;
use App\Services\Commerce\QuoteService;
use App\Services\Core\PdfStamperService;
use App\Services\RH\RHDocumentService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

trait HasSignature
{
    /**
     * Get the URL of the document to be signed.
     */
    public function getSignatureDocumentUrl(Signature $signature): ?string
    {
        if (static::class === ThirdPartyDocument::class) {
            return $this->getFirstMediaUrl('third_party_documents');
        }

        if (static::class === Contract::class) {
            return Storage::disk('public')->url('documents/rh/contrat_'.$this->employee->registration_number.'.pdf');
        }

        if (static::class === Employee::class) {
            return Storage::disk('public')->url("documents/rh/onboarding/affiliation_probtp_{$this->id}_{$this->registration_number}.pdf");
        }

        if (static::class === CustomerQuote::class) {
            return Storage::disk('public')->url('documents/commerce/quotes/devis_'.$this->reference.'.pdf');
        }

        return null;
    }

    /**
     * Get the absolute path to the PDF for stamping.
     */
    public function getSignatureDocumentPath(): ?string
    {
        if (static::class === ThirdPartyDocument::class) {
            $media = $this->getFirstMedia('third_party_documents');

            return $media?->getPath();
        }

        if (static::class === Employee::class) {
            $media = $this->getMedia('rh_documents')->filter(function ($item) {
                return str_contains($item->file_name, 'affiliation_probtp');
            })->last();

            return $media?->getPath();
        }

        if (static::class === CustomerQuote::class) {
            return Storage::disk('public')->path('documents/commerce/quotes/devis_'.$this->reference.'.pdf');
        }

        return null;
    }

    /**
     * Get the signatory display name.
     */
    public function getSignatoryName(): ?string
    {
        if (static::class === ThirdPartyDocument::class) {
            return $this->thirdParty->name ?? null;
        }

        if (static::class === Employee::class) {
            return $this->full_name;
        }

        if (static::class === CustomerQuote::class) {
            return $this->client->name ?? null;
        }

        if (static::class === Contract::class) {
            return $this->employee->full_name ?? null;
        }

        return null;
    }

    /**
     * Handle post-signature logic for this model.
     */
    public function handlePostSignature(Signature $signature): void
    {
        if (static::class === Contract::class) {
            $this->update(['signature_status' => SignatureStatus::SIGNED]);
            app(RHDocumentService::class)->generateContract($this);

            return;
        }

        if (static::class === ThirdPartyDocument::class) {
            $this->update([
                'status' => ThirdPartyDocumentStatus::VALID,
                'signed_at' => now(),
            ]);

            return;
        }

        if (static::class === CustomerQuote::class) {
            try {
                $responsable = $signature->user;
                if ($responsable) {
                    app(QuoteService::class)->acceptQuote($this, $responsable);
                } else {
                    $this->update([
                        'status' => QuoteStatus::SIGNED,
                        'signed_at' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Erreur lors de l'acceptation automatique du devis post-signature : ".$e->getMessage());
            }

            return;
        }
    }

    /**
     * Stamp the document PDF with the signature certificate.
     */
    public function stampSignatureDocument(Signature $signature): void
    {
        $documentPath = $this->getSignatureDocumentPath();
        $signatoryName = $this->getSignatoryName();

        if ($documentPath && file_exists($documentPath)) {
            $stamper = app(PdfStamperService::class);
            $stampedPdfPath = $stamper->stamp($documentPath, $signature, $signatoryName);

            try {
                if ($this instanceof ThirdPartyDocument) {
                    $this->clearMediaCollection('third_party_documents');
                    $this->addMedia($stampedPdfPath)->toMediaCollection('third_party_documents');
                } elseif ($this instanceof Employee) {
                    $media = $this->getMedia('rh_documents')->filter(function ($item) {
                        return str_contains($item->file_name, 'affiliation_probtp');
                    })->last();
                    if ($media) {
                        $media->delete();
                    }
                    $this->addMedia($stampedPdfPath)->toMediaCollection('rh_documents');
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
}
