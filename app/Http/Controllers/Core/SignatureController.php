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

        if (! $signature->signable) {
            abort(404, 'Le document associé est introuvable ou a été supprimé.');
        }

        if ($signature->status !== SignatureStatus::PENDING) {
            return view('signature.completed');
        }

        $documentUrl = null;
        if ($signature->signable_type === ThirdPartyDocument::class) {
            $documentUrl = $signature->signable->getFirstMediaUrl('third_party_documents');
        } elseif ($signature->signable_type === \App\Models\RH\Contract::class) {
            $documentUrl = \Illuminate\Support\Facades\Storage::disk('public')->url('documents/rh/contrat_'.$signature->signable->employee->registration_number.'.pdf');
        } elseif ($signature->signable_type === \App\Models\RH\Employee::class) {
            $documentUrl = \Illuminate\Support\Facades\Storage::disk('public')->url("documents/rh/onboarding/affiliation_probtp_{$signature->signable->id}_{$signature->signable->registration_number}.pdf");
        } elseif ($signature->signable_type === \App\Models\Commerce\CustomerQuote::class) {
            $documentUrl = \Illuminate\Support\Facades\Storage::disk('public')->url('documents/commerce/quotes/devis_'.$signature->signable->reference.'.pdf');
        }

        return view('signature.show', compact('signature', 'documentUrl'));
    }

    public function sign(Request $request, string $token, SignatureService $service)
    {
        $signature = Signature::where('token', $token)->firstOrFail();

        if (! $signature->signable) {
            abort(404, 'Le document associé est introuvable ou a été supprimé.');
        }

        if ($signature->status !== SignatureStatus::PENDING) {
            return redirect()->route('signature.show', $token)->with('error', 'Ce document a déjà été signé.');
        }

        $request->validate([
            'signature_data' => ['required', 'string', 'max:2000000', 'regex:/^data:image\/(png|jpe?g);base64,[A-Za-z0-9+\/=]+$/'],
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

        // Mise à jour de l'état du document lié
        if ($signature->signable) {
            $updates = [];
            if ($signature->signable_type === \App\Models\RH\Contract::class) {
                $updates['signature_status'] = SignatureStatus::SIGNED;
            } elseif ($signature->signable_type === \App\Models\Tiers\ThirdPartyDocument::class) {
                $updates['status'] = \App\Enums\Tiers\ThirdPartyDocumentStatus::VALID;
                $updates['signed_at'] = now();
            }

            if (!empty($updates)) {
                $signature->signable->update($updates);
            }
        }

        // Récupérer le chemin du document en fonction du type pour y apposer la signature
        $documentPath = null;
        $signatoryName = null;
        $media = null;

        if ($signature->signable_type === ThirdPartyDocument::class) {
            $media = $signature->signable->getFirstMedia('third_party_documents');
            if ($media) {
                $documentPath = $media->getPath();
            }
            $signatoryName = $signature->signable->thirdParty->name ?? null;
        } elseif ($signature->signable_type === \App\Models\RH\Contract::class) {
            $documentPath = \Illuminate\Support\Facades\Storage::disk('public')->path('documents/rh/contrat_'.$signature->signable->employee->registration_number.'.pdf');
            $signatoryName = $signature->signable->employee->full_name;
        } elseif ($signature->signable_type === \App\Models\RH\Employee::class) {
            $media = $signature->signable->getMedia('rh_documents')->filter(function ($item) {
                return str_contains($item->file_name, 'affiliation_probtp');
            })->last();
            if ($media) {
                $documentPath = $media->getPath();
            }
            $signatoryName = $signature->signable->full_name;
        } elseif ($signature->signable_type === \App\Models\Commerce\CustomerQuote::class) {
            $documentPath = \Illuminate\Support\Facades\Storage::disk('public')->path('documents/commerce/quotes/devis_'.$signature->signable->reference.'.pdf');
            $signatoryName = $signature->signable->client->name ?? null;
        }

        if ($documentPath && file_exists($documentPath)) {
            $stamper = app(PdfStamperService::class);
            $stampedPdfPath = $stamper->stamp($documentPath, $signature, $signatoryName);

            try {
                // Remplacer le fichier original par le fichier signé
                if ($signature->signable_type === ThirdPartyDocument::class) {
                    $signature->signable->clearMediaCollection('third_party_documents');
                    $signature->signable->addMedia($stampedPdfPath)->toMediaCollection('third_party_documents');
                } elseif ($signature->signable_type === \App\Models\RH\Employee::class) {
                    if ($media) {
                        $media->delete();
                    }
                    $signature->signable->addMedia($stampedPdfPath)->toMediaCollection('rh_documents');
                } else {
                    // Fichiers physiques standards (Contract, Quote)
                    \Illuminate\Support\Facades\File::copy($stampedPdfPath, $documentPath);
                }
            } finally {
                if (file_exists($stampedPdfPath)) {
                    @unlink($stampedPdfPath);
                }
            }
        }

        return redirect()->route('signature.show', $token)->with('success', 'Document signé avec succès !');
    }
}
