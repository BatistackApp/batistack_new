<?php

namespace App\Http\Controllers\Core;

use App\Enums\Commerce\QuoteStatus;
use App\Enums\Core\SignatureStatus;
use App\Enums\Tiers\ThirdPartyDocumentStatus;
use App\Http\Controllers\Controller;
use App\Models\Commerce\CustomerQuote;
use App\Models\Core\Signature;
use App\Models\Core\SignatureSigner;
use App\Models\RH\Contract;
use App\Models\RH\Employee;
use App\Models\Tiers\ThirdPartyDocument;
use App\Models\User;
use App\Services\Commerce\QuoteService;
use App\Services\Core\PdfStamperService;
use App\Services\Core\SignatureService;
use App\Services\RH\RHDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SignatureController extends Controller
{
    /**
     * Show the signature portal page.
     * Supports both legacy single-signer (token on Signature) and new multi-signer (token on SignatureSigner).
     */
    public function show(string $token)
    {
        // Try multi-signer first
        $signer = SignatureSigner::where('token', $token)->first();
        if ($signer) {
            if ($signer->status !== SignatureStatus::PENDING) {
                return view('signature.completed', ['signer' => $signer]);
            }

            $signature = $signer->signature;
            if (! $signature->signable) {
                abort(404, 'Le document associé est introuvable ou a été supprimé.');
            }

            $documentUrl = $this->resolveDocumentUrl($signature);
            $allSigners = $signature->signers;

            return view('signature.show', compact('signer', 'signature', 'documentUrl', 'allSigners'));
        }

        // Legacy single-signer fallback
        $signature = Signature::where('token', $token)->firstOrFail();

        if (! $signature->signable) {
            abort(404, 'Le document associé est introuvable ou a été supprimé.');
        }

        if ($signature->status !== SignatureStatus::PENDING) {
            return view('signature.completed', ['signature' => $signature]);
        }

        $documentUrl = $this->resolveDocumentUrl($signature);

        return view('signature.show', compact('signature', 'documentUrl'));
    }

    /**
     * Process the signature (supports both legacy and multi-signer).
     */
    public function sign(Request $request, string $token, SignatureService $service)
    {
        $request->validate([
            'signature_data' => ['required', 'string', 'max:2000000', 'regex:/^data:image\/(png|jpe?g);base64,[A-Za-z0-9+\/=]+$/'],
        ]);

        // Try multi-signer first
        $signer = SignatureSigner::where('token', $token)->first();
        if ($signer) {
            if ($signer->status !== SignatureStatus::PENDING) {
                return redirect()->route('signature.show', $token)->with('error', 'Vous avez déjà signé ce document.');
            }

            $service->signAsSigner(
                $token,
                $request->signature_data,
                $request->ip(),
                $request->userAgent()
            );

            // Post-signature logic for the parent model
            $this->handlePostSignature($signer->signature);

            return redirect()->route('signature.show', $token)->with('success', 'Document signé avec succès !');
        }

        // Legacy single-signer
        $signature = Signature::where('token', $token)->firstOrFail();

        if (! $signature->signable) {
            abort(404, 'Le document associé est introuvable ou a été supprimé.');
        }

        if ($signature->status !== SignatureStatus::PENDING) {
            return redirect()->route('signature.show', $token)->with('error', 'Ce document a déjà été signé.');
        }

        $signature->update([
            'status' => SignatureStatus::SIGNED,
            'signature_data' => $request->signature_data,
            'ip_address' => $request->ip(),
            'signed_at' => now(),
            'metadata' => array_merge($signature->metadata ?? [], [
                'user_agent' => $request->userAgent(),
                'source' => 'external_public_link',
            ]),
        ]);

        $this->handlePostSignature($signature);

        return redirect()->route('signature.show', $token)->with('success', 'Document signé avec succès !');
    }

    /**
     * Refuse a signature (multi-signer workflow only).
     */
    public function refuse(Request $request, string $token, SignatureService $service)
    {
        $signer = SignatureSigner::where('token', $token)
            ->where('status', SignatureStatus::PENDING)
            ->firstOrFail();

        $service->refuseAsSigner($token, $request->input('reason'));

        return redirect()->route('signature.show', $token)->with('success', 'Signature refusée. Le donneur d\'ordre a été notifié.');
    }

    /**
     * Handle post-signature logic based on model type.
     */
    protected function handlePostSignature(Signature $signature): void
    {
        if (! $signature->signable) {
            return;
        }

        // For multi-signature, only run post-signature when ALL have signed
        if ($signature->is_multi_signatory && $signature->status !== SignatureStatus::SIGNED) {
            return;
        }

        $updates = [];
        if ($signature->signable_type === Contract::class) {
            $updates['signature_status'] = SignatureStatus::SIGNED;
        } elseif ($signature->signable_type === ThirdPartyDocument::class) {
            $updates['status'] = ThirdPartyDocumentStatus::VALID;
            $updates['signed_at'] = now();
        } elseif ($signature->signable_type === CustomerQuote::class) {
            try {
                $responsable = User::find($signature->user_id);
                if ($responsable) {
                    app(QuoteService::class)->acceptQuote($signature->signable, $responsable);
                } else {
                    $signature->signable->update([
                        'status' => QuoteStatus::SIGNED,
                        'signed_at' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Erreur lors de l'acceptation automatique du devis post-signature : ".$e->getMessage());
            }
        }

        if (! empty($updates)) {
            $signature->signable->update($updates);
        }

        // PDF stamping
        if ($signature->signable_type === Contract::class) {
            app(RHDocumentService::class)->generateContract($signature->signable);
        } else {
            $this->stampDocument($signature);
        }
    }

    /**
     * Stamp the document PDF with the signature certificate.
     */
    protected function stampDocument(Signature $signature): void
    {
        $documentPath = null;
        $signatoryName = null;
        $media = null;

        if ($signature->signable_type === ThirdPartyDocument::class) {
            $media = $signature->signable->getFirstMedia('third_party_documents');
            if ($media) {
                $documentPath = $media->getPath();
            }
            $signatoryName = $signature->signable->thirdParty->name ?? null;
        } elseif ($signature->signable_type === Employee::class) {
            $media = $signature->signable->getMedia('rh_documents')->filter(function ($item) {
                return str_contains($item->file_name, 'affiliation_probtp');
            })->last();
            if ($media) {
                $documentPath = $media->getPath();
            }
            $signatoryName = $signature->signable->full_name;
        } elseif ($signature->signable_type === CustomerQuote::class) {
            $documentPath = Storage::disk('public')->path('documents/commerce/quotes/devis_'.$signature->signable->reference.'.pdf');
            $signatoryName = $signature->signable->client->name ?? null;
        }

        if ($documentPath && file_exists($documentPath)) {
            $stamper = app(PdfStamperService::class);
            $stampedPdfPath = $stamper->stamp($documentPath, $signature, $signatoryName);

            try {
                if ($signature->signable_type === ThirdPartyDocument::class) {
                    $signature->signable->clearMediaCollection('third_party_documents');
                    $signature->signable->addMedia($stampedPdfPath)->toMediaCollection('third_party_documents');
                } elseif ($signature->signable_type === Employee::class) {
                    if ($media) {
                        $media->delete();
                    }
                    $signature->signable->addMedia($stampedPdfPath)->toMediaCollection('rh_documents');
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
     * Resolve the document URL based on the signable model type.
     */
    protected function resolveDocumentUrl(Signature $signature): ?string
    {
        if ($signature->signable_type === ThirdPartyDocument::class) {
            return $signature->signable->getFirstMediaUrl('third_party_documents');
        } elseif ($signature->signable_type === Contract::class) {
            return Storage::disk('public')->url('documents/rh/contrat_'.$signature->signable->employee->registration_number.'.pdf');
        } elseif ($signature->signable_type === Employee::class) {
            return Storage::disk('public')->url("documents/rh/onboarding/affiliation_probtp_{$signature->signable->id}_{$signature->signable->registration_number}.pdf");
        } elseif ($signature->signable_type === CustomerQuote::class) {
            return Storage::disk('public')->url('documents/commerce/quotes/devis_'.$signature->signable->reference.'.pdf');
        }

        return null;
    }
}
