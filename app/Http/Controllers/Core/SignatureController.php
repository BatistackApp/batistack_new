<?php

namespace App\Http\Controllers\Core;

use App\Enums\Commerce\QuoteStatus;
use App\Enums\Core\SignatureStatus;
use App\Enums\Tiers\ThirdPartyDocumentStatus;
use App\Http\Controllers\Controller;
use App\Models\Commerce\CustomerQuote;
use App\Models\Core\Signature;
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
        } elseif ($signature->signable_type === Contract::class) {
            $documentUrl = Storage::disk('public')->url('documents/rh/contrat_'.$signature->signable->employee->registration_number.'.pdf');
        } elseif ($signature->signable_type === Employee::class) {
            $documentUrl = Storage::disk('public')->url("documents/rh/onboarding/affiliation_probtp_{$signature->signable->id}_{$signature->signable->registration_number}.pdf");
        } elseif ($signature->signable_type === CustomerQuote::class) {
            $documentUrl = Storage::disk('public')->url('documents/commerce/quotes/devis_'.$signature->signable->reference.'.pdf');
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
            if ($signature->signable_type === Contract::class) {
                $updates['signature_status'] = SignatureStatus::SIGNED;
            } elseif ($signature->signable_type === ThirdPartyDocument::class) {
                $updates['status'] = ThirdPartyDocumentStatus::VALID;
                $updates['signed_at'] = now();
            } elseif ($signature->signable_type === CustomerQuote::class) {
                // Pour un devis client, on déclenche le processus d'acceptation complète (création chantier/commande)
                // en utilisant l'utilisateur qui avait généré la demande de signature.
                try {
                    $responsable = User::find($signature->user_id);
                    if ($responsable) {
                        app(QuoteService::class)->acceptQuote($signature->signable, $responsable);
                    } else {
                        // Secours au cas où l'utilisateur n'existe plus
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
        }

        // Pour les contrats, le processus est de regénérer le document pour inclure la signature visuelle dans le blade
        if ($signature->signable_type === Contract::class) {
            app(RHDocumentService::class)->generateContract($signature->signable);
        } else {
            // Récupérer le chemin du document en fonction du type pour y apposer la signature via PdfStamperService
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
                    // Remplacer le fichier original par le fichier signé
                    if ($signature->signable_type === ThirdPartyDocument::class) {
                        $signature->signable->clearMediaCollection('third_party_documents');
                        $signature->signable->addMedia($stampedPdfPath)->toMediaCollection('third_party_documents');
                    } elseif ($signature->signable_type === Employee::class) {
                        if ($media) {
                            $media->delete();
                        }
                        $signature->signable->addMedia($stampedPdfPath)->toMediaCollection('rh_documents');
                    } else {
                        // Fichiers physiques standards (Quote)
                        File::copy($stampedPdfPath, $documentPath);
                    }
                } finally {
                    if (file_exists($stampedPdfPath)) {
                        @unlink($stampedPdfPath);
                    }
                }
            }
        }

        return redirect()->route('signature.show', $token)->with('success', 'Document signé avec succès !');
    }
}
