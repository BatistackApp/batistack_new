<?php

namespace App\Services\Commerce;

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Commerce\Payment;
use App\Models\Commerce\PaymentAllocation;
use DB;
use Illuminate\Database\Eloquent\Model;

class PaymentService
{
    /**
     * Ventile (lettre) un montant d'un paiement sur une pièce comptable (Facture ou Situation).
     * @throws \Throwable
     */
    public function allocatePayment(Payment $payment, Model $payable, float $amountToAllocate): PaymentAllocation
    {
        return DB::transaction(function () use ($payment, $payable, $amountToAllocate) {

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
                }
            }

            return $allocation;
        });
    }
}
