<?php

namespace App\Services\Commerce;

use App\Enums\Commerce\PaymentMethod;
use App\Enums\Commerce\PaymentStatus;
use App\Enums\Commerce\PaymentType;
use App\Events\Commerce\PaymentCancelledEvent;
use App\Events\Commerce\PaymentRecordedEvent;
use App\Models\Commerce\Payment;
use App\Models\Tiers\ThirdParty;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentRecordingService
{
    /**
     * Enregistrer un nouveau paiement
     *
     * Exemple :
     * $paymentService = app(PaymentRecordingService::class);
     * $payment = $paymentService->recordPayment(
     *     third_party: $client,
     *     type: PaymentType::IN,              // Encaissement
     *     method: PaymentMethod::BANK_TRANSFER,
     *     amount: 5000.00,
     *     payment_date: now(),
     *     reference: 'VIR-2026-00123',
     *     notes: 'Virement client ABC'
     * );
     *
     * @param  ThirdParty  $third_party  Le client ou fournisseur
     * @param  PaymentType  $type  'in' (encaissement) ou 'out' (décaissement)
     * @param  PaymentMethod  $method  Moyen de paiement
     * @param  float  $amount  Montant du paiement
     * @param  Carbon  $payment_date  Date du paiement
     * @param  string|null  $reference  Numéro/référence du paiement (généré si null)
     * @param  string|null  $notes  Notes additionnelles
     * @return Payment Le paiement créé
     *
     * @throws \Exception Si validation échoue
     */
    public function recordPayment(
        ThirdParty $third_party,
        PaymentType $type,
        PaymentMethod $method,
        float $amount,
        Carbon $payment_date,
        ?string $reference = null,
        ?string $notes = null,
    ): Payment {
        return DB::transaction(function () use ($third_party, $type, $method, $amount, $payment_date, $reference, $notes) {

            // 1. Validation
            $this->validatePayment($third_party, $type, $method, $amount, $payment_date);

            // 2. Générer la référence si non fournie
            if (! $reference) {
                $reference = $this->generatePaymentReference($type, $method);
            }

            // 3. Vérifier que la référence est unique
            $this->validateUniqueReference($reference);

            // 4. Créer le paiement
            $payment = Payment::create([
                'third_party_id' => $third_party->id,
                'reference' => $reference,
                'type' => $type,
                'method' => $method,
                'status' => PaymentStatus::COMPLETED,
                'amount' => $amount,
                'payment_date' => $payment_date,
                'notes' => $notes,
            ]);

            // 5. Logger l'action
            Log::info('Payment recorded', [
                'payment_id' => $payment->id,
                'reference' => $payment->reference,
                'type' => $payment->type,
                'method' => $payment->method,
                'amount' => $payment->amount,
                'third_party_id' => $third_party->id,
                'third_party_name' => $third_party->name,
            ]);

            // 6. Dispatcher l'événement
            event(new PaymentRecordedEvent($payment));

            return $payment;
        });
    }

    /**
     * Enregistrer un paiement avec allocations directes
     *
     * Cas d'usage : Paiement qui paie immédiatement une ou plusieurs factures
     *
     * Exemple :
     * $payment = $paymentService->recordPaymentWithAllocations(
     *     third_party: $client,
     *     type: PaymentType::IN,
     *     method: PaymentMethod::BANK_TRANSFER,
     *     amount: 15000.00,
     *     payment_date: now(),
     *     allocations: [
     *         ['invoice' => $invoice1, 'amount' => 5000],
     *         ['invoice' => $invoice2, 'amount' => 6000],
     *         ['invoice' => $invoice3, 'amount' => 4000],
     *     ],
     * );
     *
     * @param  ThirdParty  $third_party  Le tiers
     * @param  PaymentType  $type  Type de paiement
     * @param  PaymentMethod  $method  Méthode
     * @param  float  $amount  Montant total
     * @param  Carbon  $payment_date  Date
     * @param  array  $allocations  Array d'allocations [['invoice' => Model, 'amount' => float]]
     * @param  string|null  $reference  Référence (auto-générée si null)
     * @param  string|null  $notes  Notes
     * @return Payment Le paiement créé avec allocations
     *
     * @throws \Exception
     */
    public function recordPaymentWithAllocations(
        ThirdParty $third_party,
        PaymentType $type,
        PaymentMethod $method,
        float $amount,
        Carbon $payment_date,
        array $allocations,
        ?string $reference = null,
        ?string $notes = null,
    ): Payment {
        return DB::transaction(function () use ($third_party, $type, $method, $amount, $payment_date, $allocations, $reference, $notes) {

            // 1. Créer le paiement d'abord
            $payment = $this->recordPayment(
                $third_party,
                $type,
                $method,
                $amount,
                $payment_date,
                $reference,
                $notes
            );

            // 2. Ventiler le paiement sur les factures
            $allocationService = app(PaymentService::class);
            $totalAllocated = 0;

            foreach ($allocations as $allocation) {
                $invoice = $allocation['invoice'];
                $allocAmount = $allocation['amount'];

                // Allouer via le service (qui gère le lock)
                $allocationService->allocatePayment($payment, $invoice, $allocAmount);

                $totalAllocated += $allocAmount;
            }

            // 3. Vérifier que tout le paiement a été alloué
            if (abs($totalAllocated - $amount) > 0.05) {
                throw new \Exception(
                    "Somme des allocations ({$totalAllocated}€) ≠ montant du paiement ({$amount}€)"
                );
            }

            Log::info('Payment recorded with allocations', [
                'payment_id' => $payment->id,
                'total_allocated' => $totalAllocated,
                'allocation_count' => count($allocations),
            ]);

            return $payment;
        });
    }

    /**
     * Mettre à jour un paiement existant
     *
     * Exemple :
     * $paymentService->updatePayment(
     *     $payment,
     *     amount: 6000.00,    // Modifier le montant
     *     notes: 'Correction'  // Modifier les notes
     * );
     *
     * @param  Payment  $payment  Le paiement à modifier
     * @param  float|null  $amount  Nouveau montant (null = ne pas modifier)
     * @param  Carbon|null  $date  Nouvelle date (null = ne pas modifier)
     * @param  string|null  $notes  Nouvelles notes (null = ne pas modifier)
     * @return Payment Le paiement modifié
     *
     * @throws \Exception Si le paiement ne peut pas être modifié
     */
    public function updatePayment(
        Payment $payment,
        ?float $amount = null,
        ?Carbon $date = null,
        ?string $notes = null,
    ): Payment {
        return DB::transaction(function () use ($payment, $amount, $date, $notes) {

            // Vérifier que le paiement peut être modifié
            if ($payment->status === PaymentStatus::FAILED) {
                throw new \Exception('Impossible de modifier un paiement annulé');
            }

            $original = $payment->toArray();

            // Valider les nouvelles valeurs
            if ($amount !== null && $amount <= 0) {
                throw new \Exception('Le montant doit être positif');
            }

            // Mettre à jour
            $data = [];
            if ($amount !== null) {
                $data['amount'] = $amount;
            }
            if ($date !== null) {
                $data['payment_date'] = $date;
            }
            if ($notes !== null) {
                $data['notes'] = $notes;
            }

            $payment->update($data);

            Log::info('Payment updated', [
                'payment_id' => $payment->id,
                'original' => $original,
                'updated' => $data,
            ]);

            return $payment;
        });
    }

    /**
     * Annuler un paiement et dé-lettrer toutes ses allocations
     *
     * Exemple :
     * $paymentService->cancelPayment($payment, 'Erreur de saisie');
     *
     * @param  Payment  $payment  Le paiement à annuler
     * @param  string  $reason  Raison de l'annulation
     * @return Payment Le paiement annulé
     *
     * @throws \Exception|\Throwable Si paiement déjà annulé
     */
    public function cancelPayment(Payment $payment, string $reason = ''): Payment
    {
        return DB::transaction(function () use ($payment, $reason) {

            // Vérifier l'état
            if ($payment->status === PaymentStatus::FAILED) {
                throw new \Exception('Le paiement est déjà annulé');
            }

            // Dé-lettrer tous les allocations
            $deallocatedCount = $payment->allocations()->count();
            foreach ($payment->allocations as $allocation) {
                // Récupérer la facture et dé-lettrer
                $payable = $allocation->payable;

                $allocation->delete();

                // Rétrogader la facture si nécessaire
                if (method_exists($payable, 'markAsUnpaid')) {
                    $payable->markAsUnpaid();
                }
            }

            // Annuler le paiement
            $payment->update([
                'status' => PaymentStatus::FAILED,
                'notes' => ($payment->notes ? $payment->notes."\n" : '')."Annulé : {$reason}",
            ]);

            Log::warning('Payment cancelled', [
                'payment_id' => $payment->id,
                'reason' => $reason,
                'deallocated_count' => $deallocatedCount,
            ]);

            event(new PaymentCancelledEvent($payment, $reason));

            return $payment;
        });
    }

    /**
     * Dupliquer un paiement (créer un paiement similaire)
     *
     * Utile pour les paiements récurrents
     *
     * @param  Payment  $payment  Le paiement à dupliquer
     * @param  Carbon|null  $new_payment_date  Nouvelle date (null = maintenant)
     * @return Payment Le nouveau paiement créé
     */
    public function duplicatePayment(Payment $payment, ?Carbon $new_payment_date = null): Payment
    {
        return $this->recordPayment(
            $payment->thirdParty,
            $payment->type,
            $payment->method,
            $payment->amount,
            $new_payment_date ?? now(),
            null, // Générer une nouvelle référence
            $payment->notes ? "Copie : {$payment->notes}" : 'Copie de paiement'
        );
    }

    // ==================== MÉTHODES PRIVÉES ====================

    /**
     * Valider un paiement avant enregistrement
     * @throws \Exception
     */
    private function validatePayment(
        ThirdParty $third_party,
        PaymentType $type,
        PaymentMethod $method,
        float $amount,
        Carbon $payment_date,
    ): void {
        // Montant positif
        if ($amount <= 0) {
            throw new \Exception('Le montant doit être > 0');
        }

        // Montant raisonnable (max 1 million)
        if ($amount > 1_000_000) {
            throw new \Exception('Montant dépasse la limite (1M€)');
        }

        // Tiers existe
        if (! $third_party->exists) {
            throw new \Exception('Le tiers n\'existe pas');
        }

        // Date pas dans le futur
        if ($payment_date->isFuture()) {
            throw new \Exception('La date du paiement ne peut pas être dans le futur');
        }

        // Date pas trop loin dans le passé (> 10 ans)
        if ($payment_date->diffInYears(now()) > 10) {
            throw new \Exception('La date du paiement est trop ancienne');
        }
    }

    /**
     * Générer une référence unique pour un paiement
     */
    private function generatePaymentReference(PaymentType $type, PaymentMethod $method): string
    {
        // Format : TYPE-YYYY-NNNNN
        // Exemple : IN-2026-00123 ou OUT-2026-00456

        $prefix = match ($type) {
            PaymentType::IN => 'IN',     // Encaissement
            PaymentType::OUT => 'OUT',   // Décaissement
            default => 'PAY',
        };

        $year = now()->year;

        // Trouver le prochain numéro séquentiel pour cette année/type
        $lastPayment = Payment::where('reference', 'like', "{$prefix}-{$year}-%")
            ->orderBy('created_at', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastPayment) {
            // Extraire le numéro et incrémenter
            preg_match('/(\d+)$/', $lastPayment->reference, $matches);
            $nextNumber = (int) $matches[1] + 1;
        }

        return sprintf('%s-%d-%05d', $prefix, $year, $nextNumber);
    }

    /**
     * Vérifier que la référence est unique
     */
    private function validateUniqueReference(string $reference): void
    {
        if (Payment::where('reference', $reference)->exists()) {
            throw new \Exception("La référence '{$reference}' existe déjà");
        }
    }
}
