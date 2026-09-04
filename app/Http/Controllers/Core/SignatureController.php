<?php

namespace App\Http\Controllers\Core;

use App\Enums\Core\SignatureStatus;
use App\Http\Controllers\Controller;
use App\Models\Core\Signature;
use App\Models\Core\SignatureSigner;
use App\Services\Core\SignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            if ($signer->status->value !== 'pending') {
                return view('signature.completed', ['signer' => $signer]);
            }

            $signature = $signer->signature;
            if (! $signature->signable) {
                abort(404, 'Le document associé est introuvable ou a été supprimé.');
            }

            $documentUrl = $signature->signable->getSignatureDocumentUrl($signature);
            $allSigners = $signature->signers;

            return view('signature.show', compact('signer', 'signature', 'documentUrl', 'allSigners'));
        }

        // Legacy single-signer fallback
        $signature = Signature::where('token', $token)->firstOrFail();

        if (! $signature->signable) {
            abort(404, 'Le document associé est introuvable ou a été supprimé.');
        }

        if ($signature->status->value !== 'pending') {
            return view('signature.completed', ['signature' => $signature]);
        }

        $documentUrl = $signature->signable->getSignatureDocumentUrl($signature);

        return view('signature.show', compact('signature', 'documentUrl'));
    }

    /**
     * Process the signature (supports both legacy and multi-signer).
     */
    public function sign(Request $request, string $token, SignatureService $service)
    {
        $signatureData = $this->validateSignatureData($request);

        // Try multi-signer first
        $signer = SignatureSigner::where('token', $token)->first();
        if ($signer) {
            if ($signer->status->value !== 'pending') {
                return redirect()->route('signature.show', $token)->with('error', 'Vous avez déjà signé ce document.');
            }

            $service->signAsSigner(
                $token,
                $signatureData,
                $request->ip(),
                $request->userAgent()
            );

            $this->handlePostSignature($signer->signature);

            return redirect()->route('signature.show', $token)->with('success', 'Document signé avec succès !');
        }

        // Legacy single-signer
        $signature = Signature::where('token', $token)->first();

        if (! $signature) {
            return redirect()->route('home')->with('error', 'Lien de signature invalide ou expiré.');
        }

        if (! $signature->signable) {
            abort(404, 'Le document associé est introuvable ou a été supprimé.');
        }

        if ($signature->status->value !== 'pending') {
            return redirect()->route('signature.show', $token)->with('error', 'Ce document a déjà été signé.');
        }

        $signature->update([
            'status' => SignatureStatus::SIGNED,
            'signature_data' => $signatureData,
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
     * For multi-signature, only runs when ALL signers have signed.
     */
    protected function handlePostSignature(Signature $signature): void
    {
        if (! $signature->signable) {
            return;
        }

        // Multi-signature : on attend que tous les signataires aient terminé
        if ($signature->is_multi_signatory && $signature->signers()->where('status', '!=', SignatureStatus::SIGNED)->exists()) {
            return;
        }

        try {
            // Logique métier (ex: Acceptation devis)
            $signature->signable->handlePostSignature($signature);

            // Stamping PDF via le trait HasSignature
            if (method_exists($signature->signable, 'stampSignatureDocument')) {
                $signature->signable->stampSignatureDocument($signature);
            }
        } catch (\Exception $e) {
            Log::error('Erreur post-signature pour '.class_basename($signature->signable).': '.$e->getMessage());
        }
    }

    /**
     * Validate and return the signature data from the request.
     */
    protected function validateSignatureData(Request $request): string
    {
        $request->validate([
            'signature_data' => ['required', 'string', 'max:2000000', 'regex:/^data:image\/(png|jpe?g);base64,[A-Za-z0-9+\/=]+$/'],
        ]);

        return $request->signature_data;
    }
}
