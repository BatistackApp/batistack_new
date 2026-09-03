<?php

namespace App\Services\Core\Providers;

use App\Contracts\Core\SignatureProviderInterface;
use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Mail\Core\MultiSignatureRequestedMail;
use App\Models\Core\Signature;
use App\Models\Core\SignatureSigner;
use App\Notifications\Core\SignatureCompletedNotification;
use App\Notifications\Core\SignatureRefusedNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Service de gestion de la signature numérique "maison".
 * Sécurise l'intégrité des documents par checksum et gère les états via Enums.
 */
class LocalSignatureProvider implements SignatureProviderInterface
{
    /**
     * Enregistre une signature finalisée pour un document donné.
     */
    public function sign(
        Model $model,
        ?string $signatureData,
        SignatureType $type = SignatureType::AUTOGRAPH,
        array $additionalMetadata = []
    ): Signature {
        return Signature::create([
            'token' => Str::uuid()->toString(),
            'signable_type' => $model->getMorphClass(),
            'signable_id' => $model->id,
            'user_id' => Auth::id(),
            'status' => SignatureStatus::SIGNED,
            'type' => $type,
            'signature_data' => $signatureData,
            'checksum' => $this->generateChecksum($model),
            'ip_address' => request()->ip(),
            'signed_at' => now(),
            'metadata' => array_merge([
                'user_agent' => request()->userAgent(),
                'source' => 'internal_erp',
            ], $additionalMetadata),
        ]);
    }

    /**
     * Crée une demande de signature en attente (single signer - legacy).
     */
    public function requestSignature(
        Model $model,
        SignatureType $type = SignatureType::AUTOGRAPH,
        ?string $email = null,
        ?string $name = null,
        ?string $documentPath = null
    ): Signature {
        $emailToSend = null;

        $signature = DB::transaction(function () use ($model, $type, $email, $name, &$emailToSend) {
            $signature = Signature::create([
                'token' => Str::uuid()->toString(),
                'signable_type' => $model->getMorphClass(),
                'signable_id' => $model->id,
                'user_id' => Auth::id(),
                'status' => SignatureStatus::PENDING,
                'type' => $type,
                'checksum' => $this->generateChecksum($model),
                'metadata' => [
                    'requested_at' => now()->toDateTimeString(),
                ],
            ]);

            // Create a single signer for backward compatibility
            if ($email && $name) {
                $signer = SignatureSigner::create([
                    'signature_id' => $signature->id,
                    'name' => $name,
                    'email' => $email,
                    'user_id' => Auth::id(),
                    'role' => 'Signataire',
                    'status' => SignatureStatus::PENDING,
                    'token' => Str::uuid()->toString(),
                    'metadata' => [
                        'requested_at' => now()->toDateTimeString(),
                    ],
                ]);

                $emailToSend = ['email' => $email, 'signer' => $signer];
            }

            return $signature;
        });

        // Dispatch email AFTER transaction commits
        if ($emailToSend) {
            DB::afterCommit(function () use ($emailToSend, $documentPath) {
                Mail::to($emailToSend['email'])->send(new MultiSignatureRequestedMail($emailToSend['signer'], $documentPath));
            });
        }

        return $signature;
    }

