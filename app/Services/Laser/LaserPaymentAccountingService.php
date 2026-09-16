<?php

namespace App\Services\Laser;

use App\Enums\Accounting\JournalType;
use App\Enums\Commerce\PaymentType;
use App\Models\Accounting\EcritureComptable;
use App\Models\Commerce\Payment;
use App\Models\Commerce\PaymentAllocation;
use App\Models\Laser\LaserInvoice;
use App\Services\Accounting\EcritureComptableService;
use App\Services\Core\SettingService;
use Illuminate\Support\Facades\DB;

class LaserPaymentAccountingService
{
    public function __construct(
        private SettingService $settingService,
        private EcritureComptableService $ecritureService,
    ) {}

    public function syncAllocation(PaymentAllocation $allocation): void
    {
        $invoice = $allocation->payable;
        $payment = $allocation->payment;

        if (! $invoice instanceof LaserInvoice || $payment->type !== PaymentType::IN) {
            return;
        }

        DB::transaction(function () use ($allocation, $invoice, $payment) {
            $existing = EcritureComptable::query()
                ->where('reconcilable_type', $allocation->getMorphClass())
                ->where('reconcilable_id', $allocation->getKey())
                ->exists();

            if ($existing) {
                return;
            }

            $amount = (float) $allocation->allocated_amount;
            $date = $payment->payment_date?->toDateString() ?? now()->toDateString();
            $bankAccount = $this->settingService->get('laser_bank_account', '512000');
            $clientAccount = $this->settingService->get('customer_account', '411100');

            $base = [
                'date_ecriture' => $date,
                'date_piece' => $date,
                'journal_type' => JournalType::BANQUE,
                'numero_piece' => $payment->reference,
                'libelle' => 'Encaissement facture Laser '.$invoice->reference,
                'reconcilable_type' => $allocation->getMorphClass(),
                'reconcilable_id' => $allocation->getKey(),
            ];

            $this->ecritureService->createBalancedPair(
                array_merge($base, ['compte_numero' => $bankAccount, 'debit' => $amount]),
                array_merge($base, ['compte_numero' => $clientAccount, 'credit' => $amount]),
            );

            $this->lettrerIfFullyPaid($invoice);
        });
    }

    public function removeAllocation(PaymentAllocation $allocation): void
    {
        DB::transaction(function () use ($allocation) {
            $invoice = $allocation->payable;

            EcritureComptable::query()
                ->where('reconcilable_type', $allocation->getMorphClass())
                ->where('reconcilable_id', $allocation->getKey())
                ->delete();

            if (! $invoice instanceof LaserInvoice) {
                return;
            }

            $invoiceEntry = $this->invoiceEntry($invoice);
            if (! $invoiceEntry) {
                return;
            }

            if ($invoiceEntry->lettrage) {
                $this->ecritureService->dellettrer(collect([$invoiceEntry]));
            }

            // The caller deletes the allocation after this method. Exclude it
            // here so a remaining balance can be rebuilt immediately.
            $this->lettrerIfFullyPaid($invoice, $allocation->getKey());
        });
    }

    private function lettrerIfFullyPaid(LaserInvoice $invoice, ?int $excludedAllocationId = null): void
    {
        $invoiceEntry = $this->invoiceEntry($invoice);

        if (! $invoiceEntry || $invoiceEntry->lettrage) {
            return;
        }

        $allocationIds = PaymentAllocation::query()
            ->where('payable_type', $invoice->getMorphClass())
            ->where('payable_id', $invoice->getKey())
            ->when($excludedAllocationId, fn ($query) => $query->where('id', '!=', $excludedAllocationId))
            ->pluck('id');

        $paymentEntries = EcritureComptable::query()
            ->where('compte_numero', $invoiceEntry->compte_numero)
            ->where('reconcilable_type', (new PaymentAllocation)->getMorphClass())
            ->whereIn('reconcilable_id', $allocationIds)
            ->get();

        if (abs((float) $paymentEntries->sum('credit') - (float) $invoiceEntry->debit) > 0.01) {
            return;
        }

        $this->ecritureService->lettrer(
            collect([$invoiceEntry, ...$paymentEntries->all()]),
            'LET-'.$invoice->reference,
        );
    }

    private function invoiceEntry(LaserInvoice $invoice): ?EcritureComptable
    {
        return EcritureComptable::query()
            ->where('reconcilable_type', $invoice->getMorphClass())
            ->where('reconcilable_id', $invoice->getKey())
            ->where('compte_numero', $this->settingService->get('customer_account', '411100'))
            ->first();
    }
}
