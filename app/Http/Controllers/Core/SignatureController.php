<?php

namespace App\Http\Controllers\Core;

use App\Enums\Core\SignatureStatus;
use App\Http\Controllers\Controller;
use App\Models\Core\Signature;
use App\Models\Tiers\ThirdPartyDocument;
use App\Services\Core\PdfStamperService;
use App\Services\Core\SignatureService;
use Illuminate\Http\Request;

class SignatureController extends Controller
{
    public function show(string $token)
    {
        $signature = Signature::where('token', $token)->firstOrFail();

        if ($signature->status !== SignatureStatus::PENDING) {
            return view('signature.completed');
        }

        // Si le document est un ThirdPartyDocument, on peut récupérer le lien vers le PDF
        $documentUrl = null;
        if ($signature->signable_type === ThirdPartyDocument::class) {
            $documentUrl = $signature->signable->getFirstMediaUrl('third_party_documents');
        }

        return view('signature.show', compact('signature', 'documentUrl'));
    }

    public function sign(Request $request, string $token, SignatureService $service)
    {
        $signature = Signature::where('token', $token)->firstOrFail();

        if ($signature->status !== SignatureStatus::PENDING) {
            return redirect()->route('signature.show', $token)->with('error', 'Ce document a déjà été signé.');
        }

        $request->validate([
            'signature_data' => 'required|string',
        ]);

        // Mise à jour de la signature existante
        // Attention : la méthode sign() du service crée actuellement une nouvelle signature.
        // Nous allons plutôt mettre à jour l'instance existante (pending -> signed).
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

        // Vérifier s'il y a un document associé (via MediaLibrary)
        if ($signature->signable_type === ThirdPartyDocument::class) {
            $media = $signature->signable->getFirstMedia('third_party_documents');
            if ($media) {
                $stamper = app(PdfStamperService::class);

                // Pour le nom, on essaie de récupérer le nom du signataire depuis le signable
                $signatoryName = $signature->signable->thirdParty->name ?? null;

                $stampedPdfPath = $stamper->stamp($media->getPath(), $signature, $signatoryName);

                // On supprime l'ancien document non signé
                $signature->signable->clearMediaCollection('third_party_documents');

                // On remplace le document original par le document certifié (stamped)
                $signature->signable->addMedia($stampedPdfPath)
                    ->toMediaCollection('third_party_documents');
            }
        }

        return redirect()->route('signature.show', $token)->with('success', 'Document signé avec succès !');
    }
}