    /**
     * Crée une demande de signature multi-signataires (workflow parallèle).
     *
     * @param  array<array{name: string, email: string, role?: string, user_id?: int}>  $signers
     */
    public function requestMultiSignature(
        Model $model,
        SignatureType $type,
        array $signers,
        ?string $documentPath = null
    ): Signature {
        $emailsToSend = [];

        $signature = DB::transaction(function () use ($model, $type, $signers, &$emailsToSend) {
            $signature = Signature::create([
                'token' => Str::uuid()->toString(),
                'signable_type' => $model->getMorphClass(),
                'signable_id' => $model->id,
                'user_id' => Auth::id(),
                'status' => SignatureStatus::PENDING,
                'type' => $type,
                'checksum' => $this->generateChecksum($model),
                'metadata' => [
                    'requested_at' => now()->toDateTimeString(),
                    'signers_count' => count($signers),
                ],
            ]);

            foreach ($signers as $signerData) {
                $signer = SignatureSigner::create([
                    'signature_id' => $signature->id,
                    'name' => $signerData['name'],
                    'email' => $signerData['email'],
                    'user_id' => $signerData['user_id'] ?? null,
                    'role' => $signerData['role'] ?? 'Signataire',
                    'status' => SignatureStatus::PENDING,
                    'token' => Str::uuid()->toString(),
                    'metadata' => [
                        'requested_at' => now()->toDateTimeString(),
                    ],
                ]);

                $emailsToSend[] = ['email' => $signerData['email'], 'signer' => $signer];
            }

            return $signature;
        });

        // Dispatch emails AFTER transaction commits
        DB::afterCommit(function () use ($emailsToSend, $documentPath) {
            foreach ($emailsToSend as $item) {
                Mail::to($item['email'])->send(new MultiSignatureRequestedMail($item['signer'], $documentPath));
            }
        });

        return $signature;
    }

    /**
     * Signe en tant que signataire via le portail public.
     */
    public function signAsSigner(
        string $token,
        string $signatureData,
        string $ipAddress,
        string $userAgent
    ): SignatureSigner {
        $signer = SignatureSigner::where('token', $token)
            ->where('status', SignatureStatus::PENDING)
            ->firstOrFail();

        $signer->update([
            'status' => SignatureStatus::SIGNED,
            'signature_data' => $signatureData,
            'ip_address' => $ipAddress,
            'signed_at' => now(),
            'metadata' => array_merge($signer->metadata ?? [], [
                'user_agent' => $userAgent,
                'source' => 'external_public_link',
            ]),
        ]);

        // Check if all signers have signed
        $signature = $signer->signature;
        $allSigned = ! $signature->signers()
            ->where('status', '!=', SignatureStatus::SIGNED)
            ->exists();

        if ($allSigned) {
            $signature->update([
                'status' => SignatureStatus::SIGNED,
                'signed_at' => now(),
            ]);

            // Dispatch completion notification
            $this->dispatchCompletionNotification($signature);
        }

        return $signer;
    }

    /**
     * Refuse en tant que signataire via le portail public.
     */
    public function refuseAsSigner(
        string $token,
        ?string $reason = null
    ): SignatureSigner {
        $signer = SignatureSigner::where('token', $token)
            ->where('status', SignatureStatus::PENDING)
            ->firstOrFail();

        $signer->update([
            'status' => SignatureStatus::REFUSED,
            'metadata' => array_merge($signer->metadata ?? [], [
                'refused_at' => now()->toDateTimeString(),
                'refusal_reason' => $reason,
            ]),
        ]);

        // The whole workflow is stopped
        $signature = $signer->signature;
        $signature->update([
            'status' => SignatureStatus::REFUSED,
        ]);

        // Notify admin/owner via relationship
        if ($signature->user) {
            Notification::send($signature->user, new SignatureRefusedNotification($signature, $signer));
        }

        return $signer;
    }

    /**
     * Vérifie si la signature est toujours valide par rapport à l'état actuel du document.
     */
    public function verify(Signature $signature): bool
    {
        if ($signature->status !== SignatureStatus::SIGNED) {
            return false;
        }

        $currentChecksum = $this->generateChecksum($signature->signable);

        return hash_equals($signature->checksum, $currentChecksum);
    }

    /**
     * Dispatch la notification de complétion du workflow.
     */
    protected function dispatchCompletionNotification(Signature $signature): void
    {
        if ($signature->user) {
            Notification::send($signature->user, new SignatureCompletedNotification($signature));
        }
    }

    /**
     * Génère une empreinte unique (SHA-256) basée sur les attributs du modèle.
     */
    protected function generateChecksum(Model $model): string
    {
        return hash('sha256', json_encode($model->toArray()));
    }
}
