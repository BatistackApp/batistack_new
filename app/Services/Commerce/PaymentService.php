<?php

namespace App\Services\Commerce;

use App\Enums\Commerce\InvoiceStatus;
use App\Exceptions\Commerce\AllocationOverflowException;
use App\Models\Commerce\Payment;
use App\Models\Commerce\PaymentAllocation;
use DB;
use Illuminate\Database\Eloquent\Model;
use Log;

class PaymentService
{
    /**
     * Ventile (lettre) un montant d'un paiement sur une pièce comptable (Facture ou Situation).
     * @throws \Throwable
     */
    public function allocatePayment(Payment $payment, Model $payable, float $amountToAllocate): PaymentAllocation
    {
        return DB::transaction(function () use ($payment, $payable, $amountToAllocate) {
            // ← AJOUTER : Lock + Validation
            $lockedPayable = $payable->newQuery()->whereKey($payable->getKey())->lockForUpdate()->firstOrFail();
            $this->validateAllocation($lockedPayable, $amountToAllocate);

            // 1. Création de la ligne d'affectation
            $allocation = PaymentAllocation::create([
                'payment_id' => $payment->id,
                'payable_type' => $payable->getMorphClass(),
                'payable_id' => $payable->id,
                'allocated_amount' => $amountToAllocate,
            ]);

            // 2. Calcul du total lettré sur cette facture
            $totalAllocated = PaymentAllocation::where('payable_type', $payable->getMorphClass())
                ->where('payable_id', $payable->id)
                ->sum('allocated_amount');

            // 3. Définition du montant cible (TTC) selon le type de pièce
            $targetAmount = $payable->total_ttc ?? $payable->amount_ttc ?? 0;

            // 4. Si la facture est totalement payée (avec une tolérance de centimes)
            if ($totalAllocated >= ($targetAmount - 0.05)) {
                if (method_exists($payable, 'update')) {
                    $payable->update(['status' => InvoiceStatus::PAID]);
                    Log::info('Invoice PAID', ['invoice' => $payable->id]);
                }
            }

            return $allocation;
        });
    }

    /**
     * @throws AllocationOverflowException
     */
    private function validateAllocation(Model $payable, float $amount): void
    {
        $targetAmount = $payable->total_ttc ?? $payable->amount_ttc ?? $payable->total_ht ?? 0;
        $existing = PaymentAllocation::where('payable_type', $payable->getMorphClass())
            ->where('payable_id', $payable->id)
            ->sum('allocated_amount');

        $remaining = $targetAmount - $existing;

        if ($amount > $remaining + 0.05) {
            throw new AllocationOverflowException(
                "Cannot allocate {$amount}€ (only {$remaining}€ remaining)"
            );
        }
    }
}
